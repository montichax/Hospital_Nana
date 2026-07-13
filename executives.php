<?php
// หน้าอยู่ระหว่างการจัดทำ — ทำเนียบหัวหน้ากลุ่มงาน
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทำเนียบหัวหน้ากลุ่มงาน - กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="news_detail.css">
    <link rel="stylesheet" href="all_news.css">
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
                <a class="nav-link" href="<?= basename($_SERVER['PHP_SELF']) ?>"><i class="bi bi-house-door-fill"></i> หน้าแรก</a>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building me-1"></i>เกี่ยวกับกลุ่มงาน
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="aboutDropdown">
                        <li><a class="dropdown-item" href="executives.php"><i class="bi bi-person-badge-fill me-2"></i> ทำเนียบหัวหน้ากลุ่มงาน</a></li>
                        <li><a class="dropdown-item" href="ward_heads.php"><i class="bi bi-person-lines-fill me-2"></i> ทำเนียบหัวหน้างาน</a></li>
                        <li><a class="dropdown-item" href="personnel_gallery.php"><i class="bi bi-people-fill me-2 "></i>รูปบุคลากร</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-briefcase-fill me-1"></i>งานบริหาร
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="org_structure.php"><i class="bi bi-diagram-3-fill me-2"></i> โครงสร้างบริหาร</a></li>
                        <li><a class="dropdown-item" href="risk_management.php"><i class="bi bi-shield-exclamation me-2"></i> บริหารความเสี่ยง</a></li>
                        <li><a class="dropdown-item" href="nursing_ethics.php"><i class="bi bi-patch-check-fill me-2"></i> จริยธรรมการพยาบาล</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-mortarboard-fill me-1"></i>งานวิชาการ
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="academicDropdown">
                        <li><a class="dropdown-item" href="dataset.php"><i class="bi bi-database-fill me-2"></i> Data set</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="qualityDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-star-fill me-1"></i>คุณภาพการพยาบาล
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="qualityDropdown">
                        <li><a class="dropdown-item" href="kpi.php"><i class="bi bi-bar-chart-fill me-2"></i> ตัวชี้วัดคุณภาพ</a></li>
                        <li><a class="dropdown-item" href="service_profile.php"><i class="bi bi-file-earmark-person-fill me-2"></i> Service profile</a></li>
                        <li><a class="dropdown-item" href="cpg.php"><i class="bi bi-clipboard2-pulse-fill me-2"></i> CNPG</a></li>
                        <li><a class="dropdown-item" href="wi.php"><i class="bi bi-file-earmark-text-fill me-2"></i> WI</a></li>
                        <li><a class="dropdown-item" href="research.php"><i class="bi bi-search me-2"></i> วิจัย</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="infoDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-lightbulb-fill me-1"></i>งานสารสนเทศ
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="infoDropdown">
                        <li><a class="dropdown-item" href="staffing.php"><i class="bi bi-diagram-2-fill me-2"></i> อัตรากำลัง</a></li>
                        <li><a class="dropdown-item" href="workload.php"><i class="bi bi-speedometer2 me-2"></i> ภาระงาน</a></li>
                    </ul>
                </div>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="newsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell-fill me-1"></i>ข่าวสารประชาสัมพันธ์
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="newsDropdown">
                        <li><a class="dropdown-item active" href="<?= basename($_SERVER['PHP_SELF']) ?>"><i class="bi bi-megaphone-fill me-2"></i> ข่าวสารของแผนก</a></li>
                        <li><a class="dropdown-item" href="meeting_reports.php"><i class="bi bi-journal-text me-2"></i> รายงานการประชุม</a></li>
                    </ul>
                </div>
                <a href="index.php" class="btn-back nav-btn-back ms-auto"><i class="bi bi-arrow-left-circle-fill"></i> กลับหน้าหลัก</a>
            </div>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="bi bi-person-badge-fill me-2"></i>ทำเนียบหัวหน้ากลุ่มงาน</h1>
    </div>
</div>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8" id="generalContent">
            <div class="empty-state">
        <i class="bi bi-cone-striped"></i>
        <p>หน้า "ทำเนียบหัวหน้ากลุ่มงาน" อยู่ระหว่างการจัดทำ</p>
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
<script>window.CONTENT_SECTION = 'executives';</script>
<script src="assets/js/api-config.js"></script>
<script src="assets/js/dept-context.js"></script>
<script src="assets/js/general-content.js"></script>
</body>
</html>
