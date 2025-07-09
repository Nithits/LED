<?php
session_name('user_session');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit;
}
include(__DIR__ . '/../db.php');

// ตรวจสอบค่าจาก URL และแสดงแจ้งเตือน
$alert = '';
if (isset($_GET['conflict'])) {
    $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-x-circle-fill me-2"></i>ไม่สามารถจองได้: มีการจองซ้ำในช่วงเวลาดังกล่าว
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
} elseif (isset($_GET['too_long'])) {
    $alert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>ไม่สามารถจองเกิน 15 วัน
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
} elseif (isset($_GET['past'])) {
    $alert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>ไม่สามารถจองย้อนหลังได้
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
}
if (isset($_GET['reverse'])) {
    $alert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i> ไม่สามารถจองได้: วันที่เริ่มต้องไม่มากกว่าวันที่สิ้นสุด
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
}

$led_boards = $conn->query("SELECT * FROM sign_boards WHERE type = 'LED' AND version = 'new'");
$faculty_list = $conn->query("SELECT name FROM faculties ORDER BY name");
$workplace_list = $conn->query("SELECT name FROM workplaces ORDER BY name");

// ทำให้สามารถ reuse ได้หลายรอบ
$faculties = $faculty_list ? $faculty_list->fetch_all(MYSQLI_ASSOC) : [];
$workplaces = $workplace_list ? $workplace_list->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายการป้ายประชาสัมพันธ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/sysstyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../images/iconmsu.ico">
</head>
<body>

<?php include('../components/top-navbar.php'); ?>

