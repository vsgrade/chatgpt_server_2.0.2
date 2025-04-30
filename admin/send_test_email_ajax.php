<?php
session_start();
// Проверяем авторизацию администратора
if (!isset($_SESSION["admin"])) {
    http_response_code(403); // Отправляем статус 403 Forbidden
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// В этом скрипте мы читаем настройки SMTP из POST данных,
// которые приходят с формы emailSettingsForm,
// кроме пароля, если он не был введен в форме (тогда читаем из БД).
// Поэтому нам потребуется подключение к БД для получения сохраненного пароля.
require_once "../api/db.php"; // Подключаем базу данных
require_once "../api/config.php"; // Для ключей шифрования
require_once "../api/log.php"; // Для логирования ошибок

// Подключаем PHPMailer
// Убедитесь, что ЭТИ ПУТИ ПРАВИЛЬНЫ для ВАШЕГО сервера,
// основываясь на предоставленном вами test_mail.php, PHPMailer находится в папке libs в корне
require '../../libs/PHPMailer/src/PHPMailer.php';
require '../../libs/PHPMailer/src/SMTP.php';
require '../../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Функция расшифровки (используется для пароля из БД)
function decrypt($value) {
    // Используем константы из config.php
    $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "mysecretkey12345";
    $secret_iv = defined('SECRET_IV') ? SECRET_IV : "1234567891011121";
     if (empty($value) || !is_string($value)) return "";
    // В вашем старом decrypt была base64_decode. Если ваш сохраненный пароль кодировался в base64, раскомментируйте следующую строку.
    // $value = base64_decode($value);
    // if ($value === false) return ""; // Проверяем, что это base64
    $decrypted_value = openssl_decrypt($value, "AES-128-CTR", $secret_key, 0, $secret_iv);
     if ($decrypted_value === false) {
         log_message('WARNING', 'Decryption failed in send_test_email_ajax for value: ' . (is_string($value) ? $value : 'Not a string'), __FILE__, __LINE__);
         return ""; // Возвращаем пустую строку в случае ошибки расшифровки
     }
    return $decrypted_value;
}

header('Content-Type: application/json'); // Устанавливаем заголовок ответа как JSON

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получаем данные из POST запроса (из FormData) - используем smtp_ названия
    $recipient_email = $_POST["test_email_recipient"] ?? '';
    $smtp_host       = $_POST["smtp_host"] ?? '';
    $smtp_port       = intval($_POST["smtp_port"] ?? 0);
    // Значение чекбокса приходит как "1" или отсутствует. Приводим к булеву типу.
    $smtp_auth       = isset($_POST["smtp_auth"]) ? true : false;
    $smtp_username   = $_POST["smtp_username"] ?? '';
    $smtp_password_from_post   = $_POST["smtp_password"] ?? ''; // Пароль, введенный в форму
    $smtp_secure     = $_POST["smtp_secure"] ?? ''; // 'tls', 'ssl', или ''


    if (empty($recipient_email) || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Некорректный адрес получателя для теста.']);
        log_message('WARNING', 'Некорректный адрес получателя для теста: ' . $recipient_email, __FILE__, __LINE__);
        exit;
    }

    // Проверяем наличие минимальных настроек SMTP, которые пришли в POST
     // Если аутентификация включена, логин должен быть заполнен
    if (empty($smtp_host) || empty($smtp_port) || ($smtp_auth && empty($smtp_username))) {
         echo json_encode(['success' => false, 'error' => 'Неполные настройки SMTP. Укажите хост, порт, логин (если включена аутентификация).']);
         log_message('WARNING', 'Неполные настройки SMTP в AJAX запросе. Host: ' . $smtp_host . ', Port: ' . $smtp_port . ', Auth: ' . ($smtp_auth ? 'true' : 'false') . ', User: ' . $smtp_username, __FILE__, __LINE__);
         exit;
    }

    // --- Получение пароля для SMTP ---
    $smtp_password = '';
    if (!empty($smtp_password_from_post)) {
        // Если пароль введен в форму при тесте, используем его напрямую
         $smtp_password = $smtp_password_from_post;
         // log_message('DEBUG', 'Using SMTP password from form.', __FILE__, __LINE__); // Не логируем пароль!
    } elseif ($smtp_auth) { // Если аутентификация включена и пароль из формы пустой, берем из БД
        // Если пароль не введен в форму, загружаем его из БД и расшифровываем
        try {
            // Используем глобальную переменную $db
            global $db;
            $stmt = $db->prepare("SELECT smtp_password FROM settings LIMIT 1");
            $stmt->execute();
            $saved_password_encrypted = $stmt->fetchColumn();
            if ($saved_password_encrypted) {
                $smtp_password = decrypt($saved_password_encrypted);
                // Проверка, что расшифровка успешна (decrypt вернет пустую строку при ошибке)
                if (empty($smtp_password) && !empty($saved_password_encrypted)) {
                     log_message('ERROR', 'Decrypted password from DB is empty, but encrypted is not.', __FILE__, __LINE__);
                     echo json_encode(['success' => false, 'error' => 'Не удалось расшифровать сохраненный пароль SMTP. Возможно, изменились ключи шифрования (SECRET_KEY/SECRET_IV). Попробуйте ввести пароль заново в настройках и сохранить.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                 // log_message('DEBUG', 'Using SMTP password from DB.', __FILE__, __LINE__); // Не логируем пароль!
            } else {
                 // Пароль не введен в форму и не найден в БД, но аутентификация включена
                 log_message('WARNING', 'SMTP password is not provided (form/DB), but auth is enabled.', __FILE__, __LINE__);
                 echo json_encode(['success' => false, 'error' => 'Требуется аутентификация SMTP, но пароль не указан и не найден в базе данных.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } catch (PDOException $e) {
            log_message('ERROR', 'DB error when retrieving SMTP password: ' . $e->getMessage(), __FILE__, __LINE__);
            echo json_encode(['success' => false, 'error' => 'Ошибка базы данных при получении пароля: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            log_message('ERROR', 'Exception during SMTP password retrieval/decryption: ' . $e->getMessage(), __FILE__, __LINE__);
            echo json_encode(['success' => false, 'error' => 'Произошла ошибка при получении пароля: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    // Если $smtp_auth выключен, $smtp_password может остаться пустым, и это нормально.
    // ===============================


    $mail = new PHPMailer(true); // Создаем новый экземпляр, true разрешает выброс исключений при ошибках

    // **НАСТРОЙКА ОТЛАДКИ SMTP**
    // Включите временно (например, SMTP::DEBUG_SERVER), если возникнут ошибки подключения
    // Установите в 0 на рабочем сайте!
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // 0 = off, 1 = client, 2 = client and server


    try {
        $mail->isSMTP(); // Отправка через SMTP
        $mail->Host       = $smtp_host; // Хост из формы/БД
        $mail->Port       = $smtp_port; // Порт из формы/БД
        $mail->SMTPAuth   = $smtp_auth; // Включите SMTP аутентификацию

        if ($mail->SMTPAuth) {
             $mail->Username   = $smtp_username; // Имя пользователя из формы/БД
             $mail->Password   = $smtp_password; // Пароль (из формы или БД)
        }

        // Настройка шифрования
        if (!empty($smtp_secure)) {
             $mail->SMTPSecure = $smtp_secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;

             // Опционально: Настройка опций SSL/TLS, если возникают проблемы с сертификатами
             // $mail->SMTPOptions = array(
             //    'ssl' => array(
             //        'verify_peer' => false,
             //        'verify_peer_name' => false,
             //        'allow_self_signed' => true
             //    )
             // );
        } else {
            $mail->SMTPSecure = false; // Без шифрования
        }


        // Recipient - Отправляем на адрес из поля "Получатель тестового Email"
        // Отправитель - Имя пользователя SMTP, если задано, иначе noreply@example.com
        $sender_email = !empty($smtp_username) ? $smtp_username : 'noreply@example.com';
        // Дополнительная проверка, что адрес отправителя является валидным email
        if (!filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
             $sender_email = 'noreply@example.com';
             log_message('WARNING', 'SMTP username "' . $smtp_username . '" не является валидным email адресом отправителя для теста. Использован noreply@example.com', __FILE__, __LINE__);
        }
        // Имя отправителя можно взять из другого поля формы, если оно есть, или использовать email
        // В вашей старой форме было поле mail_from_name. Если вы добавите его в emailSettingsForm
        // под именем smtp_from_name, можно получить его так:
        // $smtp_from_name = $_POST["smtp_from_name"] ?? '';
        // $mail->setFrom($sender_email, !empty($smtp_from_name) ? $smtp_from_name : $sender_email);
        // Пока используем просто email отправителя как имя
        $mail->setFrom($sender_email, $sender_email); // Отправитель
        $mail->addAddress($recipient_email);     // Получатель тестового письма


        // Content
        $mail->CharSet = 'UTF-8'; // Кодировка письма
        $mail->isHTML(true); // Тестовое письмо будет HTML, как в вашем старом примере
        $mail->Subject = "Тестовое письмо из админки ChatGPT";
        $mail->Body    = 'Это тестовое письмо для проверки настроек SMTP из админ-панели.';
        $mail->AltBody = 'Это тестовое письмо для проверки настроек SMTP из админ-панели.';


        // *** В этом месте происходит попытка подключения, аутентификации и отправка ***
        $mail->send();

        // Если отправка успешна, выводим JSON ответ об успехе
        echo json_encode(['success' => true, 'message' => 'Тестовое письмо успешно отправлено на адрес ' . htmlspecialchars($recipient_email) . '. Проверьте почтовый ящик.']);
        log_message('INFO', 'Тестовое письмо успешно отправлено на адрес ' . $recipient_email . ' с настройками: Host=' . $smtp_host . ', Port=' . $smtp_port . ', Auth=' . ($smtp_auth ? 'true' : 'false') . ', User=' . $smtp_username . ', Secure=' . $smtp_secure, __FILE__, __LINE__);

    } catch (Exception $e) {
        // Получаем информацию об ошибке PHPMailer и общее сообщение исключения
        $error_message = $mail->ErrorInfo ?: $e->getMessage();
        echo json_encode(['success' => false, 'error' => "Ошибка при отправке письма: " . $error_message, 'details' => $e->getMessage()]);
        log_message('ERROR', 'Ошибка при отправке тестового письма: ' . $error_message . ' | Exception: ' . $e->getMessage(), __FILE__, __LINE__);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса.']);
    log_message('WARNING', 'Received non-POST request to send_test_email_ajax.php', __FILE__, __LINE__);
}
?>