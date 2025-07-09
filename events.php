<?php
include("db.php");
header('Content-Type: application/json');

// ตรวจสอบว่ามี sign_board_id ถูกส่งมาหรือไม่
$sign_board_id = isset($_GET['sign_board_id']) ? (int)$_GET['sign_board_id'] : 0;

// ดึงเฉพาะสถานะที่ต้องการแสดง และเฉพาะป้ายที่ระบุ
$sql = "
  SELECT b.id, b.start_date, b.end_date, b.title AS booking_title, b.status,
         s.code, s.location
  FROM bookings b
  INNER JOIN sign_boards s ON b.sign_board_id = s.id
  WHERE b.status NOT IN ('cancelled', 'rejected')
";

if ($sign_board_id > 0) {
  $sql .= " AND b.sign_board_id = $sign_board_id";
}

$results = $conn->query($sql);
$events = [];

while ($row = $results->fetch_assoc()) {
  // สิ้นสุดต้องบวกเพิ่ม 1 วันให้แสดงถูกต้องใน FullCalendar
  $end_date = date('Y-m-d', strtotime($row['end_date'] . ' +1 day'));

  // ตรวจสอบสถานะและตั้งค่าคลาส
  $status = $row['status'];
  $className = '';

  if ($status == 'approved') {
    $className = 'fc-approved';
  } elseif ($status == 'pending') {
    $className = 'fc-pending';
  } elseif ($status == 'in_progress') {
    $className = 'fc-in_progress';
  }

  $events[] = [
    'id'    => $row['id'],
    'title' => "{$row['code']}: {$row['booking_title']}",
    'start' => $row['start_date'],
    'end'   => $end_date,
    'className' => $className,
    'extendedProps' => [
      'sign_code' => $row['code'],
      'booking_title' => $row['booking_title'],
      'location' => $row['location'],
      'status' => $row['status']
    ]
  ];
}

echo json_encode($events);
