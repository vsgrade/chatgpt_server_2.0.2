<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
require_once "../api/db.php"; // Подключаем базу данных
require_once "../api/config.php"; // Подключаем config для ключей шифрования

// Загружаем из базы только настройки для 'Настройки API' (API ключ)
$settings = $db->query("SELECT api_key FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Убедимся, что $settings является массивом, даже если строка настроек пуста
$settings = $settings ?: [];

// Функция расшифровки - нужна для API ключа
function decrypt($value) {
  // Используем константы из config.php, если они там есть, иначе хардкод
  $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "mysecretkey12345";
  $secret_iv = defined('SECRET_IV') ? SECRET_IV : "1234567891011121";
  return openssl_decrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv);
}
$settings["api_key"] = isset($settings["api_key"]) ? decrypt($settings["api_key"]) : "";
?>
<h3>⚙️ Настройки API</h3>
<form id="settingsForm" method="POST" action="save.php" class="mb-3">
    <div id="main">
        <div class="mb-3">
            <label for="api_key" class="form-label">API ключ</label>
            <input type="text" class="form-control" name="api_key" id="api_key" value="<?= htmlspecialchars($settings['api_key'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <button type="button" class="btn btn-outline-secondary ms-2" onclick="checkApi()">Проверить API</button>
        <div id="apiResult" class="mt-2 small"></div>
    </div>
    <div id="saveStatus" class="mt-3"></div>
</form>

<script>
// Существующая функция для проверки API
function checkApi() {
  const key = document.getElementById('api_key').value;
  // Получаем актуальные значения прокси из базы (путь правильный, т.к. test_api.php в той же папке)
  fetch('get_current_proxy.php')
    .then(res => res.json())
    .then(proxySettings => {
      fetch('test_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          api_key: key,
          proxy: proxySettings.proxy,
          proxy_type: proxySettings.proxy_type
        })
      })
      .then(res => res.text())
      .then(data => document.getElementById('apiResult').innerHTML = data)
      .catch(err => document.getElementById('apiResult').innerText = 'Ошибка: ' + err);
    });
}

// ДОБАВЛЕННЫЙ СКРИПТ ДЛЯ AJAX-СОХРАНЕНИЯ
document.addEventListener("DOMContentLoaded", function() {
  // Находим форму по ее ID
  const saveForm = document.getElementById('settingsForm'); // Используем ID формы
  // Находим кнопку отправки внутри этой формы
  const saveButton = saveForm.querySelector('button[type="submit"]');
  // Находим элемент для вывода статуса сохранения
  const saveStatusDiv = document.getElementById('saveStatus');

  // Добавляем обработчик события submit для формы
  saveForm.addEventListener('submit', function(event) {
    event.preventDefault(); // Предотвращаем стандартную отправку формы (которая перезагружает страницу)

    saveButton.disabled = true; // Отключаем кнопку на время отправки, чтобы избежать повторных нажатий
    saveStatusDiv.innerHTML = '<span class="text-info">Сохранение...</span>'; // Показываем индикатор сохранения
    saveStatusDiv.className = 'mt-3 text-info'; // Сбрасываем классы статуса и ставим индикатор

    const formData = new FormData(saveForm); // Собираем данные формы в объект FormData

    // Определяем URL для отправки данных (берем из атрибута action формы)
    const saveUrl = saveForm.getAttribute('action');

    // Отправляем данные с помощью Fetch API
    fetch(saveUrl, {
      method: 'POST', // Используем метод POST, как указано в форме
      body: formData // FormData автоматически устанавливает правильный Content-Type
    })
    .then(response => {
      // Проверяем, что HTTP статус ответа указывает на успех (2xx)
      if (!response.ok) {
        // Если ошибка HTTP, читаем текст ответа и бросаем исключение
        return response.text().then(text => {
          throw new Error('Ошибка сети или сервера: ' + response.status + ' ' + response.statusText + ' - ' + text);
        });
      }
       // Проверяем, что ответ в формате JSON
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return response.json(); // Парсим JSON ответ
      } else {
        // Если ответ не JSON, но статус OK, возможно, ошибка не в формате JSON
        return response.text().then(text => {
          throw new Error('Неожиданный формат ответа сервера: ' + text);
        });
      }
    })
    .then(data => {
      // Обрабатываем JSON ответ от сервера {success: true/false, message/error: '...'}
      if (data.success) {
        // Если success: true, показываем сообщение об успехе
        saveStatusDiv.innerHTML = data.message || 'Сохранено успешно!';
        saveStatusDiv.className = 'mt-3 text-success'; // Зеленый цвет для успеха
      } else {
        // Если success: false, показываем сообщение об ошибке
        saveStatusDiv.innerHTML = 'Ошибка: ' + (data.error || 'Неизвестная ошибка сервера');
        saveStatusDiv.className = 'mt-3 text-danger'; // Красный цвет для ошибки
      }
    })
    .catch(error => {
      // Обрабатываем любые ошибки, возникшие в процессе fetch или обработки ответа
      console.error('Ошибка сохранения:', error); // Логируем ошибку в консоль браузера
      saveStatusDiv.innerHTML = 'Ошибка сохранения: ' + error.message;
      saveStatusDiv.className = 'mt-3 text-danger'; // Красный цвет для ошибки
    })
    .finally(() => {
      // Этот блок выполняется всегда после завершения fetch (успех или ошибка)
      saveButton.disabled = false; // Включаем кнопку обратно

      // Опционально: Сообщение о статусе исчезнет через несколько секунд
      // setTimeout(() => { saveStatusDiv.innerHTML = ''; saveStatusDiv.className = 'mt-3'; }, 7000); // Скрыть через 7 секунд
    });
  });
});
</script>