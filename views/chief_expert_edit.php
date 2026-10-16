<?php $isEdit = !empty($expert); ?>
<div class="page-title">
    <div>
        <h2><?= $isEdit ? 'Редактирование главного эксперта' : 'Новый главный эксперт' ?></h2>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('chief_experts')) ?>">← К списку</a>
</div>

<div class="card">
    <form method="post" action="<?= e(url('chief_expert_save')) ?>" class="form">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $expert['id'] ?>">
        <?php endif; ?>
        <label>ФИО
            <input type="text" name="display_name" required value="<?= e($expert['display_name'] ?? '') ?>">
        </label>
        <label>Логин
            <input type="text" name="login" required pattern="[A-Za-z0-9._-]{3,64}"
                   value="<?= e($expert['login'] ?? '') ?>" autocomplete="off">
        </label>
        <label>Пароль <?= $isEdit ? '(оставьте пустым, чтобы не менять)' : '' ?>
            <input type="password" name="password" <?= $isEdit ? '' : 'required minlength="4"' ?>
                   autocomplete="new-password">
            <span class="help">Этот пароль запрашивается при открытии компетенции.</span>
        </label>
        <div class="toolbar">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a class="btn btn-ghost" href="<?= e(url('chief_experts')) ?>">Отмена</a>
        </div>
    </form>
    <?php if ($isEdit): ?>
        <hr>
        <form method="post" action="<?= e(url('chief_expert_delete')) ?>"
              onsubmit="return confirm('Удалить главного эксперта? Назначение с компетенций будет снято.');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $expert['id'] ?>">
            <button type="submit" class="btn btn-danger">Удалить</button>
        </form>
    <?php endif; ?>
</div>
