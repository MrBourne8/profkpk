<div class="page-title">
    <div>
        <h2><?= e($specialty['name']) ?></h2>
        <p class="subtitle"><?= e($competition['title']) ?> · рейтинг конкурсантов</p>
    </div>
    <div class="toolbar" style="margin:0">
        <?php if (competition_allows_scoring($competition)): ?>
            <a class="btn" href="<?= e(url('scores', ['id' => $specialty['id']])) ?>">Внести оценки</a>
        <?php else: ?>
            <span class="btn btn-ghost" title="Доступно только для активного конкурса" style="opacity:0.55;cursor:not-allowed">Внести оценки</span>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= e(url('specialty_settings', ['id' => $specialty['id']])) ?>">Редактировать</a>
        <a class="btn btn-ghost" href="<?= e(url('print_results', ['id' => $specialty['id']])) ?>">Печать</a>
    </div>
</div>
<?php if (!competition_allows_scoring($competition)): ?>
    <div class="alert alert-info">
        Ввод оценок недоступен: конкурс в статусе «<?= e(status_label($competition['status'] ?? 'draft')) ?>».
        Нужен статус «Активный».
    </div>
<?php endif; ?>

<div class="card">
    <?php if (!$rows): ?>
        <p class="muted">Конкурсантов пока нет.</p>
        <p><a class="btn btn-primary" href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'contestants'])) ?>">Добавить конкурсантов</a></p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="rank-table">
                <thead>
                <tr>
                    <th>Место</th>
                    <th>№</th>
                    <th>ФИО</th>
                    <th>Группа</th>
                    <?php foreach ($modules as $m): ?>
                        <th class="num"><?= e($m['title']) ?></th>
                    <?php endforeach; ?>
                    <th class="num">Итого</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="place"><?= $row['place'] !== null ? (int) $row['place'] : '—' ?></td>
                        <td><?= e($row['contestant']['number']) ?></td>
                        <td><strong><?= e($row['contestant']['full_name']) ?></strong></td>
                        <td><?= e($row['contestant']['group_name'] ?: '—') ?></td>
                        <?php foreach ($modules as $m): ?>
                            <td class="num"><?= e(format_points($row['module_scores'][(int) $m['id']] ?? null)) ?></td>
                        <?php endforeach; ?>
                        <td class="num"><strong><?= e(format_points($row['total'])) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
