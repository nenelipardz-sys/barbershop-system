<?php
/**
 * Cut & Schedule — Barber Shop Management System
 * Application configuration. Adjust the database credentials to match your XAMPP setup.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'cut_schedule');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'Cut & Schedule');
define('APP_TAGLINE', 'Barber Shop');
define('APP_TIMEZONE', 'Asia/Manila');

// Discount rate applied to Senior Citizen / PWD bookings (Philippine law: 20%).
define('DISCOUNT_RATE', 0.20);

// Remember-me cookie lifetime (days).
define('REMEMBER_DAYS', 30);

// Max upload size for avatars / service images (bytes).
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024);

// Base URL is detected automatically (e.g. "/cut-schedule"). Set it manually if detection fails.
define('BASE_URL_OVERRIDE', null);
