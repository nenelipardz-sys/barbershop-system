<?php
require __DIR__ . '/includes/bootstrap.php';

if ($current = Auth::user()) {
    redirect($current->homeUrl());
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $data = [
        'name' => input('name'), 'username' => input('username'), 'email' => input('email'), 'phone' => input('phone'),
        'password' => (string) ($_POST['password'] ?? ''), 'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
    ];
    if (empty($_POST['terms'])) {
        $errors[] = 'Please agree to the shop policies to continue.';
    }
    if (!$errors) {
        try {
            $pdo = Database::conn();
            $pdo->beginTransaction();
            // Clients register themselves; the role is always "client".
            $userId = User::create($data, 'client');
            $user = User::find($userId);
            // Link to an existing walk-in profile with the same email, otherwise create one.
            $existing = Database::one('SELECT id FROM clients WHERE email = ? AND user_id IS NULL LIMIT 1', [strtolower($data['email'])]);
            if ($existing) {
                Database::query('UPDATE clients SET user_id = ?, name = ?, phone = COALESCE(?, phone) WHERE id = ?', [$userId, $data['name'], $data['phone'] ?: null, $existing['id']]);
            } else {
                ClientProfile::createForUser($user);
            }
            $pdo->commit();
            Notification::toOwners('New client registered', $data['name'] . ' created a client account.', 'owner/clients.php');
            Auth::login($user);
            flash('success', 'Welcome to Lumié Talent, ' . $user->getFirstName() . '! Your account is ready.');
            redirect($user->homeUrl());
        } catch (ValidationException $e) {
            Database::conn()->inTransaction() && Database::conn()->rollBack();
            $errors = $e->getErrors();
        }
    }
    keep_old($data);
}

$authTitle = 'Create Account';
$compact = true;
require __DIR__ . '/includes/auth_top.php';
?>
      <form class="auth-card" method="post" action="<?= url('register.php') ?>" novalidate>
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
        <p class="a-foot">Already have an account?<a href="<?= url('login.php') ?>">Sign in here</a></p>
      </form>
<?php require __DIR__ . '/includes/auth_bottom.php'; ?>
