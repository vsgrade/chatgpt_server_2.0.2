<?php
session_start();
if (!isset($_SESSION["admin"])) {
  header("Location: login.php");
  exit;
}
require_once "../api/db.php"; // Подключаем базу данных
// Не нужен require_once "../api/config.php"; здесь, т.к. не используем decrypt


// Логика загрузки моделей остается прежней
?>
<h3>🤖 Управление моделями OpenAI</h3>
<form id="modelsForm" method="post" action="save_models.php">
  <div class="mb-3">
    <button type="button" class="btn btn-outline-secondary" onclick="loadOpenAIModels()">Загрузить с OpenAI</button>
  </div>
  <div id="modelsList">
    <div>Загрузка списка...</div>
  </div>
  <button type="submit" class="btn btn-primary mt-3">Сохранить выбранные модели</button>
  <div id="saveStatus" class="mt-3"></div>
</form>

<script>
// Существующие функции для загрузки и отображения моделей
function loadOpenAIModels() {
  document.getElementById('modelsList').innerHTML = "Загрузка...";
  fetch("load_openai_models.php")
    .then(res => res.json())
    .then(models => renderModelsList(models))
    .catch(e => {
      document.getElementById('modelsList').innerHTML = "Ошибка загрузки: " + e;
    });
}
function renderModelsList(models) {
  if (!Array.isArray(models)) {
    document.getElementById('modelsList').innerHTML =
      '<div class="text-danger">Ошибка: ' + (models && models.error ? models.error : 'Неизвестный формат ответа') + '</div>';
    return;
  }
  fetch('get_enabled_models.php')
    .then(res => res.json())
    .then(enabled => {
      let html = '<div class="row">';
      models.forEach(m => {
        let checked = enabled.includes(m.id) ? 'checked' : '';
        html += `
        <div class="col-12 col-md-6 mb-2">
          <label>
            <input type="checkbox" name="models[]" value="${m.id}" ${checked}> ${m.name} <span class="text-muted small">(${m.id})</span>
          </label>
        </div>`;
      });
      html += '</div>';
      document.getElementById('modelsList').innerHTML = html;
    });
}
document.addEventListener("DOMContentLoaded", function () {
  fetch('get_enabled_models.php')
    .then(res => res.json())
    .then(enabled => renderModelsList(
        enabled.map(id => ({id, name: id}))
      ));
});


// ДОБАВЛЕННЫЙ СКРИПТ ДЛЯ AJAX-СОХРАНЕНИЯ
document.addEventListener("DOMContentLoaded", function() {
  // Находим форму по ее ID (уже был modelsForm)
  const saveForm = document.getElementById('modelsForm'); // Используем ID формы modelsForm
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

    // Определяем URL для отправки данных (берем из атрибута action формы - save_models.php)
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