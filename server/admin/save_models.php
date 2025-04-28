<?php
session_start();
if (!isset($_SESSION["admin"])) {
    // Если админ не авторизован, возвращаем ошибку JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once "../api/db.php";

$response = ['success' => false, 'error' => 'Неизвестная ошибка']; // Начальный ответ

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $models = $_POST["models"] ?? []; // Получаем массив выбранных моделей из POST

        // Фильтруем массив, оставляя только строки (для безопасности)
        $models = array_filter($models, 'is_string');

        // Преобразуем массив в строку JSON для хранения в базе
        // Используем array_values() для сброса ключей, если они не числовые после array_filter
        $modelsJson = json_encode(array_values($models), JSON_UNESCAPED_UNICODE);

        // Обновляем поле enabled_models в таблице settings
        // Используем exec для простого запроса, т.к. значение одно и оно уже подготовлено (json_encode и quote)
        $db->exec("UPDATE settings SET enabled_models=" . $db->quote($modelsJson));

        // Формируем успешный ответ
        $response = ['success' => true, 'message' => 'Выбранные модели успешно сохранены!'];

    } catch (Exception $e) {
        // В случае ошибки, формируем ответ с ошибкой
        // Логируем ошибку, если настроена система логирования
        // error_log('Ошибка UPDATE в save_models.php: ' . $e->getMessage()); // Пример

        $response = ['success' => false, 'error' => 'Ошибка сохранения моделей: ' . $e->getMessage()];
    }
} else {
    $response = ['success' => false, 'error' => 'Неверный метод запроса'];
}

// Устанавливаем заголовок Content-Type для JSON и отправляем JSON ответ
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit; // Завершаем выполнение скрипта