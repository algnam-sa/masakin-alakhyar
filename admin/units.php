<?php
require __DIR__ . '/_layout.php';
$user = require_login();

$pdo = db();
$modelKeys = array_values(array_filter(array_map(fn($m) => (string)$m['key'], content('models')['items'] ?? []), 'strlen'));
$floors = [];
foreach (content('units_section')['floors'] ?? [] as $f) $floors[(int)$f['number']] = $f['name'];

$numOrNull = function ($v) {
    $v = trim(str_replace(',', '', (string)$v));
    return ($v === '' || !is_numeric($v)) ? null : (float)$v;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $pdo->beginTransaction();
        $st = $pdo->prepare('UPDATE units SET floor=?, area=?, model=?, laundry=?, terrace=?, roof=?, status=?, price=?, notes=? WHERE no=?');
        foreach ((array)($_POST['u'] ?? []) as $no => $u) {
            if (!is_array($u)) continue;
            $status = isset(UNIT_STATUSES[$u['status'] ?? '']) ? $u['status'] : 'available';
            $st->execute([
                (int)($u['floor'] ?? 1), (float)($numOrNull($u['area'] ?? '') ?? 0), mb_substr(trim((string)($u['model'] ?? '')), 0, 20),
                !empty($u['laundry']) ? 1 : 0, !empty($u['terrace']) ? 1 : 0, $numOrNull($u['roof'] ?? ''),
                $status, $numOrNull($u['price'] ?? ''), mb_substr(trim((string)($u['notes'] ?? '')), 0, 500), (int)$no,
            ]);
        }
        $pdo->commit();
        flash('تم حفظ بيانات الشقق، وظهرت التعديلات في الموقع.');
    } elseif ($action === 'add') {
        $no = (int)($_POST['no'] ?? 0);
        $exists = $pdo->prepare('SELECT 1 FROM units WHERE no = ?');
        $exists->execute([$no]);
        if ($no < 1) {
            flash('اكتب رقم شقة صحيحًا.', 'err');
        } elseif ($exists->fetchColumn()) {
            flash("الشقة رقم $no موجودة مسبقًا.", 'err');
        } else {
            $pdo->prepare('INSERT INTO units(no, floor, area, model, status) VALUES(?,?,?,?,?)')
                ->execute([$no, (int)($_POST['floor'] ?? 1), (float)($numOrNull($_POST['area'] ?? '') ?? 0), (string)($_POST['model'] ?? ''), 'available']);
            flash("تمت إضافة الشقة رقم $no.");
        }
    } elseif ($action === 'delete') {
        $no = (int)($_POST['no'] ?? 0);
        $pdo->prepare('DELETE FROM units WHERE no = ?')->execute([$no]);
        flash("تم حذف الشقة رقم $no.");
    }
    redirect('units.php');
}

$rows = units();
$filter = $_GET['status'] ?? '';
page_start('الشقق', 'units', $user);
?>
<p class="lead-text">عدّل حالة أي شقة أو سعرها ثم اضغط "حفظ التعديلات". التغييرات تظهر في الموقع مباشرة.</p>

<div class="toolbar">
  <div class="seg-filter" role="group" aria-label="تصفية حسب الحالة">
    <button type="button" data-filter="" class="<?= $filter === '' ? 'on' : '' ?>">الكل <b><?= count($rows) ?></b></button>
    <?php foreach (UNIT_STATUSES as $k => $label): $c = count(array_filter($rows, fn($r) => $r['status'] === $k)); ?>
      <button type="button" data-filter="<?= $k ?>" class="<?= $filter === $k ? 'on' : '' ?>"><i class="dot dot-<?= $k ?>"></i><?= e($label) ?> <b><?= $c ?></b></button>
    <?php endforeach; ?>
  </div>
  <label class="search"><?= ico('search') ?><input type="search" id="unitSearch" placeholder="ابحث برقم الشقة أو الملاحظة"></label>
</div>

