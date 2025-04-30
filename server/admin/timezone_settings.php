<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
require_once "../api/db.php"; // Подключаем базу данных

// Загружаем настройку часового пояса
$settings = $db->query("SELECT timezone FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$settings = $settings ?: []; // Убедимся, что $settings является массивом
?>
<h3>📅 Настройка часового пояса</h3>
<form id="timezoneSettingsForm" method="POST" action="save.php" class="mb-3">
    <div id="main">
        <div class="mb-3">
            <label for="timezone" class="form-label">Часовой пояс:</label>
            <input type="text" class="form-control" name="timezone" id="timezone" placeholder="Например, Europe/Moscow" value="<?= htmlspecialchars($settings['timezone'] ?? 'UTC') ?>">
            <div class="form-text">Укажите ваш часовой пояс (например, Europe/Moscow, America/New_York, UTC). <a href="https://www.php.net/manual/ru/timezones.php" target="_blank">Список часовых поясов</a>.</div>
        </div>
    </div> <button type="submit" class="btn btn-primary">Сохранить</button>
    <div id="saveStatus" class="mt-3"></div>
</form>

<script>
// СКРИПТ ДЛЯ AJAX-СОХРАНЕНИЯ
document.addEventListener("DOMContentLoaded", function() {
  const saveForm = document.getElementById('timezoneSettingsForm'); // Используем ID формы
  const saveButton = saveForm.querySelector('button[type="submit"]');
  const saveStatusDiv = document.getElementById('saveStatus');

   if (!saveForm || !saveButton || !saveStatusDiv) {
      console.error("Timezone settings form elements not found!");
      return;
  }

  saveForm.addEventListener('submit', function(event) {
    event.preventDefault();

    saveButton.disabled = true;
    saveStatusDiv.innerHTML = '<span class="text-info">Сохранение...</span>';
    saveStatusDiv.className = 'mt-3 text-info';

    const formData = new FormData(saveForm);
    const saveUrl = saveForm.getAttribute('action');

    fetch(saveUrl, {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        return response.text().then(text => {
          throw new Error('Ошибка сети или сервера: ' + response.status + ' ' + response.statusText + ' - ' + text);
        });
      }
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return response.json();
      } else {
        return response.text().then(text => {
          throw new Error('Неожиданный формат ответа сервера: ' + text);
        });
      }
    })
    .then(data => {
      if (data.success) {
        saveStatusDiv.innerHTML = data.message || 'Сохранено успешно!';
        saveStatusDiv.className = 'mt-3 text-success';
      } else {
        saveStatusDiv.innerHTML = 'Ошибка: ' + (data.error || 'Неизвестная ошибка сервера');
        saveStatusDiv.className = 'mt-3 text-danger';
      }
    })
    .catch(error => {
      console.error('Ошибка сохранения:', error);
      saveStatusDiv.innerHTML = 'Ошибка сохранения: ' + error.message;
      saveStatusDiv.className = 'mt-3 text-danger';
    })
    .finally(() => {
      saveButton.disabled = false;
    });
  });
});
</script>