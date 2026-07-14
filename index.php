<?php
// 🔗 เรียกใช้งานไฟล์เชื่อมต่อฐานข้อมูล MySQL
require_once 'connect.php';

// ==================== [VISITOR COUNTER] ระบบสถิติผู้เข้าชมจริง ====================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$counter_file = 'visitor_counter.json';
$today_date = date('Y-m-d');

$counter_data = [
    'last_date' => $today_date,
    'today' => 0,
    'week' => 0,
    'total' => 0,
    'week_start_date' => date('Y-m-d', strtotime('monday this week'))
];

if (file_exists($counter_file)) {
    $json_content = file_get_contents($counter_file);
    $loaded_data = json_decode($json_content, true);
    if ($loaded_data) {
        $counter_data = array_merge($counter_data, $loaded_data);
    }
}

if ($counter_data['last_date'] !== $today_date) {
    $counter_data['last_date'] = $today_date;
    $counter_data['today'] = 0;
}

$current_week_start = date('Y-m-d', strtotime('monday this week'));
if ($counter_data['week_start_date'] !== $current_week_start) {
    $counter_data['week_start_date'] = $current_week_start;
    $counter_data['week'] = 0;
}

if (!isset($_SESSION['has_visited_hospital'])) {
    $_SESSION['has_visited_hospital'] = true;
    $counter_data['today']++;
    $counter_data['week']++;
    $counter_data['total']++;
    file_put_contents($counter_file, json_encode($counter_data, JSON_PRETTY_PRINT), LOCK_EX);
}
// =================================================================================

// ฟังก์ชันแปลงรูปแบบวันที่ ค.ศ. เป็น พ.ศ. สไตล์ย่อ
function dateToThaiShort($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'ไม่ระบุ';
    $time = strtotime($dateStr);
    if (!$time) return htmlspecialchars($dateStr);
    $d = date('j', $time);
    $m = date('n', $time);
    $y = (date('Y', $time) + 543) % 100;
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return "$d {$months[$m]} $y";
}

// ฟังก์ชันแปลงรูปแบบวันที่ ค.ศ. เป็น พ.ศ. แบบเต็ม
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

// 🔄 QUERY ดึงข้อมูลจาก MySQL
$stmt_news = $conn->query("SELECT * FROM news ORDER BY id DESC");
$news_list = $stmt_news->fetchAll(PDO::FETCH_ASSOC);

$stmt_depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
$dept_list = $stmt_depts->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล Banner จาก MySQL (เฉพาะ Banner หน้าแรก ไม่รวม Banner ประจำแผนก)
$stmt_banners = $conn->query("SELECT * FROM banners WHERE is_active = 1 AND department_id IS NULL ORDER BY sort_order ASC, id ASC");
$banner_list = $stmt_banners->fetchAll(PDO::FETCH_ASSOC);

// รูปรวม + รูปกิจกรรม หน้าแรก (จัดการผ่าน admin.php > หน้าแรก > รูปภาพหน้าแรก)
$stmt_group_photo = $conn->query("SELECT * FROM department_contents WHERE department_id IS NULL AND section = 'idx_group_photo' ORDER BY sort_order ASC, id ASC LIMIT 1");
$group_photo = $stmt_group_photo->fetch(PDO::FETCH_ASSOC);

$stmt_activity_photos = $conn->query("SELECT * FROM department_contents WHERE department_id IS NULL AND section = 'idx_activity_photos' ORDER BY sort_order ASC, id ASC");
$activity_photos = $stmt_activity_photos->fetchAll(PDO::FETCH_ASSOC);

function firstImageFile($fileData) {
    $decoded = json_decode($fileData, true);
    $files = is_array($decoded) ? $decoded : (empty($fileData) ? [] : [$fileData]);
    foreach ($files as $f) {
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return $f;
    }
    return '';
}

