<?php
session_start();
require_once "../api/db.php";  // Подключение к базе данных

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Обработка действия по проверке таблиц
if (isset($_GET['action']) && $_GET['action'] == 'check') {
    // Список обязательных таблиц
    $requiredTables = ['users', 'settings', 'token_usage', 'admins', 'error_logs', 'chats', 'messages'];
    
    $missingTables = [];
    $checkedTables = [];  // Массив для сохранения проверенных таблиц
    
    try {
        // Проверка наличия каждой таблицы
        foreach ($requiredTables as $table) {
            // Подставляем таблицу в запрос без использования подготовленных выражений
            $stmt = $db->prepare("SHOW TABLES LIKE '$table'");
            $stmt->execute();
            
            $checkedTables[] = $table;  // Добавляем проверенную таблицу в массив
            
            if ($stmt->rowCount() == 0) {
                $missingTables[] = $table;  // Если таблица не найдена, добавляем в список отсутствующих
            }
        }
        
        // Формируем сообщение о проверенных таблицах
        $checkedTablesMessage = "Проверенные таблицы: " . implode(', ', $checkedTables);
        
        // Возвращаем JSON-ответ в зависимости от наличия таблиц
        if (empty($missingTables)) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Все таблицы присутствуют.',
                'checked_tables' => $checkedTablesMessage
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Отсутствающие таблицы: ' . implode(', ', $missingTables),
                'checked_tables' => $checkedTablesMessage
            ]);
        }
    } catch (Exception $e) {
        // В случае ошибки выводим сообщение об ошибке
        echo json_encode(['status' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()]);
    }
    exit;
}

// Обработка действия по созданию таблиц
if (isset($_GET['action']) && $_GET['action'] == 'create') {
    try {
        // Список SQL-запросов для создания таблиц
        $createQueries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_name VARCHAR(255) NOT NULL,
                value TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS token_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_usage INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            "CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS error_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                level VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                file VARCHAR(255) NOT NULL,
                line INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS chats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                chat_data TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            "CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chat_id INT NOT NULL,
                message_text TEXT NOT NULL,
                sender_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (chat_id) REFERENCES chats(id),
                FOREIGN KEY (sender_id) REFERENCES users(id)
            )"
        ];

        // Выполнение каждого запроса на создание таблицы
        foreach ($createQueries as $query) {
            $db->exec($query);
        }

        // Возвращаем успешный ответ
        echo json_encode(['status' => 'success', 'message' => 'Таблицы успешно созданы.']);
    } catch (Exception $e) {
        // В случае ошибки выводим сообщение об ошибке
        echo json_encode(['status' => 'error', 'message' => 'Ошибка создания таблиц: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Диагностика</title>
    <link rel="stylesheet" href="path_to_your_css.css"> <!-- Путь к вашему CSS -->
</head>
<body>
    <h3>🩺 Диагностика</h3>
    <p>Для корректной работы системы должны быть созданы таблицы в базе данных:</p>
    <ul>
      <li><strong>users</strong>, <strong>settings</strong>, <strong>token_usage</strong>, <strong>admins</strong>, <strong>error_logs</strong>, <strong>chats</strong>, <strong>messages</strong></li>
    </ul>
    <div class="mb-3">
        <button class="btn btn-outline-primary" onclick="runDiagnostics()">Проверить</button>
        <button class="btn btn-outline-success ms-2" onclick="createTables()">Создать таблицы</button>
    </div>
    <div id="diagnosticsResult" class="mt-3"></div>

    <script>
        // Функция для выполнения диагностики (проверки таблиц)
        function runDiagnostics() {
            const resultDiv = document.getElementById("diagnosticsResult");
            resultDiv.innerHTML = "⏳ Проверка...";  // Индикация процесса
            fetch("diagnostics.php?action=check")
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        resultDiv.innerHTML = `<span class='text-success'>${data.message}</span><br> ${data.checked_tables}`;
                    } else {
                        resultDiv.innerHTML = `<span class='text-danger'>${data.message}</span><br> ${data.checked_tables}`;
                    }
                })
                .catch(err => {
                    resultDiv.innerHTML = "<span class='text-danger'>Ошибка диагностики: " + err + "</span>";  // В случае ошибки
                });
        }

        // Функция для создания таблиц (если необходимо)
        function createTables() {
            const resultDiv = document.getElementById("diagnosticsResult");
            resultDiv.innerHTML = "⚙️ Создание таблиц...";  // Индикация создания таблиц
            fetch("diagnostics.php?action=create")
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        resultDiv.innerHTML = `<span class='text-success'>${data.message}</span>`;  // Успех
                    } else {
                        resultDiv.innerHTML = `<span class='text-danger'>${data.message}</span>`;  // Ошибка
                    }
                })
                .catch(err => {
                    resultDiv.innerHTML = "<span class='text-danger'>Ошибка создания таблиц: " + err + "</span>";  // В случае ошибки
                });
        }
    </script>
</body>
</html>
