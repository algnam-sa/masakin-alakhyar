<?php
/** AJAX upload for images and videos. Returns {"ok":true,"path":"uploads/..."}. */
require dirname(__DIR__) . '/app/bootstrap.php';
admin_headers();
header('Content-Type: application/json; charset=utf-8');

function out(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'طريقة غير مسموحة.']);
if (!current_user()) out(401, ['ok' => false, 'error' => 'انتهت الجلسة. سجّل الدخول مرة أخرى.']);
csrf_check();
if (!can('media') && !can('content.any')) out(403, ['ok' => false, 'error' => 'ليست لديك صلاحية رفع الملفات.']);

$f = $_FILES['file'] ?? null;
if (!$f || !is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = in_array($code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
        ? 'حجم الملف أكبر من الحد المسموح في الاستضافة (' . ini_get('upload_max_filesize') . ').'
        : 'لم يصل الملف. حاول مرة أخرى.';
    out(400, ['ok' => false, 'error' => $msg]);
}

// Detect the real type from the file content, not the name
$allowed = [
    'image/jpeg' => ['jpg', 8], 'image/png' => ['png', 8], 'image/webp' => ['webp', 8], 'image/gif' => ['gif', 8],
    'video/mp4' => ['mp4', 120], 'video/webm' => ['webm', 120],
    'application/pdf' => ['pdf', 0], // 0 = the largest size the hosting accepts
];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
if (!isset($allowed[$mime])) out(415, ['ok' => false, 'error' => 'نوع الملف غير مدعوم. المسموح: JPG, PNG, WEBP, GIF, MP4, WEBM, PDF.']);
[$ext, $maxMb] = $allowed[$mime];
if ($mime === 'application/pdf' && (string)file_get_contents($f['tmp_name'], false, null, 0, 5) !== '%PDF-') {
    out(415, ['ok' => false, 'error' => 'الملف ليس PDF صالحًا.']);
}
if ($maxMb > 0 && $f['size'] > $maxMb * 1024 * 1024) out(413, ['ok' => false, 'error' => "الحد الأقصى لهذا النوع {$maxMb} ميغابايت."]);
if (str_starts_with($mime, 'image/')) {
    $info = @getimagesize($f['tmp_name']);
    if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] > 12000 || $info[1] > 12000) {
        out(415, ['ok' => false, 'error' => 'الملف ليس صورة صالحة.']);
    }
    // Reject files carrying script code (image/script polyglots)
    $raw = (string)file_get_contents($f['tmp_name']);
    if (preg_match('/<\?(php|=)|<script/i', $raw)) out(415, ['ok' => false, 'error' => 'الملف ليس صورة صالحة.']);
    unset($raw);
    // Re-encode raster images with GD when available: strips anything hidden in the file.
    // GIFs are kept as-is so animations survive (they were checked above).
    $reencode = ['image/jpeg' => ['imagecreatefromjpeg', fn($im, $p) => imagejpeg($im, $p, 88)],
                 'image/png'  => ['imagecreatefrompng',  fn($im, $p) => imagepng($im, $p, 6)],
                 'image/webp' => ['imagecreatefromwebp', fn($im, $p) => imagewebp($im, $p, 86)]];
    if (isset($reencode[$mime]) && function_exists($reencode[$mime][0])) {
        $im = @$reencode[$mime][0]($f['tmp_name']);
        if (!$im) out(415, ['ok' => false, 'error' => 'الملف ليس صورة صالحة.']);
        if ($mime !== 'image/jpeg') { imagealphablending($im, false); imagesavealpha($im, true); }
        $tmp = $f['tmp_name'] . '.re';
        if ($reencode[$mime][1]($im, $tmp)) { $f['reencoded'] = $tmp; }
        imagedestroy($im);
    }
}

$dir = UPLOAD_DIR . '/' . date('Y/m');
if (!is_dir($dir) && !mkdir($dir, 0755, true)) out(500, ['ok' => false, 'error' => 'تعذّر إنشاء مجلد الرفع. تحقق من صلاحيات المجلد uploads.']);

// Keep a readable part of the original name, plus a random suffix
$base = preg_replace('/[^a-z0-9-]+/', '-', strtolower(pathinfo((string)$f['name'], PATHINFO_FILENAME)));
$base = trim(substr($base, 0, 40), '-') ?: 'file';
$name = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
$saved = !empty($f['reencoded']) ? rename($f['reencoded'], "$dir/$name") : move_uploaded_file($f['tmp_name'], "$dir/$name");
if (!$saved) out(500, ['ok' => false, 'error' => 'تعذّر حفظ الملف على الخادم.']);
@chmod("$dir/$name", 0644);

out(200, ['ok' => true, 'path' => UPLOAD_URL . '/' . date('Y/m') . '/' . $name, 'type' => str_starts_with($mime, 'video/') ? 'video' : 'image']);
