<?php
require __DIR__ . '/../includes/bootstrap.php';
/** @var ClientUser $user */
$user = Auth::requireRole('client');
$clientId = $user->clientId();

if (is_post()) {
    verify_csrf();
    try {
        $appt = Appointment::find((int) input('appointment_id'));
        if (!$appt || $appt->getClientId() !== $clientId) {
            throw new ValidationException(['Appointment not found.']);
        }
        $svc = $appt->getService()->name;
        if (input('action') === 'cancel') {
            $appt->cancel();
            Notification::toOwners('Booking cancelled', $user->getName() . " cancelled $svc on " . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . '.', 'owner/appointments.php');
            Notification::send($appt->getBarberId(), 'Booking cancelled', $user->getName() . " cancelled $svc on " . fmt_date($appt->getDate()) . '.', 'staff/appointments.php');
            flash('success', 'Your appointment was cancelled.');
        } elseif (input('action') === 'reschedule') {
            if (input('start_time') === '') {
                throw new ValidationException(['Please choose an available time slot.']);
            }
            $appt->reschedule(input('appointment_date'), input('start_time'), (int) input('barber_id'));
            $when = fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime());
            Notification::toOwners('Reschedule request', $user->getName() . " moved $svc to $when. Please confirm.", 'owner/appointments.php?status=pending');
            Notification::send($appt->getBarberId(), 'Appointment rescheduled', $user->getName() . " moved $svc to $when.", 'staff/appointments.php?tab=upcoming');
            flash('success', "Rescheduled to $when. Waiting for the shop to confirm.");
        }
    } catch (ValidationException $e) {
        flash('error', $e->getErrors());
    }
    redirect('client/appointments.php');
}

$tab = in_array(input('tab'), ['past', 'cancelled'], true) ? input('tab') : 'upcoming';
$conds = [
    'upcoming'  => "a.status IN ('pending','confirmed','in_progress') AND a.appointment_date >= CURDATE()",
    'past'      => "(a.status = 'completed' OR (a.status IN ('pending','confirmed') AND a.appointment_date < CURDATE()))",
    'cancelled' => "a.status = 'cancelled'",
];
$counts = [];
foreach ($conds as $k => $c) {
    $counts[$k] = (int) Database::value("SELECT COUNT(*) FROM appointments a WHERE a.client_id = ? AND $c", [$clientId]);
}
$rows = Database::all(
    "SELECT a.*, s.name AS service_name, s.image AS service_image, s.duration, u.name AS barber_name, u.avatar AS barber_avatar, c.name AS client_name, c.phone AS client_phone
     FROM appointments a JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id JOIN clients c ON c.id = a.client_id
     WHERE a.client_id = ? AND {$conds[$tab]}
     ORDER BY a.appointment_date " . ($tab === 'upcoming' ? 'ASC' : 'DESC') . ", a.start_time LIMIT 100",
    [$clientId]
);
$barbers = Barber::allActive();

$pageTitle = 'My Appointments';
$active = 'appointments';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>My Appointments</h1>
    <p>View, reschedule or cancel your bookings.</p>
  </div>
  <div class="page-actions"><a class="btn btn-gold" href="<?= url('client/book.php') ?>"><?= icon('calendar-plus', '', 17) ?> Book Appointment</a></div>
</div>

<nav class="tabs">
  <a class="<?= $tab === 'upcoming' ? 'active' : '' ?>" href="?tab=upcoming">Upcoming<span class="count"><?= $counts['upcoming'] ?></span></a>
  <a class="<?= $tab === 'past' ? 'active' : '' ?>" href="?tab=past">Past<span class="count"><?= $counts['past'] ?></span></a>
  <a class="<?= $tab === 'cancelled' ? 'active' : '' ?>" href="?tab=cancelled">Cancelled<span class="count"><?= $counts['cancelled'] ?></span></a>
</nav>

<?php if (!$rows): ?>
  <div class="card empty"><?= icon('calendar', '', 36) ?><h3>No <?= e($tab) ?> appointments</h3>
    <?php if ($tab === 'upcoming'): ?><a class="btn btn-gold mt-16" href="<?= url('client/book.php') ?>">Book your next cut</a><?php endif; ?></div>
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

<?php require __DIR__ . '/../includes/appointment_details_modal.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
