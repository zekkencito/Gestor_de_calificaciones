<?php
require_once "check_session.php";
require_once "../force_password_check.php";
require_once '../conection.php';
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <title>Asignaciones</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/design-system.css?v=5">
    <link rel="stylesheet" href="../css/components.css?v=5">
    <link rel="stylesheet" href="../css/layout.css?v=5">
    <link rel="stylesheet" href="../css/styles.css?v=5">
    <link rel="stylesheet" href="../css/admin/time.css?v=5">
    <link rel="stylesheet" href="../css/admin/assignment.css?v=5">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">

    <!-- TIPOGRAFIA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">


    
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="page-assignments">
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
     <main class="ds-main">
        <?php include "../layouts/header.php"; ?>
        
        <div class="page-content">
            <!-- Header de la página -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-clipboard-check me-3"></i>
                    Gestión de Asignaciones
                </h1>
                <p class="page-subtitle">
                    Administra las asignaciones de docentes a grupos y materias
                </p>
            </div>

            <!-- Filtros -->
            <div class="asn-filters">
                <div class="asn-filter">
                    <label for="filterGrupo" class="asn-filter__label">
                        Grupo
                    </label>
                    <select class="asn-filter__select" id="filterGrupo">
                        <option value="">Todos los grupos</option>
                        <?php
                        $sqlGroupsFilter = "SELECT idGroup, CONCAT(grade, group_) as grupo FROM groups ORDER BY grade, group_";
                        $resultGroupsFilter = $conexion->query($sqlGroupsFilter);
                        while($groupFilter = $resultGroupsFilter->fetch_assoc()) { ?>
                            <option value="<?php echo $groupFilter['idGroup']; ?>"><?php echo htmlspecialchars($groupFilter['grupo']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="asn-filter">
                    <label for="filterDocente" class="asn-filter__label">
                        Docente
                    </label>
                    <select class="asn-filter__select" id="filterDocente">
                        <option value="">Todos los docentes</option>
                        <?php
                        $sqlDocentesFilter = "SELECT t.idTeacher, CONCAT(ui.names, ' ', ui.lastnamePa, ' ', ui.lastnameMa) AS nombre FROM teachers t INNER JOIN users u ON t.idUser = u.idUser INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo ORDER BY ui.names, ui.lastnamePa, ui.lastnameMa";
                        $resultDocentesFilter = $conexion->query($sqlDocentesFilter);
                        while($docenteFilter = $resultDocentesFilter->fetch_assoc()) { ?>
                            <option value="<?php echo $docenteFilter['idTeacher']; ?>"><?php echo htmlspecialchars($docenteFilter['nombre']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="asn-actions">
                    <button type="button" class="asn-btn asn-btn--primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                        <i class="bi bi-plus-lg"></i>
                        Crear Asignación
                    </button>
                </div>
            </div>

            <!-- Tabla de asignaciones -->
            <div class="asn-table-wrap">
                <div class="asn-table-header">
                    <h2 class="asn-table-title">
                        <i class="bi bi-list-check me-2"></i>
                        Asignaciones Registradas
                    </h2>
                </div>
                <div class="asn-table-responsive">
                    <table class="asn-table" id="tabla">
                        <thead>
                            <tr>
                                <th>Ciclo Escolar</th>
                                <th>Grupo</th>
                                <th>Materias</th>
                                <th>Apellido Paterno</th>
                                <th>Apellido Materno</th>
                                <th>Nombre</th>
                            </tr>
                        </thead>
                                        <tbody id="tbody">
                    <?php
                    // Filtro PHP para mostrar solo los resultados buscados (solo para carga inicial)
                    $whereTGS = '';
                    $whereTG = '';
                    if (isset($_GET['buscar']) && isset($_GET['valor'])) {
                        $buscar = $_GET['buscar'];
                        $valor = $_GET['valor'];
                        if ($buscar === 'grupo') {
                            $whereTGS = " AND g.idGroup = '" . $conexion->real_escape_string($valor) . "'";
                            $whereTG = " AND g2.idGroup = '" . $conexion->real_escape_string($valor) . "'";
                        } else if ($buscar === 'maestro') {
                            $whereTGS = " AND tgs.idTeacher = '" . $conexion->real_escape_string($valor) . "'";
                            $whereTG = " AND tg2.idTeacher = '" . $conexion->real_escape_string($valor) . "'";
                        } else if ($buscar === 'materia') {
                            $whereTGS = " AND sub.idSubject = '" . $conexion->real_escape_string($valor) . "'";
                            // Para materia, solo la primera parte del UNION aplica
                        }
                    }
                    // UNION: parte 1 = teacherGroupsSubjects agrupados (asignaciones con materias)
                    //         parte 2 = teacherGroup sin subjects (asignaciones vacías)
                    $sql = "SELECT * FROM (
                        SELECT 
                            tg.idTeacherGroup,
                            ts.idSchoolYear, 
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
                        LEFT JOIN teacherGroup tg ON tg.idTeacher = tgs.idTeacher AND tg.idGroup = tgs.idGroup AND tg.idSchoolYear = ts.idSchoolYear
                        WHERE 1 $whereTGS
                        GROUP BY t.idTeacher, g.idGroup, ts.idSchoolYear

                        UNION

                        SELECT 
                            tg2.idTeacherGroup,
                            tg2.idSchoolYear,
                            LEFT(sy2.startDate, 4) AS ciclo,
                            g2.idGroup,
                            CONCAT(g2.grade, g2.group_) as grupo,
                            '' as subjects_data,
                            ui2.lastnamePa,
                            ui2.lastnameMa,
                            ui2.names,
                            t2.idTeacher
                        FROM teacherGroup tg2
                        INNER JOIN groups g2 ON tg2.idGroup = g2.idGroup
                        INNER JOIN teachers t2 ON tg2.idTeacher = t2.idTeacher
                        INNER JOIN users u2 ON t2.idUser = u2.idUser
                        INNER JOIN usersInfo ui2 ON u2.idUserInfo = ui2.idUserInfo
                        INNER JOIN schoolYear sy2 ON tg2.idSchoolYear = sy2.idSchoolYear
                        LEFT JOIN teacherGroupsSubjects tgs2 ON tgs2.idTeacher = tg2.idTeacher AND tgs2.idGroup = tg2.idGroup
                        WHERE tgs2.idDFM IS NULL $whereTG
                    ) AS combined
                    ORDER BY ciclo DESC, grupo, lastnamePa";
                    $result = $conexion->query($sql);

                    // Deduplicar por teacherGroup id (UNION puede dar duplicados si teacherGroup tiene subjects)
                    $seenTG = [];
                    
                    // Manejo de errores SQL
                    if (!$result) {
                        echo '<tr><td colspan="7" class="text-center text-danger py-4">';
                        echo '<i class="bi bi-exclamation-triangle me-2"></i>';
                        echo 'Error en la consulta: ' . htmlspecialchars($conexion->error);
                        echo '</td></tr>';
                    } else if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Deduplicar: si ya mostramos este teacherGroup, saltar
                            $rowIdTG = htmlspecialchars($row['idTeacherGroup']);
                            if ($rowIdTG && isset($seenTG[$rowIdTG])) continue;
                            if ($rowIdTG) $seenTG[$rowIdTG] = true;

                            // Datos generales de la fila (Grupo, Docente, Ciclo)
                            $rowIdGroup = htmlspecialchars($row['idGroup']);
                            $rowIdTeacher = htmlspecialchars($row['idTeacher']);
                            $rowIdYear = htmlspecialchars($row['idSchoolYear']);
                            
                            // Nombres para data attributes
                            $rowTxtGrupo = htmlspecialchars($row['grupo']);
                            $rowTxtCiclo = htmlspecialchars($row['ciclo']);
                            $rowTxtDocente = htmlspecialchars($row['names'] . ' ' . $row['lastnamePa'] . ' ' . $row['lastnameMa']);
                            
                            echo '<tr class="align-middle" data-idgrupo="' . $rowIdGroup . '" data-idteacher="' . $rowIdTeacher . '" data-idyear="' . $rowIdYear . '">';
                            echo '<td class="text-center">' . $rowTxtCiclo . '</td>';
                            echo '<td class="text-center"><span class="asn-subject-badge">' . $rowTxtGrupo . '</span></td>';
                            
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
                                echo '<span class="asn-subject-badge">' . $subName . '</span>';
                                
                                // Botón Editar
                                echo '<button type="button" class="asn-btn asn-btn--sm asn-btn--icon" '
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
                                echo '<button type="button" class="asn-btn asn-btn--sm asn-btn--icon asn-btn--icon-danger" '
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

                            // Botón + Añadir materia (siempre visible)
                            echo '<button type="button" class="asn-btn asn-btn--sm asn-btn--outline" '
                                . 'data-bs-toggle="modal" data-bs-target="#addSubjectModal" '
                                . 'data-idtg="' . $rowIdTG . '" '
                                . 'data-idgrupo="' . $rowIdGroup . '" '
                                . 'data-idteacher="' . $rowIdTeacher . '" '
                                . 'data-idyear="' . $rowIdYear . '" '
                                . 'data-txtgrupo="' . $rowTxtGrupo . '" '
                                . 'data-txtdocente="' . $rowTxtDocente . '" '
                                . 'title="Añadir materia">'
                                . '<i class="bi bi-plus-lg me-1"></i>Añadir materia'
                                . '</button>';

                            echo '</div>';
                            echo '</td>';
                            
                            echo '<td class="text-center">' . htmlspecialchars($row['lastnamePa']) . '</td>';
                            echo '<td class="text-center">' . htmlspecialchars($row['lastnameMa']) . '</td>';
                            echo '<td class="text-center">' . htmlspecialchars($row['names']) . '</td>';
                            
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
        <div class="modal fade ds-modal" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Editar Asignación
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formEditAssignment">
                        <div class="modal-body">
                            <input type="hidden" name="idgrupo" id="edit-idgrupo">
                            <input type="hidden" name="docente" id="edit-idteacher">
                            <input type="hidden" name="old_materia" id="edit-oldmateria">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-collection me-1"></i>
                                    Grupo / Clase:
                                </label>
                                <div class="asn-filters__field-value" id="edit-txtgrupo"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-person-workspace me-1"></i>
                                    Docente:
                                </label>
                                <div class="asn-filters__field-value" id="edit-txtdocente"></div>
                            </div>
                            <div class="mb-3">
                                <label for="edit-materia" class="form-label fw-semibold">
                                    <i class="bi bi-book me-1"></i>
                                    Materia:
                                </label>
                                <?php
                                $sqlSubjects = "SELECT idSubject, name FROM subjects ORDER BY name";
                                $resultSubjects = $conexion->query($sqlSubjects);
                                ?>
                                <select class="form-select border-secondary" name="materia" id="edit-materia" required>
                                    <option value="">Seleccionar Materia</option>
                                    <?php while($subject = $resultSubjects->fetch_assoc()) { ?>
                                        <option value="<?php echo $subject['idSubject']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="asn-btn asn-btn--outline" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="asn-btn asn-btn--primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <!-- MODAL add subject -->
    <div class="modal fade ds-modal" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Añadir Materia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="addSubject-idTeacherGroup">
                    <input type="hidden" id="addSubject-idGrupo">
                    <input type="hidden" id="addSubject-idTeacher">
                    <input type="hidden" id="addSubject-idYear">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-collection me-1"></i>
                            Grupo / Clase:
                        </label>
                        <div class="asn-filters__field-value" id="addSubject-txtGrupo"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person-workspace me-1"></i>
                            Docente:
                        </label>
                        <div class="asn-filters__field-value" id="addSubject-txtDocente"></div>
                    </div>
                    <div class="mb-3">
                        <label for="addSubject-materia" class="form-label fw-semibold">
                            <i class="bi bi-book me-1"></i>
                            Materia:
                        </label>
                        <?php
                        $sqlSubjectsAdd = "SELECT idSubject, name FROM subjects ORDER BY name";
                        $resultSubjectsAdd = $conexion->query($sqlSubjectsAdd);
                        ?>
                        <select class="form-select border-secondary" id="addSubject-materia" required>
                            <option value="">Seleccionar Materia</option>
                            <?php while($subject = $resultSubjectsAdd->fetch_assoc()) { ?>
                                <option value="<?php echo $subject['idSubject']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="asn-btn asn-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="asn-btn asn-btn--primary" id="btnSaveSubject">
                        <i class="bi bi-check-circle me-1"></i>
                        Añadir Materia
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- MODAL delete-->
    <div class="modal fade ds-modal" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
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
                    <div class="text-center py-3">
                        <i class="bi bi-question-circle-fill text-warning display-4 mb-3"></i>
                        <h6 class="mb-0">¿Está seguro que desea eliminar esta asignación?</h6>
                        <p class="text-muted mt-2">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="asn-btn asn-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="asn-btn asn-btn--danger" data-bs-toggle="modal" data-bs-target="#confirmModal">
                        <i class="bi bi-trash3 me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL confirm delete-->
    <div class="modal fade ds-modal" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        Confirmación Final
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="bi bi-exclamation-diamond-fill text-danger display-4 mb-3"></i>
                        <h6 class="mb-0">¿Está completamente seguro?</h6>
                        <p class="text-muted mt-2">Esta asignación será eliminada permanentemente del sistema.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="asn-btn asn-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>
                        Cambié de Opinión
                    </button>
                    <button type="button" class="asn-btn asn-btn--danger" id="eliminar">
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
                // Poblar modal de edición cuando se abre (el botón que lo abre trae los data-* via relatedTarget)
                document.getElementById('editModal').addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button || !button.hasAttribute('data-idgrupo')) return;

                    document.getElementById('edit-idgrupo').value = button.getAttribute('data-idgrupo') || '';
                    document.getElementById('edit-idteacher').value = button.getAttribute('data-idteacher') || '';
                    document.getElementById('edit-oldmateria').value = button.getAttribute('data-idsubject') || '';

                    document.getElementById('edit-txtgrupo').textContent = button.getAttribute('data-txtgrupo') || '-';
                    document.getElementById('edit-txtdocente').textContent = button.getAttribute('data-txtdocente') || '-';

                    // Seleccionar materia actual en el dropdown
                    const selectMateria = document.getElementById('edit-materia');
                    const idSubject = button.getAttribute('data-idsubject') || '';
                    for (let i = 0; i < selectMateria.options.length; i++) {
                        if (selectMateria.options[i].value === idSubject) {
                            selectMateria.selectedIndex = i;
                            break;
                        }
                    }
                });

                // Poblar addSubjectModal cuando se abre
                document.getElementById('addSubjectModal').addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button || !button.hasAttribute('data-idgrupo')) return;

                    var idTG = button.getAttribute('data-idtg') || '';
                    document.getElementById('addSubject-idTeacherGroup').value = idTG;
                    document.getElementById('addSubject-idGrupo').value = button.getAttribute('data-idgrupo') || '';
                    document.getElementById('addSubject-idTeacher').value = button.getAttribute('data-idteacher') || '';
                    document.getElementById('addSubject-idYear').value = button.getAttribute('data-idyear') || '';
                    document.getElementById('addSubject-txtGrupo').textContent = button.getAttribute('data-txtgrupo') || '-';
                    document.getElementById('addSubject-txtDocente').textContent = button.getAttribute('data-txtdocente') || '-';

                    // Resetear select de materia
                    var sel = document.getElementById('addSubject-materia');
                    sel.value = '';
                    sel.classList.remove('is-invalid');

                    // Fetch materias ya asignadas y deshabilitarlas
                    if (idTG) {
                        fetch('getAssignedSubjects.php?idTG=' + encodeURIComponent(idTG))
                            .then(function(r) { return r.json(); })
                            .then(function(assignedIds) {
                                Array.from(sel.options).forEach(function(opt) {
                                    if (opt.value === '') return;
                                    opt.disabled = assignedIds.indexOf(parseInt(opt.value)) !== -1;
                                });
                                // Si la materia seleccionada estaba asignada, limpiar selección
                                if (sel.value && assignedIds.indexOf(parseInt(sel.value)) !== -1) {
                                    sel.value = '';
                                }
                            })
                            .catch(function() { /* ignore, show all options */ });
                    }
                });

                // Poblar confirmModal cuando deleteModal se abre (forward data desde el botón original)
                document.getElementById('deleteModal').addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button || !button.hasAttribute('data-idgrupo')) return;

                    const confirmModal = document.getElementById('confirmModal');
                    confirmModal.setAttribute('data-idgrupo', button.getAttribute('data-idgrupo'));
                    confirmModal.setAttribute('data-idteacher', button.getAttribute('data-idteacher'));
                    confirmModal.setAttribute('data-idsubject', button.getAttribute('data-idsubject'));
                    confirmModal.setAttribute('data-idyear', button.getAttribute('data-idyear'));
                    confirmModal._row = null;
                });
            }

            // Guardar materia nueva
            var btnSaveSubject = document.getElementById('btnSaveSubject');
            if (btnSaveSubject) {
                btnSaveSubject.addEventListener('click', function() {
                    var select = document.getElementById('addSubject-materia');
                    var idSubject = select.value;
                    if (!idSubject) {
                        select.classList.add('is-invalid');
                        return;
                    }
                    select.classList.remove('is-invalid');

                    var idTG = document.getElementById('addSubject-idTeacherGroup').value;
                    var idGrupo = document.getElementById('addSubject-idGrupo').value;
                    var idTeacher = document.getElementById('addSubject-idTeacher').value;
                    var idYear = document.getElementById('addSubject-idYear').value;
                    var self = this;
                    self.disabled = true;
                    self.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Añadiendo...';

                    fetch('addSubjectToGroup.php', {
                        method: 'POST',
                headers: {

                            'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            idTeacherGroup: idTG ? parseInt(idTG) : 0,
                            idGrupo: parseInt(idGrupo),
                            idTeacher: parseInt(idTeacher),
                            idSchoolYear: parseInt(idYear),
                            idSubject: parseInt(idSubject)
                        })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Materia añadida', text: data.message, confirmButtonColor: '#192E4E' })
                            .then(function() { location.reload(); });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#192E4E' });
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar con el servidor', confirmButtonColor: '#192E4E' });
                    })
                    .finally(function() {
                        self.disabled = false;
                        self.innerHTML = '<i class="bi bi-check-circle me-1"></i>Añadir Materia';
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
                const data = new FormData(this);
                fetch('updateAssignment.php', {
                    method: 'POST',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                    body: data
                })
                .then(res => res.json())
                .then(res => {
                    if(res.success){
                        Swal.fire({
                            icon: 'success',
                            title: 'Materia actualizada',
                            text: 'La asignación se ha actualizado correctamente.',
                            confirmButtonColor: '#192E4E',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
                        });
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
                headers: {

                        'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
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
        // Filtro rápido por grupo y docente
        function aplicarFiltros() {
            const grupoId = document.getElementById('filterGrupo').value;
            const docenteId = document.getElementById('filterDocente').value;
            const tbody = document.getElementById('tbody');
            const rows = tbody.getElementsByTagName('tr');
            
            // Filtrar filas según los valores seleccionados
            Array.from(rows).forEach(row => {
                const rowGrupoId = row.getAttribute('data-idgrupo');
                const rowDocenteId = row.getAttribute('data-idteacher');
                
                // Mostrar la fila si cumple con los filtros seleccionados
                let mostrar = true;
                
                // Si hay filtro de grupo y no coincide, ocultar
                if (grupoId && rowGrupoId !== grupoId) {
                    mostrar = false;
                }
                
                // Si hay filtro de docente y no coincide, ocultar
                if (docenteId && rowDocenteId !== docenteId) {
                    mostrar = false;
                }
                
                row.style.display = mostrar ? '' : 'none';
            });
        }
        
        // Eventos para ambos filtros
        document.getElementById('filterGrupo').addEventListener('change', aplicarFiltros);
        document.getElementById('filterDocente').addEventListener('change', aplicarFiltros);
    </script>

    <!-- Modal para crear asignación -->
    <div class="modal fade ds-modal" id="addAssignmentModal" tabindex="-1" aria-labelledby="addAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAssignmentModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva Asignación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form" action="./addAssignment.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <div class="mb-3">
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
                        <div class="mb-3">
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
                        <div class="mb-3">
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="asn-btn asn-btn--outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="asn-btn asn-btn--primary" form="form">
                        <i class="bi bi-check-circle me-1"></i>
                        Crear Asignación
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>