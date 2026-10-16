<?php
/** @var string $content */
if (auth_user()) {
    auth_refresh_session_role();
}
$nav = auth_user() ? nav_context($pdo) : null;
$base = rtrim($config['base_path'] ?? '', '/');
$isAdmin = auth_is_admin();
$roleLabel = $isAdmin ? 'админ' : (auth_is_chief() ? 'главный эксперт' : 'пользователь');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? $config['app_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e($base . '/assets/style.css') ?>">
</head>
<body class="<?= auth_user() ? 'has-sidebar' : '' ?>">
<header class="app-header">
    <h1><a href="<?= e(url('home')) ?>" style="color:#fff;text-decoration:none">Профессионалы</a></h1>
    <nav>
        <?php if (auth_user()): ?>
            <?php if ($isAdmin): ?>
                <a href="<?= e(url('chief_experts')) ?>">Главные эксперты</a>
            <?php endif; ?>
            <span class="user">
                <?= e(auth_user()['display_name']) ?>
                · <?= e($roleLabel) ?>
            </span>
            <a href="<?= e(url('logout')) ?>">Выход</a>
        <?php endif; ?>
    </nav>
</header>

<div class="app-shell">
    <?php if ($nav): ?>
        <aside class="sidebar" id="app-sidebar" aria-label="Навигация">
            <div class="sidebar-top">
                <button type="button" class="sidebar-toggle" id="sidebar-toggle"
                        title="Свернуть / развернуть панель" aria-expanded="true" aria-controls="app-sidebar">
                    <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                    <span class="sidebar-text">Свернуть</span>
                </button>
            </div>

            <div class="sidebar-section">
                <a class="sidebar-link <?= $nav['route'] === 'home' ? 'active' : '' ?>"
                   href="<?= e(url('home')) ?>" title="Все конкурсы">
                    <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                    <span class="sidebar-text">Все конкурсы</span>
                </a>
                <?php if ($isAdmin): ?>
                    <a class="sidebar-link sidebar-muted" href="<?= e(url('competition_edit')) ?>" title="Новый конкурс">
                        <i class="bi bi-plus-square" aria-hidden="true"></i>
                        <span class="sidebar-text">Новый конкурс</span>
                    </a>
                    <a class="sidebar-link <?= in_array($nav['route'], ['chief_experts', 'chief_expert_edit'], true) ? 'active' : '' ?>"
                       href="<?= e(url('chief_experts')) ?>" title="Главные эксперты">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <span class="sidebar-text">Главные эксперты</span>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($nav['competitions']): ?>
                <div class="sidebar-section">
                    <div class="sidebar-label"><span class="sidebar-text">Конкурсы</span></div>
                    <?php foreach ($nav['competitions'] as $c): ?>
                        <?php
                        $isActiveComp = $nav['current_competition']
                            && (int) $nav['current_competition']['id'] === (int) $c['id'];
                        ?>
                        <a class="sidebar-link <?= $isActiveComp ? 'active' : '' ?>"
                           href="<?= e(url('competition', ['id' => $c['id']])) ?>"
                           title="<?= e($c['title']) ?>">
                            <i class="bi bi-trophy" aria-hidden="true"></i>
                            <span class="sidebar-text"><?= e($c['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($nav['current_competition']): ?>
                <div class="sidebar-section">
                    <div class="sidebar-label"><span class="sidebar-text">Компетенции</span></div>
                    <?php foreach ($nav['specialties'] as $s): ?>
                        <?php
                        $isActiveSpec = $nav['current_specialty']
                            && (int) $nav['current_specialty']['id'] === (int) $s['id'];
                        ?>
                        <a class="sidebar-link <?= $isActiveSpec ? 'active' : '' ?>"
                           href="<?= e(url('specialty', ['id' => $s['id']])) ?>"
                           title="<?= e($s['name']) ?>">
                            <i class="bi bi-bookmark" aria-hidden="true"></i>
                            <span class="sidebar-text"><?= e($s['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($isAdmin): ?>
                        <a class="sidebar-link sidebar-muted"
                           href="<?= e(url('specialty_edit', ['competition_id' => $nav['current_competition']['id']])) ?>"
                           title="Добавить компетенцию">
                            <i class="bi bi-bookmark-plus" aria-hidden="true"></i>
                            <span class="sidebar-text">Добавить компетенцию</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php
            $showSpecialtyMenu = $nav['current_specialty']
                && ($isAdmin || specialty_is_unlocked((int) $nav['current_specialty']['id']));
            ?>
            <?php if ($showSpecialtyMenu): ?>
                <div class="sidebar-section">
                    <div class="sidebar-label"><span class="sidebar-text">Действия</span></div>
                    <?php $sid = (int) $nav['current_specialty']['id']; ?>
                    <a class="sidebar-link <?= $nav['route'] === 'specialty' ? 'active' : '' ?>"
                       href="<?= e(url('specialty', ['id' => $sid])) ?>" title="Рейтинг">
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        <span class="sidebar-text">Рейтинг</span>
                    </a>
                    <?php
                    $navComp = $nav['current_competition'] ?? null;
                    $canScore = competition_allows_scoring($navComp);
                    ?>
                    <?php if ($canScore): ?>
                        <a class="sidebar-link <?= $nav['route'] === 'scores' ? 'active' : '' ?>"
                           href="<?= e(url('scores', ['id' => $sid])) ?>" title="Внести оценки">
                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            <span class="sidebar-text">Внести оценки</span>
                        </a>
                    <?php else: ?>
                        <span class="sidebar-link sidebar-muted" title="Только для активного конкурса">
                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            <span class="sidebar-text">Внести оценки</span>
                        </span>
                    <?php endif; ?>
                    <a class="sidebar-link <?= in_array($nav['route'], ['specialty_settings', 'specialty_edit', 'contestant_edit', 'expert_edit', 'module_edit', 'criterion_edit'], true) ? 'active' : '' ?>"
                       href="<?= e(url('specialty_settings', ['id' => $sid])) ?>" title="Настройки">
                        <i class="bi bi-gear" aria-hidden="true"></i>
                        <span class="sidebar-text">Настройки</span>
                    </a>
                    <a class="sidebar-link <?= $nav['route'] === 'print_results' ? 'active' : '' ?>"
                       href="<?= e(url('print_results', ['id' => $sid])) ?>" title="Печать итогов">
                        <i class="bi bi-printer" aria-hidden="true"></i>
                        <span class="sidebar-text">Печать итогов</span>
                    </a>
                    <a class="sidebar-link <?= $nav['route'] === 'print_expert_blank' ? 'active' : '' ?>"
                       href="<?= e(url('print_expert_blank', ['specialty_id' => $sid])) ?>" title="Бланки экспертов">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        <span class="sidebar-text">Бланки экспертов</span>
                    </a>
                </div>
            <?php endif; ?>
        </aside>
    <?php endif; ?>

    <main class="container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= e($flash['type'] === 'error' ? 'error' : 'success') ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        <?= $content ?>
    </main>
</div>
<?php if ($nav): ?>
<script src="<?= e($base . '/assets/app.js') ?>"></script>
<?php endif; ?>
</body>
</html>
