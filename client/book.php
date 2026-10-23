<?php
require __DIR__ . '/../includes/bootstrap.php';
/** @var ClientUser $user */
$user = Auth::requireRole('client');
$profile = $user->profile();

if (is_post()) {
    verify_csrf();
    $pdo = Database::conn();
    try {
        // Selected services, in catalog order; they are done back-to-back with the same barber.
        $ids = array_map('intval', (array) ($_POST['service_ids'] ?? []));
        $selected = array_values(array_filter(Service::all(), fn($s) => in_array($s->id, $ids, true)));
        $errors = [];
        if (!$selected) $errors[] = 'Please choose at least one service.';
        if (!(int) input('barber_id')) $errors[] = 'Please choose your barber.';
        if (!valid_date(input('appointment_date'))) $errors[] = 'Please choose a valid date.';
        if (input('start_time') === '') $errors[] = 'Please choose an available time slot.';
        if ($errors) {
            throw new ValidationException($errors);
        }

        // All services are saved together, or none at all if one of them conflicts.
        $pdo->beginTransaction();
        $start = input('start_time');
        $booked = [];
        foreach ($selected as $i => $service) {
            // New bookings start as "pending" until the shop confirms them.
            $appt = new Appointment($profile->getId(), (int) input('barber_id'), $service, input('appointment_date'), $start,
                Fee::for(input('discount_type')), $i === 0 ? input('notes') : null, $user->getId());
            $appt->book();
            $booked[] = $appt;
            $start = substr($appt->getEndTime(), 0, 5);
        }
        $pdo->commit();

        $first = $booked[0];
        $names = implode(' + ', array_map(fn($a) => $a->getService()->name, $booked));
        $when = fmt_date($first->getDate()) . ' at ' . fmt_time($first->getStartTime());
        Notification::toOwners('New booking request', $user->getName() . ' requested ' . $names . ' on ' . $when . '.', 'owner/appointments.php?status=pending');
        Notification::send($first->getBarberId(), 'New appointment assigned', $user->getName() . ' booked ' . $names . ' on ' . $when . '.', 'staff/appointments.php?tab=upcoming');
        flash('success', 'Booked! Your ' . $names . ' on ' . $when . ' is pending confirmation from the shop.');
        redirect('client/appointments.php');
    } catch (ValidationException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $e->getErrors());
        keep_old($_POST);
        redirect('client/book.php');
    }
}

$services = Service::all();
$barbers = Barber::allActive();
$favBarbers = array_map('intval', array_column(Database::all("SELECT ref_id FROM favorites WHERE client_id = ? AND fav_type = 'barber'", [$profile->getId()]), 'ref_id'));
$selServices = array_map('intval', (array) old('service_ids', input('service') ? [input('service')] : ($profile->getPreferredServiceId() ? [$profile->getPreferredServiceId()] : [])));
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
    <p>Choose one or more services, your barber and an open time slot. Taken slots are blocked automatically.</p>
  </div>
  <div class="page-date only-desktop"><?= date('M j, Y') ?></div>
</div>

