<?php
// Загрузка настроек
$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
function decrypt($value) {
  return openssl_decrypt($value, "AES-128-CTR", "mysecretkey12345", 0, "1234567891011121");
}
$settings["api_key"] = isset($settings["api_key"]) ? decrypt($settings["api_key"]) : "";
$settings["chat_ttl_days"] = isset($settings["chat_ttl_days"]) ? (int)$settings["chat_ttl_days"] : 30;
?>
<h3>⚙️ Настройки</h3>
<ul class="nav nav-pills mb-3" id="settingsTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="main-tab" data-bs-toggle="pill" data-bs-target="#main" type="button">Основные</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="limits-tab" data-bs-toggle="pill" data-bs-target="#limits" type="button">Лимиты</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="chats-tab" data-bs-toggle="pill" data-bs-target="#chats" type="button">Время хранения чатов</button>
  </li>
</ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="main">
    <form method="POST" action="save.php" class="mb-3">
      <div class="mb-3">
        <label for="api_key" class="form-label">API ключ</label>
        <input type="text" class="form-control" name="api_key" id="api_key" value="<?= htmlspecialchars($settings['api_key'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primary">Сохранить</button>
      <button type="button" class="btn btn-outline-secondary ms-2" onclick="checkApi()">Проверить API</button>
      <div id="apiResult" class="mt-2 small"></div>
    </form>
  </div>
  <div class="tab-pane fade" id="limits">
    <form method="POST" action="save.php">
      <?php foreach(["minute","hour","day","week","month"] as $limit): ?>
      <div class="mb-3">
        <label class="form-label">Макс. токенов в <?= $limit ?></label>
        <input type="number" class="form-control" name="limit_<?= $limit ?>" value="<?= htmlspecialchars($settings["limit_$limit"] ?? '') ?>">
      </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
  </div>
  <div class="tab-pane fade" id="chats">
    <form method="POST" action="save.php">
      <div class="mb-3">
        <label for="chat_ttl_days" class="form-label">Время хранения неактивных чатов (дней)</label>
        <input type="number" class="form-control" name="chat_ttl_days" id="chat_ttl_days" min="1" value="<?= (int)($settings['chat_ttl_days'] ?? 30) ?>">
        <div class="form-text">Чаты без сообщений будут удаляться через указанное число дней.</div>
      </div>
      <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
  </div>
</div>
<script>
function checkApi() {
  const key = document.getElementById('api_key').value;
  // Получаем актуальные значения прокси из базы
  fetch('get_current_proxy.php')
    .then(res => res.json())
    .then(proxySettings => {
      fetch('test_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          api_key: key,
          proxy: proxySettings.proxy,
          proxy_type: proxySettings.proxy_type
        })
      })
      .then(res => res.text())
      .then(data => document.getElementById('apiResult').innerHTML = data)
      .catch(err => document.getElementById('apiResult').innerText = 'Ошибка: ' + err);
    });
}
</script>
