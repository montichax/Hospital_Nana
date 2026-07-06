<?php
// =====================================================================
//  ศัลยกรรม — หน้าหอผู้ป่วย/หน่วยงาน
//  ดึงข้อมูลจากตาราง department_contents โดยอ้างอิง department_id = 8
// =====================================================================
require_once 'connect.php';

$DEPT_ID   = 8;
$DEPT_NAME = 'ศัลยกรรม';

function dateToThaiFull($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'ไม่ระบุวันที่';
    $time = strtotime($dateStr);
    if (!$time) return htmlspecialchars($dateStr);
    $d = date('j', $time);
    $m = date('n', $time);
    $y = date('Y', $time) + 543;
    $months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    return "$d {$months[$m]} $y";
}

function parseFileNames($fileData) {
    if (empty($fileData)) return [];
    $decoded = json_decode($fileData, true);
    if (is_array($decoded)) return $decoded;
    if (is_string($fileData) && !empty($fileData)) return [$fileData];
    return [];
}

// ---------- ข้อมูลแผนกนี้ ----------
$stmt = $conn->prepare("SELECT * FROM departments WHERE id = :id");
$stmt->execute([':id' => $DEPT_ID]);
$dept = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$dept) $dept = ['id' => $DEPT_ID, 'name' => $DEPT_NAME, 'link_url' => null];

// ---------- เนื้อหาของแผนกนี้ (จัดกลุ่มตาม section) ----------
$stmt = $conn->prepare("SELECT * FROM department_contents WHERE department_id = :id ORDER BY section ASC, sort_order ASC, id ASC");
$stmt->execute([':id' => $DEPT_ID]);
$content_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bySection = [];
foreach ($content_rows as $row) { $bySection[$row['section']][] = $row; }

$sectionLabels = [
    'structure'       => 'โครงสร้างการบริหารงาน',
    'personnel'       => 'ทำเนียบบุคลากร',
    'service'         => 'การให้บริการต่างๆ',
    'service_profile' => 'Service Profile',
    'indicator'       => 'ตัวชี้วัด',
    'academic'        => 'ผลงานวิจัย',
    'wi'              => 'WI / SP',
    'knowledge'       => 'ข่าวประชาสัมพันธ์ / เกร็ดความรู้',
];

// ---------- เมนูแนวนอน 5 หมวด ----------
$menuGroups = [
    ['label' => 'ข่าวประชาสัมพันธ์ / เกร็ดความรู้',  'icon' => 'bi-lightbulb-fill',         'sections' => ['knowledge']],
    ['label' => 'โครงสร้างการบริหารงาน',           'icon' => 'bi-diagram-3-fill',         'sections' => ['structure', 'personnel', 'service']],
    ['label' => 'Service Profile',                  'icon' => 'bi-clipboard2-pulse-fill',  'sections' => ['service_profile', 'indicator']],
    ['label' => 'ผลงานวิจัย / วิชาการ',             'icon' => 'bi-journal-text',           'sections' => ['academic']],
    ['label' => 'WI, SP',                           'icon' => 'bi-file-earmark-medical-fill','sections' => ['wi']],
];

