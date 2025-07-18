<?php
session_name('admin_session');
session_start();
include(__DIR__ . '/db.php');

// 1. ตรวจสอบสิทธิ์การเข้าถึงของแอดมิน
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

// 2. ตรวจสอบว่ามี ID ส่งมาหรือไม่
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_bookings.php');
    exit;
}
$id = (int)$_GET['id'];

// 3. ดึงข้อมูลการจองจากฐานข้อมูล
$stmt = $conn->prepare("
    SELECT b.*, s.code, s.name AS sign_name, s.type, s.size, s.location
    FROM bookings b
    JOIN sign_boards s ON b.sign_board_id = s.id
    WHERE b.id = ?
");

if ($stmt === false) {
    die("Error preparing the statement: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

// 4. เตรียมข้อมูลสำหรับ Dropdowns และคำนวณเพิ่มเติม
if ($booking) {
    $faculties_result = $conn->query("SELECT name FROM faculties ORDER BY name ASC");
    $faculties_list = $faculties_result ? $faculties_result->fetch_all(MYSQLI_ASSOC) : [];

    $workplaces_result = $conn->query("SELECT name FROM workplaces ORDER BY name ASC");
    $workplaces_list = $workplaces_result ? $workplaces_result->fetch_all(MYSQLI_ASSOC) : [];

    $student_years = ['ปี 1', 'ปี 2', 'ปี 3', 'ปี 4', 'สูงกว่าปริญญาตรี'];

    // คำนวณระยะเวลาจอง
    $startDate = new DateTime($booking['start_date']);
    $endDate = new DateTime($booking['end_date']);
    $duration_interval = $startDate->diff($endDate);
    $booking_duration = $duration_interval->days + 1;
}


// 5. ฟังก์ชันสำหรับสร้าง Badge สถานะและฟังก์ชันจัดรูปแบบวันที่
function getStatusBadge($status) {
    $badges = [
        // [EDITED] เปลี่ยนข้อความ
        'pending'     => ['class' => 'bg-warning text-dark', 'icon' => 'bi-clock-history', 'text' => 'รออนุมัติ'],
        'approved'    => ['class' => 'bg-success', 'icon' => 'bi-check-circle-fill', 'text' => 'อนุมัติแล้ว'],
        'rejected'    => ['class' => 'bg-danger', 'icon' => 'bi-x-circle-fill', 'text' => 'ถูกปฏิเสธ'],
        'in_progress' => ['class' => 'bg-info', 'icon' => 'bi-arrow-repeat', 'text' => 'ดำเนินการเเล้ว']
    ];
    return $badges[$status] ?? ['class' => 'bg-secondary', 'icon' => 'bi-question-circle', 'text' => 'ไม่ระบุ'];
}

function formatDateThai($date) {
    if (!$date) return '-';
    if (class_exists('IntlDateFormatter')) {
        try {
            $formatter = new IntlDateFormatter('th_TH', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
            return $formatter->format(strtotime($date));
        } catch (Exception $e) { /* Fallback */ }
    }
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $ts = strtotime($date);
    return date("j", $ts) . " " . $months[(int)date("n", $ts)] . " " . (date("Y", $ts) + 543);
}

$status_info = $booking ? getStatusBadge($booking['status']) : getStatusBadge(null);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดการจอง #<?= htmlspecialchars($id) ?> (แอดมิน)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .sidebar-container { background-color: #5a4e8c; color: white; min-height: 100vh; position: sticky; top: 0; }
        .sidebar-container a { color: white; text-decoration: none; }
        .sidebar-container .nav-link:hover, .sidebar-container .nav-link.active { background-color: #7c6db3; border-radius: 5px; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .card-header { border-radius: 0.75rem 0.75rem 0 0 !important; }
        .info-label { font-weight: 500; color: #6c757d; }
        .btn-toggle-edit.active { background-color: #6c757d; border-color: #6c757d; }
        .artwork-preview-container { max-width: 100%; border: 1px solid #dee2e6; padding: 0.5rem; border-radius: 0.5rem; background-color: #f8f9fa; }
        .artwork-preview-container img,
        .artwork-preview-container video {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        <?php include("admin_sidebar.php"); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <?php if (!$booking): ?>
                <div class="alert alert-danger text-center">
                    <h4 class="alert-heading">เกิดข้อผิดพลาด</h4>
                    <p>ไม่พบข้อมูลการจองสำหรับ ID: <strong><?= htmlspecialchars($id) ?></strong></p>
                    <hr>
                    <a href="admin_bookings.php" class="btn btn-danger">กลับไปหน้ารายการจอง</a>
                </div>
            <?php else: ?>

            <div class="d-flex align-items-center gap-3 mb-3">
                <a href="admin_bookings.php" class="btn btn-secondary"><i class="bi bi-chevron-left"></i> กลับ</a>
                <h4 class="mb-0 border-start ps-3">รายละเอียดการจอง #<?= htmlspecialchars($booking['id']) ?></h4>
            </div>
            <div class="card mb-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">สถานะปัจจุบัน</h6>
                            <span class="badge fs-6 <?= $status_info['class'] ?>">
                                <i class="bi <?= $status_info['icon'] ?> me-1"></i> <?= $status_info['text'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <form id="bookingForm" action="admin_update_booking.php" method="POST">
                <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>ข้อมูลผู้จอง</h6>
                                <button type="button" id="toggleEditBtn" class="btn btn-sm btn-outline-primary btn-toggle-edit">
                                    <i class="bi bi-pencil"></i> แก้ไขข้อมูล
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 mb-2"><label class="info-label">ชื่องาน/กิจกรรม</label><input type="text" name="title" class="form-control" value="<?= htmlspecialchars($booking['title']) ?>" readonly></div>
                                    <div class="col-md-6 mb-2"><label class="info-label">ชื่อผู้จอง</label><input type="text" name="requester_name" class="form-control" value="<?= htmlspecialchars($booking['requester_name']) ?>" readonly></div>
                                    <div class="col-md-6 mb-2"><label class="info-label">เบอร์โทร</label><input type="text" name="requester_phone" class="form-control" value="<?= htmlspecialchars($booking['requester_phone']) ?>" readonly></div>
                                    <div class="col-12 mb-2"><label class="info-label">อีเมล</label><input type="email" name="requester_email" class="form-control" value="<?= htmlspecialchars($booking['requester_email']) ?>" readonly></div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="info-label">สถานภาพ</label>
                                        <select name="user_status" id="user_status" class="form-select" disabled>
                                            <option value="นิสิต" <?= ($booking['user_status'] == 'นิสิต') ? 'selected' : '' ?>>นิสิต</option>
                                            <option value="บุคลากร" <?= ($booking['user_status'] == 'บุคลากร') ? 'selected' : '' ?>>บุคลากร</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8" id="studentFields">
                                        <div class="row">
                                            <div class="col-md-7 mb-2"><label class="info-label">คณะ</label>
                                                <select name="faculty" class="form-select" disabled>
                                                    <?php foreach ($faculties_list as $f) { $selected = ($f['name'] == $booking['faculty']) ? 'selected' : ''; echo "<option value='".htmlspecialchars($f['name'])."' $selected>".htmlspecialchars($f['name'])."</option>"; } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-5 mb-2"><label class="info-label">ชั้นปี</label>
                                                <select name="year" class="form-select" disabled>
                                                    <?php foreach ($student_years as $y) { $selected = ($y == $booking['year']) ? 'selected' : ''; echo "<option value='".htmlspecialchars($y)."' $selected>".htmlspecialchars($y)."</option>"; } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8" id="staffFields">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="info-label">หน่วยงาน</label>
                                                <select name="workplace" class="form-select" disabled>
                                                    <option value="">-- เลือกหน่วยงาน --</option>
                                                    <?php foreach ($workplaces_list as $w) {
                                                        $selected = ($w['name'] == $booking['workplace']) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($w['name']) . "' $selected>" . htmlspecialchars($w['name']) . "</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="info-label">ตำแหน่ง</label>
                                                <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($booking['position'] ?? '') ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['sample_file'])): 
                            $filePath = str_starts_with($booking['sample_file'], 'uploads/') 
                                ? htmlspecialchars($booking['sample_file']) 
                                : 'uploads/' . htmlspecialchars($booking['sample_file']);
                            $fileExtension = strtolower(pathinfo($booking['sample_file'], PATHINFO_EXTENSION));
                        ?>
                        <div class="card mb-4">
                            <div class="card-header"><h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>ไฟล์งานออกแบบ</h6></div>
                            <div class="card-body text-center">
                                <div class="artwork-preview-container mb-3">
                                    <?php if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <img src="<?= $filePath ?>" alt="Artwork Preview">
                                    <?php elseif (in_array($fileExtension, ['mp4', 'webm', 'ogg'])): ?>
                                        <video width="100%" controls>
                                            <source src="<?= $filePath ?>" type="video/<?= $fileExtension ?>">
                                            เบราว์เซอร์ของคุณไม่รองรับการแสดงผลวิดีโอ
                                        </video>
                                    <?php else: ?>
                                        <p class="text-muted my-3">
                                            <i class="bi bi-file-earmark-text fs-1"></i><br>
                                            ไม่สามารถแสดงตัวอย่างไฟล์ประเภท .<?= $fileExtension ?> ได้
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <a href="<?= $filePath ?>" class="btn btn-outline-dark" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-download me-2"></i>ดาวน์โหลดไฟล์ต้นฉบับ
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                    <div class="col-lg-5">
                        <div class="card mb-4 sticky-top" style="top: 20px;">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>สำหรับผู้ดูแลระบบ</h6>
                            </div>
                            <div class="card-body">
                                
                                <h6 class="info-label mb-2"><i class="bi bi-signpost-2-fill me-2"></i>ข้อมูลป้ายที่จอง</h6>
                                <dl class="row mb-2">
                                    <dt class="col-sm-4 info-label">รหัสป้าย:</dt><dd class="col-sm-8 "><?= htmlspecialchars($booking['code']) ?></dd>
                                    <dt class="col-sm-4 info-label">ชื่อป้าย:</dt><dd class="col-sm-8 "><?= htmlspecialchars($booking['sign_name']) ?></dd>
                                    <dt class="col-sm-4 info-label">ประเภท:</dt><dd class="col-sm-8 "><?= htmlspecialchars($booking['type']) ?></dd>
                                    <dt class="col-sm-4 info-label">ขนาด:</dt><dd class="col-sm-8 "><?= htmlspecialchars($booking['size']) ?></dd>
                                    <dt class="col-sm-4 info-label">ตำแหน่ง:</dt><dd class="col-sm-8 "><?= htmlspecialchars($booking['location']) ?></dd>
                                </dl>
                                
                                <hr> <div class="mb-3">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="start_date_input" class="info-label d-block">วันที่เริ่มจอง</label>
                                            <input type="date" class="form-control" id="start_date_input" name="start_date" 
                                                value="<?= htmlspecialchars($booking['start_date']) ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="end_date_input" class="info-label d-block">วันที่สิ้นสุด</label>
                                            <input type="date" class="form-control" id="end_date_input" name="end_date"
                                                value="<?= htmlspecialchars($booking['end_date']) ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <span class="badge bg-secondary">รวมระยะเวลา <?= $booking_duration ?> วัน</span>
                                    </div>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">สถานะการจอง</label>
                                    <select name="status" class="form-select form-select-lg">
                                        <option value="pending" <?= ($booking['status'] == 'pending') ? 'selected' : '' ?>>รออนุมัติ</option>
                                        <option value="approved" <?= ($booking['status'] == 'approved') ? 'selected' : '' ?>>อนุมัติ</option>
                                        <option value="rejected" <?= ($booking['status'] == 'rejected') ? 'selected' : '' ?>>ปฏิเสธ</option>
                                        <option value="in_progress" <?= ($booking['status'] == 'in_progress') ? 'selected' : '' ?>>ดำเนินการแล้ว</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">หมายเหตุ (ถ้ามี)</label>
                                    <textarea name="note" class="form-control" rows="3" placeholder="เช่น เหตุผลที่ปฏิเสธ, คำแนะนำเพิ่มเติม"><?= htmlspecialchars($booking['note'] ?? '') ?></textarea>
                                </div>
                                <hr>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-2"></i>บันทึกการเปลี่ยนแปลง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('bookingForm')) {
        const userStatusSelect = document.getElementById('user_status');
        const studentFields = document.getElementById('studentFields');
        const staffFields = document.getElementById('staffFields');
        const formFieldsToToggle = document.querySelectorAll('#bookingForm input:not([type=hidden]), #bookingForm select:not([name=status])');
        const toggleEditBtn = document.getElementById('toggleEditBtn');
        let isEditMode = false;

        function toggleUserSpecificFields() {
            const selectedStatus = userStatusSelect.value;
            studentFields.style.display = (selectedStatus === 'นิสิต') ? 'block' : 'none';
            staffFields.style.display = (selectedStatus === 'บุคลากร') ? 'block' : 'none';
        }

        userStatusSelect.addEventListener('change', toggleUserSpecificFields);
        toggleUserSpecificFields(); // Call on page load to set initial state

        toggleEditBtn.addEventListener('click', function() {
            isEditMode = !isEditMode;
            this.classList.toggle('active', isEditMode);
            this.innerHTML = isEditMode ? '<i class="bi bi-x-lg"></i> ยกเลิก' : '<i class="bi bi-pencil"></i> แก้ไขข้อมูล';

            formFieldsToToggle.forEach(field => {
                // This targets all fields except the main status dropdown
                if (field.name !== 'status') {
                    // For input, toggle readonly. For select, toggle disabled.
                    if (field.tagName.toLowerCase() === 'select') {
                        field.disabled = !isEditMode;
                    } else {
                        field.readOnly = !isEditMode;
                    }
                }
            });
        });
    }
});
</script>
</body>
</html>