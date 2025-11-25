<?php
$preventCache = true;
$sessionStarted = true;
require_once "../admin/php/prevent_cache.php";
require_once "check_session.php";
require_once "../force_password_check.php";
require_once "../conection.php";

$fechaLimite = null;
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $fechaLimite = $row['limitDate'];
}

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /Gestor_de_calificaciones/index.php");
    exit();
}

// Obtener la información del usuario actual
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Primero obtener el idTeacher correspondiente al user_id
$sqlTeacher = "SELECT t.idTeacher 
               FROM teachers t 
               INNER JOIN users u ON t.idUser = u.idUser 
               WHERE u.idUser = ?";

// Preparar la consulta
$stmt = $conexion->prepare($sqlTeacher);
if (!$stmt) {
    error_log("Error en la consulta del dashboard: " . $conexion->error);
    die("Error al cargar la información del docente. Por favor, intente más tarde.");
}

// Vincular parámetros
$stmt->bind_param("i", $user_id);

// Ejecutar consulta
if (!$stmt->execute()) {
    error_log("Error al ejecutar consulta en dashboard: " . $stmt->error);
    die("Error al cargar la información. Por favor, intente más tarde.");
}

// Obtener resultados
$resTeacher = $stmt->get_result();

// Verificar si se encontró el profesor
if ($teacherData = $resTeacher->fetch_assoc()) {
    $teacher_id = $teacherData['idTeacher'];
    
    // Contar materias del usuario actual
    $sqlMaterias = "SELECT COUNT(DISTINCT tgs.idSubject) AS total 
                   FROM teacherGroupsSubjects tgs
                   WHERE tgs.idTeacher = ?";
    
    $stmt = $conexion->prepare($sqlMaterias);
    if (!$stmt) {
        error_log("Error en consulta de materias: " . $conexion->error);
        die("Error al cargar las materias. Por favor, intente más tarde.");
    }
    
    $stmt->bind_param("i", $teacher_id);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar consulta de materias: " . $stmt->error);
        die("Error al cargar la información de materias.");
    }
    
    $resMaterias = $stmt->get_result();
    $totalMaterias = $resMaterias->fetch_assoc()['total'];
    
    // Contar alumnos del maestro
    $sqlAlumnos = "SELECT COUNT(DISTINCT s.idStudent) AS total
                  FROM students s
                  JOIN groups g ON s.idGroup = g.idGroup
                  JOIN teacherGroupsSubjects tgs ON tgs.idGroup = g.idGroup
                  WHERE tgs.idTeacher = ?";
    
    $stmt = $conexion->prepare($sqlAlumnos);
    if (!$stmt) {
        error_log("Error en consulta de alumnos: " . $conexion->error);
        die("Error al cargar la información de alumnos.");
    }
    
    $stmt->bind_param("i", $teacher_id);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar consulta de alumnos: " . $stmt->error);
        die("Error al procesar la información de alumnos.");
    }
    
    $resAlumnos = $stmt->get_result();
    $totalAlumnos = $resAlumnos->fetch_assoc()['total'];
    
    // Obtener información de las materias del usuario
    $sqlMateriasInfo = "SELECT DISTINCT s.name, s.specialSubject
                       FROM teacherGroupsSubjects tgs
                       JOIN subjects s ON tgs.idSubject = s.idSubject
                       WHERE tgs.idTeacher = ?";
    
    $stmt = $conexion->prepare($sqlMateriasInfo);
    if (!$stmt) {
        error_log("Error en consulta de información de materias: " . $conexion->error);
        die("Error al cargar la información académica.");
    }
    
    $stmt->bind_param("i", $teacher_id);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar consulta de información de materias: " . $stmt->error);
        die("Error al procesar la información académica.");
    }
    
    $materiasInfo = $stmt->get_result();
} else {
    // No se encontró el profesor
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /Gestor_de_calificaciones/index.php');
    exit();
}

// Contar docentes (solo colegas del mismo departamento o escuela)
$sqlDocentes = "SELECT COUNT(DISTINCT t2.idTeacher) AS total 
               FROM teachers t1
               JOIN teachers t2 ON t1.department = t2.department  -- Asumiendo que hay un campo department
               WHERE t1.idTeacher = ?";

$stmt = $conexion->prepare($sqlDocentes);
if (!$stmt) {
    error_log("Error en consulta de docentes: " . $conexion->error);
    $totalDocentes = 0;
} else {
    $stmt->bind_param("i", $teacher_id);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar consulta de docentes: " . $stmt->error);
        $totalDocentes = 0;
    } else {
        $resDocentes = $stmt->get_result();
        $totalDocentes = $resDocentes->fetch_assoc()['total'];
    }
}

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
        include "../layouts/asideTeacher.php"; 
    ?>
    <!-- END ASIDEBAR -->
    <!-- MAIN CONTENT -->
     <main class="flex-grow-1 col-9 p-0 ">
        <?php
            include "../layouts/headerTeacher.php"; 
        ?>
        
        <!-- Header del Dashboard -->
        <div class="container-fluid px-4" style="padding-top: 8rem; height: auto;">
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-3">
                        <h1 class="page-title">
                            <i class="bi bi-speedometer2 me-3"></i>
                            Panel de Control
                        </h1>
                        <p class="page-subtitle text-muted">
                            Bienvenido. Aquí tiene un resumen de su actividad docente.
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
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="stats-card">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="stats-icon mb-3">
                                    <i class="bi bi-journal-bookmark text-primary"></i>
                                </div>
                                <h3 class="stats-number text-primary"><?php echo $totalMaterias; ?></h3>
                                <p class="stats-label text-muted mb-0">Materias Asignadas</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="stats-card">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="stats-icon mb-3">
                                    <i class="bi bi-people text-success"></i>
                                </div>
                                <h3 class="stats-number text-success"><?php echo $totalAlumnos; ?></h3>
                                <p class="stats-label text-muted mb-0">Alumnos a mi cargo</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="stats-card">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="stats-icon mb-3">
                                    <i class="bi bi-check-circle text-info"></i>
                                </div>
                                <h3 class="stats-number text-info"><?php echo mysqli_num_rows($materiasInfo); ?></h3>
                                <p class="stats-label text-muted mb-0">Materias Únicas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficas y contenido -->
        <div class="container-fluid px-4">
            <div class="row g-4">
                <!-- Calendario -->
                <div class="col-lg-6">
                    <div class="chart-card">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-calendar-event me-2 text-primary"></i>
                                    Calendario de Eventos
                                </h5>
                            </div>
                            <div class="card-body p-2">
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfica de Porcentajes -->
                <div class="col-lg-6">
                    <div class="chart-card">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-pie-chart me-2 text-success"></i>
                                    Porcentaje de Alumnos Aprobados
                                </h5>
                            </div>
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div class="chart-container">
                                    <canvas id="chartCategorias" width="400" height="400"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estilos CSS personalizados -->
        <style>
            /* Estilos para el header */
            .page-header {
                text-align: center;
                padding: 1.5rem 0 1rem 0;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 15px;
                margin-bottom: 1.5rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .page-title {
                color: #192E4E;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 0.3rem;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .page-subtitle {
                font-size: 1.1rem;
                margin-bottom: 0;
                opacity: 0.8;
            }

            /* Tarjetas de estadísticas */
            .stats-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .stats-card:hover {
                transform: translateY(-5px);
            }
            
            .stats-card .card {
                border-radius: 15px;
                background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            }
            
            .stats-icon i {
                font-size: 2.5rem;
            }
            
            .stats-number {
                font-size: 2.5rem;
                font-weight: 700;
                margin: 0.5rem 0;
            }
            
            .stats-label {
                font-size: 0.9rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* Tarjetas de gráficas */
            .chart-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .chart-card:hover {
                transform: translateY(-2px);
            }
            
            .chart-card .card {
                border-radius: 15px;
                background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
                min-height: 500px;
            }
            
            .chart-card .card-header {
                border-radius: 15px 15px 0 0;
                border-bottom: 1px solid #e9ecef;
            }
            
            .chart-container {
                max-width: 400px;
                max-height: 400px;
            }

            /* Responsividad */
            @media (max-width: 768px) {
                .page-title {
                    font-size: 2rem;
                }
                
                .page-header {
                    padding: 1rem 0 0.75rem 0;
                    margin-bottom: 1rem;
                }
                
                .stats-number {
                    font-size: 2rem;
                }
                
                .chart-card .card {
                    min-height: 400px;
                }
            }
        </style>
    </main>

     </main>
    <!-- END MAIN CONTENT --> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
    <script>
        // Mostrar la fecha límite en el dashboard (SIEMPRE desde la base de datos, en español)
        function mostrarFechaLimiteDashboard(fechaLimite = null) {
            const el = document.getElementById('fechaLimiteDashboard');
            if (!el) return;

            if (fechaLimite) {
                // Si recibe fecha como parámetro (desde el modal)
                const partes = fechaLimite.split('-');
                const fecha = new Date(
                    parseInt(partes[0], 10),
                    parseInt(partes[1], 10) - 1,
                    parseInt(partes[2], 10)
                );
                const opciones = { day: '2-digit', month: 'long', year: 'numeric' };
                el.textContent = fecha.toLocaleDateString('es-ES', opciones);
            } else {
                // Cargar desde la base de datos
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
                        const opciones = { day: '2-digit', month: 'long', year: 'numeric' };
                        el.textContent = fecha.toLocaleDateString('es-ES', opciones);
                    } else {
                        el.textContent = 'No definida';
                    }
                });
            }
        }
        document.addEventListener('DOMContentLoaded', () => mostrarFechaLimiteDashboard());
    </script>
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
</body>
</html>