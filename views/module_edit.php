<?php $isEdit = !empty($module); ?>
<div class="breadcrumbs">
    <a href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'criteria'])) ?>">Настройки</a> /
    <?= $isEdit ? 'Модуль' : 'Новый модуль' ?>
</div>
<div class="page-title"><h2><?= $isEdit ? 'Редактирование модуля' : 'Новый модуль' ?></h2></div>
<div class="card">
    <form method="post" action="<?= e(url('module_save')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $module['id'] ?>">
        <?php endif; ?>
        <label>Название модуля
            <input type="text" name="title" required value="<?= e($module['title'] ?? '') ?>">
        </label>
        <label>Описание
            <textarea name="description"><?= e($module['description'] ?? '') ?></textarea>
        </label>
        <div class="form-grid">
            <label>Макс. баллов модуля (справочно)
                <input type="number" step="0.01" name="max_score" value="<?= e($module['max_score'] ?? '') ?>">
            </label>
            <label>Порядок
                <input type="number" name="sort_order" value="<?= (int) ($module['sort_order'] ?? 0) ?>">
            </label>
        </div>
        <div class="toolbar">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a class="btn btn-ghost" href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'criteria'])) ?>">Отмена</a>
        </div>
    </form>
    <?php if ($isEdit): ?>
        <hr>
        <form method="post" action="<?= e(url('module_delete')) ?>" onsubmit="return confirm('Удалить модуль и все критерии?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $module['id'] ?>">
            <button type="submit" class="btn btn-danger">Удалить модуль</button>
        </form>
    <?php endif; ?>
</div>
