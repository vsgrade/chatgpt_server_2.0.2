<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
require_once "../api/db.php"; // Подключаем базу данных

// Загружаем из базы только настройку времени хранения чатов
$settings = $db->query("SELECT chat_ttl_days FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Убедимся, что $settings является массивом, даже если строка настроек пуста
$settings = $settings ?: [];

// Получаем значение, по умолчанию 30 дней, если не задано или некорректно
$chat_ttl_days = isset($settings["chat_ttl_days"]) ? (int)$settings["chat_ttl_days"] : 30;
if ($chat_ttl_days < 1) $chat_ttl_days = 1; // Гарантируем минимум 1 день
?>
<h3>⏳ Время хранения чатов</h3>
<form id="chatTtlForm" method="POST" action="save.php">
  <div class="mb-3">
    <label for="chat_ttl_days" class="form-label">Время хранения неактивных чатов (дней)</label>
    <input type="number" class="form-control" name="chat_ttl_days" id="chat_ttl_days" min="1" value="<?= htmlspecialchars($chat_ttl_days) ?>">
    <div class="form-text">Чаты без сообщений будут удаляться через указанное число дней.</div>
  </div>
  <button type="submit" class="btn btn-primary">Сохранить</button>
   <div id="saveStatus" class="mt-3"></div>
</form>

<script>
// ДОБАВЛЕННЫЙ СКРИПТ ДЛЯ AJAX-СОХРАНЕНИЯ
document.addEventListener("DOMContentLoaded", function() {
  // Находим форму по ее ID
  const saveForm = document.getElementById('chatTtlForm'); // Используем ID формы
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