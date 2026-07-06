<?php
require_once 'connect.php';

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

// แปลง file_name ที่อาจเป็นสตริงเดี่ยวหรือ JSON array (จาก department_contents)
function parseFileNames($fileData) {
    if (empty($fileData)) return [];
    $decoded = json_decode($fileData, true);
    if (is_array($decoded)) return $decoded;
    if (is_string($fileData) && !empty($fileData)) return [$fileData];
    return [];
}

// ===== โหมดการทำงาน =====
// - type=dept&id=<id>  -> ดูเอกสารของแผนก (ตาราง department_contents)
// - id=<id> (ไม่มี type) -> ดูข่าวกลาง (ตาราง news)
$type    = $_GET['type'] ?? 'news';
$item_id = (int)($_GET['id'] ?? 0);

if ($item_id <= 0) {
    header('Location: index.php');
    exit;
}

// ตัวแปรสากลที่ใช้แสดงผล
$page_title    = '';
$created_date  = '';
$content_text  = '';
$is_new        = 0;
$link_url      = '';
$file_list     = [];
$section_label = '';
$related_news  = [];
$back_url      = 'index.php';
$back_text     = 'กลับหน้าแรก';
$header_title  = 'รายละเอียดข่าว';
$related_box_title   = 'ข่าวอื่นๆ';
$related_link_prefix = 'news_detail.php?id=';
if ($type === 'dept') {
    // ===== โหมดเอกสารแผนก =====
    $stmt = $conn->prepare("SELECT dc.*, d.name AS dept_name, d.link_url AS dept_link FROM department_contents dc LEFT JOIN departments d ON d.id = dc.department_id WHERE dc.id = :id");
    $stmt->execute([':id' => $item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) { header('Location: index.php'); exit; }

    $page_title   = $item['title'];
    $created_date = $item['created_at'] ?? '';
    $content_text = $item['content'] ?? '';
    $link_url     = $item['link_url'] ?? '';
    $file_list    = parseFileNames($item['file_name'] ?? '');

    $sectionLabels = [
        'structure' => 'โครงสร้างการบริหารงาน', 'personnel' => 'ทำเนียบบุคลากร',
        'service' => 'การให้บริการต่างๆ', 'service_profile' => 'Service Profile',
        'indicator' => 'ตัวชี้วัด', 'academic' => 'ผลงานวิจัย',
        'wi' => 'WI / SP', 'knowledge' => 'ข่าวประชาสัมพันธ์ / เกร็ดความรู้',
    ];
    $section_label = $sectionLabels[$item['section']] ?? $item['section'];

    // เอกสารอื่นในแผนกเดียวกัน 5 รายการล่าสุด
    $stmt_rel = $conn->prepare("SELECT id, title, created_at, section FROM department_contents WHERE department_id = :dept_id AND id != :id ORDER BY created_at DESC, id DESC LIMIT 5");
    $stmt_rel->execute([':dept_id' => (int)$item['department_id'], ':id' => $item_id]);
    $related_news = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

    $dept_link = !empty($item['dept_link']) ? $item['dept_link'] : 'index.php';
    $back_url     = $dept_link;
    $back_text    = 'กลับหน้า ' . ($item['dept_name'] ?? 'แผนก');
    $header_title = 'เอกสาร — ' . ($item['dept_name'] ?? '');
    $related_box_title   = 'เอกสารอื่นในแผนก';
    $related_link_prefix = 'news_detail.php?type=dept&id=';
} else {
    // ===== โหมดข่าวกลาง (เดิม) =====
    $stmt = $conn->prepare("SELECT * FROM news WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) { header('Location: index.php'); exit; }

    $page_title   = $item['title'];
    $created_date = $item['created_at'] ?? '';
    $content_text = $item['content'] ?? '';
    $is_new       = (int)($item['is_new'] ?? 0);
    $link_url     = $item['link_url'] ?? '';
    if (!empty($item['image_name']) && $item['image_name'] !== 'default.jpg') {
        $file_list = [$item['image_name']];
    }

    $stmt_rel = $conn->prepare("SELECT id, title, created_at FROM news WHERE id != :id ORDER BY created_at DESC LIMIT 5");
    $stmt_rel->execute([':id' => $item_id]);
    $related_news = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);
}

// แยกไฟล์ที่จะแสดงเป็น hero/embed กับไฟล์แนบดาวน์โหลด
$hero_image = null;
$hero_pdf   = null;
$hero_video = null;
$download_files = [];

foreach ($file_list as $fname) {
    if (empty($fname) || $fname === 'default.jpg') continue;
    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        if ($hero_image === null) { $hero_image = $fname; continue; }
    } elseif ($ext === 'pdf') {
        if ($hero_pdf === null) { $hero_pdf = $fname; continue; }
    } elseif (in_array($ext, ['mp4','webm','ogg'])) {
        if ($hero_video === null) { $hero_video = $fname; continue; }
    }
    $download_files[] = $fname;
}

function fileIcon($ext) {
    if (in_array($ext, ['doc','docx'])) return 'bi-file-earmark-word';
    if (in_array($ext, ['xls','xlsx','csv'])) return 'bi-file-earmark-excel';
    if (in_array($ext, ['ppt','pptx'])) return 'bi-file-earmark-slides';
    if ($ext === 'pdf') return 'bi-file-earmark-pdf';
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) return 'bi-file-earmark-image';
    return 'bi-file-earmark-arrow-down';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - กลุ่มงานการพยาบาล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="news_detail.css">
    <link rel="stylesheet" href="department.css">
</head>
<body>

<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><i class="bi bi-telephone-fill"></i> สายด่วน: 044-316-999 ต่อ 4400 &nbsp;|&nbsp; <i class="bi bi-envelope-fill"></i> nursing@pkc.go.th</div>
    </div>
</div>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="bi bi-newspaper me-2"></i><?= htmlspecialchars($header_title) ?></h1>
        <a href="<?= htmlspecialchars($back_url) ?>" class="btn-back">
            <i class="bi bi-arrow-left-circle-fill"></i> <?= htmlspecialchars($back_text) ?>
        </a>
    </div>
</div>

<div class="container content-wrapper">
  <div class="row">

    <div class="col-lg-8">
        <div class="detail-card">

            <?php if ($hero_image): ?>
                <img src="uploads/<?= htmlspecialchars($hero_image) ?>"
                     class="detail-hero-img lightbox-trigger"
                     alt="<?= htmlspecialchars($page_title) ?>"
                     onerror="this.style.display='none'">

            <?php elseif ($hero_pdf): ?>
                <div class="dc-pdf-wrap pdf-lightbox-trigger" data-src="uploads/<?= htmlspecialchars($hero_pdf) ?>">
                    <embed src="uploads/<?= htmlspecialchars($hero_pdf) ?>" type="application/pdf" class="pdf-embed">
                    <div class="dc-pdf-overlay">
                        <i class="bi bi-arrows-fullscreen"></i> คลิกเพื่อดูเต็มจอ
                    </div>
                </div>

            <?php elseif ($hero_video): ?>
                <video class="pdf-embed" controls preload="metadata">
                    <source src="uploads/<?= htmlspecialchars($hero_video) ?>">
                </video>
            <?php endif; ?>

            <div class="detail-body">
                <h1 class="detail-title"><?= htmlspecialchars($page_title) ?></h1>

                <div class="detail-meta">
                    <span class="badge-date">
                        <i class="bi bi-calendar-event me-1"></i>
                        <?= dateToThaiFull($created_date) ?>
                    </span>

                    <?php if (!empty($section_label)): ?>
                        <span class="badge-date" style="background-color:#6c757d;">
                            <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($section_label) ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($is_new === 1): ?>
                        <span class="badge-isnew">
                            <i class="bi bi-stars me-1"></i>ใหม่
                        </span>
                    <?php endif; ?>
                </div>

                <hr class="detail-divider">

                <?php if (!empty(trim($content_text))): ?>
                    <div class="detail-content"><?= htmlspecialchars($content_text) ?></div>
                <?php else: ?>
                    <div class="detail-content-empty">
                        <i class="bi bi-newspaper mb-2 d-block" style="font-size:32px;color:#ddd;"></i>
                        ไม่มีรายละเอียดเพิ่มเติม
                    </div>
                <?php endif; ?>

                <?php foreach ($download_files as $fname): ?>
                    <a href="uploads/<?= htmlspecialchars($fname) ?>"
                       target="_blank"
                       class="dc-file-tile mt-3">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        <div class="dc-file-tile-label">
                            ไฟล์เอกสาร <small>คลิกเพื่อเปิด/ดาวน์โหลด</small>
                        </div>
                    </a>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="section-title">
            <i class="bi bi-megaphone-fill me-1"></i>
            <?= htmlspecialchars($related_box_title) ?>
        </div>

        <div class="bg-white border rounded p-3">
            <?php if (empty($related_news)): ?>
                <p class="text-muted small mb-0">ไม่มีรายการอื่น</p>
            <?php else: ?>
                <?php foreach ($related_news as $rel): ?>
                    <a href="<?= htmlspecialchars($related_link_prefix) . (int)$rel['id'] ?>"
                       class="related-news-item">

                        <i class="bi bi-chevron-right small"></i>

                        <div>
                            <div class="related-news-title">
                                <?= htmlspecialchars($rel['title']) ?>
                            </div>
                            <div class="related-news-date">
                                <?= dateToThaiFull($rel['created_at']) ?>
                            </div>
                        </div>

                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
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