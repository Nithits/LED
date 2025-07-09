<?php
session_name("admin_session");
session_start();

// ล้าง session ทั้งหมดที่เกี่ยวข้องกับ admin
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_logged_in']);

// ถ้ามี session อื่นๆ ค้างอยู่ เช่นจาก user
// session_destroy(); // (ถ้าต้องการล้างทั้งหมดจริงๆ)

header("Location: login_admin.php");
exit;
?>
