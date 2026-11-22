<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Barber;
use Schedule;
use Service;

/**
 * GET available time slots for a barber + service + date.
 */
class Slots extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'error' => 'Please sign in again.']);
        }

        $barber = Barber::findBarber((int) input('barber_id'));

        // One service (service_id) or several booked back-to-back (service_ids=1,2,3).
        $service = null;
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) input('service_ids', input('service_id'))))));
        $picked = array_filter(array_map(fn($id) => Service::find($id), $ids));
        if (count($picked) === 1) {
            $service = reset($picked);
        } elseif ($picked) {
            $service = new Service();
            $service->name = implode(' + ', array_map(fn($s) => $s->name, $picked));
            $service->duration = array_sum(array_map(fn($s) => $s->duration, $picked));
        }
        $date = valid_date(input('date'));
        $exclude = (int) input('exclude') ?: null;

        if (!$barber || !$barber->isActive()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please choose an available barber.']);
        }
        if (!$service) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please choose a service.']);
        }
        if (!$date) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please choose a valid date.']);
        }
        if ($date < date('Y-m-d')) {
            return $this->response->setJSON(['ok' => false, 'error' => 'You cannot book a past date.']);
        }
        if (!Schedule::isShopOpen($date)) {
            return $this->response->setJSON(['ok' => true, 'slots' => [], 'message' => 'The shop is closed on ' . date('l', strtotime($date)) . 's.']);
        }
        if (!$barber->worksOn($date)) {
            return $this->response->setJSON(['ok' => true, 'slots' => [], 'message' => $barber->getName() . ' is not working on ' . date('l', strtotime($date)) . 's.']);
        }

        return $this->response->setJSON(['ok' => true, 'slots' => Schedule::availableSlots($barber, $service, $date, $exclude)]);
    }
}
