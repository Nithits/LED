<?php
session_name('admin_session');
session_start();
include("db.php");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}

function formatDateThai($date) {
    if (!$date) return '-';
    // ใช้ IntlDateFormatter ถ้ามี extension นี้ (ให้ผลลัพธ์ดีกว่า)
    if (class_exists('IntlDateFormatter')) {
        try {
            $formatter = new IntlDateFormatter('th_TH', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
            return $formatter->format(strtotime($date));
        } catch (Exception $e) { /* Fallback to manual formatting */ }
    }
    // วิธีสำรอง
    $months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
              "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $ts = strtotime($date);
    $day = date("j", $ts);
    $month = (int)date("n", $ts);
    $year = date("Y", $ts) + 543;
    return "$day {$months[$month]} $year";
}

// ฟังก์ชันสำหรับสร้าง Badge สถานะ
function getStatusBadge($status) {
    $badges = [
        'pending'     => ['class' => 'bg-warning text-dark', 'text' => 'รออนุมัติ'],
        'approved'    => ['class' => 'bg-success', 'text' => 'อนุมัติแล้ว'],
        'rejected'    => ['class' => 'bg-danger', 'text' => 'ปฏิเสธ'],
        'in_progress' => ['class' => 'bg-primary', 'text' => 'ดำเนินการแล้ว']
    ];
    return $badges[$status] ?? ['class' => 'bg-secondary', 'text' => 'ไม่ระบุ'];
}


// --- รับค่าจากฟิลเตอร์ ---
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_start = $_GET['start'] ?? '';
$date_end = $_GET['end'] ?? '';
$search_query = $_GET['search'] ?? '';

// --- Pagination ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// --- สร้าง SQL และ Parameters สำหรับ Prepared Statement ---
$base_sql = "FROM bookings b JOIN sign_boards s ON b.sign_board_id = s.id WHERE 1=1";
$params = [];
$types = '';

if ($type_filter) {
    $base_sql .= " AND s.type = ?";
    $params[] = $type_filter;
    $types .= 's';
}
if ($status_filter) {
    $base_sql .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($date_start && $date_end) {
    $base_sql .= " AND b.start_date BETWEEN ? AND ?";
    $params[] = $date_start;
    $params[] = $date_end;
    $types .= 'ss';
}
if ($search_query) {
    $base_sql .= " AND (b.id LIKE ? OR b.requester_name LIKE ? OR b.title LIKE ? OR s.code LIKE ?)";
    $search_param = "%" . $search_query . "%";
    for ($i = 0; $i < 4; $i++) $params[] = $search_param;
    $types .= 'ssss';
}

// --- นับจำนวนแถวทั้งหมดสำหรับ Pagination ---
$count_sql = "SELECT COUNT(b.id) AS total " . $base_sql;
$stmt_count = $conn->prepare($count_sql);
$total_rows = 0;
if ($stmt_count) {
    if (!empty($types)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_rows = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();
}
$total_pages = ceil($total_rows / $per_page);


// --- ดึงข้อมูลหลักสำหรับแสดงผล ---
$data_sql = "SELECT b.*, s.code AS sign_code " . $base_sql . " ORDER BY b.id DESC LIMIT ? OFFSET ?";
$stmt_data = $conn->prepare($data_sql);

$data_params = $params;
$data_params[] = $per_page;
$data_params[] = $offset;
$data_types = $types . 'ii';

if ($stmt_data) {
    if (!empty(trim($data_types))) {
        $stmt_data->bind_param($data_types, ...$data_params);
    }
    $stmt_data->execute();
    $bookings = $stmt_data->get_result();
} else {
    $bookings = false; 
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการการจอง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .btn-info { background-color: #0dcaf0; border-color: #0dcaf0; }
        .btn-info:hover { background-color: #0b98b8; border-color: #0b98b8;}
        .sidebar-container { background-color: #5a4e8c; color: white; min-height: 100vh; position: sticky; top: 0; }
        .sidebar-container a { color: white; text-decoration: none; }
        .sidebar-container .nav-link:hover, .sidebar-container .nav-link.active { background-color: #7c6db3; border-radius: 5px; }
        td.truncate-col, th.truncate-col { max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include("admin_sidebar.php"); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <h3 class="mb-4"><i class="bi bi-calendar-check-fill me-2"></i>จัดการการจอง</h3>
            
            <div class="card p-3 mb-4">
                <form class="d-flex flex-wrap gap-3 align-items-end" method="get">
                    
                    <div>
                        <label class="form-label">ประเภทป้าย</label>
                        <select name="type" class="form-select">
                            <option value="">-- ทั้งหมด --</option>
                            <option value="LED" <?= $type_filter == 'LED' ? 'selected' : '' ?>>LED</option>
                            <option value="Vinyl" <?= $type_filter == 'Vinyl' ? 'selected' : '' ?>>ไวนิล</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="">-- ทั้งหมด --</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                            <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>ปฏิเสธ</option>
                            <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>ดำเนินการแล้ว</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">จากวันที่เริ่ม</label>
                        <input type="date" name="start" value="<?= htmlspecialchars($date_start) ?>" class="form-control">
                    </div>
                    <div>
                        <label class="form-label">ถึงวันที่เริ่ม</label>
                        <input type="date" name="end" value="<?= htmlspecialchars($date_end) ?>" class="form-control">
                    </div>
                    <div class="flex-grow-1" style="min-width: 250px;">
                        <label class="form-label">ค้นหา</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="ID / ผู้จอง / โครงการ / รหัสป้าย" 
                               value="<?= htmlspecialchars($search_query) ?>" 
                               title="ค้นหาด้วย ID, ชื่อผู้จอง, ชื่อโครงการ หรือรหัสป้าย">
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> ค้นหา</button>
                        <a href="admin_bookings.php" class="btn btn-secondary"><i class="bi bi-arrow-counterclockwise"></i> รีเซ็ต</a>
                    </div>
                </form>
            </div>

            <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>สำเร็จ!</strong> บันทึกข้อมูลการจองเรียบร้อยแล้ว
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="table-responsive card">
                <table class="table table-bordered table-hover bg-white mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th class="truncate-col">ผู้จอง</th>
                            <th class="truncate-col">ชื่อโครงการ</th>
                            <th>รหัสป้าย</th>
                            <th>วันที่เริ่ม</th>
                            <th>วันที่สิ้นสุด</th>
                            <th>สถานะ</th>
                            <th style="width: 120px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings && $bookings->num_rows > 0): ?>
                            <?php while ($row = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td class="truncate-col" title="<?= htmlspecialchars($row['requester_name']) ?>"><?= htmlspecialchars($row['requester_name']) ?></td>
                                    <td class="truncate-col" title="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['sign_code']) ?></td>
                                    <td><?= formatDateThai($row['start_date']) ?></td>
                                    <td><?= formatDateThai($row['end_date']) ?></td>
                                    <td>
                                        <?php $badge_info = getStatusBadge($row['status']); ?>
                                        <span class="badge <?= $badge_info['class'] ?>"><?= $badge_info['text'] ?></span>
                                    </td>
                                    <td>
                                        <a href="booking_detail.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm w-100" title="ดู/แก้ไขรายละเอียด">
                                            <i class="bi bi-eye-fill"></i> ดู/แก้ไข
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center p-4">ไม่พบข้อมูลการจองตามเงื่อนไขที่ระบุ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">«</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">»</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>