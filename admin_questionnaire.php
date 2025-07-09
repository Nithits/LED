<?php
session_name('admin_session');
session_start();
if (!isset($_SESSION['admin_name'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php'; 

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_link = filter_input(INPUT_POST, 'form_link', FILTER_SANITIZE_URL);
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

    // ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("UPDATE questionnaire_settings SET form_link = ?, is_active = ? WHERE id = 1");
    // กำหนดค่าให้กับพารามิเตอร์: "s" คือ string, "i" คือ integer
    $stmt->bind_param("si", $form_link, $is_active);

    if ($stmt->execute()) {
        $message = "บันทึกการตั้งค่าสำเร็จ!";
        $message_type = "success";
    } else {
        $message = "เกิดข้อผิดพลาดในการบันทึก: " . $stmt->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// ดึงข้อมูลการตั้งค่าปัจจุบันมาแสดงในฟอร์ม
$current_link = '';
$current_status = 0;
$result = $conn->query("SELECT form_link, is_active FROM questionnaire_settings WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
    $current_link = $settings['form_link'];
    $current_status = $settings['is_active'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแบบสอบถาม</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">

    <style>
        :root {
            --primary-color: #4A55A2; /* สีม่วงเข้ม ดูเป็นมืออาชีพ */
            --secondary-color: #7895CB; /* สีฟ้ารองที่เข้ากัน */
            --light-bg: #F0F2F5; /* สีพื้นหลังอ่อนๆ */
            --font-family: 'Prompt', sans-serif; /* ฟอนต์สารบรรณ */
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        body { 
            font-family: var(--font-family);
            background-color: var(--light-bg);
        }

        .main-content { 
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 700;
        }

        .card {
            border: none;
            border-radius: 12px; /* ทำให้มุมโค้งมนมากขึ้น */
            box-shadow: var(--card-shadow);
            transition: box-shadow .3s ease-in-out;
        }

        .card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .form-label strong {
            font-weight: 500;
            color: #333;
        }

        .form-control-lg {
            border-radius: 8px; /* มุมโค้งมนที่สอดคล้องกัน */
            padding: 0.75rem 1.25rem;
        }
        
        .form-control-lg:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(120, 149, 203, 0.25);
        }

        /* ดีไซน์สำหรับสวิตช์เปิด/ปิด */
        .form-switch .form-check-input {
            width: 3.5em;
            height: 1.75em;
            cursor: pointer;
            background-color: #ccc;
            border-color: #ccc;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #3b4482; /* สีเข้มขึ้นเล็กน้อยเมื่อเมาส์ชี้ */
            border-color: #3b4482;
        }
        
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 page-title d-flex align-items-center">
                    <i class="bi bi-ui-checks-grid me-3"></i>
                    จัดการแบบสอบถามความพึงพอใจ
                </h1>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> d-flex align-items-center" role="alert">
                <i class="bi <?php echo ($message_type === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                <div>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="form_link" class="form-label"><strong>ลิงก์ Google Form</strong></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-link-45deg"></i></span>
                                <input type="url" class="form-control form-control-lg border-start-0" id="form_link" name="form_link" 
                                       placeholder="https://docs.google.com/forms/..." 
                                       value="<?= htmlspecialchars($current_link) ?>">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label d-block"><strong>สถานะการใช้งาน</strong></label>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= ($current_status == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label pt-1" for="is_active">เปิดใช้งาน (แสดงแบบสอบถามหลังผู้ใช้จองสำเร็จ)</label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>