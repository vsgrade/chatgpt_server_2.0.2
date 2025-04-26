<h3>👤 Администратор</h3>
<form method="POST" action="change_password.php" class="mb-3" style="max-width:400px;">
  <div class="mb-3">
    <label class="form-label">Старый пароль</label>
    <input type="password" name="old_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Новый пароль</label>
    <input type="password" name="new_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Повторите пароль</label>
    <input type="password" name="repeat_password" class="form-control" required>
  </div>
  <button class="btn btn-warning">Изменить</button>
</form>
<a href="reset_password.php" class="btn btn-link">Сброс пароля администратора</a>
