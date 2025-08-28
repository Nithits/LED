<?php
include(__DIR__ . '/../db.php');

// ดึงข้อมูลทั้งหมด
// ดึงข้อมูลทั้งหมดและเรียงตามเวลาที่การจองเข้ามาล่าสุด
$result = $conn->query("SELECT b.title, b.start_date, b.end_date, s.code, s.name AS sign_name, s.type, b.status
                        FROM bookings b
                        INNER JOIN sign_boards s ON b.sign_board_id = s.id
                        ORDER BY b.created_at DESC"); // เรียงจากเวลาที่การจองเข้ามาล่าสุด


$bookings = [];
while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
}
?>

<style>
    /* จำกัดความยาวข้อความในคอลัมน์ชื่อกิจกรรม */
    .table td.text-start {
      max-width: 200px; /* กำหนดความกว้างสูงสุด */
      overflow: hidden; /* ซ่อนข้อความที่เกิน */
      text-overflow: ellipsis; /* แสดง ... ถ้าข้อความเกิน */
      white-space: nowrap; /* ป้องกันไม่ให้ข้อความแตกบรรทัด */
    }
    /* ปรับฟิลเตอร์ให้ดูสวยงาม */
    .form-label {
      font-size: 0.875rem;
      font-weight: 600;
      color: #555;
    }

    .form-control, .form-select {
      border-radius: 10px;
      padding: 0.5rem 0.75rem;
      background-color: #f1f1f1;
      border: 1px solid #ddd;
      transition: all 0.3s ease;
    }

    .form-control-sm, .form-select-sm {
      font-size: 0.875rem;
    }

    .form-control:focus, .form-select:focus {
      border-color: #1e3a8a;
      box-shadow: 0 0 0 0.2rem rgba(30, 58, 138, 0.25);
    }

    .row.g-3 {
      gap: 1rem;
    }

    .col-md-2 {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    /* ขนาดปุ่มพื้นฐาน */
    .pagination .page-link{ min-width:2.25rem; text-align:center; }

    /* มือถือ (≤576px) ย่อตัวอักษร/ระยะขอบ และเพิ่มช่องไฟระหว่างปุ่ม */
    @media (max-width:576px){
      .pagination{ gap:.25rem; row-gap:.5rem; }
      .pagination .page-link{ padding:.375rem .5rem; font-size:.875rem; }
    }
</style>

<h4 class="fw-bold mb-3"><i class="bi bi-calendar-week me-2"></i>ตารางการจองป้ายประชาสัมพันธ์</h4>

<!-- ฟิลเตอร์ -->
<div class="row g-3 align-items-center mb-4">
  <div class="col-md-2">
    <label class="form-label mb-0">จากวันที่</label>
    <input type="date" id="startDate" class="form-control form-control-sm">
  </div>
  <div class="col-md-2">
    <label class="form-label mb-0">ถึงวันที่</label>
    <input type="date" id="endDate" class="form-control form-control-sm">
  </div>
  <div class="col-md-2">
    <label class="form-label mb-0">ประเภทป้าย</label>
    <select id="filterType" class="form-select form-select-sm">
      <option value="">ทุกประเภท</option>
      <option value="LED">LED</option>
      <option value="Vinyl">Vinyl</option>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label mb-0">สถานะการจอง</label>
    <select id="filterStatus" class="form-select form-select-sm">
      <option value="">ทุกสถานะ</option>
      <option value="รออนุมัติ">รออนุมัติ</option>
      <option value="อนุมัติ">อนุมัติ</option>
      <option value="ยกเลิก">ยกเลิก</option>
      <option value="ดำเนินการแล้ว">ดำเนินการแล้ว</option> <!-- เพิ่มตัวเลือก "ดำเนินการแล้ว" -->
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label mb-0">ค้นหา</label>
    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="ชื่อกิจกรรม / ป้าย / รหัส">
  </div>
</div>

<!-- ตาราง -->
<div class="table-responsive mb-4">
  <table class="table table-bordered table-hover align-middle text-center" id="bookingTable">
    <thead class="table-secondary">
      <tr>
        <th>วันที่เริ่ม</th>
        <th>ถึงวันที่</th>
        <th class="text-start">ชื่อกิจกรรม</th>
        <th>รหัสป้าย</th>
        <th class="text-start">ชื่อป้าย</th>
        <th>ประเภท</th>
        <th>สถานะ</th>
      </tr>
    </thead>
    <tbody>
      <!-- เติมด้วย PHP -->
      <?php foreach ($bookings as $row): ?>
        <tr>
          <td><?= $row['start_date'] ?></td>
          <td><?= $row['end_date'] ?></td>
          <td class="text-start"><?= htmlspecialchars($row['title']) ?></td>
          <td><?= htmlspecialchars($row['code']) ?></td>
          <td class="text-start"><?= htmlspecialchars($row['sign_name']) ?></td>
          <td><?= htmlspecialchars($row['type']) ?></td>
          <td class="status-cell">
            <?php if ($row['status'] === 'pending'): ?>
              <span class="badge bg-warning text-dark">รออนุมัติ</span>
            <?php elseif ($row['status'] === 'approved'): ?>
              <span class="badge bg-success">อนุมัติ</span>
            <?php elseif ($row['status'] === 'in_progress'): ?>
              <span class="badge bg-primary">ดำเนินการแล้ว</span>
            <?php else: ?>
              <span class="badge bg-danger">ถูกปฏิเสธ</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<nav class="mt-3">
  <ul id="pagination"
      class="pagination justify-content-center flex-wrap gap-1"
      role="navigation" aria-label="Pagination">
  </ul>
</nav>

<!-- Script -->
<script>
const rowsPerPage = 10;
let currentPage = 1;
const rows = Array.from(document.querySelectorAll("#bookingTable tbody tr"));

function filterRows() {
  const keyword = document.getElementById("searchInput").value.toLowerCase();
  const start = document.getElementById("startDate").value;
  const end = document.getElementById("endDate").value;
  const type = document.getElementById("filterType").value.toLowerCase();
  const status = document.getElementById("filterStatus").value.trim().toLowerCase();

  return rows.filter(row => {
    const cells = row.querySelectorAll("td");
    const [startDate, endDate, title, code, name, boardType, statusCell] = Array.from(cells).map(td => td.textContent.trim());
    const normalizedStatus = statusCell.replace(/\s+/g, ' ').trim().toLowerCase(); // Normalize the status text for comparison

    return (!keyword || row.textContent.toLowerCase().includes(keyword)) &&
           (!start || startDate >= start) &&
           (!end || endDate <= end) &&
           (!type || boardType.toLowerCase() === type) &&
           (!status || normalizedStatus === status);
  });
}

function renderTable() {
  const filtered = filterRows();
  const startIdx = (currentPage - 1) * rowsPerPage;
  const endIdx = startIdx + rowsPerPage;

  rows.forEach(row => row.style.display = "none");
  filtered.slice(startIdx, endIdx).forEach(row => row.style.display = "");

  renderPagination(filtered.length);
}

function renderPagination(totalItems) {
  const totalPages = Math.ceil(totalItems / rowsPerPage);
  const pagination  = document.getElementById("pagination");
  pagination.innerHTML = "";

  const isMobile   = window.matchMedia("(max-width:576px)").matches;
  const windowSize = isMobile ? 3 : 3; // จำนวนปุ่มเลขหน้าที่จะโชว์ (รวมหน้าปัจจุบัน)

  // คำนวณช่วงเลขหน้าให้ล้อม currentPage
  let start = Math.max(1, currentPage - Math.floor(windowSize / 2));
  let end   = Math.min(totalPages, start + windowSize - 1);
  if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);

  // Prev
  const prev = document.createElement("li");
  prev.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
  prev.innerHTML = `<button class="page-link" aria-label="Previous">&laquo;</button>`;
  prev.onclick = () => { if (currentPage > 1) { currentPage--; renderTable(); } };
  pagination.appendChild(prev);

  // ถ้าช่วงไม่ได้เริ่มที่ 1 ใส่ปุ่มหน้าแรก + จุดไข่ปลา
  if (start > 1) {
    const first = document.createElement("li");
    first.className = "page-item";
    first.innerHTML = `<button class="page-link">1</button>`;
    first.onclick = () => { currentPage = 1; renderTable(); };
    pagination.appendChild(first);

    if (start > 2) {
      const dots = document.createElement("li");
      dots.className = "page-item disabled";
      dots.innerHTML = `<span class="page-link">…</span>`;
      pagination.appendChild(dots);
    }
  }

  // เลขหน้าตามช่วง
  for (let i = start; i <= end; i++) {
    const li = document.createElement("li");
    li.className = `page-item ${i === currentPage ? "active" : ""}`;
    li.innerHTML = `<button class="page-link">${i}</button>`;
    li.onclick = () => { currentPage = i; renderTable(); };
    pagination.appendChild(li);
  }

  // ถ้าช่วงไม่ได้จบที่ totalPages ใส่จุดไข่ปลา + ปุ่มหน้าสุดท้าย
  if (end < totalPages) {
    if (end < totalPages - 1) {
      const dots = document.createElement("li");
      dots.className = "page-item disabled";
      dots.innerHTML = `<span class="page-link">…</span>`;
      pagination.appendChild(dots);
    }
    const last = document.createElement("li");
    last.className = "page-item";
    last.innerHTML = `<button class="page-link">${totalPages}</button>`;
    last.onclick = () => { currentPage = totalPages; renderTable(); };
    pagination.appendChild(last);
  }

  // Next
  const next = document.createElement("li");
  next.className = `page-item ${currentPage === totalPages ? "disabled" : ""}`;
  next.innerHTML = `<button class="page-link" aria-label="Next">&raquo;</button>`;
  next.onclick = () => { if (currentPage < totalPages) { currentPage++; renderTable(); } };
  pagination.appendChild(next);
}

renderTable();
</script>
