<?php
// Cấu hình thời gian và phiên bản
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Mỗi lần bạn gen code xong, bạn có thể sửa số này trong file index.php
$version = "1.0.1"; 

// Lấy thời gian hiện tại
$current_time = date('H:i - d/m/Y');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bé Học Tiếng Việt 1 - Chân Trời Sáng Tạo</title>
    <!-- Thêm ?v=time() để tránh cache CSS -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="container">
        <header>
            <span class="logo-icon">🐝</span>
            <h1>Bé Học Tiếng Việt</h1>
            <p class="subtitle">Sách Chân Trời Sáng Tạo (Lớp 1)</p>
        </header>

        <div class="search-box">
            <input type="text" id="keyword" placeholder="Nhập vần (ví dụ: b, ang, kh...)" autocomplete="off">
            <button onclick="learnWords()">Học bài</button>
        </div>

        <!-- Khu vực thông báo -->
        <div id="message-area" class="message">
            👋 Chào bé! Bé hãy nhập chữ cái muốn học vào ô bên trên nhé.
        </div>

        <!-- Kết quả Từ vựng -->
        <h3 id="title-words" class="section-title">✨ Từ vựng</h3>
        <div id="words-list" class="result-grid"></div>

        <!-- Kết quả Câu -->
        <h3 id="title-sentences" class="section-title">📖 Tập đọc câu</h3>
        <div id="sentences-list" class="result-grid"></div>

        <!-- Footer thông tin phiên bản -->
        <div class="app-footer">
            <span>Cập nhật: <strong><?php echo $current_time; ?></strong></span>
            <span>Phiên bản: <span class="badge-version">v<?php echo $version; ?></span></span>
        </div>
    </div>

    <!-- Nhúng Script -->
    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>