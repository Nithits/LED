<?php
// File: get_user_sidebar.php (แก้ไขแล้ว)
session_name('user_session');
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Access denied");
}

$user_id = $_SESSION['user_id'];
$current_booking_id = $_GET['current_booking_id'] ?? 0;

// ✅ แก้ไขส่วน ORDER BY
$booking_stmt = $conn->prepare("
    SELECT b.id, b.title,
           MAX(c.created_at) AS last_message_time,
           SUM(CASE WHEN c.sender != 'user' AND c.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM bookings b
    LEFT JOIN chat_messages c ON b.id = c.booking_id
    WHERE b.user_id = ?
    GROUP BY b.id
    ORDER BY 
        (CASE WHEN SUM(CASE WHEN c.sender != 'user' AND c.is_read = 0 THEN 1 ELSE 0 END) > 0 THEN 0 ELSE 1 END),
        last_message_time DESC,
        b.id DESC
");
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$bookings = $booking_stmt->get_result();

if ($bookings) {
    while ($b = $bookings->fetch_assoc()) {
        $unread_count = $b['unread_count'] ?? 0;
        $is_active = ($b['id'] == $current_booking_id) ? 'active' : '';
        $title = htmlspecialchars($b['title'] ?: '(ไม่มีชื่อ)');

        echo <<<HTML
        <a href="chat.php?booking_id={$b['id']}" 
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {$is_active}"
           data-booking-id="{$b['id']}">
            <span class="text-truncate">{$title}</span>
HTML;
        if ($unread_count > 0) {
            echo "<span class=\"badge bg-danger rounded-pill unread-badge\">{$unread_count}</span>";
        }
        echo "</a>";
    }
}
?>