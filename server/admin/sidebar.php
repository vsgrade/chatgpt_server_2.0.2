<nav class="nav flex-column p-3 gap-2">
  <div class="fs-5 mb-2">🤖 ChatGPT Admin</div>

  <?php
    // Определяем текущую страницу из URL
    // По умолчанию 'settings' в текущей версии index.php
    $current_page = $_GET['page'] ?? 'settings';

    // Определяем страницы, которые входят в подменю "Настройки"
    // Убедитесь, что эти страницы разрешены в index.php $allowed
    $settings_submenu_pages = ['settings', 'proxy', 'admin', 'models', 'limits', 'chat_ttl'];
    // Определяем, активен ли любой пункт подменю "Настройки"
    $is_settings_submenu_active = in_array($current_page, $settings_submenu_pages);
  ?>

  <a href="#settingsSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $is_settings_submenu_active ? 'true' : 'false' ?>"
     class="nav-link text-white <?= $is_settings_submenu_active ? 'active bg-secondary' : '' ?>">
    ⚙️ Настройки
  </a>

  <div class="collapse <?= $is_settings_submenu_active ? 'show' : '' ?>" id="settingsSubmenu">
    <ul class="nav flex-column ms-3"> <li class="nav-item">
        <a href="?page=settings" class="nav-link text-white <?= $current_page === 'settings' ? 'active bg-secondary' : '' ?>">⚙️ Настройки API</a>
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