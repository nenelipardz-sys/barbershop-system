<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head">
  <div>
    <h1>Favorites</h1>
    <p>Tap the heart to save your favorite barbers and services for quick booking.</p>
  </div>
</div>

<section class="card mb-16" style="margin-bottom:20px">
  <div class="card-head"><h2>Choose Your Barber</h2><span class="muted"><?= count($favIds['barber']) ?> saved</span></div>
  <div class="barber-grid">
    <?php foreach ($barbers as $b): $on = in_array($b->getId(), $favIds['barber'], true); ?>
      <div class="barber-card" style="cursor:default">
        <button type="button" class="fav-btn<?= $on ? ' on' : '' ?>" data-fav="barber:<?= $b->getId() ?>" aria-label="<?= $on ? 'Remove from favorites' : 'Add to favorites' ?>"><?= icon($on ? 'heart-fill' : 'heart', '', 18) ?></button>
        <?= avatar($b->getName(), $b->getAvatar(), 76) ?>
        <strong><?= e(explode(' ', $b->getName())[0]) ?> <?= e(mb_substr(explode(' ', $b->getName())[1] ?? '', 0, 1)) ?>.</strong>
        <small style="color:<?= $b->getPosition() === 'Senior Barber' ? 'var(--blue)' : 'var(--muted)' ?>"><?= e($b->getPosition()) ?></small>
        <span class="rating"><?= number_format($b->getRating(), 1) ?> <?= icon('star', '', 14) ?></span>
        <small><?= $visitsWith[$b->getId()] ?? 0 ?> visits with you</small>
        <a class="btn btn-gold btn-sm mt-8" href="<?= url('client/book?barber=' . $b->getId()) ?>">Book</a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="card">
  <div class="card-head"><h2>Services</h2><span class="muted"><?= count($favIds['service']) ?> saved</span></div>
  <div class="svc-cards">
    <?php foreach ($services as $s): $on = in_array($s->id, $favIds['service'], true); ?>
      <div class="svc-card" style="position:relative">
        <?= service_img($s->image, $s->name) ?>
        <div style="flex:1">
          <strong><?= e($s->name) ?></strong>
          <span class="price"><?= peso($s->price) ?></span>
          <span class="dur"><?= (int) $s->duration ?> mins</span>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
          <button type="button" class="fav-btn<?= $on ? ' on' : '' ?>" style="position:static" data-fav="service:<?= $s->id ?>" aria-label="<?= $on ? 'Remove from favorites' : 'Add to favorites' ?>"><?= icon($on ? 'heart-fill' : 'heart', '', 18) ?></button>
          <a class="btn btn-outline btn-sm" href="<?= url('client/book?service=' . $s->id) ?>">Book</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?= $this->endSection() ?>
