<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1>My Schedule</h1>
    <p>Your assigned appointments for the week.</p>
  </div>
  <div class="page-actions">
    <a class="icon-action" href="?week=<?= date('Y-m-d', strtotime('-7 day', strtotime($week[0]))) ?>" aria-label="Previous week"><?= icon('chevron-left', '', 16) ?></a>
    <span class="report-range"><?= e(fmt_date($week[0], 'M j')) ?> – <?= e(fmt_date($week[6], 'M j, Y')) ?></span>
    <a class="icon-action" href="?week=<?= date('Y-m-d', strtotime('+7 day', strtotime($week[0]))) ?>" aria-label="Next week"><?= icon('chevron-right', '', 16) ?></a>
    <a class="btn btn-outline btn-sm" href="<?= url('staff/schedule') ?>">This week</a>
  </div>
</div>

<section class="stats stats-4">
  <div class="card stat"><div class="stat-top"><span class="stat-ico blue"><?= icon('calendar-days', '', 18, 2) ?></span>Appointments</div><div class="stat-value"><?= count($active_rows) ?></div><span class="stat-delta neutral">This week</span></div>
  <div class="card stat"><div class="stat-top"><span class="stat-ico green"><?= icon('check-circle', '', 18, 2) ?></span>Completed</div><div class="stat-value"><?= count(array_filter($rows, fn($r) => $r['status'] === 'completed')) ?></div><span class="stat-delta neutral">This week</span></div>
  <div class="card stat"><div class="stat-top"><span class="stat-ico gold"><?= icon('clock', '', 18, 2) ?></span>Working Hours</div><div class="stat-value" style="font-size:20px"><?= e(fmt_time($user->getWorkStart())) ?> – <?= e(fmt_time($user->getWorkEnd())) ?></div><span class="stat-delta neutral"><?= e(implode(', ', array_map(fn($d) => $days[$d], $user->getWorkDays()))) ?></span></div>
  <div class="card stat"><div class="stat-top"><span class="stat-ico amber"><?= icon('hourglass', '', 18, 2) ?></span>Workload</div><div class="stat-value"><?= round($bookedMin / $capacity * 100) ?>%</div><div class="workload"><i style="width:<?= min(100, round($bookedMin / $capacity * 100)) ?>%"></i></div></div>
</section>

<section class="card">
  <div class="week">
    <?php foreach ($week as $d):
        $isOff = !$user->worksOn($d) || !Schedule::isShopOpen($d);
    ?>
      <div class="week-day<?= $d === date('Y-m-d') ? ' today' : '' ?><?= $isOff ? ' off' : '' ?>">
        <header><strong><?= date('l', strtotime($d)) ?></strong><small><?= date('M j', strtotime($d)) ?> · <?= count(array_filter($byDay[$d] ?? [], fn($r) => $r['status'] !== 'cancelled')) ?> appt</small></header>
        <div class="wd-body">
          <?php if (empty($byDay[$d])): ?>
            <p class="wd-off"><?= $isOff ? 'Day off' : 'No bookings' ?></p>
          <?php endif; ?>
          <?php foreach ($byDay[$d] ?? [] as $a): ?>
            <button type="button" class="wd-appt <?= e($a['status']) ?>" style="text-align:left;border-top:0;border-right:0;border-bottom:0;cursor:pointer;font:inherit"
                    data-modal-open="staffApptModal" data-fill="<?= e(staff_fill($a, $notesMap[$a['client_id']] ?? '')) ?>">
              <strong><?= fmt_time($a['start_time']) ?> – <?= fmt_time($a['end_time']) ?></strong>
              <span><?= e($a['client_name']) ?></span><br>
              <span class="muted"><?= e($a['service_name']) ?> · <?= e(Appointment::statusLabel($a['status'], true)) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php include APPPATH . 'Views/partials/staff_modals.php'; ?>
<?= $this->endSection() ?>
