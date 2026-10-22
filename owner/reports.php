<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = Auth::requireRole('owner', 'staff');
$isStaff = $user->getRole() === 'staff';

$period = in_array(input('period'), ['daily', 'weekly', 'monthly', 'custom'], true) ? input('period') : 'monthly';
$ref = valid_date(input('ref'), date('Y-m-d'));
if ($period === 'custom') {
    $from = valid_date(input('from'), date('Y-m-01'));
    $to = valid_date(input('to'), date('Y-m-d'));
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
    $rangeLabel = fmt_date($from) . ' – ' . fmt_date($to);
} else {
    [$from, $to, $rangeLabel] = Report::period($period, $ref);
}
$serviceF = (int) input('service') ?: null;
$barberF = $isStaff ? $user->getId() : ((int) input('barber') ?: null);

$report = new Report($from, $to, $serviceF, $barberF);
$summary = $report->summary();

// CSV export
if (input('export') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="earnings_' . $from . '_to_' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date', 'Time', 'Client', 'Service', 'Barber', 'Rate', 'Base Price', 'Discount', 'Total']);
    foreach ($report->transactions(100000) as $t) {
        fputcsv($out, [$t['appointment_date'], fmt_time($t['start_time']), $t['client_name'], $t['service_name'], $t['barber_name'],
            discount_label($t['discount_type']), $t['base_price'], $t['discount_amount'], $t['total_price']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Total', '', '', '', '', '', $summary['gross'], $summary['discounts'], $summary['earnings']]);
    exit;
}

// Chart range: a day is shown with the 6 days before it for context.
$chartFrom = $period === 'daily' ? date('Y-m-d', strtotime('-6 day', strtotime($ref))) : $from;
$series = (new Report($chartFrom, $to, $serviceF, $barberF))->dailySeries();
$labelFmt = count($series) <= 7 ? 'D j' : 'j';
$chart = ['labels' => array_map(fn($p) => date($labelFmt, strtotime($p['date'])), $series), 'values' => array_map(fn($p) => $p['total'], $series)];
$byService = $report->byService();
$byBarber = $report->byBarber();
$transactions = $report->transactions(50);
$services = Service::all(false);
$barbers = array_map(fn($r) => new Barber($r), Database::all("SELECT * FROM users WHERE role = 'staff' ORDER BY name"));

$step = ['daily' => '1 day', 'weekly' => '1 week', 'monthly' => '1 month'][$period] ?? null;
$nav = function (string $dir) use ($period, $ref, $step) {
    $q = $_GET;
    $q['period'] = $period;
    $q['ref'] = date('Y-m-d', strtotime(($dir === 'prev' ? '-' : '+') . $step, strtotime($ref)));
    return '?' . http_build_query($q);
};
$periodUrl = function (string $p) {
    $q = $_GET;
    $q['period'] = $p;
    unset($q['export']);
    return '?' . http_build_query($q);
};
$exportQ = $_GET;
$exportQ['export'] = 'csv';

$pageTitle = $isStaff ? 'My Reports' : 'Reports';
$active = 'reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1><?= $isStaff ? 'My Reports' : 'Sales & Earnings Reports' ?></h1>
    <p><?= $isStaff ? 'Your completed services and the earnings you generated.' : 'Daily, weekly and monthly income — filter by service or staff member.' ?></p>
  </div>
  <div class="page-actions no-print">
    <a class="btn btn-outline" href="?<?= e(http_build_query($exportQ)) ?>"><?= icon('download', '', 16) ?> Export CSV</a>
    <button class="btn btn-navy" type="button" onclick="window.print()"><?= icon('printer', '', 16) ?> Print</button>
  </div>
</div>

<section class="card mb-16 no-print">
  <div class="report-head">
    <div class="seg">
      <?php foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'custom' => 'Custom'] as $k => $l): ?>
        <a class="<?= $period === $k ? 'active' : '' ?>" href="<?= e($periodUrl($k)) ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($step): ?>
      <div style="display:flex;align-items:center;gap:8px">
        <a class="icon-action" href="<?= e($nav('prev')) ?>" aria-label="Previous period"><?= icon('chevron-left', '', 16) ?></a>
        <span class="report-range"><?= e($rangeLabel) ?></span>
        <a class="icon-action" href="<?= e($nav('next')) ?>" aria-label="Next period"><?= icon('chevron-right', '', 16) ?></a>
      </div>
    <?php endif; ?>
  </div>
  <form class="filters" method="get" style="margin-bottom:0" data-autosubmit>
    <input type="hidden" name="period" value="<?= e($period) ?>">
    <?php if ($period === 'custom'): ?>
      <div class="field"><label for="from">From</label><input class="input" type="date" id="from" name="from" value="<?= e($from) ?>"></div>
      <div class="field"><label for="to">To</label><input class="input" type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <?php else: ?>
      <div class="field"><label for="ref">Date in period</label><input class="input" type="date" id="ref" name="ref" value="<?= e($ref) ?>"></div>
    <?php endif; ?>
    <div class="field">
      <label for="service">Service type</label>
      <select class="input" id="service" name="service">
        <option value="">All services</option>
        <?php foreach ($services as $s): ?><option value="<?= $s->id ?>" <?= $serviceF === $s->id ? 'selected' : '' ?>><?= e($s->name) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php if (!$isStaff): ?>
      <div class="field">
        <label for="barber">Staff member</label>
        <select class="input" id="barber" name="barber">
          <option value="">All staff</option>
          <?php foreach ($barbers as $b): ?><option value="<?= $b->getId() ?>" <?= $barberF === $b->getId() ? 'selected' : '' ?>><?= e($b->getName()) ?></option><?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
    <button class="btn btn-navy" type="submit"><?= icon('filter', '', 16) ?> Apply</button>
  </form>
</section>

<p class="report-range mb-16"><?= icon('calendar', '', 16) ?> <?= e($rangeLabel) ?></p>

<section class="stats stats-4">
  <div class="card stat">
    <div class="stat-top"><span class="stat-ico green"><?= icon('banknote', '', 18, 2) ?></span>Total Earnings</div>
    <div class="stat-value"><?= peso($summary['earnings']) ?></div>
    <span class="stat-delta neutral">Gross <?= peso($summary['gross']) ?></span>
  </div>
  <div class="card stat">
    <div class="stat-top"><span class="stat-ico gold"><?= icon('check-circle', '', 18, 2) ?></span>Services Completed</div>
    <div class="stat-value"><?= number_format($summary['services']) ?></div>
    <span class="stat-delta neutral"><?= (int) $summary['clients'] ?> unique clients</span>
  </div>
  <div class="card stat">
    <div class="stat-top"><span class="stat-ico blue"><?= icon('trending-up', '', 18, 2) ?></span>Average per Service</div>
    <div class="stat-value"><?= peso(round($summary['average'], 2)) ?></div>
    <span class="stat-delta neutral">Per completed service</span>
  </div>
  <div class="card stat">
    <div class="stat-top"><span class="stat-ico amber"><?= icon('tag', '', 18, 2) ?></span>Senior / PWD Discounts</div>
    <div class="stat-value"><?= peso($summary['discounts']) ?></div>
    <span class="stat-delta <?= $summary['cancelled'] ? 'warn' : 'neutral' ?>"><?= (int) $summary['cancelled'] ?> cancelled booking<?= $summary['cancelled'] == 1 ? '' : 's' ?></span>
  </div>
</section>

<section class="card mb-16">
  <div class="card-head"><h2>Earnings per Day</h2><span class="muted"><?= e(fmt_date($chartFrom, 'M j')) ?> – <?= e(fmt_date($to, 'M j, Y')) ?></span></div>
  <div class="bars tall" data-bars="<?= e(json_encode($chart)) ?>"></div>
</section>

<div class="grid g-2 mb-16">
  <section class="card">
    <div class="card-head"><h2>By Service</h2></div>
    <?php if (!$byService): ?><p class="muted">No completed services in this period.</p><?php else: ?>
      <table class="tbl tbl-simple">
        <thead><tr><th>Service</th><th>Count</th><th>Share</th><th class="text-right">Earnings</th></tr></thead>
        <tbody>
          <?php foreach ($byService as $r): $share = $summary['earnings'] > 0 ? round($r['total'] / $summary['earnings'] * 100) : 0; ?>
            <tr>
              <td><div class="tbl-person" style="min-width:0"><?= avatar($r['name'], $r['image'], 30) ?><strong><?= e($r['name']) ?></strong></div></td>
              <td><?= (int) $r['cnt'] ?></td>
              <td><div class="share-bar" title="<?= $share ?>%"><i style="width:<?= $share ?>%"></i></div></td>
              <td class="text-right t-strong"><?= peso($r['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head"><h2>By Staff</h2></div>
    <?php if (!$byBarber): ?><p class="muted">No completed services in this period.</p><?php else: ?>
      <table class="tbl tbl-simple">
        <thead><tr><th>Staff</th><th>Services</th><th>Share</th><th class="text-right">Earnings</th></tr></thead>
        <tbody>
          <?php foreach ($byBarber as $r): $share = $summary['earnings'] > 0 ? round($r['total'] / $summary['earnings'] * 100) : 0; ?>
            <tr>
              <td><div class="tbl-person" style="min-width:0"><?= avatar($r['name'], $r['avatar'], 30) ?><strong><?= e($r['name']) ?></strong></div></td>
              <td><?= (int) $r['cnt'] ?></td>
              <td><div class="share-bar" title="<?= $share ?>%"><i style="width:<?= $share ?>%"></i></div></td>
              <td class="text-right t-strong"><?= peso($r['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>

<section class="card">
  <div class="card-head"><h2>Transactions</h2><span class="muted"><?= count($transactions) < $summary['services'] ? 'Latest 50 of ' . number_format($summary['services']) . ' · export CSV for all' : number_format($summary['services']) . ' completed' ?></span></div>
  <?php if (!$transactions): ?>
    <div class="empty"><?= icon('chart-bars', '', 34) ?><h3>No transactions</h3><p>Completed services in this period will appear here.</p></div>
  <?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-responsive">
      <thead><tr><th>Date</th><th>Client</th><th>Service</th><th>Barber</th><th>Rate</th><th class="text-right">Total</th></tr></thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
          <tr>
            <td data-label="Date" class="nowrap"><?= e(fmt_date($t['appointment_date'])) ?> <span class="t-muted"><?= fmt_time($t['start_time']) ?></span></td>
            <td data-label="Client"><?= e($t['client_name']) ?></td>
            <td data-label="Service"><?= e($t['service_name']) ?></td>
            <td data-label="Barber" class="t-muted"><?= e($t['barber_name']) ?></td>
            <td data-label="Rate"><?= $t['discount_type'] === 'none' ? '<span class="t-muted">Regular</span>' : '<span class="badge badge-' . e($t['discount_type']) . '">' . e(discount_label($t['discount_type'])) . '</span>' ?></td>
            <td data-label="Total" class="text-right t-strong"><?= peso($t['total_price']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