// ---------- ดึงข่าวทั้งหมดของแผนก (หมวด knowledge) เรียงจากใหม่->เก่า ----------
$news_list = [];
if (!empty($bySection['knowledge'])) {
    $stmt = $conn->prepare("SELECT * FROM department_contents WHERE department_id = :id AND section = 'knowledge' ORDER BY sort_order ASC, id ASC");
    $stmt->execute([':id' => $DEPT_ID]);
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------- ฟังก์ชันแสดงไฟล์แนบ ----------
function renderAttachments($row) {
    $files = parseFileNames($row['file_name'] ?? '');
    $html  = '';
    foreach ($files as $fname) {
        if (empty($fname) || $fname === 'default.jpg') continue;
        $path = 'uploads/' . $fname;
        $ext  = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        $safe = htmlspecialchars($path);

       if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $html .= '<div class="text-center">
            <img src="' . $safe . '" class="dc-img lightbox-trigger shadow-sm border" alt="" onerror="this.style.display=\'none\'">
            <small class="text-muted d-block mt-2">คลิกที่รูปภาพเพื่อขยาย</small>
          </div>';
        } elseif ($ext === 'pdf') {
            // PDF: เอาตัวอักษรทับซ้อนและ overlay ออกไปทั้งหมดตามคำสั่ง
            $html .= '<div class="text-center">
            <div class="dc-pdf-wrap pdf-lightbox-trigger shadow-sm" data-src="' . $safe . '">
                <embed src="' . $safe . '" type="application/pdf" class="dc-pdf">
            </div>
            <small class="text-muted d-block mt-2">คลิกที่ไฟล์ PDF เพื่อขยาย</small>
          </div>';
        } elseif (in_array($ext, ['mp4', 'webm', 'ogg'])) {
            // วิดีโอ: เล่นในหน้า ไม่ popup
            $html .= '<video class="dc-video" controls preload="metadata"><source src="' . $safe . '"></video>';
        } else {
            // Word/Excel/PPT: คลิกที่ไอคอนใหญ่เพื่อเปิด/ดาวน์โหลดในแท็บใหม่ (ไม่มีปุ่ม)
            $icon = 'bi-file-earmark-arrow-down';
            $label = 'ไฟล์เอกสาร';
            if (in_array($ext, ['doc', 'docx'])) { $icon = 'bi-file-earmark-word-fill'; $label = 'ไฟล์ Word'; }
            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) { $icon = 'bi-file-earmark-excel-fill'; $label = 'ไฟล์ Excel'; }
            elseif (in_array($ext, ['ppt', 'pptx'])) { $icon = 'bi-file-earmark-slides-fill'; $label = 'ไฟล์ PowerPoint'; }
            $html .= '<a href="' . $safe . '" target="_blank" class="dc-file-tile">'
                  .  '<i class="bi ' . $icon . '"></i>'
                  .  '<div class="dc-file-tile-label">' . $label . '<small>คลิกเพื่อเปิด/ดาวน์โหลด</small></div>'
                  .  '</a>';
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dept['name']) ?> - กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="department.css">
</head>
<body>

<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><i class="bi bi-telephone-fill"></i> สายด่วน: 044-316-999 ต่อ 4400 &nbsp;|&nbsp; <i class="bi bi-envelope-fill"></i> nursing@pkc.go.th</div>
        <a href="login.php" class="btn btn-sm btn-outline-light">เข้าสู่ระบบ</a>
    </div>
</div>

<div class="header-banner">
    <div class="container d-flex align-items-center">
        <a href="index.php" class="text-decoration-none">
            <div class="me-3">
                <img src="uploads/logo.png" alt="Logo" style="width: 65px; height: 70px; object-fit: contain;">
            </div>
        </a>
        <div>
            <h2 class="mb-0 fw-bold">กลุ่มงานการพยาบาล <?= htmlspecialchars($dept['name']) ?></h2>
            <div class="small opacity-90">โรงพยาบาลปากช่องนานา | Nursing Department, Pakchong Nana Hospital</div>
        </div>
    </div>
</div>

<!-- ===== เมนูแนวนอนของแผนก (5 หมวด แบบ Dropdown) ===== -->
<nav class="navbar navbar-expand-lg main-nav dept-nav p-0 shadow-sm">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#deptNavContent" aria-label="เปิดเมนู">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="deptNavContent">
            <div class="navbar-nav dept-nav-bar">
                                <?php foreach ($menuGroups as $gi => $group): ?>
                    <?php
                    $groupItems = [];
                    foreach ($group['sections'] as $sec) {
                        if (!empty($bySection[$sec])) {
                            foreach ($bySection[$sec] as $it) { $it['_sec_label'] = $sectionLabels[$sec] ?? $sec; $groupItems[] = $it; }
                        }
                    }
                    $groupCount = count($groupItems);
                    ?>
                    <div class="nav-item dropdown dept-nav-item">
                        <a class="nav-link dropdown-toggle dept-nav-link" href="#" id="grp<?= $gi ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi <?= $group['icon'] ?>"></i>
                            <span><?= htmlspecialchars($group['label']) ?></span>
                        </a>
                        <ul class="dropdown-menu dept-nav-dropdown" aria-labelledby="grp<?= $gi ?>">
                            <?php if (empty($groupItems)): ?>
                                <li><span class="dropdown-item-text dept-nav-empty">— ยังไม่มีข้อมูล —</span></li>
                            <?php else: ?>
                                <?php
                                $personnel_shown = false;
                                foreach ($groupItems as $it):
                                    // หมวดบุคลากร: รวมเป็นรายการเดียว "ทำเนียบบุคลากร" (ห้ามโชว์ชื่อจริง)
                                    if ($it['section'] === 'personnel'):
                                        if ($personnel_shown) continue;
                                        $personnel_shown = true;
                                ?>
                                    <li>
                                        <a class="dropdown-item dept-nav-leaf" href="news_detail.php?type=dept_personnel&amp;dept=<?= (int)$dept['id'] ?>">
                                            <i class="bi bi-people-fill"></i>
                                            <span>ทำเนียบบุคลากร</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a class="dropdown-item dept-nav-leaf" href="news_detail.php?type=dept&amp;id=<?= (int)$it['id'] ?>">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span><?= htmlspecialchars($it['title']) ?></span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</nav>

<div class="dept-page-header">
    <div class="container d-flex justify-content-end align-items-center">
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left-circle-fill"></i> กลับหน้าแรก</a>
    </div>
</div>
    <div class="footer-copyright text-center mt-4">
        <div class="container">© 2569 กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา — สงวนลิขสิทธิ์ทั้งหมด</div>
    </div>
</footer>

<!-- ===== Lightbox สำหรับดูรูปและ PDF ขนาดเต็มจอ ===== -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered p-0" style="background:rgba(0,0,0,0.92);">
        <div class="modal-content border-0" style="background:transparent;">
            <div class="modal-body d-flex align-items-center justify-content-center p-2 position-relative" style="min-height:100vh;">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="font-size:1.4rem; z-index:10;"></button>
                <img id="lightboxImg" src="" alt="" style="max-width:100%; max-height:95vh; object-fit:contain; border-radius:6px; display:none;">
                <embed id="lightboxPdf" src="" type="application/pdf" style="width:96vw; height:94vh; border-radius:6px; display:none;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const modalEl = document.getElementById('lightboxModal');
    const imgEl   = document.getElementById('lightboxImg');
    const pdfEl   = document.getElementById('lightboxPdf');
    let bsModal   = null;

    function openLightbox(type, src) {
        if (type === 'img') {
            imgEl.src = src;
            imgEl.style.display = 'block';
            pdfEl.style.display = 'none';
            pdfEl.src = '';
        } else if (type === 'pdf') {
            pdfEl.src = src;
            pdfEl.style.display = 'block';
            imgEl.style.display = 'none';
            imgEl.src = '';
        }
        if (!bsModal) bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }

    // ล้าง src ตอนปิด เพื่อหยุดโหลด PDF
    modalEl.addEventListener('hidden.bs.modal', function () {
        imgEl.src = '';
        pdfEl.src = '';
    });

    // คลิกรูป
    document.addEventListener('click', function (e) {
        const img = e.target.closest('.lightbox-trigger');
        if (img) {
            e.preventDefault();
            openLightbox('img', img.src);
            return;
        }
        const pdfBox = e.target.closest('.pdf-lightbox-trigger');
        if (pdfBox) {
            e.preventDefault();
            openLightbox('pdf', pdfBox.dataset.src);
            return;
        }
    });
})();
</script>
</body>
</html>