<nav class="nav flex-column p-3 gap-2">
  <div class="fs-5 mb-2">🤖 ChatGPT Admin</div>

  <?php
    // Определяем текущую страницу из URL
    // По умолчанию settings, так как это первая страница настроек API
    $current_page = $_GET['page'] ?? 'settings'; // По умолчанию settings

    // Определяем страницы, которые относятся к главному разделу "Настройки"
    // Это все страницы, кроме dashboard, diagnostics, logs, logout И timezone_settings
    $main_settings_pages = ['settings', 'email_settings', 'limits', 'chat_ttl', 'models', 'proxy', 'admin']; // Удален timezone_settings

    // Главный раздел "Настройки" должен быть активен и раскрыт, если активна любая страница внутри него
    $is_main_settings_collapse_active = in_array($current_page, $main_settings_pages);

  ?>

  <a href="?page=dashboard" class="nav-link text-white <?= $current_page === 'dashboard' ? 'active bg-secondary' : '' ?>">📊 Дашборд</a>

  <a href="#mainSettingsCollapse" data-bs-toggle="collapse" aria-expanded="<?= $is_main_settings_collapse_active ? 'true' : 'false' ?>"
     class="nav-link text-white <?= $is_main_settings_collapse_active ? 'active bg-secondary' : '' ?>">
    ⚙️ Настройки
  </a>

  <div class="collapse <?= $is_main_settings_collapse_active ? 'show' : '' ?>" id="mainSettingsCollapse">
    <ul class="nav flex-column ms-3"> <li class="nav-item">
        <a href="?page=settings" class="nav-link text-white <?= $current_page === 'settings' ? 'active bg-secondary' : '' ?>">⚙️ Настройки API</a>
      </li>

       <li class="nav-item">
        <a href="?page=email_settings" class="nav-link text-white <?= $current_page === 'email_settings' ? 'active bg-secondary' : '' ?>">✉️ Настройки Email</a>
      </li>
       <li class="nav-item">
        <a href="?page=limits" class="nav-link text-white <?= $current_page === 'limits' ? 'active bg-secondary' : '' ?>">📈 Лимиты</a>
      </li>
       <li class="nav-item">
        <a href="?page=chat_ttl" class="nav-link text-white <?= $current_page === 'chat_ttl' ? 'active bg-secondary' : '' ?>">⏳ Время хранения чатов</a>
      </li>
      <li class="nav-item">
        <a href="?page=models" class="nav-link text-white <?= $current_page === 'models' ? 'active bg-secondary' : '' ?>">🤖 Модели</a>
      </li>
      <li class="nav-item">
        <a href="?page=proxy" class="nav-link text-white <?= $current_page === 'proxy' ? 'active bg-secondary' : '' ?>">🌐 Прокси</a>
      </li>
       <li class="nav-item">
        <a href="?page=admin" class="nav-link text-white <?= $current_page === 'admin' ? 'active bg-secondary' : '' ?>">👤 Сменить пароль админа</a>
      </li>
    </ul>
  </div>
  <a href="?page=diagnostics" class="nav-link text-white <?= $current_page === 'diagnostics' ? 'active bg-secondary' : '' ?>">🩺 Диагностика</a>
  <a href="?page=logs" class="nav-link text-white <?= $current_page === 'logs' ? 'active bg-secondary' : '' ?>">📝 Логи</a>

  <a href="logout.php" class="nav-link text-warning mt-4">🚪 Выйти</a>
</nav>