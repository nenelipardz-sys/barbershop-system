<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = Auth::requireRole('owner');

if (is_post()) {
    verify_csrf();
    $action = input('action');
    $pdo = Database::conn();
    try {
        if ($action === 'save') {
            $id = (int) input('staff_id');
            $data = [
                'name' => input('name'), 'username' => input('username'), 'email' => input('email'), 'phone' => input('phone'),
                'password' => (string) ($_POST['password'] ?? ''), 'password_confirm' => (string) ($_POST['password'] ?? ''),
            ];
            $details = [
                'position' => input('position'), 'salary' => input('salary', '0'), 'rating' => input('rating', '5'),
                'work_start' => input('work_start', '08:00'), 'work_end' => input('work_end', '19:00'),
                'work_days' => array_map('intval', (array) ($_POST['work_days'] ?? [])), 'bio' => input('bio'),
            ];
            $avatar = upload_image('avatar', 'avatars');
            $pdo->beginTransaction();
            if ($id) {
                $barber = Barber::findBarber($id);
                if (!$barber) {
                    throw new ValidationException(['Staff member not found.']);
                }
                $barber->updateProfile($data);
            } else {
                $id = User::create($data, 'staff'); // staff accounts are created by the owner only
                $barber = Barber::findBarber($id);
            }
            $barber->updateProfileDetails($details);
            if ($avatar) {
                delete_upload($barber->getAvatar());
                $barber->setAvatar($avatar);
            }
            $pdo->commit();
            flash('success', input('staff_id') ? 'Staff details updated.' : 'Staff account created. They can now sign in as ' . $data['username'] . '.');
        } elseif ($action === 'password') {
            $barber = Barber::findBarber((int) input('staff_id'));
            if ($barber) {
                $barber->setPassword((string) ($_POST['password'] ?? ''));
                flash('success', 'Password reset for ' . $barber->getName() . '.');
            }
        } elseif ($action === 'toggle') {
            $barber = Barber::findBarber((int) input('staff_id'));
            if ($barber) {
                $new = $barber->isActive() ? 'inactive' : 'active';
                Database::query('UPDATE users SET status = ?, remember_token = NULL WHERE id = ?', [$new, $barber->getId()]);
                flash('success', $barber->getName() . ($new === 'active' ? ' can sign in again.' : ' was deactivated and can no longer sign in or take bookings.'));
            }
        } elseif ($action === 'delete') {
            $barber = Barber::findBarber((int) input('staff_id'));
            if ($barber) {
                $used = (int) Database::value('SELECT COUNT(*) FROM appointments WHERE barber_id = ?', [$barber->getId()]);
                if ($used) {
                    throw new ValidationException([$barber->getName() . ' has ' . $used . ' appointment records. Deactivate the account instead to keep the history.']);
                }
                delete_upload($barber->getAvatar());
                Database::query('DELETE FROM users WHERE id = ?', [$barber->getId()]);
                flash('success', 'Staff account deleted.');
            }
        }
    } catch (ValidationException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $e->getErrors());
        if ($action === 'save') {
            keep_old($_POST + ['_modal' => 'staffModal']);
        }
    }
    redirect('owner/staff.php');
}

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$staff = array_map(fn($r) => new Barber($r), Database::all("SELECT * FROM users WHERE role = 'staff' ORDER BY status, id"));
$stats = [];
foreach (Database::all(
    "SELECT barber_id,
            SUM(appointment_date = ? AND status <> 'cancelled') AS today_cnt,
            SUM(CASE WHEN appointment_date = ? AND status <> 'cancelled' THEN TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 60 ELSE 0 END) AS today_min,
            SUM(appointment_date >= ? AND status = 'completed') AS month_cnt,
            SUM(CASE WHEN appointment_date >= ? AND status = 'completed' THEN total_price ELSE 0 END) AS month_total,
            SUM(appointment_date >= ? AND status IN ('pending','confirmed')) AS upcoming
     FROM appointments GROUP BY barber_id",
    [$today, $today, $monthStart, $monthStart, $today]
) as $r) {
    $stats[(int) $r['barber_id']] = $r;
}
$reopen = old('_modal') === 'staffModal';
$days = day_names();

$pageTitle = 'Staff';
$active = 'staff';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Staff</h1>
    <p>Manage barber accounts, working hours, workload and access.</p>
  </div>
  <div class="page-actions">
    <button class="btn btn-gold" type="button" data-modal-open="staffModal" data-reset data-title="Add Staff"><?= icon('user-plus', '', 18) ?> Add Staff</button>
  </div>
</div>

<?php if (!$staff): ?>
  <div class="card empty"><?= icon('users', '', 36) ?><h3>No staff yet</h3><p>Add your first barber to start taking bookings.</p></div>
