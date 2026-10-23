<?php
require __DIR__ . '/../includes/bootstrap.php';
/** @var ClientUser $user */
$user = Auth::requireRole('client');
$clientId = $user->clientId();

if (is_post()) {
    verify_csrf();
    if (input('action') === 'cancel') {
        $appt = Appointment::find((int) input('appointment_id'));
        try {
            if (!$appt || $appt->getClientId() !== $clientId) {
                throw new ValidationException(['Appointment not found.']);
            }
            $appt->cancel();
            Notification::toOwners('Booking cancelled', $user->getName() . ' cancelled ' . $appt->getService()->name . ' on ' . fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime()) . '.', 'owner/appointments.php');
            Notification::send($appt->getBarberId(), 'Booking cancelled', $user->getName() . ' cancelled ' . $appt->getService()->name . ' on ' . fmt_date($appt->getDate()) . '.', 'staff/appointments.php');
            flash('success', 'Your appointment was cancelled.');
        } catch (ValidationException $e) {
            flash('error', $e->getErrors());
        }
    }
    redirect('client/home.php');
}

$services = Service::all();
$upcoming = Database::one(
    "SELECT a.*, s.name AS service_name, s.image AS service_image, u.name AS barber_name, u.avatar AS barber_avatar, c.name AS client_name, c.phone AS client_phone
     FROM appointments a JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id JOIN clients c ON c.id = a.client_id
     WHERE a.client_id = ? AND a.status IN ('pending','confirmed','in_progress')
       AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.end_time >= CURTIME()))
     ORDER BY a.appointment_date, a.start_time LIMIT 1",
    [$clientId]
);
$visits = $user->profile()->visits(3);

$pageTitle = 'Home';
$active = 'home';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Hello, <?= e($user->getFirstName()) ?>!</h1>
    <p>Book your next appointment.</p>
  </div>
  <div class="page-date only-desktop"><?= date('M j, Y') ?></div>
</div>

<!-- Desktop hero -->
<section class="hero only-desktop">
  <div class="hero-img" style="background-image:url('<?= asset('assets/img/hero-client.jpg') ?>')"></div>
  <div class="hero-body">
    <div class="hero-title">Look Good<br>Feel Good</div>
    <p class="hero-text">Quality cuts. Professional service.</p>
    <a class="btn btn-gold" href="<?= url('client/book.php') ?>">Book Appointment</a>
  </div>
</section>

<!-- Mobile: Book Now -->
<a class="btn btn-gold btn-block btn-lg only-mobile mb-16" href="<?= url('client/book.php') ?>" style="height:54px;font-size:17px"><?= icon('calendar-days', '', 22, 2) ?> Book Now</a>

<!-- Mobile: upcoming appointment first -->
<section class="card only-mobile mb-16">
  <div class="card-head"><h2>Upcoming Appointment</h2></div>
  <?php if ($upcoming): ?>
    <div class="appt-card">
      <?= service_img($upcoming['service_image'], $upcoming['service_name']) ?>
      <div>
        <strong><?= e(fmt_date($upcoming['appointment_date'])) ?> • <?= fmt_time($upcoming['start_time']) ?></strong>
        <span class="svc" style="margin-top:4px"><?= e($upcoming['service_name']) ?></span>
        <div class="by"><?= e($upcoming['barber_name']) ?> (Barber) · <?= status_badge($upcoming['status']) ?></div>
      </div>
    </div>
    <button class="btn btn-light btn-block mt-16" type="button" data-modal-open="detailsModal" data-fill="<?= e(appt_fill($upcoming)) ?>">View Details</button>
  <?php else: ?>
    <p class="muted">No upcoming appointment.</p>
  <?php endif; ?>
</section>

<section class="card mb-16" style="margin-bottom:20px">
  <div class="card-head">
    <h2>Our Services</h2>
    <a class="view-all" href="<?= url('client/book.php') ?>">View All</a>
  </div>
  <!-- Desktop: three cards -->
  <div class="svc-cards only-desktop">
    <?php foreach (array_slice($services, 0, 3) as $s): ?>
      <a class="svc-card" href="<?= url('client/book.php?service=' . $s->id) ?>">
        <?= service_img($s->image, $s->name) ?>
        <div>
          <strong><?= e($s->name) ?></strong>
          <span class="price"><?= peso($s->price) ?></span>
          <span class="dur"><?= (int) $s->duration ?> mins</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <!-- Mobile: list with chevrons -->
  <div class="svc-list only-mobile">
    <?php foreach ($services as $s): ?>
      <a class="svc-card" href="<?= url('client/book.php?service=' . $s->id) ?>">
        <?= service_img($s->image, $s->name) ?>
        <div style="flex:1"><strong><?= e($s->name) ?></strong></div>
        <div style="text-align:right"><strong><?= peso($s->price) ?></strong><span class="dur"><?= (int) $s->duration ?> mins</span></div>
        <?= icon('chevron-right', 'muted', 18) ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<div class="dash-client">
  <section class="card only-desktop">
    <div class="card-head"><h2>Upcoming Appointment</h2></div>
    <?php if ($upcoming): ?>
      <div class="inner">
        <div class="appt-card">
          <span class="appt-ico"><?= icon('calendar-check', '', 26, 1.6) ?></span>
          <div>
            <strong><?= e(fmt_date($upcoming['appointment_date'])) ?> • <?= fmt_time($upcoming['start_time'], true) ?></strong>
            <span class="svc"><?= e($upcoming['service_name']) ?></span>
            <div class="by"><?= icon('user', '', 14) ?> <?= e($upcoming['barber_name']) ?> (Barber) <?= $upcoming['status'] === 'pending' ? ' · ' . status_badge('pending') : '' ?></div>
          </div>
        </div>
        <div class="appt-actions">
          <button class="btn btn-gold" type="button" data-modal-open="detailsModal" data-fill="<?= e(appt_fill($upcoming)) ?>">View Details</button>
          <?php if (in_array($upcoming['status'], ['pending', 'confirmed'], true)): ?>
            <form method="post" data-confirm="Cancel your <?= e($upcoming['service_name']) ?> on <?= e(fmt_date($upcoming['appointment_date'])) ?> at <?= e(fmt_time($upcoming['start_time'])) ?>?" data-confirm-style="danger" data-confirm-label="Cancel appointment" style="display:grid">
              <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="appointment_id" value="<?= (int) $upcoming['id'] ?>">
              <button class="btn btn-light" type="submit">Cancel</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="empty"><?= icon('calendar-plus', '', 34) ?><h3>No upcoming appointment</h3><p>Ready for a fresh cut?</p><a class="btn btn-gold mt-16" href="<?= url('client/book.php') ?>">Book Appointment</a></div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head">
      <h2>Visit History</h2>
      <a class="view-all" href="<?= url('client/history.php') ?>">View All</a>
    </div>
    <?php if (!$visits): ?><p class="muted">Your completed visits will show up here.</p><?php endif; ?>
    <div class="list">
      <?php foreach ($visits as $v): ?>
        <div class="list-item">
          <?= avatar($v['barber_name'], $v['barber_avatar'], 40) ?>
          <div class="li-main">
            <small><?= e(fmt_date($v['appointment_date'])) ?></small>
            <strong><?= e($v['service_name']) ?></strong>
            <small><?= e($v['barber_name']) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php require __DIR__ . '/../includes/appointment_details_modal.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
