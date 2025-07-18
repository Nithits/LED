<?php
// เปิดการแสดงข้อผิดพลาดของ mysqli (ช่วยตอน debug)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$username = "root";
$password = "2531s";
$database = "signboard_db"; // ชื่อฐานข้อมูลของคุณ

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $username, $password, $database);

// กำหนด charset ให้รองรับภาษาไทย
$conn->set_charset("utf8mb4");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
