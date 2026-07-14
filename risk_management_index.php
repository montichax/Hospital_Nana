<?php
// =====================================================================
//  กุมารเวช — หน้าหอผู้ป่วย/หน่วยงาน (เวอร์ชันปรับขนาดมีเดียและเมนูตามหน้าเดโมจริง)
//  ดึงข้อมูลจากตาราง department_contents โดยอ้างอิง department_id = 1
// =====================================================================
require_once 'connect.php';
$stmt_depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
$dept_list = $stmt_depts->fetchAll(PDO::FETCH_ASSOC);

$DEPT_ID = isset($_GET['id']) ? (int)$_GET['id'] : null;

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
if ($DEPT_ID !== null) {
    $stmt = $conn->prepare("SELECT * FROM departments WHERE id = :id");
    $stmt->execute([':id' => $DEPT_ID]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $dept = null;
}

// ---------- Banner ของแผนก ----------
$stmt = $conn->prepare("
    SELECT *
    FROM banners
    WHERE department_id = :dept_id
      AND is_active = 1
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([
    ':dept_id' => $DEPT_ID
]);

$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $html .= '<video class="dc-video shadow-sm" controls preload="metadata"><source src="' . $safe . '"></video>';
            
        } else {
            $icon = 'bi-file-earmark-arrow-down'; $label = 'ไฟล์เอกสาร';
            if (in_array($ext, ['doc', 'docx'])) { $icon = 'bi-file-earmark-word-fill'; $label = 'ไฟล์ Word'; }
            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) { $icon = 'bi-file-earmark-excel-fill'; $label = 'ไฟล์ Excel'; }
            elseif (in_array($ext, ['ppt', 'pptx'])) { $icon = 'bi-file-earmark-slides-fill'; $label = 'ไฟล์ PowerPoint'; }
            $html .= '<a href="' . $safe . '" target="_blank" class="dc-file-tile mx-auto">
                        <i class="bi ' . $icon . '"></i>
                        <div class="dc-file-tile-label">' . $label . '<small>คลิกเพื่อเปิด/ดาวน์โหลด</small></div>
                      </a>';
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
    <title>บริหารจัดการความเสี่ยง - กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="news_detail.css">
    <link rel="stylesheet" href="all_news.css">
    <link rel="stylesheet" href="department.css">
    <link rel="stylesheet" href="index.css">
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
        <div class="me-3">
            <img src="uploads/logo.png" alt="Logo" style="width: 65px; height: 70px; object-fit: contain;">
        </div>
        <div>
            <h2 class="mb-0 fw-bold">กลุ่มงานการพยาบาล <span class="fw-normal opacity-90"><?= $dept ? htmlspecialchars($dept['name']) : '' ?></span></h2>
            <div class="small opacity-90">โรงพยาบาลปากช่องนานา | Nursing Department, Pakchong Nana Hospital</div>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg main-nav p-0 shadow-sm" id="mainNav">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="เปิดเมนู">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="navbar-nav">
                <!-- <a class="nav-link active" href="#"><i class="bi bi-house-door-fill"></i> หน้าแรก</a> -->

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="deptDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-hospital-fill me-1"></i>หอผู้ป่วย/หน่วยงาน
                    </a>
                    <ul class="dropdown-menu dept-dropdown-menu" aria-labelledby="deptDropdown">
                        <?php if(empty($dept_list)): ?>
                            <li><a class="dropdown-item" href="#">ไม่พบข้อมูล</a></li>
                        <?php else: ?>
                            <?php foreach($dept_list as $dept):
                                $shortName = str_replace(['งาน', 'หน่วยงาน'], '', $dept['name']);
                            ?>
                            <li>
                                <?php $deptUrl = !empty($dept['link_url']) ? $dept['link_url'] : 'department.php?id=' . (int)$dept['id']; ?>
                                <a class="dropdown-item" href="<?= htmlspecialchars($deptUrl) ?>">
                                    <?= htmlspecialchars(trim($shortName)) ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-building me-1"></i>เกี่ยวกับกลุ่มงาน</a>
                    <ul class="dropdown-menu" aria-labelledby="aboutDropdown">
                        <li><a class="dropdown-item" href="vision_mission.php"><i class="bi bi-eye-fill me-2"></i> วิสัยทัศน์ / พันธกิจ</a></li>
                        <li><a class="dropdown-item" href="nurse_roster.php"><i class="bi bi-people-fill me-2"></i> ทำเนียบหัวหน้าพยาบาล</a></li>
                        <li><a class="dropdown-item" href="executives_index.php"><i class="bi bi-person-badge-fill me-2"></i> ทำเนียบหัวหน้ากลุ่มงาน</a></li>
                        <li><a class="dropdown-item" href="ward_heads_index.php"><i class="bi bi-person-lines-fill me-2"></i> ทำเนียบหัวหน้างาน</a></li>
                        <li><a class="dropdown-item" href="personnel_gallery_index.php"><i class="bi bi-people-fill me-2 "></i> บุคลากร</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-briefcase-fill me-1"></i>งานบริหาร</a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="org_structure_index.php"><i class="bi bi-diagram-3-fill me-2"></i> โครงสร้างบริหาร</a></li>
                        <li><a class="dropdown-item" href="regulations.php"><i class="bi bi-journal-bookmark-fill me-2"></i> คู่มือบริหาร</a></li>
                        <li><a class="dropdown-item" href="plans_projects.php"><i class="bi bi-clipboard-data-fill me-2"></i> แผนยุทธศาสตร์การพยาบาล</a></li>
                        <li><a class="dropdown-item" href="staff_dev_plan.php"><i class="bi bi-graph-up-arrow me-2"></i> แผนพัฒนาบุคลากร</a></li>
                        <li><a class="dropdown-item" href="risk_management_index.php"><i class="bi bi-shield-exclamation me-2"></i> บริหารความเสี่ยง</a></li>
                        <li><a class="dropdown-item" href="nursing_ethics_index.php"><i class="bi bi-patch-check-fill me-2"></i> จริยธรรมการพยาบาล</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-heart-pulse-fill me-1"></i>งานบริการ</a>
                    <ul class="dropdown-menu" aria-labelledby="academicDropdown">
                        <li><a class="dropdown-item" href="supervision_results_index.php"><i class="bi bi-clipboard2-check-fill me-2"></i> ผลการนิเทศ</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-mortarboard-fill me-1"></i>งานวิชาการ</a>
                    <ul class="dropdown-menu" aria-labelledby="academicDropdown">
                        <li><a class="dropdown-item" href="dataset_index.php"><i class="bi bi-database-fill me-2"></i> Data set</a></li>
                        <li><a class="dropdown-item" href="downloads.php"><i class="bi bi-file-earmark-arrow-down-fill me-2"></i> เอกสารดาวน์โหลด</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="qualityDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                     <i class="bi bi-star-fill me-1"></i>คุณภาพการพยาบาล</a>
                    <ul class="dropdown-menu" aria-labelledby="qualityDropdown">
                        <li><a class="dropdown-item" href="kpi_index.php"><i class="bi bi-bar-chart-fill me-2"></i> ตัวชี้วัดคุณภาพ</a></li>
                        <li><a class="dropdown-item" href="service_profile_index.php"><i class="bi bi-file-earmark-person-fill me-2"></i> Service profile</a></li>
                        <li><a class="dropdown-item" href="cpg_index.php"><i class="bi bi-clipboard2-pulse-fill me-2"></i> CNPG</a></li>
                        <li><a class="dropdown-item" href="wi_index.php"><i class="bi bi-file-earmark-text-fill me-2"></i> WI</a></li>
                        <li><a class="dropdown-item" href="research_index.php"><i class="bi bi-search me-2"></i> วิจัย</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="informationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                     <i class="bi bi-lightbulb-fill me-1"></i>งานสารสนเทศ</a>
                    <ul class="dropdown-menu" aria-labelledby="informationDropdown">
                        <li><a class="dropdown-item" href="staffing_index.php"><i class="bi bi-people-fill me-2"></i> อัตรากำลัง</a></li>
                        <li><a class="dropdown-item" href="workload_index.php"><i class="bi bi-bar-chart-line me-2"></i> ภาระงาน</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="newsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill me-1"></i>ข่าวประชาสัมพันธ์</a>
                    <ul class="dropdown-menu" aria-labelledby="newsDropdown">
                        <li><a class="dropdown-item" href="all_news.php"><i class="bi bi-megaphone-fill me-2"></i> ข่าวสารทั้งหมด</a></li>
                        <li><a class="dropdown-item" href="meeting_reports_index.php"><i class="bi bi-journal-text me-2"></i> รายงานการประชุม</a></li>
                    </ul>
                </div>
                <a href="index.php" class="btn-back nav-btn-back ms-auto"><i class="bi bi-arrow-left-circle-fill"></i> กลับหน้าหลัก</a>

            </div>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="bi bi-eye-fill me-2"></i>บริหารจัดการความเสี่ยง</h1>
    </div>
</div>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8" id="generalContent">
            <div class="empty-state">
        <i class="bi bi-cone-striped"></i>
        <p>หน้า "บริหารจัดการความเสี่ยง" อยู่ระหว่างการจัดทำ</p>
        <p class="small">ขออภัยในความไม่สะดวก กรุณากลับมาตรวจสอบใหม่อีกครั้งในภายหลัง</p>
    </div>
        </div>
    </div>
</div>

<footer class="main-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5><i class="bi bi-building"></i> กลุ่มงานการพยาบาล</h5>
                <p class="small opacity-80 mt-2">โรงพยาบาลปากช่องนานา<br>มุ่งมั่นในการพัฒนาคุณภาพการพยาบาล เพื่อผู้ป่วยและผู้รับบริการทุกคน</p>
            </div>
            <div class="col-md-4">
                <h5><i class="bi bi-geo-alt-fill"></i> ติดต่อเรา</h5>
                <ul class="small opacity-80">
                    <li><i class="bi bi-map"></i> 123 ถ.มิตรภาพ อ.ปากช่อง จ.นครราชสีมา 30130</li>
                    <li><i class="bi bi-telephone"></i> 044-316-999 ต่อ 4400</li>
                    <li><i class="bi bi-envelope"></i> nursing@pkc.go.th</li>
                    <li><i class="bi bi-clock"></i> เปิดให้บริการ 24 ชั่วโมง</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5><i class="bi bi-link-45deg"></i> ลิงก์ที่เกี่ยวข้อง</h5>
                <ul class="small opacity-80">
                    <li><i class="bi bi-chevron-right"></i> <a href="#">กระทรวงสาธารณสุข</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="#">สภาการพยาบาล</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="#">กรมการแพทย์</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="#">สรพ. (HA)</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-copyright text-center mt-4">
        <div class="container">
            © 2569 กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา — สงวนลิขสิทธิ์ทั้งหมด
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.CONTENT_SECTION = 'vision_mission';</script>
<script src="assets/js/api-config.js"></script>
<script src="assets/js/dept-context.js"></script>
<script src="assets/js/general-content.js"></script>
</body>
</html>