<form method="post" id="bookForm" class="dash book-page" novalidate>
  <?= csrf_field() ?>
  <div class="steps">
    <section class="card">
      <h2 class="step-title">Choose your services</h2>
      <p class="step-hint">Pick as many as you like — they will be done one after another by the same barber.</p>
      <div class="svc-cards">
        <?php foreach ($services as $s): ?>
          <label class="choice">
            <input type="checkbox" name="service_ids[]" value="<?= $s->id ?>" data-name="<?= e($s->name) ?>" data-price="<?= $s->price ?>" data-duration="<?= $s->duration ?>" <?= in_array($s->id, $selServices, true) ? 'checked' : '' ?>>
            <span class="svc-card">
              <?= service_img($s->image, $s->name) ?>
              <span>
                <strong><?= e($s->name) ?></strong>
                <span class="price"><?= peso($s->price) ?></span>
                <span class="dur"><?= (int) $s->duration ?> mins</span>
              </span>
              <span class="svc-check" aria-hidden="true"><?= icon('check', '', 16, 3) ?></span>
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
              <button type="button" class="fav-btn<?= in_array($b->getId(), $favBarbers, true) ? ' on' : '' ?>" data-fav="barber:<?= $b->getId() ?>" aria-label="Toggle favorite"><?= icon(in_array($b->getId(), $favBarbers, true) ? 'heart-fill' : 'heart', '', 20) ?></button>
              <?= avatar($b->getName(), $b->getAvatar(), 96) ?>
              <strong><?= e(explode(' ', $b->getName())[0]) ?> <?= e(mb_substr(explode(' ', $b->getName())[1] ?? '', 0, 1)) ?>.</strong>
              <small style="color:<?= $b->getPosition() === 'Senior Barber' ? 'var(--blue)' : 'var(--muted)' ?>"><?= e($b->getPosition()) ?></small>
              <span class="rating"><?= number_format($b->getRating(), 1) ?> <?= icon('star', '', 16) ?></span>
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
        <p class="slots-msg">Select your services, a barber and a date to see available times.</p>
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
      <div class="summary-row summary-services"><span>Services</span><span id="sumServices">—</span></div>
      <div class="summary-row"><span>Total time</span><span id="sumDuration">—</span></div>
      <div class="summary-row"><span>Barber</span><span id="sumBarber">—</span></div>
      <div class="summary-row"><span>Date</span><span id="sumDate">—</span></div>
      <div class="summary-row"><span>Time</span><span id="sumTime">—</span></div>
      <div class="summary-row"><span>Subtotal</span><span id="sumPrice">—</span></div>
      <div class="summary-row"><span>Discount</span><span id="sumDiscount">—</span></div>
      <div class="summary-total"><span class="muted">Total</span><strong id="sumTotal">₱0</strong></div>
      <button class="btn btn-gold btn-block btn-lg mt-16" type="submit"><?= icon('calendar-check', '', 20) ?> Confirm Booking</button>
      <p class="muted mt-8 summary-note">Your booking will be <b>pending</b> until the shop confirms it. You can reschedule or cancel anytime before your visit.</p>
    </section>
  </aside>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('bookForm');
  var rate = <?= json_encode(DISCOUNT_RATE) ?>;
  function checked(name) { return form.querySelector('[name="' + name + '"]:checked'); }
  function toMin(t) { var p = t.split(':'); return (+p[0]) * 60 + (+p[1]); }
  function fmtTime(m) { var h = Math.floor(m / 60), mm = String(m % 60).padStart(2, '0'); return ((h % 12) || 12) + ':' + mm + (h < 12 ? ' AM' : ' PM'); }
  function update() {
    var picked = Array.prototype.slice.call(form.querySelectorAll('[name="service_ids[]"]:checked'));
    var b = checked('barber_id'), d = checked('discount_type');
    var date = form.appointment_date.value, time = form.start_time.value;
    var price = picked.reduce(function (s, i) { return s + (+i.dataset.price); }, 0);
    var mins = picked.reduce(function (s, i) { return s + (+i.dataset.duration); }, 0);
    var off = d && d.value !== 'none' ? Math.round(price * rate * 100) / 100 : 0;
    var list = document.getElementById('sumServices');
    list.innerHTML = picked.length ? picked.map(function (i) {
      return '<span class="sum-svc"><b></b><em></em></span>';
    }).join('') : '—';
    picked.forEach(function (i, k) {
      var row = list.children[k];
      row.querySelector('b').textContent = i.dataset.name;
      row.querySelector('em').textContent = CS.peso(+i.dataset.price);
    });
    document.getElementById('sumDuration').textContent = mins ? mins + ' mins' : '—';
    document.getElementById('sumBarber').textContent = b ? b.dataset.name : '—';
    document.getElementById('sumDate').textContent = date ? new Date(date + 'T00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : '—';
    document.getElementById('sumTime').textContent = time ? fmtTime(toMin(time)) + (mins ? ' – ' + fmtTime(toMin(time) + mins) : '') : '—';
    document.getElementById('sumPrice').textContent = picked.length ? CS.peso(price) : '—';
    document.getElementById('sumDiscount').textContent = off ? '−' + CS.peso(off) : '—';
    document.getElementById('sumTotal').textContent = CS.peso(price - off);
  }
  form.addEventListener('change', update);
  form.addEventListener('slotchange', update);
  update();
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
