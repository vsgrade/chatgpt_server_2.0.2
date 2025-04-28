<?php
require "db.php";
$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";
$type = $data["type"] ?? "login";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

if (!$email || !$password) {
  echo json_encode(["success" => false, "error" => "Поля не заполнены"]);
  exit;
}

if ($type === "register") {
  $exists = $db->prepare("SELECT id FROM users WHERE email=?");
  $exists->execute([$email]);
  if ($exists->fetch()) {
    echo json_encode(["success" => false, "error" => "Пользователь уже существует"]);
    exit;
  }
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $insert = $db->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
  $insert->execute([$email, $hash]);
  $id = $db->lastInsertId();
  echo json_encode(["success" => true, "user_id" => $id]);
} elseif ($type === "login") {
  $stmt = $db->prepare("SELECT id, password FROM users WHERE email=?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if ($user && password_verify($password, $user["password"])) {
    echo json_encode(["success" => true, "user_id" => $user["id"]]);
  } else {
    echo json_encode(["success" => false, "error" => "Неверный логин или пароль"]);
  }
} else {
  echo json_encode(["success" => false, "error" => "Неверный тип запроса"]);
}
?>