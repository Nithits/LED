<?php
include(__DIR__ . '/../db.php');

function dateThaiShort($date) {
    if (is_array($date)) { $date = $date[0]; }
    $months_th = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $timestamp = strtotime($date);
    return date("j", $timestamp) . " " . $months_th[(int)date("n", $timestamp)];
}

function thaiMonthYearEN($date) {
    if (is_array($date)) { $date = $date[0]; }
    $months_th = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $timestamp = strtotime($date);
    return $months_th[(int)date('n', $timestamp)] . " " . (int)date('Y', $timestamp);
}

// 1. ตั้งค่าเดือน/ปีและคำนวณช่วงวันที่
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2020 || $year > 2100) $year = date('Y');

$month_start = date('Y-m-01', strtotime("$year-$month-01"));
$month_end   = date('Y-m-t', strtotime($month_start));
$days = [];
$current = strtotime($month_start);
while ($current <= strtotime($month_end)) {
    $days[] = date('Y-m-d', $current);
    $current = strtotime('+1 day', $current);
}

// 2. ดึงรายการป้าย
$vinyl_boards = $conn->query("SELECT id, code, location FROM sign_boards WHERE type = 'Vinyl' AND version = 'new' ORDER BY id");
$vinyl_list = [];
while ($row = $vinyl_boards->fetch_assoc()) {
    $vinyl_list[] = $row;
}

// 3. ดึงการจองที่คาบเกี่ยว
$query_start = date('Y-m-d', strtotime('-7 days', strtotime($month_start)));
$query_end   = date('Y-m-d', strtotime('+7 days', strtotime($month_end)));

