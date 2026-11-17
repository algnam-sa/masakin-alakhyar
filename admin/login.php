<?php
require __DIR__ . '/_layout.php';

if (users_count() === 0) redirect('setup.php');
if (current_user()) redirect('index.php');

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string)($_POST['username'] ?? ''));
    if (too_many('login', 5, 900)) {
        $error = 'محاولات دخول كثيرة. انتظر 15 دقيقة ثم حاول مرة أخرى.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        // Verify against a dummy hash when the user does not exist, so timing does not reveal valid usernames
        $hash = $u['password_hash'] ?? '$2y$10$wgsLK8PJYlT.tNSb9KPd9uPu/xEc2zz8h/Pu3qh0n5Um8br/52Y2a';
        $valid = password_verify((string)($_POST['password'] ?? ''), $hash); // always runs, even for unknown users
        if ($u && $valid) {
            clear_attempts('login');
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $u['id']]);
            }
            login_user($u);
            redirect('index.php');
        }
        record_attempt('login');
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}

$site = content('settings');
page_start('تسجيل الدخول');
?>
<div class="auth-card">
  <?php if (!empty($site['logo_dark'])): ?><img class="auth-logo" src="../<?= url($site['logo_dark']) ?>" alt=""><?php endif; ?>
  <h1>تسجيل الدخول</h1>
  <p class="muted">لوحة تحكم موقع <?= e($site['site_name'] ?? '') ?></p>
  <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label class="fld"><span>اسم المستخدم</span><input name="username" dir="ltr" required value="<?= e($username) ?>" autocomplete="username" autofocus></label>
    <label class="fld"><span>كلمة المرور</span><input type="password" name="password" dir="ltr" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block" type="submit">دخول</button>
  </form>
</div>
<?php page_end();
