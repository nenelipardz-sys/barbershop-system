<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use Database;

class History extends BaseController
{
    public function index()
    {
        /** @var \ClientUser $user */
        $user = $this->user;
        $profile = $user->profile();

        $visits = $profile->visits();
        $fav = $profile->favoriteService();
        $spent = $profile->totalSpent();
        $saved = (float) Database::value("SELECT COALESCE(SUM(discount_amount),0) FROM appointments WHERE client_id = ? AND status = 'completed'", [$profile->getId()]);
        $topBarber = Database::one(
            "SELECT u.name, u.avatar, COUNT(*) n FROM appointments a JOIN users u ON u.id = a.barber_id
             WHERE a.client_id = ? AND a.status = 'completed' GROUP BY u.id, u.name, u.avatar ORDER BY n DESC LIMIT 1",
            [$profile->getId()]
        );

        $pageTitle = 'Visit History';
        $active = 'history';
        return $this->render('client/history', get_defined_vars());
    }
}
