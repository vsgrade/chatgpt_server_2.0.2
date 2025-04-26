<?php
session_start();
if (!isset($_SESSION["admin"])) { header("Location: login.php"); exit; }
require_once "../api/db.php";
$models = $_POST["models"] ?? [];
$models = array_filter($models, 'is_string');
$json = json_encode(array_values($models), JSON_UNESCAPED_UNICODE);
$db->exec("UPDATE settings SET enabled_models=" . $db->quote($json));
header("Location: index.php?models=1#tab-models");
exit;
?>
