<div class="page-title">
    <div>
        <h2>Главные эксперты</h2>
        <p class="subtitle">Учётные записи для доступа к компетенциям</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('chief_expert_edit')) ?>">Добавить главного эксперта</a>
</div>

<div class="card">
    <?php if (!$experts): ?>
        <p class="muted">Главных экспертов пока нет. Добавьте эксперта, затем назначьте его в настройках компетенции.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Логин</th>
                    <th>Компетенций</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($experts as $ex): ?>
                    <tr>
                        <td><strong><?= e($ex['display_name']) ?></strong></td>
                        <td><?= e($ex['login']) ?></td>
                        <td><?= (int) $ex['specialty_count'] ?></td>
                        <td class="actions">
                            <a class="btn btn-sm btn-ghost" href="<?= e(url('chief_expert_edit', ['id' => $ex['id']])) ?>">Изменить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
