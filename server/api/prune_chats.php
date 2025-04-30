<?php
require_once "db.php";

// Получаем время хранения чатов (в днях)
$s = $db->query("SELECT chat_ttl_days FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$ttl = intval($s['chat_ttl_days'] ?? 30);

// Удаляем чаты, где не было сообщений за $ttl дней
// или в которых вообще нет сообщений и созданы более $ttl дней назад

// Сначала ищем ID чатов для удаления
$query = "
    SELECT c.id FROM chats c
    LEFT JOIN messages m ON m.chat_id = c.id
    GROUP BY c.id
    HAVING 
      (MAX(m.created_at) IS NOT NULL AND MAX(m.created_at) < DATE_SUB(NOW(), INTERVAL :ttl DAY))
      OR
      (MAX(m.created_at) IS NULL AND c.created_at < DATE_SUB(NOW(), INTERVAL :ttl DAY))
";
$stmt = $db->prepare($query);
$stmt->execute(['ttl' => $ttl]);
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($ids) {
    // Удаляем сообщения
    $in = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare("DELETE FROM messages WHERE chat_id IN ($in)")->execute($ids);
    // Удаляем чаты
    $db->prepare("DELETE FROM chats WHERE id IN ($in)")->execute($ids);
    echo "Удалено чатов: " . count($ids);
} else {
    echo "Нет неактивных чатов для удаления.";
}
?>
