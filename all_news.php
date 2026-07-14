<?php
// ข้อมูลข่าวดึงผ่าน Node.js API (fetch ฝั่ง browser) แทน PDO — ดูสคริปต์ท้ายไฟล์
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวประชาสัมพันธ์ทั้งหมด - กลุ่มงานการพยาบาล โรงพยาบาลปากช่องนานา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="all_news.css">
</head>
<body>

<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><i class="bi bi-telephone-fill"></i> สายด่วน: 044-316-999 ต่อ 4400 &nbsp;|&nbsp; <i class="bi bi-envelope-fill"></i> nursing@pkc.go.th</div>
    </div>
</div>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="bi bi-megaphone-fill me-2"></i>ข่าวประชาสัมพันธ์ทั้งหมด</h1>
        <a href="index.php" class="btn-back">
            <i class="bi bi-arrow-left-circle-fill"></i> กลับหน้าหลัก
        </a>
    </div>
</div>

<div class="container my-5">
    <div class="section-title">
        <i class="bi bi-megaphone-fill me-1"></i> รายการข่าวประชาสัมพันธ์
    </div>

    <div id="allNewsContainer">
        <div class="empty-state"><i class="bi bi-hourglass-split"></i><p>กำลังโหลดข่าว...</p></div>
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
<script src="assets/js/api-config.js"></script>
<script>
(function () {
    const API_BASE = window.API_BASE;

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function dateToThaiFull(dateStr) {
        if (!dateStr) return 'ไม่ระบุวันที่';
        const d = new Date(dateStr);
        if (isNaN(d)) return 'ไม่ระบุวันที่';
        const months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
        return `${d.getDate()} ${months[d.getMonth() + 1]} ${d.getFullYear() + 543}`;
    }

    function render(list) {
        const container = document.getElementById('allNewsContainer');
        if (!list.length) {
            container.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>ขณะนี้ยังไม่มีข้อมูลข่าวประชาสัมพันธ์</p></div>';
            return;
        }
        container.innerHTML = '<div class="news-list-card">' + list.map(news => {
            const badge = Number(news.is_new) === 1
                ? '<span class="badge-new"><i class="bi bi-stars me-1"></i>ใหม่</span>' : '';
            return `<a href="news_detail.php?id=${parseInt(news.id, 10)}" class="news-row">
                        <div class="news-row-left">
                            <i class="bi bi-chevron-right small"></i>
                            <div class="news-row-title">${esc(news.title)} ${badge}</div>
                        </div>
                        <div class="news-row-date">${dateToThaiFull(news.created_at)}</div>
                    </a>`;
        }).join('') + '</div>';
    }

    fetch(`${API_BASE}/news`)
        .then(res => res.json())
        .then(data => render(Array.isArray(data) ? data : []))
        .catch(() => {
            document.getElementById('allNewsContainer').innerHTML =
                '<div class="empty-state"><i class="bi bi-wifi-off"></i><p>ไม่สามารถโหลดข่าวได้ (ตรวจสอบว่า Node.js API server เปิดอยู่หรือไม่)</p></div>';
        });
})();
</script>
</body>
</html>
