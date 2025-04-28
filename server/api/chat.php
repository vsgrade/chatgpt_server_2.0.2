<?php
file_put_contents(__DIR__.'/die_marker.txt', date('Y-m-d H:i:s')." chat.php started\n", FILE_APPEND);
error_reporting(E_ALL); ini_set('display_errors', 1);

require_once __DIR__ . "/log.php";
file_put_contents(__DIR__.'/die_marker.txt', date('Y-m-d H:i:s')." log.php required\n", FILE_APPEND);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = intval($data["user_id"] ?? 0);
    $prompt = $data["message"] ?? "";
    $model = $data["model"] ?? "gpt-3.5-turbo";
    $chat_id = intval($data['chat_id'] ?? 0); // Новый параметр

    if (!$user_id || !$prompt) {
        log_message('ERROR', 'Недостаточные данные: user_id=' . $user_id . ', prompt=' . $prompt, __FILE__, __LINE__);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }

    require_once __DIR__ . "/db.php";
    file_put_contents(__DIR__.'/die_marker.txt', date('Y-m-d H:i:s')." db.php required\n", FILE_APPEND);

    $tokens_used = ceil(strlen($prompt)/4);

    try {
        $s = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$s) {
            log_message('ERROR', 'Таблица settings пуста или не найдена', __FILE__, __LINE__);
            http_response_code(500);
            echo json_encode(['error' => 'Server configuration error']);
            exit;
        }
    } catch (Exception $e) {
        log_message('ERROR', 'Database error in chat.php: ' . $e->getMessage(), __FILE__, __LINE__);
        http_response_code(500);
        echo json_encode(['error' => 'Database connection error']);
        exit;
    }

    define("ENCRYPTION_KEY", "mysecretkey12345");
    define("ENCRYPTION_IV", "1234567891011121");

    function decrypt($value) {
        return openssl_decrypt($value, "AES-128-CTR", ENCRYPTION_KEY, 0, ENCRYPTION_IV);
    }

    $apiKey = decrypt($s["api_key"]);
    $proxy = decrypt($s["proxy"]);
    $proxyType = strtoupper($s["proxy_type"] ?? "HTTP");

    if (!$apiKey) {
        log_message('ERROR', 'API key is not set', __FILE__, __LINE__);
        http_response_code(500);
        echo json_encode(['error' => 'API key is not configured']);
        exit;
    }

    $limits = [
        "minute" => $s["limit_minute"],
        "hour" => $s["limit_hour"],
        "day" => $s["limit_day"],
        "week" => $s["limit_week"],
        "month" => $s["limit_month"]
    ];

    function over_limit($unit, $limit, $db, $user_id, $prompt) {
        if (!$limit) return false;
        try {
            $stmt = $db->prepare("SELECT SUM(tokens) FROM token_usage 
                WHERE user_id=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 $unit)");
            $stmt->execute([$user_id]);
            return intval($stmt->fetchColumn()) + ceil(strlen($prompt)/4) > $limit;
        } catch (Exception $e) {
            log_message('ERROR', 'Limit check error: ' . $e->getMessage(), __FILE__, __LINE__);
            return false;
        }
    }

    foreach ($limits as $unit => $val) {
        if (over_limit($unit, $val, $db, $user_id, $prompt)) {
            log_message('WARNING', 'Превышен лимит токенов за ' . $unit, __FILE__, __LINE__);
            http_response_code(429);
            echo json_encode(["error" => "Превышен лимит токенов за $unit"]);
            exit;
        }
    }

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    $headers = [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ];
    $postData = [
        "model" => $model,
        "messages" => [["role" => "user", "content" => $prompt]],
        "stream" => false
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_TIMEOUT => 30
    ]);

    if (!empty($proxy)) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYTYPE, $proxyType === "SOCKS5" ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        log_message('ERROR', 'OpenAI API error: ' . $response . ' | ' . $curlError, __FILE__, __LINE__);
        http_response_code(500);
        echo json_encode(['error' => 'OpenAI API error: ' . ($response ?: $curlError)]);
        exit;
    }

    $responseData = json_decode($response, true);
    if (!$responseData || !isset($responseData['choices'][0]['message']['content'])) {
        log_message('ERROR', 'Invalid OpenAI response: ' . $response, __FILE__, __LINE__);
        http_response_code(500);
        echo json_encode(['error' => 'Invalid OpenAI response']);
        exit;
    }

    $reply = $responseData['choices'][0]['message']['content'];

    try {
        $db->prepare("INSERT INTO token_usage (user_id, tokens) VALUES (?, ?)")
           ->execute([$user_id, $tokens_used]);
    } catch (Exception $e) {
        log_message('WARNING', 'Failed to log token usage: ' . $e->getMessage(), __FILE__, __LINE__);
    }

    // --- Сохраняем сообщения в базе ---
    try {
        // Если chat_id не пришёл, создать новый чат с дефолтным названием
        if (!$chat_id) {
            $stmt = $db->prepare("INSERT INTO chats (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $chat_id = $db->lastInsertId();
        }
        // Сохраняем user message
        $stmt = $db->prepare("INSERT INTO messages (chat_id, user_id, role, content) VALUES (?, ?, 'user', ?)");
        $stmt->execute([$chat_id, $user_id, $prompt]);
        // Сохраняем bot message
        $stmt = $db->prepare("INSERT INTO messages (chat_id, user_id, role, content) VALUES (?, ?, 'bot', ?)");
        $stmt->execute([$chat_id, $user_id, $reply]);
    } catch (Exception $e) {
        log_message('WARNING', 'Ошибка при записи сообщений в БД: ' . $e->getMessage(), __FILE__, __LINE__);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'reply' => $reply, 'chat_id' => $chat_id]);

} catch (Exception $e) {
    log_message('CRITICAL', 'Unexpected server error: ' . $e->getMessage(), __FILE__, __LINE__);
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Unexpected server error']);
}
?>
