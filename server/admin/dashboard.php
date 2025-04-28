<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
require_once "../api/db.php"; // Подключаем базу данных (если понадобится для данных дашборда)

// Здесь в будущем будет код для загрузки данных из базы данных
// и подготовки их для отображения на дашборде (например, подсчет пользователей,
// суммарное использование токенов за период, количество ошибок и т.д.)

// Пока просто placeholder
?>
<h3>📊 Дашборд</h3> <p>На этой странице будут отображаться основные метрики работы системы.</p>
<p>В дальнейшем здесь появятся графики использования токенов, статистика по пользователям, сводка ошибок и другая полезная информация.</p>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">График использования токенов (за сегодня)</h5>
                <p class="card-text">Место для графика...</p>
                <div id="daily-usage-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Статистика пользователей</h5>
                <p class="card-text">Общее количество пользователей: ...</p>
                <p class="card-text">Новых за неделю: ...</p>
            </div>
        </div>
    </div>
</div>
<div class="row mt-4">
     <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Последние ошибки</h5>
                <p class="card-text">Список последних ошибок или их сводка...</p>
                 <div id="recent-errors-summary"></div>
            </div>
        </div>
    </div>
</div>