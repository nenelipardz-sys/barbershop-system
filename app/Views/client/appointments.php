<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1>My Appointments</h1>
    <p>View, reschedule or cancel your bookings.</p>
  </div>
  <div class="page-actions"><a class="btn btn-gold" href="<?= url('client/book') ?>"><?= icon('calendar-plus', '', 17) ?> Book Appointment</a></div>
</div>

<nav class="tabs">
  <a class="<?= $tab === 'upcoming' ? 'active' : '' ?>" href="?tab=upcoming">Upcoming<span class="count"><?= $counts['upcoming'] ?></span></a>
  <a class="<?= $tab === 'past' ? 'active' : '' ?>" href="?tab=past">Past<span class="count"><?= $counts['past'] ?></span></a>
  <a class="<?= $tab === 'cancelled' ? 'active' : '' ?>" href="?tab=cancelled">Cancelled<span class="count"><?= $counts['cancelled'] ?></span></a>
</nav>

<?php if (!$rows): ?>
  <div class="card empty"><?= icon('calendar', '', 36) ?><h3>No <?= e($tab) ?> appointments</h3>
    <?php if ($tab === 'upcoming'): ?><a class="btn btn-gold mt-16" href="<?= url('client/book') ?>">Book your next cut</a><?php endif; ?></div>
<?php endif; ?>

<div class="grid g-2">
  <?php foreach ($rows as $a):
      $canChange = in_array($a['status'], ['pending', 'confirmed'], true) && strtotime($a['appointment_date'] . ' ' . $a['start_time']) > time();
      $resFill = json_encode([
          'appointment_id' => $a['id'], 'service_id' => $a['service_id'], 'barber_id' => $a['barber_id'],
          'appointment_date' => $a['appointment_date'], 'start_time' => substr($a['start_time'], 0, 5), 'r_service' => $a['service_name'],
      ]);
  ?>
    <article class="card">
      <div class="appt-card">
        <?= service_img($a['service_image'], $a['service_name']) ?>
        <div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start">
            <strong><?= e(fmt_date($a['appointment_date'], 'D, M j, Y')) ?> • <?= fmt_time($a['start_time']) ?></strong>
            <?= status_badge($a['status']) ?>
          </div>
          <span class="svc"><?= e($a['service_name']) ?> · <?= (int) $a['duration'] ?> mins</span>
          <div class="by"><?= avatar($a['barber_name'], $a['barber_avatar'], 22) ?> <?= e($a['barber_name']) ?> (Barber)</div>
          <div class="by"><?= icon('tag', '', 14) ?> <?= peso($a['total_price']) ?><?= $a['discount_type'] !== 'none' ? ' · ' . e(discount_label($a['discount_type'])) . ' discount' : '' ?></div>
        </div>
      </div>
      <div class="appt-actions" style="grid-template-columns:repeat(<?= $canChange ? 3 : 1 ?>,1fr)">
        <button class="btn btn-light btn-sm" type="button" data-modal-open="detailsModal" data-fill="<?= e(appt_fill($a)) ?>">View Details</button>
        <?php if ($canChange): ?>
          <button class="btn btn-outline btn-sm" type="button" data-modal-open="rescheduleModal" data-fill="<?= e($resFill) ?>">Reschedule</button>
          <form method="post" style="display:grid" data-confirm="Cancel your <?= e($a['service_name']) ?> on <?= e(fmt_date($a['appointment_date'])) ?>?" data-confirm-style="danger" data-confirm-label="Cancel appointment">
            <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<div class="modal" id="rescheduleModal">
  <div class="modal-backdrop"></div>
  <form class="modal-dialog lg" method="post">
    <div class="modal-head"><h3>Reschedule · <span data-text="r_service"></span></h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="reschedule"><input type="hidden" name="appointment_id" value=""><input type="hidden" name="service_id" value="">
      <div class="form-grid">
        <div class="field">
          <label for="rs-barber">Barber</label>
          <select class="input" id="rs-barber" name="barber_id">
            <?php foreach ($barbers as $b): ?><option value="<?= $b->getId() ?>"><?= e($b->getName()) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="rs-date">New date</label>
          <input class="input" type="date" id="rs-date" name="appointment_date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+60 day')) ?>">
        </div>
        <div class="field span-2" data-slot-picker>
          <span class="label">Available times</span>
          <input type="hidden" name="start_time" value="">
          <p class="slots-msg"></p>
          <div class="slots"></div>
        </div>
      </div>
      <p class="muted mt-8">Rescheduled bookings go back to <b>pending</b> until the shop confirms.</p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Close</button>
      <button class="btn btn-gold" type="submit"><?= icon('calendar-check', '', 17) ?> Reschedule</button>
    </div>
  </form>
</div>

<?php include APPPATH . 'Views/partials/appointment_details_modal.php'; ?>
<?= $this->endSection() ?>
