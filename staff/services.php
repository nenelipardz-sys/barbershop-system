<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = Auth::requireRole('staff');

$services = Service::all();
$mine = [];
foreach (Database::all("SELECT service_id, COUNT(*) n FROM appointments WHERE barber_id = ? AND status = 'completed' AND appointment_date >= ? GROUP BY service_id", [$user->getId(), date('Y-m-01')]) as $r) {
    $mine[(int) $r['service_id']] = (int) $r['n'];
}

$pageTitle = 'Services';
$active = 'services';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Services</h1>
    <p>Current service catalog, prices and durations. Senior Citizen and PWD clients get 20% off.</p>
  </div>
</div>

<div class="svc-grid">
  <?php foreach ($services as $s): ?>
    <article class="svc-tile">
      <?= service_img($s->image, $s->name, 'svc-cover') ?>
      <div class="svc-tile-body">
        <h3><?= e($s->name) ?></h3>
        <p><?= e($s->description ?: '') ?></p>
        <div class="svc-meta">
          <span class="price"><?= peso($s->price) ?></span>
          <span class="muted"><?= icon('clock', '', 14) ?> <?= (int) $s->duration ?> mins</span>
        </div>
        <small class="muted">Senior/PWD: <?= peso($s->priceFor(new SeniorDiscount())) ?> · You did <?= $mine[$s->id] ?? 0 ?> this month</small>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
