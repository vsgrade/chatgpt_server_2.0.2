<?php
session_start();
require_once "../api/db.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $login = $_POST["login"] ?? "";
  $password = $_POST["password"] ?? "";

  // Используем новое название колонки — login
  $stmt = $db->prepare("SELECT password FROM admin WHERE login = ?");
  $stmt->execute([$login]);
  $hash = $stmt->fetchColumn();

  if ($hash && password_verify($password, $hash)) {
    $_SESSION["admin"] = $login;
    header("Location: index.php");
    exit;
  } else {
    $error = "Неверный логин или пароль.";
  }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Вход в админку</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <form method="post" class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
    <h4 class="mb-3 text-center">Вход в админку</h4>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="mb-3">
      <label for="login" class="form-label">Логин</label>
      <input type="text" class="form-control" name="login" id="login" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Пароль</label>
      <input type="password" class="form-control" name="password" id="password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Войти</button>
  </form>
</body>
</html>