// Fallback ถ้าไม่มี Banner ในฐานข้อมูล
$nature_imgs = [
    "https://images.unsplash.com/photo-1501854140801-50d01698950b?q=80&w=1920",
    "https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?q=80&w=1920",
    "https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?q=80&w=1920",
    "https://images.unsplash.com/photo-1441974231531-c6227db76b6e?q=80&w=1920"
];
$fallback_banners = [
    ['title' => 'พัฒนาคุณภาพอย่างต่อเนื่อง',              'subtitle' => 'กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา เพื่อสุขภาวะที่ดีของประชาชน', 'image_name' => '', 'link_url' => ''],
    ['title' => 'บริการด้วยใจ ปลอดภัยได้มาตรฐาน',         'subtitle' => 'กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา เพื่อสุขภาวะที่ดีของประชาชน', 'image_name' => '', 'link_url' => ''],
    ['title' => 'ยกระดับการบริบาลผู้ป่วยอย่างอบอุ่น',     'subtitle' => 'กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา เพื่อสุขภาวะที่ดีของประชาชน', 'image_name' => '', 'link_url' => ''],
    ['title' => 'ก้าวสู่ความเป็นเลิศด้านการพยาบาลชุมชน', 'subtitle' => 'กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา เพื่อสุขภาวะที่ดีของประชาชน', 'image_name' => '', 'link_url' => ''],
];
$slides = !empty($banner_list) ? $banner_list : $fallback_banners;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="index.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        body.home-page {
            padding-top: 180px;
        }
        #siteHeader {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 2147483647;
            width: 100%;
            display: block;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        }
    </style>
</head>
<body class="home-page">
<div id="siteHeader">
<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap">
        <div><i class="bi bi-telephone-fill"></i> สายด่วน: 044-316-999 ต่อ 4400 &nbsp;|&nbsp; <i class="bi bi-envelope-fill"></i> nursing@pkc.go.th</div>
        <div class="d-flex align-items-center gap-2">
            <span id="liveClock">กำลังโหลดเวลา...</span>
            <a href="login.php" class="btn btn-sm btn-outline-light">เข้าสู่ระบบ</a>
        </div>
    </div>
</div>

