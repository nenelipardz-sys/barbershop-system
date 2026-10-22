<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/staff_actions.php';
/** @var Barber $user */
$user = Auth::requireRole('staff');

if (is_post()) {
    verify_csrf();
    handle_staff_appointment_action($user);
    redirect('staff/dashboard.php');
}

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$mine = $user->appointmentsOn($today); // overridden: only appointments assigned to this barber
$mine = array_values(array_filter($mine, fn($a) => $a['status'] !== 'cancelled'));
$phones = [];
foreach (Database::all('SELECT c.id, c.phone FROM clients c JOIN appointments a ON a.client_id = c.id WHERE a.barber_id = ? AND a.appointment_date = ?', [$user->getId(), $today]) as $r) {
    $phones[$r['id']] = $r['phone'];
}
foreach ($mine as &$a) {
    $a['client_phone'] = $phones[$a['client_id']] ?? '—';
}
unset($a);

$countYesterday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date = ? AND status <> 'cancelled'", [$user->getId(), $yesterday]);
$completed = count(array_filter($mine, fn($a) => $a['status'] === 'completed'));
$completedYesterday = (int) Database::value("SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date = ? AND status = 'completed'", [$user->getId(), $yesterday]);
$pendingCount = count(array_filter($mine, fn($a) => in_array($a['status'], ['pending', 'confirmed'], true)));
$diff = count($mine) - $countYesterday;

// Next client: whoever is in the chair, otherwise the next upcoming booking today.
$next = null;
foreach ($mine as $a) {
    if ($a['status'] === 'in_progress') { $next = $a; break; }
}
if (!$next) {
    foreach ($mine as $a) {
        if (in_array($a['status'], ['pending', 'confirmed'], true) && $a['end_time'] >= date('H:i:s')) { $next = $a; break; }
    }
}
if (!$next) {
    $next = Database::one(
        "SELECT a.*, c.name AS client_name, c.phone AS client_phone, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image, u.name AS barber_name
         FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
         WHERE a.barber_id = ? AND a.status IN ('pending','confirmed') AND (a.appointment_date > ? OR (a.appointment_date = ? AND a.start_time >= ?))
         ORDER BY a.appointment_date, a.start_time LIMIT 1",
        [$user->getId(), $today, $today, date('H:i:s')]
    );
}

$recentClients = Database::all(
    "SELECT c.id, c.name, c.avatar, COUNT(*) AS visits,
            SUBSTRING_INDEX(GROUP_CONCAT(s.name ORDER BY a.appointment_date DESC, a.start_time DESC), ',', 1) AS last_service,
            MAX(a.appointment_date) AS last_visit
     FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id
     WHERE a.barber_id = ? AND a.status = 'completed'
     GROUP BY c.id, c.name, c.avatar ORDER BY last_visit DESC, MAX(a.completed_at) DESC LIMIT 3",
    [$user->getId()]
);

$byService = [];
foreach ($mine as $a) {
    $byService[$a['service_name']] = ($byService[$a['service_name']] ?? 0) + 1;
}
arsort($byService);
$colors = ['#163a6b', '#d9a441', '#3f7fc4', '#2f9a55', '#8a6ad1', '#d0644a'];
$totalSvc = array_sum($byService);
$stops = [];
$acc = 0;
$i = 0;
foreach ($byService as $name => $n) {
    $start = $totalSvc ? $acc / $totalSvc * 360 : 0;
    $acc += $n;
    $end = $totalSvc ? $acc / $totalSvc * 360 : 0;
    $stops[] = $colors[$i++ % count($colors)] . " {$start}deg {$end}deg";
}
$donut = $stops ? 'conic-gradient(' . implode(', ', $stops) . ')' : 'conic-gradient(#ece6da 0deg 360deg)';
$notesMap = client_notes_map(array_merge(array_column($mine, 'client_id'), $next ? [$next['client_id']] : []));

$pageTitle = 'Dashboard';
$fitScreen = true; // whole dashboard fits on one screen, like the prototype
$active = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1><?= e(greeting()) ?>, <?= e($user->getFirstName()) ?>!</h1>
    <p>Here’s your schedule for today.</p>
  </div>
  <div class="page-date">
    <?= date('M j, Y') ?><br>
    <a class="icon-btn" href="<?= url('staff/dashboard.php') ?>" title="Refresh" aria-label="Refresh"><?= icon('refresh', '', 17) ?></a>
  </div>
</div>

<section class="stats stats-3">
  <a class="card stat" href="<?= url('staff/appointments.php?date=' . $today) ?>">
    <div class="stat-top"><span class="stat-ico blue"><?= icon('calendar-days', '', 18, 2) ?></span>Today’s Appointments</div>
    <div class="stat-value"><?= count($mine) ?></div>
    <span class="stat-delta <?= $diff < 0 ? 'down' : 'up' ?>"><?= ($diff >= 0 ? '+' : '') . $diff ?> from yesterday</span>
  </a>
  <div class="card stat">
    <div class="stat-top"><span class="stat-ico green"><?= icon('shield-check', '', 18, 2) ?></span>Completed Services</div>
    <div class="stat-value"><?= $completed ?></div>
    <?php $pc = pct_change($completed, $completedYesterday); ?>
    <span class="stat-delta <?= str_starts_with($pc, '-') ? 'down' : 'up' ?>"><?= e($pc) ?></span>
  </div>
  <a class="card stat" href="<?= url('staff/appointments.php?date=' . $today) ?>">
    <div class="stat-top"><span class="stat-ico amber"><?= icon('star', '', 17) ?></span>Pending</div>
    <div class="stat-value"><?= $pendingCount ?></div>
    <span class="stat-delta <?= $pendingCount ? 'warn' : 'neutral' ?>"><?= $pendingCount ? 'Needs attention' : 'All done for today' ?></span>
  </a>
