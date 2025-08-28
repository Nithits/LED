<?php
session_name('user_session');
session_start();
include("db.php");

// --- ดึงข้อมูลลิงก์แบบสอบถาม ---
$show_questionnaire_redirect = false;
if (isset($_SESSION['booking_just_completed']) && $_SESSION['booking_just_completed'] === true) {
    $show_questionnaire_redirect = true;
    // ฉีกตั๋วทิ้งทันที เพื่อไม่ให้แสดงผลอีกในครั้งต่อไป
    unset($_SESSION['booking_just_completed']);
}

// --- ดึงข้อมูลลิงก์แบบสอบถาม (จะดึงข้อมูลเสมอ แต่จะใช้ก็ต่อเมื่อ $show_questionnaire_redirect เป็น true) ---
$questionnaire_link = null;
if ($show_questionnaire_redirect) { // เพิ่มเงื่อนไขให้ดึงข้อมูลเฉพาะเมื่อจำเป็น
    $sql_questionnaire = "SELECT form_link FROM questionnaire_settings WHERE id = 1 AND is_active = 1";
    $result_questionnaire = $conn->query($sql_questionnaire);

    if ($result_questionnaire && $result_questionnaire->num_rows > 0) {
        $row_questionnaire = $result_questionnaire->fetch_assoc();
        if (!empty($row_questionnaire['form_link'])) {
            $questionnaire_link = $row_questionnaire['form_link'];
        }
    }
}

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
        $thai_months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        $day = $message_time->format('j');
        $month = $thai_months[(int)$message_time->format('n') - 1];
        $year = (int)$message_time->format('Y') + 543;
        return "วันที่ $day $month $year";
    }
}

$booking_id = $_GET['booking_id'] ?? 0;
if (!$booking_id || !isset($_SESSION['user_id'])) {
    header("Location: pages/page_login.php");
    exit;
}

$sender = 'user';
$sender_id = $_SESSION['user_id'];

$mark_read_stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE booking_id = ? AND sender = 'admin' AND is_read = 0");
$mark_read_stmt->bind_param("i", $booking_id);
$mark_read_stmt->execute();

$booking_info_stmt = $conn->prepare("SELECT title FROM bookings WHERE id = ?");
$booking_info_stmt->bind_param("i", $booking_id);
$booking_info_stmt->execute();
$booking_info = $booking_info_stmt->get_result()->fetch_assoc();
$activity_title = $booking_info['title'] ?? 'ไม่ทราบกิจกรรม';

