<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = Auth::requireRole('owner');

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$apptToday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status <> 'cancelled'", [$today]);
$apptYesterday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status <> 'cancelled'", [$yesterday]);
$clientsTotal = (int) Database::value('SELECT COUNT(*) FROM clients');
$clientsLastWeek = (int) Database::value('SELECT COUNT(*) FROM clients WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
$earnToday = (float) Database::value("SELECT COALESCE(SUM(total_price),0) FROM appointments WHERE appointment_date = ? AND status = 'completed'", [$today]);
$earnYesterday = (float) Database::value("SELECT COALESCE(SUM(total_price),0) FROM appointments WHERE appointment_date = ? AND status = 'completed'", [$yesterday]);
$pending = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE status = 'pending' AND appointment_date >= ?", [$today]);

$recent = $user->appointmentsOn($today);
$recent = array_values(array_filter($recent, fn($a) => $a['status'] !== 'cancelled'));

$topServices = Database::all(
    "SELECT s.id, s.name, s.image, COUNT(a.id) AS cnt FROM services s
     LEFT JOIN appointments a ON a.service_id = s.id AND a.status = 'completed' AND a.appointment_date >= ?
     GROUP BY s.id, s.name, s.image ORDER BY cnt DESC, s.sort_order LIMIT 4",
    [date('Y-m-01')]
);
$recentClients = Database::all(
    "SELECT c.id, c.name, c.avatar, COUNT(a.id) AS visits, MAX(a.appointment_date) AS last_visit
     FROM clients c JOIN appointments a ON a.client_id = c.id AND a.status = 'completed'
     GROUP BY c.id, c.name, c.avatar ORDER BY last_visit DESC, MAX(a.completed_at) DESC LIMIT 4"
);

$delta = function (string $label, string $suffix) {
    $cls = str_starts_with($label, '-') ? 'down' : 'up';
    return '<span class="stat-delta ' . $cls . '">' . e($label) . ' ' . e($suffix) . '</span>';
};

$pageTitle = 'Dashboard';
$fitScreen = true; // whole dashboard fits on one screen, like the prototype
$active = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1><?= e(greeting()) ?>, <?= e($user->getFirstName()) ?>!</h1>
    <p>Here’s what’s happening with your shop today.</p>
  </div>
  <div class="page-date"><?= date('M j, Y') ?></div>
</div>

<section class="stats stats-4">
  <a class="card stat" href="<?= url('owner/appointments.php?date=' . $today) ?>">
    <div class="stat-top"><span class="stat-ico gold"><?= icon('calendar-days', '', 18, 2) ?></span>Total Appointments</div>
    <div class="stat-value"><?= $apptToday ?></div>
    <?= $delta(pct_change($apptToday, $apptYesterday), 'from yesterday') ?>
  </a>
  <a class="card stat" href="<?= url('owner/clients.php') ?>">
    <div class="stat-top"><span class="stat-ico orange"><?= icon('users', '', 18, 2) ?></span>Total Clients</div>
    <div class="stat-value"><?= number_format($clientsTotal) ?></div>
    <?= $delta(pct_change($clientsTotal, $clientsLastWeek), 'from last week') ?>
  </a>
  <a class="card stat" href="<?= url('owner/reports.php?period=daily') ?>">
    <div class="stat-top"><span class="stat-ico green"><?= icon('banknote', '', 18, 2) ?></span>Today’s Earnings</div>
    <div class="stat-value"><?= peso($earnToday) ?></div>
    <?= $delta(pct_change($earnToday, $earnYesterday), 'from yesterday') ?>
  </a>
  <a class="card stat" href="<?= url('owner/appointments.php?status=pending') ?>">
    <div class="stat-top"><span class="stat-ico amber"><?= icon('hourglass', '', 18, 2) ?></span>Pending Bookings</div>
    <div class="stat-value"><?= $pending ?></div>
    <span class="stat-delta <?= $pending ? 'warn' : 'neutral' ?>"><?= $pending ? 'Needs attention' : 'All caught up' ?></span>
  </a>
</section>

<div class="dash">
  <div class="stack">
    <section class="card">
      <div class="card-head">
        <h2>Recent Appointments</h2>
        <a class="chev" href="<?= url('owner/appointments.php?date=' . $today) ?>" aria-label="View all appointments"><?= icon('chevron-right', '', 18) ?></a>
      </div>
      <?php if (!$recent): ?>
        <div class="empty"><?= icon('calendar', '', 34) ?><h3>No appointments today</h3><p>New bookings will show up here.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="tbl tbl-responsive">
            <thead><tr><th>Time</th><th>Client Name</th><th>Service</th><th>Barber</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach (array_slice($recent, 0, 5) as $a): ?>
                <tr>
                  <td class="t-time" data-label="Time"><?= fmt_time($a['start_time'], true) ?></td>
                  <td data-label="Client"><?= e($a['client_name']) ?></td>
                  <td class="t-muted" data-label="Service"><?= e($a['service_name']) ?></td>
                  <td class="t-muted" data-label="Barber"><?= e(explode(' ', $a['barber_name'])[0]) ?></td>
                  <td data-label="Status"><?= status_badge($a['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <div class="grid g-2">
      <section class="card">
        <div class="card-head">
          <h2>Top Services</h2>
          <a class="chev" href="<?= url('owner/reports.php?period=monthly') ?>" aria-label="Service report"><?= icon('chevron-right', '', 18) ?></a>
        </div>
        <div class="list">
          <?php foreach ($topServices as $s): ?>
            <div class="list-item">
              <?= avatar($s['name'], $s['image'], 34) ?>
              <div class="li-main"><strong><?= e($s['name']) ?></strong></div>
              <div class="li-end"><?= (int) $s['cnt'] ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
      <section class="card">
        <div class="card-head">
          <h2>Recent Clients</h2>
          <a class="view-all" href="<?= url('owner/clients.php') ?>">View All</a>
        </div>
        <div class="list">
          <?php foreach ($recentClients as $c): ?>
            <a class="list-item" href="<?= url('owner/client_view.php?id=' . (int) $c['id']) ?>">
              <?= avatar($c['name'], $c['avatar'], 34) ?>
              <div class="li-main"><strong><?= e($c['name']) ?></strong><small><?= (int) $c['visits'] ?> visit<?= $c['visits'] == 1 ? '' : 's' ?></small></div>
              <div class="li-end"><small><?= e(fmt_date($c['last_visit'], 'M j')) ?></small></div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </div>

  <div class="stack">
    <section class="card" data-sales>
      <div class="card-head">
        <h2>Sales Overview</h2>
        <a class="chev" href="<?= url('owner/reports.php') ?>" aria-label="Open reports"><?= icon('chevron-down', '', 18) ?></a>
      </div>
      <div class="chart-head">
        <div class="seg">
          <button type="button" data-mode="daily" class="active">Daily</button>
          <button type="button" data-mode="weekly">Weekly</button>
          <button type="button" data-mode="monthly">Monthly</button>
        </div>
      </div>
      <div class="chart-total"><?= peso($earnToday) ?></div>
      <div class="chart-cap">Today’s Sales</div>
      <div class="bars"></div>
    </section>

    <section class="card">
      <div class="card-head"><h2>Quick Actions</h2></div>
      <div class="quick">
        <a href="<?= url('owner/staff.php') ?>"><?= icon('users', '', 24, 2) ?>Manage Staff</a>
        <a href="<?= url('owner/services.php') ?>"><?= icon('clipboard', '', 24, 2) ?>Manage Services</a>
        <a href="<?= url('owner/reports.php') ?>"><?= icon('bar-chart', '', 24, 2.4) ?>View Reports</a>
      </div>
    </section>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
