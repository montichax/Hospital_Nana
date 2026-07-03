<?php
$host = "localhost";
$username = "root";  // ค่าเริ่มต้นของ Laragon
$password = "";      // ค่าเริ่มต้นของ Laragon ว่างเปล่า
$dbname = "db_hospital";

try {
    // เชื่อมต่อฐานข้อมูลด้วย PDO พร้อมตั้งค่าให้รองรับภาษาไทย utf8mb4
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // ตั้งค่าโหมดตรวจจับข้อผิดพลาด (Error Mode)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // ถ้าเชื่อมต่อไม่ได้ ให้แสดงข้อความแจ้งเตือน
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
?>