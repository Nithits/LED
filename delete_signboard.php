<?php
session_name('admin_session');
session_start();
include("db.php");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // ดึงข้อมูลจากฐานข้อมูล
    $stmt = $conn->prepare("SELECT image_url FROM sign_boards WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // ตรวจสอบว่ามีไฟล์ในฐานข้อมูลหรือไม่
    if ($row && !empty($row['image_url'])) {
        $imagePath = 'images/' . $row['image_url'];

        // ลบไฟล์รูปภาพจากโฟลเดอร์
        if (file_exists($imagePath)) {
            unlink($imagePath); // ลบไฟล์
        }
    }

    // ลบข้อมูลจากฐานข้อมูล
    $stmt = $conn->prepare("DELETE FROM sign_boards WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_edit_signboards.php?success=delete");
    } else {
        header("Location: admin_edit_signboards.php?error=delete_failed");
    }
    exit;
} else {
    header("Location: admin_edit_signboards.php");
    exit;
}
