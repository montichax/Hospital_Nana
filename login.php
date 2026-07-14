<?php
require_once 'connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$redirectUrl = $_GET['redirect'] ?? 'admin.php';
$loginError = false;
$loginErrorMsg = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';

// ถ้า login แล้ว → ไปหน้า admin
if (!empty($_SESSION['is_admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// ตรวจรูปแบบ username/password แยกข้อความแจ้งเตือนตามสาเหตุ
// $allowThai: true สำหรับ username (อนุญาตภาษาไทย), false สำหรับ password (อังกฤษ/ตัวเลขเท่านั้น)
function validateLoginField($value, $label, $allowThai = false) {
    if ($value === '') return "กรุณากรอก{$label}";
    if (preg_match('/\s/u', $value)) return "{$label}ห้ามเว้นวรรค";
    $pattern = $allowThai ? '/^[A-Za-z0-9\x{0E01}-\x{0E5B}]+$/u' : '/^[A-Za-z0-9]+$/';
    if (!preg_match($pattern, $value)) return "{$label}ห้ามมีอักขระพิเศษ";
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $usernameError = validateLoginField($username, 'ชื่อผู้ใช้', true);
    $passwordError = validateLoginField($password, 'รหัสผ่าน');

    if ($usernameError || $passwordError) {
        $loginError = true;
        $loginErrorMsg = $usernameError ?: $passwordError;
    } else {
        $passwordHash = hash('sha256', $password);

        // ค้นหาผู้ใช้จากตาราง users
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && hash_equals($user['password_hash'], $passwordHash)) {
            // เก็บข้อมูล session
            $_SESSION['is_admin_logged_in'] = true;
            $_SESSION['user_id']         = (int)$user['id'];
            $_SESSION['username']        = $user['username'];
            $_SESSION['role']            = $user['role'];               // 'main' หรือ 'dept'
            $_SESSION['department_id']   = $user['department_id'] ? (int)$user['department_id'] : null;
            $_SESSION['display_name']    = $user['display_name'] ?: $user['username'];

            // ป้องกัน open-redirect
            $redirectUrl = trim($redirectUrl);
            if ($redirectUrl === '' || preg_match('/^(https?:)?\/\//i', $redirectUrl) || strpos($redirectUrl, 'login.php') !== false) {
                $redirectUrl = 'admin.php';
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        $loginError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-sm w-100" style="max-width: 420px;">
            <div class="card-body p-4">
                <h3 class="card-title text-center text-hospital mb-3">เข้าสู่ระบบผู้ดูแล</h3>
                <p class="text-center text-muted mb-4">กรุณาเข้าสู่ระบบเพื่อเข้าใช้งานส่วนจัดการเว็บไซต์</p>

                <?php if ($loginError): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($loginErrorMsg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php<?= !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '' ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-hospital-orange w-100">เข้าสู่ระบบ</button>
                </form>
                <div class="d-grid mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">กลับไปหน้าหลัก</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>