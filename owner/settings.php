<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/profile_actions.php';
$user = Auth::requireRole('owner');

if (is_post()) {
    verify_csrf();
    if (!handle_profile_actions($user) && input('action') === 'shop') {
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
    redirect('owner/settings.php');
}

$s = Settings::all();
$openDays = Settings::openDays();

$pageTitle = 'Settings';
$active = 'settings';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Settings</h1>
    <p>Shop information, opening hours, booking rules and your account.</p>
  </div>
</div>

<div class="dash">
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>Shop Information</h2></div>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="shop">
        <div class="form-grid">
          <div class="field"><label for="shop_name">Shop name</label><input class="input" id="shop_name" name="shop_name" value="<?= e($s['shop_name'] ?? '') ?>" required></div>
          <div class="field"><label for="shop_tagline">Tagline</label><input class="input" id="shop_tagline" name="shop_tagline" value="<?= e($s['shop_tagline'] ?? '') ?>"></div>
          <div class="field span-2"><label for="shop_address">Address</label><input class="input" id="shop_address" name="shop_address" value="<?= e($s['shop_address'] ?? '') ?>"></div>
          <div class="field"><label for="shop_phone">Phone</label><input class="input" id="shop_phone" name="shop_phone" value="<?= e($s['shop_phone'] ?? '') ?>"></div>
          <div class="field"><label for="shop_email">Email</label><input class="input" type="email" id="shop_email" name="shop_email" value="<?= e($s['shop_email'] ?? '') ?>"></div>
          <div class="field"><label for="open_time">Opening time</label><input class="input" type="time" id="open_time" name="open_time" value="<?= e($s['open_time'] ?? '08:00') ?>" required></div>
          <div class="field"><label for="close_time">Closing time</label><input class="input" type="time" id="close_time" name="close_time" value="<?= e($s['close_time'] ?? '19:00') ?>" required></div>
          <div class="field span-2">
            <span class="label">Open days</span>
            <div class="days">
              <?php foreach (day_names() as $n => $d): ?>
                <label><input type="checkbox" name="open_days[]" value="<?= $n ?>" <?= in_array($n, $openDays, true) ? 'checked' : '' ?>><span><?= $d ?></span></label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="field">
            <label for="slot_interval">Booking time-slot interval</label>
            <select class="input" id="slot_interval" name="slot_interval">
              <?php foreach ([15, 20, 30, 45, 60] as $m): ?><option value="<?= $m ?>" <?= (int) ($s['slot_interval'] ?? 30) === $m ? 'selected' : '' ?>>Every <?= $m ?> minutes</option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="page-actions mt-16"><button class="btn btn-gold" type="submit"><?= icon('check', '', 17, 2.2) ?> Save Shop Settings</button></div>
      </form>
    </section>

    <section class="card">
      <div class="card-head"><h2>Access &amp; Roles</h2></div>
      <div class="table-wrap"><table class="tbl tbl-simple">
        <thead><tr><th>Role</th><th>How it’s created</th><th>Homepage</th><th>Access</th></tr></thead>
        <tbody>
          <tr><td><strong><?= icon('crown', 'gold', 16) ?> Owner</strong></td><td class="t-muted">Created by developer or securely assigned</td><td>Owner Dashboard</td><td class="t-muted">Full access</td></tr>
          <tr><td><strong><?= icon('scissors', 'gold', 16) ?> Staff</strong></td><td class="t-muted">Created by owner through Staff Management</td><td>Staff Dashboard</td><td class="t-muted">Assigned schedule, service status, client notes</td></tr>
          <tr><td><strong><?= icon('user', 'gold', 16) ?> Client</strong></td><td class="t-muted">Registers through the registration page</td><td>Client Homepage</td><td class="t-muted">Own bookings, history, favorites</td></tr>
        </tbody>
      </table></div>
      <p class="muted mt-16" style="display:flex;gap:8px;align-items:flex-start"><?= icon('shield-check', 'gold', 18) ?> The role is stored in the database and checked on login. Users cannot choose their role manually.</p>
    </section>
  </div>
  <div class="stack">
    <?php require __DIR__ . '/../includes/profile_forms.php'; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
