<?php
// ตรวจสอบการประกาศฟังก์ชันก่อน
if (!function_exists('dateThaiShort')) {
  function dateThaiShort($date) {
    $months_th = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];

    // ตรวจสอบว่า $date เป็น array หรือไม่
    if (is_array($date)) {
      $date = $date[0]; // เลือกวันที่แรกใน array
    }

    $timestamp = strtotime($date);
    $day = date("j", $timestamp);
    $month = (int)date("n", $timestamp);
    return "$day " . $months_th[$month];
  }
}

if (!function_exists('thaiMonthYearEN')) {
  function thaiMonthYearEN($date) {
    $months_th = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $timestamp = strtotime($date);
    $month = (int)date('n', $timestamp);
    $year = (int)date('Y', $timestamp);
    return $months_th[$month] . " " . $year;
  }
}
?>
