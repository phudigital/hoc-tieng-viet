<?php
// api.php - Version 8.1 (Increase AI Words Quantity)
header('Content-Type: application/json; charset=utf-8');

// --- 1. LOAD CẤU HÌNH ---
if (file_exists('config.php')) {
    $config = include('config.php');
    $DEEPSEEK_API_KEY = $config['deepseek_key'];
    $ADMIN_PASS = $config['admin_pass'];
} else {
    $DEEPSEEK_API_KEY = ""; 
    $ADMIN_PASS = "off";    
}

$AI_DB_FILE = 'data-ai.json';
$USAGE_FILE = 'usage.json';
$MAX_FREE_LIMIT = 30;

// --- NHẬN DỮ LIỆU ---
$action = isset($_GET['action']) ? $_GET['action'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$useAI = isset($_GET['use_ai']) && $_GET['use_ai'] === 'true';
$userPass = isset($_GET['password']) ? $_GET['password'] : ''; 
$userIP = $_SERVER['REMOTE_ADDR'];

$dictionary = include('data.php');

// --- CÁC HÀM XỬ LÝ CHUỖI ---
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

// --- KIỂM TRA GIỚI HẠN ---
function checkUsageLimit($ip, $userInputPass, $limit, $adminConfigPass, $file) {
    if ($adminConfigPass === 'off') {
        return ['allowed' => true, 'is_vip' => true];
    }
    if ($userInputPass === $adminConfigPass) {
        return ['allowed' => true, 'is_vip' => true];
    }
    $usageData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $currentCount = $usageData[$ip] ?? 0;

    if ($currentCount >= $limit) {
        return ['allowed' => false, 'count' => $currentCount];
    }
    return ['allowed' => true, 'is_vip' => false];
}

function incrementUsage($ip, $file) {
    $usageData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    if (!isset($usageData[$ip])) $usageData[$ip] = 0;
    $usageData[$ip]++;
    file_put_contents($file, json_encode($usageData));
}

// --- BỘ LỌC TỪ VỰNG AI ---
function filterAIWords($words, $keyword) {
    $filtered = [];
    $keywordRaw = mb_strtolower($keyword, 'UTF-8');
    $keywordNoTone = removeTone($keywordRaw);
    $keywordNoAccents = removeAllAccents($keywordRaw);
    $isBroadMode = ($keywordNoTone === $keywordNoAccents);

    foreach ($words as $word) {
        $wordRaw = mb_strtolower($word, 'UTF-8');
        $wordNoTone = removeTone($wordRaw);
        $wordNoAccents = removeAllAccents($wordRaw);
        $isValid = false;

        if ($isBroadMode) {
            if (strpos($wordNoAccents, $keywordNoAccents) !== false) $isValid = true;
        } else {
            if (strpos($wordNoTone, $keywordNoTone) !== false) $isValid = true;
        }

        if ($isValid) $filtered[] = $word;
    }
    return $filtered;
}

// --- GỌI DEEPSEEK (Đã chỉnh Prompt lên 15 từ) ---
function callDeepSeekAI($keyword, $apiKey) {
    $url = "https://api.deepseek.com/chat/completions";
    
    // CẬP NHẬT PROMPT: Tăng số lượng yêu cầu lên 15 từ
    $userPrompt = "Đóng vai giáo viên Tiếng Việt lớp 1. Từ khóa: '$keyword'.
    1. Tìm 12-15 TỪ GHÉP chứa ĐÚNG vần/chữ '$keyword'. (Yêu cầu nhiều từ để lọc, ưu tiên từ đơn giản, gần gũi như con vật, đồ vật).
    2. Viết 5 câu ngắn chứa từ khóa.
    3. JSON format only: {\"words\":[], \"sentences\":[]}";

    $data = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "You are a strict Vietnamese spelling teacher."],
            ["role" => "user", "content" => $userPrompt]
        ],
        "temperature" => 0.6 // Tăng nhẹ nhiệt độ để AI sáng tạo nhiều từ hơn
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
        $data = json_decode($cleanJson, true);
        
        if ($data && isset($data['words'])) {
            $data['words'] = filterAIWords($data['words'], $keyword);
            
            // Cắt bớt nếu kết quả sau khi lọc quá dài (Giữ lại tối đa 10 từ)
            // Nếu bạn muốn hiện hết thì xóa dòng này đi
            $data['words'] = array_slice($data['words'], 0, 10);
        }
        return $data;
    }
    return null;
}

// --- LOGIC CHÍNH ---
$response = [ 'found' => false, 'keyword' => $keyword, 'error_code' => null, 'data' => [ 'words' => [], 'sentences' => [] ] ];

if ($keyword !== '') {
    $keywordRaw = mb_strtolower($keyword, 'UTF-8');

    // 1. TÌM LOCAL
    if ($action == 'search') {
        $keywordNoTone = removeTone($keywordRaw);
        $keywordNoAccents = removeAllAccents($keywordRaw);
        $isBroadMode = ($keywordNoTone === $keywordNoAccents); 
        $foundKeyMatch = false;
        $foundLocalWords = [];

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
                            $response['data']['words'][] = ['text' => $word];
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

        if (!$foundKeyMatch) {
            foreach ($dictionary as $lessonKey => $content) {
                if (isset($content['words'])) {
                    foreach ($content['words'] as $word) {
                        $wordRaw = mb_strtolower($word, 'UTF-8');
                        $wordNoTone = removeTone($wordRaw);
                        $wordNoAccents = removeAllAccents($wordRaw);
                        $isWordMatch = false;
                        if ($isBroadMode) {
                            if (strpos($wordNoAccents, $keywordNoAccents) !== false) $isWordMatch = true;
                        } else {
                            if (strpos($wordNoTone, $keywordNoTone) !== false) $isWordMatch = true;
                        }
                        
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

    // 2. XỬ LÝ AI
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
                    
                    if (!$checkLimit['is_vip']) {
                        incrementUsage($userIP, $USAGE_FILE);
                    }
                }
            }

            if ($aiResult) {
                $response['found'] = true; 
                if (isset($aiResult['words'])) {
                    foreach ($aiResult['words'] as $w) {
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