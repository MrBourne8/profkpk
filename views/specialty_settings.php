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
                        <th>№</th>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contestants as $c): ?>
                        <tr>
                            <td><?= e($c['number']) ?></td>
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
        <?php if (!$modules): ?>
            <p class="muted">Добавьте модуль (задание), затем критерии с допустимыми баллами.</p>
        <?php else: ?>
            <?php foreach ($modules as $module): ?>
                <div class="module-block">
                    <div class="toolbar" style="justify-content:space-between">
                        <h3 style="margin:0"><?= e($module['title']) ?></h3>
                        <div class="actions">
                            <a class="btn btn-sm" href="<?= e(url('criterion_edit', ['module_id' => $module['id']])) ?>">+ критерий</a>
                            <a class="btn btn-sm btn-ghost" href="<?= e(url('module_edit', ['id' => $module['id']])) ?>">Изменить</a>
                        </div>
                    </div>
                    <?php $crits = $criteriaByModule[(int) $module['id']] ?? []; ?>
                    <?php if (!$crits): ?>
                        <p class="muted">Критерии не заданы.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                <tr>
                                    <th>Критерий</th>
                                    <th>Допустимые баллы</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($crits as $cr): ?>
                                    <tr>
                                        <td>
                                            <?= e($cr['title']) ?>
                                            <?php if ($cr['description']): ?>
                                                <div class="help"><?= e($cr['description']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e(criterion_format_allowed_label($cr)) ?></td>
                                        <td class="actions">
                                            <a class="btn btn-sm btn-ghost" href="<?= e(url('criterion_edit', ['id' => $cr['id']])) ?>">Изменить</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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
