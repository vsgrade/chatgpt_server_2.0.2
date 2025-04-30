<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require_once "../api/db.php"; // Подключаем базу данных
require_once "../api/config.php"; // Подключаем config для ключей шифрования
require_once "../api/log.php"; // Для логирования ошибок

// Функция расшифровки - нужна для пароля SMTP
function decrypt($value) {
  // Используем константы из config.php, если они там есть, иначе хардкод
  $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "mysecretkey12345";
  $secret_iv = defined('SECRET_IV') ? SECRET_IV : "1234567891011121";
  // Добавлена базовая проверка на пустую или некорректную строку
  if (empty($value) || !is_string($value)) return "";
  // В вашем старом decrypt была base64_decode. Если ваш сохраненный пароль кодировался в base64, раскомментируйте следующую строку.
  // $value = base64_decode($value);
  // if ($value === false) return ""; // Проверяем, что это base64
  $decrypted_value = openssl_decrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv);
  if ($decrypted_value === false) {
       // Можно добавить логирование ошибки расшифровки
       log_message('WARNING', 'Decryption failed', __FILE__, __LINE__);
       return ""; // Ошибка расшифровки
  }
  return $decrypted_value;
}


// --- ЗАГРУЗКА НАСТРОЕК ПОЧТЫ ИЗ БАЗЫ ДАННЫХ (используем поля с префиксом smtp_) ---
try {
    $stmt = $db->prepare("SELECT smtp_host, smtp_port, smtp_auth, smtp_username, smtp_password, smtp_secure, test_email_recipient FROM settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Убедимся, что $settings является массивом, даже если строка настроек пуста
    $settings = $settings ?: [];

    // Расшифровываем пароль, если он существует (не выводим в форму, но может пригодиться для отладки)
    // $smtp_password_decrypted = isset($settings['smtp_password']) ? decrypt($settings['smtp_password']) : '';

} catch (PDOException $e) {
    // Если произошла ошибка БД при загрузке, можно вывести сообщение
    echo "<div class='alert alert-danger'>Ошибка загрузки настроек почты: " . htmlspecialchars($e->getMessage()) . "</div>";
    $settings = []; // Очищаем настройки, чтобы не пытаться их использовать
    // $smtp_password_decrypted = '';
     log_message('ERROR', 'Database error loading email settings: ' . $e->getMessage(), __FILE__, __LINE__);
} catch (Exception $e) {
     echo "<div class='alert alert-danger'>Произошла ошибка при загрузке настроек почты: " . htmlspecialchars($e->getMessage()) . "</div>";
     $settings = [];
     // $smtp_password_decrypted = '';
     log_message('ERROR', 'Exception loading email settings: ' . $e->getMessage(), __FILE__, __LINE__);
}

// =============================================

// Заголовок страницы для этого раздела
echo '<h3>📧 Настройка Email (SMTP)</h3>';

// HTML-форма для настроек почты
?>

<div class="card mb-4">
    <div class="card-header">
        Настройки подключения и отправителя
    </div>
    <form id="emailSettingsForm" method="POST" action="save.php"> <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group mb-3">
                        <label for="smtp_host" class="form-label">SMTP Хост:</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="Например, smtp.mail.ru" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-8">
                     <div class="form-group mb-3">
                        <label for="smtp_port" class="form-label">SMTP Порт:</label>
                         <input type="number" class="form-control" id="smtp_port" name="smtp_port" placeholder="Например, 465 или 587" value="<?= htmlspecialchars($settings['smtp_port'] ?? '') ?>">
                    </div>
                </div>
                 <div class="col-md-8">
                     <div class="form-group mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="smtp_auth" id="smtp_auth" value="1" <?= isset($settings['smtp_auth']) && $settings['smtp_auth'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="smtp_auth">Требуется аутентификация SMTP</label>
                    </div>
                 </div>
                <div class="col-md-8">
                     <div class="form-group mb-3">
                        <label for="smtp_username" class="form-label">Имя пользователя SMTP:</label>
                         <input type="text" class="form-control" id="smtp_username" name="smtp_username" placeholder="Ваш полный email" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group mb-3">
                        <label for="smtp_password" class="form-label">Пароль SMTP:</label>
                         <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Введите пароль, если хотите изменить">
                        <small class="form-text text-muted">Для безопасности поле не заполняется при загрузке. Введите пароль только если хотите его изменить. Если у вас 2ФА, используйте пароль приложения.</small>
                    </div>
                </div>
                 <div class="col-md-8">
                    <div class="form-group mb-3">
                        <label for="smtp_secure" class="form-label">SMTP Шифрование</label>
                        <select class="form-select" name="smtp_secure" id="smtp_secure">
                            <option value="">Без шифрования</option>
                            <option value="tls" <?= ($settings['smtp_secure'] ?? '') === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
                            <option value="ssl" <?= ($settings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SMTPS (SSL)</option>
                        </select>
                         <small class="form-text text-muted">Выберите тип шифрования, обычно STARTTLS для порта 587 или SMTPS для 465.</small>
                    </div>
                 </div>
                 <div class="col-md-8">
                    <div class="form-group mb-3">
                        <label for="test_email_recipient" class="form-label">Получатель тестового Email:</label>
                         <input type="email" class="form-control" id="test_email_recipient" name="test_email_recipient" placeholder="Email для отправки тестового письма" value="<?= htmlspecialchars($settings['test_email_recipient'] ?? '') ?>">
                        <small class="form-text text-muted">На этот адрес будет отправлено письмо при нажатии кнопки "Тестировать отправку".</small>
                    </div>
                 </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Сохранить настройки почты</button>
            <button type="button" class="btn btn-info mt-3" id="testEmailButton">Тестировать отправку</button> <div id="saveStatus" class="mt-3"></div>
            <div id="testEmailResult" class="mt-3"></div> </div>
    </form>
</div>

<?php
// Конец файла - далее JS скрипты
?>

<script>
// СКРИПТ ДЛЯ AJAX-СОХРАНЕНИЯ
document.addEventListener("DOMContentLoaded", function() {
  const saveForm = document.getElementById('emailSettingsForm'); // Используем ID формы emailSettingsForm
  const saveButton = saveForm.querySelector('button[type="submit"]');
  const saveStatusDiv = document.getElementById('saveStatus');

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
        saveStatusDiv.innerHTML = data.message || 'Настройки успешно сохранены!';
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
      // Опционально: Сообщение о статусе исчезнет через несколько секунд
      // setTimeout(() => { saveStatusDiv.innerHTML = ''; saveStatusDiv.className = 'mt-3'; }, 7000);
    });
  });
});

