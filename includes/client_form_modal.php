<?php
/**
 * Add / edit client modal. Expects $barbers, $services; optional $reopen (bool) to show it again after errors.
 */
$reopen = $reopen ?? false;
?>
<div class="modal" id="clientModal" <?= $reopen ? 'data-auto-open' : '' ?>>
  <div class="modal-backdrop"></div>
  <form class="modal-dialog" method="post">
    <div class="modal-head"><h3><?= $reopen && old('client_id') ? 'Edit Client' : 'Add Client' ?></h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="client_id" value="<?= e(old('client_id')) ?>">
      <div class="form-grid">
        <div class="field span-2"><label for="cm-name">Full name</label><input class="input" id="cm-name" name="name" value="<?= e(old('name')) ?>" required></div>
        <div class="field"><label for="cm-phone">Phone</label><input class="input" id="cm-phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="09xx xxx xxxx"></div>
        <div class="field"><label for="cm-email">Email</label><input class="input" type="email" id="cm-email" name="email" value="<?= e(old('email')) ?>" placeholder="name@email.com"></div>
        <div class="field">
          <label for="cm-rate">Rate</label>
          <select class="input" id="cm-rate" name="discount_type">
            <?php foreach (Fee::types() as $k => $l): ?><option value="<?= $k ?>" <?= old('discount_type') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="cm-barber">Preferred barber</label>
          <select class="input" id="cm-barber" name="preferred_barber_id">
            <option value="">No preference</option>
            <?php foreach ($barbers as $b): ?><option value="<?= $b->getId() ?>" <?= (int) old('preferred_barber_id') === $b->getId() ? 'selected' : '' ?>><?= e($b->getName()) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field span-2">
          <label for="cm-service">Preferred service</label>
          <select class="input" id="cm-service" name="preferred_service_id">
            <option value="">No preference</option>
            <?php foreach ($services as $s): ?><option value="<?= $s->id ?>" <?= (int) old('preferred_service_id') === $s->id ? 'selected' : '' ?>><?= e($s->name) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field span-2"><label for="cm-notes">Notes</label><textarea class="input" id="cm-notes" name="notes" placeholder="Preferences, allergies, reminders…"><?= e(old('notes')) ?></textarea></div>
      </div>
      <p class="hint muted mt-8">A phone number or an email address is required.</p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-gold" type="submit"><?= icon('check', '', 17, 2.2) ?> Save Client</button>
    </div>
  </form>
</div>
