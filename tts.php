<?php
// tts.php - Proxy lấy giọng đọc Google
if (isset($_GET['text'])) {
    $text = $_GET['text'];
    // Mã hóa văn bản để gửi qua URL
    $encoded_text = urlencode($text);
    
    // URL của Google TTS
    $url = "https://translate.google.com/translate_tts?ie=UTF-8&q={$encoded_text}&tl=vi&client=tw-ob";
    
    // Giả lập trình duyệt để Google không chặn
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                        "Referer: https://translate.google.com/\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    
    // Lấy nội dung file MP3 từ Google
    $mp3 = @file_get_contents($url, false, $context);
    
    if ($mp3) {
        // Trả về cho trình duyệt phát
        header('Content-Type: audio/mpeg');
        // Cho phép cache để lần sau đọc nhanh hơn
        header("Cache-Control: public, max-age=31536000"); 
        echo $mp3;
    } else {
        http_response_code(404);
    }
}
?>