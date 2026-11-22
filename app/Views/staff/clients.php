<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1>My Clients</h1>
    <p>Clients you have served or are scheduled to serve. Open a profile to read and add visit notes.</p>
  </div>
</div>

<section class="card">
  <form class="filters" method="get">
    <div class="field grow"><label for="q">Search</label><div class="input-icon"><?= icon('search', '', 17) ?><input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="Name or phone"></div></div>
    <button class="btn btn-navy" type="submit"><?= icon('search', '', 16) ?> Search</button>
  </form>
  <?php if (!$rows): ?>
    <div class="empty"><?= icon('users', '', 36) ?><h3>No clients found</h3></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl tbl-responsive">
        <thead><tr><th>Client</th><th>Phone</th><th>Visits with you</th><th>Last Visit</th><th>Next Visit</th><th>Notes</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $c): ?>
            <tr>
              <td data-label="Client"><a class="tbl-person" href="<?= url('staff/client_view?id=' . (int) $c['id']) ?>"><?= avatar($c['name'], $c['avatar'], 36) ?><span><strong><?= e($c['name']) ?></strong><small><?= e(discount_label($c['discount_type'])) ?></small></span></a></td>
              <td data-label="Phone"><?= e($c['phone'] ?: '—') ?></td>
              <td data-label="Visits"><strong><?= (int) $c['visits'] ?></strong></td>
              <td data-label="Last Visit" class="t-muted"><?= $c['last_visit'] ? e(fmt_date($c['last_visit'])) : '—' ?></td>
              <td data-label="Next Visit"><?= $c['next_visit'] ? '<span class="badge badge-upcoming">' . e(fmt_date($c['next_visit'], 'M j')) . '</span>' : '<span class="t-muted">—</span>' ?></td>
              <td data-label="Notes"><?= (int) $c['notes'] ?></td>
              <td class="tbl-actions-cell"><div class="tbl-actions"><a class="btn btn-outline btn-sm" href="<?= url('staff/client_view?id=' . (int) $c['id']) ?>"><?= icon('eye', '', 14) ?> View</a></div></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pager_links($page, $pages, $total, 'clients') ?>
  <?php endif; ?>
</section>
<?= $this->endSection() ?>
