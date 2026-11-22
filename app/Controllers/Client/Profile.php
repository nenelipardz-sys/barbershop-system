<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use Barber;
use Service;

class Profile extends BaseController
{
    public function index()
    {
        /** @var \ClientUser $user */
        $user = $this->user;
        $profile = $user->profile();

        if (is_post()) {
            $this->handleProfileActions($user);
            return go('client/profile');
        }

        $barbers = Barber::allActive();
        $services = Service::all();

        $pageTitle = 'Profile';
        $active = 'profile';
        return $this->render('client/profile', get_defined_vars());
    }
}
