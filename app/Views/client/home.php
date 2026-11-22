<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
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
    <a class="btn btn-gold" href="<?= url('client/book') ?>">Book Appointment</a>
  </div>
</section>

<!-- Mobile: Book Now -->
<a class="btn btn-gold btn-block btn-lg only-mobile mb-16" href="<?= url('client/book') ?>" style="height:54px;font-size:17px"><?= icon('calendar-days', '', 22, 2) ?> Book Now</a>

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
    <a class="view-all" href="<?= url('client/book') ?>">View All</a>
  </div>
  <!-- Desktop: three cards -->
  <div class="svc-cards only-desktop">
    <?php foreach (array_slice($services, 0, 3) as $s): ?>
      <a class="svc-card" href="<?= url('client/book?service=' . $s->id) ?>">
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
      <a class="svc-card" href="<?= url('client/book?service=' . $s->id) ?>">
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
      <div class="empty"><?= icon('calendar-plus', '', 34) ?><h3>No upcoming appointment</h3><p>Ready for a fresh cut?</p><a class="btn btn-gold mt-16" href="<?= url('client/book') ?>">Book Appointment</a></div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head">
      <h2>Visit History</h2>
      <a class="view-all" href="<?= url('client/history') ?>">View All</a>
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

<?php include APPPATH . 'Views/partials/appointment_details_modal.php'; ?>
<?= $this->endSection() ?>
