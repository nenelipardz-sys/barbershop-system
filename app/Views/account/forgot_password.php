<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
      <form class="auth-card" method="post" action="<?= url('forgot_password') ?>" novalidate>
        <?= csrf_field() ?>
        <h1><?= $step === 1 ? 'Forgot Password?' : 'Set New Password' ?></h1>
        <p class="sub"><?= $step === 1 ? 'Verify your account to reset your password' : 'Choose a new password for your account' ?></p>

        <?php foreach (flashes() as $f): ?>
          <div class="a-alert <?= e($f['type']) ?>"><?= icon('info', '', 18) ?><span><?= e($f['message']) ?></span></div>
        <?php endforeach; ?>
        <?php if ($errors): ?>
          <div class="a-alert error"><?= icon('alert', '', 18) ?><span><?= e(implode(' ', $errors)) ?></span></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
          <div class="a-field">
            <span class="a-ico"><?= icon('user', '', 23, 1.6) ?></span>
            <input class="a-input" type="text" name="login" placeholder="Email or Username" value="<?= e(old('login')) ?>" required autofocus>
          </div>
          <div class="a-field">
            <span class="a-ico"><?= icon('phone', '', 21, 1.6) ?></span>
            <input class="a-input" type="tel" name="phone" placeholder="Registered Phone Number" required>
          </div>
          <p class="a-hint">Enter the phone number saved on your account.</p>
          <button class="a-btn" type="submit">Verify Account <?= icon('arrow-right', '', 20, 1.8) ?></button>
        <?php else: ?>
          <div class="a-alert info"><?= icon('shield-check', '', 18) ?><span>Account verified. Enter your new password below.</span></div>
          <div class="a-field">
            <span class="a-ico"><?= icon('lock', '', 23, 1.6) ?></span>
            <input class="a-input" type="password" id="password" name="password" placeholder="New Password" autocomplete="new-password" required autofocus>
            <button class="a-eye" type="button" data-toggle-password="password" aria-label="Show password">
              <span class="hide"><?= icon('eye-off', '', 21, 1.7) ?></span><span class="show"><?= icon('eye', '', 21, 1.7) ?></span>
            </button>
          </div>
          <div class="a-field">
            <span class="a-ico"><?= icon('lock', '', 23, 1.6) ?></span>
            <input class="a-input" type="password" name="password_confirm" placeholder="Confirm New Password" autocomplete="new-password" required>
          </div>
          <button class="a-btn" type="submit">Reset Password <?= icon('arrow-right', '', 20, 1.8) ?></button>
          <p class="a-foot"><button type="submit" name="action" value="cancel" formnovalidate style="background:none;border:0;color:#e2bf72;cursor:pointer;padding:0">Use a different account</button></p>
        <?php endif; ?>

        <p class="a-foot">Remembered it?<a href="<?= url('login') ?>">Back to sign in</a></p>
      </form>
<?= $this->endSection() ?>
