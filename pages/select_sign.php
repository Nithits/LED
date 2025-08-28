<?php
session_name('user_session');
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login_user.php");
  exit;
}

// 🔔 แจ้งเตือน
$alert = '';
if (isset($_GET['success'])) {
    $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>จองป้ายสำเร็จเรียบร้อยแล้ว
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
} elseif (isset($_GET['conflict'])) {
    $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle-fill me-2"></i>ไม่สามารถจองได้: มีการจองซ้ำในช่วงเวลาดังกล่าว
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
} elseif (isset($_GET['past'])) {
    $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle-fill me-2"></i>ไม่สามารถจองย้อนหลังได้
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
} elseif (isset($_GET['too_long'])) {
    $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle-fill me-2"></i>ไม่สามารถจองป้ายเกิน 15 วันได้
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ระบบจองป้ายประชาสัมพันธ์</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/sysstyle.css">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="../images/iconmsu.ico">
<style>
  /* เพิ่มสไตล์ให้ดูน่าสนใจ */
  .section-title {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 40px;
    color: #333;
    text-transform: uppercase;
    letter-spacing: 2px;
  }

  /* การ์ด */
  .card-block {
    cursor: pointer;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 300px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
  }

  .card-block:hover {
    opacity: 0.9;
    transform: translateY(-5px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
  }

  .led-card {
    background-color: #333333;
  }

  .vinyl-card {
    background-color: #fbbc05;
  }

  .card-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); /* เพิ่มเงาให้ตัวอักษร */
  }

  .bi {
    font-size: 3rem;
    margin-bottom: 10px;
  }

  .d-flex {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
  }

  /* เพิ่มระยะห่างระหว่างการ์ด */
  .mt-5 {
    margin-top: 60px !important;
  }

  /* การ์ดตารางการจอง */
  .card {
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    background-color: #fff;
  }
</style>

</head>
<body>

  <!-- ดึง Navbar จากไฟล์ top-navbar.php -->
  <?php include('../components/top-navbar.php'); ?>

<div class="container my-4">
  <!-- แสดงข้อความแจ้งเตือนใต้ navbar -->
  <?= $alert ?>
  <!-- Section Title -->
  <div class="section-title">
    เลือกประเภทป้ายที่ต้องการจอง
  </div>

  <div class="row g-4 mb-5">
    <!-- ป้าย LED -->
    <div class="col-md-6">
      <a href="/led_list.php" class="card-block led-card">
        <div class="d-flex align-items-center justify-content-center">
          <i class="bi bi-lightning-fill fs-1 me-3 text-white"></i> <!-- Icon -->
          <h5 class="card-title">ป้าย LED</h5>
        </div>
      </a>
    </div>

    <!-- ป้ายไวนิล -->
    <div class="col-md-6">
      <a href="/vinyl_list.php" class="card-block vinyl-card">
        <div class="d-flex align-items-center justify-content-center">
          <i class="bi bi-file-earmark-text-fill fs-1 me-3 text-white"></i> <!-- Icon -->
          <h5 class="card-title">ป้ายไวนิล</h5>
        </div>
      </a>
    </div>
  </div>
</div>

<!-- ตารางการจอง -->
<div class="container">
  <div class="section-title">
    ตารางการจองป้ายประชาสัมพันธ์
  </div>

  <div class="mt-5">
    <div class="card p-4 shadow-sm" style="border-radius: 12px; background-color: #ffffff;">
      <?php include('../components/booking_schedule_table.php'); ?>
    </div>
  </div>
</div>

  <?php require_once("../components/footer.php"); ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
