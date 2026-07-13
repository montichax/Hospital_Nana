// เชื่อมหน้าแผนก (dept_*.php) กับ Node.js API แทนการ query PDO ตรง
// ต้องมี <script>window.DEPT_ID = <int>;</script> ก่อนโหลดไฟล์นี้
(function () {
    const API_BASE = window.API_BASE;
    const params = new URLSearchParams(location.search);
    const targetId = parseInt(params.get('id') || '0', 10);
    const showPersonnel = params.has('show_personnel');

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
                html += `<img src="${safe}" class="dc-img lightbox-trigger shadow-sm border" alt="" onerror="this.style.display='none'">`;
            } else if (ext === 'pdf') {
                html += `<div class="dc-pdf-wrap pdf-lightbox-trigger shadow-sm" data-src="${safe}">
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

    function renderPersonnel(bySection) {
        const list = bySection.personnel || [];
        if (!list.length) {
            return `<div class="dept-content-card dept-content-card-wide">
                        <div class="dc-empty-state"><i class="bi bi-people"></i><h3>ยังไม่มีข้อมูลบุคลากรในแผนกนี้</h3></div>
                    </div>`;
        }

        // บางแผนกแนบรูปรายคน (การ์ดต่อคน) บางแผนกแนบทำเนียบเป็น PDF ไฟล์เดียวหลายหน้า — แยกตามไฟล์แนบของแต่ละแถว
        const cardRows = [];
        const pdfRows = [];
        list.forEach(p => {
            const files = parseFileNames(p.file_name);
            const hasPdf = files.some(f => extOf(f) === 'pdf');
            (hasPdf ? pdfRows : cardRows).push(p);
        });

        const pdfHtml = pdfRows.map(p => {
            const files = parseFileNames(p.file_name);
            const pdf = files.find(f => extOf(f) === 'pdf');
            const safe = esc('uploads/' + pdf);
            return `<div class="dept-content-card mb-4">
                        <h2 class="dc-title">${esc(p.title)}</h2>
                        <div class="dc-meta mb-3"><i class="bi bi-calendar3 me-1"></i> ${dateToThaiFull(p.created_at)}</div>
                        <div class="dc-pdf-wrap pdf-lightbox-trigger shadow-sm" data-src="${safe}">
                            <embed src="${safe}" type="application/pdf" class="dc-pdf">
                        </div>
                    </div>`;
        }).join('');

        const cardsHtml = cardRows.map(p => {
            const files = parseFileNames(p.file_name);
            const img = files.find(f => ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extOf(f)));
            const imgHtml = img
                ? `<img src="uploads/${esc(img)}" class="personnel-img lightbox-trigger img-fluid mx-auto mb-3" alt="" style="width:100%;height:auto;max-height:280px;object-fit:cover;">`
                : `<div class="personnel-img personnel-img-placeholder d-flex align-items-center justify-content-center bg-light text-muted mx-auto mb-3" style="width:100%;height:220px;font-size:64px;"><i class="bi bi-person-fill"></i></div>`;
            const role = (p.content || '').trim()
                ? `<div class="personnel-role small text-muted fw-semibold">${esc(p.content)}</div>` : '';
            return `<div class="col-11 col-sm-8 col-md-6 col-lg-4 d-flex justify-content-center">
                        <div class="personnel-card w-100 p-4 border rounded shadow-sm bg-white" style="border-radius:12px;max-width:320px;">
                            ${imgHtml}
                            <div class="personnel-name fw-bold text-dark mb-1" style="font-size:16px;">${esc(p.title)}</div>
                            ${role}
                        </div>
                    </div>`;
        }).join('');
        const cardsSection = cardRows.length
            ? `<div class="dept-content-card dept-content-card-wide"><div class="row g-4 justify-content-center">${cardsHtml}</div></div>`
            : '';

        return pdfHtml + cardsSection;
    }

    function renderContentCard(row) {
        const body = row.content ? `<div class="dc-body mb-4">${esc(row.content).replace(/\n/g, '<br>')}</div>` : '';
        return `<div class="dept-content-card">
                    <h2 class="dc-title">${esc(row.title)}</h2>
                    <div class="dc-meta mb-3"><i class="bi bi-calendar3 me-1"></i> ${dateToThaiFull(row.created_at)}</div>
                    ${body}
                    <div class="dc-attachments">${renderAttachments(row)}</div>
                </div>`;
    }

    function renderKnowledgeList(bySection) {
        const list = bySection.knowledge || [];
        if (!list.length) {
            return `<div class="dept-content-card">
                        <div class="dc-empty-state"><i class="bi bi-folder-x"></i><h3>ไม่พบข้อมูลข่าวสารประชาสัมพันธ์</h3></div>
                    </div>`;
        }
        return `<div class="d-flex flex-column gap-4">${list.map(renderContentCard).join('')}</div>`;
    }

    fetch(`${API_BASE}/departments/${window.DEPT_ID}/contents`)
        .then(res => res.json())
        .then(data => {
            const contents = data.contents || [];
            const bySection = {};
            contents.forEach(row => {
                (bySection[row.section] = bySection[row.section] || []).push(row);
            });

            let selectedItem = null;
            let showAllKnowledge = true;
            if (targetId > 0) {
                selectedItem = contents.find(r => parseInt(r.id, 10) === targetId) || null;
                if (selectedItem && selectedItem.section !== 'knowledge') showAllKnowledge = false;
            }

            const mainEl = document.getElementById('deptMainContent');
            if (showPersonnel) {
                mainEl.innerHTML = renderPersonnel(bySection);
            } else if (showAllKnowledge) {
                mainEl.innerHTML = renderKnowledgeList(bySection);
            } else if (selectedItem) {
                mainEl.innerHTML = renderContentCard(selectedItem);
            } else {
                mainEl.innerHTML = '';
            }
        })
        .catch(() => {
            document.getElementById('deptMainContent').innerHTML =
                `<div class="dept-content-card">
                    <div class="dc-empty-state">
                        <i class="bi bi-wifi-off"></i>
                        <h3>ไม่สามารถโหลดข้อมูลได้ (ตรวจสอบว่า Node.js API server เปิดอยู่หรือไม่ — รัน "npm start")</h3>
                    </div>
                </div>`;
        });
})();
