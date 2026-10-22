<?php
/**
 * POST toggle a favorite barber or service for the signed-in client.
 */
require __DIR__ . '/../includes/bootstrap.php';

$user = Auth::user();
if (!$user instanceof ClientUser) {
    json_response(['ok' => false, 'error' => 'Only clients can save favorites.'], 403);
}
if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 405);
}
verify_csrf();

$type = input('type');
$id = (int) input('id');
$valid = $type === 'barber'
    ? (bool) Database::value("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'staff'", [$id])
    : ($type === 'service' && (bool) Database::value('SELECT COUNT(*) FROM services WHERE id = ?', [$id]));
if (!$valid) {
    json_response(['ok' => false, 'error' => 'That item no longer exists.'], 404);
}

$clientId = $user->clientId();
$exists = Database::value('SELECT id FROM favorites WHERE client_id = ? AND fav_type = ? AND ref_id = ?', [$clientId, $type, $id]);
if ($exists) {
    Database::query('DELETE FROM favorites WHERE id = ?', [$exists]);
} else {
    Database::query('INSERT INTO favorites (client_id, fav_type, ref_id) VALUES (?, ?, ?)', [$clientId, $type, $id]);
}

json_response(['ok' => true, 'favorite' => !$exists, 'icon' => icon($exists ? 'heart' : 'heart-fill', '', 18)]);
