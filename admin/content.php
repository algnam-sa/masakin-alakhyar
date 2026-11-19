<?php
require __DIR__ . '/_layout.php';
require_once APP . '/schema.php';
require_once APP . '/defaults.php';
$user = require_login();

$schema = content_schema();
$key = $_GET['s'] ?? 'settings';
if (!isset($schema[$key])) redirect('content.php?s=settings');
if (!can('content.' . $key)) {
    // Go to the first section this user may edit, or refuse
    foreach (array_keys($schema) as $k) if (can('content.' . $k)) redirect('content.php?s=' . $k);
    require_perm('content.' . $key);
}
$def = $schema[$key];

$anchors = ['hero' => '#top', 'stats' => '#top', 'about' => '#about', 'features' => '#features', 'models' => '#models',
            'units_section' => '#units', 'plans' => '#plans', 'location' => '#location', 'contact' => '#contact', 'footer' => '#contact'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'reset') {
        save_content($key, default_content()[$key] ?? []);
        flash('تمت استعادة المحتوى الأصلي لهذا القسم.');
    } else {
        $data = sanitize_fields($def['fields'], $_POST['c'] ?? []);
        // Keep any stored keys the form does not manage
        save_content($key, array_merge(content($key), $data));
        flash('تم الحفظ. التعديلات ظاهرة الآن في الموقع.');
    }
    redirect('content.php?s=' . $key);
}

$values = content($key);

/* ---------- Field renderer ---------- */
function render_field(array $f, $value, string $name): string
{
    if (isset($f['group'])) return '<h3 class="group-title">' . e($f['group']) . '</h3>';
    $id = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $label = '<span>' . e($f['label']) . '</span>';
    $help = !empty($f['help']) ? '<small class="help">' . e($f['help']) . '</small>' : '';
    $dir = !empty($f['dir']) ? ' dir="' . e($f['dir']) . '"' : '';
    $ph = !empty($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '';
    $v = is_array($value) ? '' : (string)$value;

    switch ($f['type']) {
        case 'textarea':
            return '<label class="fld">' . $label . '<textarea name="' . e($name) . '" rows="3"' . $dir . $ph . ' data-autosize>' . e($v) . '</textarea>' . $help . '</label>';
        case 'lines':
            return '<label class="fld">' . $label . '<textarea name="' . e($name) . '" rows="5"' . $dir . ' data-autosize>' . e($v) . '</textarea>' . $help . '</label>';
        case 'number':
            return '<label class="fld">' . $label . '<input name="' . e($name) . '" value="' . e($v) . '" inputmode="decimal" dir="ltr"' . $ph . '>' . $help . '</label>';
        case 'toggle':
            return '<label class="toggle"><input type="checkbox" name="' . e($name) . '" value="1"' . (!empty($value) ? ' checked' : '') . '><i></i>' . $label . '</label>' . $help;
        case 'select':
            $o = '';
            foreach ($f['options'] as $k => $l) $o .= '<option value="' . e($k) . '"' . ((string)$k === $v ? ' selected' : '') . '>' . e($l) . '</option>';
            $extra = $f['name'] === 'icon' ? ' data-icon-select' : '';
            return '<label class="fld">' . $label . '<select name="' . e($name) . '"' . $extra . '>' . $o . '</select>' . $help . '</label>';
        case 'image':
        case 'video':
            $isVideo = $f['type'] === 'video';
            $preview = $v === '' ? '<span class="media-empty">لا يوجد ملف</span>'
                : ($isVideo ? '<video src="' . media_src($v) . '" muted preload="metadata"></video>' : '<img src="' . media_src($v) . '" alt="">');
            return '<div class="fld media-field" data-media="' . ($isVideo ? 'video' : 'image') . '">' . $label
                . '<div class="media-row"><div class="media-preview">' . $preview . '</div><div class="media-ctrl">'
                . '<input name="' . e($name) . '" value="' . e($v) . '" dir="ltr" class="media-path" placeholder="assets/... أو uploads/...">'
                . '<div class="btn-row"><button type="button" class="btn btn-light btn-sm" data-upload>' . ico('upload') . 'رفع ملف</button>'
                . '<button type="button" class="btn btn-light btn-sm" data-pick>اختيار من المكتبة</button>'
                . '<button type="button" class="btn btn-ghost btn-sm" data-clear>إزالة</button></div>'
                . '<input type="file" hidden accept="' . ($isVideo ? 'video/mp4,video/webm' : 'image/jpeg,image/png,image/webp,image/gif') . '">'
                . '<small class="upload-status" aria-live="polite"></small>' . $help . '</div></div></div>';
        case 'list':
            $items = is_array($value) ? $value : [];
            $html = '<div class="fld list-field" data-list>' . $label . $help . '<div class="list-items">';
            foreach (array_values($items) as $i => $item) $html .= render_list_item($f, $item, $name . '[' . $i . ']', $i + 1);
            $html .= '</div><template>' . render_list_item($f, [], $name . '[__i__]', 0) . '</template>'
                . '<button type="button" class="btn btn-light btn-sm add-item" data-add>' . ico('plus') . 'إضافة ' . e($f['item_label'] ?? 'عنصر') . '</button></div>';
            return $html;
        default:
            return '<label class="fld">' . $label . '<input name="' . e($name) . '" value="' . e($v) . '"' . $dir . $ph . '>' . $help . '</label>';
    }
}

function render_list_item(array $f, array $item, string $prefix, int $n): string
{
    $html = '<fieldset class="list-item"><legend><span class="item-title">' . e($f['item_label'] ?? 'عنصر') . ' <b class="item-no">' . ($n ?: '') . '</b></span>'
        . '<span class="item-tools">'
        . '<button type="button" class="icon-btn" data-up title="تحريك لأعلى" aria-label="تحريك لأعلى">' . ico('up') . '</button>'
        . '<button type="button" class="icon-btn" data-down title="تحريك لأسفل" aria-label="تحريك لأسفل">' . ico('down') . '</button>'
        . '<button type="button" class="icon-btn danger" data-remove title="حذف" aria-label="حذف">' . ico('trash') . '</button>'
        . '</span></legend><div class="item-fields">';
    foreach ($f['fields'] as $sub) $html .= render_field($sub, $item[$sub['name']] ?? '', $prefix . '[' . $sub['name'] . ']');
    return $html . '</div></fieldset>';
}

page_start($def['title'], 'content:' . $key, $user);
?>
<div class="page-intro">
  <p class="lead-text"><?= e($def['hint'] ?? '') ?></p>
  <a class="btn btn-light btn-sm" href="../<?= e($anchors[$key] ?? '') ?>" target="_blank" rel="noopener"><?= ico('external') ?>عرض القسم في الموقع</a>
</div>

<form method="post" class="card content-form" id="contentForm">
  <?= csrf_field() ?>
  <?php foreach ($def['fields'] as $f) echo render_field($f, isset($f['name']) ? ($values[$f['name']] ?? '') : '', isset($f['name']) ? 'c[' . $f['name'] . ']' : ''); ?>
  <div class="sticky-save">
    <span class="muted" id="dirtyNote">لا توجد تعديلات غير محفوظة</span>
    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
  </div>
</form>

<form method="post" class="danger-zone" data-confirm="سيُستبدل محتوى هذا القسم بالمحتوى الأصلي للموقع. متابعة؟">
  <?= csrf_field() ?><input type="hidden" name="action" value="reset">
  <button type="submit" class="btn btn-danger-ghost">استعادة المحتوى الأصلي لهذا القسم</button>
</form>

<!-- Media library picker -->
<div class="modal" id="mediaModal" hidden>
  <div class="modal-backdrop" data-close></div>
  <div class="modal-card wide" role="dialog" aria-modal="true" aria-labelledby="mediaTitle">
    <div class="modal-head"><h2 id="mediaTitle">اختيار ملف</h2><button type="button" class="icon-btn" data-close aria-label="إغلاق">×</button></div>
    <div class="media-grid" id="mediaGrid"><p class="muted">جارٍ التحميل…</p></div>
  </div>
</div>

<?php if ($key === 'features'): ?>
<script>window.ICON_SVGS = <?= json_encode(array_map(fn($i) => $i[1], FEATURE_ICONS), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<?php endif;
page_end();
