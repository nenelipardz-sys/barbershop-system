<?php
require __DIR__ . '/includes/bootstrap.php';

$user = Auth::user();
redirect($user ? $user->homeUrl() : 'login.php');
