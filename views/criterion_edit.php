<?php
$isEdit = !empty($criterion);
$allowed = criterion_parse_allowed_values($criterion['allowed_values'] ?? []);
while (count($allowed) < 5) {
    $allowed[] = null;
}
?>
<?php $aspectNumber = (int) ($aspectNumber ?? 1); ?>
<div class="breadcrumbs">
    <a href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'criteria'])) ?>">Настройки</a> /
    <?= e($module['title']) ?> /
    Аспект №<?= $aspectNumber ?>
</div>
<div class="page-title">
    <div>
        <h2><?= $isEdit ? 'Редактирование аспекта' : 'Новый аспект' ?> №<?= $aspectNumber ?></h2>
        <p class="subtitle">Модуль: <?= e($module['title']) ?></p>
    </div>
</div>
<div class="card">
    <form method="post" action="<?= e(url('criterion_save')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="module_id" value="<?= (int) $module['id'] ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $criterion['id'] ?>">
        <?php endif; ?>
        <p class="help" style="margin-top:0">Номер аспекта в модуле: <strong>№<?= $aspectNumber ?></strong></p>
        <label>Название аспекта
            <input type="text" name="title" required value="<?= e($criterion['title'] ?? '') ?>">
        </label>
        <label>Расшифровка аспекта
            <textarea name="description" placeholder="Описание аспекта для бланка эксперта"><?= e($criterion['description'] ?? '') ?></textarea>
            <span class="help">Выводится в столбце «Расшифровка аспекта» при печати бланков.</span>
        </label>

        <fieldset style="border:1px solid var(--line);border-radius:8px;padding:1rem;margin:0">
            <legend style="padding:0 0.35rem;font-weight:700">Допустимые баллы</legend>
            <p class="help" style="margin-top:0">
                Балл <strong>0</strong> всегда доступен — его сюда вписывать не нужно.<br>
                Для варианта «только 0 или 0,5» заполните <strong>Значение 1</strong> как <strong>0.5</strong>
                (или <strong>0,5</strong>), остальные поля оставьте пустыми.<br>
                Можно указать до 5 значений больше нуля, например: 0.5 · 1 · 1.5 · 2 · 3.
            </p>
            <div class="form-grid">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <label>Значение <?= $i + 1 ?>
                        <input type="number" class="score-value-input" name="values[]"
                               step="0.01" min="0" lang="en"
                               value="<?= $allowed[$i] !== null ? e(format_points((float) $allowed[$i])) : '' ?>"
                               placeholder="например 0.5" autocomplete="off">
                    </label>
                <?php endfor; ?>
            </div>
            <p class="help">Можно ввести число с клавиатуры (через точку: 0.5) или менять стрелками у поля. Ноль в эти поля вписывать не нужно.</p>
        </fieldset>
        <script>
        (function () {
            document.querySelectorAll('.score-value-input').forEach(function (input) {
                input.addEventListener('keydown', function (e) {
                    // Разрешить запятую как десятичный разделитель → заменить на точку
                    if (e.key === ',' || e.key === 'б' || e.key === 'Б') {
                        e.preventDefault();
                        var start = input.selectionStart;
                        var end = input.selectionEnd;
                        var v = input.value;
                        if (v.indexOf('.') === -1) {
                            input.value = v.slice(0, start) + '.' + v.slice(end);
                            input.setSelectionRange(start + 1, start + 1);
                        }
                    }
                });
            });
        })();
        </script>

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
