<?php
/**
 * Notifications: open one (marks it read, then follows its link) or mark all as read.
 */
require __DIR__ . '/../includes/bootstrap.php';

$user = Auth::requireRole();

if (is_post()) {
    verify_csrf();
    if (input('action') === 'read_all') {
        Notification::markAllRead($user->getId());
        flash('success', 'All notifications marked as read.');
    }
    back($user->homeUrl());
}

if (input('action') === 'open') {
    $link = Notification::markRead((int) input('id'), $user->getId());
    redirect($link ?: $user->homeUrl());
}

redirect($user->homeUrl());
