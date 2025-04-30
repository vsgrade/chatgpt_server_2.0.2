<?php
session_start();
if (!isset($_SESSION["admin"])) {
  http_response_code(403); // Отправляем статус 403 Forbidden, если админ не авторизован
  // Вместо редиректа возвращаем JSON ошибку для AJAX запросов
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Не авторизован']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$proxy = $data["proxy"] ?? null;
$type = strtoupper($data["proxy_type"] ?? "HTTP");

// Используем сервис ip-api.com для получения информации по IP (включая страну).
// Этот сервис обычно бесплатен для некоммерческого использования, но проверьте его условия.
// Запрос без указания IP вернет информацию о вашем публичном IP (который CURL видит, проходя через прокси).
$target_url = "http://ip-api.com/json/";


$ch = curl_init($target_url); // Инициализируем CURL запрос к ip-api.com
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true, // Возвращать результат как строку
  CURLOPT_TIMEOUT => 10 // Таймаут 10 секунд
]);

if ($proxy) {
  // Если прокси указан, настраиваем CURL для его использования
  curl_setopt($ch, CURLOPT_PROXY, $proxy);
  curl_setopt($ch, CURLOPT_PROXYTYPE, $type === "SOCKS5" ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP);
  // CURLOPT_PROXYAUTH, CURLOPT_PROXYUSERPWD могут потребоваться для прокси с авторизацией
  // Если ваш прокси требует авторизацию, возможно, нужно будет добавить эти опции
  // Пример: curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'user:password');
}

$response = curl_exec($ch); // Выполняем CURL запрос
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP статус
$curl_error = curl_error($ch); // Получаем ошибку CURL, если есть
curl_close($ch); // Закрываем CURL соединение

header('Content-Type: application/json'); // Устанавливаем заголовок ответа как JSON

if ($http_code === 200) {
    // Если ответ пришел с кодом 200 (Успех)
    $data = json_decode($response, true); // Декодируем JSON ответ
    // Проверяем, что ответ корректен и содержит нужные поля (IP и страна)
    if ($data && $data['status'] === 'success' && isset($data['query'], $data['country'])) {
        // Возвращаем успешный JSON ответ с IP и Страной
        echo json_encode([
            'success' => true,
            'ip' => $data['query'],
            'country' => $data['country'],
            // Формируем сообщение для вывода на странице
            'message' => "✅ Прокси работает. IP: {$data['query']}, Страна: {$data['country']}"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Если HTTP статус 200, но ответ API некорректный
        // Можно логировать $response для отладки
        // error_log("test_proxy.php: Неожиданный ответ IP-API: " . $response);
        echo json_encode([
            'success' => false,
            'error' => "Неожиданный ответ от сервиса IP-геолокации.",
            'details' => $response // Включаем сырой ответ для отладки
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
  // Если HTTP статус не 200 (например, 404, 500, или ошибка подключения)
   // Можно логировать ошибку CURL или HTTP статус
  // error_log("test_proxy.php: Ошибка CURL: $curl_error, HTTP: $http_code, Ответ: $response");

  echo json_encode([
    'success' => false,
    'error' => "Ошибка при проверке прокси: HTTP $http_code. CURL Error: " . ($curl_error ?: 'Нет данных об ошибке.'), // Включаем ошибку CURL
    'details' => $response // Включаем сырой ответ для отладки
  ], JSON_UNESCAPED_UNICODE);
}
exit; // Завершаем скрипт