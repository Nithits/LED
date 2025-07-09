<?php
session_name('admin_session');
session_start();
require_once 'db.php';

// 1. ตรวจสอบสิทธิ์และเมธอดการเข้าถึง
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_account.php");
    exit;
}

// --- 2. รับข้อมูลจากฟอร์ม "เพิ่ม" ---
$name = trim(htmlspecialchars($_POST['admin_name'] ?? ''));
$email = trim(htmlspecialchars($_POST['admin_email'] ?? ''));
$password = $_POST['admin_password'] ?? '';
$confirm_password = $_POST['admin_confirm_password'] ?? '';

// --- 3. การตรวจสอบความถูกต้องของข้อมูล (Server-Side Validation) ---
if (empty($name) || empty($email) || empty($password)) {
    header("Location: admin_account.php?error=empty_fields");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: admin_account.php?error=invalid_email");
    exit;
}
if ($password !== $confirm_password) {
    header("Location: admin_account.php?error=password_mismatch");
    exit;
}

// --- 4. การประมวลผลข้อมูล ---
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$upload_dir = 'uploads/';
$avatar_path = 'images/default_avatar.png';

// จัดการการอัปโหลดไฟล์รูปภาพ (ถ้ามี)
if (isset($_FILES['admin_avatar']) && $_FILES['admin_avatar']['error'] == UPLOAD_ERR_OK) {
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $tmp_name = $_FILES['admin_avatar']['tmp_name'];
    $file_extension = pathinfo($_FILES['admin_avatar']['name'], PATHINFO_EXTENSION);
    // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน
    $new_filename = 'avatar_' . uniqid() . time() . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;

    if (move_uploaded_file($tmp_name, $destination)) {
        $avatar_path = $destination;
    }
}

// --- 5. บันทึกข้อมูลลงฐานข้อมูล ---
try {
    // ตรวจสอบอีเมลซ้ำ
    $check_sql = "SELECT id FROM admins WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: admin_account.php?error=email_exists");
        exit;
    }
    $check_stmt->close();

    // เพิ่มข้อมูลใหม่ลงฐานข้อมูล
    $sql = "INSERT INTO admins (name, username, password, avatar_path) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $avatar_path);
    
    if ($stmt->execute()) {
        header("Location: admin_account.php?status=add_success");
    } else {
        header("Location: admin_account.php?error=db_error");
    }
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage()); // บันทึก error ไว้ดูสำหรับโปรแกรมเมอร์
    header("Location: admin_account.php?error=db_error");
}

$conn->close();
exit;
?>