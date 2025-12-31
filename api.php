<?php
// api.php - Version 6.0 (Separated Logic for Async Loading)
header('Content-Type: application/json; charset=utf-8');

// --- CẤU HÌNH ---
// 1. Kiểm tra xem file config có tồn tại không
if (file_exists('config.php')) {
    $config = include('config.php');
    $DEEPSEEK_API_KEY = $config['deepseek_key'];
    $ADMIN_PASS = $config['admin_pass'];
} else {
    // Trường hợp lỡ quên tạo file config trên server
    // Hoặc lấy từ biến môi trường (Environment Variable) nếu deploy chuyên nghiệp
    $DEEPSEEK_API_KEY = getenv('DEEPSEEK_API_KEY');
    $ADMIN_PASS = getenv('ADMIN_PASS');
}

// ... Các cấu hình khác ...
$AI_DB_FILE = 'data-ai.json';
$USAGE_FILE = 'usage.json';
$MAX_FREE_LIMIT = 15;   // Giới hạn miễn phí mỗi IP

// --- NHẬN DỮ LIỆU ---
$action = isset($_GET['action']) ? $_GET['action'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$userPass = isset($_GET['password']) ? $_GET['password'] : '';
$userIP = $_SERVER['REMOTE_ADDR'];

$dictionary = include('data.php');

// --- CÁC HÀM XỬ LÝ (Giữ nguyên) ---
function removeTone($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace("/(à|á|ạ|ả|ã)/", "a", $str);
    $str = preg_replace("/(ằ|ắ|ặ|ẳ|ẵ)/", "ă", $str);
    $str = preg_replace("/(ầ|ấ|ậ|ẩ|ẫ)/", "â", $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ)/", "e", $str);
    $str = preg_replace("/(ề|ế|ệ|ể|ễ)/", "ê", $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ)/", "o", $str);
    $str = preg_replace("/(ồ|ố|ộ|ổ|ỗ)/", "ô", $str);
    $str = preg_replace("/(ờ|ớ|ợ|ở|ỡ)/", "ơ", $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ)/", "u", $str);
    $str = preg_replace("/(ừ|ứ|ự|ử|ữ)/", "ư", $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
    return $str;
}
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
function checkUsageLimit($ip, $password, $limit, $adminPass, $file) {
    if ($password === $adminPass) return ['allowed' => true, 'is_vip' => true];
    $usageData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $currentCount = $usageData[$ip] ?? 0;
    if ($currentCount >= $limit) return ['allowed' => false];
    return ['allowed' => true, 'is_vip' => false];
}
function incrementUsage($ip, $file) {
    $usageData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    if (!isset($usageData[$ip])) $usageData[$ip] = 0;
    $usageData[$ip]++;
    file_put_contents($file, json_encode($usageData));
}
function callDeepSeekAI($keyword, $apiKey) {
    $url = "https://api.deepseek.com/chat/completions";
    $userPrompt = "Đóng vai giáo viên soạn sách Tiếng Việt lớp 1 (Bộ Chân trời sáng tạo).
    Từ khóa cần học: '$keyword'.
    1. Tìm 6 TỪ GHÉP chứa '$keyword': Ưu tiên tên con vật, cây cối, đồ vật, hoạt động hàng ngày. Tránh từ Hán Việt khó.
    2. Viết 5 câu văn ngắn gọn, ngây thơ, dễ đánh vần, mỗi câu chứa từ có vần '$keyword'.
    3. Output JSON only: {\"words\":[], \"sentences\":[]}";

    $data = [
        "model" => "deepseek-chat",
        "messages" => [["role" => "system", "content" => "You are a helpful JSON generator for kids education."], ["role" => "user", "content" => $userPrompt]],
        "temperature" => 0.6
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result) {
        $responseObj = json_decode($result, true);
        $content = $responseObj['choices'][0]['message']['content'] ?? '';
        $content = preg_replace('/<think>[\s\S]*?<\/think>/', '', $content);
        $cleanJson = str_replace(['```json', '```'], '', $content);
        return json_decode($cleanJson, true);
    }
    return null;
}

$response = [ 'found' => false, 'keyword' => $keyword, 'error_code' => null, 'data' => [ 'words' => [], 'sentences' => [] ] ];

if ($keyword !== '') {
    $keywordRaw = mb_strtolower($keyword, 'UTF-8');

    // --- CASE 1: TÌM TRONG SÁCH (LOCAL) ---
    if ($action == 'search') {
        $keywordNoTone = removeTone($keywordRaw);
        $keywordNoAccents = removeAllAccents($keywordRaw);
        $isBroadMode = ($keywordNoTone === $keywordNoAccents); 
        $foundLocalWords = [];
        $foundKeyMatch = false;

        // 1. Tìm theo Key
        foreach ($dictionary as $lessonKey => $content) {
            $subKeys = preg_split("/[\s,]+/", $lessonKey);
            $isMatch = false;
            foreach ($subKeys as $subKey) {
                $subKeyRaw = mb_strtolower($subKey, 'UTF-8');
                $subKeyNoTone = removeTone($subKeyRaw);
                $subKeyNoAccents = removeAllAccents($subKeyRaw);
                if ($isBroadMode) { if ($subKeyNoAccents === $keywordNoAccents) { $isMatch = true; break; } } 
                else { if ($subKeyNoTone === $keywordNoTone) { $isMatch = true; break; } }
            }

            if ($isMatch) {
                $foundKeyMatch = true;
                if (isset($content['words'])) {
                    foreach ($content['words'] as $word) {
                        if (!in_array($word, $foundLocalWords)) {
                            $foundLocalWords[] = $word;
                            $response['data']['words'][] = ['text' => $word]; // Local không có flag is_ai
                        }
                    }
                }
                if (isset($content['sentences'])) {
                    foreach ($content['sentences'] as $sentence) {
                        $response['data']['sentences'][] = $sentence;
                    }
                }
            }
        }

        // 2. Fallback tìm trong word
        if (!$foundKeyMatch) {
            foreach ($dictionary as $lessonKey => $content) {
                if (isset($content['words'])) {
                    foreach ($content['words'] as $word) {
                        $wordRaw = mb_strtolower($word, 'UTF-8');
                        $wordNoTone = removeTone($wordRaw);
                        $wordNoAccents = removeAllAccents($wordRaw);
                        $isWordMatch = false;
                        if ($isBroadMode) { if (strpos($wordNoAccents, $keywordNoAccents) !== false) $isWordMatch = true; } 
                        else { if (strpos($wordNoTone, $keywordNoTone) !== false) $isWordMatch = true; }
                        
                        if ($isWordMatch) {
                            if (!in_array($word, $foundLocalWords)) {
                                $foundLocalWords[] = $word;
                                $response['data']['words'][] = ['text' => $word];
                            }
                        }
                    }
                }
            }
        }

        if (!empty($response['data']['words'])) {
            $response['found'] = true;
            usort($response['data']['words'], function($a, $b) {
                return mb_strlen($a['text']) - mb_strlen($b['text']);
            });
        }
    }

    // --- CASE 2: HỎI AI (ASK_AI) ---
    else if ($action == 'ask_ai') {
        $checkLimit = checkUsageLimit($userIP, $userPass, $MAX_FREE_LIMIT, $ADMIN_PASS, $USAGE_FILE);
        if ($checkLimit['allowed'] === false) {
            $response['error_code'] = 'LIMIT_REACHED';
        } else {
            $aiResult = null;
            $aiDatabase = file_exists($AI_DB_FILE) ? json_decode(file_get_contents($AI_DB_FILE), true) : [];
            
            if (isset($aiDatabase[$keywordRaw])) {
                $aiResult = $aiDatabase[$keywordRaw];
            } else {
                $aiResult = callDeepSeekAI($keywordRaw, $DEEPSEEK_API_KEY);
                if ($aiResult) {
                    $aiDatabase[$keywordRaw] = $aiResult;
                    file_put_contents($AI_DB_FILE, json_encode($aiDatabase, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                    if (!$checkLimit['is_vip']) incrementUsage($userIP, $USAGE_FILE);
                }
            }

            if ($aiResult) {
                $response['found'] = true; 
                if (isset($aiResult['words'])) {
                    foreach ($aiResult['words'] as $w) {
                        // AI luôn có cờ is_ai = true
                        $response['data']['words'][] = ['text' => $w, 'is_ai' => true];
                    }
                }
                if (isset($aiResult['sentences'])) {
                    $response['data']['sentences'] = $aiResult['sentences'];
                }
            }
        }
    }
}

echo json_encode($response);
?>