<?php
/**
 * Вариант 1: эксперт × модуль × конкурсант — отдельный бланк.
 * На бланке: критерии одного конкурсанта.
 */
$printExperts = $printExperts ?? [];
$contestantsList = $contestants ?: [];
$pageBreak = false;

if (!$printExperts || !$contestantsList || !$modules): ?>
    <p>Недостаточно данных для печати: нужны эксперты, конкурсанты и модули.</p>
<?php else: ?>

<?php foreach ($printExperts as $expert): ?>
    <?php foreach ($modules as $module): ?>
        <?php
        $crits = $criteriaByModule[(int) $module['id']] ?? [];
        foreach ($contestantsList as $contestant):
            ?>
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
                    <p>
                        Конкурсант № <?= e($contestant['number']) ?> —
                        <strong><?= e($contestant['full_name']) ?></strong>
                        <?php if (!empty($contestant['group_name'])): ?>
                            (<?= e($contestant['group_name']) ?>)
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (!$crits): ?>
                    <p>Критерии не заданы.</p>
                <?php else: ?>
                    <table class="print-table">
                        <thead>
                        <tr>
                            <th style="width:2.5rem">№</th>
                            <th style="width:12rem">Аспект</th>
                            <th>Расшифровка аспекта</th>
                            <th style="width:7rem">Допустимые баллы</th>
                            <th style="width:5rem">Оценка</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($crits as $i => $cr): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($cr['title']) ?></td>
                                <td><?= e($cr['description'] ?: '') ?></td>
                                <td><?= e(criterion_format_allowed_label($cr)) ?></td>
                                <td class="empty-cell"></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4"><strong>Сумма по модулю</strong></td>
                            <td class="empty-cell"></td>
                        </tr>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="print-signatures">
                    <div><p>Дата ______________</p></div>
                    <div><p>Подпись эксперта ______________</p></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php endif; ?>
