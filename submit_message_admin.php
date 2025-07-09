<?php
session_name('admin_session');
session_start();
include("db.php");

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit;
}

$sender = 'admin';
$sender_id = (int)$_SESSION['admin_id'];
$booking_id = (int)($_POST['booking_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($booking_id <= 0 || $message === '') {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบ']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO chat_messages (booking_id, sender, sender_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("isis", $booking_id, $sender, $sender_id, $message);
$stmt->execute();

echo json_encode(['status' => 'success']);
exit;
