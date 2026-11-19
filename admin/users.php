<?php
require __DIR__ . '/_layout.php';
require_once APP . '/permissions.php';
$user = require_login();
$pdo = db();
$isAdmin = can('admin');

$adminCount = fn() => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
$validUsername = fn(string $u) => (bool)preg_match('/^[A-Za-z0-9_.@-]{3,40}$/', $u);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* ---------- Own account (every user) ---------- */
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
        redirect('users.php');
    }
    if ($action === 'profile') {
        $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([mb_substr(trim((string)($_POST['name'] ?? '')), 0, 80), $user['id']]);
        flash('تم حفظ الاسم.');
        redirect('users.php');
    }

    /* ---------- Managing users (full administrators only) ---------- */
    require_perm('admin');

    if ($action === 'add') {
        $username = trim((string)($_POST['username'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $exists->execute([$username]);
        if (!$validUsername($username)) {
            flash('اسم المستخدم من 3 إلى 40 حرفًا إنجليزيًا أو أرقامًا.', 'err');
        } elseif ($exists->fetchColumn()) {
            flash('اسم المستخدم مستخدم مسبقًا.', 'err');
        } elseif (mb_strlen($pass) < 10) {
            flash('كلمة المرور يجب أن تكون 10 أحرف على الأقل.', 'err');
        } else {
            $admin = !empty($_POST['is_admin']) ? 1 : 0;
            $perms = $admin ? [] : clean_permissions($_POST['perms'] ?? []);
            $pdo->prepare('INSERT INTO users(username, name, password_hash, is_admin, perms, created_at) VALUES(?,?,?,?,?,?)')
                ->execute([$username, mb_substr(trim((string)($_POST['name'] ?? '')), 0, 80), password_hash($pass, PASSWORD_DEFAULT),
                           $admin, json_encode($perms), now()]);
            flash("تمت إضافة المستخدم $username" . ($admin || $perms ? '.' : '. لم تُحدد له صلاحيات بعد، فلن يرى شيئًا حتى تضيفها.'));
            redirect('users.php');
        }
        redirect('users.php?new=1');
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$id]);
        $target = $st->fetch();
        if (!$target) { flash('المستخدم غير موجود.', 'err'); redirect('users.php'); }
        $admin = !empty($_POST['is_admin']) ? 1 : 0;
        if ((int)$target['is_admin'] === 1 && !$admin && $adminCount() <= 1) {
            flash('يجب أن يبقى مدير عام واحد على الأقل. أضف مديرًا آخر قبل سحب هذه الصلاحية.', 'err');
            redirect('users.php?id=' . $id);
        }
        $perms = $admin ? [] : clean_permissions($_POST['perms'] ?? []);
        $pdo->prepare('UPDATE users SET name = ?, is_admin = ?, perms = ? WHERE id = ?')
            ->execute([mb_substr(trim((string)($_POST['name'] ?? '')), 0, 80), $admin, json_encode($perms), $id]);
        $newPass = (string)($_POST['new_password'] ?? '');
        if ($newPass !== '') {
            if (mb_strlen($newPass) < 10) {
                flash('تم حفظ الصلاحيات، لكن كلمة المرور الجديدة أقصر من 10 أحرف فلم تُغيَّر.', 'err');
                redirect('users.php?id=' . $id);
            }
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPass, PASSWORD_DEFAULT), $id]);
        }
        flash('تم حفظ بيانات المستخدم ' . $target['username'] . '.');
        redirect('users.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
        $st->execute([$id]);
        $wasAdmin = (int)$st->fetchColumn() === 1;
        if ($id === (int)$user['id']) {
            flash('لا يمكنك حذف حسابك وأنت مسجّل الدخول به.', 'err');
        } elseif ($wasAdmin && $adminCount() <= 1) {
            flash('لا يمكن حذف آخر مدير عام.', 'err');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('تم حذف المستخدم.');
        }
        redirect('users.php');
    }
    redirect('users.php');
}

