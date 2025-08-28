<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$is_admin     = !empty($_SESSION['admin_id']);
$is_logged_in = $is_admin || !empty($_SESSION['user_id']);

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/* helper: สร้าง URL จากรูท และเช็ค active โดยตัด .php ออก */
function url($p){ return '/'.ltrim($p,'/'); }
function strip_php($p){ return preg_replace('/\.php$/','', rtrim($p,'/')); }
function active_exact($path){
  global $currentPath;
  return strip_php($currentPath) === url(strip_php($path)) ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg custom-navbar">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= url('') ?>">ระบบจองป้ายประชาสัมพันธ์</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto d-flex align-items-center">
        <li class="nav-item">
          <a class="nav-link <?= active_exact('') ?>" href="<?= url('') ?>">หน้าแรก</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= active_exact('pages/select_sign') ?>" href="<?= url('pages/select_sign') ?>">จองป้าย</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= active_exact('pages/page2') ?>" href="<?= url('pages/page2') ?>">รายการจอง</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= active_exact('pages/guidelines') ?>" href="<?= url('pages/guidelines') ?>">แนวปฏิบัติ</a>
        </li>

        <?php if ($is_logged_in): ?>
          <?php if ($is_admin): ?>
            <li class="nav-item">
              <a class="nav-link <?= active_exact('pages/admin_dashboard') ?>" href="<?= url('pages/admin_dashboard') ?>">แดชบอร์ดผู้ดูแลระบบ</a>
            </li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('logout') ?>">ออกจากระบบ</a></li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= active_exact('login_user') ?>" href="<?= url('login_user') ?>">เข้าสู่ระบบ</a>
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
