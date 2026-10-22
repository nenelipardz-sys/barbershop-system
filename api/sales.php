<?php
/**
 * GET chart data for the "Sales Overview" widget (owner: whole shop, staff: own earnings).
 */
require __DIR__ . '/../includes/bootstrap.php';

$user = Auth::user();
if (!$user || $user->getRole() === 'client') {
    json_response(['ok' => false, 'error' => 'Not allowed.'], 403);
}

$mode = in_array(input('mode'), ['daily', 'weekly', 'monthly'], true) ? input('mode') : 'daily';
$barberId = $user->getRole() === 'staff' ? $user->getId() : ((int) input('barber') ?: null);

json_response(['ok' => true] + Report::salesOverview($mode, $barberId));