<?php endif; ?>

<div class="staff-grid">
  <?php foreach ($staff as $b):
      $s = $stats[$b->getId()] ?? ['today_cnt' => 0, 'today_min' => 0, 'month_cnt' => 0, 'month_total' => 0, 'upcoming' => 0];
      $shiftMin = max(1, (strtotime($b->getWorkEnd()) - strtotime($b->getWorkStart())) / 60);
      $load = min(100, round($s['today_min'] / $shiftMin * 100));
      $fill = json_encode([
          'staff_id' => $b->getId(), 'name' => $b->getName(), 'username' => $b->getUsername(), 'email' => $b->getEmail(), 'phone' => $b->getPhone() ?? '',
          'position' => $b->getPosition(), 'salary' => $b->getSalary($user), 'rating' => $b->getRating(),
          'work_start' => substr($b->getWorkStart(), 0, 5), 'work_end' => substr($b->getWorkEnd(), 0, 5), 'work_days' => $b->getWorkDays(),
          'bio' => $b->getBio() ?? '', 'avatar_preview' => media_url($b->getAvatar()) ?? url('assets/img/logo-monogram.png'),
      ]);
      $dayList = implode(', ', array_map(fn($d) => $days[$d], $b->getWorkDays()));
  ?>
    <article class="card staff-card">
      <div class="sc-top">
        <?= avatar($b->getName(), $b->getAvatar(), 58) ?>
        <div style="flex:1;min-width:0">
          <strong><?= e($b->getName()) ?></strong>
          <small><?= e($b->getPosition()) ?> · @<?= e($b->getUsername()) ?></small>
          <span class="rating-inline"><?= icon('star', '', 14) ?> <?= number_format($b->getRating(), 1) ?></span>
        </div>
        <span class="badge badge-<?= e($b->getStatus()) ?>"><?= $b->isActive() ? 'Active' : 'Inactive' ?></span>
      </div>
      <div class="sc-body">
        <div class="sc-row"><span>Phone</span><span><?= e($b->getPhone() ?: '—') ?></span></div>
        <div class="sc-row"><span>Email</span><span><?= e($b->getEmail()) ?></span></div>
        <div class="sc-row"><span>Schedule</span><span><?= e(fmt_time($b->getWorkStart())) ?> – <?= e(fmt_time($b->getWorkEnd())) ?></span></div>
        <div class="sc-row"><span>Days</span><span><?= e($dayList ?: '—') ?></span></div>
        <div class="sc-row"><span>Monthly salary</span><span><?= peso($b->getSalary($user)) ?></span></div>
        <div class="sc-row"><span>This month</span><span><?= (int) $s['month_cnt'] ?> services · <?= peso($s['month_total']) ?></span></div>
        <div class="sc-row"><span>Upcoming</span><span><?= (int) $s['upcoming'] ?> booking<?= (int) $s['upcoming'] === 1 ? '' : 's' ?></span></div>
        <div class="sc-row"><span>Today’s workload</span><span><?= (int) $s['today_cnt'] ?> appt · <?= $load ?>%</span></div>
        <div class="workload"><i style="width:<?= $load ?>%"></i></div>
      </div>
      <div class="sc-foot">
        <button class="btn btn-outline btn-sm" type="button" data-modal-open="staffModal" data-title="Edit Staff" data-fill="<?= e($fill) ?>"><?= icon('pencil', '', 14) ?> Edit</button>
        <a class="btn btn-outline btn-sm" href="<?= url('owner/appointments.php?barber=' . $b->getId()) ?>"><?= icon('calendar-days', '', 14) ?> Schedule</a>
        <button class="btn btn-outline btn-sm" type="button" data-modal-open="pwModal" data-fill="<?= e(json_encode(['staff_id' => $b->getId(), 'pw_name' => $b->getName()])) ?>"><?= icon('lock', '', 14) ?> Password</button>
        <form class="inline" method="post" data-confirm="<?= $b->isActive() ? 'Deactivate ' . e($b->getName()) . '? They will not be able to sign in or receive bookings.' : 'Reactivate ' . e($b->getName()) . '?' ?>" <?= $b->isActive() ? 'data-confirm-style="danger" data-confirm-label="Deactivate"' : '' ?>>
          <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="staff_id" value="<?= $b->getId() ?>">
          <button class="btn btn-sm <?= $b->isActive() ? 'btn-danger' : 'btn-success' ?>" type="submit"><?= $b->isActive() ? 'Deactivate' : 'Activate' ?></button>
        </form>
        <form class="inline" method="post" data-confirm="Delete <?= e($b->getName()) ?>’s account permanently?" data-confirm-style="danger" data-confirm-label="Delete">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="staff_id" value="<?= $b->getId() ?>">
          <button class="icon-action danger" type="submit" title="Delete account"><?= icon('trash', '', 15) ?></button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<!-- Add / edit staff -->
