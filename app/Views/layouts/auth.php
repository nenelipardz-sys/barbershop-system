<?php
/**
 * Auth layout — dark left panel with the Lumié Talent logo, photo on the right, menu drawer.
 * Expects $authTitle; optional $compact (bool). Pages fill the "content" section.
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
      <a class="lt-logo" href="<?= url('login') ?>" aria-label="<?= e($shopName) ?>">
        <span class="lt-ring"><img src="<?= asset('assets/img/logo-monogram.png') ?>" alt=""></span>
        <span class="lt-name">LUMIÉ TALENT</span>
        <span class="lt-paris"><span>PARIS</span></span>
      </a>
      <p class="lt-tagline">More than just a haircut,<br>it’s a confidence.</p>
<?= $this->renderSection('content') ?>
<?php
$days = array_map(fn($d) => day_names()[$d], Settings::openDays());
?>
    </div>
  </section>
  <section class="auth-right" aria-hidden="true"></section>
</div>

<div class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer-backdrop" data-drawer-close></div>
  <aside class="drawer-panel" role="dialog" aria-label="Menu">
    <button class="drawer-close" type="button" data-drawer-close aria-label="Close menu"><?= icon('x', '', 22) ?></button>
    <h2>LUMIÉ TALENT</h2>
    <p class="d-sub"><?= e($tagline) ?></p>
    <nav class="d-links">
      <a href="<?= url('login') ?>"><?= icon('log-in', '', 18) ?> Sign In</a>
      <a href="<?= url('register') ?>"><?= icon('user-plus', '', 18) ?> Create an Account</a>
      <a href="<?= url('forgot_password') ?>"><?= icon('lock', '', 18) ?> Forgot Password</a>
    </nav>
    <h3>Our Services</h3>
    <?php foreach ($drawerServices as $s): ?>
      <div class="d-svc">
        <?php if (media_url($s->image)): ?><img src="<?= e(media_url($s->image)) ?>" alt=""><?php endif; ?>
        <div><strong><?= e($s->name) ?></strong><small><?= (int) $s->duration ?> mins</small></div>
        <b><?= peso($s->price) ?></b>
      </div>
    <?php endforeach; ?>
    <h3>Visit Us</h3>
    <p class="d-info"><?= icon('clock', '', 17) ?> <span><?= e(($days ? reset($days) . ' – ' . end($days) : 'By appointment')) ?>, <?= e(fmt_time(Settings::get('open_time', '08:00'))) ?> – <?= e(fmt_time(Settings::get('close_time', '19:00'))) ?></span></p>
    <p class="d-info"><?= icon('map-pin', '', 17) ?> <span><?= e(Settings::get('shop_address', '')) ?></span></p>
    <p class="d-info"><?= icon('phone', '', 17) ?> <span><?= e(Settings::get('shop_phone', '')) ?></span></p>
    <p class="d-info"><?= icon('mail', '', 17) ?> <span><?= e(Settings::get('shop_email', '')) ?></span></p>
  </aside>
</div>

<script>
(function () {
  var drawer = document.getElementById('drawer');
  // Shrink the logo + form so the whole panel fits the window height (desktop/laptop only).
  function fitAuth() {
    var inner = document.querySelector('.auth-inner'), panel = document.querySelector('.auth-left');
    inner.style.zoom = '';
    if (window.innerWidth <= 900) return;
    var cs = getComputedStyle(panel);
    var avail = window.innerHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
    var h = inner.getBoundingClientRect().height;
    if (h > avail) inner.style.zoom = Math.max(0.55, avail / h);
  }
  fitAuth();
  window.addEventListener('load', fitAuth);
  window.addEventListener('resize', fitAuth);
  if (document.fonts) document.fonts.ready.then(fitAuth);
  function toggle(open) { drawer.classList.toggle('open', open); drawer.setAttribute('aria-hidden', open ? 'false' : 'true'); }
  document.querySelectorAll('[data-drawer-open]').forEach(function (b) { b.addEventListener('click', function () { toggle(true); }); });
  document.querySelectorAll('[data-drawer-close]').forEach(function (b) { b.addEventListener('click', function () { toggle(false); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggle(false); });
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-toggle-password'));
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('on', show);
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () { var b = f.querySelector('.a-btn'); if (b) setTimeout(function () { b.disabled = true; }, 0); });
  });
})();
</script>
</body>
</html>
<?php clear_old(); ?>
