<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
require_once "../version.php"; // Подключаем файл с версией

// Подключение к базе данных
// Убедитесь, что этот путь '../api/db.php' правильный на вашем сервере
require_once "../api/db.php"; // <-- ПУТЬ К DB.PHP (уже исправлен в вашей версии)

// Основная страница
$page = $_GET["page"] ?? "settings"; // По умолчанию settings
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Админка ChatGPT (v<?= ADMIN_VERSION ?>)</title> <link rel="icon" href="favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Пример стилей подменю (не были запрошены в последнем шаге, но могут пригодиться) */
    /*
    .submenu-nav .nav-link { color: #d4d4d4 !important; padding-left: 1.5rem !important; }
    .submenu-nav .nav-link.active { background-color: #495057 !important; color: #fff !important; }
    .nav-link.active-parent { background-color: #495057 !important; }
    */
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row flex-nowrap">
    <div class="col-auto col-md-2 p-0 bg-dark text-white min-vh-100">
      <?php include "sidebar.php"; ?>
    </div>
    <div class="col py-4">
      <p class="small text-muted">Версия админки: <strong><?= ADMIN_VERSION ?></strong></p>
      <hr>
      <?php
        // РАЗРЕШЕННЫЕ СТРАНИЦЫ: ДОБАВЛЕНЫ 'limits' и 'chat_ttl'
        $allowed = ["settings", "proxy", "admin", "diagnostics", "logs", "models", "limits", "chat_ttl"]; // <-- ДОБАВЛЕНО
        $page = $_GET["page"] ?? "settings"; // Определение страницы после массива allowed

        // Проверяем, разрешена ли страница и существует ли соответствующий файл
        if (in_array($page, $allowed) && file_exists("$page.php")) {
          include "$page.php"; // Включаем содержимое нужного файла
        } else {
          // Если страница не разрешена или файл не существует
          echo "<h3>Раздел не найден</h3>";
        }
      ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>