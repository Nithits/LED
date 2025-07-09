<?php
session_name('user_session');
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["error" => "Unauthorized"]);
  exit;
}

$user_id = $_SESSION['user_id'];

// ดึงจำนวนข้อความที่ admin ส่งมาแล้วยังไม่อ่าน
$query = "SELECT booking_id, COUNT(*) AS unread_count
          FROM chat_messages
          WHERE sender = 'admin' AND is_read = 0
          AND booking_id IN (
            SELECT id FROM bookings WHERE user_id = ?
          )
          GROUP BY booking_id";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$counts = [];
while ($row = $result->fetch_assoc()) {
  $counts[$row['booking_id']] = (int)$row['unread_count'];
}

header('Content-Type: application/json');
echo json_encode($counts);
