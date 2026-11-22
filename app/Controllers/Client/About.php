<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use Service;
use Settings;

/**
 * Shop landing page for signed-in clients (client/about).
 */
class About extends BaseController
{
    public function index()
    {
        helper('landing');

        /** @var \ClientUser $user */
        $user = $this->user;

        $services = Service::all();
        $shopName = Settings::get('shop_name', 'Lumié Talent Paris');
        $address = Settings::get('shop_address', '');
        $phone = Settings::get('shop_phone', '');
        $email = Settings::get('shop_email', '');
        $days = array_map(fn($d) => day_names()[$d], Settings::openDays());
        $hours = ($days ? reset($days) . ' – ' . end($days) : '') . ', ' . fmt_time(Settings::get('open_time', '08:00')) . ' – ' . fmt_time(Settings::get('close_time', '19:00'));

        $testimonials = [
            ['name' => 'Arizza Villareal', 'avatar' => 'assets/img/avatars/arizza.jpg', 'service' => 'Haircut', 'quote' => 'Mark always knows exactly what I want. My cut looks perfect every single time, and booking takes less than a minute.'],
            ['name' => 'Paulo Reyes', 'avatar' => 'assets/img/avatars/paulo.jpg', 'service' => 'Hot Towel Shave', 'quote' => 'No more waiting in line. I pick my barber and my time, walk in, and the hot towel shave is pure relaxation.'],
            ['name' => 'Jose Martinez', 'avatar' => null, 'service' => 'Haircut · Senior', 'quote' => 'Warm people, a spotless shop and an honest senior discount. This is the best barbershop I have been to in years.'],
            ['name' => 'Carlos Reyes', 'avatar' => null, 'service' => 'Beard Grooming', 'quote' => 'Great conversation, sharp line-ups and a beard trim that still looks clean two weeks later. Worth every peso.'],
        ];

        return $this->render('client/about', get_defined_vars());
    }
}
