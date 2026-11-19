<?php
require __DIR__ . '/_layout.php';
$user = require_login();
require_perm('leads.view');
$pdo = db();
$canEdit = can('leads.edit');
$canDelete = can('leads.delete');
$canExport = can('leads.export');

/* ---------- Actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['update', 'status'], true)) require_perm('leads.edit');
    if ($action === 'delete') require_perm('leads.delete');
    if ($action === 'update' && $id) {
        $status = isset(LEAD_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : 'new';
        $pdo->prepare('UPDATE leads SET status = ?, notes = ?, updated_at = ? WHERE id = ?')
            ->execute([$status, mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 5000), now(), $id]);
        flash('تم حفظ الطلب.');
        redirect('leads.php?id=' . $id);
    }
    if ($action === 'status' && $id) { // quick change from the list
        $status = isset(LEAD_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : 'new';
        $pdo->prepare('UPDATE leads SET status = ?, updated_at = ? WHERE id = ?')->execute([$status, now(), $id]);
        flash('تم تحديث حالة الطلب.');
        redirect('leads.php?' . http_build_query(array_filter(['status' => $_POST['back_status'] ?? '', 'q' => $_POST['back_q'] ?? ''])));
    }
    if ($action === 'delete' && $id) {
        $pdo->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
        flash('تم حذف الطلب.');
        redirect('leads.php');
    }
    redirect('leads.php');
}

/* ---------- Filters ---------- */
$status = isset(LEAD_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$q = trim((string)($_GET['q'] ?? ''));
$where = [];
$args = [];
if ($status !== '') { $where[] = 'status = ?'; $args[] = $status; }
if ($q !== '') {
    $where[] = '(name LIKE ? OR phone LIKE ? OR message LIKE ? OR notes LIKE ? OR unit = ?)';
    $like = '%' . $q . '%';
    array_push($args, $like, $like, $like, $like, $q);
}
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

/* ---------- Export (Excel-compatible CSV) ---------- */
if (isset($_GET['export'])) {
    require_perm('leads.export');
    $st = $pdo->prepare('SELECT * FROM leads' . $sqlWhere . ' ORDER BY id DESC');
    $st->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Arabic correctly
    // Neutralise spreadsheet formulas in user-entered text
    $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : (string)$v;
    fputcsv($out, ['رقم الطلب', 'التاريخ', 'الاسم', 'الجوال', 'النموذج', 'رقم الشقة', 'الرسالة', 'الحالة', 'ملاحظات المتابعة']);
    foreach ($st as $r) {
        fputcsv($out, array_map($safe, [$r['id'], $r['created_at'], $r['name'], $r['phone'], $r['model'], $r['unit'], $r['message'], LEAD_STATUSES[$r['status']] ?? $r['status'], $r['notes']]));
    }
    exit;
}

$waLink = function (string $phone, string $name): string {
    $p = preg_replace('/\D/', '', $phone);
    if (str_starts_with($p, '05')) $p = '966' . substr($p, 1);
    return 'https://wa.me/' . $p . '?text=' . rawurlencode('السلام عليكم ' . $name . '، ');
};

/* ---------- Detail view ---------- */
if (isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM leads WHERE id = ?');
    $st->execute([(int)$_GET['id']]);
    $lead = $st->fetch();
    if (!$lead) { flash('الطلب غير موجود.', 'err'); redirect('leads.php'); }
    page_start('طلب رقم ' . $lead['id'], 'leads', $user);
    ?>
    <p><a href="leads.php" class="link">← كل الطلبات</a></p>
    <div class="grid-2">
      <section class="card">
        <div class="card-head"><h2><?= e($lead['name']) ?></h2><span class="pill pill-<?= e($lead['status']) ?>"><?= e(LEAD_STATUSES[$lead['status']] ?? '') ?></span></div>
        <dl class="detail-list">
          <div><dt>الجوال</dt><dd dir="ltr"><?= e($lead['phone']) ?></dd></div>
          <div><dt>النموذج المفضل</dt><dd><?= e($lead['model'] ?: '—') ?></dd></div>
          <div><dt>رقم الشقة</dt><dd><?= e($lead['unit'] ?: '—') ?></dd></div>
          <div><dt>تاريخ الطلب</dt><dd><?= e($lead['created_at']) ?></dd></div>
          <div><dt>آخر تحديث</dt><dd><?= e($lead['updated_at']) ?></dd></div>
        </dl>
        <?php if ($lead['message'] !== ''): ?><h3 class="h3">رسالة العميل</h3><p class="message-box"><?= nl2br(e($lead['message'])) ?></p><?php endif; ?>
        <div class="btn-row">
          <a class="btn btn-light" href="tel:<?= e($lead['phone']) ?>"><?= ico('phone') ?>اتصال</a>
          <a class="btn btn-wa" href="<?= e($waLink($lead['phone'], $lead['name'])) ?>" target="_blank" rel="noopener"><?= ico('wa') ?>واتساب</a>
        </div>
      </section>
      <section class="card">
        <div class="card-head"><h2>المتابعة</h2></div>
        <?php if (!$canEdit): ?><p class="muted small">صلاحيتك عرض الطلبات فقط، بدون تعديل المتابعة.</p><?php endif; ?>
        <form method="post" class="form">
          <fieldset class="plain form"<?= $canEdit ? '' : ' disabled' ?>>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
          <label class="fld"><span>حالة الطلب</span>
            <select name="status"><?php foreach (LEAD_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $lead['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
          </label>
          <label class="fld"><span>ملاحظات المتابعة (داخلية)</span><textarea name="notes" rows="7"><?= e($lead['notes']) ?></textarea></label>
          <?php if ($canEdit): ?><button class="btn btn-primary" type="submit">حفظ</button><?php endif; ?>
          </fieldset>
        </form>
        <?php if ($canDelete): ?>
        <form method="post" class="danger-zone" data-confirm="حذف هذا الطلب نهائيًا؟">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
          <button class="btn btn-danger-ghost" type="submit"><?= ico('trash') ?>حذف الطلب</button>
        </form>
        <?php endif; ?>
      </section>
    </div>
    <?php
    page_end();
    exit;
}

/* ---------- List ---------- */
$perPage = 30;
$page = max(1, (int)($_GET['page'] ?? 1));
$cnt = $pdo->prepare('SELECT COUNT(*) FROM leads' . $sqlWhere);
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$st = $pdo->prepare('SELECT * FROM leads' . $sqlWhere . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
$st->execute($args);
$rows = $st->fetchAll();

$counts = ['' => (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn()];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM leads GROUP BY status') as $r) $counts[$r['status']] = (int)$r['c'];
$qs = fn(array $extra) => '?' . http_build_query(array_filter(array_merge(['status' => $status, 'q' => $q], $extra), fn($v) => $v !== '' && $v !== null));

page_start('طلبات العملاء', 'leads', $user);
?>
<div class="toolbar">
  <div class="seg-filter scroll-x">
    <a href="leads.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>" class="<?= $status === '' ? 'on' : '' ?>">الكل <b><?= $counts[''] ?></b></a>
    <?php foreach (LEAD_STATUSES as $k => $l): ?><a href="<?= e($qs(['status' => $k, 'page' => null])) ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= e($l) ?> <b><?= $counts[$k] ?? 0 ?></b></a><?php endforeach; ?>
  </div>
  <form class="search" method="get">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <?= ico('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="ابحث بالاسم أو الجوال">
  </form>
  <?php if ($canExport): ?><a class="btn btn-light" href="<?= e($qs(['export' => 1])) ?>"><?= ico('download') ?>تصدير Excel</a><?php endif; ?>
</div>

<?php if (!$rows): ?>
  <div class="card"><p class="empty"><?= $counts[''] ? 'لا توجد طلبات تطابق البحث.' : 'لا توجد طلبات بعد. ستظهر هنا طلبات نموذج "سجّل اهتمامك" فور وصولها من الموقع.' ?></p></div>
<?php else: ?>
<div class="card table-card">
  <div class="table-wrap">
  <table class="table leads-table">
    <thead><tr><th>#</th><th>العميل</th><th>الجوال</th><th>النموذج / الشقة</th><th>الرسالة</th><th>التاريخ</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['status'] === 'new' ? 'is-new' : '' ?>">
        <td class="num muted"><?= (int)$r['id'] ?></td>
        <td><a href="leads.php?id=<?= (int)$r['id'] ?>" class="strong"><?= e($r['name']) ?></a></td>
        <td dir="ltr" class="num"><?= e($r['phone']) ?></td>
        <td><?= e(trim(($r['model'] ? 'نموذج ' . $r['model'] : '') . ($r['unit'] ? ' · شقة ' . $r['unit'] : ''), ' ·') ?: '—') ?></td>
        <td class="clip" title="<?= e($r['message']) ?>"><?= e(mb_strimwidth($r['message'], 0, 60, '…')) ?: '<span class="muted">—</span>' ?></td>
        <td class="nowrap muted"><?= e(substr($r['created_at'], 0, 16)) ?></td>
        <td>
          <form method="post" class="inline-status">
            <?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="back_status" value="<?= e($status) ?>"><input type="hidden" name="back_q" value="<?= e($q) ?>">
            <select name="status" class="pill-select pill-<?= e($r['status']) ?>" data-autosubmit<?= $canEdit ? '' : ' disabled' ?> aria-label="حالة الطلب <?= (int)$r['id'] ?>">
              <?php foreach (LEAD_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </form>
        </td>
        <td class="nowrap">
          <a class="icon-btn" href="<?= e($waLink($r['phone'], $r['name'])) ?>" target="_blank" rel="noopener" title="واتساب" aria-label="مراسلة <?= e($r['name']) ?> على واتساب"><?= ico('wa') ?></a>
          <a class="icon-btn" href="leads.php?id=<?= (int)$r['id'] ?>" title="التفاصيل" aria-label="تفاصيل طلب <?= e($r['name']) ?>">←</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php if ($pages > 1): ?>
<nav class="pager" aria-label="الصفحات">
  <?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= e($qs(['page' => $i])) ?>" class="<?= $i === $page ? 'on' : '' ?>"><?= $i ?></a><?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif;
page_end();
