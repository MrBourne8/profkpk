(function () {
    var KEY = 'profkpk_sidebar_collapsed';
    var sidebar = document.getElementById('app-sidebar');
    var toggle = document.getElementById('sidebar-toggle');
    if (!sidebar || !toggle) {
        return;
    }

    var label = toggle.querySelector('.sidebar-text');

    function apply(collapsed) {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        sidebar.classList.toggle('is-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.title = collapsed ? 'Развернуть панель' : 'Свернуть панель';
        if (label) {
            label.textContent = collapsed ? 'Развернуть' : 'Свернуть';
        }
        try {
            localStorage.setItem(KEY, collapsed ? '1' : '0');
        } catch (e) {}
    }

    var saved = false;
    try {
        saved = localStorage.getItem(KEY) === '1';
    } catch (e) {}
    apply(saved);

    toggle.addEventListener('click', function () {
        apply(!document.body.classList.contains('sidebar-collapsed'));
    });
})();
