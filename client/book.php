<?php
require __DIR__ . '/../includes/bootstrap.php';
/** @var ClientUser $user */
$user = Auth::requireRole('client');
$profile = $user->profile();

if (is_post()) {
    verify_csrf();
    try {
        $service = Service::find((int) input('service_id'));
        $errors = [];
        if (!$service || !$service->isActive()) $errors[] = 'Please choose a service.';
        if (!(int) input('barber_id')) $errors[] = 'Please choose your barber.';
        if (!valid_date(input('appointment_date'))) $errors[] = 'Please choose a valid date.';
        if (input('start_time') === '') $errors[] = 'Please choose an available time slot.';
        if ($errors) {
            throw new ValidationException($errors);
        }
        // New bookings start as "pending" until the shop confirms them.
        $appt = new Appointment($profile->getId(), (int) input('barber_id'), $service, input('appointment_date'), input('start_time'),
            Fee::for(input('discount_type')), input('notes'), $user->getId());
        $appt->book();
        $when = fmt_date($appt->getDate()) . ' at ' . fmt_time($appt->getStartTime());
        Notification::toOwners('New booking request', $user->getName() . ' requested ' . $service->name . ' on ' . $when . '.', 'owner/appointments.php?status=pending');
        Notification::send($appt->getBarberId(), 'New appointment assigned', $user->getName() . ' booked ' . $service->name . ' on ' . $when . '.', 'staff/appointments.php?tab=upcoming');
        flash('success', 'Booked! Your ' . $service->name . ' on ' . $when . ' is pending confirmation from the shop.');
        redirect('client/appointments.php');
    } catch (ValidationException $e) {
        flash('error', $e->getErrors());
        keep_old($_POST);
        redirect('client/book.php');
    }
}

$services = Service::all();
$barbers = Barber::allActive();
$favBarbers = array_map('intval', array_column(Database::all("SELECT ref_id FROM favorites WHERE client_id = ? AND fav_type = 'barber'", [$profile->getId()]), 'ref_id'));
$selService = (int) old('service_id', input('service', $profile->getPreferredServiceId()));
$selBarber = (int) old('barber_id', input('barber', $profile->getPreferredBarberId()));
$selDate = valid_date(old('appointment_date'), date('Y-m-d'));
$selDiscount = old('discount_type', $profile->getDiscountType());
$openDays = implode(', ', array_map(fn($d) => day_names()[$d], Settings::openDays()));

$pageTitle = 'Book Appointment';
$active = 'book';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Book Appointment</h1>
    <p>Choose a service, your barber and an open time slot. Taken slots are blocked automatically.</p>
  </div>
  <div class="page-date only-desktop"><?= date('M j, Y') ?></div>
</div>

