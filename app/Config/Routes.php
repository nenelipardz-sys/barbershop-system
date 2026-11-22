<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Every page answers GET (show) and, where it has forms, POST (handle the form).
 * The "role" filter keeps each area to its own users.
 */
$routes->get('/', 'Home::index');

// Sign in, register, reset password
$routes->match(['get', 'post'], 'login', 'Account::login');
$routes->match(['get', 'post'], 'register', 'Account::register');
$routes->match(['get', 'post'], 'forgot_password', 'Account::forgotPassword');
$routes->get('logout', 'Account::logout');
$routes->match(['get', 'post'], 'setup', 'Setup::index');

// Owner
$routes->group('owner', ['namespace' => 'App\Controllers\Owner', 'filter' => 'role:owner'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->match(['get', 'post'], 'appointments', 'Appointments::index');
    $routes->match(['get', 'post'], 'clients', 'Clients::index');
    $routes->match(['get', 'post'], 'client_view', 'Clients::view');
    $routes->match(['get', 'post'], 'staff', 'Staff::index');
    $routes->match(['get', 'post'], 'services', 'Services::index');
    $routes->get('reports', 'Reports::index');
    $routes->match(['get', 'post'], 'settings', 'ShopSettings::index');
});

// Staff (client profiles and reports are shared with the owner)
$routes->group('staff', ['namespace' => 'App\Controllers\Staff', 'filter' => 'role:staff'], static function ($routes) {
    $routes->match(['get', 'post'], 'dashboard', 'Dashboard::index');
    $routes->match(['get', 'post'], 'schedule', 'MySchedule::index');
    $routes->match(['get', 'post'], 'appointments', 'Appointments::index');
    $routes->get('clients', 'Clients::index');
    $routes->match(['get', 'post'], 'client_view', '\App\Controllers\Owner\Clients::view');
    $routes->get('services', 'Services::index');
    $routes->get('reports', '\App\Controllers\Owner\Reports::index');
    $routes->match(['get', 'post'], 'profile', 'Profile::index');
});

// Client
$routes->group('client', ['namespace' => 'App\Controllers\Client', 'filter' => 'role:client'], static function ($routes) {
    $routes->match(['get', 'post'], 'home', 'Home::index');
    $routes->match(['get', 'post'], 'book', 'Book::index');
    $routes->match(['get', 'post'], 'appointments', 'Appointments::index');
    $routes->get('history', 'History::index');
    $routes->get('favorites', 'Favorites::index');
    $routes->match(['get', 'post'], 'profile', 'Profile::index');
    $routes->get('about', 'About::index');
});

// JSON endpoints used by app.js / landing.js (they check the user themselves)
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->post('favorites', 'Favorites::toggle');
    $routes->match(['get', 'post'], 'notifications', 'Notifications::index', ['filter' => 'role']);
    $routes->get('sales', 'Sales::index');
    $routes->get('slots', 'Slots::index');
    $routes->post('subscribe', 'Subscribe::index');
});
