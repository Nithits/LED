<?php
session_name('admin_session');
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

require_once 'db.php';

// ตรวจสอบว่ามี id ส่งมาหรือไม่
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_account.php?error=invalid_id");
    exit;
}

$admin_id_to_delete = intval($_GET['id']);

// ป้องกันแอดมินลบตัวเอง (ต้องมี id ของคนที่ล็อกอินอยู่เก็บใน session ตอนล็อกอิน)
// if (isset($_SESSION['admin_id']) && $admin_id_to_delete == $_SESSION['admin_id']) {
//     header("Location: admin_account.php?error=cannot_delete_self");
//     exit;
// }

try {
    $sql = "DELETE FROM admins WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id_to_delete);

    if ($stmt->execute()) {
        header("Location: admin_account.php?status=delete_success");
    } else {
        header("Location: admin_account.php?error=delete_failed");
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    header("Location: admin_account.php?error=delete_failed");
}
$conn->close();
exit;
?>