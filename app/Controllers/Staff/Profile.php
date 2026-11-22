<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use Report;

class Profile extends BaseController
{
    public function index()
    {
        /** @var \Barber $user */
        $user = $this->user;

        if (is_post()) {
            $this->handleProfileActions($user);
            return go('staff/profile');
        }

        $days = day_names();
        $month = (new Report(date('Y-m-01'), date('Y-m-t'), null, $user->getId()))->summary();

        $pageTitle = 'My Profile';
        $active = '';
        return $this->render('staff/profile', get_defined_vars());
    }
}
