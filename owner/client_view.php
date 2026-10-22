<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = Auth::requireRole('owner', 'staff');
$isOwner = $user->getRole() === 'owner';

$client = ClientProfile::find((int) input('id'));
if (!$client) {
    flash('error', 'Client not found.');
    redirect($isOwner ? 'owner/clients.php' : 'staff/clients.php');
}

if (is_post()) {
    verify_csrf();
    try {
        if (input('action') === 'add_note') {
            $client->addNote(input('note'), $user->getId());
            flash('success', 'Note added.');
        } elseif (input('action') === 'delete_note') {
            // Owners can remove any note; staff only their own.
            $sql = 'DELETE FROM client_notes WHERE id = ? AND client_id = ?' . ($isOwner ? '' : ' AND staff_id = ' . $user->getId());
            Database::query($sql, [(int) input('note_id'), $client->getId()]);
            flash('success', 'Note removed.');
        } elseif ($isOwner && input('action') === 'save') {
            $client->fill($_POST)->save();
            flash('success', 'Client profile updated.');
        }
    } catch (ValidationException $e) {
        flash('error', $e->getErrors());
    }
    redirect(($isOwner ? 'owner' : 'staff') . '/client_view.php?id=' . $client->getId());
}

$visits = $client->visits();
$upcoming = Database::all(
    "SELECT a.*, s.name AS service_name, s.image AS service_image, u.name AS barber_name, c.name AS client_name, c.phone AS client_phone
     FROM appointments a JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id JOIN clients c ON c.id = a.client_id
     WHERE a.client_id = ? AND a.status IN ('pending','confirmed','in_progress') ORDER BY a.appointment_date, a.start_time",
    [$client->getId()]
);
$fav = $client->favoriteService();
$prefBarber = $client->getPreferredBarberId() ? User::find($client->getPreferredBarberId()) : null;
$prefService = $client->getPreferredServiceId() ? Service::find($client->getPreferredServiceId()) : null;
$notes = $client->notesHistory();
$barbers = Barber::allActive();
$services = Service::all();

$pageTitle = $client->getName();
$active = 'clients';
require __DIR__ . '/../includes/header.php';
?>
<a class="breadcrumb" href="<?= url($isOwner ? 'owner/clients.php' : 'staff/clients.php') ?>"><?= icon('arrow-left', '', 15) ?> Back to clients</a>
<div class="page-head">
  <div>
    <h1><?= e($client->getName()) ?></h1>
    <p>Client since <?= e(fmt_date($client->getCreatedAt(), 'F Y')) ?> · <?= $client->getUserId() ? 'Registered account' : 'Walk-in client' ?></p>
  </div>
  <?php if ($isOwner): ?>
    <div class="page-actions">
      <button class="btn btn-outline" type="button" data-modal-open="clientModal" data-title="Edit Client" data-fill="<?= e(json_encode([
          'client_id' => $client->getId(), 'name' => $client->getName(), 'phone' => $client->getPhone() ?? '', 'email' => $client->getEmail() ?? '',
          'discount_type' => $client->getDiscountType(), 'preferred_barber_id' => $client->getPreferredBarberId() ?? '',
          'preferred_service_id' => $client->getPreferredServiceId() ?? '', 'notes' => $client->getNotes() ?? '',
      ])) ?>"><?= icon('pencil', '', 16) ?> Edit Profile</button>
      <a class="btn btn-gold" href="<?= url('owner/appointments.php') ?>"><?= icon('calendar-plus', '', 17) ?> Book</a>
    </div>
  <?php endif; ?>
</div>

