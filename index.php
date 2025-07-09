<?php
session_name('user_session');
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login_user.php");
  exit;
}

$page = $_GET['page'] ?? 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ระบบจองป้ายประชาสัมพันธ์</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">

  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="css/sysstyle.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
</head>
<body>

  <!-- ✅ Navbar -->
  <?php include("components/top-navbar.php"); ?>

  <!-- ✅ Page Content -->
  <div class="container mt-4">
    <?php
    switch ($page) {
      case 1:
        include("pages/select_sign.php");
        break;
      case 2:
        include("pages/page2.php");
        break;
      case 3:
        include("pages/guidelines.php");
        break;
      default:
        include("pages/home.php");
    }
    ?>
  </div>

  <?php require_once("components/footer.php"); ?>

  <!-- ✅ Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
</body>
</html>
