<?php
require __DIR__ . '/includes/bootstrap.php';

if ($current = Auth::user()) {
    redirect($current->homeUrl());
}

/*
 * Two-step reset without email:
 *  1) verify the account with email/username + the registered phone number
 *  2) set a new password
 */
$errors = [];
$step = isset($_SESSION['reset_user']) ? 2 : 1;

if (is_post()) {
    verify_csrf();
    if (input('action') === 'cancel') {
        unset($_SESSION['reset_user']);
        redirect('forgot_password.php');
    }
    if ($step === 1) {
        $login = input('login');
        $phone = preg_replace('/\D+/', '', input('phone'));
        $user = $login !== '' ? User::findByLogin($login) : null;
        if ($login === '' || $phone === '') {
            $errors[] = 'Please enter your email/username and phone number.';
        } elseif (!$user || preg_replace('/\D+/', '', (string) $user->getPhone()) !== $phone) {
            $errors[] = 'We could not verify those details. Please check and try again, or contact the shop.';
        } elseif (!$user->isActive()) {
            $errors[] = 'This account is inactive. Please contact the shop owner.';
        } else {
            $_SESSION['reset_user'] = $user->getId();
            $_SESSION['reset_expires'] = time() + 600;
            redirect('forgot_password.php');
        }
        keep_old(['login' => $login]);
    } else {
        if (($_SESSION['reset_expires'] ?? 0) < time()) {
            unset($_SESSION['reset_user']);
            flash('error', 'Your reset session expired. Please verify your account again.');
            redirect('forgot_password.php');
        }
        $errors = User::validatePassword((string) ($_POST['password'] ?? ''), (string) ($_POST['password_confirm'] ?? ''));
        if (!$errors) {
            $user = User::find((int) $_SESSION['reset_user']);
            $user->setPassword((string) $_POST['password']);
            unset($_SESSION['reset_user'], $_SESSION['reset_expires']);
            flash('success', 'Your password has been reset. You can now sign in.');
            redirect('login.php');
        }
    }
}

$authTitle = 'Reset Password';
$compact = true;
require __DIR__ . '/includes/auth_top.php';
?>
      <form class="auth-card" method="post" action="<?= url('forgot_password.php') ?>" novalidate>
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

        <p class="a-foot">Remembered it?<a href="<?= url('login.php') ?>">Back to sign in</a></p>
      </form>
<?php require __DIR__ . '/includes/auth_bottom.php'; ?>