/** Permission checkboxes, grouped. */
function perm_editor(bool $isAdminUser, array $perms): void
{
    ?>
    <div class="perm-admin">
      <label class="toggle"><input type="checkbox" name="is_admin" value="1" data-admin-toggle <?= $isAdminUser ? 'checked' : '' ?>><i></i>مدير عام (كل الصلاحيات)</label>
      <small class="help">المدير العام يرى كل شيء، ويضيف المستخدمين ويحدد صلاحياتهم.</small>
    </div>
    <div class="perm-groups<?= $isAdminUser ? ' is-off' : '' ?>" data-perm-groups>
      <?php foreach (permission_groups() as $group => $items): ?>
      <fieldset class="perm-group">
        <h4><?= e($group) ?> <button type="button" class="link" data-check-all>تحديد الكل</button></h4>
        <?php foreach ($items as $key => $label): ?>
          <label class="perm-check"><input type="checkbox" name="perms[]" value="<?= e($key) ?>" <?= in_array($key, $perms, true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
        <?php endforeach; ?>
      </fieldset>
      <?php endforeach; ?>
    </div>
    <?php
}

function perm_summary(array $u): string
{
    if ((int)$u['is_admin'] === 1) return '<span class="pill pill-admin">مدير عام</span>';
    $perms = json_decode((string)$u['perms'], true) ?: [];
    if (!$perms) return '<span class="muted small">بدون صلاحيات</span>';
    $groups = [];
    foreach (permission_groups() as $g => $items) {
        $n = count(array_intersect(array_keys($items), $perms));
        if ($n) $groups[] = '<span class="pill">' . e($g) . ' (' . $n . ')</span>';
    }
    return '<span class="perm-summary">' . implode('', $groups) . '</span>';
}

/* ---------- Edit one user (administrators) ---------- */
if ($isAdmin && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int)$_GET['id']]);
    $target = $st->fetch();
    if (!$target) { flash('المستخدم غير موجود.', 'err'); redirect('users.php'); }
    page_start('صلاحيات ' . ($target['name'] ?: $target['username']), 'users', $user);
    ?>
    <p><a href="users.php" class="link">← كل المستخدمين</a></p>
    <form method="post" class="card form">
      <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$target['id'] ?>">
      <div class="grid-2" style="gap:14px">
        <label class="fld"><span>اسم المستخدم</span><input value="<?= e($target['username']) ?>" dir="ltr" disabled></label>
        <label class="fld"><span>الاسم الظاهر</span><input name="name" value="<?= e($target['name']) ?>" maxlength="80"></label>
      </div>
      <h3 class="h3" style="margin-top:6px">الصلاحيات</h3>
      <?php perm_editor((int)$target['is_admin'] === 1, json_decode((string)$target['perms'], true) ?: []); ?>
      <label class="fld"><span>كلمة مرور جديدة (اختياري، 10 أحرف على الأقل)</span><input type="password" name="new_password" dir="ltr" minlength="10" autocomplete="new-password" placeholder="اتركه فارغًا لإبقاء كلمة المرور الحالية"></label>
      <div><button class="btn btn-primary" type="submit">حفظ</button></div>
    </form>
    <?php if ((int)$target['id'] !== (int)$user['id']): ?>
    <form method="post" class="danger-zone" data-confirm="حذف المستخدم <?= e($target['username']) ?> نهائيًا؟">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$target['id'] ?>">
      <button class="btn btn-danger-ghost" type="submit"><?= ico('trash') ?>حذف المستخدم</button>
    </form>
    <?php endif;
    page_end();
    exit;
}

page_start($isAdmin ? 'المستخدمون والصلاحيات' : 'حسابي', 'users', $user);
$users = $isAdmin ? $pdo->query('SELECT id, username, name, is_admin, perms, created_at, last_login FROM users ORDER BY is_admin DESC, id')->fetchAll() : [];
?>
<?php if ($isAdmin): ?>
<section class="card table-card">
  <div class="card-head" style="padding:18px 22px 0"><h2>المستخدمون</h2><a class="btn btn-primary btn-sm" href="#addUser"><?= ico('plus') ?>إضافة مستخدم</a></div>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>المستخدم</th><th>الصلاحيات</th><th>آخر دخول</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><b dir="ltr"><?= e($u['username']) ?></b><?php if ($u['name']): ?><br><span class="muted small"><?= e($u['name']) ?></span><?php endif; ?><?php if ((int)$u['id'] === (int)$user['id']): ?> <span class="pill">أنت</span><?php endif; ?></td>
        <td><?= perm_summary($u) ?></td>
        <td class="muted small nowrap"><?= e($u['last_login'] ? substr($u['last_login'], 0, 16) : 'لم يدخل بعد') ?></td>
        <td class="nowrap"><a class="btn btn-light btn-sm" href="users.php?id=<?= (int)$u['id'] ?>">تعديل الصلاحيات</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</section>

<section class="card" id="addUser">
  <div class="card-head"><h2>إضافة مستخدم</h2></div>
  <form method="post" class="form" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="grid-2" style="gap:14px">
      <label class="fld"><span>الاسم</span><input name="name" maxlength="80"></label>
      <label class="fld"><span>اسم المستخدم (بالإنجليزية)</span><input name="username" dir="ltr" required autocomplete="off"></label>
    </div>
    <label class="fld"><span>كلمة المرور (10 أحرف على الأقل)</span><input type="password" name="password" dir="ltr" required minlength="10" autocomplete="new-password"></label>
    <h3 class="h3" style="margin-top:6px">الصلاحيات</h3>
    <?php perm_editor(false, []); ?>
    <div><button class="btn btn-primary" type="submit"><?= ico('plus') ?>إضافة المستخدم</button></div>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <div class="card-head"><h2>حسابي</h2><?= $user['is_admin'] ? '<span class="pill pill-admin">مدير عام</span>' : '' ?></div>
  <div class="grid-2">
    <form method="post" class="form">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <label class="fld"><span>اسم المستخدم</span><input value="<?= e($user['username']) ?>" dir="ltr" disabled></label>
      <label class="fld"><span>الاسم الظاهر</span><input name="name" value="<?= e($user['name']) ?>" maxlength="80"></label>
      <div><button class="btn btn-light" type="submit">حفظ الاسم</button></div>
    </form>
    <form method="post" class="form" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <label class="fld"><span>كلمة المرور الحالية</span><input type="password" name="current" dir="ltr" required autocomplete="current-password"></label>
      <label class="fld"><span>كلمة المرور الجديدة (10 أحرف على الأقل)</span><input type="password" name="new" dir="ltr" required minlength="10" autocomplete="new-password"></label>
      <label class="fld"><span>تأكيد كلمة المرور الجديدة</span><input type="password" name="new2" dir="ltr" required minlength="10" autocomplete="new-password"></label>
      <div><button class="btn btn-primary" type="submit">تغيير كلمة المرور</button></div>
    </form>
  </div>
</section>
<?php page_end();
