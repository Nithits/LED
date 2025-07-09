<?php
// ✅ กำหนดชื่อ session ให้เฉพาะฝั่ง admin
session_name('admin_session');
session_start();

// ✅ ป้องกันการเข้าถึงโดยไม่ได้ login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

// ✅ เรียกใช้ไฟล์ db.php เพื่อทำงานกับฐานข้อมูล
require_once 'db.php';

// --- 1. ดึงข้อมูลสำหรับสร้างฟอร์ม ---
$signboards_result = $conn->query("SELECT id, code, name FROM sign_boards ORDER BY code ASC");

// --- 2. PHP สำหรับเตรียมข้อมูลให้กราฟ ---
$chart_labels = [];
$chart_data = [];

// ดึงข้อมูลสถิติการจองย้อนหลัง 12 เดือน
$chart_sql = "
    SELECT
        YEAR(start_date) AS booking_year,
        MONTH(start_date) AS booking_month,
        COUNT(id) AS booking_count
    FROM
        bookings
    WHERE
        start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY
        booking_year, booking_month
    ORDER BY
        booking_year, booking_month
";

$chart_result = $conn->query($chart_sql);

if ($chart_result) {
    $thai_months = [1=>'ม.ค.', 2=>'ก.พ.', 3=>'มี.ค.', 4=>'เม.ย.', 5=>'พ.ค.', 6=>'มิ.ย.', 7=>'ก.ค.', 8=>'ส.ค.', 9=>'ก.ย.', 10=>'ต.ค.', 11=>'พ.ย.', 12=>'ธ.ค.'];
    while($row = $chart_result->fetch_assoc()) {
        $year_be = $row['booking_year'] + 543;
        $chart_labels[] = $thai_months[(int)$row['booking_month']] . ' ' . substr($year_be, -2);
        $chart_data[] = $row['booking_count'];
    }
}
// --- จบส่วนเตรียมข้อมูล ---
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดผู้ดูแลระบบ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --main-bg: #f8f9fa;
            --card-border-radius: 0.5rem;
        }
        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--main-bg);
        }
        .content-area {
            padding: 2rem;
        }
        .dashboard-card {
            background-color: #ffffff;
            border-radius: var(--card-border-radius);
            border: 1px solid #dee2e6;
            border-left-width: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-decoration: none;
            color: #212529;
            display: block;
            height: 100%;
        }
        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .dashboard-card .card-body { padding: 1.5rem; }
        .dashboard-card .card-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }
        .dashboard-card .card-title i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        .dashboard-card .card-desc {
            color: #6c757d;
            font-size: 0.9rem;
            padding-left: 2.1rem;
        }
        .card-primary { border-left-color: var(--primary-color); }
        .card-primary .card-title i { color: var(--primary-color); }
        .card-success { border-left-color: var(--success-color); }
        .card-success .card-title i { color: var(--success-color); }
        .card-info { border-left-color: var(--info-color); }
        .card-info .card-title i { color: var(--info-color); }
        .card-warning { border-left-color: var(--warning-color); }
        .card-warning .card-title i { color: var(--warning-color); }
        .form-card {
            background-color: #fff;
            border-radius: var(--card-border-radius);
            border: 1px solid #dee2e6;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .form-card-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        .form-card-title i {
            margin-right: 0.75rem;
            font-size: 1.75rem;
        }
        .btn-purple {
            background-color: #5a4e8c;
            color: #fff;
            font-weight: 600;
            padding: 14px;
            border-radius: var(--card-border-radius);
            transition: background-color 0.3s ease, transform 0.2s ease;
            border: none;
        }

        .btn-purple:hover {
            background-color: #4a3fa3;
            transform: translateY(-2px);
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include("admin_sidebar.php"); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 content-area">
            <h2 class="mb-4 fw-bold">แดชบอร์ดผู้ดูแลระบบ</h2>

            <div class="row g-4 mb-5">
                <div class="col-lg-3 col-md-6"><a href="admin_account.php" class="dashboard-card card-primary"><div class="card-body"><h5 class="card-title"><i class="bi bi-people-fill"></i>แอดมิน</h5><p class="card-desc">ดูและจัดการบัญชีแอดมิน</p></div></a></div>
                <div class="col-lg-3 col-md-6"><a href="admin_bookings.php" class="dashboard-card card-success"><div class="card-body"><h5 class="card-title"><i class="bi bi-calendar2-check-fill"></i>การจอง</h5><p class="card-desc">ตรวจสอบและอนุมัติการจอง</p></div></a></div>
                <div class="col-lg-3 col-md-6"><a href="admin_chat_reply.php" class="dashboard-card card-info"><div class="card-body"><h5 class="card-title"><i class="bi bi-chat-dots-fill"></i>ข้อความแชท</h5><p class="card-desc">ตอบกลับและติดตามข้อความ</p></div></a></div>
                <div class="col-lg-3 col-md-6"><a href="export_bookings.php" class="dashboard-card card-warning"><div class="card-body"><h5 class="card-title"><i class="bi bi-file-earmark-arrow-down-fill"></i>ส่งออกทั้งหมด</h5><p class="card-desc">ดาวน์โหลดข้อมูลการจองทั้งหมด</p></div></a></div>
            </div>


            <div class="form-card">
                <h4 class="form-card-title"><i class="bi bi-filter-circle-fill"></i>กรองและส่งออกข้อมูลการจอง</h4>
                <form action="export_bookings.php" method="get" class="row g-3">
                    <div class="col-md-6"><label for="start_date" class="form-label fw-bold">วันที่เริ่มต้น</label><input type="date" class="form-control" id="start_date" name="start_date"></div>
                    <div class="col-md-6"><label for="end_date" class="form-label fw-bold">วันที่สิ้นสุด</label><input type="date" class="form-control" id="end_date" name="end_date"></div>
                    <div class="col-md-4"><label for="sign_board_id" class="form-label fw-bold">เลือกป้ายประกาศ</label><select id="sign_board_id" name="sign_board_id" class="form-select"><option value="all" selected>-- ทุกป้าย --</option><?php while ($board = $signboards_result->fetch_assoc()) { echo '<option value="' . htmlspecialchars($board['id']) . '">' . htmlspecialchars($board['code'] . ' - ' . $board['name']) . '</option>'; } ?></select></div>
                    <div class="col-md-4"><label for="user_status_select" class="form-label fw-bold">ประเภทผู้ใช้งาน</label><select id="user_status_select" name="user_status" class="form-select"><option value="all" selected>-- ทั้งหมด --</option><option value="นิสิต">นิสิต</option><option value="บุคลากร">บุคลากร</option></select></div>
                    <div class="col-md-4"><label for="booking_status" class="form-label fw-bold">สถานะการจอง</label><select id="booking_status" name="booking_status" class="form-select"><option value="all" selected>-- ทุกสถานะ --</option><option value="pending">รออนุมัติ</option><option value="approved">อนุมัติแล้ว</option><option value="rejected">ไม่อนุมัติ</option><option value="cancelled">ยกเลิกแล้ว</option></select></div>
                    <div class="col-12 mt-4"><button type="submit" class="btn btn-purple w-100 py-2 fw-bold"><i class="bi bi-search me-2"></i>กรองและดาวน์โหลดไฟล์ CSV</button></div>
                </form>
            </div>

            <div class="card shadow-sm mb-5">
                <div class="card-header fw-bold"><i class="bi bi-bar-chart-line-fill"></i> สถิติการจองรายเดือน (12 เดือนล่าสุด)</div>
                <div class="card-body"><canvas id="monthlyBookingsChart" style="height: 300px;"></canvas></div>
            </div>
    
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('monthlyBookingsChart');
        if (ctx) {
            const chartLabels = <?= json_encode($chart_labels); ?>;
            const chartData = <?= json_encode($chart_data); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'จำนวนการจอง',
                        data: chartData,
                        backgroundColor: 'rgba(90, 78, 140, 0.6)',
                        borderColor: 'rgba(90, 78, 140, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 }}},
                    plugins: { legend: { display: false }}
                }
            });
        }
    });
</script>

<?php $conn->close(); ?>
</body>
</html>