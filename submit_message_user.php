// ✅ submit_message_user.php
<?php
session_name('user_session');
session_start();
include("db.php");

if (empty($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit;
}

$sender = 'user';
$sender_id = (int)$_SESSION['user_id'];
$booking_id = (int)($_POST['booking_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($booking_id <= 0 || $message === '') {
    header("Location: chat.php?booking_id=$booking_id");
    exit;
}

$stmt = $conn->prepare("INSERT INTO chat_messages (booking_id, sender, sender_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("isis", $booking_id, $sender, $sender_id, $message);
$stmt->execute();

header("Location: chat.php?booking_id=$booking_id");
exit;