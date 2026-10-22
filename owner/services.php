<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = Auth::requireRole('owner');

if (is_post()) {
    verify_csrf();
    $action = input('action');
    try {
        $id = (int) input('service_id');
        $service = $id ? Service::find($id) : new Service();
        if (!$service) {
            throw new ValidationException(['Service not found.']);
        }
        if ($action === 'save') {
            $service->fill($_POST);
            $errors = $service->validate();
            if ($errors) {
                throw new ValidationException($errors);
            }
            $image = upload_image('image', 'services');
            if ($image) {
                delete_upload($service->image);
                $service->image = $image;
            }
            $service->save();
            flash('success', $id ? 'Service updated.' : 'Service added to the catalog.');
            if ($id) {
                Notification::toOwners('Service updated', $service->name . ' details were updated.', 'owner/services.php');
            }
        } elseif ($action === 'archive') {
            $service->archive();
            flash('success', $service->name . ' was archived. It is hidden from booking but kept in reports.');
        } elseif ($action === 'restore') {
            $service->restore();
            flash('success', $service->name . ' is available for booking again.');
        } elseif ($action === 'delete') {
            if ($service->usageCount() > 0) {
                throw new ValidationException([$service->name . ' is used by ' . $service->usageCount() . ' appointments. Archive it instead to keep your records.']);
            }
            delete_upload($service->image);
            Database::query('DELETE FROM services WHERE id = ?', [$service->id]);
            flash('success', 'Service deleted.');
        }
    } catch (ValidationException $e) {
        flash('error', $e->getErrors());
        if ($action === 'save') {
            keep_old($_POST + ['_modal' => 'serviceModal']);
        }
    }
    redirect('owner/services.php' . (input('tab') ? '?tab=' . urlencode(input('tab')) : ''));
}

$tab = in_array(input('tab'), ['archived', 'all'], true) ? input('tab') : 'active';
$all = Service::all(false);
$list = array_values(array_filter($all, fn($s) => $tab === 'all' || $s->status === $tab));
$usage = [];
foreach (Database::all("SELECT service_id, COUNT(*) n, SUM(status = 'completed' AND appointment_date >= ?) month FROM appointments GROUP BY service_id", [date('Y-m-01')]) as $r) {
    $usage[(int) $r['service_id']] = $r;
}
$countActive = count(array_filter($all, fn($s) => $s->isActive()));
$reopen = old('_modal') === 'serviceModal';

$pageTitle = 'Services';
$active = 'services';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Services</h1>
    <p>Define service types, durations and prices. Archive services you no longer offer.</p>
  </div>
  <div class="page-actions">
    <button class="btn btn-gold" type="button" data-modal-open="serviceModal" data-reset data-title="Add Service"><?= icon('plus', '', 18, 2.2) ?> Add Service</button>
  </div>
</div>

<nav class="tabs">
  <a class="<?= $tab === 'active' ? 'active' : '' ?>" href="?tab=active">Active<span class="count"><?= $countActive ?></span></a>
  <a class="<?= $tab === 'archived' ? 'active' : '' ?>" href="?tab=archived">Archived<span class="count"><?= count($all) - $countActive ?></span></a>
  <a class="<?= $tab === 'all' ? 'active' : '' ?>" href="?tab=all">All<span class="count"><?= count($all) ?></span></a>
</nav>

<?php if (!$list): ?>
  <div class="card empty"><?= icon('scissors', '', 36) ?><h3>No services here</h3><p><?= $tab === 'archived' ? 'Archived services will appear here.' : 'Add your first service.' ?></p></div>
<?php endif; ?>

