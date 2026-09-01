<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "check_session.php";
require_once "../force_password_check.php";
require_once '../conection.php';

// Consulta principal para obtener los datos de los profesores
$sql = "SELECT 
    t.idTeacher,
    t.profesionalID,
    t.ine,
    t.typeTeacher,
    t.idTeacherStatus,
    ui.names,
    ui.lastnamePa,
    ui.lastnameMa,
    ui.gender,
    ui.phone,
    ui.email,
    ui.street,
    ts.description AS status,
    u.username,
    u.password,
    u.raw_password,
    GROUP_CONCAT(DISTINCT CONCAT(g.grade, '°', g.group_) ORDER BY g.grade, g.group_ SEPARATOR ', ') AS grupos,
    GROUP_CONCAT(DISTINCT CONCAT(tgs.idDFM, ':', s.idSubject, ':', s.name) ORDER BY s.name SEPARATOR '|||') AS materias_data
FROM teachers t
INNER JOIN users u ON t.idUser = u.idUser
INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
INNER JOIN teacherStatus ts ON t.idTeacherStatus = ts.idTeacherStatus
LEFT JOIN teacherGroupsSubjects tgs ON t.idTeacher = tgs.idTeacher
LEFT JOIN groups g ON tgs.idGroup = g.idGroup
LEFT JOIN subjects s ON tgs.idSubject = s.idSubject
GROUP BY t.idTeacher";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error en la consulta SQL: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <title>Maestros</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css?v=5">
    <link rel="stylesheet" href="../css/components.css?v=5">
    <link rel="stylesheet" href="../css/layout.css?v=5">
    <link rel="stylesheet" href="../css/styles.css?v=5">
    <link rel="stylesheet" href="../css/admin/time.css?v=5">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../css/admin/teacher.css?v=5">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    
    <!-- TIPOGRAFIA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="page-teachers">
    <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>
    <!-- ASIDEBAR -->
    <?php include "../layouts/aside.php"; ?>
    <!-- END ASIDEBAR -->
    
    <!-- MAIN CONTENT -->
    <main class="ds-main">
        <?php include "../layouts/header.php"; ?>
        
        <!-- Header de la página -->
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-person-workspace me-3"></i>
                    Gestión de Docentes
                </h1>
                <p class="page-subtitle">
                    Administra la información de los profesores del sistema
                </p>
            </div>

        <!-- Contenido principal -->
            <!-- Panel de filtros -->
            <div class="tch-filters">
                <div class="tch-filter">
                    <label for="docente" class="tch-filter__label">
                        Buscar por nombre
                    </label>
                    <input type="text" class="tch-filter__input" id="docente" placeholder="Buscar docente...">
                </div>
                
                <div class="tch-actions">
                    <button class="tch-btn tch-btn--primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-2"></i>
                        Agregar Docente
                    </button>
                </div>
            </div>

            <!-- Tabla de docentes -->
            <div class="tch-table-wrap">
                <div class="tch-table-header">
                    <h5 class="tch-table-title">
                        <i class="bi bi-list-check me-2"></i>
                        Docentes Registrados
                    </h5>
                </div>
                <div class="tch-table-responsive">
                    <table class="tch-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Apellido Paterno</th>
                                <th>Apellido Materno</th>
                                <th>Nombre(s)</th>
                                <th>Estado</th>
                                <th>Grupo</th>
                                <th>Materia</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="teachersBody">
                                            <?php
                                            if ($resultado && $resultado->num_rows > 0) {
                                                while($fila = $resultado->fetch_assoc()){
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fila['idTeacher']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['lastnamePa']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['lastnameMa']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['names']); ?></td>
                                                <td>
                                                    <?php
                                                    if ($fila['status'] == 'Activo') {
                                                        echo '<span class="tch-badge tch-badge--active">' . htmlspecialchars($fila['status']) . '</span>';
                                                    } elseif ($fila['status'] == 'Inactivo') {
                                                        echo '<span class="tch-badge tch-badge--inactive">' . htmlspecialchars($fila['status']) . '</span>';
                                                    } else {
                                                        echo '<span class="tch-badge tch-badge--info">' . htmlspecialchars($fila['status']) . '</span>'; 
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $grupos = $fila['grupos'];
                                                    if (!empty($grupos)) {
                                                        $gruposArray = explode(',', $grupos);
                                                        foreach ($gruposArray as $grupo) {
                                                            if (!empty(trim($grupo))) {
                                                                echo '<span class="tch-badge tch-badge--institutional me-1">' . htmlspecialchars(trim($grupo)) . '</span>';
                                                            }
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">Sin asignaciones</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $materiasData = $fila['materias_data'];
                                                    if (!empty($materiasData)) {
                                                        $materiasArray = explode('|||', $materiasData);
                                                        foreach ($materiasArray as $materiaStr) {
                                                            if (empty(trim($materiaStr))) continue;
                                                            list($idDFM, $idSubject, $materiaName) = explode(':', $materiaStr);
                                                            $idDFM = intval($idDFM);
                                                            $idSubject = intval($idSubject);
                                                            $materiaName = htmlspecialchars(trim($materiaName));
                                                            echo '<span class="tch-badge tch-badge--institutional me-1 mb-1 tch-badge--clickable" '
                                                                . 'data-iddfm="' . $idDFM . '" '
                                                                . 'data-idsubject="' . $idSubject . '" '
                                                                . 'data-subjectname="' . $materiaName . '" '
                                                                . 'data-idteacher="' . $fila['idTeacher'] . '" '
                                                                . 'data-teachername="' . htmlspecialchars(trim($fila['names'] . ' ' . $fila['lastnamePa'])) . '" '
                                                                . 'title="Editar materia: ' . $materiaName . '">'
                                                                . $materiaName
                                                                . ' <i class="bi bi-pencil-fill tch-badge__edit"></i>'
                                                                . '</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">Sin materias</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <div style="display: flex; gap: 4px; justify-content: center;">
                                                        <button class="tch-btn tch-btn--sm btn-ver" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#showModal" 
                                                            data-id="<?php echo $fila['idTeacher']; ?>"
                                                            data-nombres="<?php echo htmlspecialchars($fila['names']); ?>"
                                                            data-paterno="<?php echo htmlspecialchars($fila['lastnamePa']); ?>"
                                                            data-materno="<?php echo htmlspecialchars($fila['lastnameMa']); ?>"
                                                            data-status="<?php echo htmlspecialchars($fila['status']); ?>"
                                                            data-grupos="<?php echo htmlspecialchars($fila['grupos'] ?? ''); ?>"
                                                            data-materias="<?php echo htmlspecialchars($fila['materias_data'] ?? ''); ?>"
                                                            data-ine="<?php echo htmlspecialchars($fila['ine'] ?? ''); ?>"
                                                            data-cedula="<?php echo htmlspecialchars($fila['profesionalID'] ?? ''); ?>"
                                                            data-telefono="<?php echo htmlspecialchars($fila['phone'] ?? ''); ?>"
                                                            data-tipo="<?php echo htmlspecialchars($fila['typeTeacher'] ?? ''); ?>"
                                                            data-genero="<?php echo htmlspecialchars($fila['gender'] ?? ''); ?>"
                                                            data-email="<?php echo htmlspecialchars($fila['email'] ?? ''); ?>"
                                                            data-direccion="<?php echo htmlspecialchars($fila['street'] ?? ''); ?>"
                                                            data-username="<?php echo htmlspecialchars($fila['username'] ?? ''); ?>"
                                                            data-password="<?php echo htmlspecialchars($fila['raw_password'] ?? ''); ?>"
                                                            title="Ver detalles">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>
                                                        <button class="tch-btn tch-btn--sm btn-outline-warning btn-editar" 
                                                            data-id="<?php echo $fila['idTeacher']; ?>"
                                                            data-nombres="<?php echo htmlspecialchars($fila['names']); ?>"
                                                            data-paterno="<?php echo htmlspecialchars($fila['lastnamePa']); ?>"
                                                            data-materno="<?php echo htmlspecialchars($fila['lastnameMa']); ?>"
                                                            data-status="<?php echo htmlspecialchars($fila['status']); ?>"
                                                            data-ine="<?php echo htmlspecialchars($fila['ine'] ?? ''); ?>"
                                                            data-cedula="<?php echo htmlspecialchars($fila['profesionalID'] ?? ''); ?>"
                                                            data-telefono="<?php echo htmlspecialchars($fila['phone'] ?? ''); ?>"
                                                            data-tipo="<?php echo htmlspecialchars($fila['typeTeacher'] ?? ''); ?>"
                                                            data-genero="<?php echo htmlspecialchars($fila['gender'] ?? ''); ?>"
                                                            data-email="<?php echo htmlspecialchars($fila['email'] ?? ''); ?>"
                                                            data-direccion="<?php echo htmlspecialchars($fila['street'] ?? ''); ?>"
                                                            title="Editar docente">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <button class="tch-btn tch-btn--sm btn-eliminar" 
                                                            data-id="<?php echo $fila['idTeacher']; ?>"
                                                            data-nombres="<?php echo htmlspecialchars($fila['names']); ?>"
                                                            data-paterno="<?php echo htmlspecialchars($fila['lastnamePa']); ?>"
                                                            data-materno="<?php echo htmlspecialchars($fila['lastnameMa']); ?>"
                                                            title="Eliminar docente">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php 
                                                }
                                            } else {
                                                echo '<tr><td colspan="8" class="text-center text-muted py-4">No hay docentes registrados</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
    </main>
    <!-- END MAIN CONTENT --> 

    <!-- Modal para mostrar detalles del docente -->
    <div class="modal fade tch-modal" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="bi bi-person-circle me-2"></i>
                        Información del Docente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Información Personal -->
                        <div class="col-12">
                            <h6>
                                <i class="bi bi-person-badge me-2"></i>
                                Datos Personales
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ID:</label>
                            <p class="form-control-plaintext" id="modal-id">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombres:</label>
                            <p class="form-control-plaintext" id="modal-nombres">-</p>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado:</label>
                            <div class="form-control-plaintext" id="modal-status">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido Paterno:</label>
                            <p class="form-control-plaintext" id="modal-paterno">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido Materno:</label>
                            <p class="form-control-plaintext" id="modal-materno">-</p>
                        </div>
                        
                        <!-- Información de Contacto -->
                        <div class="col-12 mt-4">
                            <h6>
                                <i class="bi bi-telephone me-2"></i>
                                Información de Contacto
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono:</label>
                            <p class="form-control-plaintext" id="modal-telefono">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email:</label>
                            <p class="form-control-plaintext" id="modal-email">-</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección:</label>
                            <p class="form-control-plaintext" id="modal-direccion">-</p>
                        </div>
                        
                        <!-- Información Profesional -->
                        <div class="col-12 mt-4">
                            <h6>
                                <i class="bi bi-briefcase me-2"></i>
                                Información Profesional
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cédula Profesional:</label>
                            <p class="form-control-plaintext" id="modal-cedula">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">INE:</label>
                            <p class="form-control-plaintext" id="modal-ine">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Docente:</label>
                            <p class="form-control-plaintext" id="modal-tipo">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Género:</label>
                            <p class="form-control-plaintext" id="modal-genero">-</p>
                        </div>
                        
                        <!-- Asignaciones -->
                        <div class="col-12 mt-4">
                            <h6>
                                <i class="bi bi-clipboard-check me-2"></i>
                                Asignaciones Actuales
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grupos:</label>
                            <div class="form-control-plaintext" id="modal-grupos">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Materias:</label>
                            <div class="form-control-plaintext" id="modal-materias">-</div>
                        </div>
                        
                        <!-- Información de Usuario -->
                        <div class="col-12 mt-4">
                            <h6>
                                <i class="bi bi-key me-2"></i>
                                Credenciales de Acceso
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario:</label>
                            <p class="form-control-plaintext" id="modal-username">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña:</label>
                            <p class="form-control-plaintext" id="modal-password">-</p>
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

    <!-- Modal para agregar docente -->
    <div class="modal fade tch-modal" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="bi bi-person-plus me-2"></i>
                        Agregar Docente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="addTeacher.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <div class="row g-3">
                            <!-- Información Personal -->
                            <div class="col-12">
                                <h6>
                                    <i class="bi bi-person-badge me-2"></i>
                                    Datos Personales
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="addName" class="form-label">
                                    <i class="bi bi-person me-1"></i>
                                    Nombre(s):
                                </label>
                                <input type="text" class="form-control" id="addName" name="txtName" required>
                                <div class="invalid-feedback">Por favor ingrese el nombre.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addLastnamePa" class="form-label">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Paterno:
                                </label>
                                <input type="text" class="form-control" id="addLastnamePa" name="txtLastnamePa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido paterno.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addLastnameMa" class="form-label">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Materno:
                                </label>
                                <input type="text" class="form-control" id="addLastnameMa" name="txtLastnameMa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido materno.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="addGender" class="form-label">
                                    <i class="bi bi-gender-ambiguous me-1"></i>
                                    Género:
                                </label>
                                <select class="form-select" id="addGender" name="txtGender" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione el género.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="addTypeTeacher" class="form-label">
                                    <i class="bi bi-mortarboard me-1"></i>
                                    Tipo de Docente:
                                </label>
                                <select class="form-select" id="addTypeTeacher" name="txtTypeTeacher" required>
                                    <option value="">Seleccione...</option>
                                    <option value="ME">Maestro Especial</option>
                                    <option value="MS">Maestro de Escolarizado</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione el tipo de docente.</div>
                            </div>

                            <!-- Información Profesional -->
                            <div class="col-12 mt-4">
                                <h6>
                                    <i class="bi bi-briefcase me-2"></i>
                                    Información Profesional
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="addIne" class="form-label">
                                    <i class="bi bi-card-heading me-1"></i>
                                    INE:
                                </label>
                                <input type="text" class="form-control" id="addIne" name="txtIne" required>
                                <div class="invalid-feedback">Por favor ingrese el número de INE.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="addProfesional" class="form-label">
                                    <i class="bi bi-award me-1"></i>
                                    Cédula Profesional:
                                </label>
                                <input type="text" class="form-control" id="addProfesional" name="txtProfesional" required>
                                <div class="invalid-feedback">Por favor ingrese la cédula profesional.</div>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="col-12 mt-4">
                                <h6>
                                    <i class="bi bi-telephone me-2"></i>
                                    Información de Contacto
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="addPhone" class="form-label">
                                    <i class="bi bi-telephone me-1"></i>
                                    Teléfono:
                                </label>
                                <input type="tel" class="form-control" id="addPhone" name="txtPhone" required>
                                <div class="invalid-feedback">Por favor ingrese el teléfono.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addEmail" class="form-label">
                                    <i class="bi bi-envelope me-1"></i>
                                    Email:
                                </label>
                                <input type="email" class="form-control" id="addEmail" name="txtEmail" required>
                                <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addAddress" class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Dirección:
                                </label>
                                <input type="text" class="form-control" id="addAddress" name="txtAddress" required>
                                <div class="invalid-feedback">Por favor ingrese la dirección.</div>
                            </div>
                        </div>

                        <div class="modal-footer mt-4">
                            <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="tch-btn tch-btn--primary">
                                <i class="bi bi-floppy me-1"></i>
                                Guardar Docente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> 

    <!-- Modal para editar docente -->
    <div class="modal fade tch-modal" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTeacherModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Docente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="updateTeacher.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <input type="hidden" id="edit-id" name="teacherId">
                        <div class="row g-3">
                            <!-- Información Personal -->
                            <div class="col-12">
                                <h6><i class="bi bi-person-badge me-2"></i>Datos Personales</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="edit-nombres" class="form-label">Nombre(s):</label>
                                <input type="text" class="form-control" id="edit-nombres" name="txtName" required>
                                <div class="invalid-feedback">Por favor ingrese los nombres.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="edit-apellido-paterno" class="form-label">Apellido Paterno:</label>
                                <input type="text" class="form-control" id="edit-apellido-paterno" name="txtLastnamePa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido paterno.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="edit-apellido-materno" class="form-label">Apellido Materno:</label>
                                <input type="text" class="form-control" id="edit-apellido-materno" name="txtLastnameMa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido materno.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-genero" class="form-label">Género:</label>
                                <select class="form-select" id="edit-genero" name="txtGender" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-tipo-maestro" class="form-label">Tipo de Docente:</label>
                                <select class="form-select" id="edit-tipo-maestro" name="txtTypeTeacher" required>
                                    <option value="">Seleccione...</option>
                                    <option value="ME">Maestro Especial</option>
                                    <option value="MS">Maestro de Escolarizado</option>
                                </select>
                            </div>

                            <!-- Información Profesional -->
                            <div class="col-12 mt-4">
                                <h6><i class="bi bi-briefcase me-2"></i>Información Profesional</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="edit-ine" class="form-label">INE:</label>
                                <input type="text" class="form-control" id="edit-ine" name="txtIne" required>
                            </div>
                            <div class="col-md-4">
                                <label for="edit-cedula" class="form-label">Cédula Profesional:</label>
                                <input type="text" class="form-control" id="edit-cedula" name="txtProfesional" required>
                            </div>
                            <div class="col-md-4">
                                <label for="edit-status" class="form-label">Estado:</label>
                                <select class="form-select" id="edit-status" name="txtStatus" required>
                                    <option value="">Seleccione...</option>
                                    <option value="1">Activo</option>
                                    <option value="2">Inactivo</option>
                                </select>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="col-12 mt-4">
                                <h6><i class="bi bi-telephone me-2"></i>Información de Contacto</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-telefono" class="form-label">Teléfono:</label>
                                <input type="tel" class="form-control" id="edit-telefono" name="txtPhone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-email" class="form-label">Email:</label>
                                <input type="email" class="form-control" id="edit-email" name="txtEmail" required>
                            </div>
                            <div class="col-12">
                                <label for="edit-direccion" class="form-label">Dirección:</label>
                                <input type="text" class="form-control" id="edit-direccion" name="txtAddress" required>
                            </div>
                        </div>
                        <div class="modal-footer mt-4">
                            <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Cancelar
                            </button>
                            <button type="submit" class="tch-btn tch-btn--primary">
                                <i class="bi bi-check-circle me-1"></i> Actualizar Docente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar materia del docente -->
    <div class="modal fade tch-modal" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="bi bi-book me-2"></i>
                        Editar Materia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="tch-modal__field">
                        <label class="tch-modal__label">Docente</label>
                        <div class="tch-modal__readonly" id="edit-teacher-name"></div>
                    </div>
                    <div class="tch-modal__field">
                        <label class="tch-modal__label">Materia actual</label>
                        <div class="tch-modal__readonly" id="edit-current-subject"></div>
                    </div>
                    <div class="tch-modal__field">
                        <label for="edit-new-subject" class="tch-modal__label">Nueva materia</label>
                        <select class="form-select" id="edit-new-subject" required>
                            <option value="">Seleccionar materia</option>
                            <?php
                            $sqlSubjects = "SELECT idSubject, name FROM subjects ORDER BY name";
                            $resultSubjects = $conexion->query($sqlSubjects);
                            while ($subject = $resultSubjects->fetch_assoc()) {
                                echo '<option value="' . $subject['idSubject'] . '">' . htmlspecialchars($subject['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" id="edit-idDFM">
                    <input type="hidden" id="edit-idTeacher">
                    <input type="hidden" id="edit-idSubjectOld">
                    <div id="editSubjectInfo" class="tch-modal__info"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>
                        Cancelar
                    </button>
                    <button type="button" class="tch-btn tch-btn--primary" id="btnSaveSubject">
                        <i class="bi bi-check-circle me-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar docente -->
    <div class="modal fade tch-modal tch-modal--delete" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-person-x display-1 mb-3"></i>
                        <h5>¿Está seguro que desea eliminar este docente?</h5>
                        <p class="text-muted" id="delete-teacher-info">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tch-btn tch-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>
                        Cancelar
                    </button>
                    <button type="button" class="tch-btn tch-btn--danger" id="confirm-delete">
                        <i class="bi bi-trash me-2"></i>
                        Eliminar Docente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Ocultar preloader con animación suave cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                // Dar un pequeño delay para que se vea la animación
                setTimeout(() => {
                    preloader.classList.add('loaded');
                    // Ocultar completamente después de la transición
                    setTimeout(() => {
                        preloader.classList.add('hidden');
                    }, 500);
                }, 100);
            }
            console.log('Página cargada, ocultando preloader...');
        });
        
        // Función de búsqueda simple
        function searchTable() {
            const searchText = document.getElementById('docente').value.toLowerCase();
            const rows = document.querySelectorAll('#teachersBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchText) || searchText === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Asignar evento de búsqueda
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('docente');
            if (searchInput) {
                searchInput.addEventListener('input', searchTable);
            }
            
            // Asignar eventos a los botones de ver detalles
            const botonesVer = document.querySelectorAll('.btn-ver');
            botonesVer.forEach(boton => {
                boton.addEventListener('click', function() {
                    // Obtener todos los datos del botón
                    const data = {
                        id: this.getAttribute('data-id'),
                        nombres: this.getAttribute('data-nombres'),
                        paterno: this.getAttribute('data-paterno'),
                        materno: this.getAttribute('data-materno'),
                        status: this.getAttribute('data-status'),
                        grupos: this.getAttribute('data-grupos'),
                        materias: this.getAttribute('data-materias'),
                        ine: this.getAttribute('data-ine'),
                        cedula: this.getAttribute('data-cedula'),
                        telefono: this.getAttribute('data-telefono'),
                        tipo: this.getAttribute('data-tipo'),
                        genero: this.getAttribute('data-genero'),
                        email: this.getAttribute('data-email'),
                        direccion: this.getAttribute('data-direccion'),
                        username: this.getAttribute('data-username'),
                        password: this.getAttribute('data-password')
                    };
                    
                    // Llenar el modal con los datos
                    document.getElementById('modal-id').textContent = data.id || '-';
                    document.getElementById('modal-nombres').textContent = data.nombres || '-';
                    document.getElementById('modal-paterno').textContent = data.paterno || '-';
                    document.getElementById('modal-materno').textContent = data.materno || '-';
                    document.getElementById('modal-telefono').textContent = data.telefono || '-';
                    document.getElementById('modal-email').textContent = data.email || '-';
                    document.getElementById('modal-direccion').textContent = data.direccion || '-';
                    document.getElementById('modal-cedula').textContent = data.cedula || '-';
                    document.getElementById('modal-ine').textContent = data.ine || '-';
                    document.getElementById('modal-tipo').textContent = data.tipo || '-';
                    document.getElementById('modal-genero').textContent = data.genero || '-';
                    document.getElementById('modal-username').textContent = data.username || '-';
                    document.getElementById('modal-password').textContent = data.password || '-';
                    
                    // Manejar el estado con badge
                    const statusElement = document.getElementById('modal-status');
                    if (data.status === 'Activo') {
                        statusElement.innerHTML = '<span class="tch-badge tch-badge--active">Activo</span>';
                    } else if (data.status === 'Inactivo') {
                        statusElement.innerHTML = '<span class="tch-badge tch-badge--inactive">Inactivo</span>';
                    } else {
                        statusElement.innerHTML = '<span class="tch-badge tch-badge--info">' + (data.status || '-') + '</span>';
                    }
                    
                    // Manejar grupos con badges
                    const gruposElement = document.getElementById('modal-grupos');
                    if (data.grupos && data.grupos.trim()) {
                        const gruposArray = data.grupos.split(',');
                        gruposElement.innerHTML = gruposArray.map(grupo => 
                            '<span class="tch-badge tch-badge--institutional me-1 mb-1">' + grupo.trim() + '</span>'
                        ).join('');
                    } else {
                        gruposElement.innerHTML = '<span class="text-muted">Sin asignaciones</span>';
                    }
                    
                    // Manejar materias con badges
                    const materiasElement = document.getElementById('modal-materias');
                    if (data.materias && data.materias.trim()) {
                        const materiasItems = data.materias.split('|||').filter(s => s.trim());
                        materiasElement.innerHTML = materiasItems.map(item => {
                            const parts = item.split(':');
                            const name = parts.length >= 3 ? parts.slice(2).join(':').trim() : item.trim();
                            return '<span class="tch-badge tch-badge--institutional me-1 mb-1">' + name + '</span>';
                        }).join('');
                    } else {
                        materiasElement.innerHTML = '<span class="text-muted">Sin materias</span>';
                    }
                });
            });
            
            // Validación de formulario Bootstrap
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });

            // Manejar clic en badges de materia para editar
            document.querySelectorAll('.tch-badge--clickable').forEach(function(badge) {
                badge.addEventListener('click', function() {
                    var idDFM = this.dataset.iddfm;
                    var idSubject = this.dataset.idsubject;
                    var subjectName = this.dataset.subjectname;
                    var idTeacher = this.dataset.idteacher;
                    var teacherName = this.dataset.teachername;

                    document.getElementById('edit-teacher-name').textContent = teacherName;
                    document.getElementById('edit-current-subject').textContent = subjectName;
                    document.getElementById('edit-idDFM').value = idDFM;
                    document.getElementById('edit-idTeacher').value = idTeacher;
                    document.getElementById('edit-idSubjectOld').value = idSubject;

                    var select = document.getElementById('edit-new-subject');
                    select.value = '';
                    select.classList.remove('is-invalid', 'is-valid');
                    document.getElementById('editSubjectInfo').innerHTML = '';

                    var modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
                    modal.show();
                });
            });

            // Guardar cambio de materia
            var btnSaveSubject = document.getElementById('btnSaveSubject');
            if (btnSaveSubject) {
                btnSaveSubject.addEventListener('click', function() {
                    var select = document.getElementById('edit-new-subject');
                    var idSubjectNew = select.value;

                    if (!idSubjectNew) {
                        select.classList.add('is-invalid');
                        return;
                    }
                    select.classList.remove('is-invalid');

                    var idDFM = document.getElementById('edit-idDFM').value;
                    var idTeacher = document.getElementById('edit-idTeacher').value;
                    var idSubjectOld = document.getElementById('edit-idSubjectOld').value;

                    if (idSubjectNew === idSubjectOld) {
                        document.getElementById('editSubjectInfo').innerHTML = '<div class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">La materia seleccionada es la misma que la actual.</div>';
                        return;
                    }

                    var self = this;
                    self.disabled = true;
                    self.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Guardando...';

                    fetch('updateTeacherSubject.php', {
                        method: 'POST',
                headers: {

                            'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            idDFM: parseInt(idDFM),
                            idSubjectNew: parseInt(idSubjectNew),
                            idSubjectOld: parseInt(idSubjectOld),
                            idTeacher: parseInt(idTeacher)
                        })
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Materia actualizada',
                                text: data.message,
                                confirmButtonColor: '#192E4E'
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonColor: '#192E4E'
                            });
                        }
                    })
                    .catch(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor',
                            confirmButtonColor: '#192E4E'
                        });
                    })
                    .finally(function() {
                        self.disabled = false;
                        self.innerHTML = '<i class="bi bi-check-circle me-2"></i>Guardar Cambios';
                    });
                });
            }

            // Manejar botones de editar docente
            document.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const editTeacherModal = document.getElementById('editTeacherModal');
                    if (!editTeacherModal) return;

                    // Llenar el formulario
                    const setVal = (id, val) => { const el = editTeacherModal.querySelector('#' + id); if (el) el.value = val || ''; };
                    
                    setVal('edit-id', this.getAttribute('data-id'));
                    setVal('edit-nombres', this.getAttribute('data-nombres'));
                    setVal('edit-apellido-paterno', this.getAttribute('data-paterno'));
                    setVal('edit-apellido-materno', this.getAttribute('data-materno'));
                    setVal('edit-genero', this.getAttribute('data-genero'));
                    setVal('edit-tipo-maestro', this.getAttribute('data-tipo'));
                    setVal('edit-ine', this.getAttribute('data-ine'));
                    setVal('edit-cedula', this.getAttribute('data-cedula'));
                    
                    const statusVal = this.getAttribute('data-status');
                    setVal('edit-status', statusVal === 'Activo' ? '1' : (statusVal === 'Inactivo' ? '2' : ''));
                    
                    setVal('edit-telefono', this.getAttribute('data-telefono'));
                    setVal('edit-email', this.getAttribute('data-email'));
                    setVal('edit-direccion', this.getAttribute('data-direccion'));

                    var modal = new bootstrap.Modal(editTeacherModal);
                    modal.show();
                });
            });

            // Manejar botones de eliminar
            let deleteTeacherId = null;
            document.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const deleteModal = document.getElementById('deleteModal');
                    if (!deleteModal) {
                        return;
                    }
                    
                    deleteTeacherId = this.getAttribute('data-id');
                    const teacherName = (this.getAttribute('data-nombres') || '') + ' ' + 
                                      (this.getAttribute('data-paterno') || '') + ' ' + 
                                      (this.getAttribute('data-materno') || '');
                    
                    // Actualizar el texto del modal
                    const deleteInfo = deleteModal.querySelector('#delete-teacher-info');
                    if (deleteInfo) {
                        deleteInfo.textContent = `Se eliminará permanentemente el docente: ${teacherName}`;
                    }

                    // Abrir el modal manualmente
                    var modal = new bootstrap.Modal(deleteModal);
                    modal.show();
                });
            });

            // Confirmar eliminación
            const confirmDeleteBtn = document.getElementById('confirm-delete');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    if (deleteTeacherId) {
                        // Redirigir a la página de eliminación usando POST
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'deleteTeacher.php';
                        
                        const inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'id';
                        inputId.value = deleteTeacherId;
                        form.appendChild(inputId);

                        const inputCsrf = document.createElement('input');
                        inputCsrf.type = 'hidden';
                        inputCsrf.name = 'csrf_token';
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        if(csrfMeta) {
                            inputCsrf.value = csrfMeta.content;
                        }
                        form.appendChild(inputCsrf);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
        
        // Mostrar alertas si vienen por GET
        <?php if (isset($_GET['status'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            let icon = 'success';
            let title = '';
            
            <?php if ($_GET['status'] == 1 || $_GET['status'] == 'success'): ?>
                title = "Docente agregado correctamente";
            <?php elseif ($_GET['status'] == 2): ?>
                title = "Docente actualizado correctamente";
            <?php elseif ($_GET['status'] == 3): ?>
                title = "Docente eliminado correctamente";
            <?php elseif ($_GET['status'] == 'error'): ?>
                icon = 'error';
                title = "<?php echo isset($_GET['message']) ? $_GET['message'] : 'Error al procesar la solicitud'; ?>";
            <?php endif; ?>
            
            if (title) {
                Swal.fire({
                    icon: icon,
                    title: title,
                    confirmButtonText: 'Aceptar'
                }).then(function() {
                    // Limpiar la URL para evitar que se muestre la alerta al recargar
                    const url = new URL(window.location.href);
                    url.searchParams.delete('status');
                    url.searchParams.delete('message');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>