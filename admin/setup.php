<?php
/** First run only: create the first admin account. Disabled once any user exists. */
require __DIR__ . '/_layout.php';

if (users_count() > 0) redirect('login.php');

$error = '';
$old = ['name' => '', 'username' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['name'] = trim((string)($_POST['name'] ?? ''));
    $old['username'] = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.@-]{3,40}$/', $old['username'])) {
        $error = 'اسم المستخدم من 3 إلى 40 حرفًا إنجليزيًا أو أرقامًا (يمكن استخدام . _ - @).';
    } elseif (mb_strlen($pass) < 10) {
        $error = 'كلمة المرور يجب أن تكون 10 أحرف على الأقل.';
    } elseif ($pass !== $pass2) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } else {
        // The first account is always a full administrator
        db()->prepare('INSERT INTO users(username, name, password_hash, is_admin, perms, created_at) VALUES(?,?,?,1,?,?)')
            ->execute([$old['username'], $old['name'], password_hash($pass, PASSWORD_DEFAULT), '[]', now()]);
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$old['username']]);
        login_user($st->fetch());
        flash('تم إنشاء حساب المدير. أهلًا بك في لوحة التحكم.');
        redirect('index.php');
    }
}

page_start('إعداد لوحة التحكم');
?>
<div class="auth-card">
  <h1>إعداد لوحة التحكم</h1>
  <p class="muted">هذه أول مرة تُفتح فيها اللوحة. أنشئ حساب المدير الذي ستدخل به.</p>
  <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form" autocomplete="off">
    <?= csrf_field() ?>
    <label class="fld"><span>الاسم</span><input name="name" value="<?= e($old['name']) ?>" maxlength="80"></label>
    <label class="fld"><span>اسم المستخدم (بالإنجليزية)</span><input name="username" dir="ltr" required value="<?= e($old['username']) ?>" autocomplete="username"></label>
    <label class="fld"><span>كلمة المرور (10 أحرف على الأقل)</span><input type="password" name="password" dir="ltr" required minlength="10" autocomplete="new-password"></label>
    <label class="fld"><span>تأكيد كلمة المرور</span><input type="password" name="password2" dir="ltr" required minlength="10" autocomplete="new-password"></label>
    <button class="btn btn-primary btn-block" type="submit">إنشاء الحساب والدخول</button>
  </form>
</div>
<?php page_end();
