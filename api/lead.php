<?php
/** Receives the "سجّل اهتمامك" form and stores it for the admin panel. */
require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function reply(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, ['ok' => false, 'error' => 'طريقة الطلب غير مسموحة.']);

// Same-site check: reject submissions posted from other websites
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('http://' . $host, PHP_URL_HOST)) {
    reply(403, ['ok' => false, 'error' => 'طلب غير مسموح.']);
}

// Spam trap filled → pretend success, store nothing
if (!empty($_POST['website'])) reply(200, ['ok' => true]);

if (too_many('lead', 5, 600)) {
    reply(429, ['ok' => false, 'error' => 'أرسلت عدة طلبات خلال وقت قصير. حاول مرة أخرى بعد قليل.']);
}

$clean = fn($v, int $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$v)), 0, $max);
$name    = $clean($_POST['name'] ?? '', 120);
$phone   = preg_replace('/[^\d+]/', '', (string)($_POST['phone'] ?? ''));
$model   = $clean($_POST['model'] ?? '', 20);
$unit    = preg_replace('/\D/', '', (string)($_POST['unit'] ?? ''));
$message = mb_substr(trim(str_replace("\r\n", "\n", (string)($_POST['message'] ?? ''))), 0, 2000);

if (mb_strlen($name) < 2) reply(422, ['ok' => false, 'error' => 'يرجى كتابة الاسم.']);
if (!preg_match('/^(05|\+9665|9665)\d{8}$/', $phone)) reply(422, ['ok' => false, 'error' => 'يرجى إدخال رقم جوال صحيح مثل 05xxxxxxxx.']);

record_attempt('lead');
$now = now();
db()->prepare('INSERT INTO leads(name, phone, model, unit, message, status, ip, created_at, updated_at) VALUES(?,?,?,?,?,?,?,?,?)')
    ->execute([$name, $phone, $model, $unit, $message, 'new', client_ip(), $now, $now]);

// Optional email notification (only if an address is set and the server can send mail)
$S = content('settings');
$to = trim($S['email'] ?? '');
if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL) && function_exists('mail')) {
    $subject = '=?UTF-8?B?' . base64_encode('طلب اهتمام جديد - ' . ($S['site_name'] ?? '')) . '?=';
    $body = "طلب جديد من الموقع\n\nالاسم: $name\nالجوال: $phone\n"
          . ($model !== '' ? "النموذج: $model\n" : '') . ($unit !== '' ? "رقم الشقة: $unit\n" : '')
          . ($message !== '' ? "ملاحظات: $message\n" : '') . "\nالوقت: $now\n";
    @mail($to, $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\n");
}

reply(200, ['ok' => true]);
