<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/profile_actions.php';
/** @var Barber $user */
$user = Auth::requireRole('staff');

if (is_post()) {
    verify_csrf();
    handle_profile_actions($user);
    redirect('staff/profile.php');
}

$days = day_names();
$month = (new Report(date('Y-m-01'), date('Y-m-t'), null, $user->getId()))->summary();

$pageTitle = 'My Profile';
$active = '';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>My Profile</h1>
    <p>Update your contact details, photo and password.</p>
  </div>
</div>

<div class="dash">
  <div class="stack">
    <?php require __DIR__ . '/../includes/profile_forms.php'; ?>
  </div>
  <div class="stack">
    <section class="card profile-card">
      <?= avatar($user->getName(), $user->getAvatar(), 88) ?>
      <h2><?= e($user->getName()) ?></h2>
      <p><?= e($user->getPosition()) ?> · <span class="rating-inline"><?= icon('star', '', 14) ?> <?= number_format($user->getRating(), 1) ?></span></p>
      <div class="mini-stats">
        <div><strong><?= (int) $month['services'] ?></strong><small>Services this month</small></div>
        <div><strong><?= peso($month['earnings']) ?></strong><small>Earnings generated</small></div>
        <div><strong><?= (int) $month['clients'] ?></strong><small>Clients served</small></div>
      </div>
      <dl class="kv">
        <dt>Working hours</dt><dd><?= e(fmt_time($user->getWorkStart())) ?> – <?= e(fmt_time($user->getWorkEnd())) ?></dd>
        <dt>Working days</dt><dd><?= e(implode(', ', array_map(fn($d) => $days[$d], $user->getWorkDays()))) ?></dd>
        <dt>Specialty</dt><dd><?= e($user->getBio() ?: '—') ?></dd>
      </dl>
      <p class="muted mt-16">Working hours and position are managed by the shop owner.</p>
    </section>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
