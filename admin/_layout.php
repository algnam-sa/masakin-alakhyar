<?php
/** Shared admin layout: page shell, sidebar, flash messages. */
require_once dirname(__DIR__) . '/app/bootstrap.php';
admin_headers();

function admin_nav(): array
{
    require_once APP . '/schema.php';
    $sections = [];
    foreach (content_schema() as $k => $s) $sections[$k] = $s['title'];
    return $sections;
}

function new_leads_count(): int
{
    return (int)db()->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn();
}

function page_start(string $title, string $active = '', ?array $user = null): void
{
    $flash = flash();
    $newLeads = $user ? new_leads_count() : 0;
    $site = content('settings');
    ?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($title) ?> · لوحة التحكم</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Changa:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css?v=<?= @filemtime(__DIR__ . '/assets/admin.css') ?>">
  <?php if (!empty($site['favicon'])): ?><link rel="icon" href="../<?= url($site['favicon']) ?>"><?php endif; ?>
</head>
<body class="<?= $user ? 'has-sidebar' : 'auth-page' ?>">
<?php if ($user): ?>
<aside class="sidebar" id="sidebar">
  <a class="sb-brand" href="index.php">
    <?php if (!empty($site['logo_light'])): ?><img src="../<?= url($site['logo_light']) ?>" alt=""><?php endif; ?>
    <span>لوحة التحكم<small><?= e($site['site_name'] ?? '') ?></small></span>
  </a>
  <nav class="sb-nav">
    <a href="index.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>"><?= ico('grid') ?>الرئيسية</a>
    <a href="units.php" class="<?= $active === 'units' ? 'active' : '' ?>"><?= ico('building') ?>الشقق</a>
    <a href="leads.php" class="<?= $active === 'leads' ? 'active' : '' ?>"><?= ico('inbox') ?>طلبات العملاء<?php if ($newLeads): ?><b class="badge"><?= $newLeads ?></b><?php endif; ?></a>
    <span class="sb-label">محتوى الموقع</span>
    <?php foreach (admin_nav() as $k => $t): ?>
      <a href="content.php?s=<?= e($k) ?>" class="sub <?= $active === 'content:' . $k ? 'active' : '' ?>"><?= e($t) ?></a>
    <?php endforeach; ?>
    <span class="sb-label">النظام</span>
    <a href="media.php" class="<?= $active === 'media' ? 'active' : '' ?>"><?= ico('image') ?>مكتبة الوسائط</a>
    <a href="users.php" class="<?= $active === 'users' ? 'active' : '' ?>"><?= ico('users') ?>المستخدمون</a>
    <a href="backup.php" class="<?= $active === 'backup' ? 'active' : '' ?>"><?= ico('save') ?>النسخ الاحتياطي</a>
  </nav>
  <div class="sb-foot">
    <a href="../" target="_blank" rel="noopener" class="btn btn-light btn-block"><?= ico('external') ?>عرض الموقع</a>
    <div class="sb-user"><span><?= e($user['name'] ?: $user['username']) ?></span>
      <form action="logout.php" method="post"><?= csrf_field() ?><button type="submit" class="link">تسجيل الخروج</button></form>
    </div>
  </div>
</aside>
<div class="sb-backdrop" data-sb-close></div>
<main class="main">
  <header class="topbar">
    <button class="sb-toggle" type="button" aria-label="القائمة" data-sb-toggle><span></span><span></span><span></span></button>
    <h1><?= e($title) ?></h1>
  </header>
<?php else: ?>
<main class="auth-main">
<?php endif; ?>
  <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></div><?php endif; ?>
<?php
}

function page_end(): void
{
    ?>
</main>
<script src="assets/admin.js?v=<?= @filemtime(__DIR__ . '/assets/admin.js') ?>"></script>
</body>
</html>
<?php
}

/** Small line icons for the admin UI. */
function ico(string $name): string
{
    $p = [
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'building' => '<path d="M4 21V5a2 2 0 012-2h8a2 2 0 012 2v16M16 9h2a2 2 0 012 2v10M2 21h20M8 7h4M8 11h4M8 15h4"/>',
        'inbox'    => '<path d="M3 13l2.5-8h13L21 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M3 13h5l1.5 3h5L16 13h5"/>',
        'image'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-8 9"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0113 0M16 4.5a3.5 3.5 0 010 7M18 14a5.5 5.5 0 013.5 6"/>',
        'save'     => '<path d="M5 3h11l3 3v15H5z"/><path d="M8 3v5h7V3M8 21v-7h8v7"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'up'       => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'down'     => '<path d="M12 5v14M6 13l6 6 6-6"/>',
        'upload'   => '<path d="M12 16V4M7 9l5-5 5 5M4 20h16"/>',
        'download' => '<path d="M12 4v12M7 11l5 5 5-5M4 20h16"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2"/>',
        'wa'       => '<path d="M4 20l1.3-4A8 8 0 1112 20a8 8 0 01-4-1z"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

/** Preview URL for a stored media path (paths are relative to the site root). */
function media_src(string $path): string
{
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return e($path);
    return '../' . url($path);
}
