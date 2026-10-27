<div class="page-title">
    <div>
        <h2>Настройки</h2>
        <p class="subtitle"><?= e($specialty['name']) ?></p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('specialty', ['id' => $specialty['id']])) ?>">← К рейтингу</a>
</div>

<div class="tabs">
    <a class="tab <?= $tab === 'contestants' ? 'active' : '' ?>"
       href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'contestants'])) ?>">Конкурсанты</a>
    <a class="tab <?= $tab === 'experts' ? 'active' : '' ?>"
       href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'experts'])) ?>">Эксперты</a>
    <a class="tab <?= $tab === 'criteria' ? 'active' : '' ?>"
       href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'criteria'])) ?>">Критерии</a>
    <?php if (auth_is_admin()): ?>
        <a class="tab <?= $tab === 'general' ? 'active' : '' ?>"
           href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'general'])) ?>">Параметры</a>
    <?php endif; ?>
</div>

<?php if ($tab === 'contestants'): ?>
    <div class="card">
        <div class="page-title" style="margin-bottom:0.75rem">
            <h3 style="margin:0">Список конкурсантов</h3>
            <a class="btn btn-sm btn-primary" href="<?= e(url('contestant_edit', ['specialty_id' => $specialty['id']])) ?>">Добавить</a>
        </div>
        <?php if (!$contestants): ?>
            <p class="muted">Участников пока нет.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>РМ</th>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contestants as $c): ?>
                        <tr>
                            <td><strong><?= e(contestant_workplace_label($c)) ?></strong></td>
                            <td><?= e($c['full_name']) ?></td>
                            <td><?= e($c['group_name'] ?: '—') ?></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-ghost" href="<?= e(url('contestant_edit', ['id' => $c['id']])) ?>">Изменить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($tab === 'experts'): ?>
    <div class="card">
        <div class="page-title" style="margin-bottom:0.75rem">
            <h3 style="margin:0">Эксперты (<?= (int) $specialty['experts_count'] ?>)</h3>
            <a class="btn btn-sm btn-primary" href="<?= e(url('expert_edit', ['specialty_id' => $specialty['id']])) ?>">Настроить список</a>
        </div>
        <?php if (!$experts): ?>
            <p class="muted">Нажмите «Настроить список», чтобы указать ФИО экспертов для печати бланков.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>№</th>
                        <th>ФИО</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($experts as $ex): ?>
                        <tr>
                            <td><?= (int) $ex['number'] ?></td>
                            <td><?= e($ex['full_name']) ?></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-ghost" href="<?= e(url('expert_edit', ['id' => $ex['id']])) ?>">Изменить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($tab === 'criteria'): ?>
    <div class="card">
        <div class="page-title" style="margin-bottom:0.75rem">
            <h3 style="margin:0">Модули и критерии</h3>
            <a class="btn btn-sm btn-primary" href="<?= e(url('module_edit', ['specialty_id' => $specialty['id']])) ?>">Добавить модуль</a>
        </div>

        <details class="import-box">
            <summary>Импорт аспектов из Excel</summary>
            <form method="post" action="<?= e(url('criteria_import')) ?>" enctype="multipart/form-data" class="form" style="margin-top:0.75rem">
                <?= csrf_field() ?>
                <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">
                <label>Файл .xlsx
                    <input type="file" name="criteria_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </label>
                <p class="help" style="margin-top:0">
                    Поддерживаются бланки «№ / Описание аспекта / Расшифровка / Оценка»
                    и приложения со строками «И» / «С».
                    Модули определяются автоматически: отдельный лист или строка-разделитель
                    (буква А/Б/В…, название модуля и сумма баллов модуля справа).
                    Цвет строки не важен — заливка может быть любой или отсутствовать.
                    Импорт <strong>добавляет</strong> новые модули и аспекты, существующие не удаляет.
                    Допустимые баллы — из шкалы файла или из долей в расшифровке
                    (например 0,20 + 0,40 + 0,10 = 0,70). Ноль всегда доступен.
                </p>
                <button type="submit" class="btn btn-primary">Загрузить и создать</button>
            </form>
        </details>

        <?php if (!$modules): ?>
            <p class="muted">Добавьте модуль (задание), затем критерии с допустимыми баллами — или импортируйте из Excel.</p>
        <?php else: ?>
            <form id="criteria-bulk-form" method="post" action="<?= e(url('criteria_bulk_delete')) ?>" class="criteria-bulk-form"
                  onsubmit="return confirm('Удалить все отмеченные аспекты?');">
                <?= csrf_field() ?>
                <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">
                <div class="criteria-bulk-bar">
                    <button type="submit" class="btn btn-sm btn-danger" id="criteria-bulk-delete" disabled>
                        Удалить отмеченные
                    </button>
                    <span class="help" id="criteria-bulk-count">Отметьте аспекты галочкой</span>
                </div>
            </form>

            <?php foreach ($modules as $mi => $module): ?>
                <?php
                $crits = $criteriaByModule[(int) $module['id']] ?? [];
                $modulePointsSum = 0.0;
                foreach ($crits as $cr) {
                    $modulePointsSum += (float) ($cr['max_points'] ?? 0);
                }
                $moduleDomId = 'module-crits-' . (int) $module['id'];
                ?>
                <div class="module-block" data-criteria-module="<?= e($moduleDomId) ?>">
                    <div class="toolbar" style="justify-content:space-between">
                        <div>
                            <h3 style="margin:0">Модуль №<?= $mi + 1 ?>. <?= e($module['title']) ?></h3>
                            <p class="module-points-sum">Сумма баллов модуля: <strong><?= e(format_points($modulePointsSum)) ?></strong></p>
                        </div>
                        <div class="actions">
                            <a class="btn btn-sm" href="<?= e(url('criterion_edit', ['module_id' => $module['id']])) ?>">+ аспект</a>
                            <a class="btn btn-sm btn-ghost" href="<?= e(url('module_edit', ['id' => $module['id']])) ?>">Изменить</a>
                            <form method="post" action="<?= e(url('module_delete')) ?>" class="inline-form"
                                  onsubmit="return confirm('Удалить модуль целиком со всеми аспектами и оценками?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $module['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                            </form>
                        </div>
                    </div>
                    <?php if (!$crits): ?>
                        <p class="muted">Аспекты не заданы.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th class="col-check">
                                        <input type="checkbox" class="criteria-check-all" title="Выбрать все в модуле"
                                               aria-label="Выбрать все аспекты модуля" data-target="<?= e($moduleDomId) ?>">
                                    </th>
                                    <th style="width:3rem">№</th>
                                    <th>Аспект</th>
                                    <th>Допустимые баллы</th>
                                    <th style="width:11rem"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($crits as $ci => $cr): ?>
                                    <tr>
                                        <td class="col-check">
                                            <input type="checkbox" class="criteria-check" name="ids[]"
                                                   value="<?= (int) $cr['id'] ?>"
                                                   form="criteria-bulk-form"
                                                   data-module="<?= e($moduleDomId) ?>"
                                                   aria-label="Отметить аспект №<?= $ci + 1 ?>">
                                        </td>
                                        <td class="num"><?= $ci + 1 ?></td>
                                        <td>
                                            <?= e($cr['title']) ?>
                                            <?php if ($cr['description']): ?>
                                                <div class="help"><?= e($cr['description']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e(criterion_format_allowed_label($cr)) ?></td>
                                        <td class="col-actions">
                                            <a class="btn btn-sm btn-ghost" href="<?= e(url('criterion_edit', ['id' => $cr['id']])) ?>">Изменить</a>
                                            <form method="post" action="<?= e(url('criterion_delete')) ?>" class="inline-form"
                                                  onsubmit="return confirm('Удалить аспект?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $cr['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="3"><strong>Итого по модулю</strong></td>
                                    <td colspan="2"><strong><?= e(format_points($modulePointsSum)) ?></strong></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <script>
            (function () {
                var form = document.getElementById('criteria-bulk-form');
                var btn = document.getElementById('criteria-bulk-delete');
                var countEl = document.getElementById('criteria-bulk-count');
                if (!form || !btn) return;

                function checks() {
                    return Array.prototype.slice.call(document.querySelectorAll('.criteria-check'));
                }

                function updateBar() {
                    var selected = checks().filter(function (c) { return c.checked; });
                    var n = selected.length;
                    btn.disabled = n === 0;
                    if (countEl) {
                        countEl.textContent = n === 0
                            ? 'Отметьте аспекты галочкой'
                            : ('Выбрано: ' + n);
                    }
                    document.querySelectorAll('.criteria-check-all').forEach(function (master) {
                        var target = master.getAttribute('data-target');
                        var group = checks().filter(function (c) {
                            return c.getAttribute('data-module') === target;
                        });
                        if (!group.length) {
                            master.checked = false;
                            master.indeterminate = false;
                            return;
                        }
                        var on = group.filter(function (c) { return c.checked; }).length;
                        master.checked = on === group.length;
                        master.indeterminate = on > 0 && on < group.length;
                    });
                }

                document.querySelectorAll('.criteria-check-all').forEach(function (master) {
                    master.addEventListener('change', function () {
                        var target = master.getAttribute('data-target');
                        checks().forEach(function (c) {
                            if (c.getAttribute('data-module') === target) {
                                c.checked = master.checked;
                            }
                        });
                        updateBar();
                    });
                });

                document.addEventListener('change', function (e) {
                    if (e.target && e.target.classList && e.target.classList.contains('criteria-check')) {
                        updateBar();
                    }
                });

                updateBar();
            })();
            </script>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($tab === 'general' && auth_is_admin()): ?>
    <div class="card">
        <form method="post" action="<?= e(url('specialty_save')) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="competition_id" value="<?= (int) $competition['id'] ?>">
            <input type="hidden" name="id" value="<?= (int) $specialty['id'] ?>">
            <label>Название компетенции
                <input type="text" name="name" required value="<?= e($specialty['name']) ?>">
            </label>
            <label>Количество экспертов (3–6)
                <input type="number" name="experts_count" min="3" max="6" required
                       value="<?= (int) $specialty['experts_count'] ?>">
                <span class="help">Для печати индивидуальных бумажных протоколов.</span>
            </label>
            <label>Главный эксперт компетенции
                <select name="chief_expert_id">
                    <option value="">— не назначен —</option>
                    <?php foreach ($chiefExperts as $ce): ?>
                        <option value="<?= (int) $ce['id'] ?>"
                            <?= (int) ($specialty['chief_expert_id'] ?? 0) === (int) $ce['id'] ? 'selected' : '' ?>>
                            <?= e($ce['display_name']) ?> (<?= e($ce['login']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="help">
                    При открытии компетенции запрашивается пароль этого эксперта.
                    <?php if (!$chiefExperts): ?>
                        Сначала <a href="<?= e(url('chief_experts')) ?>">добавьте главных экспертов</a>.
                    <?php endif; ?>
                </span>
            </label>
            <div class="toolbar">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
        <hr>
        <form method="post" action="<?= e(url('specialty_delete')) ?>" onsubmit="return confirm('Удалить компетенцию со всеми данными?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $specialty['id'] ?>">
            <button type="submit" class="btn btn-danger">Удалить компетенцию</button>
        </form>
    </div>
<?php endif; ?>
