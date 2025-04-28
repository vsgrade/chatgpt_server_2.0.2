<?php
session_start();
if (!isset($_SESSION["admin"])) {
  header("Location: login.php");
  exit;
}
require_once "../api/db.php";

$level = $_GET['level'] ?? 'all';
$query = "SELECT * FROM error_logs WHERE 1=1 ";
$params = [];

if($level != 'all') {
    $query .= "AND message LIKE ? ";
    $params[] = "[$level]%"; 
}

$query .= "ORDER BY created_at DESC LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute($params);

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table class='table table-bordered table-sm'>";
echo "<thead><tr>
        <th>Дата</th>
        <th>Уровень</th>
        <th>Файл</th>
        <th>Строка</th>
        <th>Сообщение</th>
      </tr></thead><tbody>";

foreach ($logs as $log) {
  preg_match('/^\[(\w+)\]/', $log['message'], $matches);
  $lvl = $matches[1] ?? 'INFO';
  
  echo "<tr>";
  echo "<td>" . htmlspecialchars($log["created_at"]) . "</td>";
  echo "<td>" . htmlspecialchars($lvl) . "</td>";
  echo "<td>" . htmlspecialchars($log["file"]) . "</td>";
  echo "<td>" . htmlspecialchars($log["line"]) . "</td>";
  echo "<td>" . nl2br(htmlspecialchars($log["message"])) . "</td>";
  echo "</tr>";
}
echo "</tbody></table>";
?>