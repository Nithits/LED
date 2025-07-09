<?php
session_name('user_session');
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login_user.php");
  exit;
}

$page = $_GET['page'] ?? 0;

// ข้อกำหนดการใช้งาน
$requirement_dir = __DIR__ . '/../images/';
$requirement_url = '../images/';
$requirement_images = $led_images = $vinyl_images = [];

if (is_dir($requirement_dir)) {
    $requirement_images = array_diff(scandir($requirement_dir), array('.', '..'));

    // กรองเฉพาะไฟล์ rules_led*.jpg/.png
    $led_images = array_filter($requirement_images, function($img) {
        return str_starts_with($img, 'rules_led') && preg_match('/\.(jpg|jpeg|png)$/i', $img);
    });

    // กรองเฉพาะไฟล์ rules_vinyl*.jpg/.png
    $vinyl_images = array_filter($requirement_images, function($img) {
        return str_starts_with($img, 'rules_vinyl') && preg_match('/\.(jpg|jpeg|png)$/i', $img);
    });
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
</head>
<body>

  <!-- ✅ Navbar -->
  <?php include("../components/top-navbar.php"); ?>

  <div class="container">
  <!-- แนวปฏิบัติการจองป้าย -->
  <?php if (!empty($led_images) || !empty($vinyl_images)): ?>
  <h4 id="guidelines" class="fw-bold mb-4 text-center mt-5 fs-3">แนวปฏิบัติการจองป้าย</h4>

  <div class="card shadow mb-5">
    <div class="card-body">
      <?php if (!empty($led_images)): ?>
        <?php foreach ($led_images as $img): ?>
          <div class="mb-4 text-center">
            <img src="<?= $requirement_url . htmlspecialchars($img) ?>" class="img-fluid rounded border">
          </div>
        <?php endforeach; ?>
        <hr>
      <?php endif; ?>

      <?php if (!empty($vinyl_images)): ?>
        <?php foreach ($vinyl_images as $img): ?>
          <div class="mb-4 text-center">
            <img src="<?= $requirement_url . htmlspecialchars($img) ?>" class="img-fluid rounded border">
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
</div>

<?php require_once("../components/footer.php"); ?>

<!-- ✅ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
</body>
</html>
