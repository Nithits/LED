<?php
session_name('user_session');
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login_user.php");
  exit;
}

$alert = '';
if (isset($_GET['success'])) {
  $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check-circle-fill me-2"></i>จองป้ายสำเร็จเรียบร้อยแล้ว
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? '';

$result = $conn->prepare("SELECT b.*, s.code, s.name AS sign_name, s.type 
                          FROM bookings b 
                          INNER JOIN sign_boards s ON b.sign_board_id = s.id 
                          WHERE b.user_id = ? 
                          ORDER BY b.created_at DESC");  // เรียงจากเวลาที่การจองเข้ามาล่าสุด
$result->bind_param("i", $user_id);
$result->execute();
$rows = $result->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายการจองของฉัน</title>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../css/sysstyle.css">
  <link rel="icon" type="image/x-icon" href="../images/iconmsu.ico">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      font-family: "Prompt", sans-serif;
      background-color: #f8f9fa;
    }
    .wrapper { min-height: 100%; display: flex; flex-direction: column; }
    main.content { flex: 1; }
    .badge-pending { background-color: #ffc107; }
    .badge-approved { background-color: #28a745; }
    .badge-cancelled { background-color: #6c757d; }
    /* จำกัดการแสดงข้อความในคอลัมน์ "ชื่อกิจกรรม" */
    .table td.text-start {
      max-width: 200px; /* กำหนดความกว้างสูงสุด */
      overflow: hidden; /* ซ่อนข้อความที่เกิน */
      text-overflow: ellipsis; /* แสดง ... ถ้าข้อความเกิน */
      white-space: nowrap; /* ป้องกันไม่ให้ข้อความแตกบรรทัด */
    }
  </style>
</head>
<body>
<div class="wrapper">
  <?php include('../components/top-navbar.php'); ?>
  <main class="content">
    <div class="container bg-white p-4 rounded shadow mt-4">
        <!-- แสดงข้อความแจ้งเตือนใต้ navbar -->
        <?= $alert ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="mb-1">รายการจองของฉัน</h5>
          <div class="text-muted small">
            <span class="me-3"><i class="bi bi-pencil-square"></i> <strong>แก้ไข</strong> ข้อมูล</span>
            <span class="me-3"><i class="bi bi-x-circle"></i> <strong>ยกเลิก</strong> การจอง</span>
            <span><i class="bi bi-chat-dots"></i> <strong>ติดต่อ</strong> เจ้าหน้าที่</span>
          </div>
        </div>
      </div>
      <div class="row g-2 align-items-end mb-3">
        <div class="col-md-2">
          <label class="form-label mb-1">จากวันที่</label>
          <input type="date" id="startDate" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">ถึงวันที่</label>
          <input type="date" id="endDate" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">ประเภทป้าย</label>
          <select id="filterType" class="form-select form-select-sm">
            <option value="">ทุกประเภท</option>
            <option value="LED">LED</option>
            <option value="Vinyl">Vinyl</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">สถานะการจอง</label>
          <select id="filterStatus" class="form-select form-select-sm">
            <option value="">ทุกสถานะ</option>
            <option value="รออนุมัติ">รออนุมัติ</option>
            <option value="อนุมัติแล้ว">อนุมัติแล้ว</option>
            <option value="ยกเลิกจองแล้ว">ยกเลิกจองแล้ว</option>
            <option value="ดำเนินการแล้ว">ดำเนินการแล้ว</option> <!-- เพิ่มตัวเลือก "ดำเนินการแล้ว" -->
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1">ค้นหา</label>
          <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="ชื่อกิจกรรม / ป้าย / รหัส">
        </div>
      </div>

      <table class="table" id="bookingTable">
          <thead>
              <tr>
                  <th>วันที่เริ่ม</th>
                  <th>ถึงวันที่</th>
                  <th>รหัสป้าย</th>
                  <th>ชื่อกิจกรรม</th>
                  <th>ประเภท</th>
                  <th>สถานะ</th>
                  <th class="text-center" style="width: 1%;">แก้ไข</th>
                  <th class="text-center" style="width: 1%;">ติดต่อ</th>
              </tr>
          </thead>
          <tbody>
              <?php while ($row = $rows->fetch_assoc()): ?>
                  <tr>
                      <td><?= htmlspecialchars($row['start_date']) ?></td>
                      <td><?= htmlspecialchars($row['end_date']) ?></td>
                      <td><?= htmlspecialchars($row['code']) ?></td>
                      <td class="text-start"><?= htmlspecialchars($row['title']) ?></td>
                      <td><?= htmlspecialchars($row['type']) ?></td>
                      <td>
                          <?php
                          if ($row['status'] === 'pending') {
                              echo '<span class="badge bg-warning text-dark">รออนุมัติ</span>';
                          } elseif ($row['status'] === 'approved') {
                              echo '<span class="badge bg-success">อนุมัติแล้ว</span>';
                          } elseif ($row['status'] === 'in_progress') {
                              echo '<span class="badge bg-primary">ดำเนินการแล้ว</span>';
                          } else {
                              echo '<span class="badge bg-danger">ถูกปฏิเสธ</span>';
                          }
                          ?>
                      </td>

                      <td class="text-center">
                          <a href="../edit_booking.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success" title="แก้ไข">
                              <i class="bi bi-pencil-square"></i>
                          </a>
                      </td>

                      <td class="text-center">
                          <a href="../chat.php?booking_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="ติดต่อเจ้าหน้าที่">
                              <i class="bi bi-chat-dots"></i>
                          </a>
                      </td>
                  </tr>
              <?php endwhile; ?>
          </tbody>
      </table>
      <nav><ul class="pagination justify-content-center" id="pagination"></ul></nav>
    </div>
  </main>
  <?php require_once("../components/footer.php"); ?>
</div>

<script>
const rowsPerPage = 10;
let currentPage = 1;

function filterRows() {
  const keyword = document.getElementById("searchInput").value.toLowerCase();
  const start = document.getElementById("startDate").value;
  const end = document.getElementById("endDate").value;
  const type = document.getElementById("filterType").value.toLowerCase();
  const status = document.getElementById("filterStatus").value.trim().toLowerCase();

  const rows = Array.from(document.querySelectorAll("#bookingTable tbody tr"));

  return rows.filter(row => {
    const cells = row.querySelectorAll("td");
    const startDate = cells[0]?.textContent.trim();
    const endDate = cells[1]?.textContent.trim();
    const code = cells[2]?.textContent.trim();
    const title = cells[3]?.textContent.toLowerCase();
    const boardType = cells[4]?.textContent.trim().toLowerCase();
    const statusCell = cells[5]?.textContent.trim().toLowerCase();  // เปลี่ยนเป็น lowercase

    const overlap = (!start && !end) || (
      (!start || endDate >= start) && 
      (!end || startDate <= end)
    );

    return (!keyword || (title.includes(keyword) || code.toLowerCase().includes(keyword))) &&
           overlap &&
           (!type || boardType === type) &&
           (!status || statusCell === status);  // ตรวจสอบสถานะที่กรอง
  });
}

function renderTable() {
  const rows = Array.from(document.querySelectorAll("#bookingTable tbody tr"));
  const filtered = filterRows();
  const startIdx = (currentPage - 1) * rowsPerPage;
  const endIdx = startIdx + rowsPerPage;
  rows.forEach(row => row.style.display = "none");
  filtered.slice(startIdx, endIdx).forEach(row => row.style.display = "");
  renderPagination(filtered.length);
}

function renderPagination(totalItems) {
  const totalPages = Math.ceil(totalItems / rowsPerPage);
  const pagination = document.getElementById("pagination");
  pagination.innerHTML = "";

  const prev = document.createElement("li");
  prev.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
  prev.innerHTML = `<button class="page-link">&laquo;</button>`;
  prev.onclick = () => { if (currentPage > 1) { currentPage--; renderTable(); } };
  pagination.appendChild(prev);

  for (let i = 1; i <= totalPages; i++) {
    const li = document.createElement("li");
    li.className = `page-item ${i === currentPage ? "active" : ""}`;
    li.innerHTML = `<button class="page-link">${i}</button>`;
    li.onclick = () => { currentPage = i; renderTable(); };
    pagination.appendChild(li);
  }

  const next = document.createElement("li");
  next.className = `page-item ${currentPage === totalPages ? "disabled" : ""}`;
  next.innerHTML = `<button class="page-link">&raquo;</button>`;
  next.onclick = () => { if (currentPage < totalPages) { currentPage++; renderTable(); } };
  pagination.appendChild(next);
}

document.querySelectorAll("input#searchInput, input#startDate, input#endDate")
  .forEach(el => el.addEventListener("input", () => {
    currentPage = 1;
    renderTable();
  }));

document.querySelectorAll("select#filterType, select#filterStatus")
  .forEach(el => el.addEventListener("change", () => {
    currentPage = 1;
    renderTable();
  }));

window.addEventListener("DOMContentLoaded", renderTable);
</script>
</body>
</html>
