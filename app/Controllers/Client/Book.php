<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use Appointment;
use Barber;
use Database;
use Fee;
use Notification;
use Service;
use Settings;
use ValidationException;

class Book extends BaseController
{
    public function index()
    {
        /** @var \ClientUser $user */
        $user = $this->user;
        $profile = $user->profile();

        if (is_post()) {
            try {
                // Selected services, in catalog order; they are done back-to-back with the same barber.
                $ids = array_map('intval', (array) ($_POST['service_ids'] ?? []));
                $selected = array_values(array_filter(Service::all(), fn($s) => in_array($s->id, $ids, true)));
                $errors = [];
                if (!$selected) $errors[] = 'Please choose at least one service.';
                if (!(int) input('barber_id')) $errors[] = 'Please choose your barber.';
                if (!valid_date(input('appointment_date'))) $errors[] = 'Please choose a valid date.';
                if (input('start_time') === '') $errors[] = 'Please choose an available time slot.';
                if ($errors) {
                    throw new ValidationException($errors);
                }

                // All services are saved together, or none at all if one of them conflicts.
                Database::begin();
                $start = input('start_time');
                $booked = [];
                foreach ($selected as $i => $service) {
                    // New bookings start as "pending" until the shop confirms them.
                    $appt = new Appointment($profile->getId(), (int) input('barber_id'), $service, input('appointment_date'), $start,
                        Fee::for(input('discount_type')), $i === 0 ? input('notes') : null, $user->getId());
                    $appt->book();
                    $booked[] = $appt;
                    $start = substr($appt->getEndTime(), 0, 5);
                }
                Database::commit();

                $first = $booked[0];
                $names = implode(' + ', array_map(fn($a) => $a->getService()->name, $booked));
                $when = fmt_date($first->getDate()) . ' at ' . fmt_time($first->getStartTime());
                Notification::toOwners('New booking request', $user->getName() . ' requested ' . $names . ' on ' . $when . '.', 'owner/appointments?status=pending');
                Notification::send($first->getBarberId(), 'New appointment assigned', $user->getName() . ' booked ' . $names . ' on ' . $when . '.', 'staff/appointments?tab=upcoming');
                flash('success', 'Booked! Your ' . $names . ' on ' . $when . ' is pending confirmation from the shop.');
                return go('client/appointments');
            } catch (ValidationException $e) {
                Database::rollBack();
                flash('error', $e->getErrors());
                keep_old($_POST);
                return go('client/book');
            }
        }

        $services = Service::all();
        $barbers = Barber::allActive();
        $favBarbers = array_map('intval', array_column(Database::all("SELECT ref_id FROM favorites WHERE client_id = ? AND fav_type = 'barber'", [$profile->getId()]), 'ref_id'));
        $selServices = array_map('intval', (array) old('service_ids', input('service') ? [input('service')] : ($profile->getPreferredServiceId() ? [$profile->getPreferredServiceId()] : [])));
        $selBarber = (int) old('barber_id', input('barber', $profile->getPreferredBarberId()));
        $selDate = valid_date(old('appointment_date'), date('Y-m-d'));
        $selDiscount = old('discount_type', $profile->getDiscountType());
        $openDays = implode(', ', array_map(fn($d) => day_names()[$d], Settings::openDays()));

        $pageTitle = 'Book Appointment';
        $active = 'book';
        return $this->render('client/book', get_defined_vars());
    }
}
