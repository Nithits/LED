<?php
session_name('user_session');
session_start();
include("db.php");

// ตรวจสอบว่าได้ส่ง id มาหรือไม่
if (!isset($_GET['id'])) {
    header('Location: pages/page2.php');
    exit;
}

$id = (int) $_GET['id'];
$user_id_session = $_SESSION['user_id'] ?? 0;

// ดึงข้อมูลการจองพร้อมข้อมูลป้าย
$stmt = $conn->prepare("
    SELECT b.*, s.code, s.name AS sign_name, s.type, s.size, s.location
    FROM bookings b
    JOIN sign_boards s ON b.sign_board_id = s.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $id, $user_id_session);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    include('./components/top-navbar.php');
    echo "<div class='container my-5'><div class='alert alert-danger text-center'>ไม่พบข้อมูลการจอง หรือคุณไม่มีสิทธิ์เข้าถึงรายการนี้</div></div>";
    exit;
}

// --- [NEW] เตรียมตัวแปรสำหรับควบคุมฟอร์ม และข้อความแจ้งเตือน ---
$is_editable = ($booking['status'] === 'pending');
$alert_message = '';
if (isset($_GET['error']) && $_GET['error'] === 'not_editable') {
    $alert_message = '<div class="alert alert-danger mb-4"><i class="bi bi-lock-fill me-2"></i>ไม่สามารถแก้ไขการจองนี้ได้ เนื่องจากผู้ดูแลระบบได้ดำเนินการอนุมัติ/ปฏิเสธไปแล้ว หากต้องการเปลี่ยนแปลง กรุณาติดต่อผู้ดูแลระบบโดยตรงผ่านทางแชท</div>';
} elseif (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $alert_message = '<div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill me-2"></i>บันทึกการแก้ไขเรียบร้อยแล้ว</div>';
}
// --- End of New ---

// ดึงข้อมูลสำหรับ Dropdown
$faculties_result = $conn->query("SELECT name FROM faculties ORDER BY id ASC");
$faculties_list = [];
if ($faculties_result) { while ($row = $faculties_result->fetch_assoc()) { $faculties_list[] = $row['name']; } }

$workplaces_result = $conn->query("SELECT name FROM workplaces ORDER BY id ASC");
$workplaces_list = [];
if ($workplaces_result) { while ($row = $workplaces_result->fetch_assoc()) { $workplaces_list[] = $row['name']; } }

$student_years = ['ปี 1', 'ปี 2', 'ปี 3', 'ปี 4', 'สูงกว่าปริญญาตรี'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลการจอง</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/sysstyle.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .card { border: none; border-radius: 0.75rem; }
        .card-header { border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem; }
        .form-label { font-weight: 500; }
        input:disabled, select:disabled, textarea:disabled, button:disabled {
            background-color: #e9ecef !important;
            cursor: not-allowed;
            opacity: 0.7;
        }
        /* เพิ่ม CSS สำหรับวิดีโอ */
        #filePreview video {
            max-width: 100%;
            border-radius: 0.25rem;
        }
        #filePreview img {
             max-width: 250px; 
             max-height: 150px; 
             object-fit: cover;
        }
    </style>
