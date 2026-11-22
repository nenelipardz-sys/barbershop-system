<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Database;
use Notification;

/**
 * POST newsletter sign-up from the landing page ("Stay in the know").
 */
class Subscribe extends BaseController
{
    public function index()
    {
        $name = mb_substr(input('name'), 0, 100);
        $email = strtolower(mb_substr(input('email'), 0, 150));
        if ($name === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please enter your name.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please enter a valid email address.']);
        }

        // Older installs may not have the table yet.
        Database::query(
            'CREATE TABLE IF NOT EXISTS subscribers (
               id INT AUTO_INCREMENT PRIMARY KEY,
               name VARCHAR(100) NOT NULL,
               email VARCHAR(150) NOT NULL UNIQUE,
               created_at DATETIME DEFAULT CURRENT_TIMESTAMP
             ) ENGINE=InnoDB'
        );

        if (Database::value('SELECT COUNT(*) FROM subscribers WHERE email = ?', [$email])) {
            return $this->response->setJSON(['ok' => true, 'message' => 'You are already subscribed — thank you!']);
        }
        Database::query('INSERT INTO subscribers (name, email) VALUES (?, ?)', [$name, $email]);
        Notification::toOwners('New subscriber', $name . ' (' . $email . ') subscribed to shop updates.', null);
        return $this->response->setJSON(['ok' => true, 'message' => 'Thank you! You will be the first to know about our promos and events.']);
    }
}
