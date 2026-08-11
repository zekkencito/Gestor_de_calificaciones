<aside class="sidebar-modern">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
            <li class="nav-item logo-container">
                    <img class="logo" src="../img/logo.webp" alt="Gregorio Torres Logo">
                </li>
                <li class="nav-item">
                    <a href="../teachers/dashboard.php" class="nav-link">
                        <i class="bi bi-house-door-fill me-2"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../teachers/subjects.php" class="nav-link">
                        <i class="bi bi-book-fill me-2"></i> Mis Materias
                    </a>
                </li>
                <li class="nav-item">
                    <div class="nav-link collapsible-link" data-bs-toggle="collapse" href="#usuariosMenu" role="button" aria-expanded="false" aria-controls="usuariosMenu">
                        <i class="bi bi-mortarboard-fill me-2"></i> Calificaciones <i class="bi bi-chevron-down ms-2" style="font-size: 1rem;"></i>
                    </div>
                    <div class="collapse" id="usuariosMenu">
                        <a href="../teachers/grades.php" class="nav-link sub-link">
                            <i class="bi bi-file-earmark-text-fill me-2"></i> Ver Calificaciones
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="../teachers/list.php" class="nav-link">
                        <i class="bi bi-people-fill me-2"></i> Lista de Alumnos
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<style>
    .sidebar-modern {
        background-color:rgb(236, 236, 236); /* Fondo gris claro */
        width: 190px; /* Ancho ligeramente mayor */
        min-height: 100vh; /* Para que ocupe toda la altura */
        box-shadow: 1px 10px 10px #192E4E; /* Sombra sutil */
        display: flex;
        flex-direction: column;
        padding-left: 5px;
    }

    .sidebar-content {
        padding: 5px;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .sidebar-nav {
        flex-grow: 1; /* Para que la navegación ocupe el espacio disponible */
    }

    .logo-container {
        padding-top: 9px;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .logo {
        height: 79px;
        width: 70.6px;
        display: block;
        margin: 0 auto;
    }

    .nav-link {
        padding: 0.75rem 1rem;
        color:rgb(0, 0, 0); /* Texto gris oscuro */
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
        border-radius: 0.25rem;
    }

    .nav-link:hover {
        background-color:rgb(217, 220, 224); /* Gris más claro al pasar el ratón */
        color: rgb(38, 75, 130); /* Color primario al pasar el ratón */
    }

    .nav-link i {
        font-size: 1.5rem;
        margin-right: 0.5rem;
    }

    .collapsible-link {
        cursor: pointer;
        padding: 0.6rem 1rem;
        color:rgb(0, 0, 0);
        display: flex;
        align-items: center;
        border-radius: 0.25rem;
        font-size: 0.9rem;
    }

    .collapsible-link:hover {
        background-color:rgb(217, 220, 224);
        color: rgb(38, 75, 130);
        border-radius: 0.25rem;
    }

    .sub-link {
        padding-left: 2rem;
        font-size: 0.9rem;
    }


</style>

