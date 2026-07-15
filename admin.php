<?php
// 🔗 1. เรียกใช้งานไฟล์เชื่อมต่อฐานข้อมูล MySQL
require_once 'connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['is_admin_logged_in'])) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect={$redirectUrl}");
    exit;
}

// ถ้า session เก่า (ไม่มี role) → บังคับ logout เพื่อไป login ใหม่
if (empty($_SESSION['role']) || empty($_SESSION['username'])) {
    header("Location: logout.php");
    exit;
}

// ===== Role-based access control =====
$user_role     = $_SESSION['role'] ?? 'main';                  // 'main' หรือ 'dept'
$user_dept_id  = $_SESSION['department_id'] ?? null;           // ถ้าเป็น dept admin
$is_main_admin = ($user_role === 'main');
$is_dept_admin = ($user_role === 'dept' && $user_dept_id);

// admin แผนก → เข้าได้แค่ tab dept_contents
if ($is_dept_admin) {
    $active_tab = 'dept_contents';
    // บังคับ dept_id ให้เป็นแผนกตัวเอง (ป้องกันแอบเข้าแผนกอื่นด้วย ?dept_id=)
    $_GET['dept_id'] = $user_dept_id;
} else {
    $active_tab = $_GET['tab'] ?? 'index_page';
}

// ฟังก์ชันตรวจสิทธิ์ก่อนแก้ department_contents
function assertDeptAccess($dept_id_target) {
    global $is_main_admin, $is_dept_admin, $user_dept_id;
    if ($is_main_admin) return true;
    if ($is_dept_admin && (int)$dept_id_target === (int)$user_dept_id) return true;
    http_response_code(403);
    die('ไม่มีสิทธิ์เข้าถึงแผนกนี้');
}

// ฟังก์ชันแปลงรูปแบบวันที่ ค.ศ. เป็น พ.ศ.
function dateToThaiText($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'ไม่ระบุวันที่';
    $time = strtotime($dateStr);
    if (!$time) return htmlspecialchars($dateStr);
    $d  = date('j', $time);
    $m  = date('n', $time);
    $y  = date('Y', $time) + 543;
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return "$d {$months[$m]} $y";
}

// ฟังก์ชันแปลงชื่อไฟล์เป็น array
function parseFileNames($fileData) {
    if (empty($fileData)) return [];
    $decoded = json_decode($fileData, true);
    if (is_array($decoded)) return $decoded;
    if (is_string($fileData) && !empty($fileData)) return [$fileData];
    return [];
}

// สร้าง <option> ของ dropdown "หมวดข้อมูล" — รวมหมวดของ "กลุ่มงาน/หน้าหลักที่เลือกไว้ด้านบน" (primary) + หน้าเนื้อหาทั่วไป (24 หน้า) ไว้ในที่เดียว
// $primary_sections/$primary_label = หมวดของแผนก (8 หมวด) หรือหมวดของหน้าหลัก (6 หมวด) แล้วแต่ว่า dropdown "กลุ่มงาน" ด้านบนเลือกอะไรไว้
// $include_all_option = false เมื่อใช้กับฟอร์ม "เพิ่มข้อมูล" (ต้องเลือกหมวดจริงเสมอ ไม่มี "ทั้งหมด")
function renderSectionOptions($primary_sections, $primary_label, $general_content_sections, $selected_dept_section, $selected_is_general, $selected_general_slug, $is_main_admin, $include_all_option = true) {
    $html = $include_all_option ? '<option value="">— ทั้งหมดของแผนกนี้ —</option>' : '';
    $html .= '<optgroup label="' . htmlspecialchars($primary_label) . '">';
    foreach ($primary_sections as $key => $label) {
        $sel = ($selected_dept_section === $key) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($key) . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
    }
    $html .= '</optgroup>';
    if ($is_main_admin) {
        $html .= '<optgroup label="หน้าเนื้อหาทั่วไป">';
        foreach ($general_content_sections as $slug => $label) {
            $sel = ($selected_is_general && $selected_general_slug === $slug) ? 'selected' : '';
            $html .= '<option value="g_' . htmlspecialchars($slug) . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
}

// หมวดเนื้อหาทั่วไป (key = ชื่อไฟล์ไม่รวม .php)
$general_content_sections = [
    // เกี่ยวกับกลุ่มงาน
    'executives'         => 'ทำเนียบหัวหน้ากลุ่มงาน',
    'ward_heads'         => 'ทำเนียบหัวหน้างาน',
    'personnel_gallery'  => 'รูปบุคลากร',

    // งานบริหาร
    'org_structure'      => 'โครงสร้างบริหาร',
    'risk_management'    => 'บริหารความเสี่ยง',
    'nursing_ethics'     => 'จริยธรรมการพยาบาล',

    // งานบริการ
    'supervision_results' => 'ผลการนิเทศ',

    // งานวิชาการ
    'dataset'            => 'Data Set',

    // คุณภาพการพยาบาล
    'kpi'                => 'ตัวชี้วัด',
    'service_profile'    => 'Service Profile',
    'cpg'                => 'CNPG',
    'wi'                 => 'WI',
    'research'           => 'วิจัย',

    // งานสารสนเทศ
    'staffing'           => 'อัตรากำลัง',
    'workload'           => 'ภาระงาน',

    // ข่าวประชาสัมพันธ์
    'news'               => 'ข่าวสารของแผนก',
    'meeting_reports'    => 'รายงานการประชุม',
];

// หมวดเนื้อหา "หน้าหลัก (index)" — เก็บใน department_contents โดย department_id = NULL, section ขึ้นต้นด้วย "idx_"
// จัดกลุ่มตามเมนูหลักของ index.php — ใช้ $index_page_groups สำหรับ <optgroup> ใน dropdown
$index_page_groups = [
    'เกี่ยวกับกลุ่มงาน' => [
        'idx_vision_mission' => 'วิสัยทัศน์ / พันธกิจ',
        'idx_nurse_roster'   => 'ทำเนียบหัวหน้าพยาบาล',
        'idx_executives'     => 'ทำเนียบหัวหน้ากลุ่มงาน',
        'idx_ward_heads'     => 'ทำเนียบหัวหน้างาน',
        'idx_personnel_gallery' => 'รูปบุคลากร',
    ],
    'งานบริหาร' => [
        'idx_org_structure'   => 'โครงสร้างบริหาร',
        'idx_regulations'     => 'คู่มือบริหาร',
        'idx_plans_projects'  => 'แผนยุทธศาสตร์การพยาบาล',
        'idx_staff_dev_plan'  => 'แผนพัฒนาบุคลากร',
        'idx_risk_management' => 'บริหารความเสี่ยง',
        'idx_nursing_ethics'  => 'จริยธรรมการพยาบาล',
    ],
    'งานบริการ' => [
        'idx_supervision_results'   => 'ผลการนิเทศ',
    ],
    'งานวิชาการ' => [
        'idx_dataset'   => 'Data set',
        'idx_downloads' => 'เอกสารดาวน์โหลด',
    ],
    'คุณภาพการพยาบาล' => [
        'idx_kpi'             => 'ตัวชี้วัดคุณภาพ',
        'idx_service_profile' => 'Service profile',
        'idx_cpg'             => 'CNPG',
        'idx_wi'              => 'WI',
        'idx_research'        => 'วิจัย',
    ],
    'งานสารสนเทศ' => [
        'idx_staffing'  => 'อัตรากำลัง',
        'idx_workload'  => 'ภาระงาน',
    ],
    'ข่าวสารประชาสัมพันธ์' => [
        'idx_news'            => 'ข่าวสาร',
        'idx_meeting_reports' => 'รายงานการประชุม',
    ],
];

$indexPageLinks = [
    // เกี่ยวกับกลุ่มงาน
    'idx_vision_mission'      => 'vission.php',
    'idx_nurse_roster'        => 'nurse_roster.php',
    'idx_executives'          => 'executives_index.php',
    'idx_ward_heads'          => 'ward_heads_index.php',
    'idx_personnel_gallery'   => 'personnel_gallery_index.php',

    // งานบริหาร
    'idx_org_structure'       => 'org_structure_index.php',
    'idx_regulations'         => 'regulations.php',
    'idx_plans_projects'      => 'plans_projects.php',
    'idx_staff_dev_plan'      => 'staff_dev_plan.php',
    'idx_risk_management'     => 'risk_management_index.php',
    'idx_nursing_ethics'      => 'nursing_ethics_index.php',

    // งานบริการ
    'idx_supervision_results'=> 'supervision_results_index.php',

    // งานวิชาการ
    'idx_dataset'             => 'dataset_index.php',
    'idx_downloads'           => 'downloads.php',

    // คุณภาพการพยาบาล
    'idx_kpi'                 => 'kpi_index.php',
    'idx_service_profile'     => 'service_profile_index.php',
    'idx_cpg'                 => 'cpg_index.php',
    'idx_wi'                  => 'wi_index.php',
    'idx_research'            => 'research_index.php',

    // งานสารสนเทศ
    'idx_staffing'            => 'staffing_index.php',
    'idx_workload'            => 'workload_index.php',

    // ข่าวประชาสัมพันธ์
    'idx_news'                => 'all_news.php',
    'idx_meeting_reports'     => 'meeting_reports_index.php'
];
// Flatten สำหรับ lookup
$index_page_sections = [];
foreach ($index_page_groups as $items) {
    $index_page_sections = array_merge($index_page_sections, $items);
}

// สร้างตาราง department_contents ถ้ายังไม่มี
// department_id เป็น NULL ได้ — ใช้เก็บ "เนื้อหาทั่วไป" (ไม่ผูกกับแผนกใดแผนกหนึ่ง) สำหรับหน้าเนื้อหากลางขององค์กร
$conn->exec("CREATE TABLE IF NOT EXISTS department_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NULL,
    section VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    file_name TEXT NULL,
    link_url VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_department_section (department_id, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $conn->exec("ALTER TABLE department_contents MODIFY department_id INT NULL");
} catch (Exception $e) { }

try {
    $conn->exec("ALTER TABLE department_contents MODIFY file_name TEXT NULL");
} catch (Exception $e) { }

// สร้างตาราง banners ถ้ายังไม่มี
$conn->exec("CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT NULL,
    image_name VARCHAR(500) NULL,
    link_url VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $conn->exec("ALTER TABLE banners ADD COLUMN department_id INT NULL AFTER id");
} catch (Exception $e) { }

// ---------- helpers ----------
function uploadAdminFile($fieldName, $prefix, $oldFile = '') {
    if (empty($_FILES[$fieldName]['name'])) return $oldFile;
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES[$fieldName]['name']));
    $fileName = time() . '_' . $prefix . '_' . $safeName;
    move_uploaded_file($_FILES[$fieldName]['tmp_name'], 'uploads/' . $fileName);
    return $fileName;
}

function uploadMultipleAdminFiles($fieldName, $prefix, $oldFiles = '') {
    if (empty($_FILES[$fieldName]['name'][0])) return $oldFiles;
    if (!is_dir('uploads')) mkdir('uploads', 0777, true);

    $existingFiles = [];
    if (!empty($oldFiles)) {
        $decoded = json_decode($oldFiles, true);
        if (is_array($decoded)) $existingFiles = $decoded;
        elseif (is_string($oldFiles) && $oldFiles !== 'default.jpg') $existingFiles = [$oldFiles];
    }

    $uploadedFiles = !empty($_POST['keep_old_files']) ? $existingFiles : [];

    if (is_array($_FILES[$fieldName]['name'])) {
        foreach ($_FILES[$fieldName]['name'] as $index => $fileName) {
            if (!empty($fileName)) {
                $safeName    = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($fileName));
                $newFileName = time() . '_' . $index . '_' . $prefix . '_' . $safeName;
                if (move_uploaded_file($_FILES[$fieldName]['tmp_name'][$index], 'uploads/' . $newFileName)) {
                    $uploadedFiles[] = $newFileName;
                }
            }
        }
    }

    if (count($uploadedFiles) === 0)  return $oldFiles;
    if (count($uploadedFiles) === 1)  return $uploadedFiles[0];
    return json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE);
}

