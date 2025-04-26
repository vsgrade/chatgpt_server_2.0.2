<h3>🤖 Управление моделями OpenAI</h3>
<form id="modelsForm" method="post" action="save_models.php">
  <div class="mb-3">
    <button type="button" class="btn btn-outline-secondary" onclick="loadOpenAIModels()">Загрузить с OpenAI</button>
  </div>
  <div id="modelsList">
    <div>Загрузка списка...</div>
  </div>
  <button type="submit" class="btn btn-primary mt-3">Сохранить выбранные модели</button>
</form>
<script>
function loadOpenAIModels() {
  document.getElementById('modelsList').innerHTML = "Загрузка...";
  fetch("load_openai_models.php")
    .then(res => res.json())
    .then(models => renderModelsList(models))
    .catch(e => {
      document.getElementById('modelsList').innerHTML = "Ошибка загрузки: " + e;
    });
}
function renderModelsList(models) {
  if (!Array.isArray(models)) {
    document.getElementById('modelsList').innerHTML =
      '<div class="text-danger">Ошибка: ' + (models && models.error ? models.error : 'Неизвестный формат ответа') + '</div>';
    return;
  }
  fetch('get_enabled_models.php')
    .then(res => res.json())
    .then(enabled => {
      let html = '<div class="row">';
      models.forEach(m => {
        let checked = enabled.includes(m.id) ? 'checked' : '';
        html += `
        <div class="col-12 col-md-6 mb-2">
          <label>
            <input type="checkbox" name="models[]" value="${m.id}" ${checked}> ${m.name} <span class="text-muted small">(${m.id})</span>
          </label>
        </div>`;
      });
      html += '</div>';
      document.getElementById('modelsList').innerHTML = html;
    });
}
document.addEventListener("DOMContentLoaded", function () {
  fetch('get_enabled_models.php')
    .then(res => res.json())
    .then(enabled => renderModelsList(
        enabled.map(id => ({id, name: id}))
      ));
});
</script>
