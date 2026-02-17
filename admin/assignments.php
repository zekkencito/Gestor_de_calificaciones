<?php
require_once "check_session.php";
require_once "../force_password_check.php";
include '../conection.php';
// GRUPOS
$sqlGroups = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
$resultGroups1 = $conexion->query($sqlGroups); // Para el primer select
$resultGroups2 = $conexion->query($sqlGroups); // Para el segundo select si lo necesitas
// MATERIAS
$sqlSubjects1 = "SELECT idSubject, name FROM subjects ORDER BY name";
$resultSubjects1 = $conexion->query($sqlSubjects1);
$resultSubjects2 = $conexion->query($sqlSubjects1);
// DOCENTES
$sqlTeachers1 = "SELECT t.idTeacher, CONCAT(ui.names, ' ', ui.lastnamePa, ' ', ui.lastnameMa) AS nombre FROM teachers t INNER JOIN users u ON t.idUser = u.idUser INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo ORDER BY ui.names, ui.lastnamePa, ui.lastnameMa";
$resultTeachers1 = $conexion->query($sqlTeachers1);
$resultTeachers2 = $conexion->query($sqlTeachers1);
// CICLOS ESCOLARES
$sqlYears1 = "SELECT idSchoolYear, LEFT(startDate, 4) as year FROM schoolYear ORDER BY startDate DESC";
$resultYears1 = $conexion->query($sqlYears1);
$resultYears2 = $conexion->query($sqlYears1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignaciones</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/stylesBoot.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/admin/assignment.css">
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
<body class="row d-flex" style="height: 100vh; width: 100%; margin: 0; padding: 0; overflow: hidden;">
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
     <main class="flex-grow-1 col-9 p-0" style="height: 100vh; overflow-y: auto;">
        <?php include "../layouts/header.php"; ?>
        
        <!-- Header de la página -->
        <div class="container-fluid px-4 pt-5">
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-3">
                        <h1 class="page-title">
                            <i class="bi bi-clipboard-check me-3"></i>
                            Gestión de Asignaciones
                        </h1>
                        <p class="page-subtitle ">
                            Administra las asignaciones de docentes a grupos y materias
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="container-fluid px-4">
            <!-- Controles superiores -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-end align-items-center gap-3 flex-wrap">
                        <!-- Filtro por grupo -->
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 fw-semibold text-nowrap">
                                <i class="bi bi-collection me-1"></i>
                                Filtrar por grupo:
                            </label>
                            <select class="form-select form-select-sm border-secondary" id="filterGrupo" style="min-width: 200px;">
                                <option value="">Todos los grupos</option>
                                <?php
                                $sqlGroupsFilter = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
                                $resultGroupsFilter = $conexion->query($sqlGroupsFilter);
                                while($groupFilter = $resultGroupsFilter->fetch_assoc()) { ?>
                                    <option value="<?php echo $groupFilter['idGroup']; ?>"><?php echo htmlspecialchars($groupFilter['grupo']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <!-- Botón crear asignación -->
                        <button type="button" class="btn btn-primary btn-lg shadow" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                            <i class="bi bi-plus-circle me-2"></i>
                            Crear Nueva Asignación
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de asignaciones -->
            <div class="table-card mt-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-list-check me-2 text-primary"></i>
                                    Asignaciones Registradas
                                </h5>
                            </div>
                            
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tabla">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center"><i class="bi bi-calendar me-1"></i>Ciclo Escolar</th>
                                        <th class="text-center"><i class="bi bi-collection me-1"></i>Grupo</th>
                                        <th class="text-center"><i class="bi bi-book me-1"></i>Materias</th>
                                        <th class="text-center"><i class="bi bi-person me-1"></i>Apellido Paterno</th>
                                        <th class="text-center"><i class="bi bi-person me-1"></i>Apellido Materno</th>
                                        <th class="text-center"><i class="bi bi-person-check me-1"></i>Nombre</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody">
                    <?php
                    // Filtro PHP para mostrar solo los resultados buscados (solo para carga inicial)
                    $where = '';
                    if (isset($_GET['buscar']) && isset($_GET['valor'])) {
                        $buscar = $_GET['buscar'];
                        $valor = $_GET['valor'];
                        if ($buscar === 'grupo') {
                            $where = " AND g.idGroup = '" . $conexion->real_escape_string($valor) . "'";
                        } else if ($buscar === 'maestro') {
                            $where = " AND t.idTeacher = '" . $conexion->real_escape_string($valor) . "'";
                        } else if ($buscar === 'materia') {
                            $where = " AND sub.idSubject = '" . $conexion->real_escape_string($valor) . "'";
                        }
                    }
                    $sql = "SELECT 
                        syear.idSchoolYear, 
                        LEFT(syear.startDate, 4) AS ciclo,
                        g.idGroup, 
                        CONCAT(g.grade, g.group_) as grupo, 
                        GROUP_CONCAT(DISTINCT CONCAT(sub.idSubject, '___', sub.name) ORDER BY sub.name SEPARATOR '|||') as subjects_data,
                        ui.lastnamePa, 
                        ui.lastnameMa, 
                        ui.names,
                        t.idTeacher
                    FROM teacherGroupsSubjects tgs
                    INNER JOIN groups g ON tgs.idGroup = g.idGroup
                    INNER JOIN subjects sub ON tgs.idSubject = sub.idSubject
                    INNER JOIN teachers t ON tgs.idTeacher = t.idTeacher
                    INNER JOIN users u ON t.idUser = u.idUser
                    INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
                    INNER JOIN teacherSubject ts ON ts.idTeacher = tgs.idTeacher AND ts.idSubject = tgs.idSubject
                    INNER JOIN schoolYear syear ON ts.idSchoolYear = syear.idSchoolYear
                    WHERE 1 $where
                    GROUP BY syear.idSchoolYear, g.idGroup, t.idTeacher, g.grade, g.group_, ui.lastnamePa, ui.lastnameMa, ui.names
                    ORDER BY syear.startDate DESC, g.grade, g.group_, ui.lastnamePa";
                    $result = $conexion->query($sql);
                    
                    // Manejo de errores SQL
                    if (!$result) {
                        echo '<tr><td colspan="7" class="text-center text-danger py-4">';
                        echo '<i class="bi bi-exclamation-triangle me-2"></i>';
                        echo 'Error en la consulta: ' . htmlspecialchars($conexion->error);
                        echo '</td></tr>';
                    } else if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Datos generales de la fila (Grupo, Docente, Ciclo)
                            $rowIdGroup = htmlspecialchars($row['idGroup']);
                            $rowIdTeacher = htmlspecialchars($row['idTeacher']);
                            $rowIdYear = htmlspecialchars($row['idSchoolYear']);
                            
                            // Nombres para data attributes
                            $rowTxtGrupo = htmlspecialchars($row['grupo']);
                            $rowTxtCiclo = htmlspecialchars($row['ciclo']);
                            $rowTxtDocente = htmlspecialchars($row['names'] . ' ' . $row['lastnamePa'] . ' ' . $row['lastnameMa']);
                            
                            echo '<tr class="align-middle">';
                            echo '<td class="text-center">' . $rowTxtCiclo . '</td>';
                            echo '<td class="text-center"><span class="badge bg-primary">' . $rowTxtGrupo . '</span></td>';
                            
                            // Columna de Materias con Badges Interactivos
                            echo '<td class="text-center">';
                            echo '<div class="d-flex flex-wrap justify-content-center gap-2">';
                            
                            $subjectsData = explode('|||', $row['subjects_data']);
                            foreach ($subjectsData as $subjectStr) {
                                if(empty($subjectStr)) continue;
                                list($subId, $subName) = explode('___', $subjectStr);
                                $subId = htmlspecialchars($subId);
                                $subName = htmlspecialchars($subName);
                                
                                // Badge interactivo
                                echo '<div class="btn-group btn-group-sm" role="group">';
                                echo '<span class="btn btn-info btn-sm disabled" style="opacity: 1; color: #000; font-weight: 500;">' . $subName . '</span>';
                                
                                // Botón Editar
                                echo '<button type="button" class="btn btn-warning btn-sm btn-edit-subject" '
                                    . 'data-bs-toggle="modal" data-bs-target="#editModal" '
                                    . 'data-idgrupo="' . $rowIdGroup . '" '
                                    . 'data-idteacher="' . $rowIdTeacher . '" '
                                    . 'data-idyear="' . $rowIdYear . '" '
                                    . 'data-idsubject="' . $subId . '" '
                                    . 'data-txtgrupo="' . $rowTxtGrupo . '" '
                                    . 'data-txtdocente="' . $rowTxtDocente . '" '
                                    . 'data-txtciclo="' . $rowTxtCiclo . '" '
                                    . 'data-txtmateria="' . $subName . '" '
                                    . 'title="Editar ' . $subName . '">'
                                    . '<i class="bi bi-pencil-fill"></i>'
                                    . '</button>';
                                    
                                // Botón Eliminar
                                echo '<button type="button" class="btn btn-danger btn-sm btn-delete-subject" '
                                    . 'data-bs-toggle="modal" data-bs-target="#deleteModal" '
                                    . 'data-idgrupo="' . $rowIdGroup . '" '
                                    . 'data-idteacher="' . $rowIdTeacher . '" '
                                    . 'data-idyear="' . $rowIdYear . '" '
                                    . 'data-idsubject="' . $subId . '" '
                                    . 'title="Eliminar ' . $subName . '">'
                                    . '<i class="bi bi-trash-fill"></i>'
                                    . '</button>';
                                echo '</div>';
                            }
                            echo '</div>';
                            echo '</td>';
                            
                            echo '<td class="text-center">' . htmlspecialchars($row['lastnamePa']) . '</td>';
                            echo '<td class="text-center">' . htmlspecialchars($row['lastnameMa']) . '</td>';
                            echo '<td class="text-center fw-semibold">' . htmlspecialchars($row['names']) . '</td>';
                            
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center text-muted py-4">';
                        echo '<i class="bi bi-info-circle me-2"></i>';
                        echo 'No hay asignaciones registradas.';
                        echo '</td></tr>';
                    }
                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- END MAIN CONTENT --> 
        <!-- MODAL EDIT-->
        <div class="modal fade modal-m" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white border-0">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Editar Asignación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formEditAssignment">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="grupo" class="form-label fw-semibold">
                                        <i class="bi bi-collection me-1"></i>
                                        Grupo:
                                    </label>
                                    <?php
                                    $sqlGroups = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
                                    $resultGroups = $conexion->query($sqlGroups);
                                    ?>
                                    <select class="form-select border-secondary" name="grupo">
                                        <option value="">Seleccionar Grupo</option>
                                        <?php while($group = $resultGroups->fetch_assoc()) { ?>
                                            <option value="<?php echo $group['idGroup']; ?>"><?php echo htmlspecialchars($group['grupo']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="materia" class="form-label fw-semibold">
                                        <i class="bi bi-book me-1"></i>
                                        Materia:
                                    </label>
                                    <?php
                                    $sqlSubjects = "SELECT idSubject, name FROM subjects ORDER BY name";
                                    $resultSubjects = $conexion->query($sqlSubjects);
                                    ?>
                                    <select class="form-select border-secondary" name="materia">
                                        <option value="">Seleccionar Materia</option>
                                        <?php while($subject = $resultSubjects->fetch_assoc()) { ?>
                                            <option value="<?php echo $subject['idSubject']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="docente" class="form-label fw-semibold">
                                        <i class="bi bi-person-workspace me-1"></i>
                                        Docente:
                                    </label>
                                    <?php
                                    $sqlTeachers = "SELECT t.idTeacher, CONCAT(ui.names, ' ', ui.lastnamePa, ' ', ui.lastnameMa) AS nombre FROM teachers t INNER JOIN users u ON t.idUser = u.idUser INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo ORDER BY ui.names, ui.lastnamePa, ui.lastnameMa";
                                    $resultTeachers = $conexion->query($sqlTeachers);
                                    ?>
                                    <select class="form-select border-secondary" name="docente">
                                        <option value="">Seleccionar Docente</option>
                                        <?php while($teacher = $resultTeachers->fetch_assoc()) { ?>
                                            <option value="<?php echo $teacher['idTeacher']; ?>"><?php echo htmlspecialchars($teacher['nombre']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Ciclo Escolar:</strong> Esta asignación se creará para el ciclo escolar del año actual (<?php echo date('Y'); ?>)
                            </div>
                        </div>
                        <div class="modal-footer border-0 bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <!-- MODAL delete-->
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
                    <div class="text-center py-3">
                        <i class="bi bi-question-circle-fill text-warning display-4 mb-3"></i>
                        <h6 class="mb-0">¿Está seguro que desea eliminar esta asignación?</h6>
                        <p class="text-muted mt-2">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmModal">
                        <i class="bi bi-trash3 me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL confirm delete-->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        Confirmación Final
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="bi bi-exclamation-diamond-fill text-danger display-4 mb-3"></i>
                        <h6 class="mb-0">¿Está completamente seguro?</h6>
                        <p class="text-muted mt-2">Esta asignación será eliminada permanentemente del sistema.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>
                        Cambié de Opinión
                    </button>
                    <button type="button" class="btn btn-danger btnEliminar" id="eliminar">
                        <i class="bi bi-trash3-fill me-1"></i>
                        Eliminar Definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Asignar eventos CRUD inicialmente
            asignarEventosCRUD();

            // Limpiar parámetros de búsqueda de la URL (AJAX-first UX)
            if(window.location.search.includes('buscar=') || window.location.search.includes('valor=')){
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            // Función para asignar eventos CRUD
            function asignarEventosCRUD() {
                // Asignar eventos de edición a los botones dentro de los badges
                document.querySelectorAll('.btn-edit-subject').forEach(function(button) {
                    button.addEventListener('click', function() {
                        // Obtener datos directamente del botón
                        const idGrupo = this.getAttribute('data-idgrupo');
                        const idSubject = this.getAttribute('data-idsubject');
                        const idTeacher = this.getAttribute('data-idteacher');
                        const idYear = this.getAttribute('data-idyear');

                        const txtGrupo = this.getAttribute('data-txtgrupo');
                        const txtMateria = this.getAttribute('data-txtmateria');
                        const txtDocente = this.getAttribute('data-txtdocente');

                        // Rellenar selects del modal
                        const selectGrupo = document.querySelector('#editModal select[name="grupo"]');
                        const selectMateria = document.querySelector('#editModal select[name="materia"]');
                        const selectDocente = document.querySelector('#editModal select[name="docente"]');

                        // Función helper para seleccionar la opción correcta
                        const setSelectedOption = (select, value, text) => {
                            for(let option of select.options) {
                                if(option.value === value || option.textContent.trim() === text) {
                                    option.selected = true;
                                    break;
                                }
                            }
                        };

                        // Establecer los valores seleccionados
                        setSelectedOption(selectGrupo, idGrupo, txtGrupo);
                        setSelectedOption(selectMateria, idSubject, txtMateria);
                        setSelectedOption(selectDocente, idTeacher, txtDocente);

                        // Guardar valores originales para update
                        const modal = document.querySelector('#editModal');
                        modal.setAttribute('data-old-grupo', idGrupo);
                        modal.setAttribute('data-old-materia', idSubject);
                        modal.setAttribute('data-old-docente', idTeacher);
                    });
                });

                // Asignar eventos de eliminación a los botones dentro de los badges
                document.querySelectorAll('.btn-delete-subject').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const modal = document.getElementById('confirmModal');
                        // Guardar datos en el modal
                        modal.setAttribute('data-idgrupo', this.getAttribute('data-idgrupo'));
                        modal.setAttribute('data-idsubject', this.getAttribute('data-idsubject'));
                        modal.setAttribute('data-idteacher', this.getAttribute('data-idteacher'));
                        modal.setAttribute('data-idyear', this.getAttribute('data-idyear'));
                        
                        // Limpiar referencia de fila ya que ahora eliminamos items individuales
                        modal._row = null;
                    });
                });
            }

            // Mostrar SweetAlert si viene status por GET
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) {
                let icon = 'success';
                let title = '';
                let text = '';
                if (urlParams.get('status') === 'success') {
                    title = '¡Asignación creada correctamente!';
                    text = '';
                } else if (urlParams.get('status') === 'error') {
                    icon = 'error';
                    title = 'Error';
                    text = urlParams.get('message') || 'Error al procesar la solicitud';
                }
                Swal.fire({
                    icon: icon,
                    title: title,
                    text: text,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    // Limpia la URL para evitar el mensaje al recargar
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            }



            // Evento para actualizar asignación
            document.getElementById('formEditAssignment').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const data = new FormData(form);
                // Agregar valores originales
                data.append('old_grupo', document.querySelector('#editModal').getAttribute('data-old-grupo'));
                data.append('old_materia', document.querySelector('#editModal').getAttribute('data-old-materia'));
                data.append('old_docente', document.querySelector('#editModal').getAttribute('data-old-docente'));
                fetch('updateAssignment.php', {
                    method: 'POST',
                    body: data
                })
                .then(res => res.json())
                .then(res => {
                    if(res.success){
                        // Recargar la página para reflejar los cambios correctamente
                        // ya que la estructura agrupada es compleja de actualizar via DOM
                        window.location.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo actualizar.' });
                    }
                })
                .catch(()=>{
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar.' });
                });
            });

            // Evento para eliminar asignación
            document.getElementById('eliminar').addEventListener('click', function() {
                const modal = document.getElementById('confirmModal');
                const row = modal._row; // Get the row reference we stored earlier
                
                let idTeacher, idGroup, idSubject, idSchoolYear;
                
                if (row) {
                    // Get data from the row if available
                    idTeacher = row.getAttribute('data-idteacher');
                    idGroup = row.getAttribute('data-idgrupo');
                    idSubject = row.getAttribute('data-idsubject');
                    idSchoolYear = row.getAttribute('data-idyear');
                } else {
                    // Fallback to data attributes on the modal if row reference is not available
                    idTeacher = modal.getAttribute('data-idteacher');
                    idGroup = modal.getAttribute('data-idgrupo');
                    idSubject = modal.getAttribute('data-idsubject');
                    idSchoolYear = modal.getAttribute('data-idyear');
                }
                
                if (!idTeacher || !idGroup || !idSubject || !idSchoolYear) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo encontrar la asignación a eliminar.' });
                    return;
                }
                
                // Store the row reference for later use in the success callback
                const rowToRemove = row || document.querySelector(`#tabla tbody tr[data-idgrupo='${idGroup}'][data-idsubject='${idSubject}'][data-idteacher='${idTeacher}']`);
                
                // Proceed with the delete
                fetch('deleteAssignment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `idTeacher=${encodeURIComponent(idTeacher)}&idGroup=${encodeURIComponent(idGroup)}&idSubject=${encodeURIComponent(idSubject)}&idSchoolYear=${encodeURIComponent(idSchoolYear)}`
                })
                .then(res => res.json())
                .then(res => {
                    if(res.success){
                        // Recargar la página para reflejar los cambios correctamente
                        window.location.reload();
                    } else {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Error', 
                            text: res.message || 'No se pudo eliminar la asignación.' 
                        });
                    }
                })
                .catch((error) => {
                    console.error('Error al eliminar la asignación:', error);
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Error', 
                        text: 'Ocurrió un error al intentar eliminar la asignación. Por favor, inténtalo de nuevo.' 
                    });
                });
            });
        });
    </script>

    <script>
        // Filtro rápido por grupo
        document.getElementById('filterGrupo').addEventListener('change', function() {
            const grupoId = this.value;
            const tbody = document.getElementById('tbody');
            const rows = tbody.getElementsByTagName('tr');
            
            // Mostrar todas las filas si no hay filtro
            if (!grupoId) {
                Array.from(rows).forEach(row => {
                    row.style.display = '';
                });
                return;
            }
            
            // Filtrar filas por grupo
            Array.from(rows).forEach(row => {
                const rowGrupoId = row.getAttribute('data-idgrupo');
                if (rowGrupoId === grupoId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

    <!-- Modal para crear asignación -->
    <div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-labelledby="addAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="addAssignmentModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva Asignación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form" action="./addAssignment.php" method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="grupo" class="form-label fw-semibold">
                                    <i class="bi bi-collection me-1"></i>
                                    Grupo:
                                </label>
                                <?php
                                $sqlGroups = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
                                $resultGroups = $conexion->query($sqlGroups);
                                ?>
                                <select class="form-select border-secondary" id="grupo" name="grupo" required>
                                    <option value="" selected>Seleccionar grupo</option>
                                    <?php while($group = $resultGroups->fetch_assoc()) { ?>
                                        <option value="<?php echo $group['idGroup']; ?>"><?php echo htmlspecialchars($group['grupo']); ?></option>
                                    <?php } ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un grupo.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="materia" class="form-label fw-semibold">
                                    <i class="bi bi-book me-1"></i>
                                    Materia:
                                </label>
                                <?php
                                $sqlSubjects = "SELECT idSubject, name FROM subjects ORDER BY name";
                                $resultSubjects = $conexion->query($sqlSubjects);
                                ?>
                                <select class="form-select border-secondary" id="materia" name="materia" required>
                                    <option value="" selected>Seleccionar materia</option>
                                    <?php while($subject = $resultSubjects->fetch_assoc()) { ?>
                                        <option value="<?php echo $subject['idSubject']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                    <?php } ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione una materia.
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label for="docente" class="form-label fw-semibold">
                                    <i class="bi bi-person-workspace me-1"></i>
                                    Docente:
                                </label>
                                <?php
                                $sqlTeachers = "SELECT t.idTeacher, CONCAT(ui.names, ' ', ui.lastnamePa, ' ', ui.lastnameMa) AS nombre FROM teachers t INNER JOIN users u ON t.idUser = u.idUser INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo ORDER BY ui.names, ui.lastnamePa, ui.lastnameMa";
                                $resultTeachers = $conexion->query($sqlTeachers);
                                ?>
                                <select class="form-select border-secondary" id="docente" name="docente" required>
                                    <option value="" selected>Seleccionar docente</option>
                                    <?php while($teacher = $resultTeachers->fetch_assoc()) { ?>
                                        <option value="<?php echo $teacher['idTeacher']; ?>"><?php echo htmlspecialchars($teacher['nombre']); ?></option>
                                    <?php } ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un docente.
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Ciclo Escolar:</strong> Esta asignación se creará para el ciclo escolar del año actual (<?php echo date('Y'); ?>)
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Crear Asignación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>