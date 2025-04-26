<?php
session_start();
if (!isset($_SESSION["admin"])) {
  http_response_code(403); exit;
}
require_once "../api/db.php";

$s = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

define("ENCRYPTION_KEY", "mysecretkey12345");
define("ENCRYPTION_IV", "1234567891011121");
function decrypt($value) {
    return openssl_decrypt($value, "AES-128-CTR", ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}
$apiKey = decrypt($s["api_key"]);
$proxy = !empty($s["proxy"]) ? decrypt($s["proxy"]) : '';
$proxyType = isset($s["proxy_type"]) ? strtoupper($s["proxy_type"]) : "HTTP";

// --- ОТЛАДОЧНАЯ СТРОКА: сохраняет каждый запрос к proxy_debug.txt ---
file_put_contents(__DIR__ . '/proxy_debug.txt', date('Y-m-d H:i:s') . " Proxy: $proxy ($proxyType)\n", FILE_APPEND);

if (!$apiKey) {
    http_response_code(400);
    echo json_encode(["error" => "API ключ не найден или пуст"]);
    exit;
}

$ch = curl_init("https://api.openai.com/v1/models");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ["Authorization: Bearer $apiKey"],
  CURLOPT_TIMEOUT => 10
]);

if ($proxy) {
  curl_setopt($ch, CURLOPT_PROXY, $proxy);
  curl_setopt($ch, CURLOPT_PROXYTYPE, $proxyType === "SOCKS5" ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP);
}

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code !== 200) {
  http_response_code(400);
  echo json_encode([
      "error" => "Ошибка API OpenAI: HTTP $http_code $curl_error",
      "details" => $response
  ]);
  exit;
}

$data = json_decode($response, true);

if (!isset($data["data"]) || !is_array($data["data"])) {
  http_response_code(400);
  echo json_encode(["error" => "Некорректный ответ OpenAI", "raw" => $response]);
  exit;
}

$filtered = [];
foreach ($data["data"] as $model) {
  if (strpos($model["id"], "gpt-3.5") === 0 || strpos($model["id"], "gpt-4") === 0) {
    $filtered[] = ["id" => $model["id"], "name" => $model["id"]];
  }
}
echo json_encode($filtered, JSON_UNESCAPED_UNICODE);
?>
