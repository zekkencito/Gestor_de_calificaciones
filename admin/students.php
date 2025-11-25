<?php
require_once "check_session.php";
require_once "../force_password_check.php";
include '../conection.php';

// --- FECHA LIMITE GLOBAL PARA DESCARGAS ---
$fechaLimite = null;
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $fechaLimite = $row['limitDate'];
}
$hoy = date('Y-m-d');
$descargasHabilitadas = ($fechaLimite && $hoy > date('Y-m-d', strtotime($fechaLimite . ' +0 day')));

// Obtener los ciclos escolares disponibles
$schoolYears = [];
$sqlSchoolYears = "SELECT idSchoolYear, startDate, endDate, description 
                   FROM schoolYear 
                   ORDER BY startDate DESC";
$resultSchoolYears = $conexion->query($sqlSchoolYears);

// Verificar si la consulta tuvo éxito y si hay años escolares
if (!$resultSchoolYears) {
    echo "<!-- Error en la consulta: " . $conexion->error . " -->";
}

if ($resultSchoolYears) {
    while ($row = $resultSchoolYears->fetch_assoc()) {
        $schoolYears[] = $row;
    }
    $resultSchoolYears->close();
}

// Verificar si hay ciclos escolares
echo "<!-- Número de ciclos escolares: " . count($schoolYears) . " -->";
if (count($schoolYears) > 0) {
    echo "<!-- Primer ciclo: " . json_encode($schoolYears[0]) . " -->";
} else {
    echo "<!-- No hay ciclos escolares disponibles -->";
}

// Obtener los trimestres/periodos disponibles
$quarters = [];
$sqlQuarters = "SELECT idSchoolQuarter, name FROM schoolQuarter";
$resultQuarters = $conexion->query($sqlQuarters);
if ($resultQuarters) {
    while ($row = $resultQuarters->fetch_assoc()) {
        $quarters[] = $row;
    }
    $resultQuarters->close();
}

// Consulta para obtener la lista de estudiantes con información relacionada
$sql = "SELECT 
    s.idStudent, 
    s.idStudentStatus, 
    ui.names, 
    ui.lastnamePa, 
    ui.lastnameMa, 
    ui.phone, 
    ui.street, 
    ui.gender, 
    ui.email, 
    s.curp, 
    ss.nomenclature, 
    ss.description as status, 
    u.username, 
    u.password,
    s.idGroup,
    CONCAT(g.grade, g.group_) as grupo,
    s.idSchoolYear, 
    LEFT(sy.startDate, 4) as schoolYear,
    t.idTutor,
    t.tutorName,
    t.tutorLastnamePa,
    t.tutorLastnameMa,
    t.tutorPhone,
    t.tutorEmail,
    t.tutorAddress,
    t.ine,
    t.relative_ as tutorRelationship
        FROM students s
        INNER JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
        LEFT JOIN users u ON ui.idUserInfo = u.idUserInfo
        INNER JOIN studentStatus ss ON s.idStudentStatus = ss.idStudentStatus
        LEFT JOIN groups g ON s.idGroup = g.idGroup
