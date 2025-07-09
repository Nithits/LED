<?php
session_name('user_session');
session_start();
include("db.php");

if (isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $conn->real_escape_string($_POST['email']);

  $query = $conn->query("SELECT * FROM users WHERE email = '$email' AND role = 'user'");
  if ($query && $query->num_rows === 1) {
    $user = $query->fetch_assoc();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_logged_in'] = true;
    $_SESSION['show_modal'] = true;
    echo json_encode(['status' => 'success']);
    exit;
  } else {
    $name = explode('@', $email)[0];
    $insert = $conn->prepare("INSERT INTO users (name, email, role) VALUES (?, ?, 'user')");
    $insert->bind_param("ss", $name, $email);
    $insert->execute();

    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_logged_in'] = true;
    $_SESSION['show_modal'] = true;
    echo json_encode(['status' => 'success']);
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เข้าสู่ระบบผู้ใช้งาน</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-auth-compat.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
  <style>
    body {
      font-family: 'Prompt', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f2f2f2; /* สีเทาอ่อน */
    }
    .login-box {
      background: white;
      padding: 3rem 2.5rem;
      border-radius: 1.5rem;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
      max-width: 480px;
      width: 100%;
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .login-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 16px 36px rgba(0, 0, 0, 0.15);
    }
    .logo-section {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      justify-content: center;
    }
    .logo-section img {
      width: 60px;
    }
    .logo-text {
      text-align: left;
      color: #444;
      font-size: 1rem;
    }
    .logo-text .title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #222;
    }
    .btn-google {
      background-color: #fbbc05;
      color: #222;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 0.6rem 1.2rem;
      font-size: 1rem;
      border: none;
      border-radius: 8px;
    }
    .btn-google:hover {
      background-color: #e6a900;
    }
    .btn-google img {
      width: 20px;
      height: 20px;
    }
    #status {
      font-size: 0.95rem;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo-section">
      <img src="images/logomsu.png" alt="Logo">
      <div class="logo-text">
        <div class="title">ระบบจองป้ายประชาสัมพันธ์</div>
        <div>โดยกองประชาสัมพันธ์และกิจการต่างประเทศ</div>
        <div>มหาวิทยาลัยมหาสารคาม</div>
      </div>
    </div>

    <h4 class="mb-4 fw-semibold">เข้าสู่ระบบผู้ใช้งาน</h4>

    <button onclick="loginWithGoogle()" class="btn btn-google w-100 mb-3">
      <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google">
      <span>เข้าสู่ระบบด้วย Google</span>
    </button>

    <div id="status" class="text-danger fw-semibold"></div>
    <p class="text-muted small mt-3">ระบบอนุญาตเฉพาะบัญชี <strong>@msu.ac.th</strong> เท่านั้น</p>
  </div>

  <script>
    const firebaseConfig = {
      apiKey: "AIzaSyAgb9-5V5v9vJ8I_MAiEEh5hMDHUatc4_Y",
      authDomain: "msu-login-project.firebaseapp.com",
      projectId: "msu-login-project",
      storageBucket: "msu-login-project.appspot.com",
      messagingSenderId: "294021482589",
      appId: "1:294021482589:web:3c5e79dc1608a7f00225b4"
    };
    firebase.initializeApp(firebaseConfig);
    const provider = new firebase.auth.GoogleAuthProvider();
    provider.setCustomParameters({
      prompt: 'select_account'
    });

    function loginWithGoogle() {
      firebase.auth().signInWithPopup(provider)
        .then((result) => {
          const email = result.user.email;
          if (!email.endsWith("@msu.ac.th")) {
            document.getElementById("status").textContent = "กรุณาใช้บัญชี @msu.ac.th เท่านั้น";
            firebase.auth().signOut();
            return;
          }

          fetch("login_user.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "email=" + encodeURIComponent(email)
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'success') {
              window.location.href = "index.php";
            } else {
              document.getElementById("status").textContent = data.message || "ไม่สามารถเข้าสู่ระบบได้";
              firebase.auth().signOut();
            }
          });
        })
        .catch(err => {
          console.error(err);
          document.getElementById("status").textContent = "เกิดข้อผิดพลาดในการเข้าสู่ระบบ";
        });
    }
  </script>
</body>
</html>
