<?php
/** Public site — every text, image, list and unit comes from the database (edited in /admin). */
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/icons.php';

$S  = content('settings');
$H  = content('hero');
$ST = content('stats');
$AB = content('about');
$FE = content('features');
$MO = content('models');
$US = content('units_section');
$PL = content('plans');
$LO = content('location');
$CO = content('contact');
$FO = content('footer');
$UNITS = units();

/* ---------- Data for the interactive parts (models, units) ---------- */
$floorNames = [];
foreach ($US['floors'] ?? [] as $f) $floorNames[(int)$f['number']] = $f['name'];

$models = [];
foreach ($MO['items'] ?? [] as $m) {
    $key = (string)$m['key'];
    if ($key === '') continue;
    $models[$key] = [
        'name'  => $m['name'],
        'area'  => (float)$m['area'],
        'roof'  => $m['roof'] !== '' ? (float)$m['roof'] : null,
        'image' => $m['image'],
        'rooms' => array_values(array_filter(explode("\n", (string)$m['rooms']), 'strlen')),
        'units' => [],
    ];
}
$jsUnits = [];
foreach ($UNITS as $u) {
    if (isset($models[$u['model']])) $models[$u['model']]['units'][] = (int)$u['no'];
    $jsUnits[] = [
        'no' => (int)$u['no'], 'floor' => (int)$u['floor'],
        'floorName' => $floorNames[(int)$u['floor']] ?? ('الدور ' . $u['floor']),
        'area' => (float)$u['area'], 'model' => $u['model'],
        'laundry' => (bool)$u['laundry'], 'terrace' => (bool)$u['terrace'],
        'roof' => $u['roof'] !== null ? (float)$u['roof'] : null,
        'status' => isset(UNIT_STATUSES[$u['status']]) ? $u['status'] : 'available',
        'price' => !empty($S['show_prices']) && $u['price'] !== null ? (float)$u['price'] : null,
    ];
}
$siteConfig = [
    'phoneDisplay' => $S['phone_display'] ?? '',
    'phoneIntl'    => preg_replace('/\D/', '', $S['phone_intl'] ?? ''),
    'waMessage'    => $S['whatsapp_message'] ?? '',
    'mapLat'       => (float)($LO['map_lat'] ?? 0),
    'mapLng'       => (float)($LO['map_lng'] ?? 0),
    'currency'     => $S['currency'] ?? 'ريال',
    'inquiryLabel' => $US['inquiry_label'] ?? 'استفسر عن الشقة',
    'projectName'  => $S['site_name'] ?? '',
    'success'      => $CO['success'] ?? '',
];
$json = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$marketer = function (string $class, bool $lazy = false) use ($S) {
    if (empty($S['marketer_logo'])) return '';
    return '<img src="' . url($S['marketer_logo']) . '" alt="' . e($S['marketer_name'] ?? '') . '"' . ($lazy ? ' loading="lazy"' : '') . '>'
         . '<span class="marketer-label">' . e($S['marketer_label'] ?? '') . '</span>';
};
$sectionHead = function (array $c, string $descKey = '', bool $fitRule = false) {
    $h = '<h2 class="section-title">' . e($c['title'] ?? '') . '</h2>';
    // $fitRule: the gold line spans exactly the width of the subtitle
    if ($fitRule && !empty($c['sub'])) {
        $h .= '<span class="sub-fit"><span class="section-sub">' . e($c['sub']) . '</span><span class="title-rule" aria-hidden="true"></span></span>';
    } else {
        if (!empty($c['sub'])) $h .= '<span class="section-sub">' . e($c['sub']) . '</span>';
        $h .= '<span class="title-rule" aria-hidden="true"></span>';
    }
    if ($descKey && !empty($c[$descKey])) $h .= '<p>' . rich($c[$descKey]) . '</p>';
    return $h;
};
$video = function (string $class, array $c, string $label) {
    if (empty($c['video_mp4']) && empty($c['video_webm'])) {
        return '<img class="' . e($class) . '" src="' . url($c['poster'] ?? '') . '" alt="' . e($label) . '">';
    }
    $h = '<video class="' . e($class) . '" poster="' . url($c['poster'] ?? '') . '" autoplay muted loop playsinline preload="auto" aria-label="' . e($label) . '">';
    if (!empty($c['video_mp4'])) $h .= '<source src="' . url($c['video_mp4']) . '" type="video/mp4">';
    if (!empty($c['video_webm'])) $h .= '<source src="' . url($c['video_webm']) . '" type="video/webm">';
    return $h . '</video>';
};
$siteName = $S['site_name'] ?? '';
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($S['meta_title'] ?? $siteName) ?></title>
  <meta name="description" content="<?= e($S['meta_description'] ?? '') ?>">
  <meta name="theme-color" content="#1f4a42">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($S['meta_title'] ?? $siteName) ?>">
  <meta property="og:description" content="<?= e($S['meta_description'] ?? '') ?>">
  <?php if (!empty($S['og_image'])): ?><meta property="og:image" content="<?= url($S['og_image']) ?>"><?php endif; ?>
  <?php if (!empty($S['favicon'])): ?><link rel="icon" href="<?= url($S['favicon']) ?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Changa:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php if (!empty($H['poster'])): ?><link rel="preload" as="image" href="<?= url($H['poster']) ?>"><?php endif; ?>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<!-- ============ Header ============ -->
