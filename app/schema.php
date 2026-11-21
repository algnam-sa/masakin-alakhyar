<?php
/**
 * Editor schema: one entry per site section. The admin content editor is generated from this.
 * Field types: text, textarea, number, url, image, video, toggle, select, lines (one item per line),
 *              list (repeatable group of sub-fields).
 */
require_once __DIR__ . '/icons.php';

function content_schema(): array
{
    $icons = [];
    foreach (FEATURE_ICONS as $k => [$label]) $icons[$k] = $label;

    return [
        'settings' => [
            'title' => 'الإعدادات العامة',
            'hint'  => 'اسم الموقع والشعارات وبيانات التواصل والقائمة العلوية.',
            'fields' => [
                ['name' => 'site_name', 'label' => 'اسم المشروع', 'type' => 'text'],
                ['name' => 'meta_title', 'label' => 'عنوان الصفحة في المتصفح ومحركات البحث', 'type' => 'text'],
                ['name' => 'meta_description', 'label' => 'وصف الموقع لمحركات البحث', 'type' => 'textarea'],
                ['name' => 'og_image', 'label' => 'صورة المشاركة في وسائل التواصل', 'type' => 'image'],
                ['group' => 'بيانات التواصل'],
                ['name' => 'phone_display', 'label' => 'رقم الجوال كما يظهر', 'type' => 'text', 'dir' => 'ltr', 'placeholder' => '05xxxxxxxx'],
                ['name' => 'phone_intl', 'label' => 'رقم واتساب بالصيغة الدولية (بدون +)', 'type' => 'text', 'dir' => 'ltr', 'placeholder' => '9665xxxxxxxx',
                 'help' => 'عند تعبئته يظهر زر واتساب العائم ويُفعّل زر الاتصال.'],
                ['name' => 'whatsapp_message', 'label' => 'رسالة واتساب الافتراضية', 'type' => 'text'],
                ['name' => 'email', 'label' => 'البريد الإلكتروني لاستقبال إشعارات الطلبات', 'type' => 'text', 'dir' => 'ltr',
                 'help' => 'اختياري. يُرسل إشعار بكل طلب جديد إذا كانت الاستضافة تدعم إرسال البريد.'],
                ['group' => 'الشعارات'],
                ['name' => 'logo_light', 'label' => 'الشعار الفاتح (فوق الصور وفي الأسفل)', 'type' => 'image'],
                ['name' => 'logo_dark', 'label' => 'الشعار الداكن (عند النزول في الصفحة)', 'type' => 'image'],
                ['name' => 'favicon', 'label' => 'أيقونة المتصفح', 'type' => 'image'],
                ['name' => 'logo_alt', 'label' => 'وصف الشعار (للقارئات الصوتية)', 'type' => 'text'],
                ['name' => 'marketer_logo', 'label' => 'شعار المسوق', 'type' => 'image'],
                ['name' => 'marketer_name', 'label' => 'اسم المسوق', 'type' => 'text'],
                ['name' => 'marketer_label', 'label' => 'العبارة تحت شعار المسوق', 'type' => 'text'],
                ['group' => 'الأسعار'],
                ['name' => 'show_prices', 'label' => 'إظهار أسعار الشقق في الموقع', 'type' => 'toggle',
                 'help' => 'تُدخل الأسعار من صفحة "الشقق". الشقق بدون سعر لا يظهر لها سعر.'],
                ['name' => 'currency', 'label' => 'العملة', 'type' => 'text'],
                ['group' => 'القائمة العلوية'],
                ['name' => 'nav', 'label' => 'روابط القائمة', 'type' => 'list', 'item_label' => 'رابط', 'fields' => [
                    ['name' => 'label', 'label' => 'النص', 'type' => 'text'],
                    ['name' => 'href', 'label' => 'الرابط', 'type' => 'text', 'dir' => 'ltr', 'help' => 'مثل #units أو رابط كامل'],
                ]],
                ['name' => 'nav_cta_label', 'label' => 'نص الزر المميز', 'type' => 'text'],
                ['name' => 'nav_cta_href', 'label' => 'رابط الزر المميز', 'type' => 'text', 'dir' => 'ltr'],
            ],
        ],
        'hero' => [
            'title' => 'الواجهة الرئيسية',
            'hint'  => 'أول ما يراه الزائر: العنوان والنص والأزرار والفيديو.',
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'العبارة الصغيرة فوق العنوان', 'type' => 'text'],
                ['name' => 'title', 'label' => 'العنوان الرئيسي', 'type' => 'text'],
                ['name' => 'lead', 'label' => 'النص تحت العنوان', 'type' => 'textarea', 'help' => 'يُضبط حجمه تلقائيًا ليملأ سطرين بعرض العنوان.'],
                ['name' => 'btn1_label', 'label' => 'نص الزر الأول', 'type' => 'text'],
                ['name' => 'btn1_href', 'label' => 'رابط الزر الأول', 'type' => 'text', 'dir' => 'ltr'],
                ['group' => 'البروشور'],
                ['name' => 'brochure_pdf', 'label' => 'ملف البروشور (PDF)', 'type' => 'pdf',
                 'help' => 'يظهر زر التحميل في الواجهة فقط بعد رفع الملف.'],
                ['name' => 'brochure_label', 'label' => 'نص زر التحميل', 'type' => 'text'],
                ['group' => 'الخلفية'],
                ['name' => 'video_mp4', 'label' => 'فيديو الخلفية (MP4)', 'type' => 'video', 'help' => 'اتركه فارغًا لعرض الصورة فقط.'],
                ['name' => 'video_webm', 'label' => 'نسخة WebM (اختياري)', 'type' => 'video'],
                ['name' => 'poster', 'label' => 'صورة الخلفية / صورة ما قبل تشغيل الفيديو', 'type' => 'image'],
                ['name' => 'show_marketer', 'label' => 'إظهار شعار المسوق في الواجهة', 'type' => 'toggle'],
            ],
        ],
        'stats' => [
            'title' => 'شريط الأرقام',
            'hint'  => 'الأرقام التي تظهر تحت الواجهة مباشرة.',
            'fields' => [
                ['name' => 'items', 'label' => 'الأرقام', 'type' => 'list', 'item_label' => 'رقم', 'fields' => [
                    ['name' => 'number', 'label' => 'الرقم', 'type' => 'number'],
                    ['name' => 'decimals', 'label' => 'عدد المنازل العشرية', 'type' => 'number'],
                    ['name' => 'suffix', 'label' => 'لاحقة صغيرة (اختياري)', 'type' => 'text'],
                    ['name' => 'label', 'label' => 'الوصف', 'type' => 'text'],
                ]],
            ],
        ],
        'about' => [
            'title' => 'عن المشروع',
            'hint'  => 'لكتابة نص عريض ضعه بين نجمتين مزدوجتين: **نص عريض**',
            'fields' => [
                ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'sub', 'label' => 'العنوان الفرعي', 'type' => 'text'],
                ['name' => 'paragraphs', 'label' => 'الفقرات', 'type' => 'list', 'item_label' => 'فقرة', 'fields' => [
                    ['name' => 'text', 'label' => 'النص', 'type' => 'textarea'],
                ]],
                ['name' => 'facts', 'label' => 'جدول البيانات', 'type' => 'list', 'item_label' => 'سطر', 'fields' => [
                    ['name' => 'label', 'label' => 'البند', 'type' => 'text'],
                    ['name' => 'value', 'label' => 'القيمة', 'type' => 'text'],
                ]],
                ['group' => 'الوسائط'],
                ['name' => 'video_mp4', 'label' => 'الفيديو (MP4)', 'type' => 'video', 'help' => 'اتركه فارغًا لعرض الصورة بدلًا منه.'],
                ['name' => 'video_webm', 'label' => 'نسخة WebM (اختياري)', 'type' => 'video'],
                ['name' => 'poster', 'label' => 'الصورة', 'type' => 'image'],
                ['name' => 'caption', 'label' => 'العبارة على الصورة', 'type' => 'text'],
            ],
        ],
        'features' => [
            'title' => 'المميزات',
            'hint'  => 'مربعات المميزات. يمكنك إضافة وحذف وترتيب المربعات.',
            'fields' => [
                ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'sub', 'label' => 'العنوان الفرعي', 'type' => 'text'],
                ['name' => 'items', 'label' => 'المميزات', 'type' => 'list', 'item_label' => 'ميزة', 'fields' => [
                    ['name' => 'icon', 'label' => 'الأيقونة', 'type' => 'select', 'options' => $icons],
                    ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                    ['name' => 'text', 'label' => 'الوصف', 'type' => 'textarea'],
                ]],
            ],
        ],
        'models' => [
            'title' => 'نماذج الشقق',
            'hint'  => 'رمز النموذج يربطه بالشقق في صفحة "الشقق".',
            'fields' => [
                ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'sub', 'label' => 'العنوان الفرعي', 'type' => 'text'],
                ['name' => 'desc', 'label' => 'الوصف', 'type' => 'textarea'],
                ['name' => 'items', 'label' => 'النماذج', 'type' => 'list', 'item_label' => 'نموذج', 'fields' => [
                    ['name' => 'key', 'label' => 'رمز النموذج', 'type' => 'text', 'dir' => 'ltr', 'help' => 'حرف أو رقم قصير مثل A'],
                    ['name' => 'name', 'label' => 'الاسم', 'type' => 'text'],
                    ['name' => 'area', 'label' => 'مساحة الشقة (م²)', 'type' => 'number'],
                    ['name' => 'roof', 'label' => 'مساحة السطح (م²) — اتركه فارغًا إن لم يوجد', 'type' => 'number'],
                    ['name' => 'image', 'label' => 'صورة المخطط', 'type' => 'image'],
                    ['name' => 'rooms', 'label' => 'المكونات (كل مكوّن في سطر)', 'type' => 'lines'],
                ]],
            ],
        ],
        'units_section' => [
            'title' => 'قسم الوحدات',
            'hint'  => 'نصوص القسم وأسماء الأدوار. بيانات الشقق نفسها من صفحة "الشقق".',
            'fields' => [
                ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'sub', 'label' => 'العنوان الفرعي', 'type' => 'text'],
                ['name' => 'desc', 'label' => 'الوصف', 'type' => 'textarea'],
                ['name' => 'note', 'label' => 'الملاحظة أسفل الشقق', 'type' => 'textarea'],
                ['name' => 'inquiry_label', 'label' => 'نص زر الاستفسار في نافذة الشقة', 'type' => 'text'],
                ['name' => 'floors', 'label' => 'الأدوار', 'type' => 'list', 'item_label' => 'دور', 'fields' => [
                    ['name' => 'number', 'label' => 'رقم الدور', 'type' => 'number'],
                    ['name' => 'name', 'label' => 'الاسم الكامل', 'type' => 'text'],
                    ['name' => 'short', 'label' => 'الاسم المختصر (لأزرار التصفية)', 'type' => 'text'],
                ]],
            ],
        ],
        'plans' => [
            'title' => 'المخططات',
            'hint'  => 'صور المساقط وجدول مكونات البناء.',
            'fields' => [
                ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'sub', 'label' => 'العنوان الفرعي', 'type' => 'text'],
                ['name' => 'tabs', 'label' => 'المساقط', 'type' => 'list', 'item_label' => 'مسقط', 'fields' => [
                    ['name' => 'label', 'label' => 'اسم الزر', 'type' => 'text'],
                    ['name' => 'image', 'label' => 'الصورة', 'type' => 'image'],
                    ['name' => 'caption', 'label' => 'الوصف تحت الصورة', 'type' => 'text'],
                ]],
                ['group' => 'جدول مكونات البناء'],
                ['name' => 'table_title', 'label' => 'عنوان الجدول', 'type' => 'text'],
                ['name' => 'rows', 'label' => 'صفوف الجدول', 'type' => 'list', 'item_label' => 'صف', 'fields' => [
                    ['name' => 'floor', 'label' => 'الدور', 'type' => 'text'],
                    ['name' => 'use', 'label' => 'الاستخدام', 'type' => 'text'],
                    ['name' => 'units', 'label' => 'الوحدات', 'type' => 'text'],
                    ['name' => 'area', 'label' => 'المساحة', 'type' => 'text'],
                ]],
                ['name' => 'total_label', 'label' => 'نص الإجمالي', 'type' => 'text'],
                ['name' => 'total_units', 'label' => 'إجمالي الوحدات', 'type' => 'text'],
                ['name' => 'total_area', 'label' => 'إجمالي المساحة', 'type' => 'text'],
                ['name' => 'note', 'label' => 'ملاحظة تحت الجدول', 'type' => 'text'],
            ],
        ],
        'location' => [
            'title' => 'الموقع',
            'hint'  => 'نص الموقع وحدود القطعة وإحداثيات الخريطة.',
            'fields' => [
                ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'sub', 'label' => 'العنوان الفرعي', 'type' => 'text'],
                ['name' => 'text', 'label' => 'الوصف', 'type' => 'textarea'],
                ['name' => 'boundaries', 'label' => 'الحدود', 'type' => 'list', 'item_label' => 'حد', 'fields' => [
                    ['name' => 'dir', 'label' => 'الجهة', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'الوصف', 'type' => 'text'],
                    ['name' => 'detail', 'label' => 'التفصيل', 'type' => 'text'],
                ]],
                ['name' => 'button_label', 'label' => 'نص زر الخريطة', 'type' => 'text'],
                ['group' => 'الخريطة'],
                ['name' => 'map_lat', 'label' => 'خط العرض (Latitude)', 'type' => 'number', 'help' => 'مثال: 26.370944'],
                ['name' => 'map_lng', 'label' => 'خط الطول (Longitude)', 'type' => 'number', 'help' => 'مثال: 43.887417'],
                ['name' => 'map_label', 'label' => 'الإحداثيات كما تظهر على الخريطة', 'type' => 'text', 'dir' => 'ltr'],
            ],
        ],
        'contact' => [
            'title' => 'سجّل اهتمامك',
            'hint'  => 'نصوص قسم التواصل ونموذج الطلب. الطلبات تصل إلى صفحة "طلبات العملاء".',
            'fields' => [
                ['name' => 'title', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'sub', 'label' => 'العنوان الفرعي', 'type' => 'text'],
                ['name' => 'text', 'label' => 'الوصف', 'type' => 'textarea'],
                ['name' => 'address', 'label' => 'العنوان', 'type' => 'text'],
                ['name' => 'show_marketer', 'label' => 'إظهار بطاقة المسوق', 'type' => 'toggle'],
                ['name' => 'submit_label', 'label' => 'نص زر الإرسال', 'type' => 'text'],
                ['name' => 'success', 'label' => 'رسالة النجاح بعد الإرسال', 'type' => 'textarea'],
            ],
        ],
        'footer' => [
            'title' => 'أسفل الموقع',
            'hint'  => 'اكتب {year} في نص الحقوق ليظهر العام الحالي تلقائيًا.',
            'fields' => [
                ['name' => 'logo', 'label' => 'الشعار', 'type' => 'image'],
                ['name' => 'description', 'label' => 'النبذة', 'type' => 'textarea'],
                ['name' => 'links_title', 'label' => 'عنوان الروابط', 'type' => 'text'],
                ['name' => 'links', 'label' => 'الروابط', 'type' => 'list', 'item_label' => 'رابط', 'fields' => [
                    ['name' => 'label', 'label' => 'النص', 'type' => 'text'],
                    ['name' => 'href', 'label' => 'الرابط', 'type' => 'text', 'dir' => 'ltr'],
                ]],
                ['name' => 'data_title', 'label' => 'عنوان بيانات المشروع', 'type' => 'text'],
                ['name' => 'data_items', 'label' => 'بيانات المشروع (كل بند في سطر)', 'type' => 'lines'],
                ['name' => 'copyright', 'label' => 'نص الحقوق', 'type' => 'text'],
            ],
        ],
    ];
}

