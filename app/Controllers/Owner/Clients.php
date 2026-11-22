<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use Barber;
use ClientProfile;
use Database;
use Service;
use User;
use ValidationException;

class Clients extends BaseController
{
    public function index()
    {
        $user = $this->user;

        if (is_post()) {
            $action = input('action');
            try {
                if ($action === 'save') {
                    $id = (int) input('client_id');
                    $client = $id ? ClientProfile::find($id) : new ClientProfile();
                    if (!$client) {
                        throw new ValidationException(['That client no longer exists.']);
                    }
                    $client->fill($_POST);
                    $client->save();
                    if ($client->getUserId()) {
                        // Keep the login account's contact details in sync.
                        Database::query('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$client->getName(), $client->getPhone(), $client->getUserId()]);
                    }
                    flash('success', $id ? 'Client profile updated.' : 'Client added.');
                } elseif ($action === 'delete') {
                    $client = ClientProfile::find((int) input('client_id'));
                    if ($client) {
                        $userId = $client->getUserId();
                        $client->delete();
                        if ($userId) {
                            Database::query("DELETE FROM users WHERE id = ? AND role = 'client'", [$userId]);
                        }
                        flash('success', 'Client deleted.');
                    }
                }
            } catch (ValidationException $e) {
                flash('error', $e->getErrors());
                keep_old($_POST + ['_modal' => 'clientModal']);
            }
            return go_back('owner/clients');
        }

        $q = input('q');
        $rate = in_array(input('rate'), ['none', 'senior', 'pwd'], true) ? input('rate') : '';
        $type = in_array(input('type'), ['registered', 'walkin'], true) ? input('type') : '';
        $sort = input('sort') === 'recent' ? 'recent' : (input('sort') === 'visits' ? 'visits' : 'name');

        $where = ['1=1'];
        $params = [];
        if ($q !== '') { $where[] = '(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
        if ($rate) { $where[] = 'c.discount_type = ?'; $params[] = $rate; }
        if ($type === 'registered') { $where[] = 'c.user_id IS NOT NULL'; }
        if ($type === 'walkin') { $where[] = 'c.user_id IS NULL'; }
        $w = implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) FROM clients c WHERE $w", $params);
        [$offset, $pages, $page] = paginate($total, 15);
        $orderBy = ['name' => 'c.name ASC', 'recent' => 'last_visit DESC', 'visits' => 'visits DESC, c.name'][$sort];
        $rows = Database::all(
            "SELECT c.*, u.name AS barber_name, s.name AS service_name,
                    (SELECT COUNT(*) FROM appointments a WHERE a.client_id = c.id AND a.status = 'completed') AS visits,
                    (SELECT MAX(appointment_date) FROM appointments a WHERE a.client_id = c.id AND a.status = 'completed') AS last_visit
             FROM clients c LEFT JOIN users u ON u.id = c.preferred_barber_id LEFT JOIN services s ON s.id = c.preferred_service_id
             WHERE $w ORDER BY $orderBy LIMIT $offset, 15",
            $params
        );
        $barbers = Barber::allActive();
        $services = Service::all();
        $reopen = old('_modal') === 'clientModal';

        $pageTitle = 'Clients';
        $active = 'clients';
        return $this->render('owner/clients', get_defined_vars());
    }

    /** One client's profile. Shared with staff (owner/client_view and staff/client_view). */
    public function view()
    {
        $user = $this->user;
        $isOwner = $user->getRole() === 'owner';
        $area = $isOwner ? 'owner' : 'staff';

        $client = ClientProfile::find((int) input('id'));
        if (!$client) {
            flash('error', 'Client not found.');
            return go("$area/clients");
        }

        if (is_post()) {
            try {
                if (input('action') === 'add_note') {
                    $client->addNote(input('note'), $user->getId());
                    flash('success', 'Note added.');
                } elseif (input('action') === 'delete_note') {
                    // Owners can remove any note; staff only their own.
                    $sql = 'DELETE FROM client_notes WHERE id = ? AND client_id = ?' . ($isOwner ? '' : ' AND staff_id = ' . $user->getId());
                    Database::query($sql, [(int) input('note_id'), $client->getId()]);
                    flash('success', 'Note removed.');
                } elseif ($isOwner && input('action') === 'save') {
                    $client->fill($_POST)->save();
                    flash('success', 'Client profile updated.');
                }
            } catch (ValidationException $e) {
                flash('error', $e->getErrors());
            }
            return go("$area/client_view?id=" . $client->getId());
        }

        $visits = $client->visits();
        $upcoming = Database::all(
            "SELECT a.*, s.name AS service_name, s.image AS service_image, u.name AS barber_name, c.name AS client_name, c.phone AS client_phone
             FROM appointments a JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id JOIN clients c ON c.id = a.client_id
             WHERE a.client_id = ? AND a.status IN ('pending','confirmed','in_progress') ORDER BY a.appointment_date, a.start_time",
            [$client->getId()]
        );
        $fav = $client->favoriteService();
        $prefBarber = $client->getPreferredBarberId() ? User::find($client->getPreferredBarberId()) : null;
        $prefService = $client->getPreferredServiceId() ? Service::find($client->getPreferredServiceId()) : null;
        $notes = $client->notesHistory();
        $barbers = Barber::allActive();
        $services = Service::all();

        $pageTitle = $client->getName();
        $active = 'clients';
        return $this->render('owner/client_view', get_defined_vars());
    }
}
