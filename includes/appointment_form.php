<?php
/**
 * Appointment form fields (service, barber, date, live time slots, discount, notes).
 * Expects: $services (Service[]), $barbers (Barber[]); optional $clients (array rows) and $idPrefix.
 */
$idPrefix = $idPrefix ?? 'af';
?>
<div class="form-grid">
  <?php if (!empty($clients)): ?>
    <div class="field span-2" data-client-picker>
      <label for="<?= $idPrefix ?>-client">Client</label>
      <select class="input" id="<?= $idPrefix ?>-client" name="client_id">
        <option value="">— Select a client —</option>
        <option value="new">+ New walk-in client</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?= (int) $c['id'] ?>" data-discount="<?= e($c['discount_type']) ?>"><?= e($c['name']) ?><?= $c['phone'] ? ' · ' . e($c['phone']) : '' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field new-client hidden">
      <label for="<?= $idPrefix ?>-cname">Client name</label>
      <input class="input" id="<?= $idPrefix ?>-cname" name="new_client_name" placeholder="Full name">
    </div>
    <div class="field new-client hidden">
      <label for="<?= $idPrefix ?>-cphone">Phone number</label>
      <input class="input" id="<?= $idPrefix ?>-cphone" name="new_client_phone" placeholder="09xx xxx xxxx">
    </div>
  <?php endif; ?>
  <div class="field">
    <label for="<?= $idPrefix ?>-service">Service</label>
    <select class="input" id="<?= $idPrefix ?>-service" name="service_id" required>
      <option value="">— Select a service —</option>
      <?php foreach ($services as $s): ?>
        <option value="<?= (int) $s->id ?>"><?= e($s->name) ?> · <?= peso($s->price) ?> · <?= (int) $s->duration ?> mins</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="<?= $idPrefix ?>-barber">Barber</label>
    <select class="input" id="<?= $idPrefix ?>-barber" name="barber_id" required>
      <option value="">— Select a barber —</option>
      <?php foreach ($barbers as $b): ?>
        <option value="<?= $b->getId() ?>"><?= e($b->getName()) ?> (<?= e($b->getPosition()) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="<?= $idPrefix ?>-date">Date</label>
    <input class="input" type="date" id="<?= $idPrefix ?>-date" name="appointment_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
  </div>
  <div class="field">
    <label for="<?= $idPrefix ?>-discount">Rate</label>
    <select class="input" id="<?= $idPrefix ?>-discount" name="discount_type">
      <?php foreach (Fee::types() as $k => $label): ?>
        <option value="<?= e($k) ?>"><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field span-2" data-slot-picker>
    <span class="label">Available time</span>
    <input type="hidden" name="start_time" value="">
    <p class="slots-msg">Select a service, barber and date to see available times.</p>
    <div class="slots"></div>
  </div>
  <div class="field span-2">
    <label for="<?= $idPrefix ?>-notes">Notes <span class="muted">(optional)</span></label>
    <textarea class="input" id="<?= $idPrefix ?>-notes" name="notes" maxlength="500" placeholder="Style preferences, reminders…"></textarea>
  </div>
</div>