<form method="post" class="card table-card" id="unitsForm">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="table-wrap">
  <table class="table units-table">
    <thead>
      <tr>
        <th>الشقة</th><th>الحالة</th><th>السعر</th><th>الدور</th><th>النموذج</th><th>المساحة م²</th><th>السطح م²</th><th>غسيل</th><th>تراس</th><th>ملاحظات</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $u): $n = (int)$u['no']; ?>
      <tr data-status="<?= e($u['status']) ?>" data-search="<?= e($n . ' ' . $u['notes']) ?>">
        <td class="num"><b class="unit-no"><?= $n ?></b></td>
        <td>
          <select name="u[<?= $n ?>][status]" class="status-select st-<?= e($u['status']) ?>" aria-label="حالة الشقة <?= $n ?>">
            <?php foreach (UNIT_STATUSES as $k => $label): ?><option value="<?= $k ?>" <?= $u['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
        </td>
        <td><input name="u[<?= $n ?>][price]" value="<?= $u['price'] !== null ? e(rtrim(rtrim(number_format((float)$u['price'], 2, '.', ''), '0'), '.')) : '' ?>" inputmode="decimal" dir="ltr" class="w-price" placeholder="—" aria-label="سعر الشقة <?= $n ?>"></td>
        <td>
          <select name="u[<?= $n ?>][floor]" aria-label="دور الشقة <?= $n ?>">
            <?php $fl = $floors ?: [(int)$u['floor'] => (string)$u['floor']]; if (!isset($fl[(int)$u['floor']])) $fl[(int)$u['floor']] = 'الدور ' . $u['floor'];
            foreach ($fl as $fn => $fname): ?><option value="<?= $fn ?>" <?= (int)$u['floor'] === $fn ? 'selected' : '' ?>><?= e($fname) ?></option><?php endforeach; ?>
          </select>
        </td>
        <td>
          <select name="u[<?= $n ?>][model]" aria-label="نموذج الشقة <?= $n ?>">
            <?php $mk = $modelKeys; if ($u['model'] !== '' && !in_array($u['model'], $mk, true)) $mk[] = $u['model'];
            foreach ($mk as $k): ?><option value="<?= e($k) ?>" <?= $u['model'] === $k ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?>
          </select>
        </td>
        <td><input name="u[<?= $n ?>][area]" value="<?= e((float)$u['area']) ?>" inputmode="decimal" dir="ltr" class="w-num" aria-label="مساحة الشقة <?= $n ?>"></td>
        <td><input name="u[<?= $n ?>][roof]" value="<?= $u['roof'] !== null ? e((float)$u['roof']) : '' ?>" inputmode="decimal" dir="ltr" class="w-num" placeholder="—" aria-label="مساحة سطح الشقة <?= $n ?>"></td>
        <td class="center"><input type="checkbox" name="u[<?= $n ?>][laundry]" value="1" <?= $u['laundry'] ? 'checked' : '' ?> aria-label="غرفة غسيل للشقة <?= $n ?>"></td>
        <td class="center"><input type="checkbox" name="u[<?= $n ?>][terrace]" value="1" <?= $u['terrace'] ? 'checked' : '' ?> aria-label="تراس للشقة <?= $n ?>"></td>
        <td><input name="u[<?= $n ?>][notes]" value="<?= e($u['notes']) ?>" class="w-notes" placeholder="ملاحظة داخلية" aria-label="ملاحظة للشقة <?= $n ?>"></td>
        <td><button type="button" class="icon-btn danger" data-delete-unit="<?= $n ?>" title="حذف الشقة <?= $n ?>" aria-label="حذف الشقة <?= $n ?>"><?= ico('trash') ?></button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <div class="sticky-save">
    <span class="muted" id="dirtyNote">لا توجد تعديلات غير محفوظة</span>
    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
  </div>
</form>
<p class="muted small">الملاحظات داخلية ولا تظهر للزوار. السعر يظهر في الموقع فقط عند تفعيل "إظهار الأسعار" من الإعدادات العامة.</p>

<form method="post" id="deleteUnitForm" hidden><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="no"></form>

<section class="card">
  <div class="card-head"><h2>إضافة شقة</h2></div>
  <form method="post" class="form-inline">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <label class="fld"><span>رقم الشقة</span><input name="no" type="number" min="1" required dir="ltr"></label>
    <label class="fld"><span>الدور</span><select name="floor"><?php foreach ($floors as $fn => $fname): ?><option value="<?= $fn ?>"><?= e($fname) ?></option><?php endforeach; ?></select></label>
    <label class="fld"><span>النموذج</span><select name="model"><?php foreach ($modelKeys as $k): ?><option value="<?= e($k) ?>"><?= e($k) ?></option><?php endforeach; ?></select></label>
    <label class="fld"><span>المساحة م²</span><input name="area" inputmode="decimal" dir="ltr"></label>
    <button type="submit" class="btn btn-light"><?= ico('plus') ?>إضافة</button>
  </form>
</section>
<?php page_end();
