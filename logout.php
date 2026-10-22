<?php
require __DIR__ . '/includes/bootstrap.php';

// Logout links carry the CSRF token so other sites cannot sign users out.
if (hash_equals(csrf_token(), (string) ($_GET['t'] ?? ''))) {
    Auth::logout();
    session_start();
    session_regenerate_id(true);
    flash('success', 'You have been signed out.');
}
redirect('login.php');
