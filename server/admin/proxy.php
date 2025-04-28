<?php
session_start(); // Убедимся, что сессия запущена
if (!isset($_SESSION["admin"])) { // Проверка авторизации
    header("Location: login.php");
    exit;
}
require_once "../api/db.php"; // Подключаем базу данных
require_once "../api/config.php"; // Подключаем config для ключей шифрования


// Загружаем настройки прокси из базы
$settings = $db->query("SELECT proxy, proxy_type FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Функция расшифровки - нужна для прокси
function decrypt($value) {
  // Используем константы из config.php, если они там есть, иначе хардкод
  // Убедитесь, что SECRET_KEY и SECRET_IV заданы в server/api/config.php
  $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "mysecretkey12345";
  $secret_iv = defined('SECRET_IV') ? SECRET_IV : "1234567891011121";
  // Добавлена базовая проверка на пустую или некорректную строку
  if (empty($value) || !is_string($value)) return "";
  return openssl_decrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv);
}
$settings["proxy"] = isset($settings["proxy"]) ? decrypt($settings["proxy"]) : "";

?>
<h3>🌐 Прокси</h3>
<form id="proxyForm" method="POST" action="save.php" class="mb-3">
  <div class="mb-3">
    <label for="proxy_type" class="form-label">Тип прокси</label>
    <select class="form-select" name="proxy_type" id="proxy_type">
      <option value="http" <?= ($settings['proxy_type'] ?? '') === 'http' ? 'selected' : '' ?>>HTTP</option>
      <option value="socks5" <?= ($settings['proxy_type'] ?? '') === 'socks5' ? 'selected' : '' ?>>SOCKS5</option>
    </select>
  </div>
  <div class="mb-3">
    <label for="proxy" class="form-label">Адрес прокси</label>
    <input type="text" class="form-control" name="proxy" id="proxy" value="<?= htmlspecialchars($settings['proxy'] ?? '') ?>">
  </div>
  <button type="submit" class="btn btn-primary">Сохранить</button>
  <button type="button" class="btn btn-outline-secondary ms-2" onclick="checkProxy()">Проверить прокси</button>
  <div id="proxyResult" class="mt-2 small"></div> <div id="saveStatus" class="mt-3"></div> </form>

<script>
// Функция для проверки прокси - ИЗМЕНЕНА ДЛЯ ОБРАБОТКИ JSON ОТВЕТА С IP И СТРАНОЙ
function checkProxy() {
  const proxy = document.getElementById('proxy').value;
  const type = document.getElementById('proxy_type').value;
  const proxyResultDiv = document.getElementById('proxyResult'); // Находим div для вывода результата

  // Показываем индикатор проверки
  proxyResultDiv.innerHTML = '<span class="text-info">Проверка прокси...</span>';
  proxyResultDiv.className = 'mt-2 small text-info'; // Обновляем классы для индикатора

  fetch('test_proxy.php', { // Отправляем запрос на test_proxy.php
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ proxy: proxy, proxy_type: type }) // Отправляем прокси и тип в формате JSON
  })
  .then(response => {
      // Проверяем, что ответ пришел успешно (статус 2xx)
      if (!response.ok) {
        // Если ошибка HTTP, читаем текст ответа и бросаем исключение
        return response.text().then(text => { throw new Error('HTTP error ' + response.status + ': ' + text) });
      }
      // Ожидаем и парсим JSON ответ
      return response.json();
  })
  .then(data => {
      // Обрабатываем JSON ответ от test_proxy.php
      if (data.success) {
          // Если success: true, показываем сообщение об успехе с IP и Страной из ответа
          proxyResultDiv.innerHTML = data.message; // Используем готовое сообщение из PHP
          proxyResultDiv.className = 'mt-2 small text-success'; // Зеленый цвет для успеха
      } else {
          // Если success: false, показываем сообщение об ошибке
          proxyResultDiv.innerHTML = data.error || 'Неизвестная ошибка при проверке прокси';
          proxyResultDiv.className = 'mt-2 small text-danger'; // Красный цвет для ошибки
          console.error('Proxy check details:', data.details); // Выводим детали ошибки в консоль для отладки
      }
  })
  .catch(err => {
      // Обрабатываем любые ошибки, возникшие в процессе fetch или обработки ответа
      console.error('Proxy check fetch error:', err);
      proxyResultDiv.innerHTML = 'Ошибка проверки прокси: ' + err.message;
      proxyResultDiv.className = 'mt-2 small text-danger'; // Красный цвет для ошибки
  });
}

// СКРИПТ ДЛЯ AJAX-СОХРАНЕНИЯ (добавлен ранее)
document.addEventListener("DOMContentLoaded", function() {
  const saveForm = document.getElementById('proxyForm'); // Находим форму по ее ID
  const saveButton = saveForm.querySelector('button[type="submit"]'); // Кнопка сохранения
  const saveStatusDiv = document.getElementById('saveStatus'); // Элемент для статуса сохранения

  saveForm.addEventListener('submit', function(event) {
    event.preventDefault(); // Предотвращаем стандартную отправку формы

    saveButton.disabled = true; // Отключаем кнопку
    saveStatusDiv.innerHTML = '<span class="text-info">Сохранение...</span>'; // Показываем индикатор
    saveStatusDiv.className = 'mt-3 text-info'; // Сбрасываем классы статуса

    const formData = new FormData(saveForm); // Собираем данные формы
    const saveUrl = saveForm.getAttribute('action'); // URL для сохранения (save.php)

    // Отправляем данные с помощью Fetch API
    fetch(saveUrl, {
      method: 'POST',
      body: formData // FormData автоматически устанавливает правильный Content-Type
    })
    .then(response => {
      if (!response.ok) {
        return response.text().then(text => { throw new Error('Ошибка сети или сервера: ' + response.status + ' ' + response.statusText + ' - ' + text) });
      }
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return response.json(); // Парсим JSON ответ
      } else {
        return response.text().then(text => { throw new Error('Неожиданный формат ответа сервера: ' + text) });
      }
    })
    .then(data => {
      // Обрабатываем JSON ответ от save.php {success: true/false, message/error: '...'}
      if (data.success) {
        saveStatusDiv.innerHTML = data.message || 'Сохранено успешно!';
        saveStatusDiv.className = 'mt-3 text-success'; // Зеленый цвет
      } else {
        saveStatusDiv.innerHTML = 'Ошибка: ' + (data.error || 'Неизвестная ошибка сервера');
        saveStatusDiv.className = 'mt-3 text-danger'; // Красный цвет
      }
    })
    .catch(error => {
      console.error('Ошибка сохранения:', error);
      saveStatusDiv.innerHTML = 'Ошибка сохранения: ' + error.message;
      saveStatusDiv.className = 'mt-3 text-danger'; // Красный цвет
    })
    .finally(() => {
      saveButton.disabled = false; // Включаем кнопку обратно
      // Опционально: Сообщение о статусе исчезнет через несколько секунд
      // setTimeout(() => { saveStatusDiv.innerHTML = ''; saveStatusDiv.className = 'mt-3'; }, 7000);
    });
  });
});
</script>