<?php $isEdit = !empty($competition); ?>
<div class="breadcrumbs">
    <a href="<?= e(url('home')) ?>">Конкурсы</a> /
    <?= $isEdit ? e($competition['title']) : 'Новый конкурс' ?>
</div>
<div class="page-title">
    <div>
        <h2><?= $isEdit ? 'Редактирование конкурса' : 'Новый конкурс' ?></h2>
    </div>
</div>
<div class="card">
    <form method="post" action="<?= e(url('competition_save')) ?>" class="form">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $competition['id'] ?>">
        <?php endif; ?>
        <label>Название
            <input type="text" name="title" required value="<?= e($competition['title'] ?? '') ?>">
        </label>
        <div class="form-grid">
            <label>Дата начала
                <input type="date" name="start_date" value="<?= e($competition['start_date'] ?? '') ?>">
            </label>
            <label>Дата окончания
                <input type="date" name="end_date" value="<?= e($competition['end_date'] ?? '') ?>">
            </label>
            <label>Статус
                <select name="status">
                    <?php foreach (['draft', 'active', 'finished'] as $st): ?>
                        <option value="<?= $st ?>" <?= (($competition['status'] ?? 'draft') === $st) ? 'selected' : '' ?>>
                            <?= e(status_label($st)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Примечание
            <textarea name="notes"><?= e($competition['notes'] ?? '') ?></textarea>
        </label>
        <div class="toolbar">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a class="btn btn-ghost" href="<?= e($isEdit ? url('competition', ['id' => $competition['id']]) : url('home')) ?>">Отмена</a>
        </div>
    </form>
    <?php if ($isEdit): ?>
        <hr>
        <form method="post" action="<?= e(url('competition_delete')) ?>" onsubmit="return confirm('Удалить конкурс со всеми специальностями и оценками?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $competition['id'] ?>">
            <button type="submit" class="btn btn-danger">Удалить конкурс</button>
        </form>
    <?php endif; ?>
</div>
