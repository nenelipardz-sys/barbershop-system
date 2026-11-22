<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use ClientUser;
use Database;

/**
 * POST toggle a favorite barber or service for the signed-in client.
 */
class Favorites extends BaseController
{
    public function toggle()
    {
        $user = $this->user;
        if (!$user instanceof ClientUser) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Only clients can save favorites.']);
        }

        $type = input('type');
        $id = (int) input('id');
        $valid = $type === 'barber'
            ? (bool) Database::value("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'staff'", [$id])
            : ($type === 'service' && (bool) Database::value('SELECT COUNT(*) FROM services WHERE id = ?', [$id]));
        if (!$valid) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'That item no longer exists.']);
        }

        $clientId = $user->clientId();
        $exists = Database::value('SELECT id FROM favorites WHERE client_id = ? AND fav_type = ? AND ref_id = ?', [$clientId, $type, $id]);
        if ($exists) {
            Database::query('DELETE FROM favorites WHERE id = ?', [$exists]);
        } else {
            Database::query('INSERT INTO favorites (client_id, fav_type, ref_id) VALUES (?, ?, ?)', [$clientId, $type, $id]);
        }

        return $this->response->setJSON(['ok' => true, 'favorite' => !$exists, 'icon' => icon($exists ? 'heart' : 'heart-fill', '', 18)]);
    }
}
