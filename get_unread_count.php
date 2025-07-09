<?php
session_name('admin_session');
session_start();
include("db.php");

// ตรวจสอบว่า admin login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(["error" => "Unauthorized"]);
  exit;
}

// ดึงจำนวนแชทที่ยังไม่อ่าน ตาม booking_id
$query = "SELECT booking_id, COUNT(*) AS unread_count
          FROM chat_messages
          WHERE sender != 'admin' AND is_read = 0
          GROUP BY booking_id";

$result = $conn->query($query);

$counts = [];
while ($row = $result->fetch_assoc()) {
  $counts[$row['booking_id']] = (int)$row['unread_count'];
}

header('Content-Type: application/json');
echo json_encode($counts);
