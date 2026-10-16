<div class="print-header">
    <h1>Итоговый протокол результатов</h1>
    <p><strong><?= e($competition['title']) ?></strong></p>
    <p>Специальность (компетенция): <?= e($specialty['name']) ?></p>
    <p>
        <?php
        $dates = trim(($competition['start_date'] ?? '') . ' — ' . ($competition['end_date'] ?? ''), ' —');
        if ($dates !== '') {
            echo 'Даты проведения: ' . e($dates);
        }
        ?>
    </p>
    <p>Оценки внесены по результатам общего сведения экспертов (одна согласованная оценка по каждому критерию).</p>
</div>

<div class="table-wrap">
    <table class="print-table">
        <thead>
        <tr>
            <th>Место</th>
            <th>№</th>
            <th>ФИО конкурсанта</th>
            <th>Группа</th>
            <?php foreach ($modules as $m): ?>
                <th><?= e($m['title']) ?></th>
            <?php endforeach; ?>
            <th>Итого</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= $row['place'] !== null ? (int) $row['place'] : '' ?></td>
                <td><?= e($row['contestant']['number']) ?></td>
                <td><?= e($row['contestant']['full_name']) ?></td>
                <td><?= e($row['contestant']['group_name'] ?: '') ?></td>
                <?php foreach ($modules as $m): ?>
                    <td class="num"><?= e(format_points($row['module_scores'][(int) $m['id']] ?? null)) ?></td>
                <?php endforeach; ?>
                <td class="num"><strong><?= e(format_points($row['total'])) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="print-meta">
    <p>Количество экспертов: <?= (int) $specialty['experts_count'] ?></p>
    <p>Дата формирования протокола: <?= e(date('d.m.Y H:i')) ?></p>
</div>

<div class="print-signatures">
    <div>
        <p>Главный эксперт __________________ / ____________ /</p>
    </div>
    <div>
        <p>Секретарь __________________ / ____________ /</p>
    </div>
</div>
