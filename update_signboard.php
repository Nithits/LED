<?php
session_name('admin_session');
session_start();
include("db.php");

function redirect_with_error($message) {
    header("Location: admin_edit_signboards.php?error=" . urlencode($message));
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? null;
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if (empty($id) || empty($code) || empty($name)) {
        redirect_with_error('ข้อมูลไม่ครบถ้วน');
    }

    $image_sql_part = "";
    $params = [];
    $types = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "images/";
        if (!is_dir($target_dir) || !is_writable($target_dir)) {
            redirect_with_error('โฟลเดอร์ images ไม่มีอยู่ หรือ Server ไม่สามารถเขียนไฟล์ได้');
        }

        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = strtolower($code) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // --- เปลี่ยนตรงนี้ ---
            // ถ้าอัปโหลดรูปใหม่สำเร็จ ให้เตรียมอัปเดตคอลัมน์ image_url ใน DB
            $image_sql_part = ", image_url = ?";
            $params[] = $new_filename;
            $types .= "s";
        } else {
            redirect_with_error('ไม่สามารถย้ายไฟล์รูปใหม่ได้');
        }
    }

    $sql = "UPDATE sign_boards SET code = ?, name = ?, type = ?, size = ?, location = ?, updated_at = NOW() {$image_sql_part} WHERE id = ?";
    
    array_unshift($params, $code, $name, $type, $size, $location);
    $types = "sssss" . $types . "i";
    $params[] = $id;

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            header("Location: admin_edit_signboards.php?success=1");
        } else {
            redirect_with_error("อัปเดตข้อมูลล้มเหลว: " . $stmt->error);
        }
        $stmt->close();
    } else {
        redirect_with_error("SQL Error: " . $conn->error);
    }
    $conn->close();
}
?>