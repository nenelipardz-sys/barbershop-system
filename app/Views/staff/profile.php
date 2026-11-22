<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1>My Profile</h1>
    <p>Update your contact details, photo and password.</p>
  </div>
</div>

<div class="dash">
  <div class="stack">
    <?php include APPPATH . 'Views/partials/profile_forms.php'; ?>
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
<?= $this->endSection() ?>