<div class="header-banner">
    <div class="container d-flex align-items-center">
        <div class="me-3">
            <img src="uploads/logo.png" alt="Logo" style="width: 65px; height: 70px; object-fit: contain;">
        </div>
        <div>
            <h2 class="mb-0 fw-bold">กลุ่มงานการพยาบาล</h2>
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
                <a class="nav-link active" href="#"><i class="bi bi-house-door-fill"></i> หน้าแรก</a>

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
                        <li><a class="dropdown-item" href="executives.php"><i class="bi bi-people-fill me-2"></i> ทำเนียบหัวหน้ากลุ่มงาน</a></li>
                        <li><a class="dropdown-item" href="ward_heads.php"><i class="bi bi-people-fill me-2"></i> ทำเนียบหัวหน้างาน</a></li>
                        <li><a class="dropdown-item" href="personnel_gallery.php"><i class="bi bi-people-fill me-2"></i> รูปบุคลากร</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-briefcase-fill me-1"></i>งานบริหาร</a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="org_structure.php"><i class="bi bi-diagram-3 me-2"></i>โครงสร้างบริหาร</a></li>
                        <li><a class="dropdown-item" href="regulations.php"><i class="bi bi-book me-2"></i>คู่มือบริหาร</a></li>
                        <li><a class="dropdown-item" href="plans_projects.php"><i class="bi bi-bullseye me-2"></i>แผนยุทธศาสตร์การพยาบาล</a></li>
                        <li><a class="dropdown-item" href="staff_dev_plan.php"><i class="bi bi-person-workspace me-2"></i>แผนพัฒนาบุคลากร</a></li>
                        <li><a class="dropdown-item" href="risk_management.php"><i class="bi bi-shield-check me-2"></i>บริหารความเสี่ยง</a></li>
                        <li><a class="dropdown-item" href="nursing_ethics.php"><i class="bi bi-heart-pulse me-2"></i>จริยธรรมทางการพยาบาล</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="serviceDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-clipboard2-check-fill me-1"></i>งานบริการ</a>
                    <ul class="dropdown-menu" aria-labelledby="serviceDropdown">
                        <li><a class="dropdown-item" href="supervision_results.php"><i class="bi bi-clipboard-check me-2"></i> ผลการนิเทศ</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-mortarboard-fill me-1"></i>งานวิชาการ</a>
                    <ul class="dropdown-menu" aria-labelledby="academicDropdown">
                        <li><a class="dropdown-item" href="dataset.php"><i class="bi bi-database me-2"></i> Data set</a></li>
                        <li><a class="dropdown-item" href="downloads.php"><i class="bi bi-file-earmark-arrow-down me-2"></i> เอกสารดาวน์โหลด</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="qualityDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                     <i class="bi bi-star-fill me-1"></i>คุณภาพทางการพยาบาล</a>
                    <ul class="dropdown-menu" aria-labelledby="qualityDropdown">
                        <li><a class="dropdown-item" href="kpi.php"><i class="bi bi-graph-up-arrow me-2"></i> ตัวชี้วัดคุณภาพ</a></li>
                        <li><a class="dropdown-item" href="service_profile.php"><i class="bi bi-file-medical me-2"></i> Service profile</a></li>
                        <li><a class="dropdown-item" href="cpg.php"><i class="bi bi-journal-medical me-2"></i> CNPG</a></li>
                        <li><a class="dropdown-item" href="wi.php"><i class="bi bi-file-earmark-text me-2"></i> WI</a></li>
                        <li><a class="dropdown-item" href="research.php"><i class="bi bi-search-heart me-2"></i> วิจัย</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="informationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                     <i class="bi bi-lightbulb-fill me-1"></i>งานสารสนเทศ</a>
                    <ul class="dropdown-menu" aria-labelledby="informationDropdown">
                        <li><a class="dropdown-item" href="staffing.php"><i class="bi bi-people-fill me-2"></i> อัตรากำลัง</a></li>
                        <li><a class="dropdown-item" href="workload.php"><i class="bi bi-bar-chart-line me-2"></i> ภาระงาน</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="newsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill me-1"></i>ข่าวประชาสัมพันธ์</a>
                    <ul class="dropdown-menu" aria-labelledby="newsDropdown">
                        <li><a class="dropdown-item" href="all_news.php"><i class="bi bi-newspaper me-2"></i> ข่าวสาร</a></li>
                        <li><a class="dropdown-item" href="meeting_reports.php"><i class="bi bi-file-earmark-text me-2"></i> รายงานการประชุม</a></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</nav>
</div>


<!-- ==================== HERO CAROUSEL (ดึงจากตาราง banners) ==================== -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-indicators">
        <?php foreach($slides as $i => $slide): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>" <?= $i === 0 ? 'class="active"' : '' ?>></button>
        <?php endforeach; ?>
    </div>

    <div class="carousel-inner">
        <?php foreach($slides as $i => $slide):
            $isActive = ($i === 0) ? 'active' : '';

            // กำหนด background image
            if (!empty($slide['image_name'])) {
                $bgUrl  = 'uploads/' . htmlspecialchars($slide['image_name']);
                $bgStyle = "background-image: url('{$bgUrl}');";
            } else {
                $bgStyle = "background-image: url('{$nature_imgs[$i % 4]}');";
            }

            $slide_link  = !empty($slide['link_url'])  ? htmlspecialchars($slide['link_url'])  : '';
            $slide_sub   = htmlspecialchars($slide['subtitle'] ?? '');
        ?>
        <div class="carousel-item <?= $isActive ?>" style="<?= $bgStyle ?>">
            <div class="carousel-overlay"></div>
            <div class="carousel-caption-custom">
                <?php if(!empty($slide_sub)): ?>
                    <p class="mb-4" style="font-size: 16px; opacity: 0.95;"><?= $slide_sub ?></p>
                <?php endif; ?>
                <?php if(!empty($slide_link)): ?>
                    <a href="<?= $slide_link ?>" target="_blank" class="btn-readmore">อ่านเพิ่มเติม</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>
<!-- ========================================================================== -->

<div class="vision-bar text-center">
    <div class="container">
        " วิสัยทัศน์: กลุ่มงานการพยาบาลที่มีคุณภาพ มาตรฐาน เป็นที่ไว้วางใจของผู้รับบริการ ภายใต้หลักธรรมาภิบาล เพื่อสุขภาวะที่ดีของประชาชน "
    </div>
