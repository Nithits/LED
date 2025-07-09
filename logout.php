<?php
// ✅ ตั้งชื่อ session สำหรับผู้ใช้ก่อนเริ่ม session
session_name("user_session");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ เคลียร์ข้อมูล session ทั้งหมด
$_SESSION = [];
session_unset();
session_destroy();

// ✅ ล้าง session cookie ด้วย (ถ้ามี)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ✅ ส่งกลับไปหน้า login
header("Location: login_user.php");
exit;
