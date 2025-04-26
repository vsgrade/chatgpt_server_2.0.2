<?php
$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
function decrypt($value) {
  return openssl_decrypt($value, "AES-128-CTR", "mysecretkey12345", 0, "1234567891011121");
}
$settings["proxy"] = isset($settings["proxy"]) ? decrypt($settings["proxy"]) : "";
?>
<h3>🌐 Прокси</h3>
<form method="POST" action="save.php" class="mb-3">
  <div class="mb-3">
    <label for="proxy_type" class="form-label">Тип прокси</label>
    <select class="form-select" name="proxy_type" id="proxy_type">
      <option value="http" <?= ($settings['proxy_type'] ?? '') === 'http' ? 'selected' : '' ?>>HTTP</option>
      <option value="socks5" <?= ($settings['proxy_type'] ?? '') === 'socks5' ? 'selected' : '' ?>>SOCKS5</option>
    </select>
  </div>
  <div class="mb-3">
    <label for="proxy" class="form-label">Адрес прокси</label>
    <input type="text" class="form-control" name="proxy" id="proxy" value="<?= htmlspecialchars($settings['proxy'] ?? '') ?>">
  </div>
  <button type="submit" class="btn btn-primary">Сохранить</button>
  <button type="button" class="btn btn-outline-secondary ms-2" onclick="checkProxy()">Проверить прокси</button>
  <div id="proxyResult" class="mt-2 small"></div>
</form>
<script>
function checkProxy() {
  const proxy = document.getElementById('proxy').value;
  const type = document.getElementById('proxy_type').value;
  fetch('test_proxy.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ proxy, proxy_type: type })
  })
  .then(res => res.text())
  .then(data => document.getElementById('proxyResult').innerHTML = data)
  .catch(err => document.getElementById('proxyResult').innerText = 'Ошибка: ' + err);
}
</script>