</head>
<body>
<?php include('./components/top-navbar.php'); ?>
<div class="container my-4 my-lg-5">
    <div class="card shadow-lg">
        <div class="card-header bg-warning bg-gradient text-dark d-flex align-items-center">
            <a href="pages/page2.php" class="btn btn-light btn-sm text-dark me-3"><i class="bi bi-chevron-left me-2"></i> ย้อนกลับ</a>
            <h4 class="mb-0 py-2"><i class="bi bi-pencil-square me-2"></i> แก้ไขข้อมูลการจอง</h4>
        </div>

        <div class="card-body p-4">
            <?= $alert_message ?>
            <div class="row g-4">
                <div class="col-lg-8 order-lg-2">
                    <form action="update_booking.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                        <div class="form-content-scroll" style="max-height: 65vh; overflow-y: auto; padding-right: 1rem;">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-card-list me-2"></i>รายละเอียดการจอง</h5>
                            
                            <div class="mb-3"><label for="title" class="form-label">ชื่องาน/กิจกรรมประชาสัมพันธ์ *</label><input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($booking['title'] ?? '') ?>" <?= !$is_editable ? 'disabled' : '' ?> required></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label for="requester_name" class="form-label">ชื่อผู้จอง *</label><input type="text" name="requester_name" id="requester_name" class="form-control" value="<?= htmlspecialchars($booking['requester_name']) ?>" <?= !$is_editable ? 'disabled' : '' ?> required></div>
                                <div class="col-md-6 mb-3"><label for="requester_phone" class="form-label">เบอร์โทร *</label><input type="tel" name="requester_phone" id="requester_phone" class="form-control" value="<?= htmlspecialchars($booking['requester_phone']) ?>" <?= !$is_editable ? 'disabled' : '' ?> required></div>
                            </div>
                            <div class="mb-3"><label for="requester_email" class="form-label">อีเมล *</label><input type="email" name="requester_email" id="requester_email" class="form-control" value="<?= htmlspecialchars($booking['requester_email']) ?>" <?= !$is_editable ? 'disabled' : '' ?> required></div>
                            <div class="mb-3">
                                <label for="user_status" class="form-label">สถานภาพ *</label>
                                <select name="user_status" id="user_status" class="form-select" <?= !$is_editable ? 'disabled' : '' ?> required>
                                    <option value="นิสิต" <?= ($booking['user_status'] ?? '') === 'นิสิต' ? 'selected' : '' ?>>นิสิต</option>
                                    <option value="บุคลากร" <?= ($booking['user_status'] ?? '') === 'บุคลากร' ? 'selected' : '' ?>>บุคลากร</option>
                                </select>
                            </div>
                            <div id="studentFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">คณะของนิสิต *</label><select name="faculty" id="student_faculty" class="form-select" <?= !$is_editable ? 'disabled' : '' ?>><option value="">-- เลือกคณะ --</option><?php foreach ($faculties_list as $faculty_name){ echo "<option value='".htmlspecialchars($faculty_name)."' ".(($booking['faculty'] ?? '') === $faculty_name ? 'selected' : '').">".htmlspecialchars($faculty_name)."</option>"; } ?></select></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">ชั้นปี *</label><select name="year" id="student_year" class="form-select" <?= !$is_editable ? 'disabled' : '' ?>><option value="">-- เลือกชั้นปี --</option><?php foreach ($student_years as $year){ echo "<option value='".htmlspecialchars($year)."' ".(($booking['year'] ?? '') === $year ? 'selected' : '').">".htmlspecialchars($year)."</option>"; } ?></select></div>
                                </div>
                            </div>
                            <div id="staffFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">หน่วยงาน/คณะ *</label><select name="workplace" id="staff_workplace" class="form-select" <?= !$is_editable ? 'disabled' : '' ?>><option value="">-- เลือกหน่วยงาน/คณะ --</option><?php foreach ($workplaces_list as $workplace_name){ echo "<option value='".htmlspecialchars($workplace_name)."' ".(($booking['workplace'] ?? '') === $workplace_name ? 'selected' : '').">".htmlspecialchars($workplace_name)."</option>"; } ?></select></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">ตำแหน่ง *</label><input type="text" name="position" id="staff_position" class="form-control" value="<?= htmlspecialchars($booking['position'] ?? '') ?>" placeholder="ระบุตำแหน่ง" <?= !$is_editable ? 'disabled' : '' ?>></div>
                                </div>
                            </div>
                            
                            <h5 class="border-bottom pb-2 mt-4 mb-3"><i class="bi bi-paperclip me-2"></i>ไฟล์และลิงก์</h5>
                            <div class="mb-3"><label class="form-label">ลิงก์ Google Drive (ถ้ามี)</label><input type="url" name="google_drive_link" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($booking['drive_link'] ?? '') ?>" <?= !$is_editable ? 'disabled' : '' ?>></div>
                            
                            <div class="mb-3">
                                <label for="formFile" class="form-label">อัปโหลดไฟล์ใหม่ (jpg, png, mp4)</label>
                                <input class="form-control" type="file" name="new_image" id="formFile" accept=".jpg,.jpeg,.png,.mp4" <?= !$is_editable ? 'disabled' : '' ?>>
                                <div class="form-text">หากไม่ต้องการเปลี่ยนไฟล์ ไม่ต้องอัปโหลดใหม่</div>
                                
                                <?php 
                                    $initial_file_path = !empty($booking['sample_file']) 
                                        ? (str_starts_with($booking['sample_file'], 'uploads/') 
                                            ? htmlspecialchars($booking['sample_file']) 
                                            : 'uploads/' . htmlspecialchars($booking['sample_file'])) 
                                        : '';
                                    $file_extension = !empty($initial_file_path) ? strtolower(pathinfo($initial_file_path, PATHINFO_EXTENSION)) : '';
                                ?>
                                <div class="mt-2" id="imagePreviewContainer" style="<?= empty($initial_file_path) ? 'display: none;' : '' ?>">
                                    <p class="mb-1 fw-bold small">ตัวอย่างไฟล์ปัจจุบัน:</p>
                                    <div id="filePreview">
                                        <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <a href="<?= $initial_file_path ?>" target="_blank">
                                                <img src="<?= $initial_file_path ?>" alt="ตัวอย่างรูปภาพ" class="img-fluid rounded border" />
                                            </a>
                                        <?php elseif (in_array($file_extension, ['mp4', 'webm'])): ?>
                                            <video width="320" height="240" controls>
                                                <source src="<?= $initial_file_path ?>" type="video/<?= $file_extension ?>">
                                                เบราว์เซอร์ของคุณไม่รองรับการแสดงผลวิดีโอ
                                            </video>
                                        <?php elseif (!empty($initial_file_path)): ?>
                                            <p class="text-muted">ไม่สามารถแสดงตัวอย่างไฟล์ <a href="<?= $initial_file_path ?>" target="_blank">คลิกเพื่อดาวน์โหลด</a></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="pages/page2.php" class="btn btn-dark"><i class="bi bi-x-circle me-1"></i>ยกเลิก</a>
                            <button type="submit" class="btn btn-warning" <?= !$is_editable ? 'disabled' : '' ?>><i class="bi bi-save me-1"></i>บันทึกการแก้ไข</button>
                        </div>
                    </form>
                </div>
                
                <div class="col-lg-4 order-lg-1">
                    <div class="position-sticky" style="top: 1.5rem;">
                         <div class="mb-4">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-info-circle-fill me-2"></i>ข้อมูลป้ายที่เลือก</h5>
                            <div class="p-3 rounded" style="background-color: #f8f9fa;">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">รหัสป้าย:</dt><dd class="col-sm-8"><?= htmlspecialchars($booking['code']) ?></dd>
                                    <dt class="col-sm-4">ชื่อป้าย:</dt><dd class="col-sm-8 text-break"><?= htmlspecialchars($booking['sign_name']) ?></dd>
                                    <dt class="col-sm-4">ประเภท:</dt><dd class="col-sm-8"><?= htmlspecialchars($booking['type']) ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-calendar-range-fill me-2"></i>ระยะเวลาติดตั้ง</h5>
                            <div class="p-3 rounded" style="background-color: #f8f9fa;">
                                <div class="mb-2"><label class="form-label small text-muted">วันที่เริ่มติดตั้ง</label><input type="date" class="form-control" value="<?= $booking['start_date'] ?>" readonly disabled></div>
                                <div><label class="form-label small text-muted">วันที่สิ้นสุด</label><input type="date" class="form-control" value="<?= $booking['end_date'] ?>" readonly disabled></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const userStatusSelect = document.getElementById('user_status');
    const studentFields = document.getElementById('studentFields');
    const staffFields = document.getElementById('staffFields');
    const studentFaculty = document.getElementById('student_faculty');
    const studentYear = document.getElementById('student_year');
    const staffPosition = document.getElementById('staff_position');
    const staffWorkplace = document.getElementById('staff_workplace');
    const fileInput = document.getElementById('formFile');
    const filePreview = document.getElementById('filePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');

    function toggleUserFields() {
        if (!userStatusSelect) return;
        const selectedStatus = userStatusSelect.value;
        studentFields.style.display = (selectedStatus === 'นิสิต') ? 'block' : 'none';
        staffFields.style.display = (selectedStatus === 'บุคลากร') ? 'block' : 'none';
        if(studentFaculty && studentYear) {
            studentFaculty.required = (selectedStatus === 'นิสิต');
            studentYear.required = (selectedStatus === 'นิสิต');
        }
        if(staffPosition && staffWorkplace) {
            staffPosition.required = (selectedStatus === 'บุคลากร');
            staffWorkplace.required = (selectedStatus === 'บุคลากร');
        }
    }

    if(userStatusSelect) {
        userStatusSelect.addEventListener('change', toggleUserFields);
        toggleUserFields();
    }

    if(fileInput) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    filePreview.innerHTML = ''; 
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-fluid rounded border';
                        filePreview.appendChild(img);
                    } else if (file.type.startsWith('video/')) {
                        const video = document.createElement('video');
                        video.src = e.target.result;
                        video.width = 320;
                        video.height = 240;
                        video.controls = true;
                        filePreview.appendChild(video);
                    }
                    imagePreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
</body>
</html>