$stmt = $conn->prepare("SELECT * FROM chat_messages WHERE booking_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$messages = $stmt->get_result();

$user_id = $_SESSION['user_id'];
$booking_stmt = $conn->prepare("SELECT b.id, b.title, MAX(c.created_at) AS last_message_time, SUM(CASE WHEN c.sender != 'user' AND c.is_read = 0 THEN 1 ELSE 0 END) AS unread_count FROM bookings b LEFT JOIN chat_messages c ON b.id = c.booking_id WHERE b.user_id = ? GROUP BY b.id ORDER BY unread_count DESC, last_message_time DESC");
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$bookings = $booking_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>การสนทนา - <?= htmlspecialchars($activity_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/sysstyle.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/iconmsu.ico">
<style>
    body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
    .chat-area-container { background-color: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; flex-direction: column; height: calc(100vh - 120px); }
    .chat-messages { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; padding-right: 10px; }
    .message-box { max-width: 70%; padding: 10px 15px; border-radius: 18px; line-height: 1.5; word-wrap: break-word; }
    .from-me { background-color: #7e57c2; color: #fff; border-bottom-right-radius: 0; }
    .from-other { background-color: #e9ecef; color: #333; border-bottom-left-radius: 0; }
    .message-time { font-size: 0.75rem; color: #a79ab1; text-align: right; margin-top: 5px; }
    .from-other .message-time { color: #6c757d; }
    .list-group-item.active { background-color: #7e57c2; border-color: #7e57c2; color: white; }
    .sidebar-scroll { height: calc(100vh - 120px); overflow-y: auto; }
    .read-receipt { font-size: 0.75rem; color: #6c757d; margin-right: 8px; white-space: nowrap; width: 45px; text-align: right; }
    .chat-date-divider { text-align: center; margin: 20px 0; color: #888; font-size: 0.8rem; font-weight: 500; display: flex; align-items: center; }
    .chat-date-divider::before, .chat-date-divider::after { content: ''; flex-grow: 1; height: 1px; background-color: #e0e0e0; margin: 0 10px; }
</style>
</head>
<body>

<?php include_once(__DIR__ . '/components/top-navbar.php'); ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-3 border-end sidebar-scroll">
            <h5 class="mb-3"><a href="pages/page2.php" class="text-decoration-none text-dark"><i class="bi bi-chevron-left me-2"></i> รายการจองของฉัน</a></h5>
            <div class="list-group list-group-flush" id="user-booking-list-container">
                <?php while ($b = $bookings->fetch_assoc()): ?>
                    <a href="chat.php?booking_id=<?= $b['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= ($b['id'] == $booking_id) ? 'active' : '' ?>" data-booking-id="<?= $b['id'] ?>">
                        <span class="text-truncate"><?= htmlspecialchars($b['title']) ?: '(ไม่มีชื่อ)' ?></span>
                        <?php if($b['unread_count'] > 0): ?><span class="badge bg-danger rounded-pill unread-badge"><?= $b['unread_count'] ?></span><?php endif; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="col-md-8">
            <div class="chat-area-container">
                <h5 class="mb-3 border-bottom pb-2">การสนทนา: <span style="color: #7e57c2"><?= htmlspecialchars($activity_title) ?></span></h5>
                <div class="chat-messages" id="chatScroll">
                    <?php 
                    $last_message_date = null;
                    while ($row = $messages->fetch_assoc()):
                        $current_message_date = date('Y-m-d', strtotime($row['created_at']));
                        if ($current_message_date !== $last_message_date) {
                            echo '<div class="chat-date-divider"><span>' . format_chat_date($row['created_at']) . '</span></div>';
                            $last_message_date = $current_message_date;
                        }
                        $is_self = ($row['sender'] === 'user');
                    ?>
                        <?php if ($is_self): ?>
                            <div class="d-flex justify-content-end align-items-end mb-2">
                                <div class="read-receipt" data-message-id="<?= $row['id'] ?>">
                                    <?php if ($row['is_read'] == 1): ?>อ่านแล้ว<?php endif; ?>
                                </div>
                                <div class="message-box from-me">
                                    <?= nl2br(htmlspecialchars($row['message'])) ?>
                                    <div class="message-time"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-start mb-2">
                                <div class="message-box from-other">
                                    <?= nl2br($row['message']) ?>
                                    <div class="message-time"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <div id="typingBubble"></div>
                </div>

                <div id="typingStatus" class="text-muted small mt-2" style="min-height: 20px;"></div>

                <form method="POST" action="submit_message_user.php" class="d-flex gap-2 mt-3" id="chatForm">
                    <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                    <input type="text" name="message" class="form-control" placeholder="พิมพ์ข้อความ..." required autofocus autocomplete="off">
                    <button type="submit" class="btn btn-primary rounded-circle" style="width: 44px; height: 44px;"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script>
        // --- สำหรับการเด้งไปหน้าแบบสอบถาม ---
    document.addEventListener('DOMContentLoaded', () => {
        // รับค่า "ตั๋ว" จาก PHP
        const shouldRedirect = <?= json_encode($show_questionnaire_redirect) ?>;
        const questionnaireLink = <?= json_encode($questionnaire_link) ?>;

        // ทำงานเฉพาะเมื่อมี "ตั๋ว" และมี "ลิงก์" เท่านั้น
        if (shouldRedirect && questionnaireLink) {
            const alertBox = document.createElement('div');
            alertBox.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #198754; color: white; padding: 15px; border-radius: 8px; z-index: 1060; box-shadow: 0 4px 8px rgba(0,0,0,0.2);';
            
            let seconds = 3;
            alertBox.innerHTML = `การจองสำเร็จ! จะนำท่านไปยังหน้าแบบสอบถามในอีก <strong>${seconds}</strong> วินาที...`;
            document.body.appendChild(alertBox);

            const countdown = setInterval(() => {
                seconds--;
                alertBox.innerHTML = `การจองสำเร็จ! จะนำท่านไปยังหน้าแบบสอบถามในอีก <strong>${seconds}</strong> วินาที...`;
                if (seconds <= 0) {
                    clearInterval(countdown);
                }
            }, 1000);

            setTimeout(() => {
                window.location.href = questionnaireLink;
            }, 3000);
        }
    });

    // --- ค่าคงที่และตัวแปรหลัก ---
    const chatScroll = document.getElementById("chatScroll");
    if (chatScroll) chatScroll.scrollTop = chatScroll.scrollHeight;

    const socket = io({ path: "/socket.io/" }); // เปลี่ยนเป็น URL ของ Server จริง
    const chatForm = document.getElementById("chatForm");
    const input = chatForm.querySelector("input[name='message']");
    const typingStatus = document.getElementById("typingStatus");
    const bookingId = <?= json_encode($booking_id) ?>;
    const senderId = <?= json_encode($sender_id) ?>;
    const senderType = 'user';

    // --- ฟังก์ชันช่วยเหลือ ---
    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
    
    // ฟังก์ชันสำหรับอัปเดต Sidebar โดยการ fetch ข้อมูลใหม่ทั้งหมด (วิธีที่เสถียรที่สุด)
    function updateSidebar() {
        const sidebarContainer = document.getElementById('user-booking-list-container');
        if (sidebarContainer) {
            // ส่ง ID ของห้องที่กำลังดูอยู่ไปด้วย เพื่อให้ PHP สามารถใส่ class 'active' ได้ถูกต้อง
            fetch(`get_user_sidebar.php?current_booking_id=${bookingId}`)
                .then(response => response.ok ? response.text() : Promise.reject('Failed to load sidebar'))
                .then(html => {
                    sidebarContainer.innerHTML = html;
                })
                .catch(error => console.error('Error updating user sidebar:', error));
        }
    }

    // --- การเชื่อมต่อและเข้าห้อง ---
    socket.on('connect', () => {
        if (bookingId > 0) {
            socket.emit('join_room', bookingId);
            socket.emit("mark_as_read", { booking_id: bookingId, sender: 'user' });
            // อัปเดต Sidebar ทันทีที่เข้าหน้า เพื่อลบ Badge ที่อ่านแล้ว
            updateSidebar();
        }
    });

    // --- การส่งข้อความ ---
    chatForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const message = input.value.trim();
        if (message !== "") {
            const postData = new URLSearchParams({ booking_id: bookingId, message: message });
            fetch("submit_message_user.php", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: postData });
            
            socket.emit("send_message", {
                booking_id: bookingId, sender: senderType, sender_id: senderId,
                message: message, created_at: new Date().toISOString()
            });
            input.value = "";
            socket.emit("stop_typing", { booking_id: bookingId, sender: senderType, sender_id: senderId });
            
            // อัปเดต Sidebar ทันทีหลังส่งข้อความ เพื่อย้ายแชทขึ้นบนสุด
            updateSidebar();
        }
    });

    let typingTimeout;
    input.addEventListener("input", () => {
        socket.emit("typing", { booking_id: bookingId, sender: senderType, sender_id: senderId });
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            socket.emit("stop_typing", { booking_id: bookingId, sender: senderType, sender_id: senderId });
        }, 1500);
    });

    // --- การรับข้อมูล Real-time ---
    socket.on("receive_message", (data) => {
        if (data.booking_id == bookingId) {
            const is_self = data.sender === senderType && data.sender_id == senderId;
            const container = document.createElement("div");

            if (is_self) {
                container.className = "d-flex justify-content-end align-items-end mb-2";
                container.innerHTML = `
                    <div class="read-receipt"></div>
                    <div class="message-box from-me">
                        ${escapeHTML(data.message).replace(/\n/g, '<br>')}
                        <div class="message-time">${new Date(data.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}</div>
                    </div>`;
            } else { // ข้อความจาก Admin
                container.className = "d-flex justify-content-start mb-2";
                container.innerHTML = `
                    <div class="message-box from-other">
                        ${data.message.replace(/\n/g, '<br>')}
                        <div class="message-time">${new Date(data.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}</div>
                    </div>`;
            }
            const typingBubble = document.getElementById('typingBubble');
            chatScroll.insertBefore(container, typingBubble);
            chatScroll.scrollTop = chatScroll.scrollHeight;
            if (typingStatus) typingStatus.innerText = "";

            if (!is_self) {
                socket.emit("mark_as_read", { booking_id: bookingId, sender: 'user' });
                const formData = new URLSearchParams({ booking_id: bookingId, role: 'user' });
                fetch('mark_as_read_ajax.php', { method: 'POST', body: formData });
            }
        }
    });

    socket.on("messages_have_been_read", (data) => {
        if (data.booking_id == bookingId) {
            document.querySelectorAll(".read-receipt").forEach(div => {
                if(div.textContent.trim() === "") {
                    div.textContent = "อ่านแล้ว";
                }
            });
        }
    });

    socket.on("show_typing", (data) => {
        if (data.booking_id == bookingId && data.sender === 'admin') {
            if (typingStatus) typingStatus.innerText = "แอดมินกำลังพิมพ์...";
        }
    });

    socket.on("hide_typing", (data) => {
        if (data.booking_id == bookingId) {
            if (typingStatus) typingStatus.innerText = "";
        }
    });

    // ✅✅✅ ส่วนที่สำคัญที่สุดสำหรับแก้ปัญหานี้ ✅✅✅
    socket.on("new_unread_message", (data) => {
        // เมื่อมีข้อความใหม่ในห้องอื่น ให้โหลด Sidebar ใหม่ทั้งหมด
        // ไม่ต้องมีเงื่อนไข if ใดๆ ทั้งสิ้น เพื่อให้ทำงานทุกครั้งที่ได้รับสัญญาณ
        updateSidebar();
    });
</script>
</body>
</html>