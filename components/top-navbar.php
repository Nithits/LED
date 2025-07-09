<?php
// เริ่มต้น session หากยังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) session_start();

// ตรวจสอบการเข้าสู่ระบบทั้ง user และ admin
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
$is_admin = isset($_SESSION['admin_id']);
?>

<nav class="navbar navbar-expand-lg custom-navbar">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/LED/index.php">ระบบจองป้ายประชาสัมพันธ์</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto d-flex align-items-center">
        <li class="nav-item">
          <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/LED/index.php') ? 'active' : ''; ?>" href="/LED/index.php">หน้าแรก</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/LED/pages/select_sign.php') ? 'active' : ''; ?>" href="/LED/pages/select_sign.php">จองป้าย</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/LED/pages/page2.php') ? 'active' : ''; ?>" href="/LED/pages/page2.php">รายการจอง</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/LED/pages/guidelines.php') ? 'active' : ''; ?>" href="/LED/pages/guidelines.php">แนวปฏิบัติ</a>
        </li>

        <?php if ($is_logged_in): ?>
          <!-- เมนูสำหรับ user -->
          <?php if ($is_admin): ?>
            <!-- เมนูสำหรับ admin -->
            <li class="nav-item">
              <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/LED/pages/admin_dashboard.php') ? 'active' : ''; ?>" href="/LED/pages/admin_dashboard.php">แดชบอร์ดผู้ดูแลระบบ</a>
            </li>
          <?php endif; ?>

          <li class="nav-item">
            <a class="nav-link" href="/LED/logout.php" title="ออกจากระบบ">
              ออกจากระบบ
            </a>
          </li>
        <?php else: ?>
          <!-- เมนูสำหรับผู้ที่ยังไม่ได้เข้าสู่ระบบ -->
          <li class="nav-item">
            <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/LED/login_user.php') ? 'active' : ''; ?>" href="/LED/login_user.php" title="เข้าสู่ระบบ">
              เข้าสู่ระบบ
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- ปรับแต่ง CSS -->
<style>
  .navbar-nav .nav-link.active {
    border-bottom: 2px solid #ffcc00; /* เส้นขีดใต้เมนูที่ถูกเลือกเป็นสีเหลือง */
    color: #ffcc00 !important; /* เปลี่ยนสีของข้อความเมนูที่เลือกเป็นสีเหลือง */
  }

  .nav-link {
    transition: all 0.3s ease-in-out; /* เพิ่มการเปลี่ยนแปลงที่ลื่นไหล */
  }

  .nav-link:hover {
    color: #ffcc00 !important; /* สีของข้อความเมนูเมื่อ hover */
    border-bottom: 2px solid #ffcc00;
  }

</style>
