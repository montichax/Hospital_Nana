// สไลด์ Banner ประจำแผนก (จัดการผ่าน admin.php แท็บ Banner/Slider — เลือก "แสดงที่" เป็นแผนกนี้)
// ต้องมี <script>window.DEPT_ID = <int>;</script> ก่อนโหลดไฟล์นี้
(function () {
    const API_BASE = window.API_BASE;
    const wrap = document.getElementById('deptHeroWrap');
    const carouselEl = document.getElementById('deptHeroCarousel');
    if (!wrap || !carouselEl || typeof window.DEPT_ID === 'undefined') return;

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    fetch(`${API_BASE}/banners/department/${window.DEPT_ID}`)
        .then(res => res.json())
        .then(list => {
            const slides = Array.isArray(list) ? list : [];
            if (!slides.length) return;

            const indicators = slides.map((s, i) =>
                `<button type="button" data-bs-target="#deptHeroCarousel" data-bs-slide-to="${i}" ${i === 0 ? 'class="active"' : ''}></button>`
            ).join('');

            const items = slides.map((s, i) => {
                const bg   = s.image_name ? `uploads/${esc(s.image_name)}` : '';
                const sub  = s.subtitle ? `<p class="mb-4" style="font-size:16px; opacity:0.95;">${esc(s.subtitle)}</p>` : '';
                const link = s.link_url ? `<a href="${esc(s.link_url)}" target="_blank" class="btn-readmore">อ่านเพิ่มเติม</a>` : '';
                return `<div class="carousel-item ${i === 0 ? 'active' : ''}" style="background-image:url('${bg}');">
                            <div class="carousel-caption-custom">${sub}${link}</div>
                        </div>`;
            }).join('');

            carouselEl.innerHTML = `
                <div class="carousel-indicators">${indicators}</div>
                <div class="carousel-inner">${items}</div>
                ${slides.length > 1 ? `
                <button class="carousel-control-prev" type="button" data-bs-target="#deptHeroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#deptHeroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>` : ''}
            `;
            wrap.style.display = '';
            new bootstrap.Carousel(carouselEl, { interval: 4000, ride: 'carousel' });
        })
        .catch(() => {});
})();
