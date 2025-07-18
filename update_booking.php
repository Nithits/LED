<?php
session_name('user_session');
session_start();
include(__DIR__ . '/db.php');

// 1. ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit;
}

// 2. ตรวจสอบว่าเป็น POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 403 Forbidden");
    echo "ไม่อนุญาตให้เข้าถึงโดยตรง";
    exit;
}

// 3. รับข้อมูลจากฟอร์มทั้งหมด
$id = $_POST['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (empty($id)) {
    die("เกิดข้อผิดพลาด: ไม่พบ ID ของรายการ");
}

try {
    // 4. ตรวจสอบความเป็นเจ้าของและสถานะปัจจุบัน
    $stmt_check = $conn->prepare("SELECT * FROM bookings WHERE id = ?"); // ดึงข้อมูลทั้งหมดมาเพื่อเปรียบเทียบ
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $existing_booking = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$existing_booking || $existing_booking['user_id'] != $user_id) {
        die("เกิดข้อผิดพลาด: คุณไม่มีสิทธิ์แก้ไขรายการนี้");
    }

    // ป้องกันการแก้ไข หากสถานะไม่ใช่ 'pending'
    if ($existing_booking['status'] !== 'pending') {
        header("Location: edit_booking.php?id=$id&error=not_editable");
        exit;
    }

    $current_image = $existing_booking['sample_file'];

    // 5. รับและเตรียมข้อมูล
    $title = trim($_POST['title'] ?? '');
    $requester_name = trim($_POST['requester_name'] ?? '');
    $requester_phone = trim($_POST['requester_phone'] ?? '');
    $requester_email = trim($_POST['requester_email'] ?? '');
    $user_status = $_POST['user_status'] ?? '';
    $drive_link = trim($_POST['google_drive_link'] ?? '');

    $faculty_to_save = null;
    $year_to_save = null;
    $workplace_to_save = null;
    $position_to_save = null;

    if ($user_status === 'นิสิต') {
        $faculty_to_save = trim($_POST['faculty'] ?? '');
        $year_to_save = trim($_POST['year'] ?? '');
    } elseif ($user_status === 'บุคลากร') {
        $workplace_to_save = trim($_POST['workplace'] ?? '');
        $position_to_save = trim($_POST['position'] ?? '');
    }

    // 6. จัดการการอัปโหลดไฟล์ (ส่วนนี้เหมือนเดิม)
    $file_path_to_update = $current_image;

    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        $file_tmp = $_FILES['new_image']['tmp_name'];
        $file_name = basename($_FILES['new_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['new_image']['size'];

        // ✅ เพิ่มการตรวจสอบขนาดไฟล์ (10MB = 10 * 1024 * 1024)
        if ($file_size > 10 * 1024 * 1024) {
            die("ขนาดไฟล์ต้องไม่เกิน 10 MB");
        }

        // ✅ เพิ่มนามสกุลที่รองรับ
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'mp4', 'webm', 'ogg'];

        if (!in_array($file_ext, $allowed_exts)) {
            die("ประเภทไฟล์ไม่รองรับ (รองรับ: jpg, png, mp4, pdf, เป็นต้น)");
        }

        // สร้างชื่อไฟล์ใหม่แบบสุ่ม
        $new_file_name = uniqid('sample_', true) . '.' . $file_ext;
        $new_file_path = $upload_dir . $new_file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($file_tmp, $new_file_path)) {
            // ลบไฟล์เก่า (ถ้ามี)
            if (!empty($current_image)) {
                $old_file_path = __DIR__ . '/' . $current_image;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }

            // บันทึก path ใหม่แบบ relative
            $file_path_to_update = 'uploads/' . $new_file_name;
        } else {
            die("อัปโหลดไฟล์ไม่สำเร็จ");
        }
    }

    // 7. อัปเดตข้อมูลลงฐานข้อมูล (ส่วนนี้เหมือนเดิม)
    $stmt = $conn->prepare("
        UPDATE bookings SET
            requester_name = ?, requester_phone = ?, requester_email = ?,
            user_status = ?, title = ?, faculty = ?, year = ?,
            workplace = ?, position = ?, drive_link = ?, sample_file = ? 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("sssssssssssii", 
        $requester_name, $requester_phone, $requester_email, $user_status, $title, 
        $faculty_to_save, $year_to_save, $workplace_to_save, $position_to_save, 
        $drive_link, $file_path_to_update, $id, $user_id
    );

    if ($stmt->execute()) {
        // --- [NEW] สร้างสรุปการเปลี่ยนแปลงเพื่อส่งแชท ---
        $changes_summary = [];

        // ฟังก์ชันช่วยเปรียบเทียบและเพิ่มลงใน list
        function add_change(string $label, ?string $old, ?string $new, array &$changes) {
            $old_val = $old ?? '';
            $new_val = $new ?? '';
            if ($old_val !== $new_val) {
                $changes[] = "• $label: '{$old_val}' → '{$new_val}'";
            }
        }

        // เปรียบเทียบแต่ละฟิลด์
        add_change("เรื่อง", $existing_booking['title'], $title, $changes_summary);
        add_change("ชื่อผู้จอง", $existing_booking['requester_name'], $requester_name, $changes_summary);
        add_change("เบอร์โทร", $existing_booking['requester_phone'], $requester_phone, $changes_summary);
        add_change("อีเมล", $existing_booking['requester_email'], $requester_email, $changes_summary);
        add_change("สถานภาพ", $existing_booking['user_status'], $user_status, $changes_summary);
        
        if ($user_status === 'นิสิต') {
            add_change("คณะ", $existing_booking['faculty'], $faculty_to_save, $changes_summary);
            add_change("ชั้นปี", $existing_booking['year'], $year_to_save, $changes_summary);
        } elseif ($user_status === 'บุคลากร') {
            add_change("หน่วยงาน", $existing_booking['workplace'], $workplace_to_save, $changes_summary);
            add_change("ตำแหน่ง", $existing_booking['position'], $position_to_save, $changes_summary);
        }
        
        add_change("ลิงก์ Drive", $existing_booking['drive_link'], $drive_link, $changes_summary);

        if ($file_path_to_update !== $current_image) {
            $changes_summary[] = "• มีการเปลี่ยนแปลงไฟล์แนบ";
        }
        
        // ประกอบร่างข้อความสุดท้าย
        if (!empty($changes_summary)) {
            $message_body = implode("\n", $changes_summary);
            $chat_message_to_admin = "👤 **ผู้ใช้แก้ไขข้อมูลการจอง (ID: #{$id})**\n"
                                   . "---------------------------------\n"
                                   . "ผู้แก้ไข: {$requester_name}\n\n"
                                   . "**รายการที่เปลี่ยนแปลง:**\n"
                                   . $message_body;
        } else {
            // กรณีที่กดบันทึกแต่ไม่มีอะไรเปลี่ยน
            $chat_message_to_admin = "👤 ผู้ใช้กดบันทึกการจอง (ID: #{$id}) แต่ไม่มีการเปลี่ยนแปลงข้อมูล";
        }

        $stmt_msg = $conn->prepare("INSERT INTO chat_messages (booking_id, sender, sender_id, message, is_read, created_at) VALUES (?, 'user', ?, ?, 0, NOW())");
        $stmt_msg->bind_param("iis", $id, $user_id, $chat_message_to_admin);
        $stmt_msg->execute();

        echo "
        <script src='https://cdn.socket.io/4.7.2/socket.io.min.js'></script>
        <script>
            const socket = io('http://10.88.88.171:3000');
            socket.on('connect', () => {
                socket.emit('send_message', {
                    booking_id: " . $id . ",
                    sender: 'user',
                    sender_id: " . $user_id . ",
                    message: " . json_encode($chat_message_to_admin) . ",
                    created_at: new Date().toISOString()
                });
                setTimeout(() => { window.location.href = 'edit_booking.php?id=$id&updated=1'; }, 500);
            });
        </script>
        ";
        exit;
    } else {
        throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error);
    }
} catch (Exception $e) {
    die($e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    if(isset($stmt_msg)) $stmt_msg->close();
    $conn->close();
}
?>