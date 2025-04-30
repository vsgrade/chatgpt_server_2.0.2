<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require_once "../api/db.php";
require_once "../api/config.php"; // Убедитесь, что этот файл содержит константы для ключей шифрования
require_once "../api/log.php"; // Для логирования ошибок
require '../vendor/autoload.php'; // Если вы используете Composer для PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$message = "";
$message_type = ""; // 'success' or 'danger'

// Функция расшифровки - нужна для пароля SMTP (если он хранится в зашифрованном виде)
function decrypt($value) {
    // Используем константы из config.php, если они там есть, иначе хардкод
    $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "mysecretkey12345"; // Использовать тот же ключ, что и для других настроек
    $secret_iv = defined('SECRET_IV') ? SECRET_IV : "1234567891011121"; // Использовать тот же IV
    if (empty($value) || !is_string($value)) return "";
    return openssl_decrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv);
}

// Загружаем настройки SMTP и тестового получателя из базы данных
$settings = $db->query("SELECT test_email_recipient, smtp_host, smtp_port, smtp_auth, smtp_username, smtp_password, smtp_secure FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$settings = $settings ?: []; // Убедимся, что $settings является массивом

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $recipient_email = $_POST["recipient_email"] ?? '';

    if (empty($recipient_email) || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Пожалуйста, введите корректный адрес электронной почты получателя.";
        $message_type = "danger";
    } else {
        // Проверяем наличие минимальных настроек SMTP
        if (empty($settings['smtp_host']) || empty($settings['smtp_port']) || ($settings['smtp_auth'] && empty($settings['smtp_username']))) {
             $message = "Настройки SMTP неполны. Проверьте раздел Настройки API.";
             $message_type = "danger";
             log_message('ERROR', 'Неполные настройки SMTP для тестового письма. Host: ' . ($settings['smtp_host'] ?? 'NULL') . ', Port: ' . ($settings['smtp_port'] ?? 'NULL') . ', Auth: ' . ($settings['smtp_auth'] ?? 'NULL') . ', User: ' . ($settings['smtp_username'] ?? 'NULL'), __FILE__, __LINE__);
        } else {
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Включите подробный вывод отладки, если нужно
                $mail->isSMTP();
                $mail->Host       = $settings['smtp_host'];
                $mail->Port       = intval($settings['smtp_port']); // Приводим порт к int
                $mail->SMTPAuth   = (bool)($settings['smtp_auth'] ?? false); // Требуется аутентификация SMTP

                if ($mail->SMTPAuth) {
                     $mail->Username   = $settings['smtp_username'];
                     $mail->Password   = decrypt($settings['smtp_password']); // Расшифровываем пароль
                }

                // Настройка шифрования
                if (!empty($settings['smtp_secure'])) {
                     $mail->SMTPSecure = $settings['smtp_secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = false; // Без шифрования
                }


                //Recipients
                // Используем smtp_username в качестве отправителя, если он задан, иначе можно использовать любой другой адрес
                $sender_email = !empty($settings['smtp_username']) ? $settings['smtp_username'] : 'noreply@example.com'; // Укажите отправителя по умолчанию, если имя пользователя SMTP не используется как адрес
                $mail->setFrom($sender_email, 'ChatGPT Admin Test'); // Отправитель
                $mail->addAddress($recipient_email);     // Получатель

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Тестовое письмо из админки ChatGPT';
                $mail->Body    = 'Это тестовое письмо для проверки настроек SMTP из админ-панели.';
                $mail->AltBody = 'Это тестовое письмо для проверки настроек SMTP из админ-панели.';

                $mail->send();
                $message = "Тестовое письмо успешно отправлено на адрес " . htmlspecialchars($recipient_email);
                $message_type = "success";
                log_message('INFO', 'Тестовое письмо успешно отправлено на адрес ' . $recipient_email, __FILE__, __LINE__);

            } catch (Exception $e) {
                $message = "Ошибка при отправке письма: {$mail->ErrorInfo}";
                $message_type = "danger";
                log_message('ERROR', 'Ошибка при отправке тестового письма: ' . $mail->ErrorInfo, __FILE__, __LINE__);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
  <div class="container">
    <div class="card mx-auto shadow-sm" style="max-width: 500px;">
      <div class="card-body">
        <h4 class="card-title mb-4 text-center">Отправка тестового Email</h4>
        <?php if ($message): ?>
          <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label for="recipient_email" class="form-label">Email получателя</label>
            <input type="email" name="recipient_email" id="recipient_email" class="form-control" required value="<?= htmlspecialchars($_POST['recipient_email'] ?? ($settings['test_email_recipient'] ?? '')) ?>">
            <div class="form-text">Письмо будет отправлено на этот адрес с использованием настроек SMTP из раздела "Настройки API и Email".</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Отправить тестовое письмо</button>
          <a href="index.php?page=settings" class="btn btn-link w-100 mt-2">← Настройки API и Email</a>
        </form>
      </div>
    </div>
  </div>
</body>
</html>