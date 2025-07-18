<?php
session_name("user_session");
session_start();
include(__DIR__ . '/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- รับข้อมูลจากฟอร์ม ---
    $sign_board_id = (int)($_POST['sign_board_id'] ?? 0);
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $name = $conn->real_escape_string($_POST['requester_name'] ?? '');
    $phone = $conn->real_escape_string($_POST['requester_phone'] ?? '');
    $email = $conn->real_escape_string($_POST['requester_email'] ?? '');
    $user_status = $conn->real_escape_string($_POST['user_status'] ?? '');
    $start_date = $conn->real_escape_string($_POST['start_date'] ?? '');
    $end_date = $conn->real_escape_string($_POST['end_date'] ?? '');
    $drive_link = $conn->real_escape_string($_POST['drive_link'] ?? '');
    $today = date('Y-m-d');

    // --- การรับข้อมูลสำหรับบุคลากรและนิสิต ---
    $faculty = null;
    $year = null;
    $workplace = null;
    $position = null;

    if ($user_status === 'นิสิต') {
        $faculty = $conn->real_escape_string($_POST['faculty'] ?? '');
        $year = $conn->real_escape_string($_POST['year'] ?? '');
    } elseif ($user_status === 'บุคลากร') {
        $workplace = $conn->real_escape_string($_POST['workplace'] ?? '');
        $position = $conn->real_escape_string($_POST['position'] ?? '');
    }

    $page = isset($_POST['sign_type']) && $_POST['sign_type'] === 'Vinyl' ? 'vinyl_list' : 'led_list';

    // --- ส่วนของการตรวจสอบเงื่อนไข (Validation) ---
    if ($start_date < $today) {
        header("Location: pages/$page.php?past=1");
        exit;
    }
    if (strtotime($start_date) > strtotime($end_date)) {
        header("Location: pages/$page.php?reverse=1");
        exit;
    }
    $diff_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
    if ($diff_days > 15) {
        header("Location: pages/$page.php?too_long=1");
        exit;
    }

    $sign_info_stmt = $conn->prepare("SELECT code, type, location FROM sign_boards WHERE id = ?");
    $sign_info_stmt->bind_param("i", $sign_board_id);
    $sign_info_stmt->execute();
    $sign_info = $sign_info_stmt->get_result()->fetch_assoc();
    $sign_code = $sign_info['code'] ?? '';
    $sign_type = $sign_info['type'] ?? '';
    $sign_location = $sign_info['location'] ?? '-';
    $sign_info_stmt->close();
    
    // ตรวจสอบการจองซ้ำ (เฉพาะป้าย Vinyl เท่านั้น)
    if ($sign_type === 'Vinyl') {
        $stmt_check = $conn->prepare("SELECT id FROM bookings WHERE sign_board_id = ? AND status NOT IN ('cancelled', 'rejected') AND (? < end_date AND ? > start_date)");
        $stmt_check->bind_param("iss", $sign_board_id, $start_date, $end_date);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            header("Location: pages/$page.php?conflict=1");
            exit;
        }
        $stmt_check->close();
    }

    // --- ส่วนการอัปโหลดไฟล์ ---
    $filename = null;
    if (isset($_FILES['sample_file']) && $_FILES['sample_file']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['sample_file']['size'] <= 10 * 1024 * 1024) { // 10MB
            $ext = pathinfo($_FILES['sample_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('sample_', true) . '.' . $ext;
            move_uploaded_file($_FILES['sample_file']['tmp_name'], __DIR__ . "/uploads/$filename");
        }
    }

    // --- คำสั่ง SQL INSERT ---
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (user_id, sign_board_id, requester_name, requester_phone, requester_email, title, user_status, start_date, end_date, sample_file, drive_link, type, faculty, workplace, position, year, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->bind_param(
        "iissssssssssssss",
        $user_id, $sign_board_id, $name, $phone, $email, $title,
        $user_status, $start_date, $end_date, $filename, $drive_link,
        $sign_type, $faculty, $workplace, $position, $year
    );

    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;

        // --- การสร้างข้อความแจ้งเตือน ---
        $auto_msg = "📣 แจ้งเตือนคำขอจองป้ายประชาสัมพันธ์\n"
            . "• เรื่อง: $title\n"
            . "• ผู้ยื่นคำขอ: $name\n"
            . "• เบอร์ติดต่อ: $phone\n"
            . "• อีเมล: $email\n"
            . "• สถานภาพ: $user_status\n"
            . ($user_status === 'นิสิต'
                ? "• คณะ: " . ($faculty ?: '-') . "\n• ชั้นปี: " . ($year ?: '-') . "\n"
                : "• หน่วยงาน: " . ($workplace ?: '-') . "\n• ตำแหน่ง: " . ($position ?: '-') . "\n")
            . "• วันที่ขอใช้งาน: $start_date ถึง $end_date\n"
            . "• ป้ายที่ขอใช้: $sign_code ($sign_type)\n"
            . "• ตำแหน่งติดตั้ง: $sign_location\n"
            . "• สถานะปัจจุบัน: ⏳ รออนุมัติ";

        $stmt_msg = $conn->prepare("INSERT INTO chat_messages (booking_id, sender, sender_id, message, is_read, created_at) VALUES (?, 'admin', 0, ?, 0, NOW())");
        $stmt_msg->bind_param("is", $booking_id, $auto_msg);
        $stmt_msg->execute();

        // สร้าง Session เพื่อส่งสัญญาณว่าเพิ่งจองเสร็จ
        $_SESSION['booking_just_completed'] = true;

        echo "
        <script src='https://cdn.socket.io/4.7.2/socket.io.min.js'></script>
        <script>
            const socket = io('http://10.88.88.171:3000');
            socket.on('connect', () => {
                socket.emit('send_message', {
                    booking_id: $booking_id,
                    sender: 'user',
                    sender_id: $user_id,
                    message: " . json_encode($auto_msg) . ",
                    created_at: new Date().toISOString()
                });
                // ส่งผู้ใช้ไปหน้าแชทหลังจากส่งข้อมูลสำเร็จ
                window.location.href = 'chat.php?booking_id=' + $booking_id;
            });
        </script>
        ";
        exit;
    } else {
        echo "<h4 class='text-danger'>\u274c เกิดข้อผิดพลาด: " . htmlspecialchars($stmt->error) . "</h4>";
    }
} else {
    header('Location: index.php');
    exit;
}
?>