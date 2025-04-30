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
// Функция шифрования - нужна для API ключа и прокси, а теперь и для пароля SMTP
function encrypt($value) {
  // Используем константы из config.php, если они там есть, иначе хардкод
  // Это важно для безопасности, ключи должны быть заданы в config.php и храниться в секрете
  $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "mysecretkey12345"; // *** Убедитесь, что этот ключ ЗАДАН в server/api/config.php ***
  $secret_iv = defined('SECRET_IV') ? SECRET_IV : "1234567891011121"; // *** Убедитесь, что этот IV ЗАДАН в server/api/config.php ***
   if (empty($value) || !is_string($value)) return "";
    // Если вы шифровали с base64_encode, раскомментируйте следующую строку
    // $value = base64_encode(openssl_encrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv));
   $encrypted_value = openssl_encrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv);
    // Если при шифровании произошла ошибка, openssl_encrypt может вернуть false
   if ($encrypted_value === false) {
        // Можно добавить логирование ошибки шифрования, если log.php доступен
        // require_once "../api/log.php";
        // log_message('ERROR', 'Encryption failed', __FILE__, __LINE__);
        return ""; // Возвращаем пустую строку или обрабатываем ошибку иначе
   }
   return $encrypted_value;
}


$fields = []; // Массив для построения части SET запроса (например, "api_key = ?")
$params = []; // Массив для параметров подготовленного запроса (?)

// --- Обработка полей из $_POST ---
// Обработка всех лимитов
$limits = ["minute", "hour", "day", "week", "month"];
foreach ($limits as $limit) {
    // Проверяем, было ли поле отправлено в POST запросе
    if (isset($_POST["limit_$limit"])) {
        $fields[] = "limit_$limit = ?";
        $params[] = intval($_POST["limit_$limit"]);
    }
}

// Время хранения чатов
if (isset($_POST["chat_ttl_days"])) {
    $fields[] = "chat_ttl_days = ?";
    $params[] = intval($_POST["chat_ttl_days"]);
}

// Прокси
if (isset($_POST["proxy"])) {
    $fields[] = "proxy = ?";
    $params[] = encrypt($_POST["proxy"]);
}
if (isset($_POST["proxy_type"])) {
    $fields[] = "proxy_type = ?";
    $params[] = $_POST["proxy_type"];
}

// API ключ
if (isset($_POST["api_key"])) {
    $fields[] = "api_key = ?";
    $params[] = encrypt($_POST["api_key"]); // Шифруем API ключ
}

// --- Настройки SMTP ---
if (isset($_POST["smtp_host"])) {
    $fields[] = "smtp_host = ?";
    $params[] = $_POST["smtp_host"];
}
if (isset($_POST["smtp_port"])) {
    $fields[] = "smtp_port = ?";
    $params[] = intval($_POST["smtp_port"]);
}
// smtp_auth - чекбокс отправляет '1' если отмечен, или не отправляет ничего если не отмечен
// Важно: Даже если чекбокс не отмечен, нам нужно обновить значение в БД на 0
$fields[] = "smtp_auth = ?";
$params[] = isset($_POST["smtp_auth"]) ? 1 : 0;


if (isset($_POST["smtp_username"])) {
    $fields[] = "smtp_username = ?";
    $params[] = $_POST["smtp_username"];
}
// Пароль SMTP сохраняем только если поле не пустое
if (isset($_POST["smtp_password"]) && $_POST["smtp_password"] !== '') {
    $fields[] = "smtp_password = ?";
    $params[] = encrypt($_POST["smtp_password"]); // Шифруем пароль SMTP
}
if (isset($_POST["smtp_secure"])) {
    $fields[] = "smtp_secure = ?";
    $params[] = $_POST["smtp_secure"];
}

// Тестовый Email получатель
if (isset($_POST["test_email_recipient"])) {
    $fields[] = "test_email_recipient = ?";
    $params[] = $_POST["test_email_recipient"];
}

// --- Поле Часового пояса УДАЛЕНО ---


$response = ['success' => false, 'error' => 'Нет данных для сохранения']; // Начальный ответ

// Если есть поля для обновления, строим и выполняем запрос
if (!empty($fields)) {
    // Строим SQL запрос: UPDATE settings SET поле1=?, поле2=?, ...
    $query = "UPDATE settings SET " . implode(", ", $fields);
    // Добавляем условие WHERE id = 1, так как у нас только одна строка настроек
    $query .= " WHERE id = 1";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params); // Выполняем запрос с параметрами

        // Проверяем, была ли затронута строка (не обязательно, но может быть полезно)
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