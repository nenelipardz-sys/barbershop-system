<?php /** Read-only appointment details modal, filled through data-fill="<?= appt_fill($row) ?>". */ ?>
<div class="modal" id="detailsModal">
  <div class="modal-backdrop"></div>
  <div class="modal-dialog">
    <div class="modal-head"><h3>Appointment Details</h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <div class="appt-card mb-16">
        <img class="svc-img" data-src="d_avatar" src="<?= url('assets/img/services/haircut.jpg') ?>" alt="">
        <div>
          <strong data-text="d_service"></strong>
          <span class="muted" data-text="d_date"></span>
          <div class="by"><?= icon('clock', '', 14) ?><span data-text="d_time"></span></div>
        </div>
      </div>
      <dl class="kv">
        <dt>Client</dt><dd data-text="d_client"></dd>
        <dt>Phone</dt><dd data-text="d_phone"></dd>
        <dt>Barber</dt><dd data-text="d_barber"></dd>
        <dt>Status</dt><dd data-text="d_status"></dd>
        <dt>Rate</dt><dd data-text="d_rate"></dd>
        <dt>Total</dt><dd data-text="d_price"></dd>
        <dt>Notes</dt><dd data-text="d_notes"></dd>
      </dl>
    </div>
    <div class="modal-foot"><button class="btn btn-light" type="button" data-modal-close>Close</button></div>
  </div>
</div>
