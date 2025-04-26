<?php
require "db.php";
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
parse_str($_SERVER['QUERY_STRING'] ?? '', $query);

if ($method === 'GET') {
    $user_id = intval($_GET['user_id'] ?? 0);
    if (!$user_id) {
        echo json_encode(["success"=>false, "error"=>"user_id required"]);
        exit;
    }
    $stmt = $db->prepare("SELECT id, name, created_at FROM chats WHERE user_id=? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode(["success"=>true, "chats"=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if ($method === 'POST') {
    $user_id = intval($data['user_id'] ?? 0);
    $name = trim($data['name'] ?? "Новый чат");
    if (!$user_id) {
        echo json_encode(["success"=>false, "error"=>"user_id required"]);
        exit;
    }
    $stmt = $db->prepare("INSERT INTO chats (user_id, name) VALUES (?, ?)");
    $stmt->execute([$user_id, $name]);
    echo json_encode(["success"=>true, "chat_id"=>$db->lastInsertId()]);
    exit;
}

if ($method === 'PUT') {
    $chat_id = intval($data['chat_id'] ?? 0);
    $user_id = intval($data['user_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    if (!$chat_id || !$user_id || !$name) {
        echo json_encode(["success"=>false, "error"=>"chat_id, user_id и name обязательны"]);
        exit;
    }
    $stmt = $db->prepare("UPDATE chats SET name=? WHERE id=? AND user_id=?");
    $stmt->execute([$name, $chat_id, $user_id]);
    echo json_encode(["success"=>true]);
    exit;
}

if ($method === 'DELETE') {
    $chat_id = intval($data['chat_id'] ?? 0);
    $user_id = intval($data['user_id'] ?? 0);
    if (!$chat_id || !$user_id) {
        echo json_encode(["success"=>false, "error"=>"chat_id и user_id обязательны"]);
        exit;
    }
    $db->prepare("DELETE FROM messages WHERE chat_id=?")->execute([$chat_id]);
    $db->prepare("DELETE FROM chats WHERE id=? AND user_id=?")->execute([$chat_id, $user_id]);
    echo json_encode(["success"=>true]);
    exit;
}

echo json_encode(["success"=>false, "error"=>"Неверный метод"]);
?>
