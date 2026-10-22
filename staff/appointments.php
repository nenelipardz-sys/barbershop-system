<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/staff_actions.php';
/** @var Barber $user */
$user = Auth::requireRole('staff');

if (is_post()) {
    verify_csrf();
    handle_staff_appointment_action($user);
    back('staff/appointments.php');
}

$today = date('Y-m-d');
$tabs = ['today' => 'Today', 'upcoming' => 'Upcoming', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'all' => 'All'];
$date = valid_date(input('date'));
$tab = array_key_exists(input('tab'), $tabs) ? input('tab') : ($date ? 'all' : 'today');
$q = input('q');

$conds = [
    'today'       => ['a.appointment_date = ?', [$today]],
    'upcoming'    => ["a.appointment_date >= ? AND a.status IN ('pending','confirmed')", [$today]],
    'in_progress' => ["a.status = 'in_progress'", []],
    'completed'   => ["a.status = 'completed'", []],
    'cancelled'   => ["a.status = 'cancelled'", []],
    'all'         => ['1=1', []],
];
$base = 'a.barber_id = ?';
$baseParams = [$user->getId()];
if ($date) { $base .= ' AND a.appointment_date = ?'; $baseParams[] = $date; }
if ($q !== '') { $base .= ' AND c.name LIKE ?'; $baseParams[] = "%$q%"; }

$counts = [];
foreach ($conds as $k => [$sql, $p]) {
    $counts[$k] = (int) Database::value("SELECT COUNT(*) FROM appointments a JOIN clients c ON c.id = a.client_id WHERE $base AND $sql", array_merge($baseParams, $p));
}
[$sql, $p] = $conds[$tab];
$total = $counts[$tab];
[$offset, $pages, $page] = paginate($total, 15);
$order = in_array($tab, ['today', 'upcoming', 'in_progress'], true) ? 'ASC' : 'DESC';
$rows = Database::all(
    "SELECT a.*, c.name AS client_name, c.phone AS client_phone, c.avatar AS client_avatar, s.name AS service_name, s.image AS service_image, u.name AS barber_name
     FROM appointments a JOIN clients c ON c.id = a.client_id JOIN services s ON s.id = a.service_id JOIN users u ON u.id = a.barber_id
     WHERE $base AND $sql ORDER BY a.appointment_date $order, a.start_time $order LIMIT $offset, 15",
    array_merge($baseParams, $p)
);
$notesMap = client_notes_map(array_column($rows, 'client_id'));
$tabUrl = function (string $t) {
    $q = $_GET;
    unset($q['page']);
    $q['tab'] = $t;
    return '?' . http_build_query($q);
};

$pageTitle = 'Appointments';
$active = 'appointments';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Appointments</h1>
    <p>Your assigned appointments. Start services, mark them complete and record visit notes.</p>
  </div>
</div>

<section class="card">
  <form class="filters" method="get" data-autosubmit>
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="field grow"><label for="q">Search client</label><div class="input-icon"><?= icon('search', '', 17) ?><input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="Client name"></div></div>
    <div class="field"><label for="date">Date</label><input class="input" type="date" id="date" name="date" value="<?= e($date) ?>"></div>
    <button class="btn btn-navy" type="submit"><?= icon('search', '', 16) ?> Search</button>
    <?php if ($date || $q !== ''): ?><a class="btn btn-light" href="<?= url('staff/appointments.php') ?>">Clear</a><?php endif; ?>
  </form>

  <nav class="tabs">
    <?php foreach ($tabs as $k => $label): ?>
      <a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= e($tabUrl($k)) ?>"><?= e($label) ?><span class="count"><?= $counts[$k] ?></span></a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$rows): ?>
    <div class="empty"><?= icon('calendar', '', 36) ?><h3>No appointments here</h3><p>Try another tab or date.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl tbl-responsive">
        <thead><tr><th>Date &amp; Time</th><th>Client</th><th>Service</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $a): $fill = staff_fill($a, $notesMap[$a['client_id']] ?? ''); $f = json_decode($fill, true); ?>
            <tr>
              <td data-label="Date &amp; Time" class="nowrap"><strong><?= e(fmt_date($a['appointment_date'], 'D, M j')) ?></strong><br><span class="t-muted"><?= fmt_time($a['start_time'], true) ?> – <?= fmt_time($a['end_time'], true) ?></span></td>
              <td data-label="Client"><a class="tbl-person" href="<?= url('staff/client_view.php?id=' . (int) $a['client_id']) ?>"><?= avatar($a['client_name'], $a['client_avatar'], 34) ?><span><strong><?= e($a['client_name']) ?></strong><small><?= e($a['client_phone'] ?? '') ?></small></span></a></td>
              <td data-label="Service"><?= e($a['service_name']) ?><?= $a['notes'] ? '<br><span class="t-muted">' . e(mb_strimwidth($a['notes'], 0, 40, '…')) . '</span>' : '' ?></td>
              <td data-label="Status"><?= status_badge($a['status'], true) ?></td>
              <td class="tbl-actions-cell"><div class="tbl-actions">
                <button class="icon-action" type="button" title="Details" data-modal-open="staffApptModal" data-fill="<?= e($fill) ?>"><?= icon('eye', '', 16) ?></button>
                <?php if ($f['can_start']): ?>
                  <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="start"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>"><button class="btn btn-blue btn-sm" type="submit"><?= icon('play', '', 13) ?> Start</button></form>
                <?php endif; ?>
                <?php if ($f['can_complete']): ?>
                  <form class="inline" method="post" data-confirm="Mark <?= e($a['service_name']) ?> for <?= e($a['client_name']) ?> as completed?"><?= csrf_field() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>"><button class="btn btn-success btn-sm" type="submit"><?= icon('check', '', 14, 2.4) ?> Complete</button></form>
                <?php endif; ?>
                <button class="icon-action" type="button" title="Add note" data-modal-open="noteModal" data-fill="<?= e(json_encode(['appointment_id' => $a['id'], 'd_client' => $a['client_name'], 'd_service' => $a['service_name']])) ?>"><?= icon('note', '', 15) ?></button>
              </div></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pager_links($page, $pages, $total, 'appointments') ?>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/staff_modals.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
