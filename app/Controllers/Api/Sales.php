<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Report;

/**
 * GET chart data for the "Sales Overview" widget (owner: whole shop, staff: own earnings).
 */
class Sales extends BaseController
{
    public function index()
    {
        $user = $this->user;
        if (!$user || $user->getRole() === 'client') {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not allowed.']);
        }

        $mode = in_array(input('mode'), ['daily', 'weekly', 'monthly'], true) ? input('mode') : 'daily';
        $barberId = $user->getRole() === 'staff' ? $user->getId() : ((int) input('barber') ?: null);

        return $this->response->setJSON(['ok' => true] + Report::salesOverview($mode, $barberId));
    }
}
