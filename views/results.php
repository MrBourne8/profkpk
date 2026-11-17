<div class="breadcrumbs">
    <a href="<?= e(url('specialty', ['id' => $specialty['id']])) ?>"><?= e($specialty['name']) ?></a> / Итоги
</div>
<div class="page-title">
    <div>
        <h2>Итоговые результаты</h2>
        <p class="subtitle"><?= e($competition['title']) ?> · согласованные оценки экспертов</p>
    </div>
    <div class="toolbar" style="margin:0">
        <a class="btn" href="<?= e(url('scores', ['id' => $specialty['id']])) ?>">Согласованные оценки</a>
        <a class="btn btn-primary" href="<?= e(url('print_results', ['id' => $specialty['id']])) ?>">Печать протокола</a>
    </div>
</div>

<div class="card">
    <?php if (!$rows): ?>
        <p class="muted">Нет данных для отображения.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="rank-table">
                <thead>
                <tr>
                    <th class="col-place">Место</th>
                    <th class="col-rm">РМ</th>
                    <th class="col-name">ФИО</th>
                    <th class="col-group">Группа</th>
                    <?php foreach ($modules as $m): ?>
                        <th class="num col-module"><?= e($m['title']) ?></th>
                    <?php endforeach; ?>
                    <th class="num col-total">Итого</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="place col-place"><?= $row['place'] !== null ? (int) $row['place'] : '—' ?></td>
                        <td class="col-rm"><?= e(contestant_workplace_label($row['contestant'])) ?></td>
                        <td class="col-name"><?= e($row['contestant']['full_name']) ?></td>
                        <td class="group-label col-group"><?= e($row['contestant']['group_name'] ?: '—') ?></td>
                        <?php foreach ($modules as $m): ?>
                            <td class="num col-module"><?= e(format_points($row['module_scores'][(int) $m['id']] ?? null)) ?></td>
                        <?php endforeach; ?>
                        <td class="num col-total"><strong><?= e(format_points($row['total'])) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
