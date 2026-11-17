<?php
/** Line icons for feature cards (24×24, stroked). key => [Arabic label, inner SVG]. */
const FEATURE_ICONS = [
    'facades'  => ['واجهات / مبنى', '<path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>'],
    'parking'  => ['مواقف سيارات', '<path d="M5 17h14M6 17l1.5-5h9L18 17M7 17v2M17 17v2M8 14h.01M16 14h.01"/><rect x="3" y="3" width="18" height="18" rx="3"/>'],
    'shield'   => ['أمان / كود البناء', '<path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/><path d="M9 12l2 2 4-4"/>'],
    'roof'     => ['سطح / تراس', '<path d="M4 20V10M20 20V10M2 10h20M6 10V6h12v4M8 20v-6M12 20v-6M16 20v-6"/>'],
    'laundry'  => ['غرفة غسيل', '<rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="13" r="4"/><path d="M8 6h.01M11 6h.01"/>'],
    'home'     => ['منزل / جودة', '<path d="M3 9l9-6 9 6v12H3z"/><path d="M3 9h18M8 21V13h8v8"/>'],
    'ac'       => ['تكييف', '<rect x="3" y="4" width="18" height="7" rx="2"/><path d="M7 8h10M8 15c0 1.5-1 2-1 3.5M12 15c0 1.5-1 2-1 3.5M16 15c0 1.5-1 2-1 3.5"/>'],
    'balcony'  => ['بلكونة', '<path d="M5 3h14v9H5zM12 3v9M3 12h18M5 12v9M19 12v9M9 12v9M15 12v9M3 21h18"/>'],
    'elevator' => ['مصعد', '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M12 3v18M8.5 9l1.5-2 1.5 2M12.5 15l1.5 2 1.5-2"/>'],
    'camera'   => ['كاميرات مراقبة', '<path d="M3 7l13-3 1 4-13 3zM8 10l1 4h4M4 10v8M2 18h4"/>'],
    'key'      => ['تسليم مفتاح', '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3M15 8l2 2"/>'],
    'location' => ['موقع مميز', '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0112 2.5a7 7 0 017 7C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>'],
    'mosque'   => ['قرب المسجد', '<path d="M4 21V11M20 21V11M4 11c0-2 1-3 1-5 0 2 1 3 1 5M18 11c0-2 1-3 1-5 0 2 1 3 1 5M7 21v-6a5 5 0 0110 0v6M3 21h18M12 6a3 3 0 00-3 3h6a3 3 0 00-3-3zM12 3v3"/>'],
    'school'   => ['قرب المدارس', '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5M22 9v6"/>'],
    'tree'     => ['مساحات خضراء', '<path d="M12 21v-6M7 15h10l-3-4h2l-4-6-4 6h2z"/>'],
    'water'    => ['خزانات مياه', '<path d="M12 3s6 6.5 6 11a6 6 0 01-12 0c0-4.5 6-11 6-11z"/>'],
    'bolt'     => ['كهرباء مستقلة', '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>'],
    'wifi'     => ['تجهيزات ذكية', '<path d="M2 9a15 15 0 0120 0M5 12.5a10 10 0 0114 0M8.5 16a5 5 0 017 0M12 19.5h.01"/>'],
    'star'     => ['ميزة عامة', '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>'],
    'check'    => ['علامة صح', '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>'],
];

function icon_svg(string $key): string
{
    $inner = FEATURE_ICONS[$key][1] ?? FEATURE_ICONS['star'][1];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $inner . '</svg>';
}
