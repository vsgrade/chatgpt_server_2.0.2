<?php
require "db.php";
header('Content-Type: application/json');
$s = $db->query("SELECT enabled_models FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$models = [];
if (!empty($s["enabled_models"])) {
    $models = json_decode($s["enabled_models"], true);
}
echo json_encode($models, JSON_UNESCAPED_UNICODE);
?>
