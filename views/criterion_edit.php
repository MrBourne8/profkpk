<?php
$isEdit = !empty($criterion);
$allowed = criterion_parse_allowed_values($criterion['allowed_values'] ?? []);
while (count($allowed) < 5) {
    $allowed[] = null;
}
?>
<div class="breadcrumbs">
    <a href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'criteria'])) ?>">Настройки</a> /
    <?= e($module['title']) ?> /
    <?= $isEdit ? 'Критерий' : 'Новый критерий' ?>
</div>
<div class="page-title"><h2><?= $isEdit ? 'Редактирование критерия' : 'Новый критерий' ?></h2></div>
<div class="card">
    <form method="post" action="<?= e(url('criterion_save')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="module_id" value="<?= (int) $module['id'] ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $criterion['id'] ?>">
        <?php endif; ?>
        <label>Название критерия
            <input type="text" name="title" required value="<?= e($criterion['title'] ?? '') ?>">
        </label>
        <label>Расшифровка аспекта
            <textarea name="description" placeholder="Описание аспекта для бланка эксперта"><?= e($criterion['description'] ?? '') ?></textarea>
            <span class="help">Выводится в столбце «Расшифровка аспекта» при печати бланков.</span>
        </label>

        <fieldset style="border:1px solid var(--line);border-radius:8px;padding:1rem;margin:0">
            <legend style="padding:0 0.35rem;font-weight:700">Допустимые баллы</legend>
            <p class="help" style="margin-top:0">
                Всегда доступен <strong>0</strong>. Дополнительно укажите до 5 других значений
                (например: 1, 2, 3 или 0.5, 1, 1.5).
            </p>
            <div class="form-grid">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <label>Значение <?= $i + 1 ?>
                        <input type="number" step="0.01" min="0.01" name="values[]"
                               value="<?= $allowed[$i] !== null ? e(format_points((float) $allowed[$i])) : '' ?>"
                               placeholder="не задано">
                    </label>
                <?php endfor; ?>
            </div>
        </fieldset>

        <label>Порядок
            <input type="number" name="sort_order" value="<?= (int) ($criterion['sort_order'] ?? 0) ?>">
        </label>
        <div class="toolbar">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a class="btn btn-ghost" href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'criteria'])) ?>">Отмена</a>
        </div>
    </form>
    <?php if ($isEdit): ?>
        <hr>
        <form method="post" action="<?= e(url('criterion_delete')) ?>" onsubmit="return confirm('Удалить критерий?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $criterion['id'] ?>">
            <button type="submit" class="btn btn-danger">Удалить критерий</button>
        </form>
    <?php endif; ?>
</div>
