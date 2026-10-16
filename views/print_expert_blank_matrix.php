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
                        <thead>
                        <tr>
                            <th style="min-width:8rem">Аспект</th>
                            <th style="min-width:12rem">Расшифровка аспекта</th>
                            <th style="min-width:5rem">Баллы</th>
                            <?php foreach ($contestantsList as $c): ?>
                                <th class="contestant-vhead">
                                    <div class="contestant-vhead-inner">
                                        <span class="contestant-vnum">№ <?= e($c['number']) ?></span>
                                        <span class="contestant-vname"><?= e($c['full_name']) ?></span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($crits as $cr): ?>
                            <tr>
                                <td><?= e($cr['title']) ?></td>
                                <td style="font-size:0.9em;text-align:left"><?= e($cr['description'] ?: '') ?></td>
                                <td style="font-size:0.85em"><?= e(criterion_format_allowed_label($cr)) ?></td>
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
