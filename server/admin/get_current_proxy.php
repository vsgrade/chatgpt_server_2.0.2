<?php
session_start();
if (!isset($_SESSION["admin"])) { http_response_code(403); exit; }
require_once "../api/db.php";
function decrypt($value) {
  return openssl_decrypt($value, "AES-128-CTR", "mysecretkey12345", 0, "1234567891011121");
}
$settings = $db->query("SELECT proxy, proxy_type FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo json_encode([
  "proxy" => isset($settings["proxy"]) ? decrypt($settings["proxy"]) : "",
  "proxy_type" => $settings["proxy_type"] ?? "http"
]);
?>
