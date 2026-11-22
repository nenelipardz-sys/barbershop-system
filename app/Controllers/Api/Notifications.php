<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Notification;

/**
 * Notifications: open one (marks it read, then follows its link) or mark all as read.
 */
class Notifications extends BaseController
{
    public function index()
    {
        $user = $this->user;

        if (is_post()) {
            if (input('action') === 'read_all') {
                Notification::markAllRead($user->getId());
                flash('success', 'All notifications marked as read.');
            }
            return go_back($user->homeUrl());
        }

        if (input('action') === 'open') {
            $link = Notification::markRead((int) input('id'), $user->getId());
            return go($link ?: $user->homeUrl());
        }

        return go($user->homeUrl());
    }
}