<div class="svc-grid">
  <?php foreach ($list as $s):
      $u = $usage[$s->id] ?? ['n' => 0, 'month' => 0];
      $fill = json_encode([
          'service_id' => $s->id, 'name' => $s->name, 'description' => $s->description ?? '', 'price' => $s->price,
          'duration' => $s->duration, 'sort_order' => $s->sortOrder, 'image_preview' => media_url($s->image) ?? url('assets/img/logo-monogram.png'),
      ]);
  ?>
    <article class="svc-tile<?= $s->isActive() ? '' : ' archived' ?>">
      <?= service_img($s->image, $s->name, 'svc-cover') ?>
      <div class="svc-tile-body">
        <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start">
          <h3><?= e($s->name) ?></h3>
          <span class="badge badge-<?= e($s->status) ?>"><?= $s->isActive() ? 'Active' : 'Archived' ?></span>
        </div>
        <p><?= e($s->description ?: 'No description.') ?></p>
        <div class="svc-meta">
          <span class="price"><?= peso($s->price) ?></span>
          <span class="muted"><?= icon('clock', '', 14) ?> <?= (int) $s->duration ?> mins</span>
        </div>
        <small class="muted"><?= (int) $u['month'] ?> completed this month · <?= (int) $u['n'] ?> total bookings</small>
      </div>
      <div class="svc-tile-actions">
        <button class="btn btn-outline btn-sm" type="button" data-modal-open="serviceModal" data-title="Edit Service" data-fill="<?= e($fill) ?>"><?= icon('pencil', '', 14) ?> Edit</button>
        <form class="inline" method="post" <?= $s->isActive() ? 'data-confirm="Archive ' . e($s->name) . '? Clients will no longer be able to book it."' : '' ?>>
          <?= csrf_field() ?><input type="hidden" name="tab" value="<?= e($tab) ?>"><input type="hidden" name="service_id" value="<?= $s->id ?>">
          <input type="hidden" name="action" value="<?= $s->isActive() ? 'archive' : 'restore' ?>">
          <button class="btn btn-sm <?= $s->isActive() ? 'btn-light' : 'btn-success' ?>" type="submit"><?= icon($s->isActive() ? 'archive' : 'rotate-ccw', '', 14) ?> <?= $s->isActive() ? 'Archive' : 'Restore' ?></button>
        </form>
        <form class="inline" method="post" data-confirm="Delete <?= e($s->name) ?> permanently?" data-confirm-style="danger" data-confirm-label="Delete">
          <?= csrf_field() ?><input type="hidden" name="tab" value="<?= e($tab) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="service_id" value="<?= $s->id ?>">
          <button class="icon-action danger" type="submit" title="Delete"><?= icon('trash', '', 15) ?></button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<div class="modal" id="serviceModal" <?= $reopen ? 'data-auto-open' : '' ?>>
  <div class="modal-backdrop"></div>
  <form class="modal-dialog" method="post" enctype="multipart/form-data">
    <div class="modal-head"><h3><?= $reopen && old('service_id') ? 'Edit Service' : 'Add Service' ?></h3><button class="icon-btn" type="button" data-modal-close aria-label="Close"><?= icon('x', '', 18) ?></button></div>
    <div class="modal-body">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="tab" value="<?= e($tab) ?>" data-keep-value>
      <input type="hidden" name="service_id" value="<?= e(old('service_id')) ?>">
      <div class="upload-row mb-16">
        <img class="svc-img" id="svcPreview" data-src="image_preview" data-preview data-default="<?= url('assets/img/logo-monogram.png') ?>" src="<?= url('assets/img/logo-monogram.png') ?>" alt="" style="background:#0c1a2e;object-fit:contain">
        <div class="field"><label for="svc-image">Service photo</label><input class="file-input" type="file" id="svc-image" name="image" accept="image/*" data-preview-target="svcPreview"><span class="hint">JPG, PNG or WEBP · max 3 MB</span></div>
      </div>
      <div class="form-grid">
        <div class="field span-2"><label for="svc-name">Service name</label><input class="input" id="svc-name" name="name" value="<?= e(old('name')) ?>" required maxlength="100"></div>
        <div class="field"><label for="svc-price">Price (₱)</label><input class="input" type="number" id="svc-price" name="price" min="1" step="0.01" value="<?= e(old('price')) ?>" required></div>
        <div class="field"><label for="svc-duration">Duration (minutes)</label><input class="input" type="number" id="svc-duration" name="duration" min="5" max="240" step="5" value="<?= e(old('duration', '30')) ?>" required></div>
        <div class="field span-2"><label for="svc-desc">Description</label><textarea class="input" id="svc-desc" name="description" maxlength="255"><?= e(old('description')) ?></textarea></div>
        <div class="field"><label for="svc-sort">Display order</label><input class="input" type="number" id="svc-sort" name="sort_order" value="<?= e(old('sort_order', '0')) ?>"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" type="button" data-modal-close>Cancel</button>
      <button class="btn btn-gold" type="submit"><?= icon('check', '', 17, 2.2) ?> Save Service</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
