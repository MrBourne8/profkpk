<div class="page-title">
    <div>
        <h2>Бланки для экспертов</h2>
        <p class="subtitle"><?= e($competition['title']) ?> · <?= e($specialty['name']) ?></p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('specialty', ['id' => $specialty['id']])) ?>">← К рейтингу</a>
</div>

<div class="alert" style="background:#dcfce7;border:1px solid #16a34a;color:#14532d;padding:0.75rem 1rem;margin-bottom:1rem">
    Сборка на сервере: <strong>2026-11-17-maxpoints</strong> — если этой зелёной плашки нет, PHP читает другую папку сайта.
</div>

<div class="card">
    <p class="muted" style="margin-top:0">
        Экспертов: <?= count($printExperts) ?>,
        конкурсантов: <?= count($contestants) ?>,
        модулей: <?= count($modules) ?>.
        Выберите формат печати.
    </p>

    <div class="choose-grid">
        <a class="choose-card"
           href="<?= e(url('print_expert_blank', array_filter([
               'specialty_id' => $specialty['id'],
               'format' => 'per_contestant',
               'module_id' => $moduleId ?: null,
               'expert_id' => $expertId ?: null,
           ]))) ?>">
            <strong>1. Отдельный бланк на каждого конкурсанта</strong>
            <span>
                Для каждого эксперта — отдельный лист на каждого конкурсанта по каждому модулю.
                На бланке критерии одного участника.
            </span>
        </a>

        <a class="choose-card"
           href="<?= e(url('print_expert_blank', array_filter([
               'specialty_id' => $specialty['id'],
               'format' => 'matrix',
               'module_id' => $moduleId ?: null,
               'expert_id' => $expertId ?: null,
           ]))) ?>">
            <strong>2. Все конкурсанты на одном бланке</strong>
            <span>
                Для каждого эксперта и модуля — один лист: строки — критерии, столбцы — конкурсанты.
            </span>
        </a>
    </div>
</div>
