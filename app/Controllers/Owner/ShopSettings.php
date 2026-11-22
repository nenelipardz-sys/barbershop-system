<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use Settings;

/**
 * Owner settings page (owner/settings): shop details, opening hours and the owner's own account.
 */
class ShopSettings extends BaseController
{
    public function index()
    {
        $user = $this->user;

        if (is_post()) {
            if (!$this->handleProfileActions($user) && input('action') === 'shop') {
                $errors = [];
                $open = input('open_time');
                $close = input('close_time');
                $days = array_map('intval', (array) ($_POST['open_days'] ?? []));
                $interval = (int) input('slot_interval');
                if (input('shop_name') === '') $errors[] = 'Shop name is required.';
                if (!preg_match('/^\d{2}:\d{2}$/', $open) || !preg_match('/^\d{2}:\d{2}$/', $close) || $open >= $close) $errors[] = 'Closing time must be after opening time.';
                if (!$days) $errors[] = 'Select at least one open day.';
                if (!in_array($interval, [15, 20, 30, 45, 60], true)) $errors[] = 'Choose a valid time-slot interval.';
                if (input('shop_email') !== '' && !filter_var(input('shop_email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid shop email.';
                if ($errors) {
                    flash('error', $errors);
                } else {
                    foreach (['shop_name', 'shop_tagline', 'shop_address', 'shop_phone', 'shop_email'] as $k) {
                        Settings::set($k, mb_substr(input($k), 0, 255));
                    }
                    Settings::set('open_time', $open);
                    Settings::set('close_time', $close);
                    Settings::set('open_days', implode(',', array_unique($days)));
                    Settings::set('slot_interval', (string) $interval);
                    flash('success', 'Shop settings saved.');
                }
            }
            return go('owner/settings');
        }

        $s = Settings::all();
        $openDays = Settings::openDays();

        $pageTitle = 'Settings';
        $active = 'settings';
        return $this->render('owner/settings', get_defined_vars());
    }
}