<div class="modal" id="staffModal" <?= $reopen ? 'data-auto-open' : '' ?>>
  <div class="modal-backdrop"></div>
  <form class="modal-dialog lg" method="post" enctype="multipart/form-data">
    <div class="modal-head"><h3><?= $reopen && old('staff_id') ? 'Edit Staff' : 'Add Staff' ?></h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="staff_id" value="<?= e(old('staff_id')) ?>">
      <div class="upload-row mb-16">
        <img class="avatar" id="staffAvatar" data-src="avatar_preview" data-preview data-default="<?= url('assets/img/logo-monogram.png') ?>" src="<?= url('assets/img/logo-monogram.png') ?>" alt="" style="width:64px;height:64px;background:#0c1a2e;padding:4px">
        <div class="field"><label for="sm-avatar">Profile photo</label><input class="file-input" type="file" id="sm-avatar" name="avatar" accept="image/*" data-preview-target="staffAvatar"><span class="hint">JPG, PNG or WEBP · max 3 MB</span></div>
      </div>
      <div class="form-grid">
        <div class="field"><label for="sm-name">Full name</label><input class="input" id="sm-name" name="name" value="<?= e(old('name')) ?>" required></div>
        <div class="field"><label for="sm-position">Position</label><input class="input" id="sm-position" name="position" value="<?= e(old('position', 'Barber')) ?>" list="positions" required>
          <datalist id="positions"><option value="Barber"><option value="Senior Barber"><option value="Junior Barber"><option value="Master Barber"></datalist></div>
        <div class="field"><label for="sm-username">Username</label><input class="input" id="sm-username" name="username" value="<?= e(old('username')) ?>" required></div>
        <div class="field"><label for="sm-email">Email</label><input class="input" type="email" id="sm-email" name="email" value="<?= e(old('email')) ?>" required></div>
        <div class="field"><label for="sm-phone">Phone</label><input class="input" id="sm-phone" name="phone" value="<?= e(old('phone')) ?>"></div>
        <div class="field"><label for="sm-password">Password <span class="muted">(new accounts)</span></label><input class="input" type="password" id="sm-password" name="password" autocomplete="new-password" placeholder="At least 6 characters"></div>
        <div class="field"><label for="sm-salary">Monthly salary (₱) <span class="muted">— private</span></label><input class="input" type="number" min="0" step="100" id="sm-salary" name="salary" value="<?= e(old('salary', '15000')) ?>"></div>
        <div class="field"><label for="sm-rating">Rating</label><input class="input" type="number" min="0" max="5" step="0.1" id="sm-rating" name="rating" value="<?= e(old('rating', '5.0')) ?>"></div>
        <div class="field"><label for="sm-start">Shift start</label><input class="input" type="time" id="sm-start" name="work_start" value="<?= e(old('work_start', '08:00')) ?>"></div>
        <div class="field"><label for="sm-end">Shift end</label><input class="input" type="time" id="sm-end" name="work_end" value="<?= e(old('work_end', '19:00')) ?>"></div>
        <div class="field span-2">
          <span class="label">Working days</span>
          <div class="days">
            <?php $oldDays = array_map('intval', (array) old('work_days', [1, 2, 3, 4, 5, 6])); foreach ($days as $n => $d): ?>
              <label><input type="checkbox" name="work_days[]" value="<?= $n ?>" <?= in_array($n, $oldDays, true) ? 'checked' : '' ?>><span><?= $d ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="field span-2"><label for="sm-bio">Specialty / bio</label><input class="input" id="sm-bio" name="bio" value="<?= e(old('bio')) ?>" maxlength="255" placeholder="e.g. Fades, tapers and classic cuts"></div>
      </div>
      <p class="hint muted mt-8">The password is required for new accounts only. Use “Password” on the staff card to reset it later.</p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-gold" type="submit"><?= icon('check', '', 17, 2.2) ?> Save Staff</button>
    </div>
  </form>
</div>

<!-- Reset password -->
<div class="modal" id="pwModal">
  <div class="modal-backdrop"></div>
  <form class="modal-dialog sm" method="post">
    <div class="modal-head"><h3>Reset Password</h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="password"><input type="hidden" name="staff_id" value="">
      <p class="muted mb-16">Set a new password for <strong data-text="pw_name"></strong>.</p>
      <div class="field"><label for="pw-new">New password</label><input class="input" type="password" id="pw-new" name="password" minlength="6" required autocomplete="new-password"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-gold" type="submit">Reset Password</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