$booking_map = [];
$booking_stmt = $conn->prepare("
    SELECT sign_board_id, title, status, start_date, end_date, 
           user_status, faculty, workplace, position, 
           sample_file, drive_link 
    FROM bookings 
    WHERE NOT (end_date < ? OR start_date > ?)
      AND status NOT IN ('rejected', 'cancelled')
");
$booking_stmt->bind_param("ss", $query_start, $query_end);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

while ($row = $booking_result->fetch_assoc()) {
    $sid = $row['sign_board_id'];
    $booking_map[$sid][] = $row;
}

function getPrevMonth($month, $year) { $month--; if ($month < 1) { $month = 12; $year--; } return ['month' => $month, 'year' => $year]; }
function getNextMonth($month, $year) { $month++; if ($month > 12) { $month = 1; $year++; } return ['month' => $month, 'year' => $year]; }

$prev = getPrevMonth($month, $year);
$next = getNextMonth($month, $year);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตารางป้ายไวนิล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; font-family: 'Prompt', sans-serif; }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
            white-space: nowrap;
            height: 60px;
        }
        td.busy {
            font-weight: 500;
            padding: 6px;
            border-left: 3px solid #fff;
            border-radius: 0.5rem;
            cursor: pointer;
            overflow: hidden;
        }
        td.busy span.d-block {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        td.text-muted { color: #adb5bd !important; }
        .table thead th { 
            background-color: #333; 
            color: white; 
            font-size: 0.8rem; 
            position: sticky; 
            top: 0; 
            z-index: 10; 
        }
        .table tbody th { 
            background-color: #e9ecef; 
            font-weight: 600; 
            font-size: 0.9rem; 
            position: sticky; 
            left: 0; 
            z-index: 5;
        }
        /* --- [FIXED] เพิ่ม CSS เพื่อล็อกช่อง "ป้าย / วันที่" --- */
        .table thead tr th:first-child {
            left: 0;
            z-index: 15; /* สูงกว่า z-index ของ thead และ tbody th */
        }
        .header-section {
            background-color: #333; color: white;
            padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem;
        }
        .bg-approve { background-color: #28a745 !important; color: white; }
        .bg-pending { background-color: #ffc107 !important; color: black; font-weight: bold; }
        .bg-reject { background-color: #dc3545 !important; color: white; }
        .bg-in-progress { background-color: #007bff !important; color: white; }
        
        #modalFiles img, #modalFiles video {
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            max-height: 300px;
            max-width: 100%;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="header-section">
        <div class="d-flex align-items-center justify-content-between">
            <a href="?month=<?= $prev['month'] ?>&year=<?= $prev['year'] ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-chevron-left"></i> ก่อนหน้า</a>
            <h4 class="mb-0 text-center"><i class="bi bi-calendar-week"></i> ตารางการใช้งานป้ายไวนิล - <?= thaiMonthYearEN($month_start) ?></h4>
            <a href="?month=<?= $next['month'] ?>&year=<?= $next['year'] ?>" class="btn btn-sm btn-outline-light">ถัดไป <i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white table-sm">
            <thead>
                <tr>
                    <th style="min-width: 140px;">ป้าย / วันที่</th>
                    <?php foreach ($days as $d): ?>
                        <th><?= dateThaiShort($d) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vinyl_list as $sign):
                echo "<tr><th>" . htmlspecialchars($sign['code']) . ($sign['location'] ? "<div class='small text-muted'>" . htmlspecialchars($sign['location']) . "</div>" : '') . "</th>";      
                $sid = $sign['id'];
                $bookings = $booking_map[$sid] ?? [];
                
                for ($i = 0; $i < count($days); ) {
                    $current_date = $days[$i];
                    $matched = null;
                    foreach ($bookings as $b) {
                        if (strtotime($current_date) >= strtotime($b['start_date']) && strtotime($current_date) <= strtotime($b['end_date'])) {
                            $matched = $b;
                            break;
                        }
                    }

                    if ($matched) {
                        $start = new DateTime($matched['start_date']);
                        $end = new DateTime($matched['end_date']);
                        $display_start = max(strtotime($matched['start_date']), strtotime($current_date));
                        $display_end = min(strtotime($matched['end_date']), strtotime(end($days)));
                        $colspan = round(($display_end - $display_start) / 86400) + 1;
                        
                        if ($display_start === strtotime($matched['start_date']) || $current_date === $month_start) {
                            $title = htmlspecialchars($matched['title']);
                            switch ($matched['status']) {
                                case 'approved':    $class = 'bg-approve'; $icon = '<i class="bi bi-check-circle-fill"></i> '; $label = 'อนุมัติแล้ว'; break;
                                case 'pending':     $class = 'bg-pending'; $icon = '<i class="bi bi-hourglass-split"></i> '; $label = 'รอดำเนินการ'; break;
                                case 'in_progress': $class = 'bg-in-progress'; $icon = '<i class="bi bi-play-circle-fill"></i> '; $label = 'ดำเนินการแล้ว'; break;
                                case 'rejected':    $class = 'bg-reject'; $icon = '<i class="bi bi-x-circle-fill"></i> '; $label = 'ปฏิเสธแล้ว'; break;
                                default:            $class = 'bg-secondary'; $icon = ''; $label = 'ไม่ระบุ';
                            }

                            echo "<td class='busy $class' colspan='$colspan'
                                      data-title='$title'
                                      data-range='" . date('j M Y', strtotime($matched['start_date'])) . " - " . date('j M Y', strtotime($matched['end_date'])) . "'
                                      data-status='$label'
                                      data-user-status='" . htmlspecialchars($matched['user_status']) . "'
                                      data-faculty='" . htmlspecialchars($matched['faculty'] ?? '') . "'
                                      data-workplace='" . htmlspecialchars($matched['workplace'] ?? '') . "'
                                      data-position='" . htmlspecialchars($matched['position'] ?? '') . "'
                                      data-sample='" . htmlspecialchars($matched['sample_file'] ?? '') . "'
                                      data-drive='" . htmlspecialchars($matched['drive_link'] ?? '') . "'>
                                      <span class='d-block' title='$title'>$icon$title</span>
                                      <small class='d-block'>[" . $label . "]</small>
                                  </td>";
                            $i += $colspan;
                        } else {
                            $i++;
                        }
                    } else {
                        echo "<td class='text-muted'><i class='bi bi-dash-circle'></i> ว่าง</td>";
                        $i++;
                    }
                }
                echo "</tr>";
            endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        <span class="badge bg-in-progress me-2"><i class="bi bi-play-circle-fill"></i> ดำเนินการแล้ว</span>
        <span class="badge bg-approve me-2"><i class="bi bi-check-circle-fill"></i> อนุมัติแล้ว</span>
        <span class="badge bg-pending me-2"><i class="bi bi-hourglass-split"></i> รออนุมัติ</span>
        <span class="badge bg-reject me-2"><i class="bi bi-x-circle-fill"></i> ปฏิเสธแล้ว</span>
        <span class="text-muted ms-2"><i class="bi bi-dash-circle"></i> ว่าง</span>
    </div>

</div>

<div class="modal fade" id="activityModal" tabindex="-1" aria-labelledby="activityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-semibold" id="activityModalLabel"><i class="bi bi-info-circle-fill me-2"></i> รายละเอียดกิจกรรม</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="mb-3"><span class="text-muted fw-semibold">ชื่อกิจกรรม:</span><div class="bg-light border rounded p-2" id="modalTitle"></div></div>
                <div class="mb-3"><span class="text-muted fw-semibold">ช่วงเวลา:</span><div class="bg-light border rounded p-2" id="modalDateRange"></div></div>
                <div class="mb-3"><span class="text-muted fw-semibold">สถานะ:</span><div class="bg-light border rounded p-2" id="modalStatus"></div></div>
                <div class="mb-3">
                    <span class="text-muted fw-semibold" id="modalAffiliationLabel">สังกัด:</span>
                    <div class="bg-light border rounded p-2" id="modalAffiliation"></div>
                </div>
                <div class="mb-2">
                    <span class="text-muted fw-semibold">ไฟล์แนบ:</span>
                    <div id="modalFiles" class="bg-light border rounded p-3 mt-2 text-center"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JavaScript ทั้งหมดไม่มีการเปลี่ยนแปลง
document.addEventListener('DOMContentLoaded', function() {
    const activityModal = new bootstrap.Modal(document.getElementById('activityModal'));
    const modalTitle = document.getElementById('modalTitle');
    const modalDateRange = document.getElementById('modalDateRange');
    const modalStatus = document.getElementById('modalStatus');
    const modalAffiliationLabel = document.getElementById('modalAffiliationLabel');
    const modalAffiliation = document.getElementById('modalAffiliation');
    const modalFiles = document.getElementById('modalFiles');

    document.querySelectorAll('.busy[data-title]').forEach(function (cell) {
        cell.addEventListener('click', function () {
            modalTitle.textContent = this.dataset.title;
            modalDateRange.textContent = this.dataset.range;
            modalStatus.textContent = this.dataset.status;

            const userStatus = this.dataset.userStatus;
            if (userStatus === 'นิสิต') {
                modalAffiliationLabel.textContent = 'คณะ:';
                modalAffiliation.textContent = this.dataset.faculty || '-';
            } else if (userStatus === 'บุคลากร') {
                modalAffiliationLabel.textContent = 'หน่วยงาน/ตำแหน่ง:';
                const workplace = this.dataset.workplace || '';
                const position = this.dataset.position || '';
                modalAffiliation.textContent = (workplace && position) ? `${workplace} (${position})` : (workplace || position || '-');
            } else {
                 modalAffiliationLabel.textContent = 'สังกัด:';
                 modalAffiliation.textContent = '-';
            }

            modalFiles.innerHTML = '';
            let hasContent = false;
            const sampleFile = this.dataset.sample;
            if (sampleFile) {
                const filePath = '../uploads/' + sampleFile; 
                const extension = sampleFile.split('.').pop().toLowerCase();
                
                if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                    const img = document.createElement('img');
                    img.src = filePath;
                    img.alt = 'รูปภาพที่แนบไว้';
                    modalFiles.appendChild(img);
                    hasContent = true;
                } else if (['mp4', 'webm'].includes(extension)) {
                    const video = document.createElement('video');
                    video.src = filePath;
                    video.controls = true;
                    modalFiles.appendChild(video);
                    hasContent = true;
                }
            }
            
            const driveLink = this.dataset.drive;
            if (driveLink) {
                const link = document.createElement('a');
                link.href = driveLink;
                link.target = '_blank';
                link.className = 'btn btn-outline-primary btn-sm d-block mt-2';
                link.innerHTML = '<i class="bi bi-box-arrow-up-right"></i> เปิดไฟล์จาก Google Drive';
                modalFiles.appendChild(link);
                hasContent = true;
            }

            if (!hasContent) {
                modalFiles.innerHTML = '<p class="text-muted mb-0">ไม่มีไฟล์แนบ</p>';
            }

            activityModal.show();
        });
    });
});
</script>

</body>
</html>