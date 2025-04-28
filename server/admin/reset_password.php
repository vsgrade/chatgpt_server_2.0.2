<?php
// Простой защитный код (можно изменить)
define("RESET_CODE", "reset123");

require_once "../api/db.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $code = $_POST["code"] ?? "";
  $new = $_POST["new_password"] ?? "";
  $repeat = $_POST["repeat_password"] ?? "";
  $login = $_POST["login"] ?? "admin";

  if ($code !== RESET_CODE) {
    $message = "Неверный защитный код.";
  } elseif ($new !== $repeat) {
    $message = "Пароли не совпадают.";
  } elseif (strlen($new) < 6) {
    $message = "Пароль слишком короткий.";
  } else {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE admin SET password = ? WHERE login = ?");
    $stmt->execute([$hash, $login]);
    $message = "✅ Пароль обновлён для пользователя $login.";
  }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Сброс пароля администратора</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
  <div class="container">
    <div class="card mx-auto shadow-sm" style="max-width: 500px;">
      <div class="card-body">
        <h4 class="card-title mb-4 text-center">Сброс пароля</h4>
        <?php if ($message): ?>
          <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Имя пользователя (login)</label>
            <input type="text" name="login" class="form-control" value="admin" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Новый пароль</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Повторите пароль</label>
            <input type="password" name="repeat_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Защитный код</label>
            <input type="text" name="code" class="form-control" placeholder="например: reset123" required>
          </div>
          <button class="btn btn-warning w-100">Сбросить пароль</button>
          <a href="login.php" class="btn btn-link w-100 mt-2">← Назад к входу</a>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
