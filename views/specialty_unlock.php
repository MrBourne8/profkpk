<div class="modal-backdrop">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="unlock-title">
        <h2 id="unlock-title">Доступ к компетенции</h2>
        <p class="muted">
            <?= e($competition['title'] ?? '') ?><br>
            <strong><?= e($specialty['name']) ?></strong>
        </p>
        <?php if ($chief): ?>
            <p>Главный эксперт: <strong><?= e($chief['display_name']) ?></strong> (логин <?= e($chief['login']) ?>)</p>
            <p class="help">Введите пароль главного эксперта, чтобы открыть компетенцию.</p>
        <?php else: ?>
            <p class="alert alert-error">Главный эксперт не найден. Обратитесь к администратору.</p>
        <?php endif; ?>

        <?php if (!empty($unlockError)): ?>
            <div class="alert alert-error"><?= e($unlockError) ?></div>
        <?php endif; ?>

        <?php if ($chief): ?>
            <form method="post" action="<?= e(url('specialty_unlock')) ?>" class="form" style="max-width:none">
                <?= csrf_field() ?>
                <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">
                <input type="hidden" name="return_route" value="<?= e($return_route) ?>">
                <?php foreach ($return_params as $key => $value): ?>
                    <?php if ($key === 'r' || !is_scalar($value)) continue; ?>
                    <input type="hidden" name="return_params[<?= e((string) $key) ?>]" value="<?= e((string) $value) ?>">
                <?php endforeach; ?>
                <label>Пароль главного эксперта
                    <input type="password" name="password" required autofocus autocomplete="current-password">
                </label>
                <div class="toolbar">
                    <button type="submit" class="btn btn-primary">Открыть</button>
                    <a class="btn btn-ghost" href="<?= e(url('competition', ['id' => $specialty['competition_id']])) ?>">Отмена</a>
                </div>
            </form>
        <?php else: ?>
            <a class="btn btn-ghost" href="<?= e(url('home')) ?>">Назад</a>
        <?php endif; ?>
    </div>
</div>
