<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use Appointment;
use Barber;
use ClientProfile;
use Database;
use Fee;
use Notification;
use Service;
use ValidationException;

class Appointments extends BaseController
{
    public function index()
    {
        $user = $this->user;

        /* ---------- Actions ---------- */
        if (is_post()) {
            $action = input('action');
            try {
                if ($action === 'create') {
                    $clientId = input('client_id');
                    if ($clientId === 'new') {
                        $client = (new ClientProfile())->fill([
                            'name' => input('new_client_name'), 'phone' => input('new_client_phone'), 'discount_type' => input('discount_type'),
                        ]);
                        $clientId = $client->save();
                    } elseif (!(int) $clientId) {
                        throw new ValidationException(['Please select a client or add a new walk-in client.']);
                    }
                    $service = Service::find((int) input('service_id'));
                    if (!$service) {
                        throw new ValidationException(['Please select a service.']);
                    }
                    if (input('start_time') === '') {
                        throw new ValidationException(['Please choose an available time slot.']);
                    }
                    $appt = new Appointment((int) $clientId, (int) input('barber_id'), $service, input('appointment_date'), input('start_time'),
                        Fee::for(input('discount_type')), input('notes'), $user->getId());
                    $appt->book();
                    $appt->confirm(); // bookings made by the owner are approved right away
                    Notification::send($appt->getBarberId(), 'New appointment assigned',
                        $service->name . ' on ' . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . '.', 'staff/appointments');
                    Notification::toClient((int) $clientId, 'Appointment confirmed',
                        'Your ' . $service->name . ' on ' . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . ' is confirmed.');
                    flash('success', 'Appointment booked and confirmed.');
                } else {
                    $appt = Appointment::find((int) input('appointment_id'));
                    if (!$appt) {
                        throw new ValidationException(['That appointment no longer exists.']);
                    }
                    $label = $appt->getService()->name . ' on ' . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime());
                    switch ($action) {
                        case 'update':
                            $service = Service::find((int) input('service_id'));
                            if (!$service) {
                                throw new ValidationException(['Please select a service.']);
                            }
                            if (input('start_time') === '') {
                                throw new ValidationException(['Please choose an available time slot.']);
                            }
                            $appt->changeService($service);
                            $appt->setFee(Fee::for(input('discount_type')));
                            $appt->setNotes(input('notes'));
                            $appt->reschedule(input('appointment_date'), input('start_time'), (int) input('barber_id'));
                            $appt->confirm(); // the owner approves their own changes
                            Notification::toClient($appt->getClientId(), 'Appointment updated',
                                'Your appointment is now ' . $service->name . ' on ' . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . '.');
                            Notification::send($appt->getBarberId(), 'Appointment updated', $service->name . ' moved to ' . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . '.', 'staff/appointments');
                            flash('success', 'Appointment updated.');
                            break;
                        case 'confirm':
                            $appt->confirm();
                            Notification::toClient($appt->getClientId(), 'Appointment confirmed', "Your $label is confirmed. See you!");
                            Notification::send($appt->getBarberId(), 'Appointment confirmed', "$label was confirmed.", 'staff/appointments');
                            flash('success', 'Appointment confirmed.');
                            break;
                        case 'start':
                            $appt->start();
                            flash('success', 'Service started.');
                            break;
                        case 'complete':
                            $appt->complete();
                            flash('success', 'Service marked as completed. ' . peso($appt->getTotal()) . ' recorded.');
                            break;
                        case 'cancel':
                            $appt->cancel();
                            Notification::toClient($appt->getClientId(), 'Appointment cancelled', "Your $label was cancelled by the shop.");
                            Notification::send($appt->getBarberId(), 'Appointment cancelled', "$label was cancelled.", 'staff/appointments');
                            flash('success', 'Appointment cancelled.');
                            break;
                        case 'delete':
                            $appt->delete();
                            flash('success', 'Appointment deleted.');
                            break;
                        default:
                            throw new ValidationException(['Unknown action.']);
                    }
                }
            } catch (ValidationException $e) {
                flash('error', $e->getErrors());
            }
            return go_back('owner/appointments');
        }

        /* ---------- Filters ---------- */
        $status = array_key_exists(input('status'), Appointment::STATUSES) ? input('status') : '';
        $from = valid_date(input('from'), input('date') ? valid_date(input('date')) : null);
        $to = valid_date(input('to'), input('date') ? valid_date(input('date')) : null);
        if (!isset($_GET['from']) && !isset($_GET['date']) && !isset($_GET['to']) && !$status) {
            $from = date('Y-m-d'); // default: today onwards
        }
        $barberF = (int) input('barber');
        $serviceF = (int) input('service');
        $q = input('q');

        $where = ['1=1'];
        $params = [];
        if ($from) { $where[] = 'a.appointment_date >= ?'; $params[] = $from; }
        if ($to) { $where[] = 'a.appointment_date <= ?'; $params[] = $to; }
        if ($barberF) { $where[] = 'a.barber_id = ?'; $params[] = $barberF; }
        if ($serviceF) { $where[] = 'a.service_id = ?'; $params[] = $serviceF; }
        if ($q !== '') { $where[] = '(c.name LIKE ? OR c.phone LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        $baseWhere = implode(' AND ', $where);

        $counts = ['' => 0];
        foreach (Database::all("SELECT a.status, COUNT(*) n FROM appointments a JOIN clients c ON c.id = a.client_id WHERE $baseWhere GROUP BY a.status", $params) as $r) {
            $counts[$r['status']] = (int) $r['n'];
            $counts[''] += (int) $r['n'];
        }
        $listWhere = $baseWhere . ($status ? ' AND a.status = ?' : '');
        $listParams = $status ? array_merge($params, [$status]) : $params;
        $total = $status ? ($counts[$status] ?? 0) : $counts[''];
        [$offset, $pages, $page] = paginate($total, 15);
        $order = ($from && $from >= date('Y-m-d')) || $status === 'pending' ? 'ASC' : 'DESC';
        $rows = Database::all(
            "SELECT a.*, c.name AS client_name, c.phone AS client_phone, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image,
                    s.duration, u.name AS barber_name
             FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
             WHERE $listWhere ORDER BY a.appointment_date $order, a.start_time $order LIMIT $offset, 15",
            $listParams
        );

        $services = Service::all();
        $allServices = Service::all(false);
        $barbers = Barber::allActive();
        $clients = Database::all('SELECT id, name, phone, discount_type FROM clients ORDER BY name');
        $tabUrl = function (string $s) {
            $q = $_GET;
            unset($q['page']);
            $q['status'] = $s;
            if (!isset($q['from']) && !isset($q['date'])) {
                $q['from'] = date('Y-m-d');
            }
            return '?' . http_build_query($q);
        };

        $pageTitle = 'Appointments';
        $active = 'appointments';
        return $this->render('owner/appointments', get_defined_vars());
    }
}
