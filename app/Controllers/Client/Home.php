<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use Appointment;
use Database;
use Notification;
use Service;
use ValidationException;

class Home extends BaseController
{
    public function index()
    {
        /** @var \ClientUser $user */
        $user = $this->user;
        $clientId = $user->clientId();

        if (is_post()) {
            if (input('action') === 'cancel') {
                $appt = Appointment::find((int) input('appointment_id'));
                try {
                    if (!$appt || $appt->getClientId() !== $clientId) {
                        throw new ValidationException(['Appointment not found.']);
                    }
                    $appt->cancel();
                    Notification::toOwners('Booking cancelled', $user->getName() . ' cancelled ' . $appt->getService()->name . ' on ' . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . '.', 'owner/appointments');
                    Notification::send($appt->getBarberId(), 'Booking cancelled', $user->getName() . ' cancelled ' . $appt->getService()->name . ' on ' . fmt_date($appt->getDate()) . '.', 'staff/appointments');
                    flash('success', 'Your appointment was cancelled.');
                } catch (ValidationException $e) {
                    flash('error', $e->getErrors());
                }
            }
            return go('client/home');
        }

        $services = Service::all();
        $upcoming = Database::one(
            "SELECT a.*, s.name AS service_name, s.image AS service_image, u.name AS barber_name, u.avatar AS barber_avatar, c.name AS client_name, c.phone AS client_phone
             FROM appointments a JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id JOIN clients c ON c.id = a.client_id
             WHERE a.client_id = ? AND a.status IN ('pending','confirmed','in_progress')
               AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.end_time >= CURTIME()))
             ORDER BY a.appointment_date, a.start_time LIMIT 1",
            [$clientId]
        );
        $visits = $user->profile()->visits(3);

        $pageTitle = 'Home';
        $active = 'home';
        return $this->render('client/home', get_defined_vars());
    }
}
