<?php
include(__DIR__ . '/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $booking_id = $_POST['booking_id'] ?? null;
  $status = $_POST['status'] ?? 'pending';

  if ($booking_id && in_array($status, ['approved', 'rejected', 'pending'])) {
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $booking_id);
    $stmt->execute();

    // เพิ่มข้อความ log
    $log = $conn->prepare("INSERT INTO booking_messages (booking_id, sender_role, message) VALUES (?, 'provider', ?)");
    $msg_text = "สถานะถูกอัปเดตเป็น: " . ucfirst($status);
    $log->bind_param("is", $booking_id, $msg_text);
    $log->execute();
  }
}

// กลับไปหน้าเดิม
header("Location: admin_edit_signboards.php?success=1");
exit;

// หรือถ้า error:
header("Location: admin_edit_signboards.php?error=ไม่สามารถอัปเดตข้อมูลได้");
exit;

