<?php
session_name('admin_session');
session_start();
include("db.php");

// ฟังก์ชันสำหรับ Redirect พร้อมข้อความ Error
function redirect_with_error($message) {
    header("Location: admin_edit_signboards.php?error=" . urlencode($message));
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $version = 'new'; // เพิ่ม version เป็น new โดยอัตโนมัติ

    if (empty($code) || empty($name)) {
        redirect_with_error('กรุณากรอกรหัสและชื่อป้าย');
    }

    $image_filename_to_db = ''; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "images/";
        if (!is_dir($target_dir) || !is_writable($target_dir)) {
            redirect_with_error('โฟลเดอร์ images ไม่มีอยู่ หรือ Server ไม่สามารถเขียนไฟล์ได้');
        }

        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_extension, $allowed_extensions)) {
            redirect_with_error('อนุญาตให้อัปโหลดไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif, webp)');
        }

        $new_filename = strtolower($code) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_filename_to_db = $new_filename;
        } else {
            redirect_with_error('ไม่สามารถย้ายไฟล์ที่อัปโหลดได้');
        }
    }

    // --- เปลี่ยนตรงนี้ ---
    // เปลี่ยนจาก image เป็น image_url และเพิ่ม version
    $sql = "INSERT INTO sign_boards (code, name, type, size, location, image_url, version, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    if ($stmt = $conn->prepare($sql)) {
        // --- เปลี่ยนประเภทของ bind_param ให้รองรับ version ---
        $stmt->bind_param("sssssss", $code, $name, $type, $size, $location, $image_filename_to_db, $version);
        if ($stmt->execute()) {
            header("Location: admin_edit_signboards.php?success=1");
            exit;
        } else {
            redirect_with_error("บันทึกข้อมูลล้มเหลว: " . $stmt->error);
        }
        $stmt->close();
    } else {
        redirect_with_error("SQL Error: " . $conn->error);
    }
    $conn->close();
}
?>