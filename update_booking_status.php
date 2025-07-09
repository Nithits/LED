<?php
include("db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    if (in_array($new_status, ['pending', 'approved', 'rejected', 'in_progress']) && $booking_id > 0) {
        // อัปเดตสถานะ
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        $stmt->execute();

        // ดึงข้อมูลประกอบแชท
        $info_stmt = $conn->prepare("
            SELECT b.title, b.start_date, b.end_date, s.code, s.type
            FROM bookings b
            JOIN sign_boards s ON b.sign_board_id = s.id
            WHERE b.id = ?
        ");
        $info_stmt->bind_param("i", $booking_id);
        $info_stmt->execute();
        $result = $info_stmt->get_result()->fetch_assoc();

        // แปลสถานะ
        $thai_status = [
            'pending' => '⏳ รอดำเนินการ',
            'approved' => '✅ อนุมัติ',
            'rejected' => '❌ ปฏิเสธ',
            'in_progress' => '⚙️ ดำเนินการแล้ว' // เพิ่มสถานะ "ดำเนินการแล้ว"
        ];

        $message = "📢 สถานะการจองของคุณมีการอัปเดตแล้ว\n"
                 . "สถานะ: {$thai_status[$new_status]}\n"
                 . "กิจกรรม: {$result['title']}\n"
                 . "วันที่: {$result['start_date']} ถึง {$result['end_date']}\n"
                 . "ป้าย: {$result['code']} ({$result['type']})";

        // บันทึกลงตารางแชท
        $stmt2 = $conn->prepare("INSERT INTO chat_messages (booking_id, sender, sender_id, message, is_read, created_at) VALUES (?, 'admin', 0, ?, 0, NOW())");
        $stmt2->bind_param("is", $booking_id, $message);
        $stmt2->execute();

        // ส่งผ่าน socket.io
        echo "
        <script src='https://cdn.socket.io/4.7.2/socket.io.min.js'></script>
        <script>
          const socket = io('http://localhost:3000');
          socket.emit('send_message', {
            booking_id: $booking_id,
            sender: 'admin',
            sender_id: 0,
            message: " . json_encode($message) . ",
            created_at: new Date().toISOString()
          });
          window.location.href = 'admin_bookings.php?success=1';
        </script>
        ";
        exit;
    } else {
        echo "ข้อมูลไม่ถูกต้อง";
    }
}
?>
