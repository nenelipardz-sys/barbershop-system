<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
      <form class="auth-card" method="post" action="<?= url('register') ?>" novalidate>
        <?= csrf_field() ?>
        <h1>Create Account</h1>
        <p class="sub">Register to book your next appointment</p>

        <?php if ($errors): ?>
          <div class="a-alert error"><?= icon('alert', '', 18) ?><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <div class="a-field">
          <span class="a-ico"><?= icon('user', '', 22, 1.6) ?></span>
          <input class="a-input" type="text" name="name" placeholder="Full Name" value="<?= e(old('name')) ?>" autocomplete="name" required>
        </div>
        <div class="a-grid">
          <div class="a-field">
            <span class="a-ico"><?= icon('user-round', '', 22, 1.6) ?></span>
            <input class="a-input" type="text" name="username" placeholder="Username" value="<?= e(old('username')) ?>" autocomplete="username" required>
          </div>
          <div class="a-field">
            <span class="a-ico"><?= icon('phone', '', 20, 1.6) ?></span>
            <input class="a-input" type="tel" name="phone" placeholder="Phone Number" value="<?= e(old('phone')) ?>" autocomplete="tel" required>
          </div>
        </div>
        <div class="a-field">
          <span class="a-ico"><?= icon('mail', '', 21, 1.6) ?></span>
          <input class="a-input" type="email" name="email" placeholder="Email Address" value="<?= e(old('email')) ?>" autocomplete="email" required>
        </div>
        <div class="a-grid">
          <div class="a-field">
            <span class="a-ico"><?= icon('lock', '', 22, 1.6) ?></span>
            <input class="a-input" type="password" id="password" name="password" placeholder="Password" autocomplete="new-password" required>
            <button class="a-eye" type="button" data-toggle-password="password" aria-label="Show password">
              <span class="hide"><?= icon('eye-off', '', 20, 1.7) ?></span><span class="show"><?= icon('eye', '', 20, 1.7) ?></span>
            </button>
          </div>
          <div class="a-field">
            <span class="a-ico"><?= icon('lock', '', 22, 1.6) ?></span>
            <input class="a-input" type="password" id="password_confirm" name="password_confirm" placeholder="Confirm Password" autocomplete="new-password" required>
            <button class="a-eye" type="button" data-toggle-password="password_confirm" aria-label="Show password">
              <span class="hide"><?= icon('eye-off', '', 20, 1.7) ?></span><span class="show"><?= icon('eye', '', 20, 1.7) ?></span>
            </button>
          </div>
        </div>
        <p class="a-hint">At least 6 characters.</p>

        <div class="a-row">
          <label class="a-check">
            <input type="checkbox" name="terms" value="1" <?= old('name') === '' || !empty($_POST['terms']) ? 'checked' : '' ?>>
            <span class="box"><?= icon('check', '', 15, 3) ?></span>
            I agree to the shop’s booking policies
          </label>
        </div>

        <button class="a-btn" type="submit">Register <?= icon('arrow-right', '', 20, 1.8) ?></button>
        <p class="a-foot">Already have an account?<a href="<?= url('login') ?>">Sign in here</a></p>
      </form>
<?= $this->endSection() ?>
