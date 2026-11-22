<?php

namespace App\Controllers;

/**
 * Home — the site root sends each user to their own dashboard (guests to the login page).
 */
class Home extends BaseController
{
    public function index()
    {
        return go($this->user ? $this->user->homeUrl() : 'login');
    }
}
