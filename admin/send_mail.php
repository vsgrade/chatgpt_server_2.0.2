<?php
// Подключаем PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Путь к файлу для сохранения данных
$settings_file = 'settings.json';

// Загружаем ранее сохранённые данные
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
}

// Если отправлена форма
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $smtp_server = $_POST['smtp_server'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_encryption = $_POST['smtp_encryption']; // Новое поле
    $smtp_username = $_POST['smtp_username'];
    $smtp_password = $_POST['smtp_password'];
    $from_email = $_POST['from_email'];
    $to_email = $_POST['to_email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Сохраняем введенные данные в файл
    $settings_to_save = [
        'smtp_server' => $smtp_server,
        'smtp_port' => $smtp_port,
        'smtp_encryption' => $smtp_encryption,
        'smtp_username' => $smtp_username,
        'smtp_password' => $smtp_password,
        'from_email' => $from_email,
        'to_email' => $to_email,
        'subject' => $subject,
        'message' => $message,
    ];
    file_put_contents($settings_file, json_encode($settings_to_save, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Проверяем какая кнопка была нажата
    if (isset($_POST['send_mail'])) {
        // Отправляем письмо
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_server;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;

            if ($smtp_encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = (int)$smtp_port;

            $mail->setFrom($from_email, 'Mailer');
            $mail->addAddress($to_email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = nl2br(htmlspecialchars($message));
            $mail->AltBody = strip_tags($message);

            $mail->send();
            echo "<p style='color:green;'>Письмо успешно отправлено!</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>Ошибка отправки письма: {$mail->ErrorInfo}</p>";
        }
    } elseif (isset($_POST['save_settings'])) {
        echo "<p style='color:blue;'>Настройки успешно сохранены!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отправка письма через SMTP</title>
</head>
<body>
    <h1>Отправка письма через SMTP</h1>
    <form method="POST">
        <label>SMTP сервер:</label><br>
        <input type="text" name="smtp_server" value="<?= htmlspecialchars($settings['smtp_server'] ?? 'smtp.mail.ru') ?>" required><br><br>

        <label>SMTP порт:</label><br>
        <input type="text" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" required><br><br>

        <label>Тип шифрования:</label><br>
        <select name="smtp_encryption" required>
            <option value="tls" <?= (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] === 'tls') ? 'selected' : '' ?>>TLS (STARTTLS)</option>
            <option value="ssl" <?= (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] === 'ssl') ? 'selected' : '' ?>>SSL</option>
        </select><br><br>

        <label>SMTP логин (email):</label><br>
        <input type="email" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" required><br><br>

        <label>SMTP пароль:</label><br>
        <input type="password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" required><br><br>

        <label>От кого (email):</label><br>
        <input type="email" name="from_email" value="<?= htmlspecialchars($settings['from_email'] ?? '') ?>" required><br><br>

        <label>Кому (email):</label><br>
        <input type="email" name="to_email" value="<?= htmlspecialchars($settings['to_email'] ?? '') ?>" required><br><br>

        <label>Тема письма:</label><br>
        <input type="text" name="subject" value="<?= htmlspecialchars($settings['subject'] ?? '') ?>" required><br><br>

        <label>Сообщение:</label><br>
        <textarea name="message" rows="8" cols="50" required><?= htmlspecialchars($settings['message'] ?? '') ?></textarea><br><br>

        <button type="submit" name="save_settings">Сохранить настройки</button>
        <button type="submit" name="send_mail">Отправить письмо</button>
    </form>
</body>
</html>
