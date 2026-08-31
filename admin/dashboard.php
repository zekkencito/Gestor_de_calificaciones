<?php
// Debe ser lo PRIMERO en el archivo, sin espacios/blancos antes
require_once "check_session.php";
require_once "../force_password_check.php";
require_once "php/prevent_cache.php";

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
    session_destroy();
    header("Location: /Gestor_de_calificaciones/index.php");
    exit();
}

require_once "../conection.php";

// Obtener la información del usuario actual
$user_id = $_SESSION['user_id'];

// Contar alumnos
$resAlumnos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM students");
$totalAlumnos = mysqli_fetch_assoc($resAlumnos)['total'];

// Contar docentes
$resDocentes = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM teachers");
$totalDocentes = mysqli_fetch_assoc($resDocentes)['total'];

// Contar materias
$resMaterias = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM subjects");
$totalMaterias = mysqli_fetch_assoc($resMaterias)['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <title>Panel de Administración</title>
    <!-- Bootstrap (necesario para sidebar/header/modal/dropdown) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <!-- Design System -->
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/layout.css">
    <!-- Dashboard styles -->
    <link rel="stylesheet" href="../css/admin/dashboard.css">
    <!-- External libs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Tipografía -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="page-dashboard">
    <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>

    <!-- Sidebar -->
    <?php include "../layouts/aside.php"; ?>

    <!-- Main Content -->
    <main class="ds-main">
        <?php include "../layouts/header.php"; ?>

        <div class="dash-content">

            <!-- Page Header -->
            <div class="dash-header">
                <h1 class="dash-header__title">Panel Administrativo</h1>
                <p class="dash-header__subtitle">Bienvenido al sistema de gestión de calificaciones</p>
            </div>

            <!-- Deadline Notice -->
            <div class="dash-deadline">
                <div class="dash-deadline__icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="dash-deadline__text">
                    <span class="dash-deadline__label">Fecha límite de calificaciones</span>
                    <span class="dash-deadline__date" id="fechaLimiteDashboard">Cargando...</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="dash-stats">
                <div class="dash-stat">
                    <div class="dash-stat__icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="dash-stat__data">
                        <p class="dash-stat__number"><?php echo $totalAlumnos; ?></p>
                        <p class="dash-stat__label">Total de Alumnos</p>
                    </div>
                </div>

                <div class="dash-stat">
                    <div class="dash-stat__icon">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div class="dash-stat__data">
                        <p class="dash-stat__number"><?php echo $totalDocentes; ?></p>
                        <p class="dash-stat__label">Total de Docentes</p>
                    </div>
                </div>

                <div class="dash-stat">
                    <div class="dash-stat__icon">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <div class="dash-stat__data">
                        <p class="dash-stat__number"><?php echo $totalMaterias; ?></p>
                        <p class="dash-stat__label">Total de Materias</p>
                    </div>
                </div>
            </div>

            <!-- Calendar + Chart Grid -->
            <div class="dash-grid">
                <!-- Calendar Panel -->
                <div class="dash-panel dash-panel--calendar">
                    <div class="dash-panel__body dash-panel__body--flush">
                        <div id="calendar"></div>
                    </div>
                </div>

                <!-- Chart Panel -->
                <div class="dash-panel dash-panel--chart">
                    <div class="dash-panel__header">
                        <h2 class="dash-panel__title">Aprobación por Grupo</h2>
                    </div>
                    <div class="dash-panel__body">
                        <div class="dash-chart-wrap">
                            <canvas id="chartCategorias"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/locale/es.js"></script>

    <!-- Preloader -->
    <script>
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.classList.add('loaded');
                setTimeout(() => preloader.remove(), 500);
            }
        });
    </script>

    <!-- Calendar Init -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('../api/get_calendar_events.php')
                .then(response => response.json())
                .then(data => {
                    const calendar = new FullCalendar.Calendar(
                        document.getElementById('calendar'),
                        {
                            locale: 'es',
                            buttonText: {
                                today: 'Hoy',
                                month: 'Mes',
                                week: 'Semana',
                                day: 'Día'
                            },
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            events: data.events || [],
                            height: 'auto',
                            dayHeaders: true,
                            navLinks: false,
                            editable: false,
                            nowIndicator: true
                        }
                    );
                    calendar.render();
                });
        });
    </script>

    <!-- Deadline Display -->
    <script>
        window.mostrarFechaLimiteDashboard = function(fechaLimite = null) {
            const el = document.getElementById('fechaLimiteDashboard');
            if (!el) return;

            if (fechaLimite) {
                const partes = fechaLimite.split('-');
                const fecha = new Date(
                    parseInt(partes[0], 10),
                    parseInt(partes[1], 10) - 1,
                    parseInt(partes[2], 10)
                );
                el.textContent = fecha.toLocaleDateString('es-ES', {
                    day: '2-digit', month: 'long', year: 'numeric'
                });
            } else {
                fetch('get_fecha_limite.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.fechaLimite) {
                            const partes = data.fechaLimite.split('-');
                            const fecha = new Date(
                                parseInt(partes[0], 10),
                                parseInt(partes[1], 10) - 1,
                                parseInt(partes[2], 10)
                            );
                            el.textContent = fecha.toLocaleDateString('es-ES', {
                                day: '2-digit', month: 'long', year: 'numeric'
                            });
                        } else {
                            el.textContent = 'No definida';
                        }
                    });
            }
        };
        document.addEventListener('DOMContentLoaded', () => window.mostrarFechaLimiteDashboard());
    </script>
</body>
</html>