</div>

<!-- ==================== รูปรวม ==================== -->
<?php if (!empty($group_photo) && firstImageFile($group_photo['file_name'] ?? '')): ?>
<div class="container my-4">
    <div class="block-header mb-3">
        <span class="fs-5 fw-bold"><i class="bi bi-image-fill"></i> ภาพรวมกลุ่มงาน</span>
    </div>
    <div class="text-center">
        <img src="uploads/<?= htmlspecialchars(firstImageFile($group_photo['file_name'])) ?>" alt="<?= htmlspecialchars($group_photo['title'] ?? 'ภาพรวมกลุ่มงาน') ?>" class="img-fluid rounded shadow-sm w-100" style="max-height: 480px; object-fit: cover;">
        <?php if (!empty($group_photo['content'])): ?>
            <p class="text-muted small mt-2"><?= htmlspecialchars($group_photo['content']) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<!-- ========================================================================== -->

<!-- ==================== รูปกิจกรรม (คลิกเลื่อนดู) ==================== -->
<?php if (!empty($activity_photos)): ?>
<div class="container my-4">
    <div class="block-header mb-0">
        <span class="fs-5 fw-bold"><i class="bi bi-images"></i> รูปกิจกรรม</span>
    </div>

    <div class="news-carousel-wrap position-relative">
        <div class="news-carousel" id="activityCarousel">
            <?php foreach ($activity_photos as $activity):
                $activity_img = firstImageFile($activity['file_name'] ?? '');
            ?>
            <div class="news-card-item">
                <a href="<?= $activity_img ? 'uploads/' . htmlspecialchars($activity_img) : '#' ?>" target="_blank" class="news-poster-card" title="<?= htmlspecialchars($activity['title']) ?>">
                    <?php if ($activity_img): ?>
                        <img src="uploads/<?= htmlspecialchars($activity_img) ?>" alt="<?= htmlspecialchars($activity['title']) ?>" class="news-poster-img" loading="lazy">
                    <?php else: ?>
                        <div class="news-poster-placeholder">
                            <i class="bi bi-images"></i>
                            <span>ไม่มีรูปภาพ</span>
                        </div>
                    <?php endif; ?>
                    <div class="news-poster-overlay">
                        <h3 class="news-poster-title"><?= htmlspecialchars($activity['title']) ?></h3>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ปุ่มเลื่อน -->
        <button class="news-nav-btn news-nav-prev" id="activityPrev" aria-label="ก่อนหน้า"><i class="bi bi-chevron-left"></i></button>
        <button class="news-nav-btn news-nav-next" id="activityNext" aria-label="ถัดไป"><i class="bi bi-chevron-right"></i></button>
    </div>
</div>
<?php endif; ?>
<!-- ========================================================================== -->

<!-- ==================== ข่าวประชาสัมพันธ์ (Carousel) ==================== -->
<?php if (!empty($news_list)): ?>
<div class="container my-4">
    <div class="block-header mb-0">
        <span class="fs-5 fw-bold"><i class="bi bi-megaphone-fill"></i> ข่าวประชาสัมพันธ์</span>
        <a href="all_news.php" class="text-white text-decoration-none small">ดูทั้งหมด <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="news-carousel-wrap position-relative">
        <div class="news-carousel" id="newsCarousel">
            <?php foreach ($news_list as $idx => $news):
                // แยกไฟล์แนบ
                $raw_files = $news['image_name'] ?? '';
                $files = array_filter(array_map('trim', explode(',', $raw_files)));
                $first_img = '';
                foreach ($files as $f) {
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) { $first_img = $f; break; }
                }
            ?>
            <div class="news-card-item">
                <a href="news_detail.php?id=<?= (int)$news['id'] ?>" class="news-poster-card" title="<?= htmlspecialchars($news['title']) ?>">
                    <?php if ($first_img): ?>
                        <img src="uploads/<?= htmlspecialchars($first_img) ?>" alt="<?= htmlspecialchars($news['title']) ?>" class="news-poster-img" loading="lazy">
                    <?php else: ?>
                        <div class="news-poster-placeholder">
                            <i class="bi bi-newspaper"></i>
                            <span>ไม่มีรูปภาพ</span>
                        </div>
                    <?php endif; ?>
                    <div class="news-poster-overlay">
                        <h3 class="news-poster-title"><?= htmlspecialchars($news['title']) ?></h3>
                        <span class="news-poster-date"><i class="bi bi-calendar3"></i> <?= dateToThaiFull($news['created_at']) ?></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ปุ่มเลื่อน -->
        <button class="news-nav-btn news-nav-prev" id="newsPrev" aria-label="ก่อนหน้า"><i class="bi bi-chevron-left"></i></button>
        <button class="news-nav-btn news-nav-next" id="newsNext" aria-label="ถัดไป"><i class="bi bi-chevron-right"></i></button>
    </div>
