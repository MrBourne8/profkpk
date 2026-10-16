<?php $isEdit = !empty($contestant); ?>
<div class="breadcrumbs">
    <a href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'contestants'])) ?>">Настройки</a> /
    <?= $isEdit ? 'Конкурсант' : 'Новый конкурсант' ?>
</div>
<div class="page-title"><h2><?= $isEdit ? 'Редактирование конкурсанта' : 'Новый конкурсант' ?></h2></div>
<div class="card">
    <form method="post" action="<?= e(url('contestant_save')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $contestant['id'] ?>">
        <?php endif; ?>
        <div class="form-grid">
            <label>Номер
                <input type="text" name="number" required value="<?= e($contestant['number'] ?? '') ?>">
            </label>
            <label>Группа
                <input type="text" name="group_name" value="<?= e($contestant['group_name'] ?? '') ?>">
            </label>
        </div>
        <label>ФИО
            <input type="text" name="full_name" required value="<?= e($contestant['full_name'] ?? '') ?>">
        </label>
        <label>Порядок
            <input type="number" name="sort_order" value="<?= (int) ($contestant['sort_order'] ?? 0) ?>">
        </label>
        <div class="toolbar">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a class="btn btn-ghost" href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'contestants'])) ?>">Отмена</a>
        </div>
    </form>
    <?php if ($isEdit): ?>
        <hr>
        <form method="post" action="<?= e(url('contestant_delete')) ?>" onsubmit="return confirm('Удалить конкурсанта и его оценки?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $contestant['id'] ?>">
            <button type="submit" class="btn btn-danger">Удалить</button>
        </form>
    <?php endif; ?>
</div>