// ==================== PROCESS ====================

// [1] ข่าวประชาสัมพันธ์ (news)
// [0] จัดการผู้ใช้ (users) — เฉพาะ main admin
if ($is_main_admin) {
    // เพิ่มผู้ใช้ใหม่
    if (isset($_POST['action_user']) && $_POST['action_user'] === 'create') {
        $u_username     = trim($_POST['u_username'] ?? '');
        $u_password     = $_POST['u_password'] ?? '';
        $u_role         = $_POST['u_role'] ?? 'dept';
        $u_department_id = ($u_role === 'dept') ? (int)($_POST['u_department_id'] ?? 0) : null;
        $u_display_name = trim($_POST['u_display_name'] ?? '');

        if ($u_username === '' || strlen($u_password) < 8) {
            $_SESSION['user_flash'] = ['type' => 'danger', 'msg' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านอย่างน้อย 8 ตัวอักษร'];
        } elseif ($u_role === 'dept' && $u_department_id <= 0) {
            $_SESSION['user_flash'] = ['type' => 'danger', 'msg' => 'กรุณาเลือกแผนกสำหรับ admin แผนก'];
        } else {
            try {
                $hash = hash('sha256', $u_password);
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, department_id, display_name) VALUES (:u, :p, :r, :d, :n)");
                $stmt->execute([
                    ':u' => $u_username, ':p' => $hash, ':r' => $u_role,
                    ':d' => $u_department_id, ':n' => $u_display_name ?: $u_username,
                ]);
                $_SESSION['user_flash'] = ['type' => 'success', 'msg' => "เพิ่มผู้ใช้ '$u_username' เรียบร้อยแล้ว"];
            } catch (PDOException $e) {
                $msg = ($e->getCode() == 23000) ? "ชื่อผู้ใช้ '$u_username' มีอยู่แล้วในระบบ" : "เกิดข้อผิดพลาด: " . $e->getMessage();
                $_SESSION['user_flash'] = ['type' => 'danger', 'msg' => $msg];
            }
        }
        header("Location: admin.php?tab=users");
        exit;
    }

    // เปลี่ยนรหัสผ่าน
    if (isset($_POST['action_user']) && $_POST['action_user'] === 'reset_password') {
        $uid = (int)($_POST['id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        if ($uid > 0 && strlen($new_password) >= 8) {
            $hash = hash('sha256', $new_password);
            $conn->prepare("UPDATE users SET password_hash = :p WHERE id = :id")->execute([':p' => $hash, ':id' => $uid]);
            $_SESSION['user_flash'] = ['type' => 'success', 'msg' => 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว'];
        } else {
            $_SESSION['user_flash'] = ['type' => 'danger', 'msg' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร'];
        }
        header("Location: admin.php?tab=users");
        exit;
    }

    // ลบผู้ใช้
    if (isset($_GET['del_user'])) {
        $uid = (int)$_GET['del_user'];
        if ($uid === (int)$_SESSION['user_id']) {
            $_SESSION['user_flash'] = ['type' => 'danger', 'msg' => 'ไม่สามารถลบบัญชีตนเองได้'];
        } else {
            $chk = $conn->prepare("SELECT role FROM users WHERE id = :id");
            $chk->execute([':id' => $uid]);
            $target = $chk->fetch(PDO::FETCH_ASSOC);
            if ($target && $target['role'] === 'main') {
                $cnt = $conn->query("SELECT COUNT(*) FROM users WHERE role='main'")->fetchColumn();
                if ((int)$cnt <= 1) {
                    $_SESSION['user_flash'] = ['type' => 'danger', 'msg' => 'ไม่สามารถลบ admin หลักคนสุดท้ายได้'];
                } else {
                    $conn->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $uid]);
                    $_SESSION['user_flash'] = ['type' => 'success', 'msg' => 'ลบผู้ใช้เรียบร้อยแล้ว'];
                }
            } else {
                $conn->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $uid]);
                $_SESSION['user_flash'] = ['type' => 'success', 'msg' => 'ลบผู้ใช้เรียบร้อยแล้ว'];
            }
        }
        header("Location: admin.php?tab=users");
        exit;
    }
}

