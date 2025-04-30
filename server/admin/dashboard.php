<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
require_once "../api/db.php"; // Подключаем базу данных

// --- Получение данных для дашборда ---
$total_users = 0;
$total_chats = 0;
$total_messages = 0;
$total_tokens_used = 0;
$users_today = 0;
$tokens_today = 0;
$recent_errors = []; // Будем хранить последние несколько ошибок

try {
    // Общее количество пользователей
    $stmt = $db->query("SELECT COUNT(id) FROM users");
    $total_users = $stmt->fetchColumn();

    // Общее количество чатов
    $stmt = $db->query("SELECT COUNT(id) FROM chats");
    $total_chats = $stmt->fetchColumn();

    // Общее количество сообщений
    $stmt = $db->query("SELECT COUNT(id) FROM messages");
    $total_messages = $stmt->fetchColumn();

    // Общее количество использованных токенов
    $stmt = $db->query("SELECT SUM(tokens) FROM token_usage");
    $total_tokens_used = $stmt->fetchColumn() ?: 0; // Если SUM вернул null (нет записей), используем 0

    // Новые пользователи за сегодня
    $stmt = $db->query("SELECT COUNT(id) FROM users WHERE created_at >= CURRENT_DATE");
    $users_today = $stmt->fetchColumn();

    // Использовано токенов за сегодня
    $stmt = $db->query("SELECT SUM(tokens) FROM token_usage WHERE created_at >= CURRENT_DATE");
    $tokens_today = $stmt->fetchColumn() ?: 0; // Если SUM вернул null, используем 0

    // Последние 5 ошибок из логов
    $stmt = $db->query("SELECT level, message, created_at FROM error_logs ORDER BY created_at DESC LIMIT 5");
    $recent_errors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Если произошла ошибка БД при загрузке данных
    $error_message = "Ошибка базы данных при загрузке данных дашборда: " . htmlspecialchars($e->getMessage());
    // Можно также логировать эту ошибку
    // require_once "../api/log.php";
    // log_message('ERROR', $error_message, __FILE__, __LINE__);
} catch (Exception $e) {
     $error_message = "Произошла ошибка при загрузке данных дашборда: " . htmlspecialchars($e->getMessage());
     // require_once "../api/log.php";
     // log_message('ERROR', $error_message, __FILE__, __LINE__);
}

// --- Конец получения данных ---
?>
<h3>📊 Дашборд</h3>
<p>Здесь отображаются основные метрики работы системы.</p>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?= $error_message ?></div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Пользователи</h5>
                <p class="card-text"><strong>Всего:</strong> <?= $total_users ?></p>
                <p class="card-text"><strong>За сегодня:</strong> <?= $users_today ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Чаты и сообщения</h5>
                <p class="card-text"><strong>Всего чатов:</strong> <?= $total_chats ?></p>
                <p class="card-text"><strong>Всего сообщений:</strong> <?= $total_messages ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Использование токенов</h5>
                <p class="card-text"><strong>Всего:</strong> <?= number_format($total_tokens_used) ?></p>
                <p class="card-text"><strong>За сегодня:</strong> <?= number_format($tokens_today) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
     <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Последние ошибки (из логов)</h5>
                <?php if (!empty($recent_errors)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recent_errors as $error): ?>
                            <?php
                            // --- Время выводится как есть из базы данных ---
                            $formatted_time = htmlspecialchars($error['created_at']);
                            ?>
                            <li class="list-group-item">
                                <small class="text-muted"><?= $formatted_time ?></small><br> <span class="badge bg-<?= $error['level'] === 'ERROR' || $error['level'] === 'CRITICAL' ? 'danger' : ($error['level'] === 'WARNING' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($error['level']) ?></span>
                                <?= htmlspecialchars(substr($error['message'], 0, 200)) ?>... <a href="?page=logs" class="small ms-2">Подробнее</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="card-text">Ошибок не найдено за последнее время.</p>
                <?php endif; ?>
                 <?php if (isset($error_message)): ?>
                     <p class="text-danger mt-2">Не удалось загрузить ошибки из-за ошибки БД при полузке данных дашборда.</p>
                 <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Секции для графиков оставлены пустыми, если вы захотите добавить JS графики позже
?>