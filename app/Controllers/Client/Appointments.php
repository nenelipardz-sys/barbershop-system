<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use Appointment;
use Barber;
use Database;
use Notification;
use ValidationException;

class Appointments extends BaseController
{
    public function index()
    {
        /** @var \ClientUser $user */
        $user = $this->user;
        $clientId = $user->clientId();

        if (is_post()) {
            try {
                $appt = Appointment::find((int) input('appointment_id'));
                if (!$appt || $appt->getClientId() !== $clientId) {
                    throw new ValidationException(['Appointment not found.']);
                }
                $svc = $appt->getService()->name;
                if (input('action') === 'cancel') {
                    $appt->cancel();
                    Notification::toOwners('Booking cancelled', $user->getName() . " cancelled $svc on " . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . '.', 'owner/appointments');
                    Notification::send($appt->getBarberId(), 'Booking cancelled', $user->getName() . " cancelled $svc on " . fmt_date($appt->getDate()) . '.', 'staff/appointments');
                    flash('success', 'Your appointment was cancelled.');
                } elseif (input('action') === 'reschedule') {
                    if (input('start_time') === '') {
                        throw new ValidationException(['Please choose an available time slot.']);
                    }
                    $appt->reschedule(input('appointment_date'), input('start_time'), (int) input('barber_id'));
                    $when = fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime());
                    Notification::toOwners('Reschedule request', $user->getName() . " moved $svc to $when. Please confirm.", 'owner/appointments?status=pending');
                    Notification::send($appt->getBarberId(), 'Appointment rescheduled', $user->getName() . " moved $svc to $when.", 'staff/appointments?tab=upcoming');
                    flash('success', "Rescheduled to $when. Waiting for the shop to confirm.");
                }
            } catch (ValidationException $e) {
                flash('error', $e->getErrors());
            }
            return go('client/appointments');
        }

        $tab = in_array(input('tab'), ['past', 'cancelled'], true) ? input('tab') : 'upcoming';
        $conds = [
            'upcoming'  => "a.status IN ('pending','confirmed','in_progress') AND a.appointment_date >= CURDATE()",
            'past'      => "(a.status = 'completed' OR (a.status IN ('pending','confirmed') AND a.appointment_date < CURDATE()))",
            'cancelled' => "a.status = 'cancelled'",
        ];
        $counts = [];
        foreach ($conds as $k => $c) {
            $counts[$k] = (int) Database::value("SELECT COUNT(*) FROM appointments a WHERE a.client_id = ? AND $c", [$clientId]);
        }
        $rows = Database::all(
            "SELECT a.*, s.name AS service_name, s.image AS service_image, s.duration, u.name AS barber_name, u.avatar AS barber_avatar, c.name AS client_name, c.phone AS client_phone
             FROM appointments a JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id JOIN clients c ON c.id = a.client_id
             WHERE a.client_id = ? AND {$conds[$tab]}
             ORDER BY a.appointment_date " . ($tab === 'upcoming' ? 'ASC' : 'DESC') . ", a.start_time LIMIT 100",
            [$clientId]
        );
        $barbers = Barber::allActive();

        $pageTitle = 'My Appointments';
        $active = 'appointments';
        return $this->render('client/appointments', get_defined_vars());
    }
}
