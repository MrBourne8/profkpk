<div class="auth-card" style="margin: 3rem auto;">
    <h2>Вход</h2>
    <p class="muted">Администратор или главный эксперт компетенции</p>
    <form method="post" action="<?= e(url('login')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Логин
            <input type="text" name="login" required autofocus>
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary">Войти</button>
    </form>
</div>
