<?php
/**
 * One-click installer: creates the `cut_schedule` database and loads the demo data
 * from database/cut_schedule.sql. (You can also import that file in phpMyAdmin.)
 */
define('SKIP_DB_CHECK', true);
require __DIR__ . '/includes/bootstrap.php';

$message = null;
$error = null;
$installed = false;

try {
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($mysqli->connect_errno) {
        throw new RuntimeException('Cannot connect to MySQL: ' . $mysqli->connect_error . '. Start MySQL in the XAMPP Control Panel.');
    }
    $mysqli->set_charset('utf8mb4');
    $res = $mysqli->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $mysqli->real_escape_string(DB_NAME) . "' AND table_name = 'users'");
    $installed = (int) $res->fetch_row()[0] > 0;

    if (is_post()) {
        verify_csrf();
        if ($installed && empty($_POST['confirm_reset'])) {
            throw new RuntimeException('Tick the confirmation box to reinstall. This erases all current data.');
        }
        $sql = file_get_contents(__DIR__ . '/database/cut_schedule.sql');
        if (!$mysqli->multi_query($sql)) {
            throw new RuntimeException('Import failed: ' . $mysqli->error);
        }
        do {
            if ($r = $mysqli->store_result()) {
                $r->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        if ($mysqli->errno) {
            throw new RuntimeException('Import failed: ' . $mysqli->error);
        }
        $installed = true;
        $message = 'Database installed successfully with demo data.';
    }
} catch (Throwable $t) {
    $error = $t->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup · Cut &amp; Schedule</title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <style>
    body { background: radial-gradient(900px 500px at 50% -10%, #1a2a47, #070f1c); min-height: 100vh; display: grid; place-items: center; padding: 20px; }
    .setup { max-width: 560px; width: 100%; }
    .setup .brand-name { color: #f1ead9; }
    .setup .brand { margin-bottom: 14px; }
    .setup .card { padding: 26px 28px; }
    .setup h1 { font-size: 22px; margin-bottom: 8px; }
    .setup .acct { width: 100%; font-size: 13.5px; margin: 14px 0; border-collapse: collapse; }
    .setup .acct td, .setup .acct th { padding: 8px 10px; border-bottom: 1px solid var(--line-2); text-align: left; }
    .setup .ok { background: var(--green-bg); color: #1f6e3b; padding: 10px 14px; border-radius: 9px; margin-bottom: 14px; }
    .setup .err { background: var(--red-bg); color: #9b2c2c; padding: 10px 14px; border-radius: 9px; margin-bottom: 14px; }
  </style>
</head>
<body>
  <div class="setup">
    <div class="brand"><?= brand_emblem(64) ?><span class="brand-name">CUT &amp; SCHEDULE</span><span class="brand-sub">Barber Shop</span></div>
    <div class="card">
      <h1>Database Setup</h1>
      <p class="muted">Creates the <b><?= e(DB_NAME) ?></b> database on <b><?= e(DB_HOST) ?></b> and loads the demo shop data.</p>
      <div class="mt-16"></div>
      <?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

      <?php if ($installed): ?>
        <table class="acct">
          <tr><th>Role</th><th>Username</th><th>Password</th></tr>
          <tr><td>Owner</td><td>admin1</td><td>admin2026</td></tr>
          <tr><td>Staff</td><td>mark / jay / luis</td><td>staff123</td></tr>
          <tr><td>Client</td><td>arizza / paulo</td><td>client123</td></tr>
        </table>
        <a class="btn btn-gold btn-block btn-lg" href="<?= url('login.php') ?>">Go to Login</a>
        <form method="post" class="mt-24">
          <?= csrf_field() ?>
          <label class="check"><input type="checkbox" name="confirm_reset" value="1"> Erase everything and reinstall the demo data</label>
          <button class="btn btn-light btn-block mt-8" type="submit">Reinstall Database</button>
        </form>
      <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <button class="btn btn-gold btn-block btn-lg" type="submit">Install Database</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
