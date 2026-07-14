// จุดตั้งค่ากลางของ Base URL สำหรับ Node.js API
// ต้องโหลดไฟล์นี้ก่อน dept-api.js / dept-context.js / dept-banner.js / general-content.js เสมอ
// เวลา deploy ขึ้นโดเมนจริง: ถ้า Node ถูก reverse-proxy ไว้ที่ /api บนโดเมนเดียวกัน ไม่ต้องแก้อะไรเลย (auto-detect)
// ถ้า Node อยู่คนละโฮสต์/พอร์ต ให้แก้ที่ PROD_API_BASE บรรทัดเดียวด้านล่างนี้ที่เดียว
(function () {
    const PROD_API_BASE = null; // เช่น 'https://api.example.com/api' — ใส่ค่าถ้า Node ไม่ได้อยู่ใต้ /api ของโดเมนเดียวกัน
    // isLocal ครอบคลุมทั้ง localhost/127.0.0.1 และโดเมน .test ของ Laragon (เช่น pakchong_nana_hospital.test)
    // เพราะเปิดผ่านโดเมน .test ก็ยังรันบนเครื่องเดียวกัน — Node API อยู่ที่พอร์ต 3000 ของโฮสต์เดียวกันเสมอ
    const isLocal = ['localhost', '127.0.0.1'].includes(location.hostname) || location.hostname.endsWith('.test');

    window.API_BASE = isLocal
        ? `${location.protocol}//${location.hostname}:3000/api`
        : (PROD_API_BASE || `${location.protocol}//${location.hostname}/api`);
})();
