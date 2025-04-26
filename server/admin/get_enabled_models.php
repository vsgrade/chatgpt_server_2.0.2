<?php
session_start();
if (!isset($_SESSION["admin"])) { http_response_code(403); exit; }
require_once "../api/db.php";
$s = $db->query("SELECT enabled_models FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$models = [];
if (!empty($s["enabled_models"])) {
    $models = json_decode($s["enabled_models"], true);
}
echo json_encode($models);
?>
