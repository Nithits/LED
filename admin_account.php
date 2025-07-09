<?php
session_name('admin_session');
session_start();

// ตรวจสอบการล็อกอินของแอดมิน
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

require_once 'db.php';

$admins = [];
try {
    $sql = "SELECT id, name, username, avatar_path FROM admins ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result) {
        $admins = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
} catch (mysqli_sql_exception $e) {
    $db_error = "ไม่สามารถดึงข้อมูลได้: " . $e->getMessage();
}

// จัดการการแสดงข้อความแจ้งเตือน (Alerts)
$alert_message = '';
$alert_type = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'add_success':
            $alert_message = 'เพิ่มผู้ดูแลระบบสำเร็จ!';
            $alert_type = 'success';
            break;
        case 'edit_success':
            $alert_message = 'แก้ไขข้อมูลผู้ดูแลสำเร็จ!';
            $alert_type = 'success';
            break;
        case 'delete_success':
            $alert_message = 'ลบผู้ดูแลระบบสำเร็จ!';
            $alert_type = 'success';
            break;
    }
} elseif (isset($_GET['error'])) {
    $alert_type = 'danger';
    switch ($_GET['error']) {
        case 'delete_failed':
            $alert_message = 'ไม่สามารถลบผู้ดูแลได้';
            break;
        case 'edit_failed':
            $alert_message = 'แก้ไขข้อมูลไม่สำเร็จ';
            break;
        case 'email_exists':
            $alert_message = 'อีเมลนี้มีผู้ใช้งานในระบบแล้ว';
            break;
        case 'edit_password_mismatch':
            $alert_message = 'การยืนยันรหัสผ่านใหม่ไม่ตรงกัน!';
            break;
        case 'current_password_incorrect':
            $alert_message = 'รหัสผ่านปัจจุบันไม่ถูกต้อง!';
            break;
        default:
            $alert_message = 'เกิดข้อผิดพลาดที่ไม่รู้จัก';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการแอดมิน</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .content-area { padding: 30px; }
        .add-admin-btn { background-color: #5a4e8c; border-color: #5a4e8c; }
        .add-admin-btn:hover { background-color: #483d76; border-color: #483d76; }
        .admin-card { background-color: #ffffff; border: 1px solid #e9ecef; }
        .admin-avatar img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .admin-info .name { font-weight: 500; color: #212529; line-height: 1.2; }
        .admin-info .role { font-size: 0.85rem; color: #6c757d; }
        .dropdown-item { cursor: pointer; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include("admin_sidebar.php"); ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="m-0">จัดการแอดมิน</h2>
                <button type="button" class="btn btn-primary add-admin-btn d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="bi bi-plus-lg me-2"></i>เพิ่มผู้ดูแล
                </button>
            </div>

            <?php if (!empty($alert_message)): ?>
            <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="list-group">
                <?php if (!empty($admins)): ?>
                    <?php foreach ($admins as $admin): ?>
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center admin-card">
                        <div class="d-flex align-items-center">
                            <div class="admin-avatar me-3">
                                <img src="<?php echo htmlspecialchars($admin['avatar_path']); ?>" alt="Avatar" onerror="this.onerror=null;this.src='images/default_avatar.png';">
                            </div>
                            <div class="admin-info">
                                <div class="name"><?php echo htmlspecialchars($admin['name']); ?></div>
                                <div class="role">ระดับผู้ดูแล: แอดมิน</div>
                            </div>
                        </div>
                        <div class="admin-actions dropdown">
                            <button class="btn btn-light btn-sm border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item edit-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editAdminModal"
                                        data-id="<?php echo $admin['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($admin['name']); ?>"
                                        data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                        data-avatar_path="<?php echo htmlspecialchars($admin['avatar_path']); ?>">
                                        <i class="bi bi-pencil-square me-2"></i>แก้ไข
                                    </button>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" 
                                       href="delete_admin.php?id=<?php echo $admin['id']; ?>" 
                                       onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ดูแลคนนี้?');">
                                       <i class="bi bi-trash3 me-2"></i>ลบ
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item text-center p-3">
                        <?php echo isset($db_error) ? htmlspecialchars($db_error) : 'ยังไม่มีผู้ดูแลในระบบ'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">ฟอร์มเพิ่มผู้ดูแลระบบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAdminForm" action="add_admin_process.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                    <div class="mb-3">
                        <label for="adminName" class="form-label">ชื่อ-สกุล</label>
                        <input type="text" class="form-control" id="adminName" name="admin_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminEmail" class="form-label">อีเมล (ใช้สำหรับ Login)</label>
                        <input type="email" class="form-control" id="adminEmail" name="admin_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminPassword" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="adminPassword" name="admin_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminConfirmPassword" class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" class="form-control" id="adminConfirmPassword" name="admin_confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminAvatar" class="form-label">รูปโปรไฟล์ (Avatar)</label>
                        <input class="form-control" type="file" id="adminAvatar" name="admin_avatar">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">แก้ไขข้อมูลผู้ดูแล</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAdminForm" action="edit_admin_process.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="edit_admin_id">
                    <div class="mb-3">
                        <label for="edit_admin_name" class="form-label">ชื่อ-สกุล</label>
                        <input type="text" class="form-control" id="edit_admin_name" name="admin_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_admin_username" class="form-label">อีเมล (ใช้สำหรับ Login)</label>
                        <input type="email" class="form-control" id="edit_admin_username" name="admin_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูปโปรไฟล์ปัจจุบัน</label>
                        <div class="admin-avatar">
                            <img src="" id="edit_current_avatar" alt="Current Avatar">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_admin_avatar" class="form-label">เปลี่ยนรูปโปรไฟล์ (ถ้าต้องการ)</label>
                        <input class="form-control" type="file" id="edit_admin_avatar" name="new_avatar">
                    </div>
                    <hr>
                    <p class="text-muted small">กรอกข้อมูลด้านล่างเฉพาะกรณีที่ต้องการเปลี่ยนรหัสผ่าน</p>
                    <div class="mb-3">
                        <label for="edit_admin_current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" class="form-control" id="edit_admin_current_password" name="current_password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_admin_password" class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="edit_admin_password" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_admin_confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="edit_admin_confirm_password" name="confirm_password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Script for Add Admin Form Validation
    const addAdminForm = document.getElementById('addAdminForm');
    if (addAdminForm) {
        addAdminForm.addEventListener('submit', function(event) {
            const password = document.getElementById('adminPassword').value;
            const confirmPassword = document.getElementById('adminConfirmPassword').value;
            if (password !== confirmPassword) {
                alert('รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน โปรดตรวจสอบอีกครั้ง');
                event.preventDefault(); 
            }
        });
    }

    // Script for populating Edit Admin Modal
    const editAdminModal = document.getElementById('editAdminModal');
    if (editAdminModal) {
        editAdminModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const username = button.getAttribute('data-username');
            const avatarPath = button.getAttribute('data-avatar_path');
            const modalIdInput = editAdminModal.querySelector('#edit_admin_id');
            const modalNameInput = editAdminModal.querySelector('#edit_admin_name');
            const modalUsernameInput = editAdminModal.querySelector('#edit_admin_username');
            const modalCurrentAvatar = editAdminModal.querySelector('#edit_current_avatar');
            const modalAvatarInput = editAdminModal.querySelector('#edit_admin_avatar');
            const modalCurrentPasswordInput = editAdminModal.querySelector('#edit_admin_current_password');
            const modalPasswordInput = editAdminModal.querySelector('#edit_admin_password');
            const modalConfirmPasswordInput = editAdminModal.querySelector('#edit_admin_confirm_password');
            modalIdInput.value = id;
            modalNameInput.value = name;
            modalUsernameInput.value = username;
            modalCurrentAvatar.src = avatarPath;
            modalAvatarInput.value = '';
            modalCurrentPasswordInput.value = '';
            modalPasswordInput.value = '';
            modalConfirmPasswordInput.value = '';
        });
    }
    
    // Script for validating Edit Admin Form
    const editAdminForm = document.getElementById('editAdminForm');
    if (editAdminForm) {
        editAdminForm.addEventListener('submit', function(event) {
            const currentPassword = document.getElementById('edit_admin_current_password').value;
            const newPassword = document.getElementById('edit_admin_password').value;
            const confirmPassword = document.getElementById('edit_admin_confirm_password').value;

            if (newPassword !== '' || confirmPassword !== '') {
                if (currentPassword === '') {
                    alert('กรุณากรอกรหัสผ่านปัจจุบันเพื่อยืนยันการเปลี่ยนแปลง');
                    event.preventDefault();
                    return; 
                }
            }
            
            if (newPassword !== confirmPassword) {
                alert('รหัสผ่านใหม่และการยืนยันไม่ตรงกัน!');
                event.preventDefault();
            }
        });
    }
});
</script>

</body>
</html>