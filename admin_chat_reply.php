<?php
session_name('admin_session');
session_start();
include("db.php");

// ฟังก์ชันสำหรับจัดรูปแบบวันที่แชท
function format_chat_date($datetime_str) {
    $timezone = new DateTimeZone('Asia/Bangkok');
    $message_time = new DateTime($datetime_str, $timezone);
    $today_midnight = (new DateTime('now', $timezone))->setTime(0, 0, 0);
    $yesterday_midnight = (new DateTime('yesterday', $timezone))->setTime(0, 0, 0);
    if ($message_time >= $today_midnight) return 'วันนี้';
    if ($message_time >= $yesterday_midnight) return 'เมื่อวานนี้';
    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter('th_TH', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Bangkok', IntlDateFormatter::GREGORIAN, 'eeee, d MMMM Y');
        return $formatter->format($message_time);
    } else {
        $thai_days = ["อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์"];
        $thai_months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        $day_of_week = "วัน" . $thai_days[(int)$message_time->format('w')];
        $day = $message_time->format('j');
        $month = $thai_months[(int)$message_time->format('n') - 1];
        $year = (int)$message_time->format('Y') + 543;
        return "$day_of_week ที่ $day $month $year";
    }
}

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$sender = 'admin';
$sender_id = $_SESSION['admin_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Query ดึงรายการแชททั้งหมดสำหรับ Sidebar
$booking_list_stmt = $conn->query("
    SELECT b.id, b.title, sb.type,
           MAX(c.created_at) AS last_message_time, 
           SUM(CASE WHEN c.sender != 'admin' AND c.is_read = 0 THEN 1 ELSE 0 END) AS unread_count 
    FROM bookings b 
    LEFT JOIN chat_messages c ON b.id = c.booking_id 
    LEFT JOIN sign_boards sb ON b.sign_board_id = sb.id
    GROUP BY b.id 
    ORDER BY 
        (CASE WHEN SUM(CASE WHEN c.sender != 'admin' AND c.is_read = 0 THEN 1 ELSE 0 END) > 0 THEN 0 ELSE 1 END), 
        last_message_time DESC, b.id DESC
");

$activity_title = 'ไม่ทราบกิจกรรม';
$sign_type = ''; // เพิ่มตัวแปรเพื่อเก็บประเภทป้าย
$messages = [];

if ($booking_id > 0) {
    // อัปเดตสถานะข้อความเป็นอ่านแล้ว
    $mark_stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE booking_id = ? AND sender != ? AND is_read = 0");
    $mark_stmt->bind_param("is", $booking_id, $sender);
    $mark_stmt->execute();

    // ดึงข้อมูล title และประเภทป้าย
    $booking_info_stmt = $conn->prepare("
        SELECT b.title, sb.type 
        FROM bookings b
        LEFT JOIN sign_boards sb ON b.sign_board_id = sb.id
        WHERE b.id = ?
    ");
    $booking_info_stmt->bind_param("i", $booking_id);
    $booking_info_stmt->execute();
    $booking_info = $booking_info_stmt->get_result()->fetch_assoc();

    $activity_title = $booking_info['title'] ?? 'ไม่ทราบกิจกรรม';
    $sign_type = $booking_info['type'] ?? '';

    // ดึงข้อความสนทนา
    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE booking_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $messages = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>การสนทนาผู้ดูแล - Booking #<?= $booking_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
<style>
    html, body { height: 100%; margin: 0; font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
    .container-fluid, .row { height: 100vh; overflow: hidden; }
    .sidebar-scroll { height: 100vh; overflow-y: auto; padding: 20px 10px; background-color: #ede7f6; border-right: 1px solid #ce93d8; }
    .list-group-item.active { background-color: #7e57c2 !important; color: white !important; }
    .chat-area-container { background-color: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; flex-direction: column; height: 100vh; }
    .chat-messages { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; padding-right: 10px; margin-bottom: 1rem; }
    .message-box { max-width: 70%; padding: 10px 15px; border-radius: 18px; line-height: 1.5; word-wrap: break-word; }
    .from-me { background-color: #7e57c2; color: white; border-bottom-right-radius: 0; }
    .from-other { background-color: #e9ecef; color: #333; border-bottom-left-radius: 0; }
    .message-time { font-size: 0.75rem; color: #a79ab1; text-align: right; margin-top: 5px; }
    .from-other .message-time { color: #6c757d; }
    .read-receipt { font-size: 0.75rem; color: #6c757d; margin-right: 8px; white-space: nowrap; width: 45px; text-align: right;}
    .chat-date-divider { text-align: center; margin: 20px 0; color: #888; font-size: 0.8rem; font-weight: 500; display: flex; align-items: center; }
    .chat-date-divider::before, .chat-date-divider::after { content: ''; flex-grow: 1; height: 1px; background-color: #e0e0e0; margin: 0 10px; }
    form#chatForm { margin-top: auto; display: flex; gap: 10px; }
    form#chatForm input[type="text"] { flex-grow: 1; border-radius: 20px; padding: 10px 16px; border: 1px solid #ccc; outline: none; transition: border-color 0.2s; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include("admin_sidebar.php"); ?>
        <div class="col-md-3 sidebar-scroll border-end">
            <h5 class="mt-2 mb-3 fw-bold text-center" style="color: #5e35b1;">
                <i class="bi bi-list-ul me-2"></i> รายการสนทนา
            </h5>

            <form class="mb-3 d-flex flex-column gap-2" id="sidebarFilterForm">
                <input type="text" class="form-control" name="search" id="sidebarSearchInput" placeholder="ค้นหาชื่อกิจกรรม...">
                <select class="form-select" name="type" id="sidebarTypeFilter">
                    <option value="">-- ทุกประเภท --</option>
                    <option value="LED">LED</option>
                    <option value="Vinyl">Vinyl</option>
                </select>
            </form>

            <div class="list-group mt-3" id="booking-list-container">
                <?php while ($b = $booking_list_stmt->fetch_assoc()):
                    $unread_count = $b['unread_count'] ?? 0;
                ?>
                    <a href="admin_chat_reply.php?booking_id=<?= $b['id'] ?>" data-booking-id="<?= $b['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= ($b['id'] == $booking_id) ? 'active' : '' ?>">
                        <div class="d-flex align-items-center" style="overflow: hidden;">
                        <i class="bi bi-chat-left-text me-3"></i>
                        <span class="fw-semibold text-truncate">
                            <?php if (!empty($b['type'])): ?>
                                <small class="text-muted me-1">(<?= htmlspecialchars($b['type']) ?>)</small>
                            <?php endif; ?>
                            <?= htmlspecialchars($b['title']) ?: '(ไม่มีชื่อกิจกรรม)' ?>
                        </span>
                        </div>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger rounded-pill unread-badge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <main class="col-md-7 ms-sm-auto col-lg-7 content-area">
            <div class="chat-area-container">
                <?php if ($booking_id): ?>
                <h5 class="mb-3">
                    การสนทนา: <span style="color: #7e57c2"><?= htmlspecialchars($activity_title) ?></span>
                    <?php if ($sign_type): ?>
                        <small class="text-muted ms-2">(<?= htmlspecialchars($sign_type) ?>)</small>
                    <?php endif; ?>
                    <small class="text-muted ms-2">(Booking ID: <?= $booking_id ?>)</small>
                </h5>
                <div class="chat-messages" id="chatScroll">
                    <?php 
                    $last_message_date = null; 
                    while ($row = $messages->fetch_assoc()): 
                        $current_message_date = date('Y-m-d', strtotime($row['created_at']));
                        if ($current_message_date !== $last_message_date) {
                            echo '<div class="chat-date-divider"><span>' . format_chat_date($row['created_at']) . '</span></div>';
                            $last_message_date = $current_message_date;
                        }
                        $is_self = ($row['sender'] === 'admin');
                        $is_read_by_user = ($is_self && $row['is_read'] == 1); 
                    ?>
                        <?php if ($is_self): ?>
                            <div class="d-flex justify-content-end align-items-end mb-2">
                                <div class="read-receipt" data-message-id="<?= $row['id'] ?>">
                                    <?php if ($is_read_by_user): ?>อ่านแล้ว<?php endif; ?>
                                </div>
                                <div class="message-box from-me">
                                    <?= nl2br($row['message']) // Admin messages can have HTML ?>
                                    <div class="message-time"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-start mb-2">
                                <div class="message-box from-other">
                                    <?= nl2br(htmlspecialchars($row['message'])) // User messages are sanitized ?>
                                    <div class="message-time"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <div id="typingBubble"></div>
                </div>
                <div id="typingStatus" class="text-muted small mt-2" style="min-height: 22px;"></div>
                <form method="POST" action="submit_message_admin.php" class="d-flex gap-2 mt-3" id="chatForm">
                    <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                    <input type="text" name="message" class="form-control" placeholder="พิมพ์ข้อความ..." required autofocus autocomplete="off">
                    <button type="submit" class="btn btn-primary rounded-circle" style="width: 44px; height: 44px;"><i class="bi bi-send-fill"></i></button>
                </form>
                <?php else: ?>
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center text-muted">
                            <i class="bi bi-chat-square-dots" style="font-size: 4rem;"></i>
                            <p class="mt-3">กรุณาเลือกรายการสนทนาจากทางซ้าย</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script>
    const chatScroll = document.getElementById("chatScroll");
    if (chatScroll) chatScroll.scrollTop = chatScroll.scrollHeight;

    const socket = io("http://10.88.88.171:3000");
    const chatForm = document.getElementById("chatForm");
    const input = chatForm?.querySelector("input[name='message']");
    const typingStatus = document.getElementById("typingStatus");
    const bookingId = <?= json_encode($booking_id) ?>;
    const senderId = <?= json_encode($sender_id) ?>;

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    function updateSidebar() {
        const sidebarContainer = document.getElementById('booking-list-container');
        if (sidebarContainer) {
            fetch(`get_admin_sidebar.php?current_booking_id=${bookingId}`)
                .then(response => response.ok ? response.text() : Promise.reject('Failed to load sidebar'))
                .then(html => { sidebarContainer.innerHTML = html; })
                .catch(error => console.error('Error updating admin sidebar:', error));
        }
    }

    socket.on('connect', () => {
        if (bookingId > 0) {
            socket.emit('join_room', bookingId);
            socket.emit("mark_as_read", { booking_id: bookingId, sender: 'admin' });
            updateSidebar();
        }
    });

    if(chatForm) {
        chatForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const message = input.value.trim();
            if (message !== "") {
                const postData = new URLSearchParams({ booking_id: bookingId, message: message });
                fetch("submit_message_admin.php", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: postData });
                
                socket.emit("send_message", {
                    booking_id: bookingId, sender: "admin", sender_id: senderId,
                    message: message, created_at: new Date().toISOString()
                });

                input.value = "";
                socket.emit("stop_typing", { booking_id: bookingId, sender: "admin", sender_id: senderId });
                updateSidebar();
            }
        });
        
        let typingTimeout;
        input.addEventListener("input", () => {
            socket.emit("typing", { booking_id: bookingId, sender: "admin", sender_id: senderId });
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                socket.emit("stop_typing", { booking_id: bookingId, sender: "admin", sender_id: senderId });
            }, 2000);
        });
    }

    socket.on("receive_message", (data) => {
        if (data.booking_id == bookingId) {
            const is_self = data.sender === "admin";
            const container = document.createElement("div");
            
            if(is_self) {
                container.className = "d-flex justify-content-end align-items-end mb-2";
                container.innerHTML = `<div class="read-receipt"></div><div class="message-box from-me">${escapeHTML(data.message).replace(/\n/g, '<br>')}<div class="message-time">${new Date(data.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div></div>`;
            } else {
                container.className = "d-flex justify-content-start mb-2";
                container.innerHTML = `<div class="message-box from-other">${escapeHTML(data.message).replace(/\n/g, '<br>')}<div class="message-time">${new Date(data.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div></div>`;
            }
            const typingBubble = document.getElementById('typingBubble');
            chatScroll.insertBefore(container, typingBubble);
            chatScroll.scrollTop = chatScroll.scrollHeight;
            if (typingStatus) typingStatus.innerHTML = "";

            if (!is_self) {
                socket.emit("mark_as_read", { booking_id: bookingId, sender: 'admin' });
                const formData = new URLSearchParams({ booking_id: bookingId, role: 'admin' });
                fetch('mark_as_read_ajax.php', { method: 'POST', body: formData });
            }
        }
    });

    socket.on("messages_have_been_read", (data) => {
        if (data.booking_id == bookingId) {
            document.querySelectorAll(".read-receipt").forEach(div => {
                if (div.innerHTML.trim() === "") {
                    div.innerHTML = `อ่านแล้ว`;
                }
            });
        }
    });

    socket.on("show_typing", (data) => {
        if (data.booking_id == bookingId && data.sender !== 'admin') {
            typingStatus.innerHTML = `<div class="d-flex align-items-center"><div class="spinner-grow spinner-grow-sm text-secondary me-2" role="status"></div><span>ผู้ใช้กำลังพิมพ์...</span></div>`;
        }
    });

    socket.on("hide_typing", (data) => {
        if (data.booking_id == bookingId) {
            typingStatus.innerHTML = "";
        }
    });

    socket.on("new_unread_message", (data) => {
        updateSidebar();
    });

    // ฟังก์ชันโหลดรายการแชทใหม่ด้วย filter
    function updateSidebarWithFilters() {
        const sidebarContainer = document.getElementById('booking-list-container');
        const search = document.getElementById('sidebarSearchInput').value;
        const type = document.getElementById('sidebarTypeFilter').value;

        const params = new URLSearchParams({
            current_booking_id: bookingId,
            search: search,
            type: type
        });

        fetch('get_admin_sidebar.php?' + params.toString())
            .then(response => response.text())
            .then(html => {
                sidebarContainer.innerHTML = html;
            });
    }

    // โหลดใหม่เมื่อพิมพ์หรือเปลี่ยนประเภท
    document.getElementById('sidebarSearchInput').addEventListener('input', () => {
        clearTimeout(window._searchTimeout);
        window._searchTimeout = setTimeout(updateSidebarWithFilters, 300); // debounce
    });

    document.getElementById('sidebarTypeFilter').addEventListener('change', () => {
        updateSidebarWithFilters();
    });
</script>
</body>
</html>