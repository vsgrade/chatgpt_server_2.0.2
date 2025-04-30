<h3>📝 Логи ошибок</h3>
<div id="errorLogContainer">Загрузка...</div>
<script>
function loadErrorLog() {
  fetch("load_logs.php")
    .then(res => res.text())
    .then(html => {
      document.getElementById("errorLogContainer").innerHTML = html;
    })
    .catch(err => {
      document.getElementById("errorLogContainer").innerHTML = "Ошибка загрузки логов: " + err;
    });
}
document.addEventListener("DOMContentLoaded", loadErrorLog);
</script>