/** Normalise submitted form data against a list of field definitions. */
function sanitize_fields(array $fields, $input): array
{
    $input = is_array($input) ? $input : [];
    $out = [];
    foreach ($fields as $f) {
        if (!isset($f['name'])) continue;
        $n = $f['name'];
        $v = $input[$n] ?? null;
        switch ($f['type']) {
            case 'toggle':
                $out[$n] = !empty($v);
                break;
            case 'list':
                $items = [];
                foreach ((is_array($v) ? $v : []) as $item) {
                    $clean = sanitize_fields($f['fields'], $item);
                    if (implode('', array_map(fn($x) => is_bool($x) ? '' : (string)$x, $clean)) === '') continue; // skip empty rows
                    $items[] = $clean;
                }
                $out[$n] = $items;
                break;
            case 'number':
                $v = trim((string)$v);
                $out[$n] = ($v === '' || !is_numeric($v)) ? '' : $v;
                break;
            case 'select':
                $v = (string)$v;
                $out[$n] = array_key_exists($v, $f['options']) ? $v : (string)array_key_first($f['options']);
                break;
            case 'lines':
                $lines = preg_split('/\r\n|\r|\n/', (string)$v);
                $out[$n] = implode("\n", array_values(array_filter(array_map('trim', $lines), 'strlen')));
                break;
            case 'textarea':
                $out[$n] = trim(str_replace("\r\n", "\n", (string)$v));
                break;
            default: // text, url, image, video
                $out[$n] = trim(preg_replace('/[\r\n]+/', ' ', (string)$v));
        }
    }
    return $out;
}