</section>

<div class="dash">
  <div class="stack">
    <section class="card">
      <div class="card-head">
        <h2>My Schedule</h2>
        <a class="chev" href="<?= url('staff/schedule.php') ?>" aria-label="Open my schedule"><?= icon('chevron-down', '', 18) ?></a>
      </div>
      <?php if (!$mine): ?>
        <div class="empty"><?= icon('calendar', '', 34) ?><h3>No appointments today</h3><p>Enjoy the breather — new bookings will appear here.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="tbl tbl-responsive">
            <thead><tr><th>Time</th><th>Client Name</th><th>Service</th><th>Status</th><th class="text-right"></th></tr></thead>
            <tbody>
              <?php foreach ($mine as $a): ?>
                <tr>
                  <td class="t-time" data-label="Time"><?= fmt_time($a['start_time'], true) ?></td>
                  <td data-label="Client"><?= e($a['client_name']) ?></td>
                  <td class="t-muted" data-label="Service"><?= e($a['service_name']) ?></td>
                  <td data-label="Status"><?= status_badge($a['status'], true) ?></td>
                  <td class="tbl-actions-cell"><div class="tbl-actions">
                    <button class="icon-action" type="button" title="Details" data-modal-open="staffApptModal" data-fill="<?= e(staff_fill($a, $notesMap[$a['client_id']] ?? '')) ?>"><?= icon('eye', '', 16) ?></button>
                    <button class="icon-action" type="button" title="Add note" data-modal-open="noteModal" data-fill="<?= e(json_encode(['appointment_id' => $a['id'], 'd_client' => $a['client_name'], 'd_service' => $a['service_name']])) ?>"><?= icon('note', '', 15) ?></button>
                  </div></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head">
        <h2>Recent Clients</h2>
        <a class="view-all" href="<?= url('staff/clients.php') ?>">View All</a>
      </div>
      <?php if (!$recentClients): ?><p class="muted">Clients you serve will appear here.</p><?php endif; ?>
      <div class="list">
        <?php foreach ($recentClients as $c): ?>
          <a class="list-item" href="<?= url('staff/client_view.php?id=' . (int) $c['id']) ?>">
            <?= avatar($c['name'], $c['avatar'], 38) ?>
            <div class="li-main" style="flex:1.2"><strong><?= e($c['name']) ?></strong></div>
            <div class="li-main"><small><?= (int) $c['visits'] ?> visit<?= $c['visits'] == 1 ? '' : 's' ?></small></div>
            <div class="li-main"><strong style="font-weight:400;color:var(--ink-2)"><?= e($c['last_service']) ?></strong></div>
            <div class="li-end"><small><?= e(fmt_date($c['last_visit'], 'M j')) ?></small></div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>Next Client</h2></div>
      <?php if (!$next): ?>
        <p class="muted">No more clients scheduled.</p>
      <?php else: ?>
        <div class="inner">
          <div class="next-client" style="padding:4px 0 14px">
            <?= avatar($next['client_name'], $next['client_avatar'], 64) ?>
            <div>
              <strong><?= e($next['client_name']) ?></strong>
              <small><?= e($next['service_name']) ?></small>
              <div class="time"><?= $next['appointment_date'] !== $today ? e(fmt_date($next['appointment_date'], 'M j')) . ' · ' : '' ?><?= fmt_time($next['start_time'], true) ?> <?= $next['status'] === 'in_progress' ? status_badge('in_progress') : '' ?></div>
            </div>
          </div>
          <button class="btn btn-gold btn-block" type="button" data-modal-open="staffApptModal" data-fill="<?= e(staff_fill($next, $notesMap[$next['client_id']] ?? '')) ?>">View Details</button>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Quick Actions</h2></div>
      <div class="quick">
        <a href="<?= url('staff/appointments.php?date=' . $today) ?>"><?= icon('clipboard-pen', '', 24, 2) ?>Update Service</a>
        <?php if ($next): ?>
          <button type="button" data-modal-open="noteModal" data-fill="<?= e(json_encode(['appointment_id' => $next['id'], 'd_client' => $next['client_name'], 'd_service' => $next['service_name']])) ?>"><?= icon('note', '', 24, 2) ?>Add Note</button>
        <?php else: ?>
          <a href="<?= url('staff/clients.php') ?>"><?= icon('note', '', 24, 2) ?>Add Note</a>
        <?php endif; ?>
        <a href="<?= url('staff/schedule.php') ?>"><?= icon('calendar-days', '', 24, 2) ?>View Schedule</a>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h2>Today’s Services</h2></div>
      <div class="donut-wrap">
        <div class="donut" style="background:<?= e($donut) ?>">
          <div class="donut-center"><strong><?= $totalSvc ?></strong><small>Total Services</small></div>
        </div>
        <ul class="legend">
          <?php $i = 0; foreach ($byService as $name => $n): ?>
            <li><i style="background:<?= $colors[$i++ % count($colors)] ?>"></i><span><?= e($name) ?></span><b><?= $n ?></b></li>
          <?php endforeach; ?>
          <?php if (!$byService): ?><li><span class="muted">No services yet today</span></li><?php endif; ?>
        </ul>
      </div>
    </section>
  </div>
</div>

<?php require __DIR__ . '/../includes/staff_modals.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
