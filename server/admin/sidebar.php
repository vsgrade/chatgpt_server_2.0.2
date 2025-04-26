<nav class="nav flex-column p-3 gap-2">
  <div class="fs-5 mb-2">🤖 ChatGPT Admin</div>
  <a href="?page=settings" class="nav-link text-white <?= ($_GET['page'] ?? 'settings') === 'settings' ? 'active bg-secondary' : '' ?>">⚙️ Настройки</a>
  <a href="?page=proxy" class="nav-link text-white <?= ($_GET['page'] ?? '') === 'proxy' ? 'active bg-secondary' : '' ?>">🌐 Прокси</a>
  <a href="?page=admin" class="nav-link text-white <?= ($_GET['page'] ?? '') === 'admin' ? 'active bg-secondary' : '' ?>">👤 Администратор</a>
  <a href="?page=diagnostics" class="nav-link text-white <?= ($_GET['page'] ?? '') === 'diagnostics' ? 'active bg-secondary' : '' ?>">🩺 Диагностика</a>
  <a href="?page=logs" class="nav-link text-white <?= ($_GET['page'] ?? '') === 'logs' ? 'active bg-secondary' : '' ?>">📝 Логи</a>
  <a href="?page=models" class="nav-link text-white <?= ($_GET['page'] ?? '') === 'models' ? 'active bg-secondary' : '' ?>">🤖 Модели</a>
  <a href="logout.php" class="nav-link text-warning mt-4">🚪 Выйти</a>
</nav>
