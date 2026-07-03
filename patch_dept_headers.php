<?php
/* ============================================================
 * patch_dept_headers.php
 * แก้หัวเว็บของไฟล์ dept_*.php ทุกไฟล์ในโฟลเดอร์นี้ให้:
 *   1) เอาชื่อแผนกไปต่อท้าย "กลุ่มงานการพยาบาล" บนแบนเนอร์
 *   2) เอาชื่อแผนกออกจากแถบล่าง เหลือแค่ปุ่ม "กลับหน้าแรก"
 *
 * วิธีใช้:  วางไฟล์นี้ไว้โฟลเดอร์เดียวกับ dept_*.php แล้วรัน
 *          php patch_dept_headers.php
 * รันซ้ำได้ ไฟล์ที่แก้แล้วจะถูกข้าม (idempotent)
 * สำรองไฟล์เดิมเป็น .bak ให้อัตโนมัติ
 * ============================================================ */

$dir = __DIR__;

$OLD_BANNER = '            <h2 class="mb-0 fw-bold">กลุ่มงานการพยาบาล</h2>';
$NEW_BANNER = '            <h2 class="mb-0 fw-bold">กลุ่มงานการพยาบาล <span class="fw-normal opacity-90">· <?= htmlspecialchars($dept[\'name\']) ?></span></h2>';

$OLD_HEAD =
'<div class="dept-page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-buildings-fill me-2"></i><?= htmlspecialchars($dept[\'name\']) ?></h1>
        </div>
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left-circle-fill"></i> กลับหน้าแรก</a>
    </div>
</div>';
$NEW_HEAD =
'<div class="dept-page-header">
    <div class="container d-flex justify-content-end align-items-center flex-wrap gap-2">
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left-circle-fill"></i> กลับหน้าแรก</a>
    </div>
</div>';

$files = glob($dir . '/dept_*.php');
$patched = 0; $skipped = 0;

foreach ($files as $f) {
    $raw = file_get_contents($f);
    $nl  = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";   // จำ line ending เดิม
    $txt = str_replace("\r\n", "\n", $raw);

    if (strpos($txt, $OLD_BANNER) === false && strpos($txt, $OLD_HEAD) === false) {
        echo "skip  " . basename($f) . " (แก้ไปแล้ว หรือรูปแบบต่างจากแม่แบบ)\n";
        $skipped++;
        continue;
    }

    $txt = str_replace($OLD_BANNER, $NEW_BANNER, $txt);
    $txt = str_replace($OLD_HEAD,   $NEW_HEAD,   $txt);

    copy($f, $f . '.bak');                          // สำรองไฟล์เดิม
    file_put_contents($f, str_replace("\n", $nl, $txt));
    echo "OK    " . basename($f) . "\n";
    $patched++;
}

echo "\nเสร็จ: แก้ $patched ไฟล์, ข้าม $skipped ไฟล์ (สำรองเป็น .bak ให้แล้ว)\n";