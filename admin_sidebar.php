<?php
// ดึงชื่อไฟล์หน้าเว็บที่เปิดอยู่ เช่น admin_chat_reply.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
.sidebar {
    background-color: #5a4e8c;
    min-height: 100vh;
    color: white;
}
.sidebar a {
    color: white;
    text-decoration: none;
}
.sidebar a:hover,
.sidebar a.active {
    background-color: #7c6db3;
    border-radius: 5px;
}
.sidebar .nav-link {
    padding: 12px 20px;
    font-size: 15px;
}
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" type="image/x-icon" href="images/iconmsu.ico">

<nav class="col-md-3 col-lg-2 d-md-block sidebar py-4">
    <div class="text-center mb-4">
        <img src="images/logo.jpg" alt="Admin Logo" class="img-fluid rounded mb-2"
       style="max-width: 240px; height: auto; object-fit: contain;">
        <h5 class="text-white mt-2 mb-0">ระบบผู้ดูแล</h5>
        <p class="small mb-0">ยินดีต้อนรับ: <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong></p>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage == 'admin.php') ? 'active' : '' ?>" href="admin.php">
                <i class="bi bi-speedometer2 me-2"></i>หน้าหลัก
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage == 'admin_account.php') ? 'active' : '' ?>" href="admin_account.php">
                <i class="bi bi-people me-2"></i>จัดการแอดมิน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage == 'admin_bookings.php') ? 'active' : '' ?>" href="admin_bookings.php">
                <i class="bi bi-calendar-check me-2"></i>จัดการการจอง
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage == 'admin_chat_reply.php') ? 'active' : '' ?>" href="admin_chat_reply.php">
                <i class="bi bi-chat-left-text me-2"></i>ตอบแชท
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage == 'admin_edit_signboards.php') ? 'active' : '' ?>" href="admin_edit_signboards.php">
                <i class="bi bi-image me-2"></i>จัดการแก้ไขป้าย
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage == 'admin_questionnaire.php') ? 'active' : '' ?>" href="admin_questionnaire.php">
                <i class="bi bi-patch-question me-2"></i>ตั้งค่าแบบสอบถาม
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage == 'logout_admin.php') ? 'active' : '' ?>" href="logout_admin.php">
                <i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ
            </a>
        </li>
    </ul>
</nav>