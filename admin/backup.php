<?php
/** Download a full database backup, or restore one. */
require __DIR__ . '/_layout.php';
$user = require_login();

$dbPath = DATA_DIR . '/' . app_config()['db_file'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'download') {
        // Consistent copy via SQLite's own backup (VACUUM INTO), then stream it
        $tmp = DATA_DIR . '/backup-' . bin2hex(random_bytes(6)) . '.sqlite';
        db()->exec('VACUUM INTO ' . db()->quote($tmp));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="masakin-backup-' . date('Y-m-d-His') . '.sqlite"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    if ($action === 'restore') {
        $f = $_FILES['backup'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) { flash('لم يصل ملف النسخة الاحتياطية.', 'err'); redirect('backup.php'); }
        // Validate: must be a SQLite file containing the site tables
        $head = (string)file_get_contents($f['tmp_name'], false, null, 0, 16);
        $ok = str_starts_with($head, 'SQLite format 3');
        if ($ok) {
            try {
                $test = new PDO('sqlite:' . $f['tmp_name']);
                $tables = $test->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
                $ok = !array_diff(['content', 'units', 'leads', 'users'], $tables)
                    && (int)$test->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
                $test = null;
            } catch (Throwable $e) { $ok = false; }
        }
        if (!$ok) { flash('الملف ليس نسخة احتياطية صالحة لهذا الموقع.', 'err'); redirect('backup.php'); }
        // Keep the current database as a safety copy, then swap
        db()->exec('VACUUM INTO ' . db()->quote(DATA_DIR . '/before-restore-' . date('Ymd-His') . '.sqlite'));
        foreach (['-wal', '-shm'] as $sfx) @unlink($dbPath . $sfx);
        if (!move_uploaded_file($f['tmp_name'], $dbPath)) { flash('تعذّر استبدال قاعدة البيانات.', 'err'); redirect('backup.php'); }
        logout();
        start_session();
        flash('تمت استعادة النسخة الاحتياطية. سجّل الدخول بحساب من تلك النسخة.');
        redirect('login.php');
    }
    redirect('backup.php');
}

page_start('النسخ الاحتياطي', 'backup', $user);
?>
<div class="grid-2">
  <section class="card">
    <div class="card-head"><h2>تنزيل نسخة احتياطية</h2></div>
    <p>ملف واحد يحتوي كل محتوى الموقع وبيانات الشقق وطلبات العملاء والمستخدمين. ننصح بتنزيل نسخة كل أسبوع.</p>
    <p class="muted small">الصور والفيديوهات المرفوعة غير مضمّنة. انسخ مجلد <code dir="ltr">uploads</code> من الاستضافة للاحتفاظ بها.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="download">
      <button class="btn btn-primary" type="submit"><?= ico('download') ?>تنزيل النسخة الآن</button>
    </form>
  </section>
  <section class="card">
    <div class="card-head"><h2>استعادة نسخة</h2></div>
    <p>ارفع ملف نسخة احتياطية نزّلته سابقًا من هذه الصفحة. ستُستبدل كل البيانات الحالية بمحتوى النسخة.</p>
    <p class="muted small">قبل الاستبدال تُحفظ نسخة من البيانات الحالية على الخادم تلقائيًا.</p>
    <form method="post" enctype="multipart/form-data" class="form" data-confirm="ستُستبدل كل البيانات الحالية بمحتوى النسخة. متابعة؟">
      <?= csrf_field() ?><input type="hidden" name="action" value="restore">
      <label class="fld"><span>ملف النسخة (.sqlite)</span><input type="file" name="backup" accept=".sqlite" required></label>
      <button class="btn btn-light" type="submit"><?= ico('upload') ?>استعادة</button>
    </form>
  </section>
</div>
<?php page_end();
