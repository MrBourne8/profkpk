<?php
$isSingle = !empty($expert);
$isSync = !empty($syncMode);
?>
<div class="breadcrumbs">
    <a href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'experts'])) ?>">Настройки</a> /
    Эксперты
</div>
<div class="page-title">
    <h2><?= $isSingle ? 'Редактирование эксперта' : 'Эксперты специальности' ?></h2>
</div>
<div class="card">
    <?php if ($isSingle): ?>
        <form method="post" action="<?= e(url('expert_save')) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $expert['id'] ?>">
            <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">
            <label>Номер
                <input type="number" name="number" min="1" max="6" required value="<?= (int) $expert['number'] ?>">
            </label>
            <label>ФИО
                <input type="text" name="full_name" required value="<?= e($expert['full_name']) ?>">
            </label>
            <div class="toolbar">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a class="btn btn-ghost" href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'experts'])) ?>">Отмена</a>
            </div>
        </form>
    <?php else: ?>
        <p class="muted">Будут созданы/обновлены слоты экспертов 1…<?= (int) $specialty['experts_count'] ?>.</p>
        <form method="post" action="<?= e(url('experts_sync')) ?>" class="form wide">
            <?= csrf_field() ?>
            <input type="hidden" name="specialty_id" value="<?= (int) $specialty['id'] ?>">
            <?php for ($i = 1; $i <= (int) $specialty['experts_count']; $i++): ?>
                <?php
                $existing = null;
                foreach ($experts as $ex) {
                    if ((int) $ex['number'] === $i) {
                        $existing = $ex;
                        break;
                    }
                }
                ?>
                <label>Эксперт <?= $i ?>
                    <input type="text" name="names[<?= $i ?>]"
                           value="<?= e($existing['full_name'] ?? ('Эксперт ' . $i)) ?>" required>
                </label>
            <?php endfor; ?>
            <div class="toolbar">
                <button type="submit" class="btn btn-primary">Сохранить экспертов</button>
                <a class="btn btn-ghost" href="<?= e(url('specialty_settings', ['id' => $specialty['id'], 'tab' => 'experts'])) ?>">Отмена</a>
            </div>
        </form>
    <?php endif; ?>
</div>
