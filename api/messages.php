<?php
require "db.php";
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $chat_id = intval($_GET['chat_id'] ?? 0);
    $user_id = intval($_GET['user_id'] ?? 0);
    if (!$chat_id || !$user_id) {
        echo json_encode(["success"=>false, "error"=>"chat_id и user_id обязательны"]);
        exit;
    }
    $stmt = $db->prepare("SELECT id, role, content, created_at FROM messages WHERE chat_id=? AND user_id=? ORDER BY created_at ASC");
    $stmt->execute([$chat_id, $user_id]);
    echo json_encode(["success"=>true, "messages"=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
echo json_encode(["success"=>false, "error"=>"Только GET поддерживается"]);
?>