LEFT JOIN schoolYear sy ON s.idSchoolYear = sy.idSchoolYear
LEFT JOIN tutors t ON s.idTutor = t.idTutor";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error en la consulta: " . $conexion->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        .table td, .table th {
            text-align: center;
            vertical-align: middle;
        }
        .table tbody tr td {
            padding: 1rem;
        }
        .table tbody tr td ul {
            margin: 0;
            padding: 0;
        }
        .table tbody tr td ul li {
            text-align: left;
            margin-bottom: 0.25rem;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumnos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/admin/student.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    
    <!-- TIPOGRAFIA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    
    <link rel="icon" href="../img/logo.ico">

</head>
<body class="row d-flex" style="height: 100vh; width: 100%; margin: 0; padding: 0; overflow: hidden;">
    <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>
    <!-- ASIDEBAR -->
    <?php include "../layouts/aside.php"; ?>
    <!-- END ASIDEBAR -->

    
    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 col-9 p-0" style="height: 100vh; overflow-y: auto;">
        <?php include "../layouts/header.php"; ?>
        
        <!-- Header de la página -->
        <div class="container-fluid px-4 pt-5">
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-3">
                        <h1 class="page-title">
                            <i class="bi bi-people me-3"></i>
                            Gestión de Estudiantes
                        </h1>
                        <p class="page-subtitle">
                            Administra la información de los estudiantes del sistema
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
                                    <div class="col-md-6">
                                        <!-- BUSCAR POR ALUMNO -->
                                        <div class="search-container">
                                            <label for="alumno" class="form-label fw-semibold">
                                                <i class="bi bi-person-search me-1"></i>
                                                Buscar por Alumno:
                                            </label>
                                            <div class="input-group">
                                                <input type="text" class="form-control border-secondary" id="alumno" placeholder="Buscar alumno...">
                                                <span class="input-group-text bg-light border-secondary">
                                                    <i class="bi bi-search text-primary"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <!-- BUSCAR POR AÑO ESCOLAR -->
                                        <div class="search-container">
                                            <label for="schoolYear" class="form-label fw-semibold">
                                                <i class="bi bi-calendar-date me-1"></i>
                                                Año Escolar:
                                            </label>
                                            <select class="form-select border-secondary" id="schoolYear" name="schoolYear">
                                                <option value="">Todos los años</option>
                                                <?php
                                                $sqlYears = "SELECT idSchoolYear, CONCAT(LEFT(startDate, 4)) as year FROM schoolYear ORDER BY startDate DESC";
                                                $resultYears = $conexion->query($sqlYears);
                                                while ($year = $resultYears->fetch_assoc()) {
                                                    $selected = (isset($_GET['year']) && $_GET['year'] == $year['idSchoolYear']) ? 'selected' : '';
                                                    echo "<option value='" . $year['idSchoolYear'] . "' $selected>" . $year['year'] . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mt-2">
                                    <!-- Segunda fila de búsquedas -->
                                    <div class="col-md-6">
                                        <!-- BUSCAR POR GRUPO -->
                                        <div class="search-container">
                                            <label for="grupo" class="form-label fw-semibold">
                                                <i class="bi bi-collection me-1"></i>
                                                Buscar por Grupo:
                                            </label>
                                            <select class="form-select border-secondary" id="grupo" name="grupo">
                                                <option value="">Todos los grupos</option>
                                                <?php
                                                $sqlGroups = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
                                                $resultGroups = $conexion->query($sqlGroups);
                                                while ($group = $resultGroups->fetch_assoc()) {
                                                    $selected = (isset($_GET['group']) && $_GET['group'] == $group['idGroup']) ? 'selected' : '';
                                                    echo "<option value='" . $group['idGroup'] . "' $selected>" . $group['grupo'] . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <!-- Botones de acción -->
                                        <div class="d-flex gap-2 w-100">
                                            <!-- Botón para descargar PDFs del grupo (oculto por defecto) -->
                                            <button id="btnDescargarGrupo" 
                                                    class="btn <?php echo $descargasHabilitadas ? 'btn-success' : 'btn-secondary'; ?> d-none flex-fill" 
                                                    <?php if(!$descargasHabilitadas) echo 'disabled title="Las descargas se habilitarán después del ' . date('d/m/Y', strtotime($fechaLimite)) . '"'; ?>>
                                                <i class="bi bi-download me-2"></i> 
                                                <?php echo $descargasHabilitadas ? 'Descargar PDFs del Grupo' : 'Descarga disponible después del ' . date('d/m/Y', strtotime($fechaLimite)); ?>
                                            </button>
                                            
                                            <button class="btn btn-primary flex-fill" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                                <i class="bi bi-plus-lg me-2"></i>
                                                Inscribir alumno
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de estudiantes -->
            <div class="row">
                <div class="col-12">
                    <div class="table-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-table me-2 text-success"></i>
                                    Lista de Alumnos
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="fw-semibold">No.</th>
                                                <th class="fw-semibold">A. Paterno</th>
                                                <th class="fw-semibold">A. Materno</th>
                                                <th class="fw-semibold">Nombres</th>
                                                <th class="fw-semibold">Grupo</th>
                                                <th class="fw-semibold">Año Escolar</th>
                                                <th class="fw-semibold">Estado</th>
                                                <th class="fw-semibold text-center">Boleta</th>
                                                <th class="fw-semibold text-center">Ver</th>
                                                <th class="fw-semibold text-center">Editar</th>
                                            </tr>
                                        </thead>
                                        <tbody id="alumnosBody">
                    <?php while ($row = $resultado->fetch_assoc()) { ?>
                        <tr data-schoolyear="<?php echo htmlspecialchars($row['idSchoolYear']); ?>" data-grupo="<?php echo htmlspecialchars($row['idGroup']); ?>">
                            <td><?php echo htmlspecialchars($row['idStudent']); ?></td>
                            <td><?php echo htmlspecialchars($row['lastnamePa']); ?></td>
                            <td><?php echo htmlspecialchars($row['lastnameMa']); ?></td>
                            <td><?php echo htmlspecialchars($row['names']); ?></td>
                            <td><?php echo htmlspecialchars($row['grupo']); ?></td>
                            <td><?php echo htmlspecialchars($row['schoolYear']); ?></td>
                            <td><?php
                                if ($row['nomenclature'] == 'AC') {
                                    echo '<span class="badge bg-success">' . $row['status'] . '</span>';
                                } elseif ($row['nomenclature'] == 'BA') {
                                    echo '<span class="badge bg-danger">' . $row['status'] . '</span>';
                                } elseif ($row['nomenclature'] == 'RE') {
                                    echo '<span class="badge bg-warning">' . $row['status'] . '</span>';
                                }elseif ($row['nomenclature'] == 'EG') {
                                    echo '<span class="badge bg-primary">' . $row['status'] . '</span>';
                                }elseif ($row['nomenclature'] == 'IN') {
                                    echo '<span class="badge bg-secondary">' . $row['status'] . '</span>';
                                }elseif ($row['nomenclature'] == 'TR') {
                                    echo '<span class="badge bg-info">' . $row['status'] . '</span>';
                                }elseif ($row['nomenclature'] == 'RC') {
                                    echo '<span class="badge bg-dark">' . $row['status'] . '</span>';
                                }elseif ($row['nomenclature'] == 'EX') {
                                    echo '<span class="badge bg-light">' . $row['status'] . '</span>';
                                }
                            ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalCamposFormativos"
                                    data-id="<?php echo $row['idStudent']; ?>"
                                    data-nombres="<?php echo htmlspecialchars($row['names']); ?>"
                                    data-paterno="<?php echo htmlspecialchars($row['lastnamePa']); ?>"
                                    data-materno="<?php echo htmlspecialchars($row['lastnameMa']); ?>"
                                    data-grade="<?php echo htmlspecialchars($row['grade']); ?>"
                                    data-grupo="<?php echo htmlspecialchars($row['grupo']); ?>"
                                    title="Ver boleta">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info btn-ver" 
                                data-id="<?php echo isset($row['idStudent']) ? htmlspecialchars($row['idStudent']) : ''; ?>"
                                data-nombres="<?php echo isset($row['names']) ? htmlspecialchars($row['names']) : ''; ?>"
                                data-paterno="<?php echo isset($row['lastnamePa']) ? htmlspecialchars($row['lastnamePa']) : ''; ?>"
                                data-materno="<?php echo isset($row['lastnameMa']) ? htmlspecialchars($row['lastnameMa']) : ''; ?>"
                                data-status="<?php echo isset($row['status']) ? htmlspecialchars($row['status']) : ''; ?>"
                                data-grupo="<?php echo isset($row['grupo']) ? htmlspecialchars($row['grupo']) : ''; ?>"
                                data-schoolyear="<?php echo isset($row['schoolYear']) ? htmlspecialchars($row['schoolYear']) : ''; ?>"
                                data-genero="<?php echo isset($row['gender']) ? htmlspecialchars($row['gender']) : ''; ?>"
                                data-direccion="<?php echo isset($row['street']) ? htmlspecialchars($row['street']) : ''; ?>"
                                data-telefono="<?php echo isset($row['phone']) ? htmlspecialchars($row['phone']) : ''; ?>"
                                data-username="<?php echo isset($row['username']) ? htmlspecialchars($row['username']) : ''; ?>"
                                data-email="<?php echo isset($row['email']) ? htmlspecialchars($row['email']) : ''; ?>"
                                data-curp="<?php echo isset($row['curp']) ? htmlspecialchars($row['curp']) : ''; ?>"
                                data-grado="<?php echo isset($row['idGroup']) ? htmlspecialchars($row['idGroup']) : ''; ?>"
                                data-tutornombres="<?php echo isset($row['tutorName']) ? htmlspecialchars($row['tutorName']) : ''; ?>"
                                data-tutorpaterno="<?php echo isset($row['tutorLastnamePa']) ? htmlspecialchars($row['tutorLastnamePa']) : ''; ?>"
                                data-tutormaterno="<?php echo isset($row['tutorLastnameMa']) ? htmlspecialchars($row['tutorLastnameMa']) : ''; ?>"
                                data-tutorine="<?php echo isset($row['ine']) ? htmlspecialchars($row['ine']) : ''; ?>"
                                data-tutortelefono="<?php echo isset($row['tutorPhone']) ? htmlspecialchars($row['tutorPhone']) : ''; ?>"
                                data-tutoremail="<?php echo isset($row['tutorEmail']) ? htmlspecialchars($row['tutorEmail']) : ''; ?>"
                                data-tutordireccion="<?php echo isset($row['tutorAddress']) ? htmlspecialchars($row['tutorAddress']) : ''; ?>"
                                data-tutorparentesco="<?php echo isset($row['tutorRelationship']) ? htmlspecialchars($row['tutorRelationship']) : ''; ?>"
                                title="Ver detalles">
                                <i class="bi bi-person-fill"></i>
                            </button>
                            </td>
                            <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning btn-editar"    
                                data-id="<?php echo isset($row['idStudent']) ? htmlspecialchars($row['idStudent']) : ''; ?>"
                                data-nombres="<?php echo isset($row['names']) ? htmlspecialchars($row['names']) : ''; ?>"
                                data-paterno="<?php echo isset($row['lastnamePa']) ? htmlspecialchars($row['lastnamePa']) : ''; ?>"
                                data-materno="<?php echo isset($row['lastnameMa']) ? htmlspecialchars($row['lastnameMa']) : ''; ?>"
                                data-status="<?php echo isset($row['idStudentStatus']) ? htmlspecialchars($row['idStudentStatus']) : '1'; ?>"
                                data-grupo="<?php echo isset($row['idGroup']) ? htmlspecialchars($row['idGroup']) : ''; ?>"
                                data-schoolyear="<?php echo isset($row['idSchoolYear']) ? htmlspecialchars($row['idSchoolYear']) : ''; ?>"
                                data-telefono="<?php echo isset($row['phone']) ? htmlspecialchars($row['phone']) : ''; ?>"
                                data-tutornombres="<?php echo isset($row['tutorName']) ? htmlspecialchars($row['tutorName']) : ''; ?>"
                                data-tutorpaterno="<?php echo isset($row['tutorLastnamePa']) ? htmlspecialchars($row['tutorLastnamePa']) : ''; ?>"
                                data-tutormaterno="<?php echo isset($row['tutorLastnameMa']) ? htmlspecialchars($row['tutorLastnameMa']) : ''; ?>"
                                data-tutortelefono="<?php echo isset($row['tutorPhone']) ? htmlspecialchars($row['tutorPhone']) : ''; ?>"
                                data-tutorine="<?php echo isset($row['ine']) ? htmlspecialchars($row['ine']) : ''; ?>"
                                data-tutoremail="<?php echo isset($row['tutorEmail']) ? htmlspecialchars($row['tutorEmail']) : ''; ?>"
                                data-tutordireccion="<?php echo isset($row['tutorAddress']) ? htmlspecialchars($row['tutorAddress']) : ''; ?>"
                                data-tutorparentesco="<?php echo isset($row['tutorRelationship']) ? htmlspecialchars($row['tutorRelationship']) : ''; ?>"
                                data-genero="<?php echo isset($row['gender']) ? htmlspecialchars($row['gender']) : ''; ?>"
                                data-direccion="<?php echo isset($row['street']) ? htmlspecialchars($row['street']) : ''; ?>"
                                data-username="<?php echo isset($row['username']) ? htmlspecialchars($row['username']) : ''; ?>"
                                data-email="<?php echo isset($row['email']) ? htmlspecialchars($row['email']) : ''; ?>"
                                data-curp="<?php echo isset($row['curp']) ? htmlspecialchars($row['curp']) : ''; ?>"
                                title="Editar estudiante">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            </td>
                        </tr>
                    <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Estado vacío -->
                                <div id="emptyState" class="text-center py-5" style="display: none;">
                                    <div class="mb-3">
                                        <i class="bi bi-inbox display-4 text-muted"></i>
                                    </div>
                                    <h5 class="text-muted">No hay estudiantes registrados</h5>
                                    <p class="text-muted">Agrega estudiantes usando el botón "Inscribir alumno"</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- END MAIN CONTENT -->
    
    <!-- MODAL AGREGAR ALUMNO -->
    <div class="modal fade modal-lg" id="addStudentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h1 id="tituloModal" class="modal-title fs-5">
                        <i class="bi bi-person-plus me-2"></i>
                        Inscribir Alumno
                    </h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="addStudent.php" id="formInscribir" method="POST" class="needs-validation" novalidate>
                        <!-- Información Personal -->
                        <div class="mb-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person me-2"></i>
                                Información Personal
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtName">
                                        <i class="bi bi-person me-1"></i>
                                        Nombres:
                                    </label>
                                    <input required type="text" name="txtName" class="form-control border-secondary" placeholder="Nombres">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtLastnamePa">
                                        <i class="bi bi-person me-1"></i>
                                        Apellido Paterno:
                                    </label>
                                    <input required type="text" name="txtLastnamePa" class="form-control border-secondary" placeholder="Apellido Paterno">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtLastnameMa">
                                        <i class="bi bi-person me-1"></i>
                                        Apellido Materno:
                                    </label>
                                    <input required type="text" name="txtLastnameMa" class="form-control border-secondary" placeholder="Apellido Materno">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtGender">
                                        <i class="bi bi-gender-ambiguous me-1"></i>
                                        Género:
                                    </label>
                                    <select required name="txtGender" class="form-select border-secondary">
                                        <option value="">Seleccionar género</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Seleccione una opción</div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold" for="txtCurp">
                                        <i class="bi bi-card-text me-1"></i>
                                        CURP:
                                    </label>
                                    <input required type="text" name="txtCurp" class="form-control border-secondary" placeholder="CURP">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="mb-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-telephone me-2"></i>
                                Información de Contacto
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="txtPhone">
                                        <i class="bi bi-telephone me-1"></i>
                                        Teléfono:
                                    </label>
                                    <input required type="tel" name="txtPhone" class="form-control border-secondary" placeholder="Teléfono">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="txtEmail">
                                        <i class="bi bi-envelope me-1"></i>
                                        Correo:
                                    </label>
                                    <input required type="email" name="txtEmail" class="form-control border-secondary" placeholder="Correo">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Ingrese un correo válido</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="txtAddress">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        Dirección:
                                    </label>
                                    <input required type="text" name="txtAddress" class="form-control border-secondary" placeholder="Dirección">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                            </div>
                        </div>

                        <!-- Información Académica -->
                        <div class="mb-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-book me-2"></i>
                                Información Académica
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="txtGroup">
                                        <i class="bi bi-people me-1"></i>
                                        Grupo:
                                    </label>
                                    <select required name="txtGroup" class="form-select border-secondary">
                                        <option value="">Seleccionar grupo</option>
                                        <?php
                                        $sqlGroups = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
                                        $resultGroups = $conexion->query($sqlGroups);
                                        while ($group = $resultGroups->fetch_assoc()) {
                                            echo "<option value='" . $group['idGroup'] . "'>" . $group['grupo'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Seleccione una opción</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="txtSchoolYear">
                                        <i class="bi bi-calendar me-1"></i>
                                        Año Escolar:
                                    </label>
                                    <select required name="txtSchoolYear" class="form-select border-secondary">
                                        <option value="">Seleccionar año</option>
                                        <?php
                                        $sqlYears = "SELECT idSchoolYear, CONCAT(LEFT(startDate, 4), '-', LEFT(endDate, 4)) as year FROM schoolYear ORDER BY startDate DESC";
                                        $resultYears = $conexion->query($sqlYears);
                                        while ($year = $resultYears->fetch_assoc()) {
                                            echo "<option value='" . $year['idSchoolYear'] . "'>" . $year['year'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Seleccione una opción</div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Tutor -->
                        <div class="mb-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person-heart me-2"></i>
                                Información del Tutor
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtTutorName">
                                        <i class="bi bi-person me-1"></i>
                                        Nombres:
                                    </label>
                                    <input required type="text" name="txtTutorName" class="form-control border-secondary" placeholder="Nombres del tutor">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtTutorLastnames">
                                        <i class="bi bi-person me-1"></i>
                                        Apellidos:
                                    </label>
                                    <input required type="text" name="txtTutorLastnames" class="form-control border-secondary" placeholder="Apellidos del tutor">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtTutorIne">
                                        <i class="bi bi-card-text me-1"></i>
                                        INE:
                                    </label>
                                    <input required type="text" name="txtTutorIne" class="form-control border-secondary" placeholder="INE del tutor">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtTutorPhone">
                                        <i class="bi bi-telephone me-1"></i>
                                        Teléfono:
                                    </label>
                                    <input required type="tel" name="txtTutorPhone" class="form-control border-secondary" placeholder="Teléfono del tutor">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtTutorEmail">
                                        <i class="bi bi-envelope me-1"></i>
                                        Correo:
                                    </label>
                                    <input required type="email" name="txtTutorEmail" class="form-control border-secondary" placeholder="Correo del tutor">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Ingrese un correo válido</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="txtTutorRelative">
                                        <i class="bi bi-heart me-1"></i>
                                        Parentesco:
                                    </label>
                                    <input required type="text" name="txtTutorRelative" class="form-control border-secondary" placeholder="Parentesco del tutor">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="txtTutorAddress">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        Dirección:
                                    </label>
                                    <input required type="text" name="txtTutorAddress" class="form-control border-secondary" placeholder="Dirección del tutor">
                                    <div class="valid-feedback">Correcto</div>
                                    <div class="invalid-feedback">Campo requerido</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-danger d-none" id="divAlerta">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Favor de llenar todos los campos correctamente
                        </div>

                        <div class="modal-footer border-0 bg-light mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Inscribir Alumno
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL VER ALUMNO -->
    <!-- Modal para mostrar detalles del estudiante -->
    <div class="modal fade modal-lg" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="bi bi-person-circle me-2"></i>
                        Información del Estudiante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Información Personal -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person-badge me-2"></i>
                                Datos Personales
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-muted">ID:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_id">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Nombres:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_nombres">-</p>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-muted">Estado:</label>
                            <div class="border rounded px-3 py-2 bg-light d-flex align-items-center" id="show_status">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Apellido Paterno:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_paterno">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Apellido Materno:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_materno">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">CURP:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_curp">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Género:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_genero">-</p>
                        </div>
                        
                        <!-- Información Académica -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-book me-2"></i>
                                Información Académica
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Grupo:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_grupo">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Año Escolar:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_schoolYear">-</p>
                        </div>
                        
                        <!-- Información de Contacto -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-telephone me-2"></i>
                                Información de Contacto
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Teléfono:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_telefono">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Email:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_email">-</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted">Dirección:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_direccion">-</p>
                        </div>
                        
                        <!-- Información del Tutor -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person-hearts me-2"></i>
                                Información del Tutor
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Nombres del Tutor:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_tutorNombres">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Apellidos del Tutor:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_tutorApellidos">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">INE del Tutor:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_tutorIne">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Parentesco:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_tutorParentesco">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Teléfono del Tutor:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_tutorPhone">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Email del Tutor:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_tutorEmail">-</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted">Dirección del Tutor:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="show_tutorAddress">-</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar estudiante -->
    <div class="modal fade modal-lg" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="bi bi-pencil me-2"></i>
                        Editar Estudiante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="updateStudent.php" method="POST" class="needs-validation" novalidate id="formEditStudent">
                        <input type="hidden" name="studentId" id="studentId">
                        <div class="row g-3">
                            <!-- Información Personal -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-person-badge me-2"></i>
                                    Datos Personales
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="editName" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Nombres <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editName" name="txtName" required>
                                <div class="invalid-feedback">Por favor ingrese los nombres.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editLastnamePa" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Paterno <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editLastnamePa" name="txtLastnamePa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido paterno.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editLastnameMa" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Materno <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editLastnameMa" name="txtLastnameMa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido materno.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editCurp" class="form-label fw-semibold">
                                    <i class="bi bi-card-text me-1"></i>
                                    CURP <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editCurp" name="txtCurp" required>
                                <div class="invalid-feedback">Por favor ingrese el CURP.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editGender" class="form-label fw-semibold">
                                    <i class="bi bi-gender-ambiguous me-1"></i>
                                    Género <span class="text-danger">*</span>
                                </label>
                                <select class="form-select border-secondary" id="editGender" name="txtGender" required>
                                    <option value="">Seleccionar género</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un género.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editStatus" class="form-label fw-semibold">
                                    <i class="bi bi-bookmark me-1"></i>
                                    Estado <span class="text-danger">*</span>
                                </label>
                                <select class="form-select border-secondary" id="editStatus" name="txtStatus" required>
                                    <option value="">Seleccionar estado</option>
                                    <option value="1">Activo, actualmente cursando el año escolar</option>
                                    <option value="2">Dado de baja (por traslado, abandono u otras razones)</option>
                                    <option value="3">Reinscrito después de una baja</option>
                                    <option value="4">Egresado</option>
                                    <option value="5">Inscrito, pendiente de comenzar clases</option>
                                    <option value="6">En trámite de inscripción o documentación</option>
                                    <option value="7">Repetidor de grado</option>
                                    <option value="8">Intercambio temporal</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un estado.</div>
                            </div>

                            <!-- Información Académica -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-book me-2"></i>
                                    Información Académica
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="editGroup" class="form-label fw-semibold">
                                    <i class="bi bi-people me-1"></i>
                                    Grupo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select border-secondary" id="editGroup" name="txtGroup" required>
                                    <option value="">Seleccionar grupo</option>
                                    <?php
                                    $sqlGroups = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
                                    $resultGroups = $conexion->query($sqlGroups);
                                    while ($group = $resultGroups->fetch_assoc()) {
                                        echo "<option value='" . $group['idGroup'] . "'>" . $group['grupo'] . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un grupo.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editSchoolYear" class="form-label fw-semibold">
                                    <i class="bi bi-calendar me-1"></i>
                                    Año Escolar <span class="text-danger">*</span>
                                </label>
                                <select class="form-select border-secondary" id="editSchoolYear" name="txtSchoolYear" required>
                                    <option value="">Seleccionar año</option>
                                    <?php
                                    $sqlYears = "SELECT idSchoolYear, CONCAT(LEFT(startDate, 4), '-', LEFT(endDate, 4)) as year FROM schoolYear ORDER BY startDate DESC";
                                    $resultYears = $conexion->query($sqlYears);
                                    while ($year = $resultYears->fetch_assoc()) {
                                        echo "<option value='" . $year['idSchoolYear'] . "'>" . $year['year'] . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un año escolar.</div>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-telephone me-2"></i>
                                    Información de Contacto
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="editPhone" class="form-label fw-semibold">
                                    <i class="bi bi-telephone me-1"></i>
                                    Teléfono <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control border-secondary" id="editPhone" name="txtPhone" required>
                                <div class="invalid-feedback">Por favor ingrese un teléfono válido.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editEmail" class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-1"></i>
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control border-secondary" id="editEmail" name="txtEmail" required>
                                <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                            </div>
                            <div class="col-12">
                                <label for="editAddress" class="form-label fw-semibold">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Dirección <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control border-secondary" id="editAddress" name="txtAddress" rows="2" required></textarea>
                                <div class="invalid-feedback">Por favor ingrese la dirección.</div>
                            </div>

                            <!-- Información del Tutor -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-person-hearts me-2"></i>
                                    Información del Tutor
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="editTutorName" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Nombres del Tutor <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editTutorName" name="txtTutorName" required>
                                <div class="invalid-feedback">Por favor ingrese los nombres del tutor.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="editTutorLastnamePa" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Paterno del Tutor <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editTutorLastnamePa" name="txtTutorLastnamePa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido paterno del tutor.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="editTutorLastnameMa" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Materno del Tutor <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editTutorLastnameMa" name="txtTutorLastnameMa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido materno del tutor.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editTutorIne" class="form-label fw-semibold">
                                    <i class="bi bi-card-heading me-1"></i>
                                    INE del Tutor <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editTutorIne" name="txtTutorIne" required>
                                <div class="invalid-feedback">Por favor ingrese el INE del tutor.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editTutorRelative" class="form-label fw-semibold">
                                    <i class="bi bi-heart me-1"></i>
                                    Parentesco <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-secondary" id="editTutorRelative" name="txtTutorRelative" required>
                                <div class="invalid-feedback">Por favor ingrese el parentesco.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editTutorPhone" class="form-label fw-semibold">
                                    <i class="bi bi-telephone me-1"></i>
                                    Teléfono del Tutor <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control border-secondary" id="editTutorPhone" name="txtTutorPhone" required>
                                <div class="invalid-feedback">Por favor ingrese el teléfono del tutor.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="editTutorEmail" class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-1"></i>
                                    Email del Tutor <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control border-secondary" id="editTutorEmail" name="txtTutorEmail" required>
                                <div class="invalid-feedback">Por favor ingrese un email válido para el tutor.</div>
                            </div>
                            <div class="col-12">
                                <label for="editTutorAddress" class="form-label fw-semibold">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Dirección del Tutor <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control border-secondary" id="editTutorAddress" name="txtTutorAddress" rows="2" required></textarea>
                                <div class="invalid-feedback">Por favor ingrese la dirección del tutor.</div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 bg-light mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Actualizar Estudiante
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


     <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-person-x text-danger display-1 mb-3"></i>
                        <h5>¿Está seguro que desea eliminar este docente?</h5>
                        <p class="text-muted" id="delete-teacher-info">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger"  id="eliminar">
                        <i class="bi bi-trash me-2"></i>
                        Eliminar Docente
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- MODAL ELIMINAR ALUMNO 
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 id="tituloModal" class="modal-title fs-5">¿Desea eliminar este alumno?</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-footer">
                    <button class="botonCancelar" type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#showModal">Cancelar
                        <i id="iconoAdd" class="bi bi-x-circle-fill"></i>
                    </button>
                    <button class="botonEnter" type="submit" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">Eliminar
                        <i id="iconoAdd" class="bi bi-trash3-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>-->

    <!-- MODAL CONFIRMAR ELIMINACIÃ“N 
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 id="tituloModal" class="modal-title fs-5">¿Está seguro que desea eliminar este alumno?</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-footer">
                    <button class="botonCancelar" type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#showModal">Cambié de opinión
                        <i id="iconoAdd" class="bi bi-x-circle-fill"></i>
                    </button>
                    <button class="botonEnter" type="submit" class="btn btn-primary btnEliminar" id="eliminar">Eliminar
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>-->

    <!-- MODAL BOLETA -->
    <div class="modal fade" id="modalCamposFormativos" tabindex="-1" aria-labelledby="modalCamposFormativosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="modalCamposFormativosLabel">Boleta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <!-- Ãrea para mostrar la información del estudiante -->
                    <!--div id="studentInfo" class="alert alert-light mb-4 border">
                        <-- La información del estudiante se cargará dinámicamente --
                    </div-->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cicloFormativo" class="form-label fw-bold">Año Escolar:</label>
                                <select class="form-select" id="cicloFormativo">
                                    <option value="" selected>Seleccionar año escolar</option>
                                    <?php 
                                    $years = $conexion->query("SELECT idSchoolYear, startDate, endDate FROM schoolYear ORDER BY startDate DESC");
                                    while ($year = $years->fetch_assoc()):
                                        $label = substr($year['startDate'], 0, 4);
                                    ?>
                                        <option value="<?php echo $year['idSchoolYear']; ?>"><?php echo $label; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                     <div class="col-md-6">        
                        <div class="mb-3 d-none" id="divTrimestreFormativo">
                            <label for="trimestreFormativo" class="form-label fw-bold">Trimestre:</label>
                            <select class="form-select" id="trimestreFormativo" disabled>
                                <option value="">Seleccionar trimestre</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4 d-none" id="divCamposFormativos">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.js"></script>

    <!-- Scripts para manejar la carga dinámica de boletas -->
    <script>
        // Variables para los elementos del DOM
        const yearSelect = document.getElementById('cicloFormativo');
        const quarterSelect = document.getElementById('trimestreFormativo');
        const divTrimestreFormativo = document.getElementById('divTrimestreFormativo');
        const divCamposFormativos = document.getElementById('divCamposFormativos');
        let selectedStudentId = '';
        let studentName = '';
        
        // Debug: verificar que todos los elementos fueron encontrados
        console.log('yearSelect:', yearSelect);
        console.log('quarterSelect:', quarterSelect);
        console.log('divTrimestreFormativo:', divTrimestreFormativo);
        console.log('divCamposFormativos:', divCamposFormativos);
        
        // Debug adicional: verificar Bootstrap
        if (divTrimestreFormativo) {
            console.log('Estado inicial divTrimestreFormativo:');
            console.log('- classList:', divTrimestreFormativo.classList.toString());
            console.log('- style.display:', divTrimestreFormativo.style.display);
            console.log('- offsetHeight:', divTrimestreFormativo.offsetHeight);
            console.log('- computedStyle display:', window.getComputedStyle(divTrimestreFormativo).display);
        }
        
        // Event listener para el año escolar
        yearSelect.addEventListener('change', function() {
            const idSchoolYear = this.value;
            console.log(`Año escolar seleccionado: ${idSchoolYear}`);
            quarterSelect.innerHTML = '<option value="" selected>Seleccionar trimestre</option>';
            
            if (!idSchoolYear) {
                console.log('No se seleccionó año escolar, ocultando trimestres');
                divTrimestreFormativo.classList.add('d-none');
                divCamposFormativos.classList.add('d-none');
                return;
            }
            
            console.log(`Obteniendo trimestres para el año ${idSchoolYear}`);
            fetch(`get_quarters.php?idSchoolYear=${idSchoolYear}`)
                .then(response => {
                    console.log(`Respuesta del servidor: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    console.log('Datos de trimestres recibidos:', data);
                    if (data.success && data.quarters && data.quarters.length > 0) {
                        console.log(`Mostrando div de trimestre y cargando ${data.quarters.length} trimestres`);
                        divTrimestreFormativo.classList.remove('d-none');
                        
                        // Limpiar las opciones anteriores y habilitar el select
                        quarterSelect.innerHTML = '<option value="">Seleccionar trimestre</option>';
                        quarterSelect.disabled = false;
                        console.log('Después de remover d-none - clase actual:', divTrimestreFormativo.className);
                        console.log('Después de remover d-none - display style:', divTrimestreFormativo.style.display);
                        console.log('Después de remover d-none - offsetHeight:', divTrimestreFormativo.offsetHeight);
                        console.log('Después de remover d-none - computedStyle display:', window.getComputedStyle(divTrimestreFormativo).display);
                        console.log('Elemento visible?:', !divTrimestreFormativo.classList.contains('d-none'));
                        data.quarters.forEach(q => {
                            const option = document.createElement('option');
                            option.value = q.idSchoolQuarter;
                            option.textContent = q.name;
                            quarterSelect.appendChild(option);
                        });
                    } else {
                        console.log('No se encontraron trimestres, mostrando mensaje de error');
                        quarterSelect.innerHTML = '<option value="" selected>No hay trimestres disponibles</option>';
                        divTrimestreFormativo.classList.remove('d-none'); // Mostrar aunque sea para el mensaje
                    }
                })
                .catch(error => {
                    console.error('Error al cargar trimestres:', error);
                    quarterSelect.innerHTML = '<option value="" selected>Error al cargar trimestres</option>';
                    divTrimestreFormativo.classList.remove('d-none'); // Mostrar aunque sea para el mensaje
                });
        });

        // Event listener para el trimestre
        quarterSelect.addEventListener('change', function() {
            if (this.value && yearSelect.value && selectedStudentId) {
                divCamposFormativos.classList.remove('d-none');
                loadStudentGrades(selectedStudentId, yearSelect.value, this.value);
            } else {
                divCamposFormativos.classList.add('d-none');
            }
        });

        // Función para obtener las calificaciones del estudiante
        function loadStudentGrades(studentId, schoolYearId, quarterId) {
            const gradesList = document.getElementById('gradesList');
            const loadingIndicator = document.getElementById('loadingGrades');
            
            // Mostrar indicador de carga y limpiar lista
            loadingIndicator.classList.remove('d-none');
            gradesList.innerHTML = '';
            
            console.log(`Cargando calificaciones para estudiante ID=${studentId}, año=${schoolYearId}, trimestre=${quarterId}`);
            
            // Obtener las materias asignadas al estudiante
            fetch(`get_student_subjects.php?idStudent=${studentId}&idSchoolYear=${schoolYearId}&idSchoolQuarter=${quarterId}`)
                .then(response => {
                    console.log(`Respuesta del servidor para materias: ${response.status} ${response.statusText}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                    }
                    return response.text(); // Primero obtenemos como texto para ver qué recibimos
                })
                .then(text => {
                    console.log('Respuesta raw del servidor:', text);
                    try {
                        return JSON.parse(text); // Luego intentamos parsearlo como JSON
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        console.error('Texto recibido:', text.substring(0, 500)); // Mostrar primeros 500 caracteres
                        throw new Error('La respuesta del servidor no es JSON válido');
                    }
                })
                .then(subjectsData => {
                    console.log('Respuesta de get_subjects:', subjectsData);
                    
                    if (!subjectsData.success) {
                        throw new Error(subjectsData.message || 'Error al obtener materias');
                    }
                    
                    if (!subjectsData.subjects || subjectsData.subjects.length === 0) {
                        console.log('No hay materias asignadas para este estudiante');
                        loadingIndicator.classList.add('d-none');
                        gradesList.innerHTML = '<div class="alert alert-info">No hay materias con calificaciones para este estudiante en el período seleccionado</div>';
                        return null;
                    }

                    console.log(`Encontradas ${subjectsData.subjects.length} materias para el estudiante`);

                    // Para cada materia, obtener sus promedios
                    const promises = subjectsData.subjects.map(subject => 
                        fetch(`getAveragesBySubject.php?idSubject=${subject.idSubject}&idSchoolYear=${schoolYearId}&idSchoolQuarter=${quarterId}&idStudent=${studentId}`)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log(`Respuesta para materia ${subject.name}:`, data);
                                return {
                                    ...data, 
                                    subjectName: subject.name, 
                                    idSubject: subject.idSubject,
                                    idLearningArea: subject.idLearningArea,
                                    learningAreaName: subject.learningAreaName
                                };
                            })
                    );

                    return Promise.all(promises);
                })
                .then(results => {
                    if (!results) return null;

                    console.log('Resultados obtenidos:', results);
                    
                    let allGrades = [];
                    results.forEach(data => {
                        if (data.success && data.students) {
                            // Filtrar para encontrar solo el estudiante solicitado
                            const studentData = data.students.find(s => s.idStudent == studentId);
                            if (studentData) {
                                const subjectData = {
                                    name: data.subjectName,
                                    idSubject: data.idSubject,
                                    grade: studentData.average || '0',
                                    idLearningArea: data.idLearningArea,
                                    learningAreaName: data.learningAreaName
                                };
                                allGrades.push(subjectData);
                            } else {
                                // Si no hay datos para este estudiante, agregar con calificación 0
                                const subjectData = {
                                    name: data.subjectName,
                                    idSubject: data.idSubject,
                                    grade: '0',
                                    idLearningArea: data.idLearningArea,
                                    learningAreaName: data.learningAreaName
                                };
                                allGrades.push(subjectData);
                            }
                        } else {
                            // Si hay error en esta materia, agregar con calificación 0
                            const subjectData = {
                                name: data.subjectName || 'Materia desconocida',
                                idSubject: data.idSubject || 0,
                                grade: '0',
                                idLearningArea: data.idLearningArea || 0,
                                learningAreaName: data.learningAreaName || 'Área desconocida'
                            };
                            allGrades.push(subjectData);
                        }
                    });

                    console.log('Calificaciones procesadas:', allGrades);
                    
                    loadingIndicator.classList.add('d-none');
                    
                    if (allGrades.length === 0) {
                        gradesList.innerHTML = '<div class="alert alert-info">No hay calificaciones disponibles para este período</div>';
                        return null;
                    }
                    
                    displayGrades(allGrades);
                })
                .catch(error => {
                    console.error('Error al cargar calificaciones:', error);
                    loadingIndicator.classList.add('d-none');
                    gradesList.innerHTML = `<div class="alert alert-danger">Error al cargar calificaciones: ${error.message}</div>`;
                });
        }
        
        // Función para mostrar las calificaciones en el modal
        // Función para redondear hacia arriba con 1 decimal (igual que saveGrades.php)
        function ceilToOneDecimal(value) {
            return Math.ceil(value * 10) / 10;
        }

        function displayGrades(grades) {
            const gradesList = document.getElementById('gradesList');
            gradesList.innerHTML = '';
            
            console.log('Mostrando calificaciones:', grades);
            
            // Agrupar por áreas de aprendizaje
            const learningAreas = {};
            
            grades.forEach(subject => {
                const areaId = subject.idLearningArea;
                if (!learningAreas[areaId]) {
                    learningAreas[areaId] = {
                        name: subject.learningAreaName,
                        subjects: [],
                        totalGrade: 0,
                        subjectCount: 0,
                        average: 0
                    };
                }
                
                const grade = parseFloat(subject.grade) || 0;
                learningAreas[areaId].subjects.push({
                    name: subject.name,
                    average: grade
                });
                learningAreas[areaId].totalGrade += grade;
                learningAreas[areaId].subjectCount++;
            });
            
            // Calcular promedio por área
            for (const areaId in learningAreas) {
                const area = learningAreas[areaId];
                area.average = area.subjectCount > 0 ? ceilToOneDecimal(area.totalGrade / area.subjectCount) : 0;
            }
            
            console.log('Áreas de aprendizaje agrupadas:', learningAreas);
            
            // Calcular promedio general considerando TODAS las áreas de aprendizaje
            const areaAverages = Object.values(learningAreas).map(area => area.average);
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
            
            Object.values(learningAreas).forEach(area => {
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
        }

        // Configurar el modal de boletas
        const modalCamposFormativos = document.getElementById('modalCamposFormativos');
        
        // Guardar el ID y nombre del estudiante cuando se abra el modal
        modalCamposFormativos.addEventListener('show.bs.modal', function(event) {
            console.log('Modal abierto - Debug inicial');
            console.log('divTrimestreFormativo tiene clase d-none:', divTrimestreFormativo.classList.contains('d-none'));
            console.log('divTrimestreFormativo display style:', divTrimestreFormativo.style.display);
            
            const button = event.relatedTarget;
            
            // Guardar datos del estudiante
            selectedStudentId = button.getAttribute('data-id');
            const nombres = button.getAttribute('data-nombres') || '';
            const paterno = button.getAttribute('data-paterno') || '';
            const materno = button.getAttribute('data-materno') || '';
            studentName = `${nombres} ${paterno} ${materno}`.trim();
            
            console.log(`Modal abierto para estudiante: ${studentName}, ID: ${selectedStudentId}`);
            
            // Actualizar el título del modal con el nombre del estudiante
            const modalTitle = modalCamposFormativos.querySelector('.modal-title');
            modalTitle.textContent = `Boleta de ${studentName}`;
            
            // Añadir información del estudiante al modal
            const gradeInfo = button.getAttribute('data-grade') || '';
            const groupInfo = button.getAttribute('data-grupo') || '';
            
            // Actualizar la información del estudiante en el área designada
            /*const studentInfoDiv = document.getElementById('studentInfo');
            studentInfoDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><strong>${studentName}</strong></h5>
                         Grupo: <strong>${groupInfo}</strong></p>
                    </div>

                </div>
            `;*/
        });
        
        // Reiniciar el modal cuando se cierre
        modalCamposFormativos.addEventListener('hidden.bs.modal', function() {
            // Restablecer los selectores
            document.getElementById('cicloFormativo').selectedIndex = 0;
            document.getElementById('trimestreFormativo').selectedIndex = 0;
            document.getElementById('divTrimestreFormativo').classList.add('d-none');
            document.getElementById('divCamposFormativos').classList.add('d-none');
            document.getElementById('gradesList').innerHTML = '';
            
            // Limpiar variables
            selectedStudentId = '';
            studentName = '';
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

            const schoolYearId = document.getElementById('cicloFormativo').value;
            const quarterId = document.getElementById('trimestreFormativo').value;
            
            if (schoolYearId && quarterId && selectedStudentId) {
                // Generar URL del PDF con los parámetros necesarios (usando el archivo local de admin)
                const pdfUrl = `generate_boleta_pdf.php?idStudent=${selectedStudentId}&idSchoolYear=${schoolYearId}&idSchoolQuarter=${quarterId}`;
                
                // Abrir el PDF en una nueva ventana
                window.open(pdfUrl, '_blank');
            } else {
                alert('Por favor, seleccione un año escolar y un trimestre para ver los detalles.');
            }
        });
    </script>
       
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
    </script>
    <?php
    if (isset($_GET['status'])) {
        $icon = 'success';
        $title = '';

        if ($_GET['status'] == 1 || $_GET['status'] == 'success') {
            $title = "Estudiante agregado correctamente";
        } else if ($_GET['status'] == 2) {
            $title = "Estudiante actualizado correctamente";
        } else if ($_GET['status'] == 3) {
            $title = "Estudiante eliminado correctamente";
        } else if ($_GET['status'] == 'error') {
            $icon = 'error';
            $title = isset($_GET['message']) ? $_GET['message'] : "Error al procesar la solicitud";
        } else if ($_GET['status'] == 0) {
            $icon = 'error';
            $title = "Favor de completar los datos correctamente";
        }

        if ($title) {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        icon: '<?php echo $icon; ?>',
                        title: "<?php echo $title; ?>",
                        confirmButtonText: 'Aceptar'
                    }).then(function () {
                        // Quitar status y message de la URL usando la API de historial
                        const url = new URL(window.location.href);
                        url.searchParams.delete('status');
                        url.searchParams.delete('message');
                        window.history.replaceState({}, document.title, url.pathname + url.search);
                    });
                });
            </script>
            <?php
        }
    }
    ?>
    <script>
        // Función para buscar en la tabla
        function searchTable() {
            const searchInput = document.getElementById('alumno');
            const searchText = searchInput.value.toLowerCase();
            const table = document.querySelector('.table');
            const rows = Array.from(table.getElementsByTagName('tr')).slice(1); // Ignorar encabezado
            const yearSelect = document.getElementById('schoolYear');
            const selectedYear = yearSelect ? yearSelect.value : '';
            const groupSelect = document.getElementById('grupo');
            const selectedGroup = groupSelect ? groupSelect.value : '';
            // Ordenar filas según coincidencia
            rows.sort((a, b) => {
                const textA = a.textContent.toLowerCase();
                const textB = b.textContent.toLowerCase();
                if (!searchText) return 0;
                const matchA = textA.includes(searchText);
                const matchB = textB.includes(searchText);
                if (matchA && !matchB) return -1;
                if (!matchA && matchB) return 1;
                const indexA = textA.indexOf(searchText);
                const indexB = textB.indexOf(searchText);
                if (indexA === -1 && indexB === -1) return 0;
                if (indexA === -1) return 1;
                if (indexB === -1) return -1;
                return indexA - indexB;
            });
            // Ocultar filas que no coinciden y resaltar
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                let shouldShow = text.includes(searchText);
                // Filtrar por año escolar
                if (selectedYear) {
                    shouldShow = shouldShow && row.dataset.schoolyear === selectedYear;
                }
                // Filtrar por grupo
                if (selectedGroup) {
                    shouldShow = shouldShow && row.dataset.grupo === selectedGroup;
                }
                row.style.display = shouldShow ? '' : 'none';
                // Limpiar resaltado si el input está vacío
                const cells = row.getElementsByTagName('td');
                if (!searchText) {
                    Array.from(cells).forEach(cell => {
                        if (cell.hasAttribute('data-original-html')) {
                            cell.innerHTML = cell.getAttribute('data-original-html');
                        }
                    });
                }
                // Resaltar texto coincidente
                if (shouldShow && searchText) {
                    Array.from(cells).forEach(cell => {
                        const originalText = cell.textContent;
                        const lowerText = originalText.toLowerCase();
                        const index = lowerText.indexOf(searchText);
                        if (index !== -1) {
                            const before = originalText.substring(0, index);
                            const match = originalText.substring(index, index + searchText.length);
                            const after = originalText.substring(index + searchText.length);
                            cell.innerHTML = `${before}<mark>${match}</mark>${after}`;
                        }
                    });
                }
            });
            // Reordenar filas en la tabla
            const tbody = table.getElementsByTagName('tbody')[0];
            rows.forEach(row => tbody.appendChild(row));
        }
        // Event listener para la búsqueda
        document.addEventListener('DOMContentLoaded', function() {
            // Guardar HTML original de cada celda al cargar la página
            document.querySelectorAll('.table tbody tr').forEach(row => {
                Array.from(row.getElementsByTagName('td')).forEach(cell => {
                    cell.setAttribute('data-original-html', cell.innerHTML);
                });
            });
            const searchInput = document.getElementById('alumno');
            const searchButton = document.getElementById('iBuscar');
            if (searchInput) {
                // Buscar al escribir
                searchInput.addEventListener('input', function() {
                    searchTable();
                    if (this.value === '') {
                        // Si se borra todo, restaurar HTML original
                        document.querySelectorAll('.table tbody tr').forEach(row => {
                            Array.from(row.getElementsByTagName('td')).forEach(cell => {
                                if (cell.hasAttribute('data-original-html')) {
                                    cell.innerHTML = cell.getAttribute('data-original-html');
                                }
                            });
                        });
                    }
                });
                // Buscar al presionar Enter
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchTable();
                    }
                });
            }
            if (searchButton) {
                searchButton.addEventListener('click', searchTable);
            }
            // Event listener para el selector de año escolar
            const yearSelect = document.getElementById('schoolYear');
            const groupSelect = document.getElementById('grupo');
            const btnDescargarGrupo = document.getElementById('btnDescargarGrupo');
            
            // Función para verificar si mostrar el botón de descarga
            function checkDownloadButton() {
                if (yearSelect && groupSelect && btnDescargarGrupo) {
                    const yearSelected = yearSelect.value;
                    const groupSelected = groupSelect.value;
                    
                    if (yearSelected && groupSelected) {
                        btnDescargarGrupo.classList.remove('d-none');
                    } else {
                        btnDescargarGrupo.classList.add('d-none');
                    }
                }
            }
            
            if (yearSelect) {
                yearSelect.addEventListener('change', function() {
                    searchTable(); // Filtra en vivo
                    checkDownloadButton(); // Verificar si mostrar botón de descarga
                    const url = new URL(window.location.href);
                    url.searchParams.delete('year');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                });
            }
            // Event listener para el selector de grupo
            if (groupSelect) {
                groupSelect.addEventListener('change', function() {
                    searchTable(); // Filtra en vivo
                    checkDownloadButton(); // Verificar si mostrar botón de descarga
                    const url = new URL(window.location.href);
                    url.searchParams.delete('group');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                });
            }
            
            // Event listener para el botón de descarga grupal
            if (btnDescargarGrupo) {
                btnDescargarGrupo.addEventListener('click', function() {
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

                    const yearSelected = yearSelect.value;
                    const groupSelected = groupSelect.value;
                    
                    if (!yearSelected || !groupSelected) {
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
                            descargarPDFsGrupo(yearSelected, groupSelected);
                        }
                    });
                });
            }

            // Función para descargar PDFs del grupo
            function descargarPDFsGrupo(yearSelected, groupSelected) {
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
                    body: `idSchoolYear=${yearSelected}&idGroup=${groupSelected}`
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
                    a.download = `Boletas_Grupo_${yearSelected}_${groupSelected}.zip`;
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
    </script>

    <script>
        // Selecciona todos los botones "ver" de estudiantes
        const botonesVer = document.querySelectorAll('.btn-ver');

        botonesVer.forEach(boton => {
            boton.addEventListener('click', function() {
                const modalElement = document.getElementById('showModal');
                if (!modalElement) {
                    return;
                }

                // Obtener datos del botón (alumno)
                const data = {
                    id: this.getAttribute('data-id'),
                    nombres: this.getAttribute('data-nombres'),
                    paterno: this.getAttribute('data-paterno'),
                    materno: this.getAttribute('data-materno'),
                    status: this.getAttribute('data-status'),
                    grupo: this.getAttribute('data-grupo'),
                    schoolYear: this.getAttribute('data-schoolyear'),
                    genero: this.getAttribute('data-genero'),
                    direccion: this.getAttribute('data-direccion'),
                    telefono: this.getAttribute('data-telefono'),
                    username: this.getAttribute('data-username'),
                    email: this.getAttribute('data-email'),
                    curp: this.getAttribute('data-curp'),
                    grado: this.getAttribute('data-grado')
                };

                // Obtener datos del tutor
                const tutorData = {
                    nombres: this.getAttribute('data-tutornombres'),
                    paterno: this.getAttribute('data-tutorpaterno'),
                    materno: this.getAttribute('data-tutormaterno'),
                    ine: this.getAttribute('data-tutorine'),
                    telefono: this.getAttribute('data-tutortelefono'),
                    email: this.getAttribute('data-tutoremail'),
                    direccion: this.getAttribute('data-tutordireccion'),
                    parentesco: this.getAttribute('data-tutorparentesco')
                };

                // Llenar el modal show (alumno)
                if(document.getElementById('show_id')) document.getElementById('show_id').textContent = data.id || 'No especificado';
                if(document.getElementById('show_nombres')) document.getElementById('show_nombres').textContent = data.nombres || 'No especificado';
                if(document.getElementById('show_paterno')) document.getElementById('show_paterno').textContent = data.paterno || 'No especificado';
                if(document.getElementById('show_materno')) document.getElementById('show_materno').textContent = data.materno || 'No especificado';
                
                // Manejar el estado con badge
                const statusElement = document.getElementById('show_status');
                if (statusElement) {
                    if (data.status === 'Activo') {
                        statusElement.innerHTML = '<span class="badge bg-success">Activo</span>';
                    } else if (data.status === 'Inactivo') {
                        statusElement.innerHTML = '<span class="badge bg-danger">Inactivo</span>';
                    } else {
                        statusElement.innerHTML = '<span class="badge bg-secondary">' + (data.status || 'No especificado') + '</span>';
                    }
                }
                
                if(document.getElementById('show_grupo')) document.getElementById('show_grupo').textContent = data.grupo || 'No asignado';
                if(document.getElementById('show_schoolYear')) document.getElementById('show_schoolYear').textContent = data.schoolYear || 'No especificado';
                if(document.getElementById('show_direccion')) document.getElementById('show_direccion').textContent = data.direccion || 'No especificado';
                if(document.getElementById('show_telefono')) document.getElementById('show_telefono').textContent = data.telefono || 'No especificado';
                if(document.getElementById('show_username')) document.getElementById('show_username').textContent = data.username || 'No especificado';
                if(document.getElementById('show_email')) document.getElementById('show_email').textContent = data.email || 'No especificado';
                if(document.getElementById('show_curp')) document.getElementById('show_curp').textContent = data.curp || 'No especificado';
                
                // Convertir género de letra a texto completo
                const generoElement = document.getElementById('show_genero');
                if (generoElement) {
                    let generoTexto = 'No especificado';
                    if (data.genero === 'M') {
                        generoTexto = 'Masculino';
                    } else if (data.genero === 'F') {
                        generoTexto = 'Femenino';
                    }
                    generoElement.textContent = generoTexto;
                }

                // Llenar el modal show (tutor)
                if(document.getElementById('show_tutorNombres')) document.getElementById('show_tutorNombres').textContent = tutorData.nombres || 'No registrado';
                if(document.getElementById('show_tutorApellidos')) document.getElementById('show_tutorApellidos').textContent = `${tutorData.paterno || ''} ${tutorData.materno || ''}`.trim() || 'No registrado';
                if(document.getElementById('show_tutorIne')) document.getElementById('show_tutorIne').textContent = tutorData.ine || 'No registrado';
                if(document.getElementById('show_tutorPhone')) document.getElementById('show_tutorPhone').textContent = tutorData.telefono || 'No registrado';
                if(document.getElementById('show_tutorEmail')) document.getElementById('show_tutorEmail').textContent = tutorData.email || 'No registrado';
                if(document.getElementById('show_tutorAddress')) document.getElementById('show_tutorAddress').textContent = tutorData.direccion || 'No registrado';
                if(document.getElementById('show_tutorParentesco')) document.getElementById('show_tutorParentesco').textContent = tutorData.parentesco || 'No registrado';

                // Mostrar el modal después de llenar los datos
                var modal = new bootstrap.Modal(modalElement);
                modal.show();
            });
        });
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#btnEditar, .btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            // Ocultar el modal de visualización si está abierto
            const showModal = document.getElementById('showModal');
            if (showModal && showModal.classList.contains('show')) {
                var showModalInstance = bootstrap.Modal.getInstance(showModal);
                if (showModalInstance) showModalInstance.hide();
            }

            const editModal = document.getElementById('editModal');
            if (!editModal) {
                return;
            }

            // Obtener los valores de los atributos data
            const grupoId = this.getAttribute('data-grupo');
            const schoolYearId = this.getAttribute('data-schoolyear');

            // Establecer los valores en los select
            const selectGrupo = editModal.querySelector('#editGroup');
            const selectSchoolYear = editModal.querySelector('#editSchoolYear');

            if (selectGrupo) {
                selectGrupo.value = grupoId;
            }

            if (selectSchoolYear) {
                selectSchoolYear.value = schoolYearId;
            }

            // Llenar el resto de los campos
            if(editModal.querySelector('#studentId')) editModal.querySelector('#studentId').value = this.getAttribute('data-id') || '';
            if(editModal.querySelector('#editName')) editModal.querySelector('#editName').value = this.getAttribute('data-nombres') || '';
            if(editModal.querySelector('#editLastnamePa')) editModal.querySelector('#editLastnamePa').value = this.getAttribute('data-paterno') || '';
            if(editModal.querySelector('#editLastnameMa')) editModal.querySelector('#editLastnameMa').value = this.getAttribute('data-materno') || '';
            if(editModal.querySelector('#editCurp')) editModal.querySelector('#editCurp').value = this.getAttribute('data-curp') || '';
            if(editModal.querySelector('#editGender')) editModal.querySelector('#editGender').value = this.getAttribute('data-genero') || '';
            if(editModal.querySelector('#editAddress')) editModal.querySelector('#editAddress').value = this.getAttribute('data-direccion') || '';
            if(editModal.querySelector('#editEmail')) editModal.querySelector('#editEmail').value = this.getAttribute('data-email') || '';
            if(editModal.querySelector('#editPhone')) editModal.querySelector('#editPhone').value = this.getAttribute('data-telefono') || '';
            if(editModal.querySelector('#editStatus')) editModal.querySelector('#editStatus').value = this.getAttribute('data-status') || '1';

            // Llenar campos del tutor
            if(editModal.querySelector('#editTutorName')) editModal.querySelector('#editTutorName').value = this.getAttribute('data-tutornombres') || '';
            if(editModal.querySelector('#editTutorLastnamePa')) editModal.querySelector('#editTutorLastnamePa').value = this.getAttribute('data-tutorpaterno') || '';
            if(editModal.querySelector('#editTutorLastnameMa')) editModal.querySelector('#editTutorLastnameMa').value = this.getAttribute('data-tutormaterno') || '';
            if(editModal.querySelector('#editTutorIne')) editModal.querySelector('#editTutorIne').value = this.getAttribute('data-tutorine') || '';
            if(editModal.querySelector('#editTutorPhone')) editModal.querySelector('#editTutorPhone').value = this.getAttribute('data-tutortelefono') || '';
            if(editModal.querySelector('#editTutorEmail')) editModal.querySelector('#editTutorEmail').value = this.getAttribute('data-tutoremail') || '';
            if(editModal.querySelector('#editTutorAddress')) editModal.querySelector('#editTutorAddress').value = this.getAttribute('data-tutordireccion') || '';
            if(editModal.querySelector('#editTutorRelative')) editModal.querySelector('#editTutorRelative').value = this.getAttribute('data-tutorparentesco') || '';

            var modal = new bootstrap.Modal(editModal);
            modal.show();
        });
    });
});
</script>

