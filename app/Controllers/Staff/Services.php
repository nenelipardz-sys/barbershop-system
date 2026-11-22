<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use Database;
use Service;

class Services extends BaseController
{
    public function index()
    {
        $user = $this->user;

        $services = Service::all();
        $mine = [];
        foreach (Database::all("SELECT service_id, COUNT(*) n FROM appointments WHERE barber_id = ? AND status = 'completed' AND appointment_date >= ? GROUP BY service_id", [$user->getId(), date('Y-m-01')]) as $r) {
            $mine[(int) $r['service_id']] = (int) $r['n'];
        }

        $pageTitle = 'Services';
        $active = 'services';
        return $this->render('staff/services', get_defined_vars());
    }
}
