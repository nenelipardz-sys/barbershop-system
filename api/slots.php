<?php
/**
 * GET available time slots for a barber + service + date.
 */
require __DIR__ . '/../includes/bootstrap.php';

$user = Auth::user();
if (!$user) {
    json_response(['ok' => false, 'error' => 'Please sign in again.'], 401);
}

$barber = Barber::findBarber((int) input('barber_id'));
$service = Service::find((int) input('service_id'));
$date = valid_date(input('date'));
$exclude = (int) input('exclude') ?: null;

if (!$barber || !$barber->isActive()) {
    json_response(['ok' => false, 'error' => 'Please choose an available barber.']);
}
if (!$service) {
    json_response(['ok' => false, 'error' => 'Please choose a service.']);
}
if (!$date) {
    json_response(['ok' => false, 'error' => 'Please choose a valid date.']);
}
if ($date < date('Y-m-d')) {
    json_response(['ok' => false, 'error' => 'You cannot book a past date.']);
}
if (!Schedule::isShopOpen($date)) {
    json_response(['ok' => true, 'slots' => [], 'message' => 'The shop is closed on ' . date('l', strtotime($date)) . 's.']);
}
if (!$barber->worksOn($date)) {
    json_response(['ok' => true, 'slots' => [], 'message' => $barber->getName() . ' is not working on ' . date('l', strtotime($date)) . 's.']);
}

json_response(['ok' => true, 'slots' => Schedule::availableSlots($barber, $service, $date, $exclude)]);
