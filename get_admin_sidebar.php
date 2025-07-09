<?php
// File: get_admin_sidebar.php (แก้ไขแล้ว)
session_name('admin_session');
session_start();
include("db.php");

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit("Access denied");
}

$current_booking_id = $_GET['current_booking_id'] ?? 0;
$search_query = $conn->real_escape_string($_GET['search'] ?? '');
$filter_type = $conn->real_escape_string($_GET['type'] ?? '');

// สร้างเงื่อนไขเพิ่มเติม
$where_clauses = [];
if ($search_query !== '') {
    $where_clauses[] = "b.title LIKE '%$search_query%'";
}
if ($filter_type !== '') {
    $where_clauses[] = "sb.type = '$filter_type'";
}
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$booking_list_stmt = $conn->query("
    SELECT b.id, b.title, sb.type,
           MAX(c.created_at) AS last_message_time,
           SUM(CASE WHEN c.sender != 'admin' AND c.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM bookings b
    LEFT JOIN chat_messages c ON b.id = c.booking_id
    LEFT JOIN sign_boards sb ON b.sign_board_id = sb.id
    $where_sql
    GROUP BY b.id, sb.type
    ORDER BY 
        (CASE WHEN SUM(CASE WHEN c.sender != 'admin' AND c.is_read = 0 THEN 1 ELSE 0 END) > 0 THEN 0 ELSE 1 END),
        last_message_time DESC,
        b.id DESC
");

if ($booking_list_stmt) {
    while ($b = $booking_list_stmt->fetch_assoc()) {
        $unread_count = $b['unread_count'] ?? 0;
        $is_active = ($b['id'] == $current_booking_id) ? 'active' : '';
        $title = htmlspecialchars($b['title'] ?: '(ไม่มีชื่อกิจกรรม)');
        $type = htmlspecialchars($b['type'] ?? '');
        $display_title = $type ? "<small class='text-muted me-1'>($type)</small> $title" : $title;

        echo <<<HTML
        <a href="admin_chat_reply.php?booking_id={$b['id']}" 
           data-booking-id="{$b['id']}"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {$is_active}">
            <div class="d-flex align-items-center" style="overflow: hidden;">
                <i class="bi bi-chat-left-text me-3"></i>
                <span class="fw-semibold text-truncate">{$display_title}</span>
            </div>
HTML;
        if ($unread_count > 0) {
            echo "<span class=\"badge bg-danger rounded-pill unread-badge\">{$unread_count}</span>";
        }
        echo "</a>";
    }
}
?>
