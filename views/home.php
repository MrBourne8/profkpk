<div class="page-title">
    <div>
        <h2>Конкурсы</h2>
        <p class="subtitle">Выберите конкурс или создайте новый</p>
    </div>
    <?php if (auth_is_admin()): ?>
        <a class="btn btn-primary" href="<?= e(url('competition_edit')) ?>">Добавить конкурс</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (!$competitions): ?>
        <p class="muted">Конкурсов пока нет.</p>
    <?php else: ?>
        <div class="simple-list">
            <?php foreach ($competitions as $c): ?>
                <a class="simple-list-item" href="<?= e(url('competition', ['id' => $c['id']])) ?>">
                    <span class="simple-list-title"><?= e($c['title']) ?></span>
                    <span class="simple-list-meta">
                        <span class="badge badge-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span>
                        · компетенций: <?= (int) $c['specialty_count'] ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
