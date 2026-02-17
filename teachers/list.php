<?php
// Última actualización: 2026-02-16
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Obtener el ciclo escolar del año actual basado en la fecha del servidor
$currentYear = date('Y');
$currentSchoolYear = null;
$sqlCurrentYear = "SELECT idSchoolYear, startDate, endDate FROM schoolYear 
                   WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? 
                   LIMIT 1";
$stmtCurrentYear = $conexion->prepare($sqlCurrentYear);
if ($stmtCurrentYear) {
    $stmtCurrentYear->bind_param('ii', $currentYear, $currentYear);
    $stmtCurrentYear->execute();
    $resultCurrentYear = $stmtCurrentYear->get_result();
    if ($rowCurrentYear = $resultCurrentYear->fetch_assoc()) {
        $currentSchoolYear = $rowCurrentYear;
    }
    $stmtCurrentYear->close();
} else {
    error_log("Error al preparar consulta de ciclo escolar: " . $conexion->error);
}

// Obtener los trimestres del año escolar actual
$quarters = [];
if ($currentSchoolYear) {
    $sqlQuarters = "SELECT idSchoolQuarter, name, startDate, endDate 
                    FROM schoolQuarter 
                    WHERE idSchoolYear = ? 
                    ORDER BY idSchoolQuarter ASC";
    $stmtQuarters = $conexion->prepare($sqlQuarters);
    if ($stmtQuarters) {
        $stmtQuarters->bind_param('i', $currentSchoolYear['idSchoolYear']);
        $stmtQuarters->execute();
        $resultQuarters = $stmtQuarters->get_result();
        while ($row = $resultQuarters->fetch_assoc()) {
            $quarters[] = $row;
        }
        $stmtQuarters->close();
    } else {
        error_log("Error al preparar consulta de trimestres: " . $conexion->error);
    }
}

$resTeacher = $stmtTeacher->get_result();
$rowTeacher = $resTeacher->fetch_assoc();
if (!$rowTeacher) {
    error_log("Error: No se encontró el docente para el usuario ID: " . $_SESSION['user_id']);
    die("No se pudo cargar la información del docente. Por favor, contacte al administrador.");
}
$idTeacher = $rowTeacher['idTeacher'];
$stmtTeacher->close();

// Obtener solo los grupos asignados al docente autenticado para el año escolar actual
$groups = [];

if ($currentSchoolYear) {
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
        $stmtGroups->bind_param("ii", $idTeacher, $currentSchoolYear['idSchoolYear']);
        $stmtGroups->execute();
        $resGroups = $stmtGroups->get_result();
        while ($row = $resGroups->fetch_assoc()) {
            $groups[] = $row;
        }
        $stmtGroups->close();
    } else {
        error_log("Error al preparar consulta de grupos: " . $conexion->error);
    }
}

// Determinar el grupo seleccionado
$selectedGroup = isset($_GET['grupo']) ? intval($_GET['grupo']) : "";

// Obtener alumnos del grupo seleccionado
$students = [];
if ($selectedGroup && $currentSchoolYear) {
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
    if ($stmt) {
        $stmt->bind_param("ii", $selectedGroup, $currentSchoolYear['idSchoolYear']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Debug temporal: log del primer estudiante
            if (empty($students)) {
                error_log("DEBUG: Primer estudiante obtenido: " . json_encode($row));
                error_log("DEBUG: idStudent value: " . var_export($row['idStudent'], true));
            }
            $students[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Error al preparar consulta de estudiantes: " . $conexion->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Alumnos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/stylesBoot.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/teacher/list.css">
    <link rel="stylesheet" href="../css/admin/student.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">

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
<body class="row d-flex" style="min-height: 100vh; width: 100%; margin: 0; padding: 0; overflow-x: hidden;">
    <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>
    <?php
        include "../layouts/asideTeacher.php"; 
    ?>
    <main class="flex-grow-1 col-9 p-0" style="overflow-y: auto; max-height: 100vh;">
        <?php
            include "../layouts/headerTeacher.php"; 
        ?> 
        
        <!-- Header de la página -->
        <div class="container-fluid px-4 pt-5" style="height: auto;">
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-3">
                        <h1 class="page-title">
                            <i class="bi bi-people me-3"></i>
                            Lista de Alumnos
                        </h1>
                        <p class="page-subtitle text-muted">
                            Consulta y gestiona la información de tus estudiantes
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="container-fluid px-4">
            <!-- Panel de filtros -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="filter-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-funnel me-2 text-primary"></i>
                                    Filtros de Búsqueda
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php if ($currentSchoolYear): ?>
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>Año Escolar Actual:</strong> <?php echo $currentYear; ?>
                                            <span class="text-muted ms-2">(<?php echo date('d/m/Y', strtotime($currentSchoolYear['startDate'])); ?> - <?php echo date('d/m/Y', strtotime($currentSchoolYear['endDate'])); ?>)</span>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>No hay ciclo escolar configurado para el año <?php echo $currentYear; ?></strong>
                                            <br><small>Contacte al administrador para configurar el ciclo escolar actual.</small>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-md-6" id="contenedorGrupo" <?php if (!$currentSchoolYear): ?>style="display:none;"<?php endif; ?>>
                                        <label id="labelGrupo" for="grupo" class="form-label fw-semibold">
                                            <i class="bi bi-collection me-1"></i>
                                            Grupo:
                                        </label>
                                        <select class="form-select border-secondary" id="grupo">
                                            <option value="" selected>Seleccionar grupo</option>
                                            <?php foreach ($groups as $g): ?>
                                                <option value="<?php echo $g['idGroup']; ?>" <?php if ($selectedGroup == $g['idGroup']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($g['grade'] . '° ' . $g['group_']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6" style="display: flex; align-items: end;">
                                        <button type="button" id="descargarGrupoBtn" 
                                                class="btn <?php echo $descargasHabilitadas ? 'btn-success' : 'btn-secondary'; ?> w-100" 
                                                <?php if(!$descargasHabilitadas) echo 'disabled title="Las descargas se habilitarán después del ' . date('d/m/Y', strtotime($fechaLimite)) . '"'; ?>>
                                            <i class="fas fa-download me-2"></i> 
                                            <?php echo $descargasHabilitadas ? 'Descargar PDFs del Grupo' : 'Descarga después del ' . date('d/m/Y', strtotime($fechaLimite)); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de estudiantes -->
            <div class="row d-none" id="contenedorTabla">
                <div class="col-12">
                    <div class="table-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-table me-2 text-success"></i>
                                    Estudiantes Registrados
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="tabla">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="fw-semibold">No.</th>
                                                <th class="fw-semibold">Apellido Paterno</th>
                                                <th class="fw-semibold">Apellido Materno</th>
                                                <th class="fw-semibold">Nombres</th>
                                                <th class="fw-semibold">CURP</th>
                                                <th class="fw-semibold">Grado</th>
                                                <th class="fw-semibold">Grupo</th>
                                                <th class="fw-semibold">Estado</th>
                                                <th class="fw-semibold">Boleta</th>
                                                <th class="fw-semibold">Ver Información</th>
                                                <th class="fw-semibold">Bitácora Incidencias</th>
                        </tr>
                    </thead>
                    <!-- DEBUG: Archivo actualizado 2026-02-13 19:00 - Columna Reporte agregada -->
                    <tbody id="alumnos-tbody">
                        <?php if ($selectedGroup && count($students) > 0): ?>
                            <?php foreach ($students as $i => $student): ?>
                                <!-- DEBUG: <?php echo "idStudent: " . ($student['idStudent'] ?? 'NULL') . ", schoolNum: " . ($student['schoolNum'] ?? 'NULL') . ", nombre: " . ($student['names'] ?? 'NULL'); ?> -->
                                <tr>    
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['lastnamePa']); ?></td>
                                    <td><?php echo htmlspecialchars($student['lastnameMa']); ?></td>
                                    <td><?php echo htmlspecialchars($student['names']); ?></td>
                                    <td><?php echo htmlspecialchars($student['curp'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($student['grade']); ?>°</td>
                                    <td><?php echo htmlspecialchars($student['group_']); ?></td>
                                    <td>
                                        <?php
                                            if ($student['nomenclature'] == 'AC') {
                                                echo '<span class="badge bg-success">' . $student['description'] . '</span>';
                                            } elseif ($student['nomenclature'] == 'BA') {
                                                echo '<span class="badge bg-danger">' . $student['description'] . '</span>';
                                            } elseif ($student['nomenclature'] == 'RE') {
                                                echo '<span class="badge bg-warning">' . $student['description'] . '</span>';
                                            } elseif ($student['nomenclature'] == 'EG') {
                                                echo '<span class="badge bg-primary">' . $student['description'] . '</span>';
                                            } elseif ($student['nomenclature'] == 'IN') {
                                                echo '<span class="badge bg-secondary">' . $student['description'] . '</span>';
                                            } elseif ($student['nomenclature'] == 'TR') {
                                                echo '<span class="badge bg-info">' . $student['description'] . '</span>';
                                            } elseif ($student['nomenclature'] == 'RC') {
                                                echo '<span class="badge bg-dark">' . $student['description'] . '</span>';
                                            } elseif ($student['nomenclature'] == 'EX') {
                                                echo '<span class="badge bg-light">' . $student['description'] . '</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">-</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="botonVer "
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
                                        <button type="button" id="botonVer"
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
                                        <!-- DEBUG: Celda de Bitácora de Incidencias para estudiante ID: <?php echo $student['idStudent']; ?> -->
                                        <button type="button" class="botonReporte" data-bs-toggle="modal" data-bs-target="#reportModal" data-id="<?php echo $student['idStudent']; ?>" data-nombres="<?php echo htmlspecialchars($student['names']); ?>" data-paterno="<?php echo htmlspecialchars($student['lastnamePa']); ?>" data-materno="<?php echo htmlspecialchars($student['lastnameMa']); ?>">
                                            <i class="bi bi-file-earmark-person-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php elseif($selectedGroup): ?>
                            <tr><td colspan="11" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="bi bi-people text-muted display-4"></i>
                                    <p class="text-muted mt-2 mb-0">No hay alumnos en este grupo.</p>
                                </div>
                            </td></tr>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="bi bi-search text-muted display-4"></i>
                                    <p class="text-muted mt-2 mb-0">Seleccione un grupo para ver los alumnos.</p>
                                </div>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                                    </table>
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
                margin-top: 4rem;
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

            /* Tarjetas */
            .filter-card, .table-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .filter-card:hover, .table-card:hover {
                transform: translateY(-2px);
            }
            
            .filter-card .card, .table-card .card {
                border-radius: 15px;
                background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            }
            
            .filter-card .card-header, .table-card .card-header {
                border-radius: 15px 15px 0 0;
                border-bottom: 1px solid #e9ecef;
            }

            /* Tabla */
            .table-responsive {
                border-radius: 0 0 15px 15px;
            }
            
            .table th {
                background-color: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                font-weight: 600;
                color: #495057;
            }
            
            .table tbody tr {
                transition: background-color 0.2s ease;
            }
            
            .table tbody tr:hover {
                background-color: rgba(13, 110, 253, 0.05);
            }

            /* Botones en tabla */
            .table .btn {
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            
            .table .btn-info {
                background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
                border: none;
            }
            
            .table .btn-primary {
                background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                border: none;
            }

            /* Estado vacío */
            .empty-state {
                padding: 2rem;
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
                
                .filter-card .card-body .row {
                    flex-direction: column;
                }
                
                .filter-card .card-body .col-md-4 {
                    margin-bottom: 1rem;
                }
            }
        </style>
    </main>
    <!-- MODAL SHOW-->
    <div class="modal fade modal-lg" id="showModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 id="tituloModal"class="modal-title fs-5" id="exampleModalLabel">Información Personal</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" enctype="multipart/form-data" method="post" class="needs-validation" novalidate id="form">
        <div class="modal-body">
            <div class="row">   
                <div class="col-6"style="padding-right: 0;">
                    <label class="labelAgregar"for="txtName">Nombres:</label>
                    <span id="modal-nombres"></span>
                </div>
                <div class="col-6">
                    <label class="labelAgregar"for="txtLastname">Apellidos:</label>
                    <span id="modal-apellidos"></span>
                </div>
            </div>
            <div class="row pt-3">
                <div class="col-6">
                    <label class="labelAgregar"for="txtCurp">CURP:</label>
                    <span id="modal-curp"></span>
                </div>
                <div class="col-6">
                    <label class="labelAgregar"for="txtGrade">Grado:</label> 
                    <span id="modal-grado"></span>
                </div>
            </div>
            <div class="row pt-3">
                <div class="col-6">
                    <label class="labelAgregar"for="txtGroup">Grupo:</label>
                    <span id="modal-grupo"></span>
                </div>

            </div>

        </div>

        <div class="modal-header">
                    <h1 id="tituloModal" class="modal-title fs-5" id="exampleModalLabel">Información del Tutor</h1>
        </div>
        <div class="modal-body">
            <div class="row">   
                <div class="col-6"style="padding-right: 0;">
                    <label class="labelAgregar"for="txtName">Nombres:</label>
                    <span id="modal-tutornombres"></span>
                </div>
                <div class="col-6">
                    <label class="labelAgregar"for="txtLastname">Apellidos:</label>
                    <span id="modal-tutorapellidos"></span>
                </div>
            </div>
            <div class="row pt-3">
                <div class="col-6">
                    <label class="labelAgregar"for="txtIne">INE:</label>
                    <span id="modal-tutorine"></span>
                </div>
                <div class="col-6">
                    <label class="labelAgregar"for="txtEmail">Correo:</label>
                    <span id="modal-tutoremail"></span>
                </div>
            </div>
            <div class="row pt-3">
                <div class="col-6">
                    <label class="labelAgregar"for="txtPhone">Número de Teléfono:</label>
                    <span id="modal-tutortelefono"></span>
                </div>
                <div class="col-6">
                    <label class="labelAgregar"for="txtAddress">Dirección:</label>
                    <span id="modal-tutordireccion"></span>
                </div>
            </div>

        </div>
    </form>

            </div>
        </div>

    </div>
    <!-- MODAL BOLETA -->
    <div class="modal fade" id="modalCamposFormativos" tabindex="-1" aria-labelledby="modalCamposFormativosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white border-0" style="background-color: #192E4E;">
                    <i class="bi bi-file-earmark-text-fill me-2"></i>
                    <h5 class="modal-title" id="modalCamposFormativosLabel">Boleta del Estudiante</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <?php if ($currentSchoolYear): ?>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Año Escolar:</strong> <?php echo $currentYear; ?>
                        <span class="text-muted ms-2">(<?php echo date('d/m/Y', strtotime($currentSchoolYear['startDate'])); ?> - <?php echo date('d/m/Y', strtotime($currentSchoolYear['endDate'])); ?>)</span>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="trimestreFormativo" class="form-label fw-bold">Trimestre:</label>
                                <select class="form-select" id="trimestreFormativo">
                                    <option value="">Seleccionar trimestre</option>
                                    <?php foreach ($quarters as $quarter): ?>
                                        <option value="<?php echo $quarter['idSchoolQuarter']; ?>">
                                            <?php echo htmlspecialchars($quarter['name']); ?>
                                            <?php if ($quarter['startDate'] && $quarter['endDate']): ?>
                                                (<?php echo date('d/m/Y', strtotime($quarter['startDate'])); ?> - <?php echo date('d/m/Y', strtotime($quarter['endDate'])); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        No hay ciclo escolar configurado para el año actual.
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4 d-none" id="divCamposFormativos">
>>>>>>> Stashed changes
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Campos Formativos</h6>
                        
                        <div id="loadingGrades" class="text-center my-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando calificaciones...</p>
                        </div>
                        
                        <ul class="list-group shadow-sm" id="gradesList">
                        </ul>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button id="btnVerDetalles" type="button" 
                            class="btn <?php echo $descargasHabilitadas ? 'btn-primary' : 'btn-secondary'; ?>"
                            <?php if(!$descargasHabilitadas) echo 'title="Disponible después del ' . date('d/m/Y', strtotime($fechaLimite)) . '"'; ?>>
                        <?php echo $descargasHabilitadas ? 'Imprimir boleta' : 'Boleta disponible después del ' . date('d/m/Y', strtotime($fechaLimite)); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL VERIFICAR BITÁCORA -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white border-0" style="background-color: #192E4E;">
                    <h5 class="modal-title" id="reportModalLabel">
                        <i class="bi bi-file-earmark-person-fill me-2"></i>Bitácora de Incidencias del Estudiante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="student-info mb-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Información del Estudiante</h6>
                        <p><strong>Nombre:</strong> <span id="report-student-name"></span></p>
                    </div>
                    
                    <div id="reportLoadingIndicator" class="text-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Verificando bitácora...</p>
                    </div>
                    
                    <div id="reportExistsContent" class="d-none">
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Este estudiante tiene <strong><span id="reportCount">0</span></strong> bitácoras registrado(s).
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Docente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="reportsList">    
                                    <!-- Los reportes se cargarán aquí dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="button" class="btn" style="background-color: #192E4E; color: white;" id="btnAddAnotherReport">
                                <i class="bi bi-plus-circle me-2"></i>Agregar Nueva Bitácora
                            </button>
                        </div>
                    </div>
                    
                    <div id="reportNotExistsContent" class="d-none">
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Este estudiante no tiene bitácoras registradas.
                        </div>
                        <p class="text-center">¿Desea crear una nueva bitácora para este estudiante?</p>
                        <div class="text-center mt-3">
                            <button type="button" class="btn" style="background-color: #192E4E; color: white;" id="btnCreateReport">
                                <i class="bi bi-file-earmark-plus me-2"></i>Crear Nueva Bitácora
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CREAR NUEVO REPORTE -->
    <div class="modal fade" id="createReportModal" tabindex="-1" aria-labelledby="createReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white border-0" style="background-color: #192E4E;">
                    <h5 class="modal-title" id="createReportModalLabel">
                        <i class="bi bi-file-earmark-plus me-2"></i>Crear Nueva Bitácora
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="reportForm">
                    <div class="modal-body">
                        <input type="hidden" id="reportStudentId" name="studentId">
                        
                        <div class="mb-4">
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Información del Estudiante</h6>
                            <p><strong>Nombre:</strong> <span id="create-report-student-name"></span></p>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reportFecha" class="form-label fw-bold">Fecha: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reportFecha" name="fecha" placeholder="Seleccione una fecha" readonly required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reportDescripcion" class="form-label fw-bold">Descripción: <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reportDescripcion" name="descripcion" rows="4" 
                                      placeholder="Describa detalladamente la situación..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="reportObservaciones" class="form-label fw-bold">Observaciones:</label>
                            <textarea class="form-control" id="reportObservaciones" name="observaciones" rows="3" 
                                      placeholder="Agregue observaciones adicionales (opcional)..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Guardar Bitácora
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL SHOW-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
        <!-- Scripts para manejar la carga dinámica de boletas -->
    <script>
        // Variables para los elementos del DOM
        const quarterSelect = document.getElementById('trimestreFormativo');
        const divCamposFormativos = document.getElementById('divCamposFormativos');
        let selectedStudentId = '';
        let studentName = '';
        
        // Año escolar actual del servidor
        const currentSchoolYearId = <?php echo $currentSchoolYear ? $currentSchoolYear['idSchoolYear'] : 'null'; ?>;
        
        // Función para mostrar un mensaje en la consola para depuración
        function debug(msg) {
            // Debug function disabled for production
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
                        gradesList.innerHTML = '<div class="alert alert-info">No hay materias asignadas para este estudiante</div>';
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
                        gradesList.innerHTML = `<div class="alert alert-danger">${data.message || 'Error al cargar calificaciones'}</div>`;
                        return;
                    }
                    
                    if (!data.subjects || data.subjects.length === 0) {
                        gradesList.innerHTML = '<div class="alert alert-info">No hay calificaciones disponibles para este período</div>';
                        return;
                    }
                    
                    // Calcular promedio general considerando TODAS las áreas de aprendizaje
                    const areaAverages = Object.values(data.learningAreas).map(area => area.average);
                    const generalAverage = areaAverages.length > 0 ? ceilToOneDecimal(areaAverages.reduce((sum, avg) => sum + avg, 0) / areaAverages.length) : 0;

                    // Mostrar promedio general
                    const avgItem = document.createElement('div');
                    avgItem.className = 'alert alert-primary fw-bold text-center mb-3';
                    
                    // Determinar el color del promedio
                    let avgBadgeClass = 'bg-secondary';
                    if (generalAverage >= 9) avgBadgeClass = 'bg-success';
                    else if (generalAverage >= 7) avgBadgeClass = 'bg-warning';
                    else if (generalAverage >= 0) avgBadgeClass = 'bg-danger';
                    
                    avgItem.innerHTML = `
                        PROMEDIO GENERAL: <span class="badge ${avgBadgeClass} rounded-pill">${Number(generalAverage || 0).toFixed(1)}</span>
                    `;
                    gradesList.appendChild(avgItem);
                    
                    // Crear tabla de áreas de aprendizaje
                    const table = document.createElement('table');
                    table.className = 'table table-bordered table-hover';
                    
                    // Cabecera de la tabla
                    const thead = document.createElement('thead');
                    thead.className = 'table-primary';
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
                        areaCell.className = 'align-middle fw-bold text-center table-light';
                        
                        // Color para el promedio del área
                        let areaBadgeClass = 'bg-secondary';
                        if (area.average >= 9) areaBadgeClass = 'bg-success';
                        else if (area.average >= 7) areaBadgeClass = 'bg-warning';
                        else if (area.average >= 0) areaBadgeClass = 'bg-danger';
                        
                        areaCell.innerHTML = `
                            ${area.name}<br>
                            <small class="badge ${areaBadgeClass} rounded-pill mt-1">${Number(area.average || 0).toFixed(1)}</small>
                        `;
                        firstRow.appendChild(areaCell);
                        
                        // Celda de la primera materia
                        const subjectCell = document.createElement('td');
                        subjectCell.textContent = firstSubject.name;
                        firstRow.appendChild(subjectCell);
                        
                        // Celda de la primera calificación
                        const gradeCell = document.createElement('td');
                        gradeCell.className = 'text-center';
                        
                        let gradeBadgeClass = 'bg-secondary';
                        if (firstSubject.average >= 9) gradeBadgeClass = 'bg-success';
                        else if (firstSubject.average >= 7) gradeBadgeClass = 'bg-warning';
                        else if (firstSubject.average >= 0) gradeBadgeClass = 'bg-danger';
                        
                        gradeCell.innerHTML = `<span class="badge ${gradeBadgeClass} rounded-pill">${Number(firstSubject.average || 0).toFixed(1)}</span>`;
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
                            
                            let gradeBadgeClass = 'bg-secondary';
                            if (subject.average >= 9) gradeBadgeClass = 'bg-success';
                            else if (subject.average >= 7) gradeBadgeClass = 'bg-warning';
                            else if (subject.average >= 0) gradeBadgeClass = 'bg-danger';
                            
                            gradeCell.innerHTML = `<span class="badge ${gradeBadgeClass} rounded-pill">${Number(subject.average || 0).toFixed(1)}</span>`;
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
                    gradesList.innerHTML = '<div class="alert alert-danger">Error al cargar calificaciones: ' + error.message + '</div>';
                });
        }
        
        // Función para redondear hacia arriba con 1 decimal (igual que saveGrades.php)
        function ceilToOneDecimal(value) {
            return Math.ceil(value * 10) / 10;
        }

        // Función para mostrar las calificaciones en el modal
        function displayGrades(grades) {
            const gradesList = document.getElementById('gradesList');
            gradesList.innerHTML = '';
            
            // Primero, obtener todos los criterios de evaluación para calcular promedio correcto
            fetch(`getEvaluationCriteria.php?subject_id=${currentSubjectId}&quarter=${currentQuarter}`)
                .then(response => response.json())
                .then(allCriteria => {
                    // Calcular promedio considerando TODOS los criterios (vacíos como 0)
                    let totalGrade = 0;
                    let totalCriteria = allCriteria.length;
                    
                    allCriteria.forEach(criteria => {
                        // Buscar si este criterio tiene calificación
                        const gradeItem = grades.find(g => g.criteria_id == criteria.criteria_id);
                        const grade = gradeItem ? parseFloat(gradeItem.grade) : 0; // Si no tiene calificación, contar como 0
                        
                        if (!isNaN(grade)) {
                            totalGrade += grade;
                        }
                    });
                    
                    const avgGrade = totalCriteria > 0 ? ceilToOneDecimal(totalGrade / totalCriteria) : 0;
                    
                    // Agregar el promedio general
                    const avgItem = document.createElement('li');
                    avgItem.className = 'list-group-item d-flex justify-content-between align-items-center bg-light fw-bold';
                    
                    // Determinar el color del promedio
                    let avgBadgeClass = 'bg-secondary';
                    if (avgGrade >= 9) avgBadgeClass = 'bg-success';
                    else if (avgGrade >= 7) avgBadgeClass = 'bg-warning';
                    else if (avgGrade >= 0) avgBadgeClass = 'bg-danger';
                    
                    avgItem.innerHTML = `
                        PROMEDIO GENERAL
                        <span class="badge ${avgBadgeClass} rounded-pill">${avgGrade.toFixed(1)}</span>
                    `;
                    gradesList.appendChild(avgItem);
                    
                    // Separador
                    const separator = document.createElement('li');
                    separator.className = 'list-group-item bg-white border-0 py-1';
                    gradesList.appendChild(separator);
                    
                    // Mostrar TODOS los criterios de evaluación (con y sin calificación)
                    allCriteria.forEach(criteria => {
                        const gradeItem = grades.find(g => g.criteria_id == criteria.criteria_id);
                        const grade = gradeItem ? parseFloat(gradeItem.grade) : 0; // Si no hay calificación, mostrar 0
                        
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        
                        // Determinar el color de la insignia según la calificación
                        let badgeClass = 'bg-secondary';
                        if (grade >= 9) badgeClass = 'bg-success';
                        else if (grade >= 7) badgeClass = 'bg-warning';
                        else if (grade >= 0) badgeClass = 'bg-danger';
                        
                        // Mostrar calificación o "No evaluado" si es 0 por falta de evaluación
                        const displayGrade = gradeItem ? grade.toString() : '0 (Sin evaluar)';
                        
                        li.innerHTML = `
                            ${criteria.criteria_name}
                            <span class="badge ${badgeClass} rounded-pill">${displayGrade}</span>
                        `;
                        
                        gradesList.appendChild(li);
                    });
                })
                .catch(error => {
                    console.error('Error al cargar criterios:', error);
                    gradesList.innerHTML = '<div class="alert alert-danger">Error al cargar criterios de evaluación</div>';
                });
        }

        // Event listener para el trimestre
        quarterSelect.addEventListener('change', function() {
            
            if (this.value && currentSchoolYearId) {
                divCamposFormativos.classList.remove('d-none');
                const gradesList = document.getElementById('gradesList');
                const loadingIndicator = document.getElementById('loadingGrades');
                
                // Verificar si tenemos ID de estudiante
                if (selectedStudentId) {
                    // Mostrar indicador de carga
                    loadingIndicator.classList.remove('d-none');
                    gradesList.innerHTML = '';
                    
                    // Usar la función loadStudentGrades que ya filtra por estudiante
                    loadStudentGrades(selectedStudentId, currentSchoolYearId, this.value);
                } else {
                    loadingIndicator.classList.add('d-none');
                    gradesList.innerHTML = '<div class="alert alert-warning">No se ha seleccionado ningún estudiante. Por favor cierre este modal y vuelva a intentarlo.</div>';
                }
            } else {
                document.getElementById('contenedorBotonDescargar').classList.add('d-none');
                divCamposFormativos.classList.add('d-none');
            }
        });


        
        // Evento: Botón Ver detalles
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

            const quarterId = document.getElementById('trimestreFormativo').value;
>>>>>>> Stashed changes
            
            if (currentSchoolYearId && quarterId && selectedStudentId) {
                // Construir la URL para generar el PDF
                const pdfUrl = `generate_boleta_pdf.php?idStudent=${selectedStudentId}&idSchoolYear=${currentSchoolYearId}&idSchoolQuarter=${quarterId}`;
                
                // Abrir el PDF en una nueva ventana
                window.open(pdfUrl, '_blank');
            } else {
                alert('Por favor, seleccione un trimestre para generar la boleta.');
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
                studentInfoDiv.className = 'alert alert-light mb-4 border';
                
                // Insertar al principio del modal-body
                modalBody.insertBefore(studentInfoDiv, modalBody.firstChild);
            }
            
            // Actualizar la información del estudiante
            studentInfoDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Alumno:</strong> ${studentName}<br>
                    </div>
                    <div class="text-end">
                        <strong>Grado:</strong> ${gradeInfo}° <strong>Grupo:</strong> ${groupInfo}
                    </div>
                </div>
            `;
            
            // Cargar automáticamente los campos formativos si ya hay año escolar y trimestre seleccionados
            if (yearSelect.value && quarterSelect.value) {
                divCamposFormativos.classList.remove('d-none');
                const loadingIndicator = document.getElementById('loadingGrades');
                loadingIndicator.classList.remove('d-none');
                const gradesList = document.getElementById('gradesList');
                gradesList.innerHTML = '';
                loadStudentGrades(selectedStudentId, yearSelect.value, quarterSelect.value);
            } else {
                divCamposFormativos.classList.add('d-none');
            }
        });
        
        // Reiniciar el modal cuando se cierre
        modalCamposFormativos.addEventListener('hidden.bs.modal', function() {
            debug("Modal de boleta cerrado - reseteo");
            
            // Restablecer el selector de trimestre
            const trimesterSelect = document.getElementById('trimestreFormativo');
            trimesterSelect.selectedIndex = 0;
            
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
            const contenedorGrupo = document.getElementById('contenedorGrupo');
            const alumnosBody = document.getElementById('alumnos-tbody');

            // Función para cargar alumnos
            function cargarAlumnos(groupId) {
                if (!groupId || !currentSchoolYearId) {
                    alumnosBody.innerHTML = '<tr><td colspan="11" class="text-center">Seleccione un grupo para ver los alumnos.</td></tr>';
                    return;
                }

                fetch(`get_students.php?grupo=${groupId}&schoolYear=${currentSchoolYearId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data || !data.students || data.students.length === 0) {
                            alumnosBody.innerHTML = '<tr><td colspan="11" class="text-center">No hay alumnos en este grupo.</td></tr>';
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
                                <td>${student.curp || ''}</td>
                                <td>${student.grade}°</td>
                                <td>${student.group_}</td>
                                <td>${getStatusBadge(student.nomenclature, student.description)}</td>
                                <td class="text-center">
                                    <button type="button" id="botonVer" class="btn-boleta"
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
                                <td>
                                    <button type="button" id="botonVer" class="botonVer"
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
                                    <button type="button" class="botonReporte"
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
                        alumnosBody.innerHTML = '<tr><td colspan="11" class="text-center">Error al cargar los alumnos.</td></tr>';
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
                        case 'RC': badge = 'dark'; break;      // Recursando
                        case 'EX': badge = 'light'; break;     // Expulsado
                    }
                    return `<span class="badge bg-${badge}">${description}</span>`;
                }
                return '';
            }

            // Event Listeners
            schoolYearSelect.addEventListener('change', function() {
                cargarGrupos(this.value);
                cargarAlumnos('', '');
                // Ocultar tabla al cambiar de año
                document.getElementById('contenedorTabla').classList.add('d-none');
                checkDownloadButton(); // Verificar si mostrar botón de descarga
            });

            grupoSelect.addEventListener('change', function() {
                // No cargamos alumnos aquí, esperamos al trimestre
                // Ocultar tabla al cambiar de grupo
                document.getElementById('contenedorTabla').classList.add('d-none');
                checkDownloadButton(); // Verificar si mostrar botón de descarga
            });

            // Event listener para el trimestre - mostrar tabla cuando se seleccione
            document.getElementById('trimestre').addEventListener('change', function() {
                const contenedorTabla = document.getElementById('contenedorTabla');
                if (this.value && grupoSelect.value && schoolYearSelect.value) {
                    // Mostrar tabla y cargar alumnos
                    contenedorTabla.classList.remove('d-none');
                    cargarAlumnos(grupoSelect.value, schoolYearSelect.value);
                } else {
                    // Ocultar tabla si no hay trimestre seleccionado
                    contenedorTabla.classList.add('d-none');
                }
            });

            // Función para verificar si mostrar el botón de descarga
            function checkDownloadButton() {
                const descargarBtn = document.getElementById('descargarGrupoBtn');
                const grupoValue = grupoSelect.value;
                
                if (currentSchoolYearId && grupoValue) {
                    descargarBtn.style.display = 'block';
                } else {
                    descargarBtn.style.display = 'none';
                }
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

                const grupoValue = grupoSelect.value;
                
                if (!currentSchoolYearId || !grupoValue) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selección incompleta',
                        text: 'Por favor selecciona un grupo antes de descargar.'
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
                        descargarPDFsGrupo(currentSchoolYearId, grupoValue);
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
            if (e.target.closest('.botonReporte')) {
                const button = e.target.closest('.botonReporte');
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
                                <td>
                                    <button class="btn btn-sm btn-danger me-2" onclick="viewReportPDF(${report.idConductReport})" title="Ver PDF">
                                        <i class="bi bi-file-pdf"></i> Ver PDF
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="viewReportDetails(${report.idConductReport})" title="Ver detalles">
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
                Swal.fire({
                    title: 'Detalles del Reporte',
                    html: `
                        <div class="text-start">
                            <p><strong>Fecha:</strong> ${report.fecha}</p>
                            <p><strong>Docente:</strong> ${report.teacherFullName || 'N/A'}</p>
                            <hr>
                            <p><strong>Descripción:</strong></p>
                            <p>${report.descripcion}</p>
                            ${report.observaciones ? `<hr><p><strong>Observaciones:</strong></p><p>${report.observaciones}</p>` : ''}
                            <hr>
                            <p class="text-muted"><small>Creado: ${report.createdAt}</small></p>
                        </div>
                    `,
                    icon: 'info',
                    width: '600px',
                    showCloseButton: true,
                    confirmButtonText: 'Cerrar'
                });
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
                dateFormat: "Y-m-d",        // Formato interno para la base de datos
                altInput: true,              // Usar input alternativo para mostrar
                altFormat: "d/m/Y",          // Formato visual en español: día/mes/año
                defaultDate: new Date(),
                allowInput: false,
                disableMobile: true
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
            
            // Con altInput, el input original ya tiene el formato Y-m-d correcto para el servidor
            // No necesitamos conversión adicional
            
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