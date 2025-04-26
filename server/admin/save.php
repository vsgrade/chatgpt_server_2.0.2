<?php
session_start();
if (!isset($_SESSION["admin"])) {
  header("Location: login.php");
  exit;
}
require_once "../api/db.php";
define("ENCRYPTION_KEY", "mysecretkey12345");
define("ENCRYPTION_IV", "1234567891011121");

function encrypt($value) {
    return openssl_encrypt($value, "AES-128-CTR", ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

$fields = [];
$params = [];

// Обработка всех лимитов
$limits = ["minute", "hour", "day", "week", "month"];
foreach ($limits as $limit) {
    if (isset($_POST["limit_$limit"])) {
        $fields[] = "limit_$limit = ?";
        $params[] = intval($_POST["limit_$limit"]);
    }
}

// Время хранения чатов
if (isset($_POST["chat_ttl_days"])) {
    $fields[] = "chat_ttl_days = ?";
    $params[] = intval($_POST["chat_ttl_days"]);
}

if (isset($_POST["proxy"])) {
    $fields[] = "proxy = ?";
    $params[] = encrypt($_POST["proxy"]);
}
if (isset($_POST["proxy_type"])) {
    $fields[] = "proxy_type = ?";
    $params[] = $_POST["proxy_type"];
}
if (isset($_POST["api_key"])) {
    $fields[] = "api_key = ?";
    $params[] = encrypt($_POST["api_key"]);
}

if (!empty($fields)) {
    $query = "UPDATE settings SET " . implode(", ", $fields);
    $stmt = $db->prepare($query);
    $stmt->execute($params);
}

header("Location: index.php?saved=1");
exit;
?>
