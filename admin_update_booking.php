<?php
session_name('admin_session');
session_start();
include(__DIR__ . '/db.php');

// 1. ตรวจสอบสิทธิ์การเข้าถึงของแอดมิน
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

// ตรวจสอบว่าเป็น POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_bookings.php");
    exit;
}

// 2. รับ ID และตรวจสอบว่ามีข้อมูลหรือไม่
$id = $_POST['id'] ?? null;
if (empty($id)) {
    die("เกิดข้อผิดพลาด: ไม่พบ ID ของรายการที่ต้องการอัปเดต");
}

try {
    // 3. ดึงข้อมูลการจองปัจจุบันจากฐานข้อมูล
    $stmt_fetch = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt_fetch->bind_param("i", $id);
    $stmt_fetch->execute();
    $current_booking = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$current_booking) {
        die("เกิดข้อผิดพลาด: ไม่พบข้อมูลการจองสำหรับ ID: " . $id);
    }

    // ---- [FIXED 1] แก้ไข CHANGE DETECTION ให้รู้จัก 'position' ----
    $statusChanged = isset($_POST['status']) && $_POST['status'] !== $current_booking['status'];
    $noteChanged = isset($_POST['note']) && trim($_POST['note']) !== $current_booking['note'];
    
    $detailsChanged = (
        (isset($_POST['requester_name']) && trim($_POST['requester_name']) !== $current_booking['requester_name']) ||
        (isset($_POST['requester_phone']) && trim($_POST['requester_phone']) !== $current_booking['requester_phone']) ||
        (isset($_POST['requester_email']) && trim($_POST['requester_email']) !== $current_booking['requester_email']) ||
        (isset($_POST['user_status']) && $_POST['user_status'] !== $current_booking['user_status']) ||
        (isset($_POST['faculty']) && trim($_POST['faculty']) !== $current_booking['faculty']) ||
        (isset($_POST['year']) && trim($_POST['year']) !== $current_booking['year']) ||
        (isset($_POST['workplace']) && trim($_POST['workplace']) !== $current_booking['workplace']) ||
        (isset($_POST['position']) && trim($_POST['position']) !== $current_booking['position']) || // เพิ่ม position
        (isset($_POST['title']) && trim($_POST['title']) !== $current_booking['title']) ||
        (isset($_POST['start_date']) && $_POST['start_date'] !== $current_booking['start_date']) ||
        (isset($_POST['end_date']) && $_POST['end_date'] !== $current_booking['end_date'])
    );
    
    // 5. รับข้อมูลจากฟอร์ม
    $requester_name = trim($_POST['requester_name'] ?? $current_booking['requester_name']);
    $requester_phone = trim($_POST['requester_phone'] ?? $current_booking['requester_phone']);
    $requester_email = trim($_POST['requester_email'] ?? $current_booking['requester_email']);
    $user_status = $_POST['user_status'] ?? $current_booking['user_status'];
    $status = $_POST['status'] ?? $current_booking['status'];
    $note = trim($_POST['note'] ?? $current_booking['note']);
    $title = trim($_POST['title'] ?? $current_booking['title']);
    $drive_link = trim($_POST['drive_link'] ?? $current_booking['drive_link']);
    $start_date = $_POST['start_date'] ?? $current_booking['start_date'];
    $end_date = $_POST['end_date'] ?? $current_booking['end_date'];

    // ---- [FIXED 2] แก้ไขการเตรียมข้อมูลสำหรับบันทึก ----
    $faculty_to_save = $current_booking['faculty'];
    $year_to_save = $current_booking['year'];
    $workplace_to_save = $current_booking['workplace'];
    $position_to_save = $current_booking['position']; // เพิ่มตัวแปรสำหรับ position

    if ($user_status === 'นิสิต') {
        $faculty_to_save = trim($_POST['faculty'] ?? $current_booking['faculty']);
        $year_to_save = trim($_POST['year'] ?? $current_booking['year']);
        $workplace_to_save = null;
        $position_to_save = null; // ล้างค่า position ของบุคลากร
    } elseif ($user_status === 'บุคลากร') {
        $workplace_to_save = trim($_POST['workplace'] ?? $current_booking['workplace']);
        $position_to_save = trim($_POST['position'] ?? $current_booking['position']); // รับค่า position
        $faculty_to_save = null;
        $year_to_save = null;
    }

    // ---- [FIXED 3] แก้ไขคำสั่ง SQL UPDATE และ bind_param ----
    $stmt = $conn->prepare("
        UPDATE bookings SET 
            requester_name = ?, requester_phone = ?, requester_email = ?, user_status = ?, title = ?, 
            faculty = ?, `year` = ?, workplace = ?, position = ?, drive_link = ?, 
            status = ?, note = ?, start_date = ?, end_date = ? 
        WHERE id = ?
    ");
    // เพิ่ม s อีก 1 ตัวสำหรับ position (รวมเป็น 14 ตัวแปร + 1 id)
    $stmt->bind_param("ssssssssssssssi", 
        $requester_name, $requester_phone, $requester_email, $user_status, $title, 
        $faculty_to_save, $year_to_save, $workplace_to_save, $position_to_save, $drive_link, 
        $status, $note, $start_date, $end_date, $id
    );

    if ($stmt->execute()) {
        if ($statusChanged || $detailsChanged || $noteChanged) {
            $thai_status = ['pending' => '⏳ รออนุมัติ', 'approved' => '✅ อนุมัติ', 'rejected' => '❌ ปฏิเสธ', 'in_progress' => '⚙️ ดำเนินการแล้ว'];
            $start_date_thai = date("j M Y", strtotime($start_date));
            $end_date_thai = date("j M Y", strtotime($end_date));

            $message_header = "📢 อัปเดตการจอง: {$title}";
            
            $message_status_part = "สถานะ: {$thai_status[$status]}\n"
                                 . "วันที่: {$start_date_thai} ถึง {$end_date_thai}";

            // ---- [FIXED 4] แก้ไขการสร้างข้อความแจ้งเตือน ----
            $message_details_part = "👤 **ข้อมูลผู้จองได้รับการอัปเดตเป็น:**\n"
                                  . "ชื่อ-สกุล: {$requester_name}\n"
                                  . "เบอร์โทร: {$requester_phone}\n"
                                  . "อีเมล: {$requester_email}\n"
                                  . "สถานภาพ: {$user_status}";
            if ($user_status === 'นิสิต') {
                $message_details_part .= "\nคณะ: {$faculty_to_save}\nชั้นปี: {$year_to_save}";
            } elseif ($user_status === 'บุคลากร') {
                // แสดงผลหน่วยงานและตำแหน่งแยกกัน
                $message_details_part .= "\nหน่วยงาน: {$workplace_to_save}\nตำแหน่ง: {$position_to_save}";
            }
            
            $final_message_parts = [$message_header];
            if ($statusChanged) {
                $final_message_parts[] = $message_status_part;
            }
            if ($detailsChanged) {
                $final_message_parts[] = $message_details_part;
            }

            if (!empty($note)) {
                $final_message_parts[] = "**หมายเหตุจากแอดมิน:**\n" . $note;
            }

            $message = implode("\n\n", $final_message_parts);
            
            $stmt2 = $conn->prepare("INSERT INTO chat_messages (booking_id, sender, sender_id, message, is_read, created_at) VALUES (?, 'admin', 0, ?, 0, NOW())");
            $stmt2->bind_param("is", $id, $message);
            $stmt2->execute();

            echo "
            <script src='https://cdn.socket.io/4.7.2/socket.io.min.js'></script>
            <script>
                const socket = io({ path: '/socket.io/' });
                socket.on('connect', () => {
                    socket.emit('send_message', {
                        booking_id: " . $id . ",
                        sender: 'admin',
                        sender_id: 0,
                        message: " . json_encode($message) . ",
                        created_at: new Date().toISOString()
                    });
                    setTimeout(() => { window.location.href = 'admin_bookings.php?updated=1'; }, 500);
                });
            </script>
            ";
            exit;
        } else {
            header("Location: admin_bookings.php?nochange=1");
            exit;
        }
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

} catch (Exception $e) {
    echo "<h1>เกิดข้อผิดพลาดในการบันทึกข้อมูล</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo '<a href="booking_detail.php?id=' . $id . '">กลับไปแก้ไข</a>';
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_fetch)) $stmt_fetch->close(); // ปิด stmt_fetch ด้วย
    if (isset($stmt2)) $stmt2->close();
    $conn->close();
}
?>