</div>
<?php endif; ?>
<!-- ========================================================================== -->

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <div class="block-header mb-3">
                <span class="fs-5 fw-bold"><i class="bi bi-calendar-event-fill"></i>ตารางปฏิบัติงาน</span>
            </div>
            <!-- กล่องสำหรับแสดงผล AppSheet -->
            <div class="border rounded bg-white shadow-sm overflow-hidden mb-4" style="height: 600px;">
                <iframe 
                    src="https://calendar.google.com/calendar/embed?src=c_9ac506c7372c44aec4f67376f73f952e35c158cf08222472e750f8ce147b33d7%40group.calendar.google.com&ctz=Asia%2FBangkok" 
                    width="100%" 
                    height="100%" 
                    style="border: none;" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-8 col-md-12">
            <div class="block-header">
                <span><i class="bi bi-grid-fill"></i> ลิงก์ที่เกี่ยวข้อง</span>
            </div>
            <div class="link-grid-box mb-4">
                <div class="row g-0">
                    <div class="col-6 border-end">
                        <div class="link-grid-item"><i class="bi bi-shield-check text-warning me-2"></i><a href="https://www.tnmc.or.th/" target="_blank" rel="noopener noreferrer">สภาการพยาบาล</a></div>
                        <div class="link-grid-item"><i class="bi bi-mortarboard-fill text-warning me-2"></i><a href="https://cpg.dms.go.th/" target="_blank" rel="noopener noreferrer">ระบบสืบค้น CPG</a></div>
                        <div class="link-grid-item"><i class="bi bi-tablet-landscape text-warning me-2"></i><a href="https://www.ckdoctor.com/?gad_source=1&gad_campaignid=21980229015&gbraid=0AAAAAD1H3YMu4xNuCAv4r1kzu7EDC09jH&gclid=Cj0KCQjwr4jSBhCSARIsAOX1E-IiNEXscz-aLco7ZjmCCFGS4J8SUO5D9ZsC95fT6HW7KfrGGNjY8HgaAt8tEALw_wcB" target="_blank" rel="noopener noreferrer">ระบบ HIS</a></div>
                    </div>
                    <div class="col-6">
                        <div class="link-grid-item"><i class="bi bi-heart-pulse-fill text-danger me-2"></i><a href="https://www.dms.go.th/?StartWeb=1" target="_blank" rel="noopener noreferrer">กรมการแพทย์</a></div>
                        <div class="link-grid-item"><i class="bi bi-bar-chart-line-fill text-warning me-2"></i><a href="https://spd.moph.go.th/kpi-template-%E0%B8%95%E0%B8%B1%E0%B8%A7%E0%B8%8A%E0%B8%B5%E0%B9%89%E0%B8%A7%E0%B8%B1%E0%B8%94%E0%B8%81%E0%B8%A3%E0%B8%B0%E0%B8%97%E0%B8%A3%E0%B8%A7%E0%B8%87%E0%B8%AA%E0%B8%B2%E0%B8%98%E0%B8%B2/" target="_blank" rel="noopener noreferrer">รายงาน KPI</a></div>
                        <div class="link-grid-item"><i class="bi bi-file-earmark-medical-fill text-warning me-2"></i><a href="https://intranet.dla.go.th/km/km.do" target="_blank" rel="noopener noreferrer">คลังความรู้ KM</a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-11">
            <div class="block-header"><span><i class="bi bi-share-fill"></i> ติดตามเรา</span></div>
            <div class="border p-3 bg-white text-center d-flex flex-wrap justify-content-center gap-3 mb-4" style="border-top:none;">
                <a href="#" class="btn btn-sm btn-outline-primary rounded"><i class="bi bi-facebook"></i> Facebook</a>
                <a href="#" class="btn btn-sm btn-outline-danger rounded"><i class="bi bi-youtube"></i> YouTube</a>
                <a href="#" class="btn btn-sm btn-outline-success rounded"><i class="bi bi-line"></i> Line OA</a>
            </div>

            <div class="block-header"><span><i class="bi bi-bar-chart-fill"></i> สถิติผู้เข้าชม</span></div>
            <div class="border p-3 bg-white mb-4" style="border-top:none; font-size: 14px;">
                <div class="d-flex justify-content-between mb-1"><span>วันนี้</span><span class="fw-bold text-danger"><?= number_format($counter_data['today']) ?></span></div>
                <div class="d-flex justify-content-between mb-1"><span>สัปดาห์นี้</span><span class="fw-bold text-danger"><?= number_format($counter_data['week']) ?></span></div>
                <div class="d-flex justify-content-between"><span>รวมทั้งหมด</span><span class="fw-bold text-danger"><?= number_format($counter_data['total']) ?></span></div>
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
                    <li><i class="bi bi-chevron-right"></i> <a href="https://moph.go.th/" target="_blank" rel="noopener noreferrer">กระทรวงสาธารณสุข</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="https://www.tnmc.or.th/" target="_blank" rel="noopener noreferrer">สภาการพยาบาล</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="https://www.dms.go.th/?StartWeb=1" target="_blank" rel="noopener noreferrer">กรมการแพทย์</a></li>
                    <li><i class="bi bi-chevron-right"></i> <a href="https://www.ha.or.th/TH/Home/%E0%B8%AB%E0%B8%99%E0%B9%89%E0%B8%B2%E0%B8%AB%E0%B8%A5%E0%B8%B1%E0%B8%81" target="_blank" rel="noopener noreferrer">สรพ. (HA)</a></li>
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
<script>
function updateThaiLiveClock() {
    const now = new Date();
    const days = ["อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์"];
    const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    const dayName   = days[now.getDay()];
    const dateNum   = now.getDate();
    const monthName = months[now.getMonth()];
    const thaiYear  = now.getFullYear() + 543;
    const hours     = String(now.getHours()).padStart(2, '0');
    const minutes   = String(now.getMinutes()).padStart(2, '0');
    const seconds   = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('liveClock').innerHTML =
        `วัน${dayName}ที่ ${dateNum} ${monthName} ${thaiYear} เวลา ${hours}:${minutes}:${seconds}`;
}
updateThaiLiveClock();
setInterval(updateThaiLiveClock, 1000);

// Scroll to top
window.addEventListener('scroll', function() {
    document.getElementById('scrollTopBtn').classList.toggle('show', window.scrollY > 300);
}, { passive: true });

// Carousel scroll (ข่าวประชาสัมพันธ์ + รูปกิจกรรม — คลิกเลื่อนดู)
document.addEventListener('DOMContentLoaded', function() {
    function initScrollCarousel(carouselId, prevId, nextId) {
        const carousel = document.getElementById(carouselId);
        const prevBtn  = document.getElementById(prevId);
        const nextBtn  = document.getElementById(nextId);
        if (!carousel || !prevBtn || !nextBtn) return;

        function getScrollAmount() {
            const card = carousel.querySelector('.news-card-item');
            if (!card) return 320;
            return card.offsetWidth + 20;
        }

        prevBtn.addEventListener('click', function() {
            carousel.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' });
        });
        nextBtn.addEventListener('click', function() {
            carousel.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
        });
    }

    initScrollCarousel('newsCarousel', 'newsPrev', 'newsNext');
    initScrollCarousel('activityCarousel', 'activityPrev', 'activityNext');
});

</script>

<button id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="กลับด้านบน">
    <i class="bi bi-chevron-up"></i>
</button>
</body>
</html>