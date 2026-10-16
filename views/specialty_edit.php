<div class="page-title">
    <div>
        <h2>Новая компетенция</h2>
        <p class="subtitle"><?= e($competition['title']) ?></p>
    </div>
</div>
<div class="card">
    <form method="post" action="<?= e(url('specialty_save')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="competition_id" value="<?= (int) $competition['id'] ?>">
        <label>Название компетенции
            <input type="text" name="name" required autofocus>
        </label>
        <label>Количество экспертов (3–6)
            <input type="number" name="experts_count" min="3" max="6" required value="3">
            <span class="help">Для печати индивидуальных бумажных протоколов.</span>
        </label>
        <div class="toolbar">
            <button type="submit" class="btn btn-primary">Создать</button>
            <a class="btn btn-ghost" href="<?= e(url('competition', ['id' => $competition['id']])) ?>">Отмена</a>
        </div>
    </form>
</div>
