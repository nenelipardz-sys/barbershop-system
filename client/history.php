<?php
require __DIR__ . '/../includes/bootstrap.php';
/** @var ClientUser $user */
$user = Auth::requireRole('client');
$profile = $user->profile();

$visits = $profile->visits();
$fav = $profile->favoriteService();
$spent = $profile->totalSpent();
$saved = (float) Database::value("SELECT COALESCE(SUM(discount_amount),0) FROM appointments WHERE client_id = ? AND status = 'completed'", [$profile->getId()]);
$topBarber = Database::one(
    "SELECT u.name, u.avatar, COUNT(*) n FROM appointments a JOIN users u ON u.id = a.barber_id
     WHERE a.client_id = ? AND a.status = 'completed' GROUP BY u.id, u.name, u.avatar ORDER BY n DESC LIMIT 1",
    [$profile->getId()]
);

$pageTitle = 'Visit History';
$active = 'history';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Visit History</h1>
    <p>Every service you’ve had with us.</p>
  </div>
</div>

<section class="stats stats-4">
  <div class="card stat"><div class="stat-top"><span class="stat-ico gold"><?= icon('history', '', 18, 2) ?></span>Total Visits</div><div class="stat-value"><?= count($visits) ?></div><span class="stat-delta neutral">Since <?= e(fmt_date($profile->getCreatedAt(), 'M Y')) ?></span></div>
  <div class="card stat"><div class="stat-top"><span class="stat-ico amber"><?= icon('crown', '', 16) ?></span>Favorite Service</div><div class="stat-value" style="font-size:21px"><?= e($fav['name'] ?? '—') ?></div><span class="stat-delta neutral"><?= $fav ? (int) $fav['times'] . ' times' : 'Book your first visit' ?></span></div>
  <div class="card stat"><div class="stat-top"><span class="stat-ico blue"><?= icon('scissors', '', 18, 2) ?></span>Your Barber</div><div class="stat-value" style="font-size:21px"><?= e($topBarber['name'] ?? '—') ?></div><span class="stat-delta neutral"><?= $topBarber ? (int) $topBarber['n'] . ' visits' : '' ?></span></div>
  <div class="card stat"><div class="stat-top"><span class="stat-ico green"><?= icon('banknote', '', 18, 2) ?></span>Total Spent</div><div class="stat-value"><?= peso($spent) ?></div><span class="stat-delta <?= $saved > 0 ? 'up' : 'neutral' ?>"><?= $saved > 0 ? 'Saved ' . peso($saved) . ' with discounts' : 'Thank you for your loyalty' ?></span></div>
</section>

<section class="card">
  <div class="card-head"><h2>All Visits</h2></div>
  <?php if (!$visits): ?>
    <div class="empty"><?= icon('history', '', 36) ?><h3>No visits yet</h3><p>Completed appointments will appear here.</p><a class="btn btn-gold mt-16" href="<?= url('client/book.php') ?>">Book Appointment</a></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl tbl-responsive">
        <thead><tr><th>Date</th><th>Service</th><th>Barber</th><th>Rate</th><th class="text-right">Paid</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($visits as $v): ?>
            <tr>
              <td data-label="Date" class="nowrap"><strong><?= e(fmt_date($v['appointment_date'])) ?></strong><br><span class="t-muted"><?= fmt_time($v['start_time']) ?></span></td>
              <td data-label="Service"><div class="tbl-person" style="min-width:0"><?= service_img($v['service_image'], $v['service_name'], 'svc-img') ?><strong><?= e($v['service_name']) ?></strong></div></td>
              <td data-label="Barber"><div class="tbl-person" style="min-width:0"><?= avatar($v['barber_name'], $v['barber_avatar'], 30) ?><span><?= e($v['barber_name']) ?></span></div></td>
              <td data-label="Rate" class="t-muted"><?= e(discount_label($v['discount_type'])) ?></td>
              <td data-label="Paid" class="text-right t-strong"><?= peso($v['total_price']) ?></td>
              <td class="tbl-actions-cell"><div class="tbl-actions"><a class="btn btn-outline btn-sm" href="<?= url('client/book.php?service=' . (int) $v['service_id'] . '&barber=' . (int) $v['barber_id']) ?>"><?= icon('refresh', '', 14) ?> Book again</a></div></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<style>.tbl .svc-img { width: 40px; height: 40px; }</style>
<?php require __DIR__ . '/../includes/footer.php'; ?>
