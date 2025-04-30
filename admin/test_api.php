<?php
session_start();
if (!isset($_SESSION["admin"])) {
  header("Location: login.php");
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$api_key = $data["api_key"];
$proxy = $data["proxy"] ?? null;
$proxy_type = strtoupper($data["proxy_type"] ?? "HTTP");

$ch = curl_init("https://api.openai.com/v1/models");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer " . $api_key
  ],
  CURLOPT_TIMEOUT => 10
]);

if ($proxy) {
  curl_setopt($ch, CURLOPT_PROXY, $proxy);
  curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type === "SOCKS5" ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP);
}

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($http_code === 200) {
  echo "<span class='text-success'>✅ API ключ действителен</span>";
} else {
  echo "<span class='text-danger'>❌ Ошибка API: $error (HTTP $http_code)</span>";
}
?>