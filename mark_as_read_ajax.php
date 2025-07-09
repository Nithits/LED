<?php
// File: mark_as_read_ajax.php
include("db.php");

// ต้องระบุ session_name ให้ตรงกับหน้าที่เรียกใช้
if (isset($_POST['role']) && $_POST['role'] === 'admin') {
    session_name('admin_session');
} else {
    session_name('user_session');
}
session_start();

$booking_id = $_POST['booking_id'] ?? 0;
$role = $_POST['role'] ?? '';

if (!$booking_id || !$role) {
    http_response_code(400);
    exit('Invalid request');
}

// ตรวจสอบว่าเป็น session ของ role นั้นจริงๆ
if (($role === 'admin' && !isset($_SESSION['admin_id'])) || ($role === 'user' && !isset($_SESSION['user_id']))) {
    http_response_code(403);
    exit('Access Denied');
}

// กำหนดว่าใครคือผู้ส่งที่ "ไม่เท่ากับ" เรา
$not_sender = ($role === 'admin') ? 'admin' : 'user';

// อัปเดตสถานะเป็นอ่านแล้ว
$stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE booking_id = ? AND sender != ? AND is_read = 0");
$stmt->bind_param("is", $booking_id, $not_sender);
$stmt->execute();

// ตอบกลับว่าสำเร็จ
http_response_code(200);
echo "OK";
?>