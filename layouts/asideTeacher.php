<aside class="ds-sidebar">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="ds-sidebar__nav">
                <li class="ds-sidebar__logo">
                    <img class="ds-sidebar__logo-img" src="../img/logo.webp" alt="Gregorio Torres Logo">
                </li>
                <?php
                $currentPage = basename($_SERVER['SCRIPT_FILENAME'], '.php');
                ?>
                <li class="ds-sidebar__item">
                    <a href="../teachers/dashboard.php" class="ds-sidebar__link<?= $currentPage === 'dashboard' ? ' ds-sidebar__link--active' : '' ?>">
                        <i class="bi bi-house-door-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Inicio</span>
                    </a>
                </li>
                <li class="ds-sidebar__item">
                    <a href="../teachers/subjects.php" class="ds-sidebar__link<?= $currentPage === 'subjects' ? ' ds-sidebar__link--active' : '' ?>">
                        <i class="bi bi-book-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Mis Materias</span>
                    </a>
                </li>
                <li class="ds-sidebar__item ds-sidebar__item--collapsible">
                    <div class="ds-sidebar__link" data-bs-toggle="collapse" href="#usuariosMenu" role="button"
                        aria-expanded="<?= in_array($currentPage, ['grades', 'gradesSubject']) ? 'true' : 'false' ?>" aria-controls="usuariosMenu">
                        <i class="bi bi-mortarboard-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Calificaciones</span>
                        <i class="bi bi-chevron-down ds-sidebar__chevron"></i>
                    </div>
                    <div class="collapse<?= in_array($currentPage, ['grades', 'gradesSubject']) ? ' show' : '' ?>" id="usuariosMenu">
                        <a href="../teachers/grades.php" class="ds-sidebar__link ds-sidebar__link--sub<?= $currentPage === 'grades' ? ' ds-sidebar__link--active' : '' ?>">
                            <i class="bi bi-file-earmark-text-fill ds-sidebar__icon ds-sidebar__icon--sub"></i>
                            <span class="ds-sidebar__label">Ver Calificaciones</span>
                        </a>
                    </div>
                </li>
                <li class="ds-sidebar__item">
                    <a href="../teachers/list.php" class="ds-sidebar__link<?= $currentPage === 'list' ? ' ds-sidebar__link--active' : '' ?>">
                        <i class="bi bi-people-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Lista de Alumnos</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Sidebar overlay for mobile -->
<div class="ds-sidebar-overlay" id="ds-sidebar-overlay"></div>

<!-- Sidebar toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.ds-sidebar');
    var overlay = document.getElementById('ds-sidebar-overlay');
    var toggle = document.getElementById('ds-sidebar-toggle');

    if (sidebar) {
        sidebar.classList.remove('ds-sidebar--open');
    }
    if (overlay) {
        overlay.classList.remove('ds-sidebar-overlay--visible');
    }
    document.body.classList.remove('ds-sidebar-is-open');

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('ds-sidebar--open');
            overlay.classList.toggle('ds-sidebar-overlay--visible');
            document.body.classList.toggle('ds-sidebar-is-open');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('ds-sidebar--open');
            overlay.classList.remove('ds-sidebar-overlay--visible');
            document.body.classList.remove('ds-sidebar-is-open');
        });
    }
});
</script>
