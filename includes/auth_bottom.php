<?php
/**
 * Auth layout (bottom) — closes the left panel, renders the photo panel and the menu drawer.
 */
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
      <a href="<?= url('login.php') ?>"><?= icon('log-in', '', 18) ?> Sign In</a>
      <a href="<?= url('register.php') ?>"><?= icon('user-plus', '', 18) ?> Create an Account</a>
      <a href="<?= url('forgot_password.php') ?>"><?= icon('lock', '', 18) ?> Forgot Password</a>
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
