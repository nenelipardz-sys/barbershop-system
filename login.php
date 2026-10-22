<?php
require __DIR__ . '/includes/bootstrap.php';

// Already signed in? Go straight to the right home page.
if ($current = Auth::user()) {
    redirect($current->homeUrl());
}

$errors = [];
if (is_post()) {
    verify_csrf();
    try {
        $user = Auth::attempt(input('login'), (string) ($_POST['password'] ?? ''), !empty($_POST['remember']));
        flash('success', greeting() . ', ' . $user->getFirstName() . '! Welcome back.');
        redirect($user->homeUrl()); // the system checks the role and redirects
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        keep_old(['login' => input('login'), 'remember' => !empty($_POST['remember'])]);
    }
}

$authTitle = 'Sign In';
require __DIR__ . '/includes/auth_top.php';
?>
      <form class="auth-card" method="post" action="<?= url('login.php') ?>" novalidate>
        <?= csrf_field() ?>
        <h1>Welcome Back!</h1>
        <p class="sub">Sign in to your account</p>

        <?php foreach (flashes() as $f): ?>
          <div class="a-alert <?= e($f['type']) ?>"><?= icon($f['type'] === 'success' ? 'check-circle' : 'info', '', 18) ?><span><?= e($f['message']) ?></span></div>
        <?php endforeach; ?>
        <?php if ($errors): ?>
          <div class="a-alert error"><?= icon('alert', '', 18) ?><span><?= e(implode(' ', $errors)) ?></span></div>
        <?php endif; ?>

        <div class="a-field">
          <span class="a-ico"><?= icon('user', '', 23, 1.6) ?></span>
          <input class="a-input" type="text" name="login" placeholder="Email or Username" autocomplete="username"
                 value="<?= e($_SESSION['old']['login'] ?? '') ?>" required>
        </div>
        <div class="a-field">
          <span class="a-ico"><?= icon('lock', '', 23, 1.6) ?></span>
          <input class="a-input" type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
          <button class="a-eye" type="button" data-toggle-password="password" aria-label="Show password">
            <span class="hide"><?= icon('eye-off', '', 21, 1.7) ?></span>
            <span class="show"><?= icon('eye', '', 21, 1.7) ?></span>
          </button>
        </div>

        <div class="a-row">
          <label class="a-check">
            <input type="checkbox" name="remember" value="1" <?= !empty($_SESSION['old']) && empty($_SESSION['old']['remember']) ? '' : 'checked' ?>>
            <span class="box"><?= icon('check', '', 15, 3) ?></span>
            Remember me
          </label>
          <a class="a-link" href="<?= url('forgot_password.php') ?>">Forgot password?</a>
        </div>

        <button class="a-btn" type="submit">Login <?= icon('arrow-right', '', 20, 1.8) ?></button>

        <p class="a-foot">Don’t have an account?<a href="<?= url('register.php') ?>">Register here</a></p>
      </form>
<?php require __DIR__ . '/includes/auth_bottom.php'; ?>
