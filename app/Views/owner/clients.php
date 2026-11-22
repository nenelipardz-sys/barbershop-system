<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1>Clients</h1>
    <p>Client profiles, contact details, visit history and preferences.</p>
  </div>
  <div class="page-actions">
    <button class="btn btn-gold" type="button" data-modal-open="clientModal" data-reset data-title="Add Client"><?= icon('user-plus', '', 18) ?> Add Client</button>
  </div>
</div>

<section class="card">
  <form class="filters" method="get" data-autosubmit>
    <div class="field grow">
      <label for="q">Search</label>
      <div class="input-icon"><?= icon('search', '', 17) ?><input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, phone or email"></div>
    </div>
    <div class="field">
      <label for="type">Account</label>
      <select class="input" id="type" name="type">
        <option value="">All clients</option>
        <option value="registered" <?= $type === 'registered' ? 'selected' : '' ?>>Registered</option>
        <option value="walkin" <?= $type === 'walkin' ? 'selected' : '' ?>>Walk-in</option>
      </select>
    </div>
    <div class="field">
      <label for="rate">Rate</label>
      <select class="input" id="rate" name="rate">
        <option value="">All rates</option>
        <?php foreach (['none' => 'Regular', 'senior' => 'Senior Citizen', 'pwd' => 'PWD'] as $k => $l): ?><option value="<?= $k ?>" <?= $rate === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="sort">Sort by</label>
      <select class="input" id="sort" name="sort">
        <option value="name">Name (A–Z)</option>
        <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Last visit</option>
        <option value="visits" <?= $sort === 'visits' ? 'selected' : '' ?>>Most visits</option>
      </select>
    </div>
    <button class="btn btn-navy" type="submit"><?= icon('search', '', 16) ?> Search</button>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><?= icon('users', '', 36) ?><h3>No clients found</h3><p>Add a client or adjust your search.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="tbl tbl-responsive">
        <thead><tr><th>Client</th><th>Phone</th><th>Visits</th><th>Last Visit</th><th>Preferred</th><th>Rate</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c):
            $fill = json_encode([
                'client_id' => $c['id'], 'name' => $c['name'], 'phone' => $c['phone'] ?? '', 'email' => $c['email'] ?? '',
                'discount_type' => $c['discount_type'], 'preferred_barber_id' => $c['preferred_barber_id'] ?? '',
                'preferred_service_id' => $c['preferred_service_id'] ?? '', 'notes' => $c['notes'] ?? '',
            ]);
        ?>
          <tr>
            <td data-label="Client">
              <a class="tbl-person" href="<?= url('owner/client_view?id=' . (int) $c['id']) ?>">
                <?= avatar($c['name'], $c['avatar'], 36) ?>
                <span><strong><?= e($c['name']) ?></strong><small><?= e($c['email'] ?: ($c['user_id'] ? 'Registered' : 'Walk-in client')) ?></small></span>
              </a>
            </td>
            <td data-label="Phone" class="nowrap"><?= e($c['phone'] ?: '—') ?></td>
            <td data-label="Visits"><strong><?= (int) $c['visits'] ?></strong></td>
            <td data-label="Last Visit" class="t-muted nowrap"><?= $c['last_visit'] ? e(fmt_date($c['last_visit'])) : '—' ?></td>
            <td data-label="Preferred" class="t-muted"><?= e($c['barber_name'] ?: '—') ?><?= $c['service_name'] ? '<br>' . e($c['service_name']) : '' ?></td>
            <td data-label="Rate"><?= $c['discount_type'] === 'none' ? '<span class="pill">Regular</span>' : '<span class="badge badge-' . e($c['discount_type']) . '">' . e(discount_label($c['discount_type'])) . '</span>' ?></td>
            <td class="tbl-actions-cell">
              <div class="tbl-actions">
                <a class="icon-action" href="<?= url('owner/client_view?id=' . (int) $c['id']) ?>" title="View profile"><?= icon('eye', '', 16) ?></a>
                <button class="icon-action" type="button" title="Edit" data-modal-open="clientModal" data-title="Edit Client" data-fill="<?= e($fill) ?>"><?= icon('pencil', '', 15) ?></button>
                <form class="inline" method="post" data-confirm="Delete <?= e($c['name']) ?>? Their appointment history<?= $c['user_id'] ? ' and login account' : '' ?> will also be removed." data-confirm-style="danger" data-confirm-label="Delete client">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="client_id" value="<?= (int) $c['id'] ?>">
                  <button class="icon-action danger" type="submit" title="Delete"><?= icon('trash', '', 15) ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pager_links($page, $pages, $total, 'clients') ?>
  <?php endif; ?>
</section>

<?php include APPPATH . 'Views/partials/client_form_modal.php'; ?>
<?= $this->endSection() ?>
