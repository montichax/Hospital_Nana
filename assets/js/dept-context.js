// เก็บ "บริบทแผนก" ไว้ตอนเดินเมนูหลัก — ใช้ร่วมกับหน้าที่มีเมนูหลัก (mainNav)
// ถ้าอยู่ในหน้าแผนก (window.DEPT_ID ถูกตั้งไว้ก่อนหน้านี้) หรือมี ?dept=ID ต่อท้าย URL
// จะแปะ ?dept=ID ไปกับทุกลิงก์ในเมนู เพื่อให้กลับไปแผนกเดิมได้ทุกเมื่อ
(function () {
    const API_BASE = window.API_BASE;
    const params = new URLSearchParams(location.search);
    const deptId = (typeof window.DEPT_ID !== 'undefined' && window.DEPT_ID)
        ? String(window.DEPT_ID)
        : params.get('dept');

    if (!deptId) return;

    fetch(`${API_BASE}/departments`)
        .then(res => res.json())
        .then(list => (Array.isArray(list) ? list : []).find(d => String(d.id) === deptId))
        .then(dept => {
            if (!dept) return;
            const deptUrl = dept.link_url || `department.php?id=${encodeURIComponent(dept.id)}`;

            // รายการ "ข่าวสาร" ในเมนู "ข่าวสารประชาสัมพันธ์" (ของหน้าที่ไม่ใช่หน้าแผนกเอง)
            // ให้ชี้กลับไปข่าว/เกร็ดความรู้ของแผนกเดิม แทนข่าวรวมของ index
            // (ต้องทำก่อนลูปแปะ ?dept= ด้านล่าง ไม่งั้น selector จะหา href เดิมไม่เจอ)
            if (!window.DEPT_ID) {
                const newsItem = document.querySelector('.navbar-nav a[href^="all_news.php"]');
                if (newsItem) {
                    newsItem.setAttribute('href', deptUrl);
                    newsItem.innerHTML = `<i class="bi bi-megaphone-fill me-2"></i> ข่าวสารของแผนก`;
                }

                // ปุ่ม "กลับหน้าแรก" ให้กลับไปหน้าแผนกเดิมแทน
                const backBtn = document.querySelector('.btn-back');
                if (backBtn) {
                    backBtn.setAttribute('href', deptUrl);
                    backBtn.innerHTML = `<i class="bi bi-arrow-left-circle-fill"></i> กลับหน้า${dept.name}`;
                }
            }

            // แปะ ?dept=ID ต่อท้ายทุกลิงก์ในเมนูหลัก (ยกเว้น "#") รวมถึง 2 รายการที่เพิ่งแก้ด้านบน
            document.querySelectorAll('.navbar-nav a[href], .btn-back[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href || href === '#' || /^https?:\/\//i.test(href) || href.startsWith('javascript:')) return;
                const url = new URL(href, location.href);
                url.searchParams.set('dept', dept.id);
                a.setAttribute('href', url.pathname + url.search);
            });
        })
        .catch(() => {});
})();
