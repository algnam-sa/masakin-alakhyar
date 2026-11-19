<?php
/**
 * Permission catalog. A user is either a full administrator (is_admin = 1, every permission,
 * plus managing users) or has an explicit list of the permissions below.
 */
require_once __DIR__ . '/schema.php';

function permission_groups(): array
{
    $content = [];
    foreach (content_schema() as $key => $s) $content['content.' . $key] = $s['title'];

    return [
        'الشقق' => [
            'units.view' => 'عرض الشقق',
            'units.edit' => 'تعديل الشقق (الحالة والسعر والبيانات والإضافة والحذف)',
        ],
        'طلبات العملاء' => [
            'leads.view'   => 'عرض الطلبات',
            'leads.edit'   => 'متابعة الطلبات (تغيير الحالة والملاحظات)',
            'leads.export' => 'تصدير الطلبات إلى Excel',
            'leads.delete' => 'حذف الطلبات',
        ],
        'محتوى الموقع' => $content,
        'النظام' => [
            'media'  => 'مكتبة الوسائط (رفع الملفات وحذفها)',
            'backup' => 'النسخ الاحتياطي (تنزيل واستعادة)',
        ],
    ];
}

function all_permissions(): array
{
    $all = [];
    foreach (permission_groups() as $perms) $all += $perms;
    return $all;
}

/** Keep only known permission keys, and imply "view" when an edit-type permission is given. */
function clean_permissions($input): array
{
    $known = all_permissions();
    $out = array_values(array_unique(array_filter((array)$input, fn($p) => is_string($p) && isset($known[$p]))));
    if (array_intersect(['units.edit'], $out)) $out[] = 'units.view';
    if (array_intersect(['leads.edit', 'leads.export', 'leads.delete'], $out)) $out[] = 'leads.view';
    return array_values(array_unique($out));
}

function user_can(?array $user, string $perm): bool
{
    if (!$user) return false;
    if (!empty($user['is_admin'])) return true;
    if ($perm === 'admin') return false;
    if ($perm === 'content.any') {
        foreach ($user['perms'] as $p) if (str_starts_with($p, 'content.')) return true;
        return false;
    }
    return in_array($perm, $user['perms'], true);
}
