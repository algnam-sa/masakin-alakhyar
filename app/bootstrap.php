<?php
/**
 * Core bootstrap: configuration, database, sessions, security helpers.
 * Included by the public site, the API and every admin page.
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', __DIR__);
define('DATA_DIR', ROOT . '/data');
define('UPLOAD_DIR', ROOT . '/uploads');
define('UPLOAD_URL', 'uploads');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Riyadh');

/* ---------- Local config (created on first run, holds a random DB file name) ---------- */
function app_config(): array
{
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $file = APP . '/config.local.php';
    if (!is_file($file)) {
        if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
        $cfg = [
            // Random name so the database cannot be guessed even if a server ignores .htaccess
            'db_file' => 'site-' . bin2hex(random_bytes(8)) . '.sqlite',
        ];
        file_put_contents($file, "<?php\nreturn " . var_export($cfg, true) . ";\n", LOCK_EX);
        @chmod($file, 0640);
    }
    $cfg = require $file;
    return $cfg;
}

/* ---------- Database ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $cfg = app_config();
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
    $pdo = new PDO('sqlite:' . DATA_DIR . '/' . $cfg['db_file']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000;');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS units (
            no INTEGER PRIMARY KEY,
            floor INTEGER NOT NULL,
            area REAL NOT NULL DEFAULT 0,
            model TEXT NOT NULL DEFAULT '',
            laundry INTEGER NOT NULL DEFAULT 0,
            terrace INTEGER NOT NULL DEFAULT 0,
            roof REAL,
            status TEXT NOT NULL DEFAULT 'available',
            price REAL,
            notes TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            model TEXT NOT NULL DEFAULT '',
            unit TEXT NOT NULL DEFAULT '',
            message TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'new',
            notes TEXT NOT NULL DEFAULT '',
            ip TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE COLLATE NOCASE,
            name TEXT NOT NULL DEFAULT '',
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL,
            last_login TEXT
        );
        CREATE TABLE IF NOT EXISTS attempts (
            kind TEXT NOT NULL,
            ip TEXT NOT NULL,
            at INTEGER NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_attempts ON attempts(kind, ip, at);
        CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at);
    ");
    // Seed default content and units the first time
    $has = (int)$pdo->query('SELECT COUNT(*) FROM content')->fetchColumn();
    if ($has === 0) {
        require_once APP . '/defaults.php';
        $now = now();
        $st = $pdo->prepare('INSERT INTO content(key, value, updated_at) VALUES(?, ?, ?)');
        foreach (default_content() as $k => $v) $st->execute([$k, json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $now]);
        $su = $pdo->prepare('INSERT INTO units(no, floor, area, model, laundry, terrace, roof, status) VALUES(?,?,?,?,?,?,?,?)');
        foreach (default_units() as $u) $su->execute([$u['no'], $u['floor'], $u['area'], $u['model'], (int)$u['laundry'], (int)$u['terrace'], $u['roof'], 'available']);
    }
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/* ---------- Content ---------- */
function content(string $key): array
{
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $st = db()->prepare('SELECT value FROM content WHERE key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    if ($v === false) {
        require_once APP . '/defaults.php';
        $v = default_content()[$key] ?? [];
    } else {
        $v = json_decode($v, true) ?: [];
    }
    return $cache[$key] = $v;
}

function save_content(string $key, array $value): void
{
    $st = db()->prepare('INSERT INTO content(key, value, updated_at) VALUES(?, ?, ?)
                         ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at');
    $st->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now()]);
}

function units(): array
{
    return db()->query('SELECT * FROM units ORDER BY no')->fetchAll();
}

/* ---------- Output helpers ---------- */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape text, then allow **bold** and line breaks (used for editor paragraphs). */
function rich($v): string
{
    $s = e($v);
    $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
    // Keep Latin runs in parentheses left-to-right inside Arabic text, e.g. (SBC 201 · 301)
    $s = preg_replace('/\(([A-Za-z0-9][A-Za-z0-9 .,·\/\-]*)\)/u', '<span dir="ltr">($1)</span>', $s);
    return nl2br($s, false);
}

/** Safe URL for href/src: allows relative paths, #anchors, http(s), tel:, mailto:. */
function url($v): string
{
    $v = trim((string)$v);
    if ($v === '') return '';
    if (preg_match('~^(https?:|tel:|mailto:|#|/|uploads/|assets/)~i', $v) || !preg_match('~^[a-z][a-z0-9+.-]*:~i', $v)) {
        return e($v);
    }
    return '#';
}

function num($v, int $dec = 2): string
{
    return number_format((float)$v, $dec, '.', ',');
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

/* ---------- Rate limiting (login, lead form) ---------- */
function too_many(string $kind, int $max, int $window): bool
{
    $pdo = db();
    $pdo->prepare('DELETE FROM attempts WHERE at < ?')->execute([time() - 86400]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE kind = ? AND ip = ? AND at > ?');
    $st->execute([$kind, client_ip(), time() - $window]);
    return (int)$st->fetchColumn() >= $max;
}

function record_attempt(string $kind): void
{
    db()->prepare('INSERT INTO attempts(kind, ip, at) VALUES(?, ?, ?)')->execute([$kind, client_ip(), time()]);
}

function clear_attempts(string $kind): void
{
    db()->prepare('DELETE FROM attempts WHERE kind = ? AND ip = ?')->execute([$kind, client_ip()]);
}

/* ---------- Sessions, CSRF, auth (admin only) ---------- */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('mk_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_session();
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(419);
        exit('انتهت صلاحية الصفحة. أعد تحميلها وحاول مرة أخرى.');
    }
}

function current_user(): ?array
{
    start_session();
    if (empty($_SESSION['uid'])) return null;
    // Expire idle sessions after 8 hours
    if (!empty($_SESSION['seen']) && time() - $_SESSION['seen'] > 8 * 3600) {
        logout();
        return null;
    }
    $_SESSION['seen'] = time();
    $st = db()->prepare('SELECT id, username, name, last_login FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    return $st->fetch() ?: null;
}

function users_count(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function require_login(): array
{
    if (users_count() === 0) redirect('setup.php');
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}

function login_user(array $user): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['seen'] = time();
    db()->prepare('UPDATE users SET last_login = ? WHERE id = ?')->execute([now(), $user['id']]);
}

function logout(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function flash(?string $msg = null, string $type = 'ok')
{
    start_session();
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function admin_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
}

/* ---------- Labels ---------- */
const UNIT_STATUSES = [
    'available' => 'متاحة',
    'reserved'  => 'محجوزة',
    'sold'      => 'مباعة',
];

const LEAD_STATUSES = [
    'new'           => 'جديد',
    'contacted'     => 'تم التواصل',
    'interested'    => 'مهتم',
    'visit'         => 'موعد زيارة',
    'reserved'      => 'حجز',
    'not_interested'=> 'غير مهتم',
    'closed'        => 'مغلق',
];
