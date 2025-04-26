<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
require_once "../version.php";

// Подключение к базе данных
require_once "/www/wwwroot/2inf0.ru/php/chatgpt/server/api/db.php";

// Основная страница
$page = $_GET["page"] ?? "settings";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Админка ChatGPT (v<?= ADMIN_VERSION ?>)</title>
  <link rel="icon" href="favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
  <div class="row flex-nowrap">
    <div class="col-auto col-md-2 p-0 bg-dark text-white min-vh-100">
      <?php include "sidebar.php"; ?>
    </div>
    <div class="col py-4">
      <?php
        $allowed = ["settings", "proxy", "admin", "diagnostics", "logs", "models"];
        if (in_array($page, $allowed) && file_exists("$page.php")) {
          include "$page.php";
        } else {
          echo "<h3>Раздел не найден</h3>";
        }
      ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>