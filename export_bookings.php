<?php
session_name('admin_session');
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

require_once 'db.php';

// --- รับค่าจากฟอร์มทั้งหมด ---
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$sign_board_id = $_GET['sign_board_id'] ?? 'all';
$user_status = $_GET['user_status'] ?? 'all';
$booking_status = $_GET['booking_status'] ?? 'all';

// --- สร้างชื่อไฟล์แบบไดนามิก ---
$filename_parts = ['bookings-export', date('Y-m-d')];
$filename = implode('-', $filename_parts) . '.csv';

// --- ตั้งค่า Header ---
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputs($output, "\xEF\xBB\xBF"); // BOM for UTF-8 Excel compatibility

// --- [FIXED] กำหนดหัวข้อคอลัมน์ (นำ 'ตำแหน่ง' ออก) ---
fputcsv($output, [
    'ID', 'รหัสป้าย', 'ประเภทป้าย', 'หัวข้อ/รายละเอียด', 'ชื่อผู้ขอจอง',
    'ประเภทผู้ใช้', 'เบอร์โทรศัพท์', 'อีเมล', 'วันที่เริ่มจอง', 'วันที่สิ้นสุด',
    'สถานะการจอง', 'คณะ/หน่วยงาน', 'วันที่ทำรายการ'
]);

// --- [FIXED] สร้าง SQL Query (ดึง workplace แต่ไม่ดึง position) ---
$sql = "SELECT b.id, sb.code AS sign_board_code, sb.type AS sign_board_type, b.title, b.requester_name, 
               b.user_status, b.requester_phone, b.requester_email, b.start_date, b.end_date, 
               b.status, b.faculty, b.workplace, b.created_at
        FROM bookings AS b
        LEFT JOIN sign_boards AS sb ON b.sign_board_id = sb.id";

$conditions = [];
$params = [];
$types = '';

// (ส่วนเงื่อนไข WHERE ไม่มีการเปลี่ยนแปลง)
if (!empty($start_date)) { $conditions[] = "b.start_date >= ?"; $params[] = $start_date; $types .= 's'; }
if (!empty($end_date)) { $conditions[] = "b.start_date <= ?"; $params[] = $end_date; $types .= 's'; }
if ($sign_board_id !== 'all') { $conditions[] = "b.sign_board_id = ?"; $params[] = $sign_board_id; $types .= 'i'; }
if ($user_status !== 'all') { $conditions[] = "b.user_status = ?"; $params[] = $user_status; $types .= 's'; }
if ($booking_status !== 'all') { $conditions[] = "b.status = ?"; $params[] = $booking_status; $types .= 's'; }

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY b.id DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die('Prepare failed: ' . htmlspecialchars($conn->error)); }

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$booking_count = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $booking_count++;

        $status_thai = 'ไม่ระบุ';
        switch ($row['status']) {
            case 'pending': $status_thai = 'รออนุมัติ'; break;
            case 'approved': $status_thai = 'อนุมัติแล้ว'; break;
            case 'rejected': $status_thai = 'ไม่อนุมัติ'; break;
            case 'in_progress': $status_thai = 'ดำเนินการแล้ว'; break;
            case 'cancelled': $status_thai = 'ยกเลิกแล้ว'; break;
        }

        // --- [FIXED] เพิ่มตรรกะในการเลือกแสดงผล คณะ/หน่วยงาน ---
        $faculty_or_workplace = '';
        if ($row['user_status'] === 'นิสิต') {
            $faculty_or_workplace = $row['faculty'];
        } elseif ($row['user_status'] === 'บุคลากร') {
            $faculty_or_workplace = $row['workplace'];
        }

        fputcsv($output, [
            $row['id'], 
            $row['sign_board_code'], 
            $row['sign_board_type'],
            $row['title'],
            $row['requester_name'], 
            $row['user_status'], 
            $row['requester_phone'],
            $row['requester_email'], 
            $row['start_date'], 
            $row['end_date'],
            $status_thai, 
            $faculty_or_workplace, // ใช้ตัวแปรที่เตรียมไว้
            $row['created_at']
        ]);
    }
}

fputcsv($output, []);
fputcsv($output, ['รวมทั้งสิ้น', $booking_count, 'รายการ']);

$stmt->close();
$conn->close();
fclose($output);
exit();
?>