// СКРИПТ ДЛЯ ТЕСТА EMAIL
document.addEventListener("DOMContentLoaded", function() {
    const testButton = document.getElementById('testEmailButton'); // Кнопка "Тестировать отправку"
    const testResultDiv = document.getElementById('testEmailResult'); // Элемент для вывода результата

    if (testButton) {
        testButton.addEventListener('click', function() {
            const recipientEmailInput = document.getElementById('test_email_recipient');
            const recipientEmail = recipientEmailInput ? recipientEmailInput.value.trim() : '';

            if (!recipientEmail) {
                testResultDiv.innerHTML = '<span class="text-danger">Введите адрес получателя для теста.</span>';
                testResultDiv.className = 'mt-2 small text-danger';
                return;
            }

            // Собираем данные формы для отправки настроек и получателя
            // FormData автоматически включает все поля формы с их текущими значениями
            const formData = new FormData(document.getElementById('emailSettingsForm'));

            testButton.disabled = true; // Отключаем кнопку
            testResultDiv.innerHTML = '<span class="text-info">Отправка тестового письма...</span>'; // Индикатор отправки
            testResultDiv.className = 'mt-2 small text-info';

            // Отправляем данные на обработчик AJAX теста
            // Используем имя файла, как в вашем старом test_mail.php, но адаптируем его
            fetch('send_test_email_ajax.php', { // Этот файл мы создадим на основе вашего test_mail.php
                method: 'POST',
                // FormData автоматически устанавливает правильный Content-Type (multipart/form-data)
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    // Если ошибка HTTP (например 403 Forbidden, 404 Not Found, 500 Internal Server Error)
                     // Попытаемся прочитать ответ как текст, чтобы увидеть возможные ошибки PHP
                    return response.text().then(text => {
                        console.error('HTTP error response text:', text); // Логируем текст ошибки
                        throw new Error('HTTP error ' + response.status + ': ' + text);
                    });
                }
                 // Если статус OK (2xx), ожидаем JSON
                return response.json();
            })
            .then(data => {
                // Обрабатываем JSON ответ от send_test_email_ajax.php
                if (data.success) {
                    testResultDiv.innerHTML = '<span class="text-success">✅ ' + (data.message || 'Тестовое письмо успешно отправлено.') + '</span>';
                    testResultDiv.className = 'mt-2 small text-success';
                } else {
                    testResultDiv.innerHTML = '<span class="text-danger">❌ Ошибка: ' + (data.error || 'Неизвестная ошибка при отправке.') + '</span>';
                    testResultDiv.className = 'mt-2 small text-danger';
                     console.error('Test email error details:', data.details); // Логируем детали ошибки
                }
            })
            .catch(err => {
                 console.error('Test email fetch error:', err);
                testResultDiv.innerHTML = '<span class="text-danger">❌ Ошибка: ' + err.message + '</span>';
                testResultDiv.className = 'mt-2 small text-danger';
            })
            .finally(() => {
                testButton.disabled = false; // Включаем кнопку обратно
            });
        });
    }
});
</script>