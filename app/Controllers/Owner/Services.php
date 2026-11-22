<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use Database;
use Notification;
use Service;
use ValidationException;

class Services extends BaseController
{
    public function index()
    {
        $user = $this->user;

        if (is_post()) {
            $action = input('action');
            try {
                $id = (int) input('service_id');
                $service = $id ? Service::find($id) : new Service();
                if (!$service) {
                    throw new ValidationException(['Service not found.']);
                }
                if ($action === 'save') {
                    $service->fill($_POST);
                    $errors = $service->validate();
                    if ($errors) {
                        throw new ValidationException($errors);
                    }
                    $image = upload_image('image', 'services');
                    if ($image) {
                        delete_upload($service->image);
                        $service->image = $image;
                    }
                    $service->save();
                    flash('success', $id ? 'Service updated.' : 'Service added to the catalog.');
                    if ($id) {
                        Notification::toOwners('Service updated', $service->name . ' details were updated.', 'owner/services');
                    }
                } elseif ($action === 'archive') {
                    $service->archive();
                    flash('success', $service->name . ' was archived. It is hidden from booking but kept in reports.');
                } elseif ($action === 'restore') {
                    $service->restore();
                    flash('success', $service->name . ' is available for booking again.');
                } elseif ($action === 'delete') {
                    if ($service->usageCount() > 0) {
                        throw new ValidationException([$service->name . ' is used by ' . $service->usageCount() . ' appointments. Archive it instead to keep your records.']);
                    }
                    delete_upload($service->image);
                    Database::query('DELETE FROM services WHERE id = ?', [$service->id]);
                    flash('success', 'Service deleted.');
                }
            } catch (ValidationException $e) {
                flash('error', $e->getErrors());
                if ($action === 'save') {
                    keep_old($_POST + ['_modal' => 'serviceModal']);
                }
            }
            return go('owner/services' . (input('tab') ? '?tab=' . urlencode(input('tab')) : ''));
        }

        $tab = in_array(input('tab'), ['archived', 'all'], true) ? input('tab') : 'active';
        $all = Service::all(false);
        $list = array_values(array_filter($all, fn($s) => $tab === 'all' || $s->status === $tab));
        $usage = [];
        foreach (Database::all("SELECT service_id, COUNT(*) n, SUM(status = 'completed' AND appointment_date >= ?) month FROM appointments GROUP BY service_id", [date('Y-m-01')]) as $r) {
            $usage[(int) $r['service_id']] = $r;
        }
        $countActive = count(array_filter($all, fn($s) => $s->isActive()));
        $reopen = old('_modal') === 'serviceModal';

        $pageTitle = 'Services';
        $active = 'services';
        return $this->render('owner/services', get_defined_vars());
    }
}
