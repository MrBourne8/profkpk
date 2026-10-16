<div class="page-title">
    <div>
        <h2><?= e($competition['title']) ?></h2>
        <p class="subtitle">
            <span class="badge badge-<?= e($competition['status']) ?>"><?= e(status_label($competition['status'])) ?></span>
            · выберите компетенцию
        </p>
    </div>
    <?php if (auth_is_admin()): ?>
        <div class="toolbar" style="margin:0">
            <a class="btn btn-ghost" href="<?= e(url('competition_edit', ['id' => $competition['id']])) ?>">Параметры конкурса</a>
            <a class="btn btn-primary" href="<?= e(url('specialty_edit', ['competition_id' => $competition['id']])) ?>">Добавить компетенцию</a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (!$specialties): ?>
        <p class="muted">Компетенций пока нет. Добавьте первую.</p>
    <?php else: ?>
        <div class="simple-list">
            <?php foreach ($specialties as $s): ?>
                <a class="simple-list-item" href="<?= e(url('specialty', ['id' => $s['id']])) ?>">
                    <span class="simple-list-title"><?= e($s['name']) ?></span>
                    <span class="simple-list-meta">
                        участников: <?= (int) $s['contestant_count'] ?>
                        · экспертов: <?= (int) $s['experts_count'] ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
