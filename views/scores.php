<div class="page-title">
    <div>
        <h2>Внести оценки</h2>
        <p class="subtitle">
            <?= e($specialty['name']) ?> · согласованная оценка по каждому критерию
        </p>
    </div>
    <div class="toolbar" style="margin:0">
        <a class="btn btn-primary" href="<?= e(url('specialty', ['id' => $specialty['id']])) ?>">К рейтингу</a>
        <a class="btn btn-ghost" href="<?= e(url('print_expert_blank', ['specialty_id' => $specialty['id']])) ?>">Бланки</a>
    </div>
</div>

<div class="card alert alert-info">
    После сведения экспертов главный эксперт выбирает одну согласованную оценку из допустимых баллов критерия.
    Строки — критерии, столбцы — конкурсанты.
</div>

<?php if (!$contestants || !$modules): ?>
    <div class="card">
        <p class="muted">Сначала заполните конкурсантов и критерии в настройках.</p>
        <a class="btn btn-primary" href="<?= e(url('specialty_settings', ['id' => $specialty['id']])) ?>">Открыть настройки</a>
    </div>
<?php else: ?>
    <form method="post" action="<?= e(url('scores_save')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">

        <?php $moduleNum = 0; ?>
        <?php foreach ($modules as $module): ?>
            <?php $crits = $criteriaByModule[(int) $module['id']] ?? []; ?>
            <?php if (!$crits) continue; ?>
            <?php $moduleNum++; ?>
            <div class="card module-block">
                <h3>Модуль №<?= $moduleNum ?>. <?= e($module['title']) ?></h3>
                <div class="table-wrap score-table-scroll">
                    <table class="score-table score-matrix">
                        <thead>
                        <tr>
                            <th class="num-col">№</th>
                            <th class="criterion-col">Критерий / аспект</th>
                            <th class="decode-col">Расшифровка аспекта</th>
                            <th class="points-col">Макс. балл</th>
                            <?php foreach ($contestants as $c): ?>
                                <th class="contestant-vhead">
                                    <div class="contestant-vhead-inner">
                                        <span class="contestant-vnum"><?= e(contestant_workplace_label($c)) ?></span>
                                        <span class="contestant-vname"><?= e($c['full_name']) ?></span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($crits as $ci => $cr): ?>
                            <tr>
                                <td class="num-col"><?= $ci + 1 ?></td>
                                <td class="criterion-col">
                                    <strong><?= e($cr['title']) ?></strong>
                                </td>
                                <td class="decode-col">
                                    <?= e($cr['description'] ?: '—') ?>
                                </td>
                                <td class="points-col">
                                    <?= e(format_points((float) ($cr['max_points'] ?? 0))) ?>
                                </td>
                                <?php
                                $crid = (int) $cr['id'];
                                $options = criterion_score_options($cr);
                                foreach ($contestants as $c):
                                    $cid = (int) $c['id'];
                                    $val = $scoreMap[$cid][$crid] ?? null;
                                    $name = "scores[{$cid}][{$crid}]";
                                    ?>
                                    <td>
                                        <select class="score-input" name="<?= e($name) ?>">
                                            <option value="">—</option>
                                            <?php foreach ($options as $opt): ?>
                                                <?php $selected = $val !== null && abs((float) $val - $opt) < 0.00001; ?>
                                                <option value="<?= e(format_points($opt)) ?>" <?= $selected ? 'selected' : '' ?>>
                                                    <?= e(format_points($opt)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="toolbar">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a class="btn btn-ghost" href="<?= e(url('specialty', ['id' => $specialty['id']])) ?>">Отмена</a>
        </div>
    </form>
<?php endif; ?>
