<?php
session_name('admin_session');
session_start();
include("db.php");

// ถ้า login แล้ว ให้ไปหน้า admin.php
if (isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    $query = $conn->query("SELECT * FROM admins WHERE username = '$username'");
    if ($query && $query->num_rows === 1) {
        $admin = $query->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name']; // ✅ เพิ่มบรรทัดนี้
            $_SESSION['admin_logged_in'] = true;

            header("Location: admin.php");
            exit;
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบชื่อผู้ใช้";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบผู้ดูแล</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
  <style>
    body {
      background: linear-gradient(135deg, #dbeafe 0%, #f1f5f9 100%);
      min-height: 100vh;
      font-family: 'Prompt', sans-serif;
    }
    .login-card {
      max-width: 370px;
      margin: auto;
      margin-top: 7vh;
      background: #fff;
      border-radius: 1.3rem;
      box-shadow: 0 4px 24px #fbbf2450;
      padding: 2.3rem 2rem 2rem 2rem;
    }
    .login-card .logo {
      width: 320px; /* ปรับขนาดความกว้างของโลโก้ */
      height: auto; /* เพื่อรักษาสัดส่วนของภาพ */
      margin-bottom: 0.7rem;
    }
    .login-card .form-label {
      font-weight: 500;
    }
    .login-card .btn-warning {
      background: #fbbf24;
      border: none;
      font-weight: 500;
      font-size: 1.1rem;
      transition: background 0.2s;
    }
    .login-card .btn-warning:hover {
      background: #f59e00;
    }
    .login-card h4 {
      font-weight: 600;
      margin-bottom: 1rem;
      color: #92400e;
      letter-spacing: 1px;
    }
    .fade-in {
      animation: fadeIn 0.8s;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(40px);}
      to { opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>
  <div class="login-card fade-in">
    <div class="text-center mb-2">
      <img src="images/logo.jpg" alt="Logo" class="logo">
      <h4>เข้าสู่ระบบผู้ดูแล</h4>
    </div>
    <?php if (!empty($error)) : ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
      <div class="mb-3">
        <label class="form-label" for="username">ชื่อผู้ใช้</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">รหัสผ่าน</label>
        <input type="password" id="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-warning w-100 mt-1" type="submit">เข้าสู่ระบบ</button>
    </form>
    <div class="text-center mt-3">
      <a href="index.php" class="small text-secondary text-decoration-none">กลับหน้าเว็บไซต์</a>
    </div>
  </div>
</body>
</html>
