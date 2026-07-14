<?php
// =====================================================================
//  อุรเวชช์ — หน้าหอผู้ป่วย/หน่วยงาน
//  ข้อมูลทั้งหมด (เมนู/เนื้อหา/บุคลากร) ดึงผ่าน Node.js API — ดู assets/js/dept-api.js
//  API: GET http://localhost:3000/api/departments/15/contents
// =====================================================================
$DEPT_ID   = 15;
$DEPT_NAME = 'อุรเวชช์';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($DEPT_NAME) ?> - กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="department.css">
</head>
<body>

<!-- แถบบนสุด: เบอร์สายด่วนติดต่อ/อีเมล และปุ่มเข้าสู่ระบบสำหรับเจ้าหน้าที่ -->
<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><i class="bi bi-telephone-fill"></i> สายด่วน: 044-316-999 ต่อ 4400 &nbsp;|&nbsp; <i class="bi bi-envelope-fill"></i> nursing@pkc.go.th</div>
        <a href="login.php" class="btn btn-sm btn-outline-light">เข้าสู่ระบบ</a>
    </div>
</div>

<!-- แบนเนอร์หัวหน้าเว็บ: โลโก้โรงพยาบาล + ชื่อกลุ่มงานการพยาบาล/ชื่อแผนก ($DEPT_NAME) -->
<div class="header-banner">
    <div class="container d-flex align-items-center">
        <div class="me-3">
            <img src="uploads/logo.png" alt="Logo" style="width: 65px; height: 70px; object-fit: contain;">
        </div>
        <div>
            <h2 class="mb-0 fw-bold">กลุ่มงานการพยาบาล <span class="fw-normal opacity-90">· <?= htmlspecialchars($DEPT_NAME) ?></span></h2>
            <div class="small opacity-90">โรงพยาบาลปากช่องนานา | Nursing Department, Pakchong Nana Hospital</div>
        </div>
    </div>
</div>

<!-- เมนูหลักของเว็บกลุ่มงานการพยาบาล แบ่งเป็นหมวด: เกี่ยวกับกลุ่มงาน/งานบริหาร/งานบริการ/งานวิชาการ/คุณภาพทางการพยาบาล/งานสารสนเทศ/ข่าวประชาสัมพันธ์ -->
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
                        <li><a class="dropdown-item" href="personnel_gallery.php"><i class="bi bi-people-fill me-2"></i> รูปบุคลากร</a></li>
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
                    <a class="nav-link dropdown-toggle" href="#" id="serviceDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-clipboard2-check-fill me-1"></i>งานบริการ
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="serviceDropdown">
                        <li><a class="dropdown-item" href="supervision_results.php"><i class="bi bi-clipboard-check me-2"></i> ผลการนิเทศ</a></li>
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
                        <i class="bi bi-star-fill me-1"></i>คุณภาพทางการพยาบาล
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
                        <i class="bi bi-bell-fill me-1"></i>ข่าวประชาสัมพันธ์
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

<!-- แคโรเซลรูปแบนเนอร์ประจำแผนก: ซ่อนไว้ก่อน (display:none) จะถูกแสดงโดย dept-banner.js เมื่อโหลดรูปจาก API สำเร็จ -->
<div class="dept-hero-wrap" id="deptHeroWrap" style="display:none;">
    <div id="deptHeroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000"></div>
</div>

<!-- แถบวิสัยทัศน์ของกลุ่มงานการพยาบาล -->
<div class="vision-bar text-center">
    <div class="container">
        " วิสัยทัศน์: กลุ่มงานการพยาบาลที่มีคุณภาพ มาตรฐาน เป็นที่ไว้วางใจของผู้รับบริการ ภายใต้หลักธรรมาภิบาล เพื่อสุขภาวะที่ดีของประชาชน "
    </div>
</div>

<!-- เนื้อหาหลักของแผนก: #deptMainContent เป็นที่ว่างรอ dept-api.js ดึงข้อมูล (เมนู/เนื้อหา/บุคลากร) ตาม DEPT_ID มาแสดงแทน spinner นี้ -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8" id="deptMainContent">
            <div class="text-center text-muted py-5"><i class="bi bi-hourglass-split"></i> กำลังโหลดข้อมูล...</div>
        </div>
    </div>
</div>

<!-- ส่วนท้ายเว็บไซต์: ข้อมูลติดต่อโรงพยาบาลและลิงก์หน่วยงานที่เกี่ยวข้อง -->
<footer class="main-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5><i class="bi bi-building"></i> กลุ่มงานการพยาบาล</h5>
                <p class="small opacity-80 mt-2">โรงพยาบาลปากช่องนานา<br>มุ่งมั่นในการพัฒนาคุณภาพทางการพยาบาล เพื่อผู้ป่วยและผู้รับบริการทุกคน</p>
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
        <div class="container">© 2569 กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา — สงวนลิขสิทธิ์ทั้งหมด</div>
    </div>
</footer>

<!-- โมดัลแสดงรูปภาพ/PDF แบบเต็มจอ เปิดใช้เมื่อคลิกรูปหรือไฟล์แนบในเนื้อหาที่ดึงมาจาก API -->
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

<!-- โหลด Bootstrap JS, กำหนด DEPT_ID ให้สคริปต์ฝั่ง client รู้ว่ากำลังแสดงแผนกไหน แล้วโหลดสคริปต์ดึง/แสดงข้อมูลแผนกจาก API -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.DEPT_ID = <?= (int)$DEPT_ID ?>;</script>
<script src="assets/js/api-config.js"></script>
<script src="assets/js/dept-api.js"></script>
<script src="assets/js/dept-context.js"></script>
<script src="assets/js/dept-banner.js"></script>
<script>
// เปิด/ปิดกล่อง Lightbox แสดงรูปภาพหรือ PDF แบบเต็มจอเมื่อคลิกที่รูป (.lightbox-trigger) หรือไฟล์แนบ (.pdf-lightbox-trigger)
(function () {
    const modalEl = document.getElementById('lightboxModal');
    const imgEl   = document.getElementById('lightboxImg');
    const pdfEl   = document.getElementById('lightboxPdf');
    let bsModal   = null;

    function openLightbox(type, src) {
        if (type === 'img') {
            imgEl.src = src; imgEl.style.display = 'block';
            pdfEl.style.display = 'none'; pdfEl.src = '';
        } else if (type === 'pdf') {
            pdfEl.src = src; pdfEl.style.display = 'block';
            imgEl.style.display = 'none'; imgEl.src = '';
        }
        if (!bsModal) bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }

    modalEl.addEventListener('hidden.bs.modal', function () {
        imgEl.src = ''; pdfEl.src = '';
    });

    document.addEventListener('click', function (e) {
        const img = e.target.closest('.lightbox-trigger');
        if (img) { e.preventDefault(); openLightbox('img', img.src); return; }
        const pdfBox = e.target.closest('.pdf-lightbox-trigger');
        if (pdfBox) { e.preventDefault(); openLightbox('pdf', pdfBox.dataset.src); return; }
    });
})();
</script>
</body>
</html>
