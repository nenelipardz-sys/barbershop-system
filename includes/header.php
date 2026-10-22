<?php
/**
 * App layout — header + sidebar. Expects $user (User), $pageTitle and $active (nav key).
 */
$role = $user->getRole();
$unread = Notification::unreadCount($user->getId());
$notifications = Notification::forUser($user->getId(), 8);
$profileUrl = ['owner' => 'owner/settings.php', 'staff' => 'staff/profile.php', 'client' => 'client/profile.php'][$role];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle ?? 'Dashboard') ?> · Cut &amp; Schedule</title>
  <link rel="icon" href="<?= asset('assets/img/logo-monogram.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Parisienne&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <script>
    // Scale the desktop layout down on laptop screens before the first paint (see fitScreen() in app.js).
    (function () {
      var w = window.innerWidth, z = w >= 992 ? Math.min(1, Math.max(0.8, w / 1536)) : 1;
      document.documentElement.style.setProperty('--z', z);
      if (z < 1) document.documentElement.style.zoom = z;
    })();
  </script>
</head>
<body class="app role-<?= e($role) ?>" data-base="<?= e(BASE_URL) ?>"<?= !empty($fitScreen) ? ' data-fit' : '' ?>>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= url($user->homeUrl()) ?>">
      <?= brand_emblem(58) ?>
      <span class="brand-name">CUT &amp; SCHEDULE</span>
      <span class="brand-sub">Barber Shop Management System</span>
    </a>
    <nav class="nav">
      <?php foreach (nav_items($role) as [$key, $label, $href, $ico]): ?>
        <a class="nav-link<?= ($active ?? '') === $key ? ' active' : '' ?>" href="<?= url($href) ?>">
          <?= icon($ico, '', 20) ?><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a class="sidebar-user" href="<?= url($profileUrl) ?>">
        <?= avatar($user->getName(), $user->getAvatar(), 42) ?>
        <span>
          <strong><?= e($user->getName()) ?></strong>
          <small><?= e($user->roleLabel()) ?></small>
        </span>
      </a>
      <a class="nav-link logout" href="<?= url('logout.php?t=' . csrf_token()) ?>"><?= icon('log-out', '', 20) ?><span>Log Out</span></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-close-sidebar></div>

  <div class="main">
    <header class="mobile-header">
      <a class="mh-brand" href="<?= url($user->homeUrl()) ?>">
        <?= brand_emblem(44) ?>
        <span class="mh-sep"></span>
        <span><strong>CUT &amp; SCHEDULE</strong><small><?= e(portal_label($role)) ?></small></span>
      </a>
      <button class="mh-menu" type="button" data-open-sidebar aria-label="Open menu"><?= icon('menu', '', 26) ?></button>
    </header>

    <div class="topbar">
      <div class="dropdown" data-dropdown>
        <button class="icon-btn bell" type="button" data-dropdown-toggle aria-label="Notifications">
          <?= icon('bell', '', 21) ?>
          <?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
        </button>
        <div class="dropdown-menu notif-menu">
          <div class="dm-head">
            <strong>Notifications</strong>
            <?php if ($unread): ?>
              <form method="post" action="<?= url('api/notifications.php') ?>">
                <?= csrf_field() ?><input type="hidden" name="action" value="read_all">
                <button class="link-btn" type="submit">Mark all as read</button>
              </form>
            <?php endif; ?>
          </div>
          <?php if (!$notifications): ?>
            <p class="empty-sm">You're all caught up.</p>
          <?php endif; ?>
          <?php foreach ($notifications as $n): ?>
            <a class="notif<?= $n['is_read'] ? '' : ' unread' ?>" href="<?= url('api/notifications.php?action=open&id=' . (int) $n['id']) ?>">
              <span class="notif-ico"><?= icon('bell', '', 16) ?></span>
              <span><strong><?= e($n['title']) ?></strong><small><?= e($n['message']) ?></small><em><?= e(time_ago($n['created_at'])) ?></em></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="dropdown" data-dropdown>
        <button class="avatar-btn" type="button" data-dropdown-toggle aria-label="Account menu">
          <?= avatar($user->getName(), $user->getAvatar(), 38) ?>
        </button>
        <div class="dropdown-menu user-menu">
          <div class="um-head">
            <?= avatar($user->getName(), $user->getAvatar(), 44) ?>
            <span><strong><?= e($user->getName()) ?></strong><small><?= e($user->getEmail()) ?></small></span>
          </div>
          <a href="<?= url($profileUrl) ?>"><?= icon($role === 'owner' ? 'settings' : 'user', '', 17) ?> <?= $role === 'owner' ? 'Settings' : 'My Profile' ?></a>
          <a href="<?= url($user->homeUrl()) ?>"><?= icon('home', '', 17) ?> Home</a>
          <a href="<?= url('logout.php?t=' . csrf_token()) ?>" class="danger"><?= icon('log-out', '', 17) ?> Log Out</a>
        </div>
      </div>
    </div>

    <main class="content">
      <div class="toasts" id="toasts">
        <?php foreach (flashes() as $f): ?>
          <div class="toast toast-<?= e($f['type']) ?>" role="alert">
            <?= icon($f['type'] === 'success' ? 'check-circle' : ($f['type'] === 'error' ? 'alert' : 'info'), '', 18) ?>
            <span><?= e($f['message']) ?></span>
            <button type="button" class="toast-x" aria-label="Dismiss"><?= icon('x', '', 16) ?></button>
          </div>
        <?php endforeach; ?>
      </div>
