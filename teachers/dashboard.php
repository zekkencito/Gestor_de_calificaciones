<?php
$preventCache = true;
$sessionStarted = true;
require_once "check_session.php";
require_once "../admin/php/prevent_cache.php";
require_once "../force_password_check.php";
require_once "../conection.php";

$fechaLimite = null;
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($res instanceof mysqli_result) {
    if ($row = $res->fetch_assoc()) {
        $fechaLimite = $row['limitDate'];
    }
} else {
    error_log("Error al consultar la fecha límite en dashboard: " . $conexion->error);
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
    
    // Obtener el ciclo escolar actual
    $currentYear = date('Y');
    $sqlCurrentYear = "SELECT idSchoolYear FROM schoolYear WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? ORDER BY startDate DESC LIMIT 1";
    $stmtCurrentYear = $conexion->prepare($sqlCurrentYear);
    $stmtCurrentYear->bind_param('ii', $currentYear, $currentYear);
    $stmtCurrentYear->execute();
    $resCY = $stmtCurrentYear->get_result();
    $currentSchoolYearId = 0; // fallback
    if ($rowCY = $resCY->fetch_assoc()) {
        $currentSchoolYearId = $rowCY['idSchoolYear'];
    }
    $stmtCurrentYear->close();

    // Contar alumnos del maestro
    $sqlAlumnos = "SELECT COUNT(DISTINCT s.idStudent) AS total
                  FROM students s
                  JOIN groups g ON s.idGroup = g.idGroup
                  JOIN teacherGroupsSubjects tgs ON tgs.idGroup = g.idGroup
                  WHERE tgs.idTeacher = ? AND s.idSchoolYear = ?";
    
    $stmt = $conexion->prepare($sqlAlumnos);
    if (!$stmt) {
        error_log("Error en consulta de alumnos: " . $conexion->error);
        die("Error al cargar la información de alumnos.");
    }
    
    $stmt->bind_param("ii", $teacher_id, $currentSchoolYearId);
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
    if (!$materiasInfo) {
        error_log("No se pudo obtener el resultado de materias en dashboard: " . $stmt->error);
    }

    // Obtener los grupos asignados al docente para el año escolar actual
    $groups = [];
    $sqlGroups = "SELECT DISTINCT g.idGroup, g.grade, g.group_
                  FROM teacherGroupsSubjects tgs
                  JOIN groups g ON tgs.idGroup = g.idGroup
                  WHERE tgs.idTeacher = ?
                  AND EXISTS (
                    SELECT 1 FROM students s 
                    WHERE s.idGroup = g.idGroup 
                    AND s.idSchoolYear = ?
                  )
                  GROUP BY g.idGroup, g.grade, g.group_
                  ORDER BY g.grade, g.group_";

    $stmtGroups = $conexion->prepare($sqlGroups);
    if ($stmtGroups) {
        $stmtGroups->bind_param("ii", $teacher_id, $currentSchoolYearId);
        if ($stmtGroups->execute()) {
            $resGroups = $stmtGroups->get_result();
            while ($row = $resGroups->fetch_assoc()) {
                $groups[] = $row;
            }
        }
        $stmtGroups->close();
    }
} else {
    // No se encontró el profesor
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /Gestor_de_calificaciones/index.php');
    exit();
}

// Contar docentes (todos, ya que no hay campo department)
$totalDocentes = 0;
try {
    $sqlDocentes = "SELECT COUNT(idTeacher) AS total FROM teachers";
    $stmt = $conexion->prepare($sqlDocentes);
    if ($stmt && $stmt->execute()) {
        $resDocentes = $stmt->get_result();
        $totalDocentes = $resDocentes->fetch_assoc()['total'];
    }
} catch (Throwable $e) {
    error_log("Error en consulta de docentes: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <title>Panel Docente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <!-- Design System -->
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/admin/dashboard.css">
    <!-- External libs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">
    <!-- Tipografía -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="page-tch-dashboard">
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
     <main class="ds-main">
        <?php
            include "../layouts/headerTeacher.php"; 
        ?>
        
        <div class="dash-content">

            <!-- Page Header -->
            <div class="dash-header">
                <h1 class="dash-header__title">Panel Docente</h1>
                <p class="dash-header__subtitle">Bienvenido. Aquí tiene un resumen de su actividad académica.</p>
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
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <div class="dash-stat__data">
                        <p class="dash-stat__number"><?php echo $totalMaterias; ?></p>
                        <p class="dash-stat__label">Materias Asignadas</p>
                    </div>
                </div>

                <div class="dash-stat">
                    <div class="dash-stat__icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="dash-stat__data">
                        <p class="dash-stat__number"><?php echo $totalAlumnos; ?></p>
                        <p class="dash-stat__label">Alumnos a mi cargo</p>
                    </div>
                </div>

                <div class="dash-stat">
                    <div class="dash-stat__icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="dash-stat__data">
                        <p class="dash-stat__number"><?php echo $materiasInfo ? mysqli_num_rows($materiasInfo) : 0; ?></p>
                        <p class="dash-stat__label">Materias Únicas</p>
                    </div>
                </div>
            </div>

            <!-- Mis Grupos -->
            <div class="dash-panel dash-panel--groups" style="margin-bottom: var(--ds-space-5);">
                <div class="dash-panel__header">
                    <h2 class="dash-panel__title">Mis Grupos</h2>
                </div>
                <div class="dash-panel__body">
                    <?php if (isset($groups) && count($groups) > 0): ?>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($groups as $g): ?>
                                <a href="list.php?grupo=<?php echo $g['idGroup']; ?>" class="btn btn-outline-primary fw-bold" style="min-width: 100px; border-radius: var(--ds-radius-md); font-family: var(--ds-font-family); padding: var(--ds-space-2) var(--ds-space-4);">
                                    <?php echo htmlspecialchars($g['grade'] . ' ' . $g['group_']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0" style="font-size: var(--ds-text-sm);">No tienes grupos asignados con alumnos en el ciclo escolar actual.</p>
                    <?php endif; ?>
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
                        <h2 class="dash-panel__title">Alumnos Aprobados</h2>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/locale/es.js"></script>
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
</body>
</html>