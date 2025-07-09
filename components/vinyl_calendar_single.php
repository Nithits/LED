<?php
$sign_board_id = isset($_GET['sign_board_id']) ? (int)$_GET['sign_board_id'] : 0;
if (!$sign_board_id) {
    echo "<h5 class='text-danger'>ไม่พบรหัสป้ายที่เลือก</h5>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ปฏิทินกิจกรรมป้ายไวนิล</title>

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f0f2f5;
        padding: 20px;
    }
    #calendar-container {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        max-width: 1100px;
        margin: auto;
    }
    .fc .fc-col-header-cell-cushion { color: #333; font-weight: 600; }
    .fc .fc-daygrid-day-number { color: #333 !important; font-weight: 500; text-decoration: none !important; }
    .fc-approved { background-color: #198754 !important; border-color: #198754 !important; }
    .fc-pending { background-color: #ffc107 !important; border-color: #ffc107 !important; color: black !important; }
    .fc-in_progress { background-color: #0d6efd !important; border-color: #0d6efd !important; }
    .fc .fc-toolbar-title { font-size: 1.75rem; font-weight: 600; }
    .fc th, .fc td, .swal2-title, .swal2-html-container { font-family: 'Prompt', sans-serif; }
    .fc-daygrid-event:hover { filter: brightness(1.1); cursor: pointer; }
    .fc-event-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fc-event { outline: none !important; box-shadow: none !important; border-radius: 6px; padding: 4px 8px; font-weight: 500; }
    .swal2-popup { border-radius: 12px !important; }
</style>
</head>
<body>

    <div id="calendar-container">
        <div id='calendar'></div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'th',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            events: {
                url: '../events.php',
                method: 'GET',
                extraParams: {
                    sign_board_id: <?= $sign_board_id ?>
                },
                failure: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดข้อมูลกิจกรรมได้'
                    });
                }
            },
            eventDisplay: 'block',
            eventClick: function(info) {
                const props = info.event.extendedProps;
                let statusText, statusClass;

                // --- [NEW] ส่วนของการจัดการและจัดรูปแบบวันที่ ---
                const startDate = info.event.start;
                const endDate = info.event.end;
                
                // FullCalendar ให้ end date แบบ exclusive (วันสุดท้าย + 1) จึงต้องลบออก 1 วันเพื่อแสดงผล
                const inclusiveEndDate = new Date(endDate);
                inclusiveEndDate.setDate(inclusiveEndDate.getDate() - 1);

                // สร้างตัวจัดรูปแบบวันที่เป็นภาษาไทย
                const thaiDateFormatter = new Intl.DateTimeFormat('th-TH', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });

                const formattedStartDate = thaiDateFormatter.format(startDate);
                let dateRangeString;
                
                // ตรวจสอบว่าเป็นกิจกรรมวันเดียวหรือไม่
                if (!endDate || startDate.getTime() === inclusiveEndDate.getTime()) {
                    dateRangeString = formattedStartDate;
                } else {
                    dateRangeString = `${formattedStartDate} - ${thaiDateFormatter.format(inclusiveEndDate)}`;
                }
                // --- [END] สิ้นสุดส่วนของการจัดการวันที่ ---

                switch (props.status) {
                    case 'approved':
                        statusText = 'อนุมัติแล้ว';
                        statusClass = 'badge bg-success';
                        break;
                    case 'pending':
                        statusText = 'รออนุมัติ';
                        statusClass = 'badge bg-warning text-dark';
                        break;
                    case 'in_progress':
                        statusText = 'ดำเนินการแล้ว';
                        statusClass = 'badge bg-primary';
                        break;
                    default:
                        statusText = 'ไม่ระบุ';
                        statusClass = 'badge bg-secondary';
                }

                Swal.fire({
                    icon: 'info',
                    title: `<span style="font-weight: 600;">${info.event.title}</span>`,
                    // [MODIFIED] เพิ่มบรรทัดแสดงช่วงเวลา
                    html: `
                        <div style="text-align: left; padding: 0 1rem; font-size: 1rem;">
                            <hr>
                            <p class="mb-2">
                                <strong><i class="bi bi-calendar-range-fill text-info"></i> ช่วงเวลา:</strong> 
                                ${dateRangeString}
                            </p>
                            <p class="mb-2">
                                <strong><i class="bi bi-geo-alt-fill text-danger"></i> ตำแหน่ง:</strong> 
                                ${props.location || 'ไม่ระบุ'}
                            </p>
                            <p class="mb-0">
                                <strong><i class="bi bi-flag-fill text-primary"></i> สถานะ:</strong> 
                                <span class="${statusClass}">${statusText}</span>
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'ปิด',
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        calendar.render();
    });
    </script>

</body>
</html>