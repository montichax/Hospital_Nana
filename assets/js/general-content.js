// เชื่อมหน้าเนื้อหากลางขององค์กร (vision_mission.php, kpi.php, wi.php ฯลฯ) กับ Node.js API
// ต้องมี <script>window.CONTENT_SECTION = '<slug>';</script> ก่อนโหลดไฟล์นี้ และ <div id="generalContent"></div> ในหน้า
(function () {
    const API_BASE = window.API_BASE;
    const mainEl = document.getElementById('generalContent');
    if (!mainEl || !window.CONTENT_SECTION) return;

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

    function renderAttachments(row) {
        const files = parseFileNames(row.file_name);
        let html = '';
        files.forEach(fname => {
            if (!fname || fname === 'default.jpg') return;
            const safe = esc('uploads/' + fname);
            const ext = extOf(fname);
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                html += `<a href="${safe}" target="_blank"><img src="${safe}" class="dc-img shadow-sm border" alt="" onerror="this.style.display='none'"></a>`;
            } else if (ext === 'pdf') {
                html += `<div class="dc-pdf-wrap shadow-sm">
                            <embed src="${safe}" type="application/pdf" class="dc-pdf">
                          </div>`;
            } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
                html += `<video class="dc-video shadow-sm" controls preload="metadata"><source src="${safe}"></video>`;
            } else {
                let icon = 'bi-file-earmark-arrow-down', label = 'ไฟล์เอกสาร';
                if (['doc', 'docx'].includes(ext)) { icon = 'bi-file-earmark-word-fill'; label = 'ไฟล์ Word'; }
                else if (['xls', 'xlsx', 'csv'].includes(ext)) { icon = 'bi-file-earmark-excel-fill'; label = 'ไฟล์ Excel'; }
                else if (['ppt', 'pptx'].includes(ext)) { icon = 'bi-file-earmark-slides-fill'; label = 'ไฟล์ PowerPoint'; }
                html += `<a href="${safe}" target="_blank" class="dc-file-tile mx-auto">
                            <i class="bi ${icon}"></i>
                            <div class="dc-file-tile-label">${label}<small>คลิกเพื่อเปิด/ดาวน์โหลด</small></div>
                          </a>`;
            }
        });
        return html;
    }

    function renderContentCard(row) {
        const body = row.content ? `<div class="dc-body mb-4">${esc(row.content).replace(/\n/g, '<br>')}</div>` : '';
        const link = row.link_url
            ? `<div class="mb-3"><a href="${esc(row.link_url)}" target="_blank"><i class="bi bi-link-45deg"></i> ${esc(row.link_url)}</a></div>`
            : '';
        return `<div class="dept-content-card">
                    <h2 class="dc-title">${esc(row.title)}</h2>
                    <div class="dc-meta mb-3"><i class="bi bi-calendar3 me-1"></i> ${dateToThaiFull(row.created_at)}</div>
                    ${body}
                    ${link}
                    <div class="dc-attachments">${renderAttachments(row)}</div>
                </div>`;
    }

    fetch(`${API_BASE}/general-contents/${encodeURIComponent(window.CONTENT_SECTION)}`)
        .then(res => res.json())
        .then(list => {
            const rows = Array.isArray(list) ? list : [];
            if (!rows.length) return; // เหลือ empty-state เดิมที่ hardcode ไว้ในหน้า
            mainEl.innerHTML = `<div class="d-flex flex-column gap-4">${rows.map(renderContentCard).join('')}</div>`;
        })
        .catch(() => {});
})();