// [0.5] หน้าหลัก (index_page) — เก็บใน department_contents โดย department_id = NULL
if (isset($_POST['action_index_page'])) {
    $idx_section  = $_POST['section'] ?? 'idx_news';
    if (!isset($index_page_sections[$idx_section])) $idx_section = 'idx_news';
    $sort_order   = max(1, (int)($_POST['sort_order'] ?? 1));
    $link_url     = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
    $file_name    = uploadMultipleAdminFiles('content_file', 'idx', $_POST['old_file'] ?? '');

    if ($_POST['action_index_page'] == 'create') {
        $stmt = $conn->prepare("INSERT INTO department_contents (department_id, section, title, content, file_name, link_url, sort_order) VALUES (NULL, :section, :title, :content, :file_name, :link_url, :sort_order)");
        $stmt->execute([':section' => $idx_section, ':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':file_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order]);
    } elseif ($_POST['action_index_page'] == 'update') {
        $stmt = $conn->prepare("UPDATE department_contents SET section=:section, title=:title, content=:content, file_name=:file_name, link_url=:link_url, sort_order=:sort_order WHERE id=:id AND department_id IS NULL");
        $stmt->execute([':section' => $idx_section, ':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':file_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=index_page&idx_section=" . urlencode($idx_section)); exit;
}
if (isset($_GET['del_index_page'])) {
    $conn->prepare("DELETE FROM department_contents WHERE id=:id AND department_id IS NULL")->execute([':id' => (int)$_GET['del_index_page']]);
    header("Location: admin.php?tab=index_page&idx_section=" . urlencode($_GET['idx_section'] ?? '')); exit;
}

// [1] ข่าว
if (isset($_POST['action_news'])) {
    $file_name    = uploadMultipleAdminFiles('image', 'news', $_POST['old_image'] ?? 'default.jpg');
    $is_new_status = isset($_POST['is_new']) ? 1 : 0;
    $created_at   = !empty($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d');
    $link_url     = !empty($_POST['link_url']) ? $_POST['link_url'] : null;

    if ($_POST['action_news'] == 'create') {
        $stmt = $conn->prepare("INSERT INTO news (title, content, created_at, image_name, is_new, link_url) VALUES (:title, :content, :created_at, :image_name, :is_new, :link_url)");
        $stmt->execute([':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':created_at' => $created_at, ':image_name' => $file_name, ':is_new' => $is_new_status, ':link_url' => $link_url]);
    } elseif ($_POST['action_news'] == 'update') {
        $stmt = $conn->prepare("UPDATE news SET title=:title, content=:content, created_at=:created_at, image_name=:image_name, is_new=:is_new, link_url=:link_url WHERE id=:id");
        $stmt->execute([':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':created_at' => $created_at, ':image_name' => $file_name, ':is_new' => $is_new_status, ':link_url' => $link_url, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=news"); exit;
}
if (isset($_GET['del_news'])) {
    $conn->prepare("DELETE FROM news WHERE id=:id")->execute([':id' => $_GET['del_news']]);
    header("Location: admin.php?tab=news"); exit;
}

// [2] หน่วยงาน (departments)
if (isset($_POST['action_dept'])) {
    $link_url = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
    if ($_POST['action_dept'] == 'create') {
        $conn->prepare("INSERT INTO departments (name, link_url) VALUES (:name, :link_url)")->execute([':name' => $_POST['name'], ':link_url' => $link_url]);
    } elseif ($_POST['action_dept'] == 'update') {
        $conn->prepare("UPDATE departments SET name=:name, link_url=:link_url WHERE id=:id")->execute([':name' => $_POST['name'], ':link_url' => $link_url, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=departments"); exit;
}
if (isset($_GET['del_dept'])) {
    if (!$is_main_admin) { http_response_code(403); die('เฉพาะ admin หลัก'); }
    $conn->prepare("DELETE FROM department_contents WHERE department_id=:id")->execute([':id' => $_GET['del_dept']]);
    $conn->prepare("DELETE FROM departments WHERE id=:id")->execute([':id' => $_GET['del_dept']]);
    header("Location: admin.php?tab=departments"); exit;
}

// [3] ข้อมูลรายกลุ่มงาน (dept_contents)
// section ที่ขึ้นต้นด้วย "g_" = หน้าเนื้อหาทั่วไป (ไม่ผูกกับแผนก) เช่น g_vision_mission -> บันทึก department_id เป็น NULL เสมอ
if (isset($_POST['action_dept_content'])) {
    $submitted_section = $_POST['section'] ?? 'knowledge';
    $is_general_submit  = $is_main_admin && strpos($submitted_section, 'g_') === 0 && isset($general_content_sections[substr($submitted_section, 2)]);

    if ($is_general_submit) {
        $department_id_db = null;
        $section = substr($submitted_section, 2);
    } else {
        $department_id = (int)($_POST['department_id'] ?? 0);
        // dept admin สามารถแก้ได้เฉพาะแผนกตัวเอง
        assertDeptAccess($department_id);
        $department_id_db = $department_id;
        $section = $submitted_section;
    }

    $sort_order    = max(1, (int)($_POST['sort_order'] ?? 1));
    $link_url      = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
    $file_name     = uploadMultipleAdminFiles('content_file', 'dept_content', $_POST['old_file'] ?? '');

    if ($_POST['action_dept_content'] == 'create') {
        $stmt = $conn->prepare("INSERT INTO department_contents (department_id, section, title, content, file_name, link_url, sort_order) VALUES (:department_id, :section, :title, :content, :file_name, :link_url, :sort_order)");
        $stmt->execute([':department_id' => $department_id_db, ':section' => $section, ':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':file_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order]);
    } elseif ($_POST['action_dept_content'] == 'update') {
        $stmt = $conn->prepare("UPDATE department_contents SET department_id=:department_id, section=:section, title=:title, content=:content, file_name=:file_name, link_url=:link_url, sort_order=:sort_order WHERE id=:id");
        $stmt->execute([':department_id' => $department_id_db, ':section' => $section, ':title' => $_POST['title'], ':content' => $_POST['content'] ?? '', ':file_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order, ':id' => $_POST['id']]);
    }
    $redirect_dept    = $is_general_submit ? (int)($_POST['department_id'] ?? 0) : $department_id_db;
    $redirect_section = $submitted_section; // เก็บ prefix g_ ไว้ตามเดิมถ้ามี เพื่อกลับไปหน้าเดิมที่กำลังกรองอยู่
    header("Location: admin.php?tab=dept_contents&dept_id=" . $redirect_dept . "&section=" . urlencode($redirect_section)); exit;
}
if (isset($_GET['del_dept_content'])) {
    // ตรวจสอบสิทธิ์ก่อนลบ
    $check_stmt = $conn->prepare("SELECT department_id FROM department_contents WHERE id = :id");
    $check_stmt->execute([':id' => (int)$_GET['del_dept_content']]);
    $check_row = $check_stmt->fetch(PDO::FETCH_ASSOC);
    if ($check_row) assertDeptAccess($check_row['department_id']);
    $redirect_dept    = (int)($_GET['dept_id'] ?? 0);
    $redirect_section = $_GET['section'] ?? '';
    $conn->prepare("DELETE FROM department_contents WHERE id=:id")->execute([':id' => $_GET['del_dept_content']]);
    header("Location: admin.php?tab=dept_contents&dept_id=" . $redirect_dept . "&section=" . urlencode($redirect_section)); exit;
}

// [4] Banner / Slider
if (isset($_POST['action_banner'])) {
    $file_name     = uploadAdminFile('banner_image', 'banner', $_POST['old_image'] ?? '');
    $is_active     = isset($_POST['is_active']) ? 1 : 0;
    $sort_order    = max(1, (int)($_POST['sort_order'] ?? 1));
    $link_url      = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;

    if ($_POST['action_banner'] == 'create') {
        $stmt = $conn->prepare("INSERT INTO banners (department_id, title, subtitle, image_name, link_url, sort_order, is_active) VALUES (:department_id, :title, :subtitle, :image_name, :link_url, :sort_order, :is_active)");
        $stmt->execute([':department_id' => $department_id, ':title' => $_POST['title'], ':subtitle' => $_POST['subtitle'] ?? '', ':image_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order, ':is_active' => $is_active]);
    } elseif ($_POST['action_banner'] == 'update') {
        $stmt = $conn->prepare("UPDATE banners SET department_id=:department_id, title=:title, subtitle=:subtitle, image_name=:image_name, link_url=:link_url, sort_order=:sort_order, is_active=:is_active WHERE id=:id");
        $stmt->execute([':department_id' => $department_id, ':title' => $_POST['title'], ':subtitle' => $_POST['subtitle'] ?? '', ':image_name' => $file_name ?: null, ':link_url' => $link_url, ':sort_order' => $sort_order, ':is_active' => $is_active, ':id' => $_POST['id']]);
    }
    header("Location: admin.php?tab=banners"); exit;
}
if (isset($_GET['del_banner'])) {
    $conn->prepare("DELETE FROM banners WHERE id=:id")->execute([':id' => $_GET['del_banner']]);
    header("Location: admin.php?tab=banners"); exit;
}

// ==================== FETCH DATA ====================
$news_items         = [];
$dept_items         = [];
$dept_content_items = [];
$banner_items       = [];
$index_page_items   = [];

$all_depts        = $conn->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$selected_dept_id = $is_dept_admin
    ? (int)$user_dept_id
    : (int)($_GET['dept_id'] ?? ($all_depts[0]['id'] ?? 0));

// "หมวดข้อมูล" ที่เลือกไว้ล่าสุด — ใช้กรองตารางด้านล่างด้วย ว่างไว้ = โชว์ทุกหมวดของแผนกที่เลือก (ของเดิม)
// ค่าที่ขึ้นต้นด้วย "g_" = หน้าเนื้อหาทั่วไป (ไม่ผูกกับแผนก) เช่น g_vision_mission
$selected_section       = (string)($_GET['section'] ?? '');
$selected_is_general    = $is_main_admin && strpos($selected_section, 'g_') === 0 && isset($general_content_sections[substr($selected_section, 2)]);
$selected_general_slug  = $selected_is_general ? substr($selected_section, 2) : '';
$selected_dept_section  = (!$selected_is_general && $selected_section !== '' && isset($department_content_sections[$selected_section])) ? $selected_section : '';

// --- หมวดที่เลือกของ tab "หน้าหลัก" ---
$selected_idx_section = (string)($_GET['idx_section'] ?? '');
$selected_idx_section = isset($index_page_sections[$selected_idx_section]) ? $selected_idx_section : '';

if ($active_tab == 'index_page') {
    if ($selected_idx_section !== '') {
        $stmt = $conn->prepare("SELECT * FROM department_contents WHERE department_id IS NULL AND section = :section ORDER BY sort_order ASC, id DESC");
        $stmt->execute([':section' => $selected_idx_section]);
    } else {
        // แสดงทุกหมวดของหน้าหลัก
        $idx_keys = array_keys($index_page_sections);
        $placeholders = implode(',', array_fill(0, count($idx_keys), '?'));
        $stmt = $conn->prepare("SELECT * FROM department_contents WHERE department_id IS NULL AND section IN ($placeholders) ORDER BY section ASC, sort_order ASC, id DESC");
        $stmt->execute($idx_keys);
    }
    $index_page_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'news') {
    $news_items = $conn->query("SELECT * FROM news ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'departments') {
    $dept_items = $conn->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'dept_contents' && $selected_is_general) {
    // เนื้อหาทั่วไปของหน้าที่เลือก (department_id เป็น NULL ในฐานข้อมูล) — ไม่เกี่ยวกับแผนกที่เลือกไว้ด้านบน
    $stmt = $conn->prepare("SELECT dc.*, NULL AS department_name FROM department_contents dc WHERE dc.department_id IS NULL AND dc.section = :section ORDER BY dc.sort_order ASC, dc.id DESC");
    $stmt->execute([':section' => $selected_general_slug]);
    $dept_content_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'dept_contents' && $selected_dept_id > 0 && $selected_dept_section !== '') {
    // เฉพาะหมวดที่เลือกของแผนกนี้
    $stmt = $conn->prepare("SELECT dc.*, d.name AS department_name FROM department_contents dc INNER JOIN departments d ON d.id = dc.department_id WHERE dc.department_id = :department_id AND dc.section = :section ORDER BY dc.sort_order ASC, dc.id DESC");
    $stmt->execute([':department_id' => $selected_dept_id, ':section' => $selected_dept_section]);
    $dept_content_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'dept_contents' && $selected_dept_id > 0) {
    // ไม่ได้กรองหมวด -> โชว์ทุกหมวดของแผนกนี้รวมกัน (ของเดิม)
    $stmt = $conn->prepare("SELECT dc.*, d.name AS department_name FROM department_contents dc INNER JOIN departments d ON d.id = dc.department_id WHERE dc.department_id = :department_id ORDER BY dc.section ASC, dc.sort_order ASC, dc.id DESC");
    $stmt->execute([':department_id' => $selected_dept_id]);
    $dept_content_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($active_tab == 'banners') {
    $banner_items = $conn->query("SELECT b.*, d.name AS department_name FROM banners b LEFT JOIN departments d ON d.id = b.department_id ORDER BY b.sort_order ASC, b.id ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* ============================================
           Toggle switch "เปิดแสดงป้ายใหม่" — แก้ปัญหา toggle ทะลุพื้น
           ============================================ */
        .form-switch .form-check-input {
            width: 2.5em !important;
            height: 1.25em !important;
            margin-top: 0.25em;
            background-color: #dee2e6 !important;
            border-color: #adb5bd !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
            cursor: pointer;
        }
        .form-switch .form-check-input:checked {
            background-color: var(--hosp-orange, #f26722) !important;
            border-color: var(--hosp-orange, #f26722) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
        }
        .form-switch .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(242, 103, 34, 0.25) !important;
        }
        .form-switch-indented { padding-left: 3rem !important; }
        .form-switch-indented .form-check-input { margin-left: -2.5em !important; }

        /* ============================================
           สไตล์ตาราง Admin — ให้ทุก tab (news/dept/banner/dept_contents) ดูเหมือนกัน
           ============================================ */

        /* พื้นและ padding — ให้ทุกเซลล์เว้นระยะสบายตา */
        .admin-table-scroll table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .admin-table-scroll table thead th {
            background-color: #f8f9fa !important;
            color: #495057;
            font-size: 13.5px;
            font-weight: 700;
            padding: 14px 12px !important;
            border-bottom: 2px solid #dee2e6 !important;
            border-top: none !important;
            white-space: nowrap;
            vertical-align: middle;
        }
        .admin-table-scroll table tbody td {
            padding: 16px 12px !important;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0 !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            font-size: 14px;
        }

        /* Zebra striping ที่นุ่มนวลกว่า table-striped ของ Bootstrap */
        .admin-table-scroll table tbody tr:nth-of-type(odd) td {
            background-color: #fafbfc;
        }
        .admin-table-scroll table tbody tr:nth-of-type(even) td {
            background-color: #ffffff;
        }
        .admin-table-scroll table tbody tr:hover td {
            background-color: #fdf5ee !important;
            transition: background-color 0.15s ease;
        }

        /* คอลัมน์แรก (ที่มีรูป/ไฟล์) — จำกัดความกว้าง */
        .admin-table-scroll table td:first-child { max-width: 480px; }

        /* คอลัมน์ "จัดการ" (ปุ่ม) */
        .admin-table-scroll table td.actions-cell,
        .admin-table-scroll table th.actions-cell,
        .admin-table-scroll table td:last-child,
        .admin-table-scroll table th:last-child {
            white-space: nowrap;
            min-width: 130px;
            text-align: center;
        }

        /* ปุ่มแก้ไข/ลบ — เว้นระยะเท่ากัน */
        .admin-table-scroll table .btn-sm {
            margin: 2px;
            padding: 5px 12px;
            font-size: 13px;
            font-weight: 600;
        }

        /* พรีวิวเนื้อหาข่าว (ตัดที่ 100 ตัวอักษร แสดง 2 บรรทัด) */
        .text-preview-short {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.45;
            max-width: 600px;
            color: #6c757d;
            margin-top: 4px;
        }

        /* ลิงก์ URL ยาว — ตัดด้วย ellipsis */
        .text-preview-link {
            max-width: 380px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: bottom;
        }

        /* ไฟล์แนบและลิงก์ในคอลัมน์ "ไฟล์/ลิงก์" — ไม่ให้ underline ยาวๆ */
        .admin-table-scroll a { text-decoration: none; }
        .admin-table-scroll a:hover { text-decoration: underline; }
        .admin-file-link { max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* หัวข้อ (title) ในแต่ละแถว — เด่นและอ่านง่าย */
        .admin-table-scroll table td strong {
            font-size: 14.5px;
            color: #212529;
            display: block;
            margin-bottom: 2px;
        }

        /* ป้าย badge (หมวด, สถานะใหม่) — สม่ำเสมอ */
        .admin-table-scroll table .badge,
        .admin-table-scroll table .badge-orange-style,
        .admin-table-scroll table .dept-content-section-badge {
            font-size: 11.5px;
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .admin-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .admin-table-scroll table { min-width: 640px; }
            .admin-table-scroll table thead th,
            .admin-table-scroll table tbody td { padding: 10px 8px !important; font-size: 13px; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="mb-0 text-hospital fw-bold">ระบบจัดการข้อมูลเว็บไซต์ (Admin Dashboard)</h2>
            <small class="text-muted">
                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']) ?>
                <?php if ($is_main_admin): ?>
                    <span class="badge bg-danger ms-1">Admin หลัก</span>
                <?php else: ?>
                    <span class="badge-orange-style ms-1">Admin แผนก</span>
                <?php endif; ?>
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="logout.php" class="btn btn-outline-secondary">ออกจากระบบ</a>
        </div>
    </div>

    <ul class="nav nav-tabs" id="hospitalTabs">
        <?php if ($is_main_admin): ?>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'index_page' ? 'active' : '' ?>" href="?tab=index_page">
                <i class="bi bi-house-door-fill fs-5 icon-news"></i> หน้าหลัก (Index)
            </a>
        </li>
        <?php endif; ?>
        <?php if ($is_main_admin): ?>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'departments' ? 'active' : '' ?>" href="?tab=departments">
                <i class="bi bi-building-fill fs-5 icon-dept"></i> หอผู้ป่วย / หน่วยงาน
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'dept_contents' ? 'active' : '' ?>" href="?tab=dept_contents">
                <i class="bi bi-folder2-open fs-5 icon-dept"></i> ข้อมูลรายกลุ่มงาน
            </a>
        </li>
        <?php if ($is_main_admin): ?>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'banners' ? 'active' : '' ?>" href="?tab=banners">
                <i class="bi bi-image fs-5"></i> Banner / Slider
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'users' ? 'active' : '' ?>" href="?tab=users">
                <i class="bi bi-people-fill fs-5"></i> จัดการผู้ใช้
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content bg-white p-4 border rounded-bottom shadow-sm">

        <?php if($active_tab == 'index_page'): ?>
        <div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="text-hospital mb-0 fw-bold"><i class="bi bi-house-door-fill me-1"></i>จัดการเนื้อหาหน้าหลัก (Index)</h5>
                <a href="index.php" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-up-right"></i> เปิดหน้าหลัก</a>
            </div>

            <div class="admin-form-container">
                <!-- Dropdown กรองหมวด -->
                <form method="GET" class="row g-2 mb-3">
                    <input type="hidden" name="tab" value="index_page">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">หมวดข้อมูล</label>
                        <select name="idx_section" class="form-select" onchange="this.form.submit()">
                            <option value="">— ทั้งหมด —</option>
                            <?php foreach ($index_page_groups as $group_name => $items): ?>
                                <optgroup label="<?= htmlspecialchars($group_name) ?>">
                                <?php foreach ($items as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $selected_idx_section === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <!-- ฟอร์มเพิ่ม/แก้ไข -->
                <?php
                    $idx_create_section = $selected_idx_section !== '' ? $selected_idx_section : array_key_first($index_page_sections);
                ?>
                <form id="indexPageForm" action="admin.php?tab=index_page&idx_section=<?= urlencode($selected_idx_section) ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action_index_page" id="idx_action" value="create">
                    <input type="hidden" name="id" id="idx_id">
                    <input type="hidden" name="old_file" id="idx_old_file">
                    <input type="hidden" name="section" id="idx_section_select" value="<?= htmlspecialchars($idx_create_section) ?>">

                    <div class="row g-2 mb-2">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">หัวข้อ</label>
                            <input type="text" name="title" id="idx_title" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ลำดับแสดงผล</label>
                            <input type="number" name="sort_order" id="idx_sort_order" class="form-control" value="1" min="1" step="1">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">ลิงก์ภายนอก (ถ้ามี)</label>
                        <input type="url" name="link_url" id="idx_link_url" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">รายละเอียด</label>
                        <textarea name="content" id="idx_content" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">ไฟล์แนบ / รูปภาพ / วิดีโอ</label>
                            <input type="file" name="content_file[]" class="form-control" accept="image/*,application/pdf,video/*,.doc,.docx,.xls,.xlsx,.ppt,.pptx" multiple>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" id="idx_submit" class="btn btn-hospital-orange flex-fill">+ เพิ่มข้อมูล</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetIndexPageForm()">ล้างฟอร์ม</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ตารางแสดงข้อมูล -->
            <div class="admin-table-scroll dept-content-scroll mt-4">
            <table class="table table-striped align-middle dept-content-table mb-0">
                <colgroup>
                    <col class="col-section">
                    <col class="col-title">
                    <col class="col-file">
                    <col class="col-order">
                    <col class="col-action">
                </colgroup>
                <thead>
                    <tr><th>หมวด</th><th>หัวข้อ / รายละเอียด</th><th>ไฟล์ / ลิงก์</th><th>ลำดับ</th><th>จัดการ</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($index_page_items)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลหน้าหลัก</td></tr>
                    <?php endif; ?>
                    <?php foreach($index_page_items as $row):
                        $content_file  = $row['file_name'] ?? '';
                        $content_files = parseFileNames($content_file);
                        $content_link  = $row['link_url'] ?? '';
                        $section_label = $index_page_sections[$row['section']] ?? $row['section'];
                        $edit_payload  = [
                            'id'         => (int)$row['id'],
                            'section'    => $row['section'],
                            'title'      => $row['title'],
                            'content'    => $row['content'] ?? '',
                            'file_name'  => $content_file,
                            'link_url'   => $content_link,
                            'sort_order' => (int)$row['sort_order']
                        ];
                    ?>
                    <tr>
                        <td><span class="badge bg-secondary dept-content-section-badge"><?= htmlspecialchars($section_label) ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                            <div class="small text-muted text-preview-short"><?php
                                $dc_preview = mb_substr($row['content'] ?? '', 0, 100, 'UTF-8');
                                echo htmlspecialchars($dc_preview);
                                if (mb_strlen($row['content'] ?? '', 'UTF-8') > 100) echo '...';
                            ?></div>
                        </td>
                        <td class="dept-content-file-cell">
                            <?php foreach($content_files as $file):
                                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                $fname_no_ext = pathinfo($file, PATHINFO_FILENAME);
                                $short = mb_strlen($fname_no_ext, 'UTF-8') > 30 ? mb_substr($fname_no_ext, 0, 30, 'UTF-8') . '…' : $fname_no_ext;
                                $display = $short . ($ext ? '.' . $ext : '');
                                if     (in_array($ext, ['pdf']))                    { $ic='bi-file-earmark-pdf-fill';    $cl='text-danger';  }
                                elseif (in_array($ext, ['doc','docx']))             { $ic='bi-file-earmark-word-fill';   $cl='text-primary'; }
                                elseif (in_array($ext, ['xls','xlsx','csv']))       { $ic='bi-file-earmark-excel-fill';  $cl='text-success'; }
                                elseif (in_array($ext, ['ppt','pptx']))             { $ic='bi-file-earmark-slides-fill'; $cl='text-warning'; }
                                elseif (in_array($ext, ['mp4','webm','ogg','mov'])) { $ic='bi-file-earmark-play-fill';   $cl='text-hospital';}
                                elseif (in_array($ext, ['jpg','jpeg','png','gif','webp'])) { $ic='bi-file-earmark-image-fill'; $cl='text-info'; }
                                else                                                { $ic='bi-paperclip';                $cl='text-hospital';}
                            ?>
                               <div class="mb-1">
                                   <a href="uploads/<?= htmlspecialchars($file) ?>" target="_blank" title="<?= htmlspecialchars($file) ?>" class="text-decoration-none">
                                       <i class="bi <?= $ic ?> <?= $cl ?>"></i> <?= htmlspecialchars($display) ?>
                                   </a>
                               </div>
                            <?php endforeach; ?>
                            <?php if(!empty($content_link)):
                                $link_short = mb_strlen($content_link, 'UTF-8') > 50 ? mb_substr($content_link, 0, 50, 'UTF-8') . '…' : $content_link;
                            ?>
                                <div class="admin-file-link"><i class="bi bi-link-45deg text-primary"></i> <a href="<?= htmlspecialchars($content_link) ?>" target="_blank" title="<?= htmlspecialchars($content_link) ?>" class="text-decoration-none"><?= htmlspecialchars($link_short) ?></a></div>
                            <?php endif; ?>
                            <?php if(empty($content_files) && empty($content_link)): ?>
                                <span class="text-muted small">ไม่มีไฟล์/ลิงก์</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$row['sort_order'] ?></td>
                        <td width="16%">
                            <button type="button" class="btn btn-outline-edit-style btn-sm me-1" onclick='editIndexPage(<?= json_encode($edit_payload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>แก้ไข</button>
                            <a href="admin.php?tab=index_page&idx_section=<?= urlencode($selected_idx_section) ?>&del_index_page=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('ต้องการลบข้อมูลนี้หรือไม่?')">ลบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if($active_tab == 'departments'): ?>
        <div>
            <h5 class="text-hospital mb-3 fw-bold">จัดการหอผู้ป่วย / หน่วยงาน</h5>
            <form action="admin.php?tab=departments" method="POST" class="admin-form-container">
                <input type="hidden" name="action_dept" value="create">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5"><input type="text" name="name" class="form-control" placeholder="ชื่อหอผู้ป่วย/หน่วยงาน" required></div>
                    <div class="col-md-5"><input type="url" name="link_url" class="form-control" placeholder="ลิงก์หน้าเว็บประจำแผนก"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-hospital-orange w-100">+ เพิ่มหน่วยงาน</button></div>
                </div>
            </form>

            <div class="admin-table-scroll mt-4">
            <table class="table align-middle mb-0" style="min-width: 600px;">
                <thead><tr><th>ชื่อหน่วยงาน / หอผู้ป่วย</th><th>ลิงก์ประจำแผนก</th><th style="white-space: nowrap; width: 140px;">จัดการ</th></tr></thead>
                <tbody>
                    <?php foreach($dept_items as $row):
                        $dept_link = $row['link_url'] ?? '';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php if(!empty($dept_link)): ?>
                                <i class="bi bi-link-45deg text-primary"></i>
                                <a href="<?= htmlspecialchars($dept_link) ?>" target="_blank" class="text-decoration-none" title="<?= htmlspecialchars($dept_link) ?>"><?= htmlspecialchars($dept_link) ?></a>
                            <?php else: ?>
                                <span class="text-muted small">ไม่ได้ระบุลิงก์</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap; width: 140px;">
                            <button class="btn btn-outline-edit-style btn-sm me-1" onclick='editDept(<?= (int)$row["id"] ?>, <?= json_encode($row["name"], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($dept_link, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>)'>แก้ไข</button>
                            <a href="admin.php?tab=departments&del_dept=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?')">ลบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if($active_tab == 'dept_contents'): ?>
        <div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="text-hospital mb-0 fw-bold">จัดการข้อมูลรายกลุ่มงาน</h5>
                <?php
                    $selected_dept_link = '';
                    foreach ($all_depts as $d) {
                        if ((int)$d['id'] === $selected_dept_id) { $selected_dept_link = $d['link_url'] ?? ''; break; }
                    }
                    $preview_link = $selected_is_general ? ($selected_general_slug . '.php') : $selected_dept_link;
                ?>
                <?php if($preview_link !== ''): ?>
                    <a href="<?= htmlspecialchars($preview_link) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box-arrow-up-right"></i> <?= $selected_is_general ? 'เปิดหน้านี้' : 'เปิดหน้ากลุ่มงาน' ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if(empty($all_depts)): ?>
                <div class="alert alert-warning">กรุณาเพิ่มข้อมูลหอผู้ป่วย / หน่วยงานก่อน</div>
            <?php else: ?>

            <div class="admin-form-container">
                <?php if ($is_main_admin): ?>
                <form method="GET" class="row g-2">
                    <input type="hidden" name="tab" value="dept_contents">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">กลุ่มงาน</label>
                        <select name="dept_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach($all_depts as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= (int)$dept['id'] === $selected_dept_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">หมวดข้อมูล</label>
                        <select name="section" class="form-select" onchange="this.form.submit()">
                            <?= renderSectionOptions($department_content_sections, 'หมวดของแผนก', $general_content_sections, $selected_dept_section, $selected_is_general, $selected_general_slug, $is_main_admin) ?>
                        </select>
                    </div>
                </form>
                <?php else: ?>
                    <?php
                    $my_dept_name = '';
                    foreach ($all_depts as $dept) {
                        if ((int)$dept['id'] === (int)$selected_dept_id) { $my_dept_name = $dept['name']; break; }
                    }
                    ?>
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-lock-fill fs-4"></i>
                        <div>
                            คุณกำลังจัดการข้อมูลของแผนก <strong><?= htmlspecialchars($my_dept_name) ?></strong>
                            (Admin แผนกสามารถแก้ไขได้เฉพาะแผนกของตัวเองเท่านั้น)
                        </div>
                    </div>
                    <form method="GET" class="row g-2">
                        <input type="hidden" name="tab" value="dept_contents">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">หมวดข้อมูล</label>
                            <select name="section" class="form-select" onchange="this.form.submit()">
                                <?= renderSectionOptions($department_content_sections, 'หมวดของแผนก', $general_content_sections, $selected_dept_section, $selected_is_general, $selected_general_slug, $is_main_admin) ?>
                            </select>
                        </div>
                    </form>
                <?php endif; ?>


                <?php
                    // ใช้หมวดที่เลือกไว้ใน dropdown "หมวดข้อมูล" ด้านบนเป็นหมวดของรายการใหม่โดยตรง ไม่ต้องมีช่องเลือกซ้ำ
                    // ถ้ายังไม่ได้กรอง (ดูอยู่แบบ "ทั้งหมดของแผนกนี้") ให้ตั้งค่าเริ่มต้นเป็นหมวดแรกของแผนก
                    if ($selected_is_general) {
                        $create_section_value = 'g_' . $selected_general_slug;
                    } elseif ($selected_dept_section !== '') {
                        $create_section_value = $selected_dept_section;
                    } else {
                        $create_section_value = array_key_first($department_content_sections);
                    }
                ?>
                <form id="deptContentForm" action="admin.php?tab=dept_contents&dept_id=<?= $selected_dept_id ?>&section=<?= urlencode($selected_section) ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action_dept_content" id="dept_content_action" value="create">
                    <input type="hidden" name="id" id="dept_content_id">
                    <input type="hidden" name="old_file" id="dept_content_old_file">

                    <input type="hidden" name="department_id" id="dept_content_department_id" value="<?= $selected_dept_id ?>">
                    <input type="hidden" name="section" id="dept_content_section" value="<?= htmlspecialchars($create_section_value) ?>">

                    <div class="row g-2 mb-2">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">หัวข้อ</label>
                            <input type="text" name="title" id="dept_content_title" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ลำดับแสดงผล</label>
                            <input type="number" name="sort_order" id="dept_content_sort_order" class="form-control" value="1" min="1" step="1">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">ลิงก์ภายนอก (ถ้ามี)</label>
                        <input type="url" name="link_url" id="dept_content_link_url" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">รายละเอียด</label>
                        <textarea name="content" id="dept_content_content" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">ไฟล์แนบ / รูปภาพ / วิดีโอ</label>
                            <input type="file" name="content_file[]" class="form-control" accept="image/*,application/pdf,video/*,.doc,.docx,.xls,.xlsx,.ppt,.pptx" multiple>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" id="dept_content_submit" class="btn btn-hospital-orange flex-fill">+ เพิ่มข้อมูล</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetDeptContentForm()">ล้างฟอร์ม</button>
                        </div>
                    </div>
                </form>
            </div>

                <div class="admin-table-scroll dept-content-scroll mt-4">
                <table class="table table-striped align-middle dept-content-table mb-0">
                    <colgroup>
                        <col class="col-section">
                        <col class="col-title">
                        <col class="col-file">
                        <col class="col-order">
                        <col class="col-action">
                    </colgroup>
                    <thead>
                        <tr><th>หมวด</th><th>หัวข้อ / รายละเอียด</th><th>ไฟล์ / ลิงก์</th><th>ลำดับ</th><th>จัดการ</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($dept_content_items)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลรายกลุ่มงานนี้</td></tr>
                        <?php endif; ?>
                        <?php foreach($dept_content_items as $row):
                            $content_file  = $row['file_name'] ?? '';
                            $content_files = parseFileNames($content_file);
                            $content_link  = $row['link_url'] ?? '';
                            $section_label = $selected_is_general
                                ? ($general_content_sections[$row['section']] ?? $row['section'])
                                : ($department_content_sections[$row['section']] ?? $row['section']);
                            $edit_payload  = [
                                'id'            => (int)$row['id'],
                                'department_id' => (int)$row['department_id'],
                                // แถวที่ department_id เป็น NULL (เนื้อหาทั่วไป) ต้องใส่ prefix "g_" ให้ตรงกับ value ใน dropdown "หมวดข้อมูล"
                                'section'       => ($row['department_id'] === null ? 'g_' . $row['section'] : $row['section']),
                                'title'         => $row['title'],
                                'content'       => $row['content'] ?? '',
                                'file_name'     => $content_file,
                                'link_url'      => $content_link,
                                'sort_order'    => (int)$row['sort_order']
                            ];
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary dept-content-section-badge"><?= htmlspecialchars($section_label) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($row['title']) ?></strong>
                                <div class="small text-muted text-preview-short"><?php
                                    $dc_preview = mb_substr($row['content'] ?? '', 0, 100, 'UTF-8');
                                    echo htmlspecialchars($dc_preview);
                                    if (mb_strlen($row['content'] ?? '', 'UTF-8') > 100) echo '...';
                                ?></div>
                            </td>
                            <td class="dept-content-file-cell">
                                <?php foreach($content_files as $file):
                                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    // ตัดชื่อไฟล์ให้เหลือ 30 ตัวอักษร + นามสกุลไฟล์
                                    $fname_no_ext = pathinfo($file, PATHINFO_FILENAME);
                                    $short = mb_strlen($fname_no_ext, 'UTF-8') > 30
                                        ? mb_substr($fname_no_ext, 0, 30, 'UTF-8') . '…'
                                        : $fname_no_ext;
                                    $display = $short . ($ext ? '.' . $ext : '');
                                    // เลือกไอคอนตามชนิดไฟล์
                                    if     (in_array($ext, ['pdf']))                    { $ic='bi-file-earmark-pdf-fill';    $cl='text-danger';  }
                                    elseif (in_array($ext, ['doc','docx']))             { $ic='bi-file-earmark-word-fill';   $cl='text-primary'; }
                                    elseif (in_array($ext, ['xls','xlsx','csv']))       { $ic='bi-file-earmark-excel-fill';  $cl='text-success'; }
                                    elseif (in_array($ext, ['ppt','pptx']))             { $ic='bi-file-earmark-slides-fill'; $cl='text-warning'; }
                                    elseif (in_array($ext, ['mp4','webm','ogg','mov'])) { $ic='bi-file-earmark-play-fill';   $cl='text-hospital';}
                                    elseif (in_array($ext, ['jpg','jpeg','png','gif','webp'])) { $ic='bi-file-earmark-image-fill'; $cl='text-info'; }
                                    else                                                { $ic='bi-paperclip';                $cl='text-hospital';}
                                ?>
                                   <div class="mb-1">
                                       <a href="uploads/<?= htmlspecialchars($file) ?>" target="_blank" title="<?= htmlspecialchars($file) ?>" class="text-decoration-none">
                                           <i class="bi <?= $ic ?> <?= $cl ?>"></i> <?= htmlspecialchars($display) ?>
                                       </a>
                                   </div>
                                <?php endforeach; ?>
                                <?php if(!empty($content_link)):
                                    $link_short = mb_strlen($content_link, 'UTF-8') > 50
                                        ? mb_substr($content_link, 0, 50, 'UTF-8') . '…'
                                        : $content_link;
                                ?>
                                    <div class="admin-file-link"><i class="bi bi-link-45deg text-primary"></i> <a href="<?= htmlspecialchars($content_link) ?>" target="_blank" title="<?= htmlspecialchars($content_link) ?>" class="text-decoration-none"><?= htmlspecialchars($link_short) ?></a></div>
                                <?php endif; ?>
                                <?php if(empty($content_files) && empty($content_link)): ?>
                                    <span class="text-muted small">ไม่มีไฟล์/ลิงก์</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$row['sort_order'] ?></td>
                            <td width="16%">
                                <button type="button" class="btn btn-outline-edit-style btn-sm me-1" onclick='editDeptContent(<?= json_encode($edit_payload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>แก้ไข</button>
                                <a href="admin.php?tab=dept_contents&dept_id=<?= $selected_dept_id ?>&section=<?= urlencode($selected_section) ?>&del_dept_content=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('ต้องการลบข้อมูลนี้หรือไม่?')">ลบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if($active_tab == 'banners'): ?>
        <div>
            <h5 class="text-hospital mb-3 fw-bold">จัดการ Banner / Slider</h5>
            <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> Banner จะแสดงเป็น Slider — เลือก "หน้าแรกของเว็บไซต์" หรือเลือกแผนก เพื่อให้ Banner แสดงเฉพาะหน้าแผนกนั้น เรียงตามลำดับที่กำหนด</p>

            <form action="admin.php?tab=banners" method="POST" enctype="multipart/form-data" class="admin-form-container">
                <input type="hidden" name="action_banner" value="create">
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">หัวข้อ Banner <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="หัวข้อที่แสดงบน Slider" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">แสดงที่</label>
                        <select name="department_id" class="form-select">
                            <option value="">หน้าแรกของเว็บไซต์</option>
                            <?php foreach($all_depts as $dept): ?>
                                <option value="<?= (int)$dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">ลำดับแสดง</label>
                        <input type="number" name="sort_order" class="form-control" value="1" min="1">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">ลิงก์ปุ่ม "อ่านเพิ่มเติม"</label>
                        <input type="url" name="link_url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">คำบรรยาย (Subtitle)</label>
                        <input type="text" name="subtitle" class="form-control" placeholder="คำบรรยายใต้หัวข้อ Banner">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">รูปภาพ Banner</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/*">
                        <div class="form-text">แนะนำขนาด 1920×600 px หรืออัตราส่วน 16:5</div>
                    </div>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <div class="form-check form-switch p-2 border rounded bg-white">
                            <input class="form-check-input ms-1" type="checkbox" name="is_active" id="banner_is_active" value="1" checked>
                            <label class="form-check-label ms-2" for="banner_is_active">เปิดแสดง Banner นี้บนหน้าแรก</label>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="submit" class="btn btn-hospital-orange w-100">+ เพิ่ม Banner</button>
                    </div>
                </div>
            </form>

            <div class="admin-table-scroll mt-4">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="12%">รูปภาพ</th>
                        <th>หัวข้อ / คำบรรยาย / ลิงก์</th>
                        <th width="12%">แสดงที่</th>
                        <th width="8%">ลำดับ</th>
                        <th width="9%">สถานะ</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($banner_items)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มี Banner — กรุณาเพิ่มด้านบน</td></tr>
                    <?php endif; ?>
                    <?php foreach($banner_items as $row):
                        $img = $row['image_name'] ?? '';
                    ?>
                    <tr>
                        <td>
                            <?php if(!empty($img)): ?>
                                <img src="uploads/<?= htmlspecialchars($img) ?>" class="banner-thumb" onerror="this.src='https://placehold.co/100x60?text=No+Img'">
                            <?php else: ?>
                                <span class="badge bg-secondary">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                            <?php if(!empty($row['subtitle'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars($row['subtitle']) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($row['link_url'])): ?>
                                <div class="small mt-1"><i class="bi bi-link-45deg text-primary"></i> <a href="<?= htmlspecialchars($row['link_url']) ?>" target="_blank" class="text-decoration-none"><?= htmlspecialchars($row['link_url']) ?></a></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!empty($row['department_name'])): ?>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($row['department_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">หน้าแรก</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= (int)$row['sort_order'] ?></td>
                        <td>
                            <?php if((int)$row['is_active'] === 1): ?>
                                <span class="badge bg-success">แสดง</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">ซ่อน</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-outline-edit-style btn-sm me-1"
                                onclick='editBanner(<?= (int)$row["id"] ?>, <?= json_encode($row["title"], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($row["subtitle"] ?? "", JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($row["image_name"] ?? "", JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($row["link_url"] ?? "", JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>, <?= (int)$row["sort_order"] ?>, <?= (int)$row["is_active"] ?>, <?= (int)($row["department_id"] ?? 0) ?>)'>
                                แก้ไข
                            </button>
                            <a href="admin.php?tab=banners&del_banner=<?= $row['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('ต้องการลบ Banner นี้หรือไม่?')">ลบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if($active_tab == 'users' && $is_main_admin):
            $all_users = $conn->query("SELECT u.*, d.name AS dept_name FROM users u LEFT JOIN departments d ON d.id = u.department_id ORDER BY u.role ASC, u.id ASC")->fetchAll(PDO::FETCH_ASSOC);
            $u_flash = $_SESSION['user_flash'] ?? null;
            unset($_SESSION['user_flash']);
        ?>
        <div>
            <h5 class="text-hospital mb-3 fw-bold"><i class="bi bi-people-fill me-1"></i>จัดการผู้ใช้งานระบบ</h5>

            <?php if ($u_flash): ?>
                <div class="alert alert-<?= htmlspecialchars($u_flash['type']) ?>"><?= htmlspecialchars($u_flash['msg']) ?></div>
            <?php endif; ?>

            <!-- ฟอร์มเพิ่มผู้ใช้ -->
            <form action="admin.php?tab=users" method="POST" class="admin-form-container">
                <input type="hidden" name="action_user" value="create">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">ชื่อผู้ใช้ (username)</label>
                        <input type="text" name="u_username" class="form-control" required autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
                        <input type="text" name="u_password" class="form-control" required minlength="8" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">ชื่อที่แสดง</label>
                        <input type="text" name="u_display_name" class="form-control" placeholder="เช่น หัวหน้าแผนกกุมารเวช">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">ประเภทผู้ใช้</label>
                        <select name="u_role" class="form-select" id="u_role_select" onchange="toggleUserDeptRow()">
                            <option value="dept">Admin แผนก</option>
                            <option value="main">Admin หลัก</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 align-items-end" id="u_dept_row">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">แผนกที่รับผิดชอบ</label>
                        <select name="u_department_id" class="form-select">
                            <option value="0">— เลือกแผนก —</option>
                            <?php foreach ($all_depts as $d): ?>
                                <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="submit" class="btn btn-hospital-orange"><i class="bi bi-plus-lg"></i> เพิ่มผู้ใช้</button>
                    </div>
                </div>
            </form>

            <!-- ตารางรายชื่อผู้ใช้ -->
            <div class="admin-table-scroll mt-4">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>ชื่อผู้ใช้ / ชื่อที่แสดง</th>
                        <th style="width:130px;">ประเภท</th>
                        <th>แผนก</th>
                        <th style="width:220px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($all_users)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีผู้ใช้งาน</td></tr>
                    <?php endif; ?>
                    <?php foreach ($all_users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($u['username']) ?></strong>
                            <?php if (!empty($u['display_name']) && $u['display_name'] !== $u['username']): ?>
                                <div class="small text-muted"><?= htmlspecialchars($u['display_name']) ?></div>
                            <?php endif; ?>
                            <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                                <span class="badge bg-info mt-1">คุณ</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'main'): ?>
                                <span class="badge bg-danger">Admin หลัก</span>
                            <?php else: ?>
                                <span class="badge-orange-style">Admin แผนก</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'main'): ?>
                                <span class="text-muted small">(ทั้งหมด)</span>
                            <?php elseif (!empty($u['dept_name'])): ?>
                                <?= htmlspecialchars($u['dept_name']) ?>
                            <?php else: ?>
                                <span class="text-danger small">ไม่มีแผนก</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-edit-style btn-sm" data-bs-toggle="modal" data-bs-target="#pwdModal<?= (int)$u['id'] ?>">
                                <i class="bi bi-key-fill"></i> เปลี่ยนรหัส
                            </button>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="admin.php?tab=users&del_user=<?= (int)$u['id'] ?>" class="btn btn-outline-delete-style btn-sm" onclick="return confirm('ยืนยันการลบผู้ใช้ <?= htmlspecialchars(addslashes($u['username'])) ?> ?')">
                                    <i class="bi bi-trash-fill"></i> ลบ
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <!-- Modals เปลี่ยนรหัสผ่าน -->
            <?php foreach ($all_users as $u): ?>
            <div class="modal fade" id="pwdModal<?= (int)$u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="admin.php?tab=users">
                            <div class="modal-header">
                                <h5 class="modal-title text-hospital fw-bold">เปลี่ยนรหัสผ่าน: <?= htmlspecialchars($u['username']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action_user" value="reset_password">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <label class="form-label fw-bold">รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)</label>
                                <input type="text" name="new_password" class="form-control" required minlength="8">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-hospital-orange">บันทึก</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div></div><div class="modal fade" id="modalNews" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form action="admin.php?tab=news" method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="text-hospital fw-bold">แก้ไขข่าวประชาสัมพันธ์</h5></div>
      <div class="modal-body">
        <input type="hidden" name="action_news" value="update">
        <input type="hidden" name="id" id="edit_news_id">
        <input type="hidden" name="old_image" id="edit_news_old_image">
        <div class="mb-3"><label class="form-label fw-bold">หัวข้อข่าว</label><input type="text" name="title" id="edit_news_title" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-bold">วันที่ของข่าว</label><input type="text" name="created_at" id="edit_news_date" class="form-control bg-white" required></div>
        <div class="mb-3"><label class="form-label fw-bold">ลิงก์หน้าข่าวสารเพิ่มเติมภายนอก</label><input type="url" name="link_url" id="edit_news_link" class="form-control"></div>
        <div class="mb-3"><label class="form-label fw-bold">เนื้อหาข่าวแบบละเอียด</label><textarea name="content" id="edit_news_content" class="form-control" rows="4"></textarea></div>
        <div class="mb-3">
            <div class="form-check form-switch p-2 border rounded bg-light form-switch-indented">
                <input class="form-check-input" type="checkbox" name="is_new" id="edit_news_is_new" value="1">
                <label class="form-check-label ms-2" for="edit_news_is_new"><strong>เปิดแสดงป้าย "ใหม่"</strong></label>
            </div>
        </div>
        <div class="mb-3"><label class="form-label fw-bold">เปลี่ยนไฟล์ (เว้นว่างเพื่อใช้ไฟล์เดิม)</label><input type="file" name="image[]" class="form-control" accept="image/*,application/pdf" multiple></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-hospital-orange">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalDept" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="admin.php?tab=departments" method="POST" class="modal-content">
      <div class="modal-header"><h5 class="text-hospital fw-bold">แก้ไขหน่วยงาน</h5></div>
      <div class="modal-body">
        <input type="hidden" name="action_dept" value="update">
        <input type="hidden" name="id" id="edit_dept_id">
        <div class="mb-3"><label class="form-label fw-bold">ชื่อหน่วยงาน / หอผู้ป่วย</label><input type="text" name="name" id="edit_dept_name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-bold">ลิงก์หน้าเว็บประจำแผนก</label><input type="url" name="link_url" id="edit_dept_link" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-hospital-orange">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalBanner" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form action="admin.php?tab=banners" method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="text-hospital fw-bold">แก้ไข Banner / Slider</h5></div>
      <div class="modal-body">
        <input type="hidden" name="action_banner" value="update">
        <input type="hidden" name="id" id="edit_banner_id">
        <input type="hidden" name="old_image" id="edit_banner_old_image">
        <div class="mb-3">
            <label class="form-label fw-bold">หัวข้อ Banner</label>
            <input type="text" name="title" id="edit_banner_title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">คำบรรยาย (Subtitle)</label>
            <input type="text" name="subtitle" id="edit_banner_subtitle" class="form-control" placeholder="คำบรรยายใต้หัวข้อ">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">ลิงก์ปุ่ม "อ่านเพิ่มเติม"</label>
            <input type="url" name="link_url" id="edit_banner_link" class="form-control">
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">แสดงที่</label>
                <select name="department_id" id="edit_banner_department" class="form-select">
                    <option value="">หน้าแรกของเว็บไซต์</option>
                    <?php foreach($all_depts as $dept): ?>
                        <option value="<?= (int)$dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">ลำดับแสดง</label>
                <input type="number" name="sort_order" id="edit_banner_sort" class="form-control" min="1">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">รูปภาพใหม่ (เว้นว่างเพื่อใช้รูปเดิม)</label>
                <input type="file" name="banner_image" class="form-control" accept="image/*">
            </div>
        </div>
        <div id="edit_banner_preview_wrap" class="mb-3" style="display:none;">
            <label class="form-label fw-bold">รูปปัจจุบัน</label><br>
            <img id="edit_banner_preview" src="" class="banner-thumb" style="width:180px; height:auto;">
        </div>
        <div class="form-check form-switch p-2 border rounded bg-light form-switch-indented">
            <input class="form-check-input ms-1" type="checkbox" name="is_active" id="edit_banner_active" value="1">
            <label class="form-check-label ms-2" for="edit_banner_active"><strong>เปิดแสดง Banner นี้บนหน้าแรก</strong></label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-hospital-orange">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>

<script>
const flatpickrConfig = {
    locale: "th",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "d/m/Y",
    defaultDate: "<?= date('Y-m-d') ?>",
    onUpdate:     function(s, d, i) { formatYearToThai(i); },
    onReady:      function(s, d, i) {
        formatYearToThai(i);
        i.calendarContainer.addEventListener('click', () => { setTimeout(() => formatYearToThai(i), 1); });
    },
    onMonthChange: function(s, d, i) { setTimeout(() => formatYearToThai(i), 1); },
    onYearChange:  function(s, d, i) { setTimeout(() => formatYearToThai(i), 1); }
};

function formatYearToThai(instance) {
    const thYear   = parseInt(instance.currentYear) + 543;
    const yearInput = instance.calendarContainer.querySelector('.numInput.flatpickr-year');
    if (yearInput) yearInput.value = thYear;
    if (instance.altInput) {
        const val = instance.input.value;
        if (val) {
            const parts    = val.split('-');
            const thYearInput = parseInt(parts[0]) + 543;
            instance.altInput.value = parts[2] + '/' + parts[1] + '/' + thYearInput;
        }
    }
}

<?php if($active_tab == 'news'): ?>
    const mainPicker      = flatpickr("#news_date_picker", flatpickrConfig);
    const modalNewsPicker = flatpickr("#edit_news_date",   flatpickrConfig);
<?php endif; ?>

// ----- Edit functions -----
function editNews(id, title, content, date, img, isNew, link) {
    document.getElementById('edit_news_id').value        = id;
    document.getElementById('edit_news_title').value     = title;
    document.getElementById('edit_news_content').value   = content;
    document.getElementById('edit_news_old_image').value = img;
    document.getElementById('edit_news_is_new').checked  = (parseInt(isNew) === 1);
    document.getElementById('edit_news_link').value      = link;
    if (typeof modalNewsPicker !== 'undefined') { modalNewsPicker.setDate(date); formatYearToThai(modalNewsPicker); }
    new bootstrap.Modal(document.getElementById('modalNews')).show();
}

// ล็อกระบบให้ department_id ซิงค์และดึงข้อมูลมาแก้ไขได้ตามปกติ
function editDept(id, name, link) {
    document.getElementById('edit_dept_id').value   = id;
    document.getElementById('edit_dept_name').value = name;
    document.getElementById('edit_dept_link').value = link;
    new bootstrap.Modal(document.getElementById('modalDept')).show();
}

function editDeptContent(item) {
    document.getElementById('dept_content_action').value        = 'update';
    document.getElementById('dept_content_id').value            = item.id || '';
    document.getElementById('dept_content_old_file').value      = item.file_name || '';
    document.getElementById('dept_content_department_id').value = item.department_id || '';
    document.getElementById('dept_content_section').value       = item.section || 'knowledge';
    document.getElementById('dept_content_sort_order').value    = Math.max(1, parseInt(item.sort_order || 1, 10));
    document.getElementById('dept_content_title').value         = item.title || '';
    document.getElementById('dept_content_link_url').value      = item.link_url || '';
    document.getElementById('dept_content_content').value       = item.content || '';
    document.getElementById('dept_content_submit').textContent  = 'บันทึกการแก้ไข';
    document.getElementById('deptContentForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetDeptContentForm() {
    const form = document.getElementById('deptContentForm');
    if (!form) return;
    
    // เก็บค่า department_id ปัจจุบันไว้ก่อนล้างฟอร์ม
    const currentDeptId = document.getElementById('dept_content_department_id').value;
    
    form.reset();
    
    document.getElementById('dept_content_action').value   = 'create';
    document.getElementById('dept_content_id').value       = '';
    document.getElementById('dept_content_old_file').value = '';
    document.getElementById('dept_content_sort_order').value = '1';
    
    // คืนค่า department_id ประจำกลุ่มงานกลับเข้าไปตามเดิม
    document.getElementById('dept_content_department_id').value = currentDeptId;

    document.getElementById('dept_content_submit').textContent = '+ เพิ่มข้อมูล';
}

const deptContentForm = document.getElementById('deptContentForm');
if (deptContentForm) {
    deptContentForm.addEventListener('submit', function () {
        const sortInput = document.getElementById('dept_content_sort_order');
        sortInput.value = Math.max(1, parseInt(sortInput.value || 1, 10));
    });
}

// ----- Index Page edit -----
function editIndexPage(item) {
    document.getElementById('idx_action').value         = 'update';
    document.getElementById('idx_id').value             = item.id || '';
    document.getElementById('idx_old_file').value       = item.file_name || '';
    document.getElementById('idx_section_select').value = item.section || 'idx_news';
    document.getElementById('idx_sort_order').value     = Math.max(1, parseInt(item.sort_order || 1, 10));
    document.getElementById('idx_title').value          = item.title || '';
    document.getElementById('idx_link_url').value       = item.link_url || '';
    document.getElementById('idx_content').value        = item.content || '';
    document.getElementById('idx_submit').textContent   = 'บันทึกการแก้ไข';
    document.getElementById('indexPageForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetIndexPageForm() {
    const form = document.getElementById('indexPageForm');
    if (!form) return;
    form.reset();
    document.getElementById('idx_action').value     = 'create';
    document.getElementById('idx_id').value         = '';
    document.getElementById('idx_old_file').value   = '';
    document.getElementById('idx_sort_order').value = '1';
    document.getElementById('idx_submit').textContent = '+ เพิ่มข้อมูล';
}

const indexPageForm = document.getElementById('indexPageForm');
if (indexPageForm) {
    indexPageForm.addEventListener('submit', function () {
        const sortInput = document.getElementById('idx_sort_order');
        sortInput.value = Math.max(1, parseInt(sortInput.value || 1, 10));
    });
}

// ----- Banner edit -----
function editBanner(id, title, subtitle, img, link, sort, isActive, departmentId) {
    document.getElementById('edit_banner_id').value       = id;
    document.getElementById('edit_banner_title').value    = title;
    document.getElementById('edit_banner_subtitle').value = subtitle;
    document.getElementById('edit_banner_old_image').value = img;
    document.getElementById('edit_banner_link').value     = link;
    document.getElementById('edit_banner_sort').value     = sort;
    document.getElementById('edit_banner_active').checked = (parseInt(isActive) === 1);
    document.getElementById('edit_banner_department').value = departmentId ? String(departmentId) : '';

    // แสดง preview รูปเดิม
    const previewWrap = document.getElementById('edit_banner_preview_wrap');
    const previewImg  = document.getElementById('edit_banner_preview');
    if (img) {
        previewImg.src        = 'uploads/' + img;
        previewWrap.style.display = 'block';
    } else {
        previewWrap.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('modalBanner')).show();
}

// สลับการแสดงช่อง "แผนก" ในฟอร์มเพิ่มผู้ใช้ (ซ่อนเมื่อเลือก main)
function toggleUserDeptRow() {
    const el = document.getElementById('u_role_select');
    const row = document.getElementById('u_dept_row');
    if (!el || !row) return;
    row.style.display = (el.value === 'dept') ? 'flex' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleUserDeptRow);
</script>
</body>
</html>