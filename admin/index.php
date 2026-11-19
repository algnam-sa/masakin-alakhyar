<?php
require __DIR__ . '/_layout.php';
$user = require_login();

$pdo = db();
$unitCounts = array_fill_keys(array_keys(UNIT_STATUSES), 0);
foreach ($pdo->query('SELECT status, COUNT(*) c FROM units GROUP BY status') as $r) $unitCounts[$r['status']] = (int)$r['c'];
$totalUnits = array_sum($unitCounts);

$leadTotal = (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$leadNew = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn();
$leadWeek = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= datetime('now', '-7 days', 'localtime')")->fetchColumn();
$latest = $pdo->query('SELECT * FROM leads ORDER BY id DESC LIMIT 6')->fetchAll();

// Units by model
$byModel = $pdo->query("SELECT model, COUNT(*) total, SUM(status='available') avail FROM units GROUP BY model ORDER BY model")->fetchAll();

page_start('الرئيسية', 'dashboard', $user);
?>
<p class="lead-text">أهلًا <?= e($user['name'] ?: $user['username']) ?>. هذه نظرة سريعة على المشروع والطلبات.</p>

<?php $canUnits = can('units.view'); $canLeads = can('leads.view'); ?>
<?php if ($canUnits || $canLeads): ?>
<section class="kpis">
  <?php if ($canUnits): ?>
  <a class="kpi" href="units.php?status=available"><span>شقق متاحة</span><b><?= $unitCounts['available'] ?></b><small>من <?= $totalUnits ?> شقة</small></a>
  <a class="kpi" href="units.php?status=reserved"><span>محجوزة</span><b><?= $unitCounts['reserved'] ?></b><small><?= $totalUnits ? round($unitCounts['reserved'] / $totalUnits * 100) : 0 ?>% من الشقق</small></a>
  <a class="kpi" href="units.php?status=sold"><span>مباعة</span><b><?= $unitCounts['sold'] ?></b><small><?= $totalUnits ? round($unitCounts['sold'] / $totalUnits * 100) : 0 ?>% من الشقق</small></a>
  <?php endif; if ($canLeads): ?>
  <a class="kpi kpi-accent" href="leads.php?status=new"><span>طلبات جديدة</span><b><?= $leadNew ?></b><small><?= $leadWeek ?> خلال آخر 7 أيام · <?= $leadTotal ?> إجمالًا</small></a>
  <?php endif; ?>
</section>
<?php endif; ?>

<div class="grid-2">
  <?php if ($canUnits): ?>
  <section class="card">
    <div class="card-head"><h2>حالة المبيعات</h2><a href="units.php" class="link">إدارة الشقق</a></div>
    <?php if ($totalUnits): ?>
    <div class="stackbar" role="img" aria-label="توزيع حالة الشقق">
      <?php foreach (UNIT_STATUSES as $k => $label): if (!$unitCounts[$k]) continue; ?>
        <span class="seg seg-<?= $k ?>" style="flex: <?= $unitCounts[$k] ?>" title="<?= e($label) ?>: <?= $unitCounts[$k] ?>"></span>
      <?php endforeach; ?>
    </div>
    <ul class="legend-list">
      <?php foreach (UNIT_STATUSES as $k => $label): ?><li><i class="dot dot-<?= $k ?>"></i><?= e($label) ?> <b><?= $unitCounts[$k] ?></b></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <table class="table compact">
      <thead><tr><th>النموذج</th><th>عدد الشقق</th><th>المتاح</th></tr></thead>
      <tbody>
      <?php foreach ($byModel as $m): ?><tr><td><b><?= e($m['model'] ?: '—') ?></b></td><td class="num"><?= (int)$m['total'] ?></td><td class="num"><?= (int)$m['avail'] ?></td></tr><?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endif; if ($canLeads): ?>

  <section class="card">
    <div class="card-head"><h2>أحدث الطلبات</h2><a href="leads.php" class="link">كل الطلبات</a></div>
    <?php if (!$latest): ?>
      <p class="empty">لا توجد طلبات بعد. ستظهر هنا طلبات نموذج "سجّل اهتمامك" فور وصولها.</p>
    <?php else: ?>
    <ul class="lead-mini">
      <?php foreach ($latest as $l): ?>
      <li>
        <a href="leads.php?id=<?= (int)$l['id'] ?>">
          <b><?= e($l['name']) ?></b>
          <span dir="ltr"><?= e($l['phone']) ?></span>
          <span class="pill pill-<?= e($l['status']) ?>"><?= e(LEAD_STATUSES[$l['status']] ?? $l['status']) ?></span>
          <time><?= e(substr($l['created_at'], 0, 16)) ?></time>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>

<?php $nav = admin_nav(); if ($nav): ?>
<section class="card">
  <div class="card-head"><h2>تعديل محتوى الموقع</h2></div>
  <div class="quick-links">
    <?php foreach ($nav as $k => $t): ?><a href="content.php?s=<?= e($k) ?>"><?= e($t) ?></a><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php if (!$canUnits && !$canLeads && !$nav && !can('media') && !can('backup')): ?>
<div class="card"><p class="empty">لم تُضف لحسابك أي صلاحيات بعد. اطلب من مدير اللوحة تحديد صلاحياتك.</p></div>
<?php endif; ?>
<?php page_end();
