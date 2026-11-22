<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use Barber;
use Database;
use Service;

class Favorites extends BaseController
{
    public function index()
    {
        /** @var \ClientUser $user */
        $user = $this->user;
        $clientId = $user->clientId();

        $favIds = ['barber' => [], 'service' => []];
        foreach (Database::all('SELECT fav_type, ref_id FROM favorites WHERE client_id = ?', [$clientId]) as $f) {
            $favIds[$f['fav_type']][] = (int) $f['ref_id'];
        }
        $barbers = Barber::allActive();
        $services = Service::all();
        $visitsWith = [];
        foreach (Database::all("SELECT barber_id, COUNT(*) n FROM appointments WHERE client_id = ? AND status = 'completed' GROUP BY barber_id", [$clientId]) as $r) {
            $visitsWith[(int) $r['barber_id']] = (int) $r['n'];
        }

        $pageTitle = 'Favorites';
        $active = 'favorites';
        return $this->render('client/favorites', get_defined_vars());
    }
}
