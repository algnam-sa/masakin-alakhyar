<?php
require __DIR__ . '/_layout.php';
$user = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'password') {
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$user['id']]);
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), (string)$st->fetchColumn())) {
            flash('كلمة المرور الحالية غير صحيحة.', 'err');
        } elseif (mb_strlen($new) < 10) {
            flash('كلمة المرور الجديدة يجب أن تكون 10 أحرف على الأقل.', 'err');
        } elseif ($new !== (string)($_POST['new2'] ?? '')) {
            flash('كلمتا المرور الجديدتان غير متطابقتين.', 'err');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('تم تغيير كلمة المرور.');
        }
    } elseif ($action === 'profile') {
        $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([mb_substr(trim((string)($_POST['name'] ?? '')), 0, 80), $user['id']]);
        flash('تم حفظ الاسم.');
    } elseif ($action === 'add') {
        $username = trim((string)($_POST['username'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $exists->execute([$username]);
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,40}$/', $username)) {
            flash('اسم المستخدم من 3 إلى 40 حرفًا إنجليزيًا أو أرقامًا.', 'err');
        } elseif ($exists->fetchColumn()) {
            flash('اسم المستخدم مستخدم مسبقًا.', 'err');
        } elseif (mb_strlen($pass) < 10) {
            flash('كلمة المرور يجب أن تكون 10 أحرف على الأقل.', 'err');
        } else {
            $pdo->prepare('INSERT INTO users(username, name, password_hash, created_at) VALUES(?,?,?,?)')
                ->execute([$username, mb_substr(trim((string)($_POST['name'] ?? '')), 0, 80), password_hash($pass, PASSWORD_DEFAULT), now()]);
            flash("تمت إضافة المستخدم $username.");
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$user['id']) {
            flash('لا يمكنك حذف حسابك وأنت مسجّل الدخول به.', 'err');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('تم حذف المستخدم.');
        }
    }
    redirect('users.php');
}

$users = $pdo->query('SELECT id, username, name, created_at, last_login FROM users ORDER BY id')->fetchAll();
page_start('المستخدمون', 'users', $user);
?>
<div class="grid-2">
  <section class="card">
    <div class="card-head"><h2>حسابي</h2></div>
    <form method="post" class="form">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <label class="fld"><span>اسم المستخدم</span><input value="<?= e($user['username']) ?>" dir="ltr" disabled></label>
      <label class="fld"><span>الاسم الظاهر</span><input name="name" value="<?= e($user['name']) ?>" maxlength="80"></label>
      <button class="btn btn-light" type="submit">حفظ الاسم</button>
    </form>
    <hr>
    <form method="post" class="form" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <h3 class="h3">تغيير كلمة المرور</h3>
      <label class="fld"><span>كلمة المرور الحالية</span><input type="password" name="current" dir="ltr" required autocomplete="current-password"></label>
      <label class="fld"><span>كلمة المرور الجديدة (10 أحرف على الأقل)</span><input type="password" name="new" dir="ltr" required minlength="10" autocomplete="new-password"></label>
      <label class="fld"><span>تأكيد كلمة المرور الجديدة</span><input type="password" name="new2" dir="ltr" required minlength="10" autocomplete="new-password"></label>
      <button class="btn btn-primary" type="submit">تغيير كلمة المرور</button>
    </form>
  </section>

  <section class="card">
    <div class="card-head"><h2>المستخدمون</h2></div>
    <table class="table compact">
      <thead><tr><th>المستخدم</th><th>آخر دخول</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><b dir="ltr"><?= e($u['username']) ?></b><?php if ($u['name']): ?><br><span class="muted small"><?= e($u['name']) ?></span><?php endif; ?></td>
          <td class="muted small"><?= e($u['last_login'] ? substr($u['last_login'], 0, 16) : 'لم يدخل بعد') ?></td>
          <td>
            <?php if ((int)$u['id'] === (int)$user['id']): ?><span class="pill">أنت</span>
            <?php else: ?>
            <form method="post" data-confirm="حذف المستخدم <?= e($u['username']) ?>؟"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="icon-btn danger" type="submit" aria-label="حذف <?= e($u['username']) ?>"><?= ico('trash') ?></button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <hr>
    <form method="post" class="form" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <h3 class="h3">إضافة مستخدم</h3>
      <p class="muted small">كل المستخدمين لهم صلاحيات كاملة على اللوحة.</p>
      <label class="fld"><span>الاسم</span><input name="name" maxlength="80"></label>
      <label class="fld"><span>اسم المستخدم (بالإنجليزية)</span><input name="username" dir="ltr" required autocomplete="off"></label>
      <label class="fld"><span>كلمة المرور (10 أحرف على الأقل)</span><input type="password" name="password" dir="ltr" required minlength="10" autocomplete="new-password"></label>
      <button class="btn btn-light" type="submit"><?= ico('plus') ?>إضافة المستخدم</button>
    </form>
  </section>
</div>
<?php page_end();
