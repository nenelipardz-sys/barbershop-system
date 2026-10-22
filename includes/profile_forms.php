<?php
/**
 * Profile + password cards for the signed-in user. Expects $user; optional $extraProfileFields (callable).
 */
?>
<section class="card">
  <div class="card-head"><h2>My Profile</h2></div>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <div class="upload-row mb-16">
      <span id="meAvatarWrap"><?= str_replace('class="avatar', 'id="meAvatar" class="avatar', avatar($user->getName(), $user->getAvatar(), 72)) ?></span>
      <div class="field">
        <label for="me-avatar">Profile photo</label>
        <input class="file-input" type="file" id="me-avatar" name="avatar" accept="image/*" data-preview-target="meAvatar">
        <span class="hint">JPG, PNG or WEBP · max 3 MB</span>
      </div>
    </div>
    <div class="form-grid">
      <div class="field"><label for="me-name">Full name</label><input class="input" id="me-name" name="name" value="<?= e($user->getName()) ?>" required></div>
      <div class="field"><label for="me-username">Username</label><input class="input" id="me-username" name="username" value="<?= e($user->getUsername()) ?>" required></div>
      <div class="field"><label for="me-email">Email</label><input class="input" type="email" id="me-email" name="email" value="<?= e($user->getEmail()) ?>" required></div>
      <div class="field"><label for="me-phone">Phone</label><input class="input" id="me-phone" name="phone" value="<?= e($user->getPhone()) ?>"></div>
      <?php if (isset($extraProfileFields)) { $extraProfileFields(); } ?>
    </div>
    <div class="page-actions mt-16">
      <button class="btn btn-gold" type="submit"><?= icon('check', '', 17, 2.2) ?> Save Profile</button>
    </div>
  </form>
  <?php if ($user->getAvatar()): ?>
    <form method="post" class="mt-8" data-confirm="Remove your profile photo?">
      <?= csrf_field() ?><input type="hidden" name="action" value="remove_avatar">
      <button class="link-btn" type="submit" style="color:var(--red)">Remove photo</button>
    </form>
  <?php endif; ?>
</section>

<section class="card">
  <div class="card-head"><h2>Change Password</h2></div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="form-grid">
      <div class="field span-2"><label for="pw-current">Current password</label><input class="input" type="password" id="pw-current" name="current_password" required autocomplete="current-password"></div>
      <div class="field"><label for="pw-new">New password</label><input class="input" type="password" id="pw-new" name="password" minlength="6" required autocomplete="new-password"></div>
      <div class="field"><label for="pw-confirm">Confirm new password</label><input class="input" type="password" id="pw-confirm" name="password_confirm" minlength="6" required autocomplete="new-password"></div>
    </div>
    <div class="page-actions mt-16"><button class="btn btn-navy" type="submit"><?= icon('lock', '', 16) ?> Update Password</button></div>
  </form>
</section>