<div class="container">
    <?= $alert ?>
    <div class="container py-4">
        <div class="card p-4 shadow-lg" style="border-radius: 12px; background-color: #ffffff;">
            <div class="card p-2 shadow-sm" style="border-radius: 12px; background-color: rgb(24, 23, 23); margin-bottom: 15px;">
                <div class="card-body d-flex align-items-center" style="height: 50px;">
                    <a href="select_sign.php" class="btn btn-outline-light btn-sm d-flex align-items-center" style="padding: 5px 10px;">
                        <i class="bi bi-chevron-left me-1" style="font-size: 1rem;"></i> กลับ
                    </a>
                    <h4 class="section-title mb-0" style="font-size: 1.4rem; color: white; font-weight: 600; text-align: center; width: 100%;">
                        รายการป้าย LED
                    </h4>
                </div>
            </div>

            <div class="row row-cols-1 g-4 mb-4">
                <?php while ($row = $led_boards->fetch_assoc()) echo renderSignCard($row); ?>
            </div>

            <div class="row mb-4">
                <div class="col">
                    <?php include('../components/led_schedule.php'); ?>
                </div>
            </div>

            <?php require_once("../components/footer.php"); ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function renderSignCard($row) {
    global $faculties, $workplaces;

    ob_start();
    $modalId = "bookingModal" . $row['id'];
    $code = htmlspecialchars($row['code']);
    $name = htmlspecialchars($row['name'] ?? '-');
    $type = strtolower($row['type']);
    $typeLabel = strtoupper($type);
?>
    <div class="col">
        <div class="card h-100 d-flex flex-row shadow-lg border-0" style="border-radius: 15px; overflow: hidden; height: 400px;">
            <img src="../images/<?= strtolower($row['image_url'] ?? 'default.jpg') ?>" class="card-img-left" style="width: 50%; object-fit: cover; border-right: 2px solid #ddd; border-radius: 10px 0 0 10px;">
            <div class="card-body d-flex flex-column justify-content-between" style="width: 50%; padding: 20px; background-color: #f9f9f9; border-radius: 0 10px 10px 0;">
                <div>
                    <h5 class="card-title" style="font-size: 1.2rem; font-weight: bold; color: #333;"><?= $code ?> - <?= $name ?></h5>
                    <p style="font-size: 0.9rem; color: #555;">
                        <strong>ขนาด:</strong> <?= htmlspecialchars($row['size']) ?><br>
                        <strong>ตำแหน่ง:</strong> <?= htmlspecialchars($row['location']) ?>
                    </p>
                    <p class="card-text small" style="font-size: 0.8rem; color: #777;"><?= htmlspecialchars($row['description'] ?? 'ไม่มีคำอธิบาย') ?></p>
                </div>
                <div>
                    <button class="btn" style="background-color: #ffc107; color: black; padding: 12px 15px; font-weight: bold; width: 100%;" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                        ขอใช้งานป้ายนี้
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="../submit_booking.php" method="POST" enctype="multipart/form-data" id="bookingForm_<?= $modalId ?>">
                    <input type="hidden" name="sign_board_id" value="<?= $row['id'] ?>">

                    <div class="modal-header" style="background-color: #ffc107; color: white;">
                        <h5 class="modal-title" id="<?= $modalId ?>Label">จองป้าย: <?= $code ?> - <?= $name ?> (<?= $typeLabel ?>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body bg-light">
                        <div class="mb-3"><label class="form-label">ชื่อกิจกรรมประชาสัมพันธ์</label><input type="text" name="title" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">ชื่อผู้จอง</label><input type="text" name="requester_name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">เบอร์โทร</label><input type="text" name="requester_phone" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">อีเมล</label><input type="email" name="requester_email" class="form-control" required></div>

                        <div class="mb-3">
                            <label class="form-label">สถานภาพ</label>
                            <select name="user_status" class="form-select user-status-select" id="user_status_<?= $modalId ?>" required>
                                <option value="">-- เลือก --</option>
                                <option value="นิสิต">นิสิต</option>
                                <option value="บุคลากร">บุคลากร</option>
                            </select>
                        </div>

                        <div class="mb-3" id="faculty_field_<?= $modalId ?>" style="display:none;">
                            <label class="form-label">คณะของนิสิต</label>
                            <select name="faculty" class="form-select">
                                <option value="">-- เลือกคณะ --</option>
                                <?php foreach ($faculties as $f): ?>
                                <option value="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="year_field_<?= $modalId ?>" style="display:none;">
                            <label class="form-label">ชั้นปี</label>
                            <select name="year" class="form-select">
                                <option value="">-- เลือกชั้นปี --</option>
                                <option value="ปี 1">ปี 1</option>
                                <option value="ปี 2">ปี 2</option>
                                <option value="ปี 3">ปี 3</option>
                                <option value="ปี 4">ปี 4</option>
                                <option value="สูงกว่าปริญญาตรี">สูงกว่าปริญญาตรี</option>
                            </select>
                        </div>

                        <div class="mb-3" id="workplace_field_<?= $modalId ?>" style="display:none;">
                            <label class="form-label">หน่วยงาน/คณะ</label>
                            <select name="workplace" class="form-select">
                                <option value="">-- เลือกหน่วยงาน/คณะ --</option>
                                <?php foreach ($workplaces as $w): ?>
                                <option value="<?= htmlspecialchars($w['name']) ?>"><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="position_field_<?= $modalId ?>" style="display:none;">
                            <label class="form-label">ตำแหน่ง</label>
                            <input type="text" name="position" class="form-control" placeholder="ระบุตำแหน่ง">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">วันที่เริ่ม</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3"><label class="form-label">ลิงก์ Google Drive (ถ้ามี)</label><input type="url" name="drive_link" class="form-control"></div>
                        <div class="mb-3">
                            <label class="form-label">อัปโหลดไฟล์ (รูปภาพหรือวิดีโอ)</label>
                            <input type="file" name="sample_file" class="form-control file-input" accept=".jpg,.jpeg,.png,.mp4">
                             <div class="form-text">
                                อนุญาตเฉพาะไฟล์ .jpg, .jpeg, .png หรือ .mp4<br>
                                <strong>ข้อกำหนด:</strong><br>
                                - รูปภาพควรมีความละเอียดไม่เกิน 72 dpi<br>
                                - วิดีโอควรมีความยาวไม่เกิน 10 วินาที<br>
                                - ขนาดไฟล์ไม่เกิน 10MB
                            </div>
                            <div class="error-message" style="color: red; display: none; margin-top: 5px;">ขนาดไฟล์เกิน 10MB</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <input type="hidden" name="sign_type" value="LED">
                        <button type="submit" class="btn btn-warning">ส่งคำขอจอง</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="pdfModalLabel">แนวปฏิบัติการใช้งานป้าย LED</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe src="../documents/guidelines_led.pdf" width="100%" height="600px" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ฟังก์ชันจัดการการแสดง/ซ่อนฟิลด์
    function toggleBookingFields(modalId) {
        const userStatusSelect = document.getElementById('user_status_' + modalId);
        if (!userStatusSelect) return;

        const userStatus = userStatusSelect.value;
        const studentFacultyField = document.getElementById('faculty_field_' + modalId);
        const studentYearField = document.getElementById('year_field_' + modalId);
        const staffWorkplaceField = document.getElementById('workplace_field_' + modalId);
        const staffPositionField = document.getElementById('position_field_' + modalId);

        // ซ่อนทุกฟิลด์ที่ไม่เกี่ยวข้องก่อน
        studentFacultyField.style.display = 'none';
        studentYearField.style.display = 'none';
        staffWorkplaceField.style.display = 'none';
        staffPositionField.style.display = 'none';
        
        // เปิดเฉพาะฟิลด์ที่เกี่ยวข้อง
        if (userStatus === 'นิสิต') {
            studentFacultyField.style.display = 'block';
            studentYearField.style.display = 'block';
        } else if (userStatus === 'บุคลากร') {
            staffWorkplaceField.style.display = 'block';
            staffPositionField.style.display = 'block';
        }
    }

    // Event listener สำหรับ dropdown สถานภาพทั้งหมดในหน้า
    document.querySelectorAll('.user-status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const modalId = this.id.replace('user_status_', '');
            toggleBookingFields(modalId);
        });
    });

    // Event listener สำหรับการปิด Modal เพื่อ reset ค่า
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                const modalId = this.id.replace('bookingModal', '');
                if (modalId) {
                    toggleBookingFields(modalId);
                }
            }
        });
    });

    // Event listener สำหรับตรวจสอบขนาดไฟล์
    document.querySelectorAll('.file-input').forEach(function(fileInput) {
        const form = fileInput.closest('form');
        const errorMessage = form.querySelector('.error-message');
        
        form.addEventListener('submit', function(event) {
            if (fileInput.files.length > 0) {
                if (fileInput.files[0].size > 10 * 1024 * 1024) { // 10MB
                    event.preventDefault();
                    errorMessage.style.display = 'block';
                } else {
                    errorMessage.style.display = 'none';
                }
            }
        });
    });

    // แสดง Modal PDF แค่ครั้งแรก
    const pdfModalKey = 'pdfModalShown_led';
    if (!sessionStorage.getItem(pdfModalKey)) {
        const modalElement = document.getElementById('pdfModal');
        if (modalElement) {
            const bsModal = new bootstrap.Modal(modalElement);
            bsModal.show();
            sessionStorage.setItem(pdfModalKey, 'true');
        }
    }
});
</script>