<?php
session_start();
if (!isset($_SESSION["admin"])) {
  header("Location: login.php");
  exit;
}

require_once "../api/db.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $login = $_SESSION["admin"];
  $old = $_POST["old_password"] ?? "";
  $new = $_POST["new_password"] ?? "";
  $repeat = $_POST["repeat_password"] ?? "";

  $stmt = $db->prepare("SELECT password FROM admin WHERE login = ?");
  $stmt->execute([$login]);
  $hash = $stmt->fetchColumn();

  if (!$hash || !password_verify($old, $hash)) {
    $message = "Старый пароль введён неверно.";
  } elseif ($new !== $repeat) {
    $message = "Новый пароль и повтор не совпадают.";
  } elseif (strlen($new) < 6) {
    $message = "Пароль должен быть не короче 6 символов.";
  } else {
    $stmt = $db->prepare("UPDATE admin SET password = ? WHERE login = ?");
    $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $login]);
    $message = "Пароль успешно изменён.";
  }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Смена пароля</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
  <div class="container">
    <div class="card mx-auto shadow-sm" style="max-width: 500px;">
      <div class="card-body">
        <h4 class="card-title mb-4 text-center">Смена пароля</h4>
        <?php if ($message): ?>
          <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Старый пароль</label>
            <input type="password" name="old_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Новый пароль</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Повторите пароль</label>
            <input type="password" name="repeat_password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Сменить пароль</button>
          <a href="index.php" class="btn btn-link w-100 mt-2">← Назад</a>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
