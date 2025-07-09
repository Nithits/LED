<?php
include(__DIR__ . '/db.php');

$id = $_GET['id'] ?? null;
if (!$id) {
  echo "<p>ไม่พบรหัสการจอง</p>";
  exit;
}

// ดึงข้อมูลการจอง
$stmt = $conn->prepare("SELECT b.*, s.code AS sign_code, s.location FROM bookings b JOIN sign_boards s ON b.sign_board_id = s.id WHERE b.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  echo "<p>ไม่พบข้อมูล</p>";
  exit;
}
$booking = $result->fetch_assoc();

// ดึงข้อความ
$msgs = $conn->prepare("SELECT * FROM booking_messages WHERE booking_id = ? ORDER BY created_at ASC");
$msgs->bind_param("i", $id);
$msgs->execute();
$msg_result = $msgs->get_result();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>ตอบกลับการจอง</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
</head>
<body class="bg-dark text-light">
<div class="container my-5">
  <h3>✉️ ตอบกลับการจอง</h3>
  <table class="table table-bordered table-dark">
    <tr><th>ชื่อผู้จอง</th><td><?= htmlspecialchars($booking['requester_name']) ?></td></tr>
    <tr><th>ป้าย</th><td><?= $booking['sign_code'] ?> (<?= $booking['location'] ?>)</td></tr>
    <tr><th>วันที่จอง</th><td><?= $booking['booking_date'] ?></td></tr>
    <tr><th>สถานะ</th><td><?= ucfirst($booking['status']) ?></td></tr>
  </table>

  <h5>🗨️ ข้อความโต้ตอบ</h5>
  <div class="bg-light text-dark p-3 rounded mb-3" style="max-height: 300px; overflow-y: auto">
    <?php while($row = $msg_result->fetch_assoc()): ?>
      <div><strong><?= $row['sender_role'] === 'provider' ? 'ฉัน' : 'ผู้จอง' ?>:</strong> <?= nl2br(htmlspecialchars($row['message'])) ?> <small class="text-muted">(<?= $row['created_at'] ?>)</small></div>
      <hr>
    <?php endwhile; ?>
  </div>

  <form method="POST" action="submit_message.php">
    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
    <input type="hidden" name="sender_role" value="provider">
    <div class="mb-3">
      <label>ตอบกลับถึงผู้ขอจอง:</label>
      <textarea name="message" class="form-control" rows="3" required></textarea>
    </div>
    <button type="submit" class="btn btn-success">ส่งข้อความ</button>
  </form>

  <hr>
  <form method="POST" action="update_status.php" class="d-flex gap-2">
    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
    <select name="status" class="form-select w-auto">
      <option value="approved" <?= $booking['status'] === 'approved' ? 'selected' : '' ?>>อนุมัติ</option>
      <option value="rejected" <?= $booking['status'] === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
      <option value="pending" <?= $booking['status'] === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
    </select>
    <button type="submit" class="btn btn-warning">อัปเดตสถานะ</button>
  </form>
</div>
</body>
</html>