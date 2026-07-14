<?php
// ===== โหมดการทำงาน =====
// - type=dept&id=<id>  -> ดูเอกสารของแผนก (ตาราง department_contents ผ่าน API)
// - id=<id> (ไม่มี type) -> ดูข่าวกลาง (ตาราง news ผ่าน API)
$type    = $_GET['type'] ?? 'news';
$item_id = (int)($_GET['id'] ?? 0);

if ($item_id <= 0) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitleTag">กำลังโหลด... - กลุ่มงานการพยาบาล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="news_detail.css">
    <link rel="stylesheet" href="department.css">
</head>
<body>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 id="headerTitle"><i class="bi bi-newspaper me-2"></i>กำลังโหลด...</h1>
        <a href="index.php" class="btn-back" id="backLink">
            <i class="bi bi-arrow-left-circle-fill"></i> <span id="backText">กลับหน้าหลัก</span>
        </a>
    </div>
</div>

<div class="container my-5">
    <div class="row g-4">

        <div class="col-lg-8">
            <div class="detail-card" id="detailCard">
                <div class="text-center text-muted py-5"><i class="bi bi-hourglass-split"></i> กำลังโหลด...</div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="section-title" id="relatedBoxTitle"><i class="bi bi-megaphone-fill me-1"></i> ข่าวอื่นๆ</div>
            <div class="bg-white border rounded p-3" id="relatedList">
                <p class="text-muted small mb-0">กำลังโหลด...</p>
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
<script>window.NEWS_DETAIL_TYPE = <?= json_encode($type) ?>; window.NEWS_DETAIL_ID = <?= (int)$item_id ?>;</script>
<script src="assets/js/api-config.js"></script>
<script>
(function () {
    const API_BASE = window.API_BASE;
    const type = window.NEWS_DETAIL_TYPE;
    const itemId = window.NEWS_DETAIL_ID;

    const sectionLabels = {
        structure: 'โครงสร้างการบริหารงาน', personnel: 'ทำเนียบบุคลากร', service: 'การให้บริการต่างๆ',
        service_profile: 'Service Profile', indicator: 'ตัวชี้วัด', academic: 'ผลงานวิจัย',
        wi: 'WI / SP', knowledge: 'ข่าวประชาสัมพันธ์ / เกร็ดความรู้',
    };

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

    function parseFileNames(fileData) {
        if (!fileData) return [];
        try {
            const decoded = JSON.parse(fileData);
            if (Array.isArray(decoded)) return decoded;
        } catch (e) { /* ไม่ใช่ JSON ก็ถือว่าเป็นชื่อไฟล์เดี่ยว */ }
        return typeof fileData === 'string' ? [fileData] : [];
    }

    function extOf(fname) {
        return (fname.split('.').pop() || '').toLowerCase();
    }

    function downloadTile(fname) {
        const ext = extOf(fname);
        let icon = 'bi-file-earmark-arrow-down', label = 'ไฟล์เอกสาร';
        if (['doc', 'docx'].includes(ext)) { icon = 'bi-file-earmark-word-fill'; label = 'ไฟล์ Word'; }
        else if (['xls', 'xlsx', 'csv'].includes(ext)) { icon = 'bi-file-earmark-excel-fill'; label = 'ไฟล์ Excel'; }
        else if (['ppt', 'pptx'].includes(ext)) { icon = 'bi-file-earmark-slides-fill'; label = 'ไฟล์ PowerPoint'; }
        return `<a href="uploads/${esc(fname)}" target="_blank" class="dc-file-tile mt-3">
                    <i class="bi ${icon}"></i>
                    <div class="dc-file-tile-label">${label}<small>คลิกเพื่อเปิด/ดาวน์โหลด</small></div>
                </a>`;
    }

    function renderDetail(item, sectionLabel) {
        const fileList = type === 'dept'
            ? parseFileNames(item.file_name)
            : (item.image_name && item.image_name !== 'default.jpg' ? [item.image_name] : []);

        let heroImage = null, heroPdf = null, heroVideo = null;
        const downloadFiles = [];
        fileList.forEach(fname => {
            if (!fname || fname === 'default.jpg') return;
            const ext = extOf(fname);
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext) && !heroImage) { heroImage = fname; return; }
            if (ext === 'pdf' && !heroPdf) { heroPdf = fname; return; }
            if (['mp4', 'webm', 'ogg'].includes(ext) && !heroVideo) { heroVideo = fname; return; }
            downloadFiles.push(fname);
        });

        let heroHtml = '';
        if (heroImage) {
            heroHtml = `<img src="uploads/${esc(heroImage)}" class="detail-hero-img lightbox-trigger" alt="${esc(item.title)}" onerror="this.style.display='none'">`;
        } else if (heroPdf) {
            heroHtml = `<div class="dc-pdf-wrap pdf-lightbox-trigger" data-src="uploads/${esc(heroPdf)}">
                            <embed src="uploads/${esc(heroPdf)}" type="application/pdf" class="pdf-embed">
                            <div class="dc-pdf-overlay"><i class="bi bi-arrows-fullscreen"></i> คลิกเพื่อดูเต็มจอ</div>
                        </div>`;
        } else if (heroVideo) {
            heroHtml = `<video class="pdf-embed" controls preload="metadata"><source src="uploads/${esc(heroVideo)}"></video>`;
        }

        const isNewBadge = (type !== 'dept' && Number(item.is_new) === 1)
            ? '<span class="badge-isnew"><i class="bi bi-stars me-1"></i>ใหม่</span>' : '';
        const sectionBadge = sectionLabel
            ? `<span class="badge-date" style="background-color:#6c757d;"><i class="bi bi-tag-fill me-1"></i>${esc(sectionLabel)}</span>` : '';
        const contentHtml = (item.content && item.content.trim())
            ? `<div class="detail-content">${esc(item.content)}</div>`
            : `<div class="detail-content-empty"><i class="bi bi-newspaper mb-2 d-block" style="font-size:32px;color:#ddd;"></i>ไม่มีรายละเอียดเพิ่มเติม</div>`;
        const downloadsHtml = downloadFiles.map(downloadTile).join('');

        document.getElementById('detailCard').innerHTML = `
            ${heroHtml}
            <div class="detail-body">
                <h1 class="detail-title">${esc(item.title)}</h1>
                <div class="detail-meta">
                    <span class="badge-date"><i class="bi bi-calendar-event me-1"></i>${dateToThaiFull(item.created_at)}</span>
                    ${sectionBadge}
                    ${isNewBadge}
                </div>
                <hr class="detail-divider">
                ${contentHtml}
                ${downloadsHtml}
            </div>`;

        document.getElementById('pageTitleTag').textContent = `${item.title} - กลุ่มงานการพยาบาล`;
        document.getElementById('headerTitle').innerHTML =
            `<i class="bi bi-newspaper me-2"></i>${type === 'dept' ? ('เอกสาร — ' + esc(item.dept_name || '')) : 'รายละเอียดข่าว'}`;
    }

    function renderRelated(list, linkPrefix) {
        const box = document.getElementById('relatedList');
        if (!list.length) { box.innerHTML = '<p class="text-muted small mb-0">ไม่มีรายการอื่น</p>'; return; }
        box.innerHTML = list.map(r => `
            <a href="${linkPrefix}${parseInt(r.id, 10)}" class="related-news-item">
                <i class="bi bi-chevron-right small"></i>
                <div>
                    <div class="related-news-title">${esc(r.title)}</div>
                    <div class="related-news-date">${dateToThaiFull(r.created_at)}</div>
                </div>
            </a>`).join('');
    }

    if (type === 'dept') {
        fetch(`${API_BASE}/departments/contents/item/${itemId}`)
            .then(res => { if (!res.ok) throw new Error('not found'); return res.json(); })
            .then(item => {
                renderDetail(item, sectionLabels[item.section] || item.section);

                const backUrl = item.dept_link || 'index.php';
                document.getElementById('backLink').setAttribute('href', backUrl);
                document.getElementById('backText').textContent = 'กลับหน้า ' + (item.dept_name || 'แผนก');
                document.getElementById('relatedBoxTitle').innerHTML =
                    '<i class="bi bi-megaphone-fill me-1"></i> เอกสารอื่นในแผนก';

                return fetch(`${API_BASE}/departments/${item.department_id}/contents`)
                    .then(res => res.json())
                    .then(data => {
                        const related = (data.contents || [])
                            .filter(r => parseInt(r.id, 10) !== itemId)
                            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
                            .slice(0, 5);
                        renderRelated(related, 'news_detail.php?type=dept&id=');
                    });
            })
            .catch(() => { location.href = 'index.php'; });
    } else {
        fetch(`${API_BASE}/news/${itemId}`)
            .then(res => { if (!res.ok) throw new Error('not found'); return res.json(); })
            .then(item => {
                renderDetail(item, '');
                return fetch(`${API_BASE}/news`)
                    .then(res => res.json())
                    .then(list => {
                        const related = (Array.isArray(list) ? list : [])
                            .filter(r => parseInt(r.id, 10) !== itemId)
                            .slice(0, 5);
                        renderRelated(related, 'news_detail.php?id=');
                    });
            })
            .catch(() => { location.href = 'index.php'; });
    }
})();
</script>

<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered p-0" style="background:rgba(0,0,0,0.92);">
        <div class="modal-content border-0" style="background:transparent;">
            <div class="modal-body d-flex align-items-center justify-content-center p-2 position-relative" style="min-height:100vh;">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="font-size:1.4rem; z-index:10;"></button>
                <img id="lightboxImg" src="" alt="" style="max-width:100%; max-height:95vh; object-fit:contain; border-radius:6px; box-shadow:0 4px 40px rgba(0,0,0,0.6); display:none;">
                <embed id="lightboxPdf" src="" type="application/pdf" style="width:96vw; height:94vh; border-radius:6px; display:none;">
            </div>
        </div>
    </div>
</div>

<script>
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
    modalEl.addEventListener('hidden.bs.modal', function () { imgEl.src = ''; pdfEl.src = ''; });
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