<?php
session_name('admin_session');
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_account.php");
    exit;
}

// --- รับข้อมูลจากฟอร์ม ---
$admin_id = $_POST['admin_id'];
$name = trim(htmlspecialchars($_POST['admin_name']));
$username = trim(htmlspecialchars($_POST['admin_username']));
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$current_password = $_POST['current_password'] ?? '';

// --- การตั้งค่า ---
$upload_dir = 'uploads/';
$default_avatar = 'images/default_avatar.png';
$new_avatar_path = null; // เริ่มต้นเป็น null

// --- จัดการการอัปโหลดไฟล์รูปภาพใหม่ (ถ้ามี) ---
if (isset($_FILES['new_avatar']) && $_FILES['new_avatar']['error'] == UPLOAD_ERR_OK) {
    // ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($_FILES['new_avatar']['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        header("Location: admin_account.php?error=invalid_file_type");
        exit;
    }

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $tmp_name = $_FILES['new_avatar']['tmp_name'];
    $file_extension = pathinfo($_FILES['new_avatar']['name'], PATHINFO_EXTENSION);
    $new_filename = 'avatar_' . $admin_id . '_' . time() . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;

    // ตรวจสอบว่าย้ายไฟล์สำเร็จหรือไม่
    if (move_uploaded_file($tmp_name, $destination)) {
        $new_avatar_path = $destination; // เก็บ path ใหม่เมื่อสำเร็จเท่านั้น
    } else {
        // ถ้า move_uploaded_file ล้มเหลว (อาจจะเพราะ permission)
        header("Location: admin_account.php?error=upload_failed");
        exit;
    }
}

try {
    // ... การตรวจสอบ username ซ้ำเหมือนเดิม ...

    // ดึงข้อมูลเก่าเพื่อใช้เปรียบเทียบและลบไฟล์เก่า
    $old_data_stmt = $conn->prepare("SELECT password, avatar_path FROM admins WHERE id = ?");
    $old_data_stmt->bind_param("i", $admin_id);
    $old_data_stmt->execute();
    $old_data_result = $old_data_stmt->get_result()->fetch_assoc();
    $old_data_stmt->close();
    $old_avatar_path = $old_data_result['avatar_path'];

    // สร้าง SQL และ Bind Parameters แบบไดนามิก
    $sql_parts = [];
    $params = [];
    $types = "";

    // ส่วนของ Name และ Username
    $sql_parts[] = "name = ?";
    $params[] = $name;
    $types .= "s";
    
    $sql_parts[] = "username = ?";
    $params[] = $username;
    $types .= "s";

    // ส่วนของ Avatar (อัปเดตเมื่อมีไฟล์ใหม่เท่านั้น)
    if ($new_avatar_path) {
        $sql_parts[] = "avatar_path = ?";
        $params[] = $new_avatar_path;
        $types .= "s";
    }

    // ส่วนของ Password (อัปเดตเมื่อมีการกรอกรหัสใหม่)
    if (!empty($new_password)) {
        // ... การตรวจสอบรหัสผ่านปัจจุบันและรหัสใหม่เหมือนเดิม ...
        if (!password_verify($current_password, $old_data_result['password'])) {
             header("Location: admin_account.php?error=current_password_incorrect");
             exit;
        }
        if ($new_password !== $confirm_password) {
             header("Location: admin_account.php?error=edit_password_mismatch");
             exit;
        }
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_parts[] = "password = ?";
        $params[] = $new_hashed_password;
        $types .= "s";
    }

    // ถ้าไม่มีอะไรให้อัปเดตเลย ก็ไม่ต้องทำอะไร
    if (empty($sql_parts)) {
        header("Location: admin_account.php");
        exit;
    }

    $sql = "UPDATE admins SET " . implode(", ", $sql_parts) . " WHERE id = ?";
    $params[] = $admin_id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // ถ้าอัปเดต DB สำเร็จ และมีการอัปโหลดรูปใหม่ ให้ลบรูปเก่าที่ไม่ใช่ default
        if ($new_avatar_path && $old_avatar_path !== $default_avatar && file_exists($old_avatar_path)) {
            @unlink($old_avatar_path); // @ เพื่อซ่อน warning หากลบไฟล์ไม่ได้
        }
        header("Location: admin_account.php?status=edit_success");
    } else {
        header("Location: admin_account.php?error=edit_failed");
    }
    $stmt->close();

} catch (Exception $e) {
    // error_log($e->getMessage());
    header("Location: admin_account.php?error=edit_failed");
}
$conn->close();
exit;
?>