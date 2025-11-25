<?php
require_once "check_session.php";
require_once "../force_password_check.php";
include '../conection.php';

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
    GROUP_CONCAT(DISTINCT CONCAT(g.grade, '°', g.group_)) AS grupos,
    GROUP_CONCAT(DISTINCT s.name) AS materias
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
    <title>Maestros</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin/teacher.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="icon" href="../img/logo.ico">
    
    <style>
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }
        
        #preloader.loaded {
            opacity: 0;
            pointer-events: none;
        }
        
        #preloader.hidden {
            display: none !important;
        }
        
        
        #preloader.loaded .logo {
            animation: none;
            transform: rotate(0deg);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
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
                            <i class="bi bi-person-workspace me-3"></i>
                            Gestión de Docentes
                        </h1>
                        <p class="page-subtitle">
                            Administra la información de los profesores del sistema
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
                                    <i class="bi bi-search me-2 text-primary"></i>
                                    Buscar Docente
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="docente" class="form-label fw-semibold">
                                            <i class="bi bi-person-search me-1"></i>
                                            Buscar por nombre:
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control border-secondary" id="docente" placeholder="Buscar docente...">
                                            <span class="input-group-text bg-light border-secondary">
                                                <i class="bi bi-search text-primary"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 d-flex align-items-end pt-4">
                                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addModal">
                                            <i class="bi bi-plus-lg me-2"></i>
                                            Agregar Docente
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de docentes -->
            <div class="row">
                <div class="col-12">
                    <div class="table-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-list-check me-2 text-primary"></i>
                                    Docentes Registrados
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="fw-semibold">ID</th>
                                                <th class="fw-semibold">Apellido Paterno</th>
                                                <th class="fw-semibold">Apellido Materno</th>
                                                <th class="fw-semibold">Nombre(s)</th>
                                                <th class="fw-semibold">Estado</th>
                                                <th class="fw-semibold">Grupo</th>
                                                <th class="fw-semibold">Materia</th>
                                                <th class="fw-semibold text-center">Acciones</th>
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
                                                        echo '<span class="badge bg-success">' . htmlspecialchars($fila['status']) . '</span>';
                                                    } elseif ($fila['status'] == 'Inactivo') {
                                                        echo '<span class="badge bg-danger">' . htmlspecialchars($fila['status']) . '</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($fila['status']) . '</span>'; 
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
                                                                echo '<span class="badge bg-primary me-1">' . htmlspecialchars(trim($grupo)) . '</span>';
                                                            }
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">Sin asignaciones</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $materias = $fila['materias'];
                                                    if (!empty($materias)) {
                                                        $materiasArray = explode(',', $materias);
                                                        $materiasLimitadas = array_slice($materiasArray, 0); // Solo mostrar 3
                                                        foreach ($materiasLimitadas as $materia) {
                                                            if (!empty(trim($materia))) {
                                                                echo '<span class="badge bg-info text-dark me-1 mb-1">' . htmlspecialchars(trim($materia)) . '</span>';
                                                            }
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">Sin materias</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group" aria-label="Acciones">
                                                        <button class="btn btn-sm btn-outline-info btn-ver" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#showModal" 
                                                            data-id="<?php echo $fila['idTeacher']; ?>"
                                                            data-nombres="<?php echo htmlspecialchars($fila['names']); ?>"
                                                            data-paterno="<?php echo htmlspecialchars($fila['lastnamePa']); ?>"
                                                            data-materno="<?php echo htmlspecialchars($fila['lastnameMa']); ?>"
                                                            data-status="<?php echo htmlspecialchars($fila['status']); ?>"
                                                            data-grupos="<?php echo htmlspecialchars($fila['grupos'] ?? ''); ?>"
                                                            data-materias="<?php echo htmlspecialchars($fila['materias'] ?? ''); ?>"
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
                                                        <button class="btn btn-sm btn-outline-warning btn-editar" 
                                                            data-id="<?php echo $fila['idTeacher']; ?>"
                                                            data-nombres="<?php echo htmlspecialchars($fila['names']); ?>"
                                                            data-paterno="<?php echo htmlspecialchars($fila['lastnamePa']); ?>"
                                                            data-materno="<?php echo htmlspecialchars($fila['lastnameMa']); ?>"
                                                            data-status="<?php echo htmlspecialchars($fila['status']); ?>"
                                                            data-status-id="<?php echo htmlspecialchars($fila['idTeacherStatus']); ?>"
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
                                                        <button class="btn btn-sm btn-outline-danger btn-eliminar" 
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
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- END MAIN CONTENT --> 

    <!-- Modal para mostrar detalles del docente -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="bi bi-person-circle me-2"></i>
                        Información del Docente
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
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-id">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Nombres:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-nombres">-</p>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-muted">Estado:</label>
                            <div class="border rounded px-3 py-2 bg-light d-flex align-items-center" id="modal-status">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Apellido Paterno:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-paterno">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Apellido Materno:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-materno">-</p>
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
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-telefono">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Email:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-email">-</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted">Dirección:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-direccion">-</p>
                        </div>
                        
                        <!-- Información Profesional -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-briefcase me-2"></i>
                                Información Profesional
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Cédula Profesional:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-cedula">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">INE:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-ine">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Tipo de Docente:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-tipo">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Género:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-genero">-</p>
                        </div>
                        
                        <!-- Asignaciones -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-clipboard-check me-2"></i>
                                Asignaciones Actuales
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Grupos:</label>
                            <div class="border rounded px-3 py-2 bg-light" id="modal-grupos">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Materias:</label>
                            <div class="border rounded px-3 py-2 bg-light" id="modal-materias">-</div>
                        </div>
                        
                        <!-- Información de Usuario -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-key me-2"></i>
                                Credenciales de Acceso
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Usuario:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-username">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Contraseña:</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 bg-light" id="modal-password">-</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar docente -->
    <div class="modal fade modal-lg" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="bi bi-person-plus me-2"></i>
                        Agregar Docente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="addTeacher.php" method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <!-- Información Personal -->
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-person-badge me-2"></i>
                                    Datos Personales
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="addName" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Nombre(s):
                                </label>
                                <input type="text" class="form-control border-secondary" id="addName" name="txtName" required>
                                <div class="invalid-feedback">Por favor ingrese el nombre.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addLastnamePa" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Paterno:
                                </label>
                                <input type="text" class="form-control border-secondary" id="addLastnamePa" name="txtLastnamePa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido paterno.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addLastnameMa" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>
                                    Apellido Materno:
                                </label>
                                <input type="text" class="form-control border-secondary" id="addLastnameMa" name="txtLastnameMa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido materno.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="addGender" class="form-label fw-semibold">
                                    <i class="bi bi-gender-ambiguous me-1"></i>
                                    Género:
                                </label>
                                <select class="form-select border-secondary" id="addGender" name="txtGender" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione el género.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="addTypeTeacher" class="form-label fw-semibold">
                                    <i class="bi bi-mortarboard me-1"></i>
                                    Tipo de Docente:
                                </label>
                                <select class="form-select border-secondary" id="addTypeTeacher" name="txtTypeTeacher" required>
                                    <option value="">Seleccione...</option>
                                    <option value="ME">Maestro Especial</option>
                                    <option value="MS">Maestro de Escolarizado</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione el tipo de docente.</div>
                            </div>

                            <!-- Información Profesional -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-briefcase me-2"></i>
                                    Información Profesional
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="addIne" class="form-label fw-semibold">
                                    <i class="bi bi-card-heading me-1"></i>
                                    INE:
                                </label>
                                <input type="text" class="form-control border-secondary" id="addIne" name="txtIne" required>
                                <div class="invalid-feedback">Por favor ingrese el número de INE.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="addProfesional" class="form-label fw-semibold">
                                    <i class="bi bi-award me-1"></i>
                                    Cédula Profesional:
                                </label>
                                <input type="text" class="form-control border-secondary" id="addProfesional" name="txtProfesional" required>
                                <div class="invalid-feedback">Por favor ingrese la cédula profesional.</div>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-telephone me-2"></i>
                                    Información de Contacto
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="addPhone" class="form-label fw-semibold">
                                    <i class="bi bi-telephone me-1"></i>
                                    Teléfono:
                                </label>
                                <input type="tel" class="form-control border-secondary" id="addPhone" name="txtPhone" required>
                                <div class="invalid-feedback">Por favor ingrese el teléfono.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addEmail" class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-1"></i>
                                    Email:
                                </label>
                                <input type="email" class="form-control border-secondary" id="addEmail" name="txtEmail" required>
                                <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="addAddress" class="form-label fw-semibold">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Dirección:
                                </label>
                                <input type="text" class="form-control border-secondary" id="addAddress" name="txtAddress" required>
                                <div class="invalid-feedback">Por favor ingrese la dirección.</div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 bg-light mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
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
    <div class="modal fade modal-lg" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="bi bi-pencil me-2"></i>
                        Editar Docente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="updateTeacher.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" id="edit-id" name="teacherId">
                        <div class="row g-3">
                            <!-- Información Personal -->
                            <div class="col-12">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-person-lines-fill me-2"></i>Información Personal
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit-nombres" name="txtName" required>
                                <div class="invalid-feedback">Por favor ingrese los nombres.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-apellido-paterno" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit-apellido-paterno" name="txtLastnamePa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido paterno.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-apellido-materno" class="form-label">Apellido Materno <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit-apellido-materno" name="txtLastnameMa" required>
                                <div class="invalid-feedback">Por favor ingrese el apellido materno.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-genero" class="form-label">Género <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit-genero" name="txtGender" required>
                                    <option value="">Seleccionar género</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un género.</div>
                            </div>

                            <!-- Documentos e Identificación -->
                            <div class="col-12 mt-4">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-file-text me-2"></i>Documentos e Identificación
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-ine" class="form-label">INE <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit-ine" name="txtIne" required>
                                <div class="invalid-feedback">Por favor ingrese el INE.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-cedula" class="form-label">Cédula Profesional <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit-cedula" name="txtProfesional" required>
                                <div class="invalid-feedback">Por favor ingrese la cédula profesional.</div>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="col-12 mt-4">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-envelope me-2"></i>Información de Contacto
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="edit-telefono" name="txtPhone" required>
                                <div class="invalid-feedback">Por favor ingrese un teléfono válido.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit-email" name="txtEmail" required>
                                <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                            </div>
                            <div class="col-12">
                                <label for="edit-direccion" class="form-label">Dirección <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit-direccion" name="txtAddress" rows="2" required></textarea>
                                <div class="invalid-feedback">Por favor ingrese la dirección.</div>
                            </div>

                            <!-- Información Laboral -->
                            <div class="col-12 mt-4">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-briefcase me-2"></i>Información Laboral
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-tipo-maestro" class="form-label">Tipo de Maestro <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit-tipo-maestro" name="txtTypeTeacher" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="ME">Maestro Especial</option>
                                    <option value="MS">Maestro de Escolarizado</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un tipo.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-status" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit-status" name="txtStatus" required>
                                    <option value="">Seleccionar estado</option>
                                    <option value="1">Activo</option>
                                    <option value="2">Inactivo</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un estado.</div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Actualizar Docente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar docente -->
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
                    <button type="button" class="btn btn-danger" id="confirm-delete">
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
                        statusElement.innerHTML = '<span class="badge bg-success">Activo</span>';
                    } else if (data.status === 'Inactivo') {
                        statusElement.innerHTML = '<span class="badge bg-danger">Inactivo</span>';
                    } else {
                        statusElement.innerHTML = '<span class="badge bg-secondary">' + (data.status || '-') + '</span>';
                    }
                    
                    // Manejar grupos con badges
                    const gruposElement = document.getElementById('modal-grupos');
                    if (data.grupos && data.grupos.trim()) {
                        const gruposArray = data.grupos.split(',');
                        gruposElement.innerHTML = gruposArray.map(grupo => 
                            '<span class="badge bg-primary me-1 mb-1">' + grupo.trim() + '</span>'
                        ).join('');
                    } else {
                        gruposElement.innerHTML = '<span class="text-muted">Sin asignaciones</span>';
                    }
                    
                    // Manejar materias con badges
                    const materiasElement = document.getElementById('modal-materias');
                    if (data.materias && data.materias.trim()) {
                        const materiasArray = data.materias.split(',');
                        materiasElement.innerHTML = materiasArray.map(materia => 
                            '<span class="badge bg-info text-dark me-1 mb-1">' + materia.trim() + '</span>'
                        ).join('');
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

            // Manejar botones de editar
            document.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const editModal = document.getElementById('editModal');
                    if (!editModal) {
                        return;
                    }

                    console.log('Botón de editar clickeado'); // Debug
                    
                    // Llenar el formulario de edición usando getAttribute
                    if(editModal.querySelector('#edit-id')) editModal.querySelector('#edit-id').value = this.getAttribute('data-id') || '';
                    if(editModal.querySelector('#edit-nombres')) editModal.querySelector('#edit-nombres').value = this.getAttribute('data-nombres') || '';
                    if(editModal.querySelector('#edit-apellido-paterno')) editModal.querySelector('#edit-apellido-paterno').value = this.getAttribute('data-paterno') || '';
                    if(editModal.querySelector('#edit-apellido-materno')) editModal.querySelector('#edit-apellido-materno').value = this.getAttribute('data-materno') || '';
                    if(editModal.querySelector('#edit-genero')) editModal.querySelector('#edit-genero').value = this.getAttribute('data-genero') || '';
                    if(editModal.querySelector('#edit-ine')) editModal.querySelector('#edit-ine').value = this.getAttribute('data-ine') || '';
                    if(editModal.querySelector('#edit-cedula')) editModal.querySelector('#edit-cedula').value = this.getAttribute('data-cedula') || '';
                    if(editModal.querySelector('#edit-telefono')) editModal.querySelector('#edit-telefono').value = this.getAttribute('data-telefono') || '';
                    if(editModal.querySelector('#edit-email')) editModal.querySelector('#edit-email').value = this.getAttribute('data-email') || '';
                    if(editModal.querySelector('#edit-direccion')) editModal.querySelector('#edit-direccion').value = this.getAttribute('data-direccion') || '';
                    if(editModal.querySelector('#edit-tipo-maestro')) editModal.querySelector('#edit-tipo-maestro').value = this.getAttribute('data-tipo') || '';
                    if(editModal.querySelector('#edit-status')) editModal.querySelector('#edit-status').value = this.getAttribute('data-status-id') || '';
                    
                    // Limpiar validaciones previas
                    const form = editModal.querySelector('form');
                    if (form) {
                        form.classList.remove('was-validated');
                    }

                    // Abrir el modal manualmente
                    var modal = new bootstrap.Modal(editModal);
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
                        // Redirigir a la página de eliminación
                        window.location.href = `deleteTeacher.php?id=${deleteTeacherId}`;
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