<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
// Extra fields shown inside the shared profile form (partials/profile_forms).
$extraProfileFields = function () use ($profile, $barbers, $services) { ?>
  <div class="field">
    <label for="me-barber">Preferred barber</label>
    <select class="input" id="me-barber" name="preferred_barber_id">
      <option value="">No preference</option>
      <?php foreach ($barbers as $b): ?><option value="<?= $b->getId() ?>" <?= $profile->getPreferredBarberId() === $b->getId() ? 'selected' : '' ?>><?= e($b->getName()) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="me-service">Preferred service</label>
    <select class="input" id="me-service" name="preferred_service_id">
      <option value="">No preference</option>
      <?php foreach ($services as $s): ?><option value="<?= $s->id ?>" <?= $profile->getPreferredServiceId() === $s->id ? 'selected' : '' ?>><?= e($s->name) ?></option><?php endforeach; ?>
    </select>
  </div>
<?php };
?>
<div class="page-head">
  <div>
    <h1>Profile</h1>
    <p>Manage your details, preferences and password.</p>
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
      <p>Client since <?= e(fmt_date($user->getCreatedAt(), 'F Y')) ?></p>
      <div class="mini-stats">
        <div><strong><?= $profile->visitCount() ?></strong><small>Visits</small></div>
        <div><strong><?= peso($profile->totalSpent()) ?></strong><small>Spent</small></div>
        <div><strong><?= $profile->lastVisit() ? e(fmt_date($profile->lastVisit(), 'M j')) : '—' ?></strong><small>Last visit</small></div>
      </div>
      <dl class="kv">
        <dt>Rate</dt><dd><?= $profile->getDiscountType() === 'none' ? 'Regular' : e(discount_label($profile->getDiscountType())) . ' (20% off)' ?></dd>
        <dt>Username</dt><dd>@<?= e($user->getUsername()) ?></dd>
      </dl>
      <p class="muted mt-16" style="font-size:12.5px">Senior Citizen or PWD? Choose the discount when booking and show a valid ID at the shop.</p>
      <a class="btn btn-light btn-block mt-16 only-mobile" href="<?= url('logout?t=' . csrf_hash()) ?>"><?= icon('log-out', '', 16) ?> Log Out</a>
    </section>
  </div>
</div>
<?= $this->endSection() ?>
