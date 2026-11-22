<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1>Appointments</h1>
    <p>Add, reschedule, approve and track every booking. Conflicting time slots are blocked automatically.</p>
  </div>
  <div class="page-actions">
    <button class="btn btn-gold" type="button" data-modal-open="createModal" data-reset><?= icon('plus', '', 18, 2.2) ?> New Appointment</button>
  </div>
</div>

<section class="card">
  <form class="filters" method="get" data-autosubmit>
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <div class="field grow">
      <label for="q">Search client</label>
      <div class="input-icon"><?= icon('search', '', 17) ?><input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="Name or phone"></div>
    </div>
    <div class="field"><label for="from">From</label><input class="input" type="date" id="from" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label for="to">To</label><input class="input" type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <div class="field">
      <label for="barber">Barber</label>
      <select class="input" id="barber" name="barber">
        <option value="">All barbers</option>
        <?php foreach ($barbers as $b): ?><option value="<?= $b->getId() ?>" <?= $barberF === $b->getId() ? 'selected' : '' ?>><?= e($b->getName()) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="service">Service</label>
      <select class="input" id="service" name="service">
        <option value="">All services</option>
        <?php foreach ($allServices as $s): ?><option value="<?= $s->id ?>" <?= $serviceF === $s->id ? 'selected' : '' ?>><?= e($s->name) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-navy" type="submit"><?= icon('filter', '', 16) ?> Filter</button>
    <a class="btn btn-light" href="<?= url('owner/appointments?from=&to=') ?>">Show all</a>
  </form>

  <nav class="tabs">
    <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= e($tabUrl('')) ?>">All<span class="count"><?= $counts[''] ?></span></a>
    <?php foreach (Appointment::STATUSES as $k => $label): ?>
      <a class="<?= $status === $k ? 'active' : '' ?>" href="<?= e($tabUrl($k)) ?>"><?= e($label) ?><span class="count"><?= $counts[$k] ?? 0 ?></span></a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$rows): ?>
    <div class="empty"><?= icon('calendar', '', 36) ?><h3>No appointments found</h3><p>Try a different filter or create a new appointment.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl tbl-responsive">
        <thead><tr><th>Date &amp; Time</th><th>Client</th><th>Service</th><th>Barber</th><th>Total</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a):
            $isToday = $a['appointment_date'] === date('Y-m-d');
            $editFill = json_encode([
                'appointment_id' => $a['id'], 'service_id' => $a['service_id'], 'barber_id' => $a['barber_id'],
                'appointment_date' => max($a['appointment_date'], date('Y-m-d')), 'start_time' => substr($a['start_time'], 0, 5),
                'discount_type' => $a['discount_type'], 'notes' => $a['notes'] ?? '', 'client_label' => $a['client_name'],
            ]);
        ?>
          <tr>
            <td data-label="Date &amp; Time" class="nowrap"><strong><?= fmt_date($a['appointment_date'], 'M j, Y') ?></strong><br><span class="t-muted"><?= fmt_time($a['start_time'], true) ?> – <?= fmt_time($a['end_time'], true) ?></span></td>
            <td data-label="Client">
              <a class="tbl-person" href="<?= url('owner/client_view?id=' . (int) $a['client_id']) ?>">
                <?= avatar($a['client_name'], $a['client_avatar'], 34) ?>
                <span><strong><?= e($a['client_name']) ?></strong><small><?= e($a['client_phone'] ?? '') ?></small></span>
              </a>
            </td>
            <td data-label="Service"><?= e($a['service_name']) ?><br><span class="t-muted"><?= (int) $a['duration'] ?> mins</span></td>
            <td data-label="Barber"><?= e($a['barber_name']) ?></td>
            <td data-label="Total" class="nowrap"><strong><?= peso($a['total_price']) ?></strong><?php if ($a['discount_type'] !== 'none'): ?><br><span class="badge badge-<?= e($a['discount_type']) ?>"><?= e(discount_label($a['discount_type'])) ?></span><?php endif; ?></td>
            <td data-label="Status"><?= status_badge($a['status']) ?></td>
            <td class="tbl-actions-cell">
              <div class="tbl-actions">
                <button class="icon-action" type="button" title="View details" data-modal-open="detailsModal" data-fill="<?= e(appt_fill($a)) ?>"><?= icon('eye', '', 16) ?></button>
                <?php if ($a['status'] === 'pending'): ?>
                  <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                    <button class="icon-action success" type="submit" title="Approve / confirm"><?= icon('check', '', 16, 2.2) ?></button></form>
                <?php endif; ?>
                <?php if (in_array($a['status'], ['pending', 'confirmed'], true) && $isToday): ?>
                  <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="start"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                    <button class="icon-action" type="submit" title="Start service"><?= icon('play', '', 15) ?></button></form>
                <?php endif; ?>
                <?php if (in_array($a['status'], ['confirmed', 'in_progress'], true) && $a['appointment_date'] <= date('Y-m-d')): ?>
                  <form class="inline" method="post" data-confirm="Mark this service as completed? <?= e(peso($a['total_price'])) ?> will be recorded as earnings."><?= csrf_field() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                    <button class="icon-action success" type="submit" title="Mark completed"><?= icon('check-circle', '', 16) ?></button></form>
                <?php endif; ?>
                <?php if (in_array($a['status'], ['pending', 'confirmed'], true)): ?>
                  <button class="icon-action" type="button" title="Edit / reschedule" data-modal-open="editModal" data-fill="<?= e($editFill) ?>"><?= icon('pencil', '', 15) ?></button>
                  <form class="inline" method="post" data-confirm="Cancel this appointment for <?= e($a['client_name']) ?>?" data-confirm-style="danger" data-confirm-label="Cancel appointment"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                    <button class="icon-action danger" type="submit" title="Cancel"><?= icon('x', '', 16, 2.2) ?></button></form>
                <?php else: ?>
                  <form class="inline" method="post" data-confirm="Permanently delete this appointment record?" data-confirm-style="danger" data-confirm-label="Delete"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                    <button class="icon-action danger" type="submit" title="Delete"><?= icon('trash', '', 15) ?></button></form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pager_links($page, $pages, $total, 'appointments') ?>
  <?php endif; ?>
</section>

<!-- New appointment -->
<div class="modal" id="createModal">
  <div class="modal-backdrop"></div>
  <form class="modal-dialog lg" method="post">
    <div class="modal-head"><h3>New Appointment</h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <?php $idPrefix = 'new'; include APPPATH . 'Views/partials/appointment_form.php'; ?>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-gold" type="submit"><?= icon('calendar-check', '', 17) ?> Book Appointment</button>
    </div>
  </form>
</div>

<!-- Edit / reschedule -->
<div class="modal" id="editModal">
  <div class="modal-backdrop"></div>
  <form class="modal-dialog lg" method="post">
    <div class="modal-head"><h3>Edit Appointment · <span data-text="client_label"></span></h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="appointment_id" value="">
      <?php $idPrefix = 'edit'; $clientsBackup = $clients; $clients = []; include APPPATH . 'Views/partials/appointment_form.php'; $clients = $clientsBackup; ?>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Close</button>
      <button class="btn btn-gold" type="submit"><?= icon('check', '', 17, 2.2) ?> Save Changes</button>
    </div>
  </form>
</div>

<?php include APPPATH . 'Views/partials/appointment_details_modal.php'; ?>
<?= $this->endSection() ?>