<form method="post" id="bookForm" class="dash" novalidate>
  <?= csrf_field() ?>
  <div class="steps">
    <section class="card">
      <h2 class="step-title">Choose a service</h2>
      <div class="svc-cards">
        <?php foreach ($services as $s): ?>
          <label class="choice">
            <input type="radio" name="service_id" value="<?= $s->id ?>" data-name="<?= e($s->name) ?>" data-price="<?= $s->price ?>" data-duration="<?= $s->duration ?>" <?= $selService === $s->id ? 'checked' : '' ?> required>
            <span class="svc-card">
              <?= service_img($s->image, $s->name) ?>
              <span>
                <strong><?= e($s->name) ?></strong>
                <span class="price"><?= peso($s->price) ?></span>
                <span class="dur"><?= (int) $s->duration ?> mins</span>
              </span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="card">
      <h2 class="step-title">Choose your barber</h2>
      <div class="barber-grid">
        <?php foreach ($barbers as $b): ?>
          <label class="choice">
            <input type="radio" name="barber_id" value="<?= $b->getId() ?>" data-name="<?= e($b->getName()) ?>" <?= $selBarber === $b->getId() ? 'checked' : '' ?> required>
            <span class="barber-card">
              <button type="button" class="fav-btn<?= in_array($b->getId(), $favBarbers, true) ? ' on' : '' ?>" data-fav="barber:<?= $b->getId() ?>" aria-label="Toggle favorite"><?= icon(in_array($b->getId(), $favBarbers, true) ? 'heart-fill' : 'heart', '', 18) ?></button>
              <?= avatar($b->getName(), $b->getAvatar(), 76) ?>
              <strong><?= e(explode(' ', $b->getName())[0]) ?> <?= e(mb_substr(explode(' ', $b->getName())[1] ?? '', 0, 1)) ?>.</strong>
              <small style="color:<?= $b->getPosition() === 'Senior Barber' ? 'var(--blue)' : 'var(--muted)' ?>"><?= e($b->getPosition()) ?></small>
              <span class="rating"><?= number_format($b->getRating(), 1) ?> <?= icon('star', '', 14) ?></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="card">
      <h2 class="step-title">Pick a date &amp; time</h2>
      <div class="form-grid">
        <div class="field">
          <label for="date">Date</label>
          <input class="input" type="date" id="date" name="appointment_date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+60 day')) ?>" value="<?= e($selDate) ?>" required>
          <span class="hint">Open <?= e($openDays) ?>, <?= e(fmt_time(Settings::get('open_time'))) ?> – <?= e(fmt_time(Settings::get('close_time'))) ?></span>
        </div>
      </div>
      <div class="field mt-16" data-slot-picker>
        <span class="label">Available times</span>
        <input type="hidden" name="start_time" value="" data-keep="<?= e(old('start_time')) ?>">
        <p class="slots-msg">Select a service, barber and date to see available times.</p>
        <div class="slots"></div>
      </div>
    </section>

    <section class="card">
      <h2 class="step-title">Rate &amp; notes</h2>
      <div class="discount-opts">
        <?php foreach (['none' => ['Standard Rate', 'Regular price'], 'senior' => ['Senior Citizen', '20% off · bring valid ID'], 'pwd' => ['PWD', '20% off · bring valid ID']] as $k => [$t, $sub]): ?>
          <label class="choice">
            <input type="radio" name="discount_type" value="<?= $k ?>" <?= $selDiscount === $k ? 'checked' : '' ?>>
            <span><?= e($t) ?><small><?= e($sub) ?></small></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="field mt-16">
        <label for="notes">Notes for your barber <span class="muted">(optional)</span></label>
        <textarea class="input" id="notes" name="notes" maxlength="500" placeholder="e.g. Low fade, keep the top textured"><?= e(old('notes')) ?></textarea>
      </div>
    </section>
  </div>

  <aside>
    <section class="card summary">
      <div class="card-head"><h2>Booking Summary</h2></div>
      <div class="summary-row"><span>Service</span><span id="sumService">—</span></div>
      <div class="summary-row"><span>Duration</span><span id="sumDuration">—</span></div>
      <div class="summary-row"><span>Barber</span><span id="sumBarber">—</span></div>
      <div class="summary-row"><span>Date</span><span id="sumDate">—</span></div>
      <div class="summary-row"><span>Time</span><span id="sumTime">—</span></div>
      <div class="summary-row"><span>Price</span><span id="sumPrice">—</span></div>
      <div class="summary-row"><span>Discount</span><span id="sumDiscount">—</span></div>
      <div class="summary-total"><span class="muted">Total</span><strong id="sumTotal">₱0</strong></div>
      <button class="btn btn-gold btn-block btn-lg mt-16" type="submit"><?= icon('calendar-check', '', 18) ?> Confirm Booking</button>
      <p class="muted mt-8" style="font-size:12.5px">Your booking will be <b>pending</b> until the shop confirms it. You can reschedule or cancel anytime before your visit.</p>
    </section>
  </aside>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('bookForm');
  var rate = <?= json_encode(DISCOUNT_RATE) ?>;
  function checked(name) { return form.querySelector('[name="' + name + '"]:checked'); }
  function fmtTime(t) { if (!t) return '—'; var p = t.split(':'), h = +p[0]; return ((h % 12) || 12) + ':' + p[1] + (h < 12 ? ' AM' : ' PM'); }
  function update() {
    var s = checked('service_id'), b = checked('barber_id'), d = checked('discount_type');
    var date = form.appointment_date.value, time = form.start_time.value;
    var price = s ? +s.dataset.price : 0;
    var off = d && d.value !== 'none' ? Math.round(price * rate * 100) / 100 : 0;
    document.getElementById('sumService').textContent = s ? s.dataset.name : '—';
    document.getElementById('sumDuration').textContent = s ? s.dataset.duration + ' mins' : '—';
    document.getElementById('sumBarber').textContent = b ? b.dataset.name : '—';
    document.getElementById('sumDate').textContent = date ? new Date(date + 'T00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : '—';
    document.getElementById('sumTime').textContent = fmtTime(time);
    document.getElementById('sumPrice').textContent = s ? CS.peso(price) : '—';
    document.getElementById('sumDiscount').textContent = off ? '−' + CS.peso(off) : '—';
    document.getElementById('sumTotal').textContent = CS.peso(price - off);
  }
  form.addEventListener('change', update);
  form.addEventListener('slotchange', update);
  update();
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
