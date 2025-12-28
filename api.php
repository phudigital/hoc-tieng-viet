<?php
// api.php - Backend xử lý tìm kiếm Tiếng Việt chuẩn (Version 3.0)
header('Content-Type: application/json; charset=utf-8');

// 1. Load dữ liệu
$dictionary = include('data.php');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// --- HÀM 1: CHỈ BỎ DẤU THANH (Giữ lại â, ă, ô, ơ, ê, ư...) ---
// Giúp so sánh: "chuồn" == "uôn" (vì cùng gốc 'uôn')
function removeTone($str) {
    $str = mb_strtolower($str, 'UTF-8');
    // a
    $str = preg_replace("/(à|á|ạ|ả|ã)/", "a", $str);
    // ă
    $str = preg_replace("/(ằ|ắ|ặ|ẳ|ẵ)/", "ă", $str);
    // â
    $str = preg_replace("/(ầ|ấ|ậ|ẩ|ẫ)/", "â", $str);
    // e
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ)/", "e", $str);
    // ê
    $str = preg_replace("/(ề|ế|ệ|ể|ễ)/", "ê", $str);
    // i
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
    // o
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ)/", "o", $str);
    // ô
    $str = preg_replace("/(ồ|ố|ộ|ổ|ỗ)/", "ô", $str);
    // ơ
    $str = preg_replace("/(ờ|ớ|ợ|ở|ỡ)/", "ơ", $str);
    // u
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ)/", "u", $str);
    // ư
    $str = preg_replace("/(ừ|ứ|ự|ử|ữ)/", "ư", $str);
    // y
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
    // đ (Giữ nguyên đ hoặc chuyển thành d tuỳ nhu cầu, ở đây giữ nguyên để tìm chính xác hơn)
    // $str = preg_replace("/(đ)/", "d", $str); 
    
    return $str;
}

// --- HÀM 2: BỎ HẾT DẤU (Về dạng a-z) ---
// Giúp so sánh mở rộng: "uôn" == "uon"
function removeAllAccents($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
    $str = preg_replace("/(đ)/", "d", $str);
    return $str;
}

$response = [
    'found' => false,
    'keyword' => $keyword,
    'data' => [
        'words' => [],
        'sentences' => []
    ]
];

if ($action == 'search' && $keyword !== '') {
    $keywordRaw = mb_strtolower($keyword, 'UTF-8');
    $keywordNoTone = removeTone($keywordRaw);         // uôn -> uôn (bỏ dấu sắc/huyền)
    $keywordNoAccents = removeAllAccents($keywordRaw); // uôn -> uon

    // XÁC ĐỊNH CHẾ ĐỘ TÌM KIẾM
    // Nếu "uôn" == "uon" -> Người dùng nhập không dấu -> Tìm Mở Rộng
    // Nếu "uôn" != "uon" -> Người dùng nhập có dấu (ô, ơ, ă...) -> Tìm Chính Xác Vần
    $isBroadMode = ($keywordNoTone === $keywordNoAccents); 

    $foundWords = [];
    $foundSentences = [];

    foreach ($dictionary as $lessonKey => $content) {
        // --- 1. TÌM TRONG TỪ VỰNG (QUAN TRỌNG NHẤT) ---
        if (isset($content['words'])) {
            foreach ($content['words'] as $word) {
                $wordRaw = mb_strtolower($word, 'UTF-8');
                $wordNoTone = removeTone($wordRaw);         // chuồn chuồn -> chuôn chuôn
                $wordNoAccents = removeAllAccents($wordRaw); // chuồn chuồn -> chuon chuon
                
                $isMatch = false;

                if ($isBroadMode) {
                    // Chế độ Mở Rộng (nhập 'uon'):
                    // Tìm tất cả uôn, ươn...
                    if (strpos($wordNoAccents, $keywordNoAccents) !== false) {
                        $isMatch = true;
                    }
                } else {
                    // Chế độ Chính Xác (nhập 'uôn'):
                    // So sánh dạng đã bỏ dấu thanh: 'chuôn chuôn' chứa 'uôn'? -> CÓ
                    // 'vươn' chứa 'uôn'? -> KHÔNG (vì ươ != uô)
                    if (strpos($wordNoTone, $keywordNoTone) !== false) {
                        $isMatch = true;
                    }
                }

                if ($isMatch) {
                    if (!in_array($word, $foundWords)) {
                        $foundWords[] = $word;
                        $response['data']['words'][] = [
                            'text' => $word,
                            'topic' => isset($content['topic']) ? $content['topic'] : ''
                        ];
                    }
                }
            }
        }

        // --- 2. TÌM TRONG CÂU (Dựa vào Key bài học) ---
        // Chỉ hiện câu nếu Key bài học khớp với từ khóa
        $lessonKeyNoTone = removeTone($lessonKey);
        $lessonKeyNoAccents = removeAllAccents($lessonKey);
        
        $isKeyMatch = false;
        if ($isBroadMode) {
            if (strpos($lessonKeyNoAccents, $keywordNoAccents) !== false) $isKeyMatch = true;
        } else {
            if (strpos($lessonKeyNoTone, $keywordNoTone) !== false) $isKeyMatch = true;
        }

        if ($isKeyMatch && isset($content['sentences'])) {
            foreach ($content['sentences'] as $sentence) {
                if (!in_array($sentence, $foundSentences)) {
                    $foundSentences[] = $sentence;
                    $response['data']['sentences'][] = $sentence;
                }
            }
        }
    }

    // Sắp xếp: Ưu tiên từ ngắn, từ bắt đầu bằng từ khóa
    if (!empty($response['data']['words'])) {
        $response['found'] = true;
        usort($response['data']['words'], function($a, $b) use ($keywordNoTone) {
            $textA = removeTone($a['text']);
            $textB = removeTone($b['text']);
            
            // Ưu tiên 1: Độ dài từ (ngắn xếp trước)
            $lenDiff = mb_strlen($textA) - mb_strlen($textB);
            if ($lenDiff !== 0) return $lenDiff;
            
            return 0;
        });
    }
}

echo json_encode($response);
?>