<header class="site-header" id="top">
  <div class="container header-inner">
    <a href="#top" class="brand" aria-label="<?= e(($S['logo_alt'] ?? $siteName) . ' - الرئيسية') ?>">
      <img class="logo-light" src="<?= url($S['logo_light'] ?? '') ?>" alt="<?= e($S['logo_alt'] ?? $siteName) ?>">
      <img class="logo-dark" src="<?= url($S['logo_dark'] ?? '') ?>" alt="" aria-hidden="true">
    </a>
    <nav class="main-nav" id="mainNav" aria-label="القائمة الرئيسية">
      <?php foreach ($S['nav'] ?? [] as $n): ?><a href="<?= url($n['href']) ?>"><?= e($n['label']) ?></a>
      <?php endforeach; ?>
      <?php if (!empty($S['nav_cta_label'])): ?><a href="<?= url($S['nav_cta_href'] ?? '#contact') ?>" class="btn btn-sm btn-primary nav-cta"><?= e($S['nav_cta_label']) ?></a><?php endif; ?>
    </nav>
    <button class="nav-toggle" id="navToggle" aria-label="فتح القائمة" aria-expanded="false" aria-controls="mainNav"><span></span><span></span><span></span></button>
  </div>
</header>

<main>
<!-- ============ Hero ============ -->
<section class="hero">
  <?= $video('hero-bg', $H, 'جولة حول ' . $siteName) ?>
  <div class="hero-overlay"></div>
  <div class="container hero-content">
    <?php if (!empty($H['eyebrow'])): ?><span class="eyebrow"><?= e($H['eyebrow']) ?></span><?php endif; ?>
    <div class="hero-headline">
      <h1><?= e($H['title'] ?? '') ?></h1>
      <?php if (!empty($H['lead'])): ?><p class="hero-lead"><?= e($H['lead']) ?></p><?php endif; ?>
    </div>
    <div class="hero-actions">
      <?php if (!empty($H['btn1_label'])): ?><a href="<?= url($H['btn1_href'] ?? '#') ?>" class="btn btn-primary"><?= e($H['btn1_label']) ?></a><?php endif; ?>
      <?php if (!empty($H['btn2_label'])): ?><a href="<?= url($H['btn2_href'] ?? '#') ?>" class="btn btn-ghost"><?= e($H['btn2_label']) ?></a><?php endif; ?>
    </div>
  </div>
  <?php if (!empty($H['show_marketer']) && !empty($S['marketer_logo'])): ?>
  <aside class="marketer" aria-label="<?= e($S['marketer_label'] ?? '') ?>"><?= $marketer('marketer') ?></aside>
  <?php endif; ?>
  <a href="#about" class="scroll-cue" aria-label="انتقل للأسفل"><span></span></a>
</section>

<!-- ============ Stats ============ -->
<?php if (!empty($ST['items'])): ?>
<section class="stats-bar" aria-label="أرقام المشروع">
  <div class="container stats-grid" style="--stats: <?= count($ST['items']) ?>">
    <?php foreach ($ST['items'] as $s): $dec = (int)($s['decimals'] ?: 0); ?>
    <div class="stat"><span class="stat-num"><span data-count="<?= e((float)$s['number']) ?>" data-decimals="<?= $dec ?>"><?= num($s['number'], $dec) ?></span><?php if (!empty($s['suffix'])): ?><small><?= e($s['suffix']) ?></small><?php endif; ?></span><span class="stat-label"><?= e($s['label']) ?></span></div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============ About ============ -->
<section class="section" id="about">
  <div class="container about-grid">
    <div class="about-text reveal">
      <?= $sectionHead($AB) ?>
      <?php foreach ($AB['paragraphs'] ?? [] as $p): ?><p><?= rich($p['text']) ?></p>
      <?php endforeach; ?>
      <?php if (!empty($AB['facts'])): ?>
      <dl class="facts">
        <?php foreach ($AB['facts'] as $f): ?><div><dt><?= e($f['label']) ?></dt><dd><?= e($f['value']) ?></dd></div>
        <?php endforeach; ?>
      </dl>
      <?php endif; ?>
    </div>
    <figure class="about-media reveal">
      <?= $video('about-video', $AB, $AB['caption'] ?? '') ?>
      <?php if (!empty($AB['caption'])): ?><figcaption><?= e($AB['caption']) ?></figcaption><?php endif; ?>
    </figure>
  </div>
</section>

<!-- ============ Features ============ -->
<section class="section section-alt" id="features">
  <div class="container">
    <div class="section-head reveal"><?= $sectionHead($FE) ?></div>
    <div class="features-grid">
      <?php foreach ($FE['items'] ?? [] as $f): ?>
      <article class="feature reveal">
        <div class="feature-icon"><?= icon_svg($f['icon'] ?? 'star') ?></div>
        <h3><?= e($f['title']) ?></h3>
        <p><?= rich($f['text']) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ Models ============ -->
<section class="section" id="models">
  <div class="container">
    <div class="head-row">
      <div class="section-head reveal"><?= $sectionHead($MO, 'desc') ?></div>
      <div class="tabs reveal" role="tablist" aria-label="<?= e($MO['title'] ?? '') ?>" id="modelTabs"></div>
    </div>
    <div class="model-panel reveal" id="modelPanel" role="tabpanel" aria-live="polite"></div>
  </div>
</section>

<!-- ============ Units ============ -->
<section class="section section-alt" id="units">
  <div class="container">
    <div class="section-head reveal"><?= $sectionHead($US, 'desc') ?></div>

    <div class="units-toolbar reveal">
      <div class="filter-group" role="group" aria-label="تصفية حسب الدور" id="floorFilter">
        <button class="chip is-active" data-floor="all">كل الأدوار</button>
        <?php foreach ($US['floors'] ?? [] as $f): ?><button class="chip" data-floor="<?= (int)$f['number'] ?>"><?= e($f['short'] ?: $f['name']) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="filter-group" role="group" aria-label="تصفية حسب النموذج" id="modelFilter">
        <button class="chip is-active" data-model="all">كل النماذج</button>
        <?php foreach ($models as $k => $m): ?><button class="chip" data-model="<?= e($k) ?>"><?= e($k) ?></button>
        <?php endforeach; ?>
      </div>
      <?php
        $statusCounts = array_count_values(array_column($jsUnits, 'status'));
        $barLabels = ['sold' => 'مباعة', 'reserved' => 'محجوز', 'available' => 'متاح'];
        $unitsWord = function (int $n): string {
            if ($n === 1) return 'شقة واحدة';
            if ($n === 2) return 'شقتان';
            if ($n >= 3 && $n <= 10) return $n . ' شقق';
            return $n . ' شقة';
        };
      ?>
      <div class="sales-bar" role="img" aria-label="<?= e(implode('، ', array_map(fn($k) => $barLabels[$k] . ' ' . $unitsWord((int)($statusCounts[$k] ?? 0)), array_keys($barLabels)))) ?>">
        <?php foreach ($barLabels as $k => $label): $n = (int)($statusCounts[$k] ?? 0); if (!$n) continue; ?>
        <div class="sb-seg sb-<?= $k ?>" style="flex-grow: <?= $n ?>">
          <span class="sb-label"><?= e($label) ?></span>
          <span class="sb-track" aria-hidden="true"></span>
          <span class="sb-count"><?= e($unitsWord($n)) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="building" id="building" aria-label="مخطط الأدوار والشقق"></div>
    <?php if (!empty($US['note'])): ?><p class="note"><?= e($US['note']) ?></p><?php endif; ?>
  </div>
</section>

<!-- ============ Plans ============ -->
<?php $tabs = $PL['tabs'] ?? []; $first = $tabs[0] ?? null; ?>
<section class="section" id="plans">
  <div class="container">
    <div class="head-row">
      <div class="section-head reveal"><?= $sectionHead($PL) ?></div>
      <?php if (count($tabs) > 1): ?>
      <div class="tabs reveal" role="tablist" aria-label="<?= e($PL['title'] ?? '') ?>" id="planTabs">
        <?php foreach ($tabs as $i => $t): ?><button class="tab<?= $i ? '' : ' is-active' ?>" role="tab" aria-selected="<?= $i ? 'false' : 'true' ?>" data-src="<?= url($t['image']) ?>" data-caption="<?= e($t['caption']) ?>"><?= e($t['label']) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="plan-view reveal">
      <?php if ($first): ?>
      <figure>
        <img id="planImg" src="<?= url($first['image']) ?>" alt="<?= e($first['caption']) ?>" loading="lazy" data-lightbox="<?= url($first['image']) ?>">
        <figcaption id="planCaption"><?= e($first['caption']) ?></figcaption>
      </figure>
      <?php endif; ?>
      <?php if (!empty($PL['rows'])): ?>
      <aside class="plan-table">
        <h3><?= e($PL['table_title'] ?? '') ?></h3>
        <table>
          <thead><tr><th>الدور</th><th>الاستخدام</th><th>الوحدات</th><th>المساحة</th></tr></thead>
          <tbody>
            <?php foreach ($PL['rows'] as $r): ?><tr><td><?= e($r['floor']) ?></td><td><?= e($r['use']) ?></td><td><?= e($r['units']) ?></td><td><?= e($r['area']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
          <?php if (!empty($PL['total_label'])): ?><tfoot><tr><td colspan="2"><?= e($PL['total_label']) ?></td><td><?= e($PL['total_units'] ?? '') ?></td><td><?= e($PL['total_area'] ?? '') ?></td></tr></tfoot><?php endif; ?>
        </table>
        <?php if (!empty($PL['note'])): ?><p class="note"><?= e($PL['note']) ?></p><?php endif; ?>
      </aside>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ Location ============ -->
<section class="section section-dark" id="location">
  <div class="container location-grid">
    <div class="location-text reveal">
      <?= $sectionHead($LO, '', true) ?>
      <?php if (!empty($LO['text'])): ?><p><?= rich($LO['text']) ?></p><?php endif; ?>
      <?php if (!empty($LO['boundaries'])): ?>
      <ul class="boundaries">
        <?php foreach ($LO['boundaries'] as $b): ?><li><span><?= e($b['dir']) ?></span><b><?= e($b['title']) ?></b><em><?= e($b['detail']) ?></em></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <a class="btn btn-primary" id="mapLink" target="_blank" rel="noopener"><?= e($LO['button_label'] ?? 'افتح في خرائط Google') ?></a>
    </div>
    <div class="map-wrap reveal">
      <iframe id="mapFrame" title="<?= e('خريطة موقع ' . $siteName) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      <a class="map-pin-link" id="mapPinLink" target="_blank" rel="noopener" title="الانتقال إلى موقع المشروع" aria-label="الانتقال إلى موقع المشروع في خرائط Google"></a>
      <?php if (!empty($LO['map_label'])): ?><span class="map-coords" dir="ltr"><?= e($LO['map_label']) ?></span><?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ Contact ============ -->
<section class="section" id="contact">
  <div class="container contact-grid">
    <div class="contact-info reveal">
      <?= $sectionHead($CO) ?>
      <?php if (!empty($CO['text'])): ?><p><?= rich($CO['text']) ?></p><?php endif; ?>
      <ul class="contact-list">
        <?php if (!empty($S['phone_display'])): ?><li><svg viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2"/></svg><a id="phoneLink" dir="ltr"><?= e($S['phone_display']) ?></a></li><?php endif; ?>
        <?php if (!empty($CO['address'])): ?><li><svg viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0112 2.5a7 7 0 017 7C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span><?= e($CO['address']) ?></span></li><?php endif; ?>
      </ul>
      <?php if (!empty($CO['show_marketer']) && !empty($S['marketer_logo'])): ?>
      <div class="contact-marketer"><?= $marketer('contact-marketer', true) ?></div>
      <?php endif; ?>
    </div>
    <form class="contact-form reveal" id="leadForm" action="api/lead.php" method="post" novalidate>
      <div class="field">
        <label for="fName">الاسم الكامل</label>
        <input id="fName" name="name" required maxlength="120" autocomplete="name">
      </div>
      <div class="field">
        <label for="fPhone">رقم الجوال</label>
        <input id="fPhone" name="phone" type="tel" inputmode="tel" required pattern="^(05|\+9665|9665)[0-9]{8}$" placeholder="05xxxxxxxx" dir="ltr" autocomplete="tel">
      </div>
      <div class="field-row">
        <div class="field">
          <label for="fModel">النموذج المفضل</label>
          <select id="fModel" name="model">
            <option value="">بدون تحديد</option>
            <?php foreach ($models as $k => $m): ?><option value="<?= e($k) ?>"><?= e($m['name'] . ($m['roof'] ? ' (مع سطح)' : '')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="fUnit">رقم الشقة (اختياري)</label>
          <input id="fUnit" name="unit" type="number" min="1" max="<?= max(array_column($jsUnits, 'no') ?: [1]) ?>" inputmode="numeric">
        </div>
      </div>
      <div class="field">
        <label for="fMsg">ملاحظات</label>
        <textarea id="fMsg" name="message" rows="3" maxlength="2000"></textarea>
      </div>
      <!-- Spam trap: real visitors never see or fill this -->
      <div class="hp-field" aria-hidden="true"><label for="fWebsite">Website</label><input id="fWebsite" name="website" tabindex="-1" autocomplete="off"></div>
      <button type="submit" class="btn btn-primary btn-block"><?= e($CO['submit_label'] ?? 'إرسال الطلب') ?></button>
      <p class="form-status" id="formStatus" role="status"></p>
    </form>
  </div>
</section>
</main>

<!-- ============ Footer ============ -->
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <?php if (!empty($FO['logo'])): ?><img class="footer-logo" src="<?= url($FO['logo']) ?>" alt="<?= e($S['logo_alt'] ?? $siteName) ?>" loading="lazy"><?php endif; ?>
      <?php if (!empty($FO['description'])): ?><p class="footer-desc"><?= e($FO['description']) ?></p><?php endif; ?>
    </div>
    <div>
      <h4><?= e($FO['links_title'] ?? '') ?></h4>
      <ul>
        <?php foreach ($FO['links'] ?? [] as $l): ?><li><a href="<?= url($l['href']) ?>"><?= e($l['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h4><?= e($FO['data_title'] ?? '') ?></h4>
      <ul>
        <?php foreach (array_filter(explode("\n", (string)($FO['data_items'] ?? '')), 'strlen') as $li): ?><li<?= str_contains($li, 'الحصري') ? ' class="is-bright"' : '' ?>><?= e($li) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="container footer-bottom">
    <span><?= e(str_replace('{year}', date('Y'), $FO['copyright'] ?? '')) ?></span>
  </div>
</footer>

<!-- Floating WhatsApp -->
<a class="wa-float" id="waFloat" target="_blank" rel="noopener" aria-label="تواصل عبر واتساب" hidden>
  <svg viewBox="0 0 32 32"><path d="M16 3a13 13 0 00-11.2 19.6L3 29l6.6-1.7A13 13 0 1016 3zm0 23.6c-2 0-4-.6-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.6 10.6 0 1116 26.6zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7 0a8.7 8.7 0 01-4.3-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 00-.9.4 3.6 3.6 0 00-1.1 2.7 6.3 6.3 0 001.3 3.3 14.4 14.4 0 005.5 4.9c2 .9 2.9 1 3.9.8a3.3 3.3 0 002.2-1.5 2.7 2.7 0 00.2-1.5c-.1-.2-.3-.3-.6-.4z"/></svg>
</a>

<!-- Unit modal -->
<div class="modal" id="unitModal" hidden>
  <div class="modal-backdrop" data-close></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="unitTitle">
    <button class="modal-close" data-close aria-label="إغلاق">×</button>
    <div id="unitBody"></div>
  </div>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" hidden>
  <button class="modal-close" data-close aria-label="إغلاق">×</button>
  <img alt="">
</div>

<script>
window.SITE_CONFIG = <?= $json($siteConfig) ?>;
window.UNIT_MODELS = <?= $json((object)$models) ?>;
window.UNITS = <?= $json($jsUnits) ?>;
</script>
<script src="assets/js/main.js?v=<?= @filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
</body>
</html>
