<?php
// stats.php - Hệ thống đếm lượt truy cập (IP Based)
header('Content-Type: application/json; charset=utf-8');

$STATS_FILE = 'stats.json';

// 1. Lấy IP người dùng (Hỗ trợ cả Cloudflare/Proxy)
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

$ip = getUserIP();
$now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
$todayDate = $now->format('Y-m-d');
$thisMonth = $now->format('Y-m');
$thisYear  = $now->format('Y');

// 2. Đọc dữ liệu cũ
$data = [];
if (file_exists($STATS_FILE)) {
    $data = json_decode(file_get_contents($STATS_FILE), true);
}

// Khởi tạo cấu trúc nếu file rỗng
if (!$data) {
    $data = [
        "last_date" => "",
        "today_ips" => [],
        "counts" => ["day" => 0, "month" => 0, "year" => 0, "total" => 0],
        "current_month" => "",
        "current_year" => ""
    ];
}

// 3. Xử lý Reset theo thời gian
// Nếu sang ngày mới -> Reset bộ đếm ngày và danh sách IP
if ($data['last_date'] !== $todayDate) {
    $data['counts']['day'] = 0;
    $data['today_ips'] = []; // Xóa danh sách IP của ngày hôm qua
    $data['last_date'] = $todayDate;
}

// Nếu sang tháng mới -> Reset bộ đếm tháng
if ($data['current_month'] !== $thisMonth) {
    $data['counts']['month'] = 0;
    $data['current_month'] = $thisMonth;
}

// Nếu sang năm mới -> Reset bộ đếm năm
if ($data['current_year'] !== $thisYear) {
    $data['counts']['year'] = 0;
    $data['current_year'] = $thisYear;
}

// 4. Kiểm tra IP và Tăng đếm
// Chỉ tăng nếu IP này chưa có trong danh sách của ngày hôm nay
if (!in_array($ip, $data['today_ips'])) {
    $data['today_ips'][] = $ip;
    $data['counts']['day']++;
    $data['counts']['month']++;
    $data['counts']['year']++;
    $data['counts']['total']++;
    
    // Lưu lại file (Sử dụng LOCK_EX để an toàn khi nhiều người truy cập cùng lúc)
    file_put_contents($STATS_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

// 5. Trả về kết quả để hiển thị
echo json_encode($data['counts']);
?>