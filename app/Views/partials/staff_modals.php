<?php /** Staff modals: appointment details with actions + add note. Filled through data-fill. */ ?>
<div class="modal" id="staffApptModal">
  <div class="modal-backdrop"></div>
  <div class="modal-dialog">
    <div class="modal-head"><h3>Appointment Details</h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <div class="appt-card mb-16">
        <img class="avatar" data-src="d_photo" src="<?= url('assets/img/logo-monogram.png') ?>" alt="" style="width:64px;height:64px">
        <div>
          <strong data-text="d_client"></strong>
          <span class="muted" data-text="d_service"></span>
          <div class="by"><?= icon('clock', '', 14) ?><span data-text="d_when"></span></div>
        </div>
      </div>
      <dl class="kv">
        <dt>Phone</dt><dd data-text="d_phone"></dd>
        <dt>Status</dt><dd data-text="d_status"></dd>
        <dt>Rate</dt><dd data-text="d_rate"></dd>
        <dt>Total</dt><dd data-text="d_price"></dd>
        <dt>Booking notes</dt><dd data-text="d_notes"></dd>
        <dt>Client notes</dt><dd data-text="d_client_notes"></dd>
      </dl>
    </div>
    <div class="modal-foot" style="flex-wrap:wrap">
      <a class="btn btn-light" data-href="d_profile" href="#">Client Profile</a>
      <form class="inline" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="start"><input type="hidden" name="appointment_id" value="">
        <button class="btn btn-blue" type="submit" data-show="can_start"><?= icon('play', '', 14) ?> Start Service</button>
      </form>
      <form class="inline" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="appointment_id" value="">
        <button class="btn btn-gold" type="submit" data-show="can_complete"><?= icon('check', '', 16, 2.2) ?> Mark Completed</button>
      </form>
    </div>
  </div>
</div>

<div class="modal" id="noteModal">
  <div class="modal-backdrop"></div>
  <form class="modal-dialog sm" method="post">
    <div class="modal-head"><h3>Add Client Note</h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="note"><input type="hidden" name="appointment_id" value="">
      <p class="muted mb-16">Visit note for <strong data-text="d_client"></strong> · <span data-text="d_service"></span></p>
      <textarea class="input" name="note" maxlength="1000" required placeholder="e.g. Prefers short sides, no beard trim, always uses pomade"></textarea>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-gold" type="submit"><?= icon('note', '', 16) ?> Save Note</button>
    </div>
  </form>
</div>
<script>
// Show/hide action buttons and set the profile link from the clicked row's data.
document.querySelectorAll('#staffApptModal').forEach(function (m) {
  m.addEventListener('modal:open', function (e) {
    var t = e.detail.trigger; if (!t || !t.dataset.fill) return;
    var d = JSON.parse(t.dataset.fill);
    m.querySelectorAll('[data-show]').forEach(function (b) { b.closest('form').style.display = d[b.dataset.show] ? '' : 'none'; });
    var link = m.querySelector('[data-href]'); link.href = d.d_profile || '#';
  });
});
</script>
