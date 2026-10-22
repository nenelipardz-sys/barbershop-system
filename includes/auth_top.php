<?php
/**
 * Auth layout (top) — dark left panel with the Lumié Talent logo, photo on the right.
 * Expects $authTitle; optional $compact (bool).
 */
$compact = $compact ?? false;
$shopName = Settings::get('shop_name', 'Lumié Talent Paris');
$tagline = Settings::get('shop_tagline', 'More than just a haircut, it’s a confidence.');
$drawerServices = Service::all();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($authTitle) ?> · <?= e($shopName) ?></title>
  <link rel="icon" href="<?= asset('assets/img/logo-monogram.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,500;1,600&family=Lato:wght@400;700&family=Montserrat:wght@300;400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/auth.css') ?>">
</head>
<body class="auth">
<div class="auth-wrap">
  <section class="auth-left<?= $compact ? ' compact' : '' ?>">
    <button class="hamburger" type="button" aria-label="Open menu" data-drawer-open><i></i><i></i><i></i></button>
    <div class="auth-inner">
      <a class="lt-logo" href="<?= url('login.php') ?>" aria-label="<?= e($shopName) ?>">
        <span class="lt-ring"><img src="<?= asset('assets/img/logo-monogram.png') ?>" alt=""></span>
        <span class="lt-name">LUMIÉ TALENT</span>
        <span class="lt-paris"><span>PARIS</span></span>
      </a>
      <p class="lt-tagline">More than just a haircut,<br>it’s a confidence.</p>