<div class="dash">
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>Upcoming Appointments</h2></div>
      <?php if (!$upcoming): ?>
        <p class="muted">No upcoming appointments.</p>
      <?php else: ?>
        <div class="table-wrap"><table class="tbl tbl-responsive">
          <thead><tr><th>Date</th><th>Time</th><th>Service</th><th>Barber</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($upcoming as $a): ?>
            <tr>
              <td data-label="Date"><?= e(fmt_date($a['appointment_date'])) ?></td>
              <td data-label="Time" class="t-time"><?= fmt_time($a['start_time'], true) ?></td>
              <td data-label="Service"><?= e($a['service_name']) ?></td>
              <td data-label="Barber" class="t-muted"><?= e($a['barber_name']) ?></td>
              <td data-label="Status"><?= status_badge($a['status'], !$isOwner) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Visit History</h2><span class="muted"><?= count($visits) ?> completed visit<?= count($visits) === 1 ? '' : 's' ?></span></div>
      <?php if (!$visits): ?>
        <p class="muted">No completed visits yet.</p>
      <?php else: ?>
        <div class="table-wrap"><table class="tbl tbl-responsive">
          <thead><tr><th>Date</th><th>Service</th><th>Barber</th><th>Paid</th><th>Notes</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($visits, 0, 25) as $v): ?>
            <tr>
              <td data-label="Date" class="nowrap"><?= e(fmt_date($v['appointment_date'])) ?></td>
              <td data-label="Service"><?= e($v['service_name']) ?></td>
              <td data-label="Barber" class="t-muted"><?= e($v['barber_name']) ?></td>
              <td data-label="Paid"><?= peso($v['total_price']) ?></td>
              <td data-label="Notes" class="t-muted"><?= e($v['notes'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>

  <div class="stack">
    <section class="card profile-card">
      <?= avatar($client->getName(), $client->getAvatar(), 88) ?>
      <h2><?= e($client->getName()) ?></h2>
      <p><?= $client->getDiscountType() === 'none' ? 'Regular rate' : e(discount_label($client->getDiscountType())) . ' · 20% discount' ?></p>
      <div class="mini-stats">
        <div><strong><?= count($visits) ?></strong><small>Visits</small></div>
        <div><strong><?= peso($client->totalSpent()) ?></strong><small>Total spent</small></div>
        <div><strong><?= $client->lastVisit() ? e(fmt_date($client->lastVisit(), 'M j')) : '—' ?></strong><small>Last visit</small></div>
      </div>
      <dl class="kv">
        <dt>Phone</dt><dd><?= e($client->getPhone() ?: '—') ?></dd>
        <dt>Email</dt><dd><?= e($client->getEmail() ?: '—') ?></dd>
        <dt>Preferred barber</dt><dd><?= e($prefBarber ? $prefBarber->getName() : '—') ?></dd>
        <dt>Preferred service</dt><dd><?= e($prefService ? $prefService->name : '—') ?></dd>
        <dt>Favorite service</dt><dd><?= e($fav ? $fav['name'] . ' (' . $fav['times'] . '×)' : '—') ?></dd>
        <dt>Profile notes</dt><dd><?= e($client->getNotes() ?: '—') ?></dd>
      </dl>
    </section>

    <section class="card">
      <div class="card-head"><h2>Client Notes</h2></div>
      <form method="post" class="mb-16">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_note">
        <textarea class="input" name="note" maxlength="1000" placeholder="e.g. Prefers short sides, no beard trim…" required></textarea>
        <button class="btn btn-gold btn-sm mt-8" type="submit"><?= icon('plus', '', 15, 2.2) ?> Add Note</button>
      </form>
      <div class="stack" style="gap:10px">
        <?php if (!$notes): ?><p class="muted">No notes yet.</p><?php endif; ?>
        <?php foreach ($notes as $n): ?>
          <div class="note-item">
            <?= nl2br(e($n['note'])) ?>
            <small><?= e($n['staff_name'] ?? 'Staff') ?> · <?= e(time_ago($n['created_at'])) ?>
              <?php if ($isOwner || (int) $n['staff_id'] === $user->getId()): ?>
                · <form class="inline" method="post" data-confirm="Remove this note?" data-confirm-style="danger" data-confirm-label="Remove"><?= csrf_field() ?><input type="hidden" name="action" value="delete_note"><input type="hidden" name="note_id" value="<?= (int) $n['id'] ?>"><button class="link-btn" type="submit" style="color:var(--red)">Remove</button></form>
              <?php endif; ?>
            </small>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<?php if ($isOwner) { require __DIR__ . '/../includes/client_form_modal.php'; } ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
