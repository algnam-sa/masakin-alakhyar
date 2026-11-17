<?php
/** Media library: lists site images/videos and uploads; JSON mode feeds the picker in the content editor. */
require __DIR__ . '/_layout.php';
$user = require_login();

function media_files(): array
{
    $out = [];
    $scan = function (string $dir, string $urlBase, bool $deletable) use (&$out) {
        if (!is_dir($dir)) return;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'], true)) continue;
            $rel = $urlBase . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
            $out[] = [
                'path' => $rel,
                'type' => in_array($ext, ['mp4', 'webm'], true) ? 'video' : 'image',
                'size' => $file->getSize(),
                'time' => $file->getMTime(),
                'deletable' => $deletable,
            ];
        }
    };
    $scan(UPLOAD_DIR, UPLOAD_URL, true);
    $scan(ROOT . '/assets/img', 'assets/img', false);
    $scan(ROOT . '/assets/video', 'assets/video', false);
    usort($out, fn($a, $b) => [$b['deletable'], $b['time']] <=> [$a['deletable'], $a['time']]);
    return $out;
}

/** Which content sections reference a file path. */
function media_usage(): array
{
    $used = [];
    foreach (db()->query('SELECT key, value FROM content') as $r) {
        $v = str_replace('\\/', '/', $r['value']); // JSON may escape slashes
        if (preg_match_all('~(?:uploads|assets)/[^"]+?\.(?:jpe?g|png|webp|gif|mp4|webm)~i', $v, $m)) {
            foreach ($m[0] as $p) $used[$p] = true;
        }
    }
    return $used;
}

if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    $type = $_GET['type'] ?? '';
    $files = array_values(array_filter(media_files(), fn($f) => $type === '' || $f['type'] === $type));
    echo json_encode(['ok' => true, 'files' => $files], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $path = (string)($_POST['path'] ?? '');
    $real = realpath(ROOT . '/' . $path);
    $uploads = realpath(UPLOAD_DIR);
    if ($real && $uploads && str_starts_with($real, $uploads . DIRECTORY_SEPARATOR) && is_file($real)) {
        if (isset(media_usage()[$path])) {
            flash('هذا الملف مستخدم في الموقع. استبدله في القسم الذي يستخدمه أولًا ثم احذفه.', 'err');
        } else {
            unlink($real);
            flash('تم حذف الملف.');
        }
    } else {
        flash('لا يمكن حذف هذا الملف.', 'err');
    }
    redirect('media.php');
}

$files = media_files();
$used = media_usage();
page_start('مكتبة الوسائط', 'media', $user);
?>
<div class="page-intro">
  <p class="lead-text">كل الصور والفيديوهات المتاحة للموقع. ارفع ملفات جديدة من هنا أو من حقول الصور داخل أقسام المحتوى.</p>
  <div class="media-field" data-media="any" data-library-upload>
    <input type="hidden" class="media-path">
    <input type="file" hidden accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm">
    <button type="button" class="btn btn-primary" data-upload><?= ico('upload') ?>رفع ملف</button>
    <small class="upload-status" aria-live="polite"></small>
  </div>
</div>

<div class="media-library">
<?php foreach ($files as $f): $isUsed = isset($used[$f['path']]); ?>
  <figure class="media-card">
    <a href="../<?= url($f['path']) ?>" target="_blank" rel="noopener" class="thumb">
      <?php if ($f['type'] === 'video'): ?><video src="../<?= url($f['path']) ?>" muted preload="metadata"></video><span class="tag">فيديو</span>
      <?php else: ?><img src="../<?= url($f['path']) ?>" alt="" loading="lazy"><?php endif; ?>
    </a>
    <figcaption>
      <code dir="ltr" title="<?= e($f['path']) ?>"><?= e(basename($f['path'])) ?></code>
      <span class="muted small"><?= number_format($f['size'] / 1024 / 1024, 2) ?> MB<?= $isUsed ? ' · مستخدم في الموقع' : '' ?><?= $f['deletable'] ? '' : ' · ملف أساسي' ?></span>
      <span class="btn-row">
        <button type="button" class="btn btn-ghost btn-sm" data-copy="<?= e($f['path']) ?>">نسخ المسار</button>
        <?php if ($f['deletable'] && !$isUsed): ?>
        <form method="post" data-confirm="حذف هذا الملف نهائيًا؟"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="path" value="<?= e($f['path']) ?>"><button class="btn btn-danger-ghost btn-sm" type="submit">حذف</button></form>
        <?php endif; ?>
      </span>
    </figcaption>
  </figure>
<?php endforeach; ?>
</div>
<?php page_end();
