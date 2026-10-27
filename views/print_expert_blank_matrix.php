<?php
/**
 * Вариант 2: эксперт × модуль — один бланк.
 * Строки — критерии, столбцы — конкурсанты.
 */
$printExperts = $printExperts ?? [];
$contestantsList = $contestants ?: [];
$pageBreak = false;

if (!$printExperts || !$modules): ?>
    <p>Недостаточно данных для печати: нужны эксперты и модули.</p>
<?php else: ?>

<?php foreach ($printExperts as $expert): ?>
    <?php foreach ($modules as $module): ?>
        <?php $crits = $criteriaByModule[(int) $module['id']] ?? []; ?>
        <div class="print-sheet" <?= $pageBreak ? 'style="page-break-before: always;"' : '' ?>>
            <?php $pageBreak = true; ?>
            <div class="print-header">
                <?php print_brand_logo(); ?>
                <h1>Протокол оценивания эксперта</h1>
                <p><strong><?= e($competition['title']) ?></strong></p>
                <p>Компетенция: <?= e($specialty['name']) ?></p>
                <p>Модуль: <?= e($module['title']) ?></p>
                <p>
                    Эксперт № <?= (int) $expert['number'] ?> —
                    <?= e($expert['full_name']) ?>
                </p>
            </div>

            <?php if (!$crits): ?>
                <p>Критерии не заданы.</p>
            <?php elseif (!$contestantsList): ?>
                <p>Конкурсанты не добавлены.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="print-table print-matrix">
                        <colgroup>
                            <col class="col-aspect">
                            <col class="col-desc">
                            <col class="col-points">
                            <?php foreach ($contestantsList as $c): ?>
                                <col class="col-score">
                            <?php endforeach; ?>
                        </colgroup>
                        <thead>
                        <tr>
                            <th>Аспект</th>
                            <th>Расшифровка аспекта</th>
                            <th>Баллы</th>
                            <?php foreach ($contestantsList as $c): ?>
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
                        <?php foreach ($crits as $cr): ?>
                            <tr>
                                <td class="cell-aspect"><?= e($cr['title']) ?></td>
                                <td class="cell-desc"><?= e($cr['description'] ?: '') ?></td>
                                <td class="cell-points"><?= e(criterion_format_allowed_label($cr)) ?></td>
                                <?php foreach ($contestantsList as $c): ?>
                                    <td class="empty-cell"></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3"><strong>Сумма</strong></td>
                            <?php foreach ($contestantsList as $c): ?>
                                <td class="empty-cell"></td>
                            <?php endforeach; ?>
                        </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($module['description'])): ?>
                <p><em><?= e($module['description']) ?></em></p>
            <?php endif; ?>

            <div class="print-signatures">
                <div><p>Дата ______________</p></div>
                <div><p>Подпись эксперта ______________</p></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php endif; ?>
