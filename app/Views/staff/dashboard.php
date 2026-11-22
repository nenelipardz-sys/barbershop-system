<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1><?= e(greeting()) ?>, <?= e($user->getFirstName()) ?>!</h1>
    <p>Here’s your schedule for today.</p>
  </div>
  <div class="page-date">
    <?= date('M j, Y') ?><br>
    <a class="icon-btn" href="<?= url('staff/dashboard') ?>" title="Refresh" aria-label="Refresh"><?= icon('refresh', '', 17) ?></a>
  </div>
</div>

<section class="stats stats-3">
  <a class="card stat" href="<?= url('staff/appointments?date=' . $today) ?>">
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
  <a class="card stat" href="<?= url('staff/appointments?date=' . $today) ?>">
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
        <a class="chev" href="<?= url('staff/schedule') ?>" aria-label="Open my schedule"><?= icon('chevron-down', '', 18) ?></a>
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
        <a class="view-all" href="<?= url('staff/clients') ?>">View All</a>
      </div>
      <?php if (!$recentClients): ?><p class="muted">Clients you serve will appear here.</p><?php endif; ?>
      <div class="list">
        <?php foreach ($recentClients as $c): ?>
          <a class="list-item" href="<?= url('staff/client_view?id=' . (int) $c['id']) ?>">
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
        <a href="<?= url('staff/appointments?date=' . $today) ?>"><?= icon('clipboard-pen', '', 24, 2) ?>Update Service</a>
        <?php if ($next): ?>
          <button type="button" data-modal-open="noteModal" data-fill="<?= e(json_encode(['appointment_id' => $next['id'], 'd_client' => $next['client_name'], 'd_service' => $next['service_name']])) ?>"><?= icon('note', '', 24, 2) ?>Add Note</button>
        <?php else: ?>
          <a href="<?= url('staff/clients') ?>"><?= icon('note', '', 24, 2) ?>Add Note</a>
        <?php endif; ?>
        <a href="<?= url('staff/schedule') ?>"><?= icon('calendar-days', '', 24, 2) ?>View Schedule</a>
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

<?php include APPPATH . 'Views/partials/staff_modals.php'; ?>
<?= $this->endSection() ?>
