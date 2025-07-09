<?php
include(__DIR__ . '/../db.php');

function dateThaiShort($date) {
    $months_th = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $timestamp = strtotime($date);
    $day = date("j", $timestamp);
    $month = (int)date("n", $timestamp);
    return [$day, $months_th[$month]];
}

function thaiMonthYearEN($date) {
    $months_th = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $timestamp = strtotime($date);
    $month = (int)date('n', $timestamp);
    $year = (int)date('Y', $timestamp);
    return $months_th[$month] . " " . $year;
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

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

$led_boards = $conn->query("SELECT id, code FROM sign_boards WHERE type = 'LED' AND version = 'new' ORDER BY id");
$led_list = [];
while ($row = $led_boards->fetch_assoc()) {
    $led_list[] = $row;
}

$query_start = date('Y-m-d', strtotime('-7 days', strtotime($month_start)));
$query_end   = date('Y-m-d', strtotime('+7 days', strtotime($month_end)));

$booking_map = [];
// [EDIT 1] เพิ่ม user_status และ position เพื่อให้ Modal แสดงข้อมูลครบถ้วน
$booking_stmt = $conn->prepare("
    SELECT id, sign_board_id, title, status, start_date, end_date, 
           user_status, faculty, workplace, position, 
           sample_file, drive_link
    FROM bookings 
    WHERE NOT (end_date < ? OR start_date > ?)
");
$booking_stmt->bind_param("ss", $query_start, $query_end);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

while ($row = $booking_result->fetch_assoc()) {
    $booking_map[$row['sign_board_id']][] = $row;
}

function getPrevMonth($month, $year) {
    $month--; if ($month < 1) { $month = 12; $year--; }
    return ['month' => $month, 'year' => $year];
}

function getNextMonth($month, $year) {
    $month++; if ($month > 12) { $month = 1; $year++; }
    return ['month' => $month, 'year' => $year];
}

$prev = getPrevMonth($month, $year);
$next = getNextMonth($month, $year);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตารางการใช้งานป้าย LED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
    body {
        font-family: 'Prompt', sans-serif;
        background: #f0f2f5;
    }
    .header-bar {
        background: #222;
        color: #fff;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .header-bar h5 {
        margin: 0;
        font-weight: 500;
    }
    .gantt-wrapper {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #ccc;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .gantt-header {
        display: grid;
        grid-template-columns: repeat(<?= count($days) ?> , 1fr);
        background: #222;
        color: white;
        font-size: 0.85rem;
        text-align: center;
        border-radius: 12px;
        overflow: hidden;
    }
    .gantt-date {
        padding: 6px 4px;
        display: flex;
        flex-direction: column;
        line-height: 1.2;
        white-space: nowrap;
    }
    .gantt-date span {
        display: block;
    }
    .gantt-body {
        display: flex;
        flex-direction: column;
        padding: 8px;
    }
    .gantt-row {
        display: grid;
        grid-template-columns: repeat(<?= count($days) ?> , 1fr);
        height: 50px;
        border-bottom: 1px solid #eaeaea;
        position: relative;
        background: #fafafa;
        border-radius: 6px;
        margin-bottom: 6px;
    }
    .gantt-bar {
        position: absolute;
        height: 36px;
        width: 100%;
        border-radius: 8px;
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        transition: transform 0.2s;
    }
    .gantt-bar:hover {
        transform: scale(1.02);
    }
    .bg-approve { background-color: #28a745; }
    .bg-pending { background-color: #ffc107; color: black; }
    .bg-reject { background-color: #dc3545; }
    .bg-in-progress { background-color: #007bff; }
    .gantt-header > div,
    .gantt-row > div {
        border-right: 1px solid rgba(0, 0, 0, 0.01);
    }
    .gantt-scroll {
        max-height: calc(50px * 10 + 6px * 10);
        overflow-y: auto;
    }
    .gantt-scroll::-webkit-scrollbar { width: 8px; }
    .gantt-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
    .gantt-scroll::-webkit-scrollbar-thumb:hover { background: #555; }
    .gantt-body {
        display: flex;
        flex-direction: column;
        padding: 8px;
    }
    #modalFiles img, #modalFiles video {
        border-radius: 0.5rem; border: 1px solid #dee2e6;
        max-height: 300px; max-width: 100%; margin-bottom: 0.5rem;
    }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="header-bar">
        <a href="?month=<?= $prev['month'] ?>&year=<?= $prev['year'] ?>" class="btn btn-outline-light btn-sm">
            <i class="bi bi-chevron-left"></i> ก่อนหน้า
        </a>
        <h5><i class="bi bi-calendar-week"></i> ตารางการใช้งานป้าย LED - <?= thaiMonthYearEN($month_start) ?></h5>
        <a href="?month=<?= $next['month'] ?>&year=<?= $next['year'] ?>" class="btn btn-outline-light btn-sm">
            ถัดไป <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    <div class="gantt-wrapper">
        <div class="gantt-header">
            <?php foreach ($days as $d): ?>
                <?php [$dayNum, $monthTxt] = dateThaiShort($d); ?>
            <div class="gantt-date">
                <span><?= $dayNum ?></span>
                <span><?= $monthTxt ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="gantt-scroll">
            <div class="gantt-body">
                <?php
                foreach ($led_list as $sign) {
                    $bookings = $booking_map[$sign['id']] ?? [];
                    foreach ($bookings as $b) {
                        $start_index = array_search(date('Y-m-d', strtotime($b['start_date'])), $days);
                        $end_index = array_search(date('Y-m-d', strtotime($b['end_date'])), $days);

                        if ($start_index === false || $end_index === false) {
                            if (strtotime($b['start_date']) > strtotime($month_end) || strtotime($b['end_date']) < strtotime($month_start)) {
                                continue;
                            }
                            $start_index = (strtotime($b['start_date']) < strtotime($month_start)) ? 0 : $start_index;
                            $end_index = (strtotime($b['end_date']) > strtotime($month_end)) ? count($days) - 1 : $end_index;
                            if ($start_index === false || $end_index === false) continue;
                        }

                        $span = max(1, $end_index - $start_index + 1);

                        switch ($b['status']) {
                            case 'approved': $class = 'bg-approve'; break;
                            case 'pending':  $class = 'bg-pending'; break;
                            case 'rejected': $class = 'bg-reject'; break;
                            case 'in_progress': $class = 'bg-in-progress'; break;
                            default: $class = 'bg-secondary'; break;
                        }

                        $statusLabel = match ($b['status']) {
                            'approved' => 'อนุมัติแล้ว',
                            'pending'  => 'รอดำเนินการ',
                            'rejected' => 'ปฏิเสธแล้ว',
                            'in_progress' => 'ดำเนินการแล้ว',
                            default    => 'ไม่ระบุ'
                        };
                ?>
                <div class="gantt-row">
                    <?php for ($i = 0; $i < count($days); $i++): ?>
                    <div></div>
                    <?php endfor; ?>
                    
                    <div class="gantt-bar <?= $class ?>" 
                         data-bs-toggle="modal" 
                         data-bs-target="#activityModal"
                         data-title="<?= htmlspecialchars($b['title']) ?>"
                         data-range="<?= date('j M Y', strtotime($b['start_date'])) . ' - ' . date('j M Y', strtotime($b['end_date'])) ?>"
                         data-status="<?= $statusLabel ?>"
                         data-user-status="<?= htmlspecialchars($b['user_status'] ?? 'ไม่ระบุ') ?>"
                         data-faculty="<?= htmlspecialchars($b['faculty'] ?? '') ?>"
                         data-workplace="<?= htmlspecialchars($b['workplace'] ?? '') ?>"
                         data-position="<?= htmlspecialchars($b['position'] ?? '') ?>"
                         data-sample="<?= htmlspecialchars($b['sample_file'] ?? '') ?>"
                         data-drive="<?= htmlspecialchars($b['drive_link'] ?? '') ?>"
                         style="grid-column: <?= $start_index + 1 ?> / span <?= $span ?>;">
                        <?= htmlspecialchars($b['title']) ?> [<?= $statusLabel ?>]
                    </div>
                </div>

                <?php
                    }
                }
                
                $total_rows = 0;
                foreach ($led_list as $sign) {
                    $bookings = $booking_map[$sign['id']] ?? [];
                    $total_rows += count($bookings);
                }
                
                $empty_rows = max(0, 10 - $total_rows);
                
                for ($i = 0; $i < $empty_rows; $i++):
                ?>
                <div class="gantt-row">
                    <?php for ($j = 0; $j < count($days); $j++): ?>
                    <div></div>
                    <?php endfor; ?>
                </div>
                <?php endfor; ?>

            </div>
        </div>
    </div>

    <div class="status-legend mb-3">
        <span class="badge bg-in-progress me-2"><i class="bi bi-play-circle-fill"></i> ดำเนินการแล้ว</span>
        <span class="badge bg-approve me-2"><i class="bi bi-check-circle-fill"></i> อนุมัติแล้ว</span>
        <span class="badge bg-pending me-2"><i class="bi bi-hourglass-split"></i> รออนุมัติ</span>
        <span class="badge bg-reject me-2"><i class="bi bi-x-circle-fill"></i> ปฏิเสธแล้ว</span>
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
document.addEventListener('DOMContentLoaded', function() {
    const activityModal = new bootstrap.Modal(document.getElementById('activityModal'));
    const modalTitle = document.getElementById('modalTitle');
    const modalDateRange = document.getElementById('modalDateRange');
    const modalStatus = document.getElementById('modalStatus');
    const modalAffiliationLabel = document.getElementById('modalAffiliationLabel');
    const modalAffiliation = document.getElementById('modalAffiliation');
    const modalFiles = document.getElementById('modalFiles');

    document.querySelectorAll('.gantt-bar[data-title]').forEach(function (bar) {
        bar.addEventListener('click', function () {
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
                 modalAffiliation.textContent = userStatus || '-';
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
                } else if (['mp4', 'webm', 'mov'].includes(extension)) {
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