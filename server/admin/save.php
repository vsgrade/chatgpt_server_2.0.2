<?php
session_start();
if (!isset($_SESSION["admin"])) {
    // Если админ не авторизован, возвращаем ошибку JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once "../api/db.php";
// Подключаем config.php для ключей шифрования.
// Убедитесь, что SECRET_KEY и SECRET_IV определены в server/api/config.php
require_once "../api/config.php";


// Функция шифрования - нужна для API ключа и прокси
function encrypt($value) {
  // Используем константы из config.php, если они там есть, иначе хардкод
  // Это важно для безопасности, ключи должны быть заданы в config.php и храниться в секрете
  $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "mysecretkey12345"; // *** Убедитесь, что этот ключ ЗАДАН в server/api/config.php ***
  $secret_iv = defined('SECRET_IV') ? SECRET_IV : "1234567891011121"; // *** Убедитесь, что этот IV ЗАДАН в server/api/config.php ***
  return openssl_encrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv);
}

$fields = []; // Массив для построения части SET запроса (например, "api_key = ?")
$params = []; // Массив для параметров подготовленного запроса (?)

// --- Обработка полей из $_POST ---
// Логика взята из вашего оригинального save.php
// Обработка всех лимитов
$limits = ["minute", "hour", "day", "week", "month"];
foreach ($limits as $limit) {
    // Проверяем, было ли поле отправлено в POST запросе
    if (isset($_POST["limit_$limit"])) {
        // Добавляем название столбца и placeholder в массив полей для UPDATE
        $fields[] = "limit_$limit = ?";
        // Добавляем значение в массив параметров
        $params[] = intval($_POST["limit_$limit"]); // Преобразуем в целое число
    }
}

// Время хранения чатов
if (isset($_POST["chat_ttl_days"])) {
    $fields[] = "chat_ttl_days = ?";
    $params[] = intval($_POST["chat_ttl_days"]); // Преобразуем в целое число
}

// Прокси
if (isset($_POST["proxy"])) {
    $fields[] = "proxy = ?";
    $params[] = encrypt($_POST["proxy"]); // Шифруем значение прокси
}
if (isset($_POST["proxy_type"])) {
    $fields[] = "proxy_type = ?";
    $params[] = $_POST["proxy_type"]; // Тип прокси обычно не шифруется
}

// API ключ
if (isset($_POST["api_key"])) {
    $fields[] = "api_key = ?";
    $params[] = encrypt($_POST["api_key"]); // Шифруем API ключ
}

$response = ['success' => false, 'error' => 'Нет данных для сохранения']; // Начальный ответ

// Если есть поля для обновления, строим и выполняем запрос
if (!empty($fields)) {
    // Строим SQL запрос: UPDATE settings SET поле1=?, поле2=?, ...
    $query = "UPDATE settings SET " . implode(", ", $fields);
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params); // Выполняем запрос с параметрами

        // Проверяем, были ли затронуты строки (не обязательно, но может быть полезно)
        // if ($stmt->rowCount() > 0) { ... }

        // Формируем успешный ответ
        $response = ['success' => true, 'message' => 'Настройки успешно сохранены!'];

    } catch (Exception $e) {
        // В случае ошибки, формируем ответ с ошибкой
        // Можно также логировать ошибку на сервере, если настроена система логирования
        // error_log('Ошибка UPDATE в save.php: ' . $e->getMessage()); // Пример стандартного логирования ошибок PHP

        $response = ['success' => false, 'error' => 'Ошибка сохранения настроек: ' . $e->getMessage()];
    }
}

// Устанавливаем заголовок Content-Type для JSON и отправляем JSON ответ
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit; // Завершаем выполнение скрипта