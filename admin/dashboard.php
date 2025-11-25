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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">
    <link rel="stylesheet" href="../css/teacher/dashboard.css">
    <!-- TIPOGRAFIA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

    <!-- TIPOGRAFIA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    



    
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="row d-flex" style="height: 100%; width: 100%; margin: 0; padding: 0;">
       <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>
    <!-- ASIDEBAR -->
    <?php
        include "../layouts/aside.php"; 
    ?>
    <!-- END ASIDEBAR -->
    <!-- MAIN CONTENT -->
     <main class="flex-grow-1 col-9 p-0 ">
        <?php include "../layouts/header.php"; ?>
        
        <!-- Header de la página -->
        <div class="container-fluid px-4 pt-5">
            <div class="row">
                <div class="col-12" style=" justify-content: center; display: flex;">
                    <div class="page-header mb-3" style="padding-top: 5rem;">
                        <h1 class="page-title">
                            <i class="bi bi-speedometer2 me-3"></i>
                            Panel Administrativo
                        </h1>
                        <p class="page-subtitle text-muted">
                            Bienvenido al sistema de gestión de calificaciones
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="container-fluid px-4">
            <!-- Alerta de fecha límite -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm" style="background: linear-gradient(135deg, #d1ecf1, #bee5eb); border-radius: 15px;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event fs-4 me-3 text-info"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Fecha límite de calificaciones</h6>
                                <strong id="fechaLimiteDashboard" class="text-dark">Cargando...</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de estadísticas -->
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-3">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="stats-label mb-1">Total de Alumnos</p>
                                        <h3 class="stats-number mb-0"><?php echo $totalAlumnos; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-3">
                                        <i class="bi bi-person-workspace"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="stats-label mb-1">Total de Docentes</p>
                                        <h3 class="stats-number mb-0"><?php echo $totalDocentes; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-3">
                                        <i class="bi bi-journal-bookmark-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="stats-label mb-1">Total de Materias</p>
                                        <h3 class="stats-number mb-0"><?php echo $totalMaterias; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sección de gráficos y calendario -->
            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="chart-card">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-calendar-event me-2 text-primary"></i>
                                    Calendario de Eventos
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div id="calendar" class="p-3"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 mb-4">
                    <div class="chart-card">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-pie-chart me-2 text-success"></i>
                                    Porcentaje de Grupos Aprobados
                                </h5>
                            </div>
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <div id="chart" style="width: 100%; max-width: 500px; height: 400px;">
                                    <canvas id="chartCategorias"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- END MAIN CONTENT --> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
    <!-- CALENADARIO DASHBOARD -->
    <script>
        // Hide preloader when page is fully loaded
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.classList.add('loaded');
                // Remove preloader from DOM after animation completes
                setTimeout(() => {
                    preloader.remove();
                }, 500);
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            fetch('get_fecha_limite.php')
                .then(response => response.json())
                .then(data => {
                    const fechaLimite = data.fechaLimite;
                    const eventos = [];
                    if (fechaLimite) {
                        eventos.push({
                            id: 'cierre-calificaciones',
                            title: 'Cierre de calificaciones',
                            start: fechaLimite,
                            color: '#e74c3c'
                        });
                    }
                    const calendarEl = document.getElementById('calendar');
                    window.calendar = new FullCalendar.Calendar(calendarEl, {
                        locale: 'es',
                        headerToolbar: {
                            left: 'prev,next',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        events: eventos
                    });
                    window.calendar.render();
                });
        });
    </script>
    <script>
        // Mostrar la fecha límite en el dashboard (SIEMPRE desde la base de datos)
        function mostrarFechaLimiteDashboard(fechaLimite = null) {
            const el = document.getElementById('fechaLimiteDashboard');
            if (fechaLimite) {
            let fecha = new Date(fechaLimite);
            fecha.setHours(fecha.getHours() + 12);
            const opciones = { day: '2-digit', month: 'long', year: 'numeric' };
            el.textContent = fecha.toLocaleDateString('es-ES', opciones);
            } else {
            fetch('get_fecha_limite.php')
                .then(response => response.json())
                .then(data => {
                if (data.success && data.fechaLimite) {
                    let fecha = new Date(data.fechaLimite);
                    fecha.setHours(fecha.getHours() + 12);
                    const opciones = { day: '2-digit', month: 'long', year: 'numeric' };
                    el.textContent = fecha.toLocaleDateString('es-ES', opciones);
                } else {
                    el.textContent = 'No definida';
                }
                });
            }
        }
        document.addEventListener('DOMContentLoaded', () => mostrarFechaLimiteDashboard());

        // Si tienes un input para seleccionar la fecha límite, actualiza el dashboard al seleccionar
        const inputFechaLimite = document.getElementById('inputFechaLimite');
        if (inputFechaLimite) {
            inputFechaLimite.addEventListener('change', function() {
            mostrarFechaLimiteDashboard(this.value);
            });
        }
    </script>
</body>
</html>