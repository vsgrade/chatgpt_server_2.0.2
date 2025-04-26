<?php
session_start();
if (!isset($_SESSION["admin"])) {
  header("Location: login.php");
  exit;
}
?>

<?php
$data = json_decode(file_get_contents("php://input"), true);
$proxy = $data["proxy"];
$type = strtoupper($data["proxy_type"]);
$ch = curl_init("https://api.ipify.org");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_PROXY => $proxy,
  CURLOPT_PROXYTYPE => $type === "SOCKS5" ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP,
  CURLOPT_TIMEOUT => 5
]);
$res = curl_exec($ch);
echo ($res && filter_var($res, FILTER_VALIDATE_IP))
  ? "<span class='text-green-600'>✅ Прокси работает: $res</span>"
  : "<span class='text-red-600'>❌ Ошибка прокси</span>";
?>