<?php
// Última actualización: 2026-02-16
require_once "check_session.php";
require_once "../force_password_check.php";
require_once "../conection.php";

// Verificar conexión
if (!$conexion || $conexion->connect_error) {
    die("Error de conexión a la base de datos: " . ($conexion ? $conexion->connect_error : "conexion es null"));
}

// --- FECHA LIMITE GLOBAL PARA DESCARGAS ---
$fechaLimite = null;
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $fechaLimite = $row['limitDate'];
}
$hoy = date('Y-m-d');
$descargasHabilitadas = ($fechaLimite && $hoy > date('Y-m-d', strtotime($fechaLimite . ' +0 day')));

// Validar que el id esté en la sesión
if (!isset($_SESSION['user_id'])) {
    error_log("Error de sesión: No se encontró el ID del usuario");
    die("Error de autenticación. Por favor, inicie sesión nuevamente.");
}

// Obtener el idTeacher usando el idUser de la sesión
$idUser = $_SESSION['user_id'];
$sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
$stmtTeacher = $conexion->prepare($sqlTeacher);
if (!$stmtTeacher) {
    error_log("Error preparando consulta teacher: " . $conexion->error);
    die("Error al cargar información del docente: " . $conexion->error);
}
$stmtTeacher->bind_param("i", $idUser);
$stmtTeacher->execute();
$resTeacher = $stmtTeacher->get_result();
$rowTeacher = $resTeacher->fetch_assoc();
$stmtTeacher->close();

if (!$rowTeacher) {
    error_log("Error: No se encontró el docente para el usuario ID: " . $_SESSION['user_id']);
    die("No se pudo cargar la información del docente. Por favor, contacte al administrador.");
}
$idTeacher = $rowTeacher['idTeacher'];

// Obtener automáticamente el ciclo escolar del año actual
$currentYear = date('Y');
$sqlCurrentYear = "SELECT idSchoolYear, startDate, endDate 
                   FROM schoolYear 
                   WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? 
                   ORDER BY startDate DESC LIMIT 1";
$stmtCurrentYear = $conexion->prepare($sqlCurrentYear);
if (!$stmtCurrentYear) {
    die("Error al preparar consulta del año escolar: " . $conexion->error);
}
$stmtCurrentYear->bind_param('ii', $currentYear, $currentYear);
$stmtCurrentYear->execute();
$resultCurrentYear = $stmtCurrentYear->get_result();
$currentSchoolYear = $resultCurrentYear->fetch_assoc();
$stmtCurrentYear->close();

if (!$currentSchoolYear) {
    die("No se encontró un ciclo escolar para el año actual (" . $currentYear . "). Por favor, contacta al administrador.");
}

$selectedSchoolYear = $currentSchoolYear['idSchoolYear'];

// Obtener los trimestres del ciclo escolar actual y detectar el trimestre actual
$sqlQuarters = "SELECT idSchoolQuarter, name, description, startDate, endDate 
                FROM schoolQuarter 
                WHERE idSchoolYear = ? 
                ORDER BY idSchoolQuarter ASC";
$stmtQuarters = $conexion->prepare($sqlQuarters);
if (!$stmtQuarters) {
    die("Error al preparar consulta de trimestres: " . $conexion->error);
}
$stmtQuarters->bind_param('i', $currentSchoolYear['idSchoolYear']);
$stmtQuarters->execute();
$resultQuarters = $stmtQuarters->get_result();
$quarters = [];
$currentQuarter = null;
$currentDate = date('Y-m-d');
while ($quarter = $resultQuarters->fetch_assoc()) {
    $quarters[] = $quarter;
    // Detectar el trimestre actual basado en la fecha
    if ($quarter['startDate'] && $quarter['endDate']) {
        if ($currentDate >= $quarter['startDate'] && $currentDate <= $quarter['endDate']) {
            $currentQuarter = $quarter;
        }
    }
}
$stmtQuarters->close();

// Si no se encontró trimestre actual por fecha, usar el primero disponible
if (!$currentQuarter && count($quarters) > 0) {
    $currentQuarter = $quarters[0];
}

$selectedQuarter = $currentQuarter ? $currentQuarter['idSchoolQuarter'] : null;

// Obtener solo los grupos asignados al docente autenticado para el año escolar actual
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
$stmtGroups->bind_param("ii", $idTeacher, $selectedSchoolYear);
$stmtGroups->execute();
$resGroups = $stmtGroups->get_result();
while ($row = $resGroups->fetch_assoc()) {
    $groups[] = $row;
}
$stmtGroups->close();

// Determinar el grupo seleccionado
$selectedGroup = isset($_GET['grupo']) ? intval($_GET['grupo']) : "";

// Obtener alumnos del grupo seleccionado
$students = [];
if ($selectedGroup) {
    $sqlStudents = "SELECT s.idStudent, s.schoolNum, ui.lastnamePa, ui.lastnameMa, ui.names, g.grade, g.group_, s.idStudentStatus, s.curp,
        t.tutorName, t.tutorLastnamePa, t.tutorLastnameMa, t.tutorPhone, t.tutorAddress, t.tutorEmail, t.ine as tutorIne,
        st.nomenclature, st.description
        FROM students s
        JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
        JOIN groups g ON s.idGroup = g.idGroup
        LEFT JOIN tutors t ON s.idTutor = t.idTutor
        LEFT JOIN studentStatus st ON s.idStudentStatus = st.idStudentStatus
        WHERE s.idGroup = ? AND s.idSchoolYear = ?
        ORDER BY ui.lastnamePa, ui.lastnameMa, ui.names";
    
    $stmt = $conexion->prepare($sqlStudents);
    $stmt->bind_param("ii", $selectedGroup, $selectedSchoolYear);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <title>Lista de Alumnos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Design System -->
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/layout.css">
    <!-- Page styles -->
    <link rel="stylesheet" href="../css/teacher/list.css">
    <link rel="stylesheet" href="../css/admin/student.css">
    <!-- Tipografía -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="page-tch-list">
    <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>
    <?php
        include "../layouts/asideTeacher.php"; 
    ?>
    <main class="ds-main">
        <?php
            include "../layouts/headerTeacher.php"; 
        ?> 
        
        <!-- Header de la página -->
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">Lista de Alumnos</h1>
                <p class="page-subtitle">Consulta y gestiona la información de tus estudiantes</p>
            </div>

        <!-- Contenido principal -->
        <div class="container-fluid px-4">
            <!-- Panel de filtros -->
            <div class="tch-filters">
                <div class="tch-filter--info">
                    <div class="tch-filter__info-bar">
                        <div>
                            <i class="bi bi-calendar-date"></i> <strong>Año Escolar:</strong>
                            <?php echo substr($currentSchoolYear['startDate'], 0, 4); ?>
                        </div>
                        <div>
                            <i class="bi bi-calendar3"></i> <strong>Trimestre:</strong>
                            <?php echo $currentQuarter ? htmlspecialchars($currentQuarter['name']) : 'No definido'; ?>
                        </div>
                    </div>
                </div>
                <div class="tch-filter">
                    <label id="labelGrupo" for="grupo" class="tch-filter__label">Grupo:</label>
                    <select class="tch-filter__select" id="grupo">
                        <option value="" selected>Seleccionar grupo</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?php echo $g['idGroup']; ?>" <?php if ($selectedGroup == $g['idGroup']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($g['grade'] . '° ' . $g['group_']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tch-actions" id="contenedorBotonDescargar">
                    <button type="button" id="descargarGrupoBtn"
                            class="tch-btn <?php echo $descargasHabilitadas ? 'tch-btn--success' : 'tch-btn--outline'; ?>"
                            <?php if(!$descargasHabilitadas) echo 'disabled title="Las descargas se habilitarán después del ' . date('d/m/Y', strtotime($fechaLimite)) . '"'; ?>>
                        <i class="fas fa-download"></i>
                        <?php echo $descargasHabilitadas ? 'Descargar PDFs del Grupo' : 'Descarga después del ' . date('d/m/Y', strtotime($fechaLimite)); ?>
                    </button>
                </div>
            </div>

            <!-- Tabla de estudiantes -->
            <div id="contenedorTabla" class="<?php echo !$selectedGroup ? 'd-none' : ''; ?>">
                <div class="tch-table-wrap">
                    <div class="tch-table-header">
                        <h3 class="tch-table-title">Estudiantes Registrados</h3>
                    </div>
                    <div class="tch-table-responsive">
                        <table class="tch-table" id="tabla">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Apellido Paterno</th>
                                    <th>Apellido Materno</th>
                                    <th>Nombres</th>
                                    <th>Grado</th>
                                    <th>Grupo</th>
                                    <th>Estado</th>
                                    <th class="text-center">Boleta</th>
                                    <th class="text-center">Ver Información</th>
                                    <th class="text-center">Bitácora Incidencias</th>
                                </tr>
                            </thead>
                            <tbody id="alumnos-tbody">
                                <?php if ($selectedGroup && count($students) > 0): ?>
                                    <?php foreach ($students as $i => $student): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($student['lastnamePa']); ?></td>
                                            <td><?php echo htmlspecialchars($student['lastnameMa']); ?></td>
                                            <td><?php echo htmlspecialchars($student['names']); ?></td>
                                            <td><?php echo htmlspecialchars($student['grade']); ?>°</td>
                                            <td><?php echo htmlspecialchars($student['group_']); ?></td>
                                            <td>
                                                <?php
                                                    $badgeMap = [
                                                        'AC' => 'tch-badge--success',
                                                        'BA' => 'tch-badge--danger',
                                                        'RE' => 'tch-badge--warning',
                                                        'EG' => 'tch-badge--primary',
                                                        'IN' => 'tch-badge--secondary',
                                                        'TR' => 'tch-badge--info',
                                                        'RC' => 'tch-badge--secondary',
                                                        'EX' => 'tch-badge--neutral',
                                                    ];
                                                    $badgeClass = $badgeMap[$student['nomenclature']] ?? 'tch-badge--secondary';
                                                    $badgeText = $student['description'] ?: '-';
                                                ?>
                                                <span class="tch-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="tch-btn--icon btn-boleta"
                                                    data-bs-toggle="modal" data-bs-target="#modalCamposFormativos"
                                                    data-id="<?php echo $student['idStudent']; ?>"
                                                    data-nombres="<?php echo htmlspecialchars($student['names']); ?>"
                                                    data-paterno="<?php echo htmlspecialchars($student['lastnamePa']); ?>"
                                                    data-materno="<?php echo htmlspecialchars($student['lastnameMa']); ?>"
                                                    data-grade="<?php echo htmlspecialchars($student['grade']); ?>"
                                                    data-grupo="<?php echo htmlspecialchars($student['group_']); ?>"
                                                    data-curp="<?php echo htmlspecialchars($student['curp'] ?? ''); ?>"
                                                    data-tutornombres="<?php echo htmlspecialchars($student['tutorName'] ?? ''); ?>"
                                                    data-tutorpaterno="<?php echo htmlspecialchars($student['tutorLastnamePa'] ?? ''); ?>"
                                                    data-tutormaterno="<?php echo htmlspecialchars($student['tutorLastnameMa'] ?? ''); ?>"
                                                >
                                                    <i class="bi bi-file-earmark-text-fill"></i>
                                                </button>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="tch-btn--icon btn-info"
                                                    data-id="<?php echo $student['idStudent']; ?>"
                                                    data-nombres="<?php echo htmlspecialchars($student['names']); ?>"
                                                    data-paterno="<?php echo htmlspecialchars($student['lastnamePa']); ?>"
                                                    data-materno="<?php echo htmlspecialchars($student['lastnameMa']); ?>"
                                                    data-status="<?php echo htmlspecialchars($student['idStudentStatus']); ?>"
                                                    data-grupo="<?php echo htmlspecialchars($student['group_']); ?>"
                                                    data-grade="<?php echo htmlspecialchars($student['grade']); ?>"
                                                    data-curp="<?php echo htmlspecialchars($student['curp'] ?? ''); ?>"
                                                    data-bs-toggle="modal" data-bs-target="#showModal"
                                                    data-tutornombres="<?php echo htmlspecialchars($student['tutorName'] ?? ''); ?>"
                                                    data-tutorpaterno="<?php echo htmlspecialchars($student['tutorLastnamePa'] ?? ''); ?>"
                                                    data-tutormaterno="<?php echo htmlspecialchars($student['tutorLastnameMa'] ?? ''); ?>"
                                                    data-tutoremail="<?php echo htmlspecialchars($student['tutorEmail'] ?? ''); ?>"
                                                    data-tutortelefono="<?php echo htmlspecialchars($student['tutorPhone'] ?? ''); ?>"
                                                    data-tutordireccion="<?php echo htmlspecialchars($student['tutorAddress'] ?? ''); ?>"
                                                    data-tutorine="<?php echo htmlspecialchars($student['tutorIne'] ?? ''); ?>"
                                                >
                                                    <i class="bi bi-person-fill"></i>
                                                </button>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="tch-btn--icon btn-reporte" data-bs-toggle="modal" data-bs-target="#reportModal" data-id="<?php echo $student['idStudent']; ?>" data-nombres="<?php echo htmlspecialchars($student['names']); ?>" data-paterno="<?php echo htmlspecialchars($student['lastnamePa']); ?>" data-materno="<?php echo htmlspecialchars($student['lastnameMa']); ?>">
                                                    <i class="bi bi-file-earmark-person-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php elseif($selectedGroup): ?>
                                    <tr><td colspan="10">
                                        <div class="tch-empty">
                                            <i class="bi bi-people tch-empty__icon"></i>
                                            <p class="tch-empty__title">No hay alumnos en este grupo</p>
                                        </div>
                                    </td></tr>
                                <?php else: ?>
                                    <tr><td colspan="10">
                                        <div class="tch-empty">
                                            <i class="bi bi-search tch-empty__icon"></i>
                                            <p class="tch-empty__title">Seleccione un grupo para ver los alumnos</p>
                                        </div>
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    </main>
    <!-- MODAL SHOW -->
    <div class="modal fade tch-modal" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="bi bi-person-circle me-2"></i>
                        Información del Estudiante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Información Personal -->
                    <h6 class="text-black border-bottom pb-2 mb-3">
                        <i class="bi bi-person-badge me-2"></i>
                        Datos Personales
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombres:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-nombres">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-apellidos">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CURP:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-curp">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grado:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-grado">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grupo:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-grupo">-</p>
                        </div>
                    </div>

                    <!-- Información del Tutor -->
                    <h6 class="text-black border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-person-hearts me-2"></i>
                        Información del Tutor
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombres:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-tutornombres">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-tutorapellidos">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">INE:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-tutorine">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-tutoremail">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-tutortelefono">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dirección:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-tutordireccion">-</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL BOLETA -->
    <div class="modal fade tch-modal" id="modalCamposFormativos" tabindex="-1" aria-labelledby="modalCamposFormativosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCamposFormativosLabel">
                        <i class="bi bi-file-earmark-text-fill me-2"></i>
                        Boleta del Estudiante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="divCamposFormativos">
                        <h6 class="text-black border-bottom pb-2 mb-3">
                            <i class="bi bi-journal-text me-2"></i>
                            Calificaciones
                        </h6>
                        <div id="loadingGrades" class="tch-spinner text-center my-4">
                            <div class="tch-spinner__ring"></div>
                            <p class="tch-spinner__text">Cargando calificaciones...</p>
                        </div>
                        <ul class="list-group shadow-sm" id="gradesList"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                    <button id="btnVerDetalles" type="button"
                            class="tch-btn tch-btn--primary"
                            <?php echo !$descargasHabilitadas ? 'disabled' : ''; ?>
                            <?php if(!$descargasHabilitadas) echo 'title="Disponible después del ' . date('d/m/Y', strtotime($fechaLimite)) . '"'; ?>>
                        <?php echo $descargasHabilitadas ? 'Imprimir boleta' : 'Boleta disponible después del ' . date('d/m/Y', strtotime($fechaLimite)); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL VERIFICAR BITÁCORA -->
    <div class="modal fade tch-modal" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">
                        <i class="bi bi-file-earmark-person-fill me-2"></i>Bitácora de Incidencias del Estudiante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <!-- Información del Estudiante -->
                    <h6 class="text-black border-bottom pb-2 mb-3">
                        <i class="bi bi-person-circle me-2"></i>
                        Información del Estudiante
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nombre:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="report-student-name">-</p>
                        </div>
                    </div>

                    <div id="reportLoadingIndicator" class="tch-spinner">
                        <div class="tch-spinner__ring"></div>
                        <p class="tch-spinner__text">Verificando bitácora...</p>
                    </div>

                    <div id="reportExistsContent" class="d-none">
                        <div class="tch-alert-danger" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Este estudiante tiene <strong><span id="reportCount">0</span></strong> bitácoras registrado(s).
                        </div>
                        <div class="tch-table-responsive">
                            <table class="tch-report-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Docente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="reportsList"></tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="tch-btn tch-btn--primary" id="btnAddAnotherReport">
                                <i class="bi bi-plus-circle me-2"></i>Agregar Nueva Bitácora
                            </button>
                        </div>
                    </div>

                    <div id="reportNotExistsContent" class="d-none">
                        <div class="tch-alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Este estudiante no tiene bitácoras registradas.
                        </div>
                        <p class="text-center">¿Desea crear una nueva bitácora para este estudiante?</p>
                        <div class="text-center mt-3">
                            <button type="button" class="tch-btn tch-btn--primary" id="btnCreateReport">
                                <i class="bi bi-file-earmark-plus me-2"></i>Crear Nueva Bitácora
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLES DE REPORTE -->
    <div class="modal fade tch-modal" id="reportDetailsModal" tabindex="-1" aria-labelledby="reportDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportDetailsModalLabel">
                        <i class="bi bi-info-circle me-2"></i>Detalles de la Bitácora
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="detail-report-date">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Docente:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="detail-report-teacher">-</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción:</label>
                            <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="detail-report-desc" style="min-height: 80px;">-</div>
                        </div>
                        <div class="col-12" id="detail-report-obs-container">
                            <label class="form-label">Observaciones:</label>
                            <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="detail-report-obs" style="min-height: 60px;">-</div>
                        </div>
                    </div>
                    <div class="mt-3 text-end text-muted">
                        <small id="detail-report-created"></small>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light d-flex justify-content-end gap-2 py-3 px-4 rounded-bottom">
                    <button type="button" class="tch-btn tch-btn--outline tch-btn--sm m-0" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                    <button type="button" class="tch-btn tch-btn--primary tch-btn--sm m-0 px-4 shadow-sm" id="btnImprimirReporte">
                        <i class="bi bi-printer me-2"></i>Imprimir reporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CREAR NUEVO REPORTE -->
    <div class="modal fade tch-modal" id="createReportModal" tabindex="-1" aria-labelledby="createReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createReportModalLabel">
                        <i class="bi bi-file-earmark-plus me-2"></i>Crear Nueva Bitácora
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="reportForm">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="modal-body">
                        <input type="hidden" id="reportStudentId" name="studentId">
                        <!-- Información del Estudiante -->
                        <h6 class="text-black border-bottom pb-2 mb-3">
                            <i class="bi bi-person-circle me-2"></i>
                            Información del Estudiante
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nombre:</label>
                                <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="create-report-student-name">-</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reportFecha" class="form-label">Fecha: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reportFecha" name="fecha" placeholder="Seleccione una fecha" readonly required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reportDescripcion" class="form-label">Descripción: <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reportDescripcion" name="descripcion" rows="4"
                                      placeholder="Describa detalladamente la situación..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="reportObservaciones" class="form-label">Observaciones:</label>
                            <textarea class="form-control" id="reportObservaciones" name="observaciones" rows="3"
                                      placeholder="Agregue observaciones adicionales (opcional)..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="tch-btn tch-btn--primary">
                            <i class="bi bi-check-circle me-1"></i>
                            Guardar Bitácora
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL SHOW-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
        <!-- Scripts para manejar la carga dinámica de boletas -->
    <script>
        // Constantes PHP para año y trimestre actual
        const currentSchoolYearId = <?php echo $selectedSchoolYear; ?>;
        const currentSchoolQuarterId = <?php echo $selectedQuarter ? $selectedQuarter : 'null'; ?>;
        
        // Variables para los elementos del DOM
        const grupoSelect = document.getElementById('grupo');
        let selectedStudentId = '';
        let studentName = '';
        
        // Función para mostrar un mensaje en la consola para depuración
        function debug(msg) {
            // Debug function disabled for production
        }

        // Event listener para el grupo - carga dinámica sin recargar página
        grupoSelect.addEventListener('change', function() {
            const idGroup = this.value;
            const contenedorTabla = document.getElementById('contenedorTabla');
            const tbody = document.getElementById('alumnos-tbody');
            
            if (!idGroup) {
                // Si no hay grupo seleccionado, ocultar tabla
                contenedorTabla.classList.add('d-none');
                tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">
                    <div class="tch-empty">
                        <i class="tch-empty__icon bi bi-search"></i>
                        <p class="tch-empty__title">Seleccione un grupo para ver los alumnos.</p>
                    </div>
                </td></tr>`;
                return;
            }
            
            // Mostrar spinner de carga
            tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">
                <div class="tch-spinner" role="status">
                    <div class="tch-spinner__ring"></div>
                </div>
                <p class="tch-empty__title">Cargando estudiantes...</p>
            </td></tr>`;
            
            // Mostrar la tabla
            contenedorTabla.classList.remove('d-none');
            
            // Cargar estudiantes mediante AJAX
            fetch(`get_students.php?grupo=${idGroup}&schoolYear=${currentSchoolYearId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.students && data.students.length > 0) {
                        // Renderizar estudiantes
                        tbody.innerHTML = '';
                        data.students.forEach((student, index) => {
                            const row = createStudentRow(student, index + 1);
                            tbody.appendChild(row);
                        });
                    } else if (data.success && data.students.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">
                            <div class="tch-empty">
                                <i class="tch-empty__icon bi bi-people"></i>
                                <p class="tch-empty__title">No hay alumnos en este grupo.</p>
                            </div>
                        </td></tr>`;
                    } else {
                        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">
                            <div class="tch-alert-danger">Error al cargar estudiantes</div>
                        </td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">
                        <div class="tch-alert-danger">Error de conexión</div>
                    </td></tr>`;
                });
        });
        
        // Función para crear una fila de estudiante
        function createStudentRow(student, number) {
            const tr = document.createElement('tr');
            
            // Determinar badge de estado
            let statusBadge = '<span class="tch-badge tch-badge--secondary">-</span>';
            const statusMap = {
                'AC': 'success',
                'BA': 'danger',
                'RE': 'warning',
                'EG': 'primary',
                'IN': 'secondary',
                'TR': 'info',
                'RC': 'secondary',
                'EX': 'neutral'
            };
            if (student.nomenclature && statusMap[student.nomenclature]) {
                statusBadge = `<span class="tch-badge tch-badge--${statusMap[student.nomenclature]}">${student.description || student.nomenclature}</span>`;
            }
            
            tr.innerHTML = `
                <td>${number}</td>
                <td>${escapeHtml(student.lastnamePa)}</td>
                <td>${escapeHtml(student.lastnameMa)}</td>
                <td>${escapeHtml(student.names)}</td>
                <td>${escapeHtml(student.grade)}°</td>
                <td>${escapeHtml(student.group_)}</td>
                <td>${statusBadge}</td>
                <td class="text-center">
                    <button type="button" class="tch-btn--icon btn-boleta"
                        data-bs-toggle="modal" data-bs-target="#modalCamposFormativos"
                        data-id="${student.idStudent}"
                        data-nombres="${escapeHtml(student.names)}"
                        data-paterno="${escapeHtml(student.lastnamePa)}"
                        data-materno="${escapeHtml(student.lastnameMa)}"
                        data-grade="${escapeHtml(student.grade)}"
                        data-grupo="${escapeHtml(student.group_)}"
                        data-curp="${escapeHtml(student.curp || '')}"
                        data-tutornombres="${escapeHtml(student.tutorName || '')}"
                        data-tutorpaterno="${escapeHtml(student.tutorLastnamePa || '')}"
                        data-tutormaterno="${escapeHtml(student.tutorLastnameMa || '')}">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </button>
                </td>
                <td class="text-center">
                    <button type="button" class="tch-btn--icon btn-info"
                        data-id="${student.idStudent}"
                        data-nombres="${escapeHtml(student.names)}"
                        data-paterno="${escapeHtml(student.lastnamePa)}"
                        data-materno="${escapeHtml(student.lastnameMa)}"
                        data-status="${student.idStudentStatus}"
                        data-grupo="${escapeHtml(student.group_)}"
                        data-grade="${escapeHtml(student.grade)}"
                        data-curp="${escapeHtml(student.curp || '')}"
                        data-bs-toggle="modal" data-bs-target="#showModal"
                        data-tutornombres="${escapeHtml(student.tutorName || '')}"
                        data-tutorpaterno="${escapeHtml(student.tutorLastnamePa || '')}"
                        data-tutormaterno="${escapeHtml(student.tutorLastnameMa || '')}"
                        data-tutoremail="${escapeHtml(student.tutorEmail || '')}"
                        data-tutortelefono="${escapeHtml(student.tutorPhone || '')}"
                        data-tutordireccion="${escapeHtml(student.tutorAddress || '')}"
                        data-tutorine="${escapeHtml(student.tutorIne || '')}">
                        <i class="bi bi-person-fill"></i>
                    </button>
                </td>
                <td class="text-center">
                    <button type="button" class="tch-btn--icon btn-reporte"
                        data-bs-toggle="modal" data-bs-target="#reportModal" 
                        data-id="${student.idStudent}" 
                        data-nombres="${escapeHtml(student.names)}" 
                        data-paterno="${escapeHtml(student.lastnamePa)}" 
                        data-materno="${escapeHtml(student.lastnameMa)}">
                        <i class="bi bi-file-earmark-person-fill"></i>
                    </button>
                </td>
            `;
            
            return tr;
        }
        
        // Función auxiliar para escapar HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Función para obtener las calificaciones del estudiante
        function loadStudentGrades(studentId, schoolYearId, quarterId) {
            const gradesList = document.getElementById('gradesList');
            const loadingIndicator = document.getElementById('loadingGrades');
            
            loadingIndicator.classList.remove('d-none');
            gradesList.innerHTML = '';
            
            // Obtener las materias asignadas al profesor para este estudiante
            // Cargar materias agrupadas por área de aprendizaje
            fetch(`get_subjects.php?idStudent=${studentId}&idSchoolYear=${schoolYearId}&idSchoolQuarter=${quarterId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(subjectsData => {
                    console.log('Datos de materias recibidos:', subjectsData);
                    if (!subjectsData.success || !subjectsData.subjects || subjectsData.subjects.length === 0) {
                        gradesList.innerHTML = '<div class="tch-alert-info">No hay materias asignadas para este estudiante</div>';
                        return;
                    }

                    // Agrupar materias por área de aprendizaje
                    const learningAreas = {};
                    subjectsData.subjects.forEach(subject => {
                        const areaId = subject.idLearningArea;
                        const areaName = subject.learningAreaName;
                        
                        if (!learningAreas[areaId]) {
                            learningAreas[areaId] = {
                                name: areaName,
                                subjects: []
                            };
                        }
                        learningAreas[areaId].subjects.push(subject);
                    });

                    // Para cada materia, obtener sus promedios
                    const promises = subjectsData.subjects.map(subject => {
                        return fetch(`getAveragesBySubject.php?idSubject=${subject.idSubject}&idSchoolYear=${schoolYearId}&idSchoolQuarter=${quarterId}&idStudent=${studentId}`)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status} for subject ${subject.name}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                return {...data, subjectName: subject.name};
                            });
                    });

                    return Promise.all(promises).then(results => ({
                        results: results,
                        subjectsData: subjectsData
                    }));
                })
                .then(({results, subjectsData}) => {
                    console.log('Results:', results);
                    console.log('SubjectsData:', subjectsData);
                    if (!results) return;

                    // Asociar calificaciones con materias y áreas
                    let subjectsWithGrades = [];
                    results.forEach((data, index) => {
                        const subject = subjectsData.subjects[index];
                        let average = 0;
                        
                        if (data.success && data.students && data.students.length > 0) {
                            const studentData = data.students[0];
                            average = parseFloat(studentData.average) || 0;
                        }
                        
                        subjectsWithGrades.push({
                            ...subject,
                            average: average
                        });
                    });

                    // Agrupar por área de aprendizaje con calificaciones
                    const learningAreasWithGrades = {};
                    subjectsWithGrades.forEach(subject => {
                        const areaId = subject.idLearningArea;
                        const areaName = subject.learningAreaName;
                        
                        if (!learningAreasWithGrades[areaId]) {
                            learningAreasWithGrades[areaId] = {
                                name: areaName,
                                subjects: [],
                                totalGrade: 0,
                                subjectCount: 0
                            };
                        }
                        
                        learningAreasWithGrades[areaId].subjects.push(subject);
                        learningAreasWithGrades[areaId].totalGrade += parseFloat(subject.average);
                        learningAreasWithGrades[areaId].subjectCount++;
                    });

                    // Calcular promedio por área
                    Object.values(learningAreasWithGrades).forEach(area => {
                        area.average = area.subjectCount > 0 ? ceilToOneDecimal(area.totalGrade / area.subjectCount) : 0;
                    });

                    return {
                        success: true,
                        learningAreas: learningAreasWithGrades,
                        subjects: subjectsWithGrades
                    };
                })
                .then(data => {
                    loadingIndicator.classList.add('d-none');
                    
                    if (!data.success) {
                        gradesList.innerHTML = `<div class="tch-alert-danger">${data.message || 'Error al cargar calificaciones'}</div>`;
                        return;
                    }
                    
                    if (!data.subjects || data.subjects.length === 0) {
                        gradesList.innerHTML = '<div class="tch-alert-info">No hay calificaciones disponibles para este período</div>';
                        return;
                    }
                    
                    // Calcular promedio general considerando TODAS las áreas de aprendizaje
                    const areaAverages = Object.values(data.learningAreas).map(area => area.average);
                    const generalAverage = areaAverages.length > 0 ? ceilToOneDecimal(areaAverages.reduce((sum, avg) => sum + avg, 0) / areaAverages.length) : 0;

                    // Mostrar promedio general
                    const avgItem = document.createElement('div');
                    avgItem.className = 'tch-alert-summary';
                    
                    // Determinar el color del promedio
                    let avgBadgeClass = 'tch-badge--secondary';
                    if (generalAverage >= 9) avgBadgeClass = 'tch-badge--success';
                    else if (generalAverage >= 7) avgBadgeClass = 'tch-badge--warning';
                    else if (generalAverage >= 0) avgBadgeClass = 'tch-badge--danger';
                    
                    avgItem.innerHTML = `
                        PROMEDIO GENERAL: <span class="tch-badge ${avgBadgeClass}">${Number(generalAverage || 0).toFixed(1)}</span>
                    `;
                    gradesList.appendChild(avgItem);
                    
                    // Crear tabla de áreas de aprendizaje
                    const table = document.createElement('table');
                    table.className = 'tch-report-table';
                    
                    // Cabecera de la tabla
                    const thead = document.createElement('thead');
                    thead.className = 'tch-report-table__head';
                    thead.innerHTML = `
                        <tr>
                            <th>Campo Formativo</th>
                            <th>Materia</th>
                            <th>Calificación</th>
                        </tr>
                    `;
                    table.appendChild(thead);
                    
                    // Cuerpo de la tabla
                    const tbody = document.createElement('tbody');
                    
                    Object.values(data.learningAreas).forEach(area => {
                        // Primera fila del área con rowspan
                        const firstSubject = area.subjects[0];
                        const firstRow = document.createElement('tr');
                        
                        // Celda del área (con rowspan)
                        const areaCell = document.createElement('td');
                        areaCell.rowSpan = area.subjects.length;
                        areaCell.className = 'tch-report-table__area-cell';
                        
                        // Color para el promedio del área
                        let areaBadgeClass = 'tch-badge--secondary';
                        if (area.average >= 9) areaBadgeClass = 'tch-badge--success';
                        else if (area.average >= 7) areaBadgeClass = 'tch-badge--warning';
                        else if (area.average >= 0) areaBadgeClass = 'tch-badge--danger';
                        
                        areaCell.innerHTML = `
                            ${area.name}<br>
                            <small class="tch-badge ${areaBadgeClass} tch-badge--pill mt-1">${Number(area.average || 0).toFixed(1)}</small>
                        `;
                        firstRow.appendChild(areaCell);
                        
                        // Celda de la primera materia
                        const subjectCell = document.createElement('td');
                        subjectCell.textContent = firstSubject.name;
                        firstRow.appendChild(subjectCell);
                        
                        // Celda de la primera calificación
                        const gradeCell = document.createElement('td');
                        gradeCell.className = 'text-center';
                        
                        let gradeBadgeClass = 'tch-badge--secondary';
                        if (firstSubject.average >= 9) gradeBadgeClass = 'tch-badge--success';
                        else if (firstSubject.average >= 7) gradeBadgeClass = 'tch-badge--warning';
                        else if (firstSubject.average >= 0) gradeBadgeClass = 'tch-badge--danger';
                        
                        gradeCell.innerHTML = `<span class="tch-badge ${gradeBadgeClass}">${Number(firstSubject.average || 0).toFixed(1)}</span>`;
                        firstRow.appendChild(gradeCell);
                        
                        tbody.appendChild(firstRow);
                        
                        // Resto de materias del área
                        for (let i = 1; i < area.subjects.length; i++) {
                            const subject = area.subjects[i];
                            const row = document.createElement('tr');
                            
                            // Solo materia y calificación (no área)
                            const subjectCell = document.createElement('td');
                            subjectCell.textContent = subject.name;
                            row.appendChild(subjectCell);
                            
                            const gradeCell = document.createElement('td');
                            gradeCell.className = 'text-center';
                            
                            let gradeBadgeClass = 'tch-badge--secondary';
                            if (subject.average >= 9) gradeBadgeClass = 'tch-badge--success';
                            else if (subject.average >= 7) gradeBadgeClass = 'tch-badge--warning';
                            else if (subject.average >= 0) gradeBadgeClass = 'tch-badge--danger';
                            
                            gradeCell.innerHTML = `<span class="tch-badge ${gradeBadgeClass}">${Number(subject.average || 0).toFixed(1)}</span>`;
                            row.appendChild(gradeCell);
                            
                            tbody.appendChild(row);
                        }
                    });
                    
                    table.appendChild(tbody);
                    gradesList.appendChild(table);
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    loadingIndicator.classList.add('d-none');
                    gradesList.innerHTML = '<div class="tch-alert-danger">Error al cargar calificaciones: ' + error.message + '</div>';
                });
        }
        
        // Función para redondear hacia arriba con 1 decimal (igual que saveGrades.php)
        function ceilToOneDecimal(value) {
            return Math.ceil(value * 10) / 10;
        }

        // Evento: Botón Ver detalles (Imprimir boleta) (Imprimir boleta)
        document.getElementById('btnVerDetalles').addEventListener('click', function() {
            // Verificar si las descargas están habilitadas
            <?php if(!$descargasHabilitadas): ?>
            Swal.fire({
                icon: 'info',
                title: 'Descarga no disponible',
                text: 'Las descargas se habilitarán después del <?php echo date('d/m/Y', strtotime($fechaLimite)); ?>',
                confirmButtonText: 'Entendido'
            });
            return;
            <?php endif; ?>

            // Usar las constantes auto-detectadas
            const schoolYearId = currentSchoolYearId;
            const quarterId = currentSchoolQuarterId;
            
            if (schoolYearId && quarterId && selectedStudentId) {
                // Construir la URL para generar el PDF
                const pdfUrl = `generate_boleta_pdf.php?idStudent=${selectedStudentId}&idSchoolYear=${schoolYearId}&idSchoolQuarter=${quarterId}`;
                
                // Abrir el PDF en una nueva ventana
                window.open(pdfUrl, '_blank');
            } else {
                alert('Error: No se pudo obtener la información del período actual.');
            }
        });
    </script>
    
    <!-- Preloader y configuración del modal -->
    <script>
        // Hide preloader when page is fully loaded
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.classList.add('loaded');
                setTimeout(() => {
                    preloader.remove();
                }, 500);
            }
        });
        
        // Configurar el modal de boletas
        const modalCamposFormativos = document.getElementById('modalCamposFormativos');
        
        // Guardar el ID y nombre del estudiante cuando se abra el modal
        modalCamposFormativos.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            // Guardar datos del estudiante
            selectedStudentId = button.getAttribute('data-id');
            
            const nombres = button.getAttribute('data-nombres') || '';
            const paterno = button.getAttribute('data-paterno') || '';
            const materno = button.getAttribute('data-materno') || '';
            studentName = `${nombres} ${paterno} ${materno}`.trim();
            
            // Actualizar el título del modal con el nombre del estudiante
            const modalTitle = modalCamposFormativos.querySelector('.modal-title');
            modalTitle.textContent = `Boleta de ${studentName}`;
            
            // Añadir información del estudiante al modal
            const gradeInfo = button.getAttribute('data-grade') || '';
            const groupInfo = button.getAttribute('data-grupo') || '';
            
            // Insertar información del estudiante en el modal
            const modalBody = modalCamposFormativos.querySelector('.modal-body');
            
            // Verificar si ya existe la sección de información del estudiante
            let studentInfoDiv = modalBody.querySelector('#studentInfo');
            if (!studentInfoDiv) {
                // Crear el div de información del estudiante
                studentInfoDiv = document.createElement('div');
                studentInfoDiv.id = 'studentInfo';
                studentInfoDiv.className = 'modal-info-bar';
                
                // Insertar al principio del modal-body
                modalBody.insertBefore(studentInfoDiv, modalBody.firstChild);
            }
            
            // Actualizar la información del estudiante
            studentInfoDiv.innerHTML = `
                <div>
                    <span class="fw-bold">Alumno:</span> ${studentName}
                </div>
                <div class="text-end">
                    <span class="fw-bold">Grado:</span> ${gradeInfo}° <span class="fw-bold">Grupo:</span> ${groupInfo}
                </div>
            `;
            
            // Cargar automáticamente los campos formativos con el año y trimestre auto-detectados
            if (currentSchoolYearId && currentSchoolQuarterId) {
                document.getElementById('divCamposFormativos').classList.remove('d-none');
                const loadingIndicator = document.getElementById('loadingGrades');
                loadingIndicator.classList.remove('d-none');
                const gradesList = document.getElementById('gradesList');
                gradesList.innerHTML = '';
                loadStudentGrades(selectedStudentId, currentSchoolYearId, currentSchoolQuarterId);
            } else {
                document.getElementById('divCamposFormativos').classList.add('d-none');
            }
        });
        
        // Reiniciar el modal cuando se cierre
        modalCamposFormativos.addEventListener('hidden.bs.modal', function() {
            debug("Modal de boleta cerrado - reseteo");
            
            document.getElementById('divCamposFormativos').classList.add('d-none');
            document.getElementById('gradesList').innerHTML = '';
            
            // Limpiar variables
            selectedStudentId = '';
            studentName = '';
        });
    </script>
    
    <!-- Script para manejar el modal de información del estudiante -->
    <script>
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('button[data-bs-target="#showModal"]');
            if (btn) {
                // Llenar campos del modal con los data-attributes
                document.getElementById('modal-nombres').textContent = btn.getAttribute('data-nombres') || '';
                document.getElementById('modal-apellidos').textContent = ((btn.getAttribute('data-paterno') || '') + ' ' + (btn.getAttribute('data-materno') || '')).trim();
                document.getElementById('modal-curp').textContent = btn.getAttribute('data-curp') || '';
                document.getElementById('modal-grado').textContent = btn.getAttribute('data-grade') || '';
                document.getElementById('modal-grupo').textContent = btn.getAttribute('data-grupo') || '';
                document.getElementById('modal-tutornombres').textContent = btn.getAttribute('data-tutornombres') || '';
                document.getElementById('modal-tutorapellidos').textContent = ((btn.getAttribute('data-tutorpaterno') || '') + ' ' + (btn.getAttribute('data-tutormaterno') || '')).trim();
                document.getElementById('modal-tutorine').textContent = btn.getAttribute('data-tutorine') || '';
                document.getElementById('modal-tutoremail').textContent = btn.getAttribute('data-tutoremail') || '';
                document.getElementById('modal-tutortelefono').textContent = btn.getAttribute('data-tutortelefono') || '';
                document.getElementById('modal-tutordireccion').textContent = btn.getAttribute('data-tutordireccion') || '';
            }
        });
    </script>

    <!-- Script para manejo dinámico de filtros -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const grupoSelect = document.getElementById('grupo');
            const alumnosBody = document.getElementById('alumnos-tbody');

            // Cargar grupos automáticamente para el año actual
            cargarGrupos(currentSchoolYearId);

            // Función para cargar grupos
            function cargarGrupos(schoolYearId) {
                if (!schoolYearId) {
                    return;
                }

                fetch(`ajax_students_by_group.php?schoolYear=${schoolYearId}&teacher=<?php echo $idTeacher; ?>`)
                    .then(response => {
                        return response.json();
                    })
                    .then(data => {
                        grupoSelect.innerHTML = '<option value="" selected>Seleccionar grupo</option>';
                        if (data.success && data.groups && data.groups.length > 0) {
                            data.groups.forEach(grupo => {
                                const option = document.createElement('option');
                                option.value = grupo.idGroup;
                                option.textContent = `${grupo.grade}° ${grupo.group_}`;
                                grupoSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading groups:', error);
                        alert('Error al cargar los grupos. Por favor, intente de nuevo.');
                    });
            }

            // Función para cargar alumnos
            function cargarAlumnos(groupId, schoolYearId) {
                if (!groupId || !schoolYearId) {
                    alumnosBody.innerHTML = '<tr><td colspan="10" class="text-center">Seleccione un grupo para ver los alumnos.</td></tr>';
                    return;
                }

                fetch(`get_students.php?grupo=${groupId}&schoolYear=${schoolYearId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data || !data.students || data.students.length === 0) {
                            alumnosBody.innerHTML = '<tr><td colspan="10" class="text-center">No hay alumnos en este grupo.</td></tr>';
                            return;
                        }

                        alumnosBody.innerHTML = '';
                        data.students.forEach((student, index) => {
                            const row = document.createElement('tr');

                            row.innerHTML = `
                                <td>${index + 1}</td>
                                <td>${student.lastnamePa}</td>
                                <td>${student.lastnameMa}</td>
                                <td>${student.names}</td>
                                <td>${student.grade}°</td>
                                <td>${student.group_}</td>
                                <td>${getStatusBadge(student.nomenclature, student.description)}</td>
                                <td class="text-center">
                                    <button type="button" class="tch-btn--icon btn-boleta"
                                        data-bs-toggle="modal" data-bs-target="#modalCamposFormativos"
                                        data-id="${student.idStudent}"
                                        data-nombres="${student.names}"
                                        data-paterno="${student.lastnamePa}"
                                        data-materno="${student.lastnameMa}"
                                        data-grade="${student.grade}"
                                        data-grupo="${student.group_}"
                                        data-curp="${student.curp || ''}">
                                        <i class="bi bi-file-earmark-text-fill"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="tch-btn--icon btn-info"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#showModal"
                                        data-id="${student.schoolNum}"
                                        data-nombres="${student.names}"
                                        data-paterno="${student.lastnamePa}"
                                        data-materno="${student.lastnameMa}"
                                        data-curp="${student.curp || ''}"
                                        data-grade="${student.grade}"
                                        data-grupo="${student.group_}"
                                        data-status="${student.idStudentStatus}"
                                        data-tutornombres="${student.tutorName || ''}"
                                        data-tutorpaterno="${student.tutorLastnamePa || ''}"
                                        data-tutormaterno="${student.tutorLastnameMa || ''}"
                                        data-tutortelefono="${student.tutorPhone || ''}"
                                        data-tutordireccion="${student.tutorAddress || ''}"
                                        data-tutoremail="${student.tutorEmail || ''}"
                                        data-tutorine="${student.tutorIne || ''}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="tch-btn--icon btn-reporte"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#reportModal"
                                        data-id="${student.idStudent}"
                                        data-nombres="${student.names}"
                                        data-paterno="${student.lastnamePa}"
                                        data-materno="${student.lastnameMa}">
                                        <i class="bi bi-file-earmark-person-fill"></i>
                                    </button>
                                </td>
                            `;
                            alumnosBody.appendChild(row);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading students:', error);
                        alumnosBody.innerHTML = '<tr><td colspan="10" class="text-center">Error al cargar los alumnos.</td></tr>';
                    });
            }

            // Función para generar el badge de estado
            function getStatusBadge(nomenclature, description) {
                if (nomenclature && description) {
                    let badge = 'secondary';
                    switch (nomenclature.trim().toUpperCase()) {
                        case 'AC': badge = 'success'; break;   // Activo
                        case 'BA': badge = 'danger'; break;    // Baja
                        case 'RE': badge = 'warning'; break;   // Regular
                        case 'EG': badge = 'primary'; break;   // Egresado
                        case 'IN': badge = 'secondary'; break; // Inactivo
                        case 'TR': badge = 'info'; break;      // Trasladado
                        case 'RC': badge = 'secondary'; break;  // Recursando
                        case 'EX': badge = 'neutral'; break;    // Expulsado
                    }
                    return `<span class="tch-badge tch-badge--${badge}">${description}</span>`;
                }
                return '';
            }

            // Event listener para el botón de descarga
            document.getElementById('descargarGrupoBtn').addEventListener('click', function() {
                // Verificar si las descargas están habilitadas
                <?php if(!$descargasHabilitadas): ?>
                Swal.fire({
                    icon: 'info',
                    title: 'Descarga no disponible',
                    text: 'Las descargas se habilitarán después del <?php echo date('d/m/Y', strtotime($fechaLimite)); ?>',
                    confirmButtonText: 'Entendido'
                });
                return;
                <?php endif; ?>

                const schoolYearValue = currentSchoolYearId;
                const grupoValue = grupoSelect.value;
                
                if (!schoolYearValue || !grupoValue) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selección incompleta',
                        text: 'Por favor selecciona el año escolar y el grupo antes de descargar.'
                    });
                    return;
                }

                // Confirmar descarga
                Swal.fire({
                    title: '¿Confirmar descarga?',
                    text: 'Se generarán los PDFs de todos los alumnos del grupo seleccionado',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, descargar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        descargarPDFsGrupo(schoolYearValue, grupoValue);
                    }
                });
            });

            // Función para descargar PDFs del grupo
            function descargarPDFsGrupo(schoolYear, grupo) {
                // Mostrar loading
                Swal.fire({
                    title: 'Generando PDFs...',
                    text: 'Por favor espera mientras se generan los archivos',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Realizar la petición
                fetch('generate_group_pdfs.php', {
                    method: 'POST',
                headers: {

                        'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `schoolYear=${encodeURIComponent(schoolYear)}&grupo=${encodeURIComponent(grupo)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Crear enlace de descarga
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `Boletas_Grupo_${schoolYear}_${grupo}.zip`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    // Cerrar loading y mostrar éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Descarga completada!',
                        text: 'Los PDFs del grupo se han descargado exitosamente.'
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al generar los PDFs. Por favor intenta nuevamente.'
                    });
                });
            }
        });

        // BITÁCORAS DE INCIDENCIAS
        let currentStudentId = null;
        let currentStudentName = '';

        // Event listener para los botones de reporte
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-reporte')) {
                const button = e.target.closest('.btn-reporte');
                currentStudentId = button.getAttribute('data-id');
                const nombres = button.getAttribute('data-nombres');
                const paterno = button.getAttribute('data-paterno');
                const materno = button.getAttribute('data-materno');
                currentStudentName = `${nombres} ${paterno} ${materno}`;
                
                // Actualizar nombre en el modal
                document.getElementById('report-student-name').textContent = currentStudentName;
                
                // Mostrar loading y ocultar contenido
                document.getElementById('reportLoadingIndicator').classList.remove('d-none');
                document.getElementById('reportExistsContent').classList.add('d-none');
                document.getElementById('reportNotExistsContent').classList.add('d-none');
                
                // Verificar si el estudiante tiene reporte
                checkStudentReport(currentStudentId);
            }
        });

        // Función para verificar si el estudiante tiene reporte
        function checkStudentReport(studentId) {
            fetch(`get_student_report.php?studentId=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    // Ocultar loading
                    document.getElementById('reportLoadingIndicator').classList.add('d-none');
                    
                    if (data.success && data.hasReport) {
                        // Mostrar contenido de reportes existentes
                        document.getElementById('reportCount').textContent = data.count;
                        
                        const reportsList = document.getElementById('reportsList');
                        reportsList.innerHTML = '';
                        
                        data.reports.forEach((report, index) => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${report.fecha}</td>
                                <td>${report.teacherFullName || 'N/A'}</td>
                                <td class="tch-report-actions d-flex justify-content-center gap-2">
                                    <button class="tch-btn--icon btn-pdf" onclick="viewReportPDF(${report.idConductReport})" title="Imprimir reporte">
                                        <i class="bi bi-file-pdf"></i> Imprimir
                                    </button>
                                    <button class="tch-btn--icon btn-view" onclick="viewReportDetails(${report.idConductReport})" title="Ver detalles">
                                        <i class="bi bi-eye"></i> Detalles
                                    </button>
                                </td>
                            `;
                            reportsList.appendChild(row);
                        });
                        
                        // Guardar los reportes en memoria para acceder después
                        window.currentReports = data.reports;
                        
                        document.getElementById('reportExistsContent').classList.remove('d-none');
                    } else {
                        // Mostrar opción para crear nuevo reporte
                        document.getElementById('reportNotExistsContent').classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error checking report:', error);
                    document.getElementById('reportLoadingIndicator').classList.add('d-none');
                    document.getElementById('reportNotExistsContent').classList.remove('d-none');
                });
        }
        
        // Función para ver el PDF del reporte
        window.viewReportPDF = function(idConductReport) {
            window.open(`generate_report_pdf.php?id=${idConductReport}`, '_blank');
        };
        
        // Función para ver los detalles del reporte
        window.viewReportDetails = function(idConductReport) {
            const report = window.currentReports.find(r => r.idConductReport == idConductReport);
            if (report) {
                // Llenar los datos en el modal
                document.getElementById('detail-report-date').textContent = report.fecha;
                document.getElementById('detail-report-teacher').textContent = report.teacherFullName || 'N/A';
                document.getElementById('detail-report-desc').textContent = report.descripcion;
                
                const obsContainer = document.getElementById('detail-report-obs-container');
                if (report.observaciones) {
                    obsContainer.style.display = 'block';
                    document.getElementById('detail-report-obs').textContent = report.observaciones;
                } else {
                    obsContainer.style.display = 'none';
                }
                
                document.getElementById('detail-report-created').textContent = `Creado: ${report.createdAt}`;
                
                // Actualizar el botón de imprimir
                const btnPrint = document.getElementById('btnImprimirReporte');
                btnPrint.setAttribute('onclick', `viewReportPDF(${idConductReport})`);
                
                // Ocultar el modal de lista de reportes si se desea
                const reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
                if (reportModal) {
                    reportModal.hide();
                }
                
                // Mostrar el modal de detalles
                const detailsModal = new bootstrap.Modal(document.getElementById('reportDetailsModal'));
                detailsModal.show();
            }
        };

        // Función para abrir el modal de creación de reporte
        function openCreateReportModal() {
            // Cerrar el modal de verificación
            const reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
            if (reportModal) {
                reportModal.hide();
            }
            
            // Abrir el modal de creación
            const createReportModal = new bootstrap.Modal(document.getElementById('createReportModal'));
            createReportModal.show();
            
            // Actualizar información del estudiante
            document.getElementById('create-report-student-name').textContent = currentStudentName;
            document.getElementById('reportStudentId').value = currentStudentId;
        }

        // Inicializar Flatpickr para el campo de fecha en español
        let flatpickrInstance = null;
        
        function initializeDatePicker() {
            if (flatpickrInstance) {
                flatpickrInstance.destroy();
            }
            
            flatpickrInstance = flatpickr("#reportFecha", {
                locale: "es",
                dateFormat: "d/m/Y",
                defaultDate: new Date(),
                allowInput: false,
                disableMobile: true,
                onChange: function(selectedDates, dateStr, instance) {
                    // Convertir a formato yyyy-mm-dd para enviar al servidor
                    if (selectedDates.length > 0) {
                        const date = selectedDates[0];
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        // Guardar en formato yyyy-mm-dd en un campo oculto o en el mismo campo
                        instance.input.dataset.isoDate = `${year}-${month}-${day}`;
                    }
                }
            });
        }

        // Event listener para el botón de crear primer reporte
        document.getElementById('btnCreateReport').addEventListener('click', function() {
            openCreateReportModal();
            initializeDatePicker();
        });
        
        // Event listener para el botón de agregar otro reporte
        document.getElementById('btnAddAnotherReport').addEventListener('click', function() {
            openCreateReportModal();
            initializeDatePicker();
        });

        // Event listener para el formulario de reporte
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Convertir la fecha del formato dd/mm/yyyy a yyyy-mm-dd para el servidor
            const fechaInput = document.getElementById('reportFecha');
            if (fechaInput.dataset.isoDate) {
                formData.set('fecha', fechaInput.dataset.isoDate);
            }
            
            // Mostrar loading
            Swal.fire({
                title: 'Guardando reporte...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar datos al servidor
            fetch('save_student_report.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reporte guardado!',
                        text: 'El reporte se ha guardado exitosamente.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Cerrar el modal de creación
                        const createModal = bootstrap.Modal.getInstance(document.getElementById('createReportModal'));
                        if (createModal) {
                            createModal.hide();
                        }
                        
                        // Limpiar el formulario
                        document.getElementById('reportForm').reset();
                        
                        // Destruir y limpiar flatpickr
                        if (flatpickrInstance) {
                            flatpickrInstance.destroy();
                            flatpickrInstance = null;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo guardar el reporte. Por favor intenta nuevamente.'
                    });
                }
            })
            .catch(error => {
                console.error('Error saving report:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al guardar el reporte. Por favor intenta nuevamente.'
                });
            });
        });
        
        // Limpiar flatpickr cuando se cierre el modal de crear reporte
        document.getElementById('createReportModal').addEventListener('hidden.bs.modal', function() {
            if (flatpickrInstance) {
                flatpickrInstance.destroy();
                flatpickrInstance = null;
            }
            document.getElementById('reportForm').reset();
        });
    </script>
</body>
</html>