<script>
document.getElementById('formEditStudent').addEventListener('submit', function(e) {
                e.preventDefault();
                
    // Validar formulario
                if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
                    return;
                }
                
                // Mostrar carga
                Swal.fire({
        title: 'Actualizando datos',
                    html: 'Por favor espere...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

    // Enviar datos por AJAX
    const formData = new FormData(this);
    fetch('updateStudent.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Si la respuesta no es JSON válida, mostrar error
        return response.json().catch(() => ({success: false, message: 'Respuesta inesperada del servidor'}));
    })
    .then(data => {
                        Swal.close();
        if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Éxito!',
                text: data.message,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                text: data.message || 'Ocurrió un error al procesar la solicitud'
                                });
                            }
    })
    .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
            text: 'Ocurrió un error al procesar la solicitud'
                            });
                        });
        });
    </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variable para almacenar el ID del estudiante a eliminar
    let studentIdToDelete = null;

    // Cuando se abre el modal de eliminación, guardar el ID del estudiante
    document.querySelectorAll('.btn-ver').forEach(btn => {
        btn.addEventListener('click', function() {
            studentIdToDelete = this.getAttribute('data-id');
        });
    });

    // Manejar el clic en el botón de eliminar final
    document.querySelector('#eliminar').addEventListener('click', function() {
        if (!studentIdToDelete) {
            return;
        }

        // Mostrar indicador de carga
        Swal.fire({
            title: 'Eliminando estudiante',
            html: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Realizar la petición AJAX para eliminar
        fetch('deleteStudent.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'studentId=' + encodeURIComponent(studentIdToDelete)
        })
        .then(response => response.json())
        .then(data => {
            // Cerrar todos los modales
            var modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                var modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'El estudiante ha sido eliminado correctamente',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    // Recargar la página
                    location.reload();
                });
            } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                    text: data.message || 'Ocurrió un error al eliminar el estudiante'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al procesar la solicitud'
            });
                });
            });
        });
    </script>

    <script src="../js/students.js"></script>

    <!-- Script duplicado eliminado para evitar conflictos de variables -->
    <!-- El script de boletas está implementado arriba -->
</body>
</html>
