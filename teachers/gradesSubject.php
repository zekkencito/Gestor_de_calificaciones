<?php
// Limpiar OPcache del servidor para forzar recarga del archivo
if (function_exists('opcache_reset')) {
    opcache_reset();
}
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}
// Forzar que el navegador NO use caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
require_once "check_session.php";
require_once "../force_password_check.php";
require_once "../conection.php";



$idSubject = isset($_GET['idSubject']) ? intval($_GET['idSubject']) : 0;
$subjectName = "";

// Obtener el idTeacher del usuario actual
$user_id = $_SESSION['user_id'];
$stmtTeacher = $conexion->prepare("SELECT idTeacher FROM teachers WHERE idUser = ?");
$stmtTeacher->bind_param("i", $user_id);
$stmtTeacher->execute();
$resultTeacher = $stmtTeacher->get_result();
$teacherRow = $resultTeacher->fetch_assoc();
$stmtTeacher->close();

if (!$teacherRow) {
    die("No se encontró el docente asociado a este usuario.");
}
$idTeacher = $teacherRow['idTeacher'];

// Obtener las materias del profesor actual
$query = "SELECT s.name, s.idSubject
          FROM teacherSubject ts
          JOIN subjects s ON ts.idSubject = s.idSubject
          WHERE ts.idTeacher = ?";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $idTeacher);
$stmt->execute();
$result = $stmt->get_result();
$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

if ($idSubject > 0) {
    $stmtSubject = $conexion->prepare("SELECT name FROM subjects WHERE idSubject = ?");
    $stmtSubject->bind_param("i", $idSubject);
    $stmtSubject->execute();
    $stmtSubject->bind_result($subjectName);
    $stmtSubject->fetch();
    $stmtSubject->close();
}

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

$selectedYear = $currentSchoolYear['idSchoolYear'];

// Obtener solo los grupos vinculados a la materia y al profesor actual
$groupIds = [];
if ($idSubject > 0) {
    $stmtGroups = $conexion->prepare("SELECT DISTINCT tgs.idGroup, g.grade, g.group_
                               FROM teacherGroupsSubjects tgs
                               JOIN groups g ON tgs.idGroup = g.idGroup
                               WHERE tgs.idSubject = ? 
                               AND tgs.idTeacher = ?
                               ORDER BY g.grade, g.group_");
    $stmtGroups->bind_param("ii", $idSubject, $idTeacher);
    $stmtGroups->execute();
    $resultGroups = $stmtGroups->get_result();
    while ($row = $resultGroups->fetch_assoc()) {
        $groupIds[] = $row['idGroup'];
    }
    $stmtGroups->close();
}

// Obtener los alumnos de TODOS los grupos vinculados y año escolar seleccionado
$students = [];
if (!empty($groupIds) && $selectedYear !== null) {
    $in = str_repeat('?,', count($groupIds) - 1) . '?';
    $sql = "SELECT DISTINCT s.idStudent, ui.lastnamePa, ui.lastnameMa, ui.names, g.grade, g.group_ 
            FROM students s
            JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
            JOIN groups g ON s.idGroup = g.idGroup
            JOIN teacherGroupsSubjects tgs ON g.idGroup = tgs.idGroup
            WHERE s.idGroup IN ($in) 
            AND s.idSchoolYear = ?
            AND tgs.idTeacher = ?
            AND tgs.idSubject = ?
            AND g.idGroup IN (
                SELECT idGroup 
                FROM teacherGroupsSubjects 
                WHERE idTeacher = ? 
                AND idSubject = ?
            )
            ORDER BY g.grade, g.group_, ui.lastnamePa, ui.lastnameMa, ui.names";
    $stmtStudents = $conexion->prepare($sql);
    $params = array_merge($groupIds, [$selectedYear, $idTeacher, $idSubject, $idTeacher, $idSubject]);
    $stmtStudents->bind_param(str_repeat('i', count($groupIds)) . 'iiiii', ...$params);
    $stmtStudents->execute();
    $resultStudents = $stmtStudents->get_result();
    while ($row = $resultStudents->fetch_assoc()) {
        $students[] = $row;
    }
    $stmtStudents->close();
}

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
$schoolQuarters = [];
$currentQuarter = null;
$currentDate = date('Y-m-d');
while ($quarter = $resultQuarters->fetch_assoc()) {
    $schoolQuarters[] = $quarter;
    if ($quarter['startDate'] && $quarter['endDate']) {
        if ($currentDate >= $quarter['startDate'] && $currentDate <= $quarter['endDate']) {
            $currentQuarter = $quarter;
        }
    }
}
$stmtQuarters->close();

if (!$currentQuarter && count($schoolQuarters) > 0) {
    $currentQuarter = $schoolQuarters[0];
}

$idSchoolQuarter = $currentQuarter ? $currentQuarter['idSchoolQuarter'] : null;

// --- Obtener promedios guardados ---
$studentAverages = [];
if ($selectedYear && $idSchoolQuarter) {
    $ids = array_column($students, 'idStudent');
    if (count($ids) > 0) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids)) . 'ii';
        $query = "SELECT idStudent, average FROM average WHERE idStudent IN ($in) AND idSchoolYear = ? AND idSchoolQuarter = ?";
        $stmtAverages = $conexion->prepare($query);
        $params = array_merge($ids, [$selectedYear, $idSchoolQuarter]);
        $stmtAverages->bind_param($types, ...$params);
        $stmtAverages->execute();
        $resultAverages = $stmtAverages->get_result();
        while ($row = $resultAverages->fetch_assoc()) {
            $studentAverages[$row['idStudent']] = $row['average'];
        }
        $stmtAverages->close();
    }
}
?>
<!DOCTYPE html>
<!-- ANTIGRAVITY_VERSION: <?php echo date('YmdHis'); ?> -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones de la Materia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/teacher/gradeSubject.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="row d-flex" style="height: 100%; width: 100%; margin: 0; padding: 0;">
<div id="preloader">
    <img src="../img/logo.webp" alt="Cargando..." class="logo">
</div>
<!-- ASIDEBAR -->
<?php include "../layouts/asideTeacher.php"; ?>
<!-- END ASIDEBAR -->
<!-- MAIN CONTENT -->
<main class="flex-grow-1 p-0 w-100">
    <?php include "../layouts/headerTeacher.php"; ?>
    
    <!-- Header de la página -->
    <div class="container-fluid px-4 pt-5" style="padding-top: 4rem; height: auto;">
        <div class="row">
            <div class="col-12">
                <div class="page-header mb-3">
                    <h1 class="page-title">
                        <i class="bi bi-book me-3"></i>
                        <?php echo $subjectName ? htmlspecialchars($subjectName) : "Materia no encontrada"; ?>
                    </h1>
                    <p class="page-subtitle text-muted">
                        Gestión de calificaciones por criterios de evaluación
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container-fluid px-4">
        <!-- Panel de configuración -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="filter-card">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-gear me-2 text-primary"></i>
                                Período Actual
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-calendar-date me-2"></i>
                                        <strong>Año Escolar:</strong> 
                                        <?php echo substr($currentSchoolYear['startDate'], 0, 4); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        <strong>Trimestre:</strong> 
                                        <?php echo $currentQuarter ? htmlspecialchars($currentQuarter['name']) : 'No definido'; ?>
                                    </div>
                                </div>
                                <div class="col-md-12 d-flex align-items-end">
                                    <div class="d-flex gap-2 w-100">
                                        <button class="btn flex-fill" id="addColumnBtn">
                                            <i class="bi bi-plus-lg me-2"></i>
                                            Añadir Criterio
                                        </button>
                                        <button class="btn flex-fill" id="removeColumnBtn">
                                            <i class="bi bi-trash3-fill me-2"></i>
                                            Eliminar Criterio
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de calificaciones -->
        <div class="row">
            <div class="col-12">
                <div class="table-card">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-table me-2 text-success"></i>
                                Calificaciones por Criterios
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="dataTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="fw-semibold text-center">No.</th>
                                            <th class="fw-semibold text-center">Apellido Paterno</th>
                                            <th class="fw-semibold text-center">Apellido Materno</th>
                                            <th class="fw-semibold text-center">Nombres</th>
                                            <?php
$num_criterios = 3;
for ($c = 1; $c <= $num_criterios; $c++):
?>
                                            <th class="fw-semibold text-center criteria-header" data-col-index="<?php echo $c; ?>" style="cursor: pointer;" title="Doble clic para renombrar">
                                                <span class="criteria-name">C<?php echo $c; ?></span>
                                            </th>
                                            <?php
endfor; ?>
                                            <th class="fw-semibold text-center">Promedio</th>
                                        </tr>
                                        <tr id="percentageRow" class="bg-light">
                                            <th colspan="4" class="fw-semibold text-primary">
                                                <i class="bi bi-percent me-1"></i>
                                                Porcentajes (%)
                                            </th>
                                            <?php for ($c = 1; $c <= $num_criterios; $c++): ?>
                                            <th class="text-center">
                                                <input type="number" min="0" max="100" class="form-control form-control-sm percentage-input" id="C<?php echo $c; ?>-percentage" placeholder="0" style="text-align: center;">
                                            </th>
                                            <?php
endfor; ?>
                                            <th class="text-center text-muted">-</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($students as $i => $student): ?>
                                        <tr data-student-id="<?php echo htmlspecialchars($student['idStudent']); ?>">
                                            <td class="text-center"><?php echo $i + 1; ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($student['lastnamePa']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($student['lastnameMa']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($student['names']); ?></td>
                                            <?php for ($c = 1; $c <= $num_criterios; $c++): ?>
                                            <td class="text-center" style="width:10%"><input type="text" class="form-control form-control-sm grade-input text-center" data-col-index="<?php echo $c; ?>" data-criteria-id=""></td>
                                            <?php
    endfor; ?>
                                            <td class="promedio-cell text-center fw-bold">
                                                <?php
    $avg = isset($studentAverages[$student['idStudent']]) ? $studentAverages[$student['idStudent']] : null;
    if ($avg !== null && $avg !== '') {
        $avgValue = number_format($avg, 1);
        $colorClass = $avg >= 7 ? 'text-success' : ($avg >= 6 ? 'text-warning' : 'text-danger');
        echo "<span class='$colorClass'>$avgValue</span>";
    }
    else {
        echo '<span class="text-muted">-</span>';
    }
?>
                                            </td>
                                        </tr>
                                    <?php
endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Estado vacío -->
                            <div id="emptyState" class="text-center py-5" style="display: none;">
                                <div class="mb-3">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                </div>
                                <h5 class="text-muted">No hay estudiantes registrados</h5>
                                <p class="text-muted">Selecciona un año y trimestre para ver los estudiantes</p>
                            </div>
                        </div>
                        
                        <!-- Panel de acciones -->
                        <div class="card-footer bg-light border-0 d-flex justify-content-end">
                            <button type="button" id="guardar" class="btn btn-primary btn-lg px-4" onclick="event.preventDefault(); validarYGuardar();">
                                Guardar
                            </button>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    window.addEventListener('load', function() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.classList.add('loaded');
            setTimeout(() => preloader.remove(), 500);
        }
    });
</script>

<script>
    // Constantes PHP
    const currentSchoolYearId = <?php echo $selectedYear; ?>;
    const currentSchoolQuarterId = <?php echo $idSchoolQuarter ? $idSchoolQuarter : 'null'; ?>;
    const currentSchoolYearName = "<?php echo substr($currentSchoolYear['startDate'], 0, 4) . ' - ' . substr($currentSchoolYear['endDate'], 0, 4); ?>";
    const currentQuarterName = "<?php echo $currentQuarter ? htmlspecialchars($currentQuarter['name']) : 'No definido'; ?>";
    const idGroup = <?php echo json_encode($groupIds); ?>;

    // -- CREAR INPUT DE PORCENTAJE --
    function createPercentageInput() {
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '0';
        input.max = '100';
        input.className = 'form-control form-control-sm percentage-input';
        input.style.textAlign = 'center';
        input.placeholder = '0';
        input.addEventListener('change', validatePercentages);
        input.addEventListener('input', validatePercentages);
        return input;
    }

    // -- VALIDACIÓN DE PORCENTAJES --
    function validatePercentages() {
        const inputs = document.querySelectorAll('.percentage-input');
        let totalPercentage = 0;
        inputs.forEach(input => {
            totalPercentage += parseInt(input.value) || 0;
        });
        if (totalPercentage > 100) {
            const activeInput = document.activeElement;
            if (activeInput && activeInput.classList.contains('percentage-input')) {
                const exceso = totalPercentage - 100;
                activeInput.value = Math.max(0, parseInt(activeInput.value) - exceso);
            }
        }
    }

    // -- AÑADIR CRITERIO --
    document.getElementById('addColumnBtn').addEventListener('click', function () {
        const table = document.getElementById('dataTable');
        const headerRow = table.querySelector('thead tr');
        const percentageRow = document.getElementById('percentageRow');
        const bodyRows = table.querySelectorAll('tbody tr');

        const existingHeaders = Array.from(headerRow.children).filter(th => th.getAttribute('data-col-index'));
        let maxColIndex = 0;
        existingHeaders.forEach(th => {
            const colIdx = parseInt(th.getAttribute('data-col-index'));
            if (colIdx > maxColIndex) maxColIndex = colIdx;
        });

        const newColIndex = maxColIndex + 1;
        const newColumnName = `C${newColIndex}`;

        const newHeader = document.createElement('th');
        newHeader.className = 'fw-semibold text-center criteria-header';
        newHeader.setAttribute('data-col-index', newColIndex);
        newHeader.style.cursor = 'pointer';
        newHeader.style.width = "10%";
        newHeader.title = 'Doble clic para renombrar';
        const nameSpan = document.createElement('span');
        nameSpan.className = 'criteria-name';
        nameSpan.textContent = newColumnName;
        newHeader.appendChild(nameSpan);
        headerRow.insertBefore(newHeader, headerRow.children[headerRow.children.length - 1]);

        const newPercentageCell = document.createElement('th');
        const newPercentageInput = createPercentageInput();
        newPercentageInput.id = `C${newColIndex}-percentage`;
        newPercentageCell.appendChild(newPercentageInput);
        percentageRow.insertBefore(newPercentageCell, percentageRow.children[percentageRow.children.length - 1]);

        bodyRows.forEach(row => {
            const newCell = document.createElement('td');
            newCell.className = 'text-center';
            newCell.style.width = '10%';
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm grade-input text-center';
            input.setAttribute('data-col-index', newColIndex);
            input.setAttribute('data-criteria-id', '');
            newCell.appendChild(input);
            row.insertBefore(newCell, row.children[row.children.length - 1]);
        });

        validatePercentages();
        asignarCriteriaIdInputs();
        agregarValidacionCalificaciones();
    });

    // -- ELIMINAR CRITERIO --
    document.getElementById('removeColumnBtn').addEventListener('click', function () {
        const table = document.getElementById('dataTable');
        const headerRow = table.querySelector('thead tr');
        const criteriaHeaders = Array.from(headerRow.children).filter(th => th.getAttribute('data-col-index'));

        if (criteriaHeaders.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Sin criterios', text: 'No hay criterios para eliminar.' });
            return;
        }

        let optionsHtml = '<div>';
        criteriaHeaders.forEach((header) => {
            const colIndex = header.getAttribute('data-col-index');
            const nameSpan = header.querySelector('.criteria-name');
            const name = nameSpan ? nameSpan.textContent.trim() : `C${colIndex}`;
            optionsHtml += `<div style="margin: 10px 0;"><input type="radio" name="criterioEliminar" value="${colIndex}" id="criterio_${colIndex}"><label for="criterio_${colIndex}" style="cursor: pointer; margin-left: 8px;">${name}</label></div>`;
        });
        optionsHtml += '</div>';

        Swal.fire({
            title: 'Eliminar Criterio',
            html: optionsHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            preConfirm: () => {
                const selected = document.querySelector('input[name="criterioEliminar"]:checked');
                if (!selected) { Swal.showValidationMessage('Por favor selecciona un criterio'); return false; }
                return selected.value;
            }
        }).then((result) => {
            if (result.isConfirmed) eliminateCriterioByIndex(parseInt(result.value));
        });
    });

    function eliminateCriterioByIndex(colIndex) {
        const table = document.getElementById('dataTable');
        const headerRow = table.querySelector('thead tr');
        const percentageRow = document.getElementById('percentageRow');
        const bodyRows = table.querySelectorAll('tbody tr');

        const headerToRemove = Array.from(headerRow.children).find(th => th.getAttribute('data-col-index') == colIndex);
        if (!headerToRemove) return;
        const headerIndex = Array.from(headerRow.children).indexOf(headerToRemove);

        headerRow.removeChild(headerToRemove);
        if (percentageRow.children[headerIndex]) percentageRow.removeChild(percentageRow.children[headerIndex]);
        bodyRows.forEach(row => { if (row.children[headerIndex]) row.removeChild(row.children[headerIndex]); });

        validatePercentages();
        asignarCriteriaIdInputs();
    }

    // -- CARGAR ESTUDIANTES --
    function loadStudentsForCurrentPeriod() {
        const idSubject = <?php echo $idSubject; ?>;
        if (currentSchoolQuarterId && currentSchoolYearId) {
            fetch(`getStudentsBySubject.php?idSubject=${idSubject}&idSchoolYear=${currentSchoolYearId}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#dataTable tbody');
                    tbody.innerHTML = '';
                    if (data.success && Array.isArray(data.students)) {
                        data.students.forEach((student, index) => {
                            const row = document.createElement('tr');
                            row.setAttribute('data-student-id', student.idStudent);

                            const headerRow = document.querySelector('#dataTable thead tr');
                            const criteriaHeaders = Array.from(headerRow.children).filter(th => th.getAttribute('data-col-index'));
                            const numCriterias = criteriaHeaders.length;

                            let rowHTML = `
                                <td class="text-center">${index + 1}</td>
                                <td class="text-center">${student.lastnamePa}</td>
                                <td class="text-center">${student.lastnameMa}</td>
                                <td class="text-center">${student.names}</td>
                            `;
                            for (let i = 1; i <= Math.max(numCriterias, 3); i++) {
                                rowHTML += `<td class="text-center" style="width:10%"><input type="text" class="form-control form-control-sm grade-input text-center" data-col-index="${i}" data-criteria-id=""></td>`;
                            }
                            rowHTML += `<td class="promedio-cell text-center fw-bold">-</td>`;
                            row.innerHTML = rowHTML;
                            tbody.appendChild(row);
                        });
                        agregarValidacionCalificaciones();
                    }
                });
        } else {
            const tbody = document.querySelector('#dataTable tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="100%" class="text-center">No hay período escolar configurado.</td></tr>';
        }
    }

    function resetTableStructure() {
        const table = document.getElementById('dataTable');
        const headerRow = table.querySelector('thead tr:first-child');
        const percentageRow = document.getElementById('percentageRow');
        const bodyRows = table.querySelectorAll('tbody tr');
        const currentCriteria = Array.from(headerRow.children).filter(th => th.getAttribute('data-col-index')).length;
        const criteriaToRemove = Math.max(0, currentCriteria - 3);
        for (let i = 0; i < criteriaToRemove; i++) {
            headerRow.removeChild(headerRow.children[headerRow.children.length - 2]);
            percentageRow.removeChild(percentageRow.children[percentageRow.children.length - 2]);
            bodyRows.forEach(row => { if (row.children.length > 10) row.removeChild(row.children[row.children.length - 2]); });
        }
        document.querySelectorAll('.percentage-input').forEach(input => {
            input.addEventListener('change', validatePercentages);
            input.addEventListener('input', validatePercentages);
        });
    }

    function loadEvaluationCriteria(idSubject, idSchoolYear, idSchoolQuarter, callback) {
        fetch(`getEvaluationCriteria.php?idSubject=${idSubject}&idSchoolYear=${idSchoolYear}&idSchoolQuarter=${idSchoolQuarter}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const currentColumns = document.querySelectorAll('.percentage-input').length;
                    const neededColumns = data.data.length;
                    if (neededColumns > currentColumns) {
                        for (let i = 0; i < neededColumns - currentColumns; i++) {
                            document.getElementById('addColumnBtn').click();
                        }
                    }
                    setTimeout(() => {
                        data.data.forEach((criteria, index) => {
                            const columnNumber = index + 1;
                            const percentageInput = document.querySelector(`#C${columnNumber}-percentage`);
                            const header = document.querySelector(`.criteria-header[data-col-index='${columnNumber}']`);
                            if (percentageInput) {
                                percentageInput.value = criteria.percentage;
                                percentageInput.setAttribute('data-criteria-id', criteria.idEvalCriteria);
                                document.querySelectorAll(`.grade-input[data-col-index='${columnNumber}']`).forEach(input => {
                                    input.setAttribute('data-criteria-id', criteria.idEvalCriteria);
                                });
                            }
                            if (header) {
                                const nameSpan = header.querySelector('.criteria-name');
                                if (nameSpan && criteria.name) {
                                    nameSpan.textContent = criteria.name;
                                    header.setAttribute('data-criteria-name', criteria.name);
                                }
                            }
                        });
                        document.querySelectorAll('.grade-input').forEach(input => { input.disabled = false; });
                        asignarCriteriaIdInputs();
                        if (typeof callback === 'function') callback();
                    }, 150);
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error al cargar los criterios de evaluación' }));
    }

    function loadGrades(idSubject, idSchoolYear, idSchoolQuarter) {
        fetch(`getGrades.php?idSubject=${idSubject}&idSchoolYear=${idSchoolYear}&idSchoolQuarter=${idSchoolQuarter}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const rows = document.querySelectorAll('#dataTable tbody tr');
                    rows.forEach((row) => {
                        const idStudent = row.getAttribute('data-student-id');
                        if (!idStudent) return;
                        const inputs = row.querySelectorAll('.grade-input');
                        inputs.forEach(input => input.value = '');
                        const studentGradesData = data.data[idStudent];
                        if (studentGradesData) {
                            inputs.forEach((input) => {
                                const idEvalCriteria = input.getAttribute('data-criteria-id');
                                if (idEvalCriteria && studentGradesData[idEvalCriteria] !== undefined) {
                                    input.value = studentGradesData[idEvalCriteria];
                                }
                            });
                        }
                        calcularPromedioFila(row);
                    });
                    agregarValidacionCalificaciones();
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const idSubject = <?php echo $idSubject; ?>;
        if (idSubject > 0 && currentSchoolYearId && currentSchoolQuarterId) {
            loadStudentsForCurrentPeriod();
            loadEvaluationCriteria(idSubject, currentSchoolYearId, currentSchoolQuarterId, function() {
                loadGrades(idSubject, currentSchoolYearId, currentSchoolQuarterId);
            });
        }
    });

    // --- VALIDAR CRITERIOS DUPLICADOS ---
    function validarCriteriosDuplicados(criterias) {
        const nombres = criterias.map(c => c.name.toLowerCase().trim());
        const duplicados = nombres.filter((item, index) => nombres.indexOf(item) !== index);
        if (duplicados.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Criterios Duplicados',
                html: `<p>Ya existe un criterio llamado <strong>"${duplicados[0]}"</strong></p><p>Por favor, usa nombres únicos para cada criterio.</p>`,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        return true;
    }

    // --- GUARDAR CRITERIOS ---
    function guardarCriteriosEvaluacion(idSubject, idSchoolYear, idSchoolQuarter) {
        const criterias = [];
        document.querySelectorAll('.criteria-header').forEach((header, idx) => {
            const colIndex = parseInt(header.getAttribute('data-col-index')) || (idx + 1);
            const nameSpan = header.querySelector('.criteria-name');
            let name = nameSpan ? nameSpan.textContent.trim() : `C${colIndex}`;
            if (!name) name = `C${colIndex}`;
            const select = document.getElementById(`C${colIndex}-percentage`);
            const criteriaId = select ? select.getAttribute('data-criteria-id') : null;
            criterias.push({ index: colIndex, name: name, percentage: 0, idEvalCriteria: criteriaId });
        });
        document.querySelectorAll('.percentage-input').forEach((input) => {
            const colIndex = parseInt(input.id.match(/\d+/)[0]);
            const criteriaToUpdate = criterias.find(c => c.index === colIndex);
            if (criteriaToUpdate) criteriaToUpdate.percentage = parseInt(input.value) || 0;
        });

        if (!validarCriteriosDuplicados(criterias)) {
            const error = new Error('Criterios duplicados');
            error.isDuplicateError = true;
            return Promise.reject(error);
        }

        return fetch('saveEvaluationCriteria.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idSubject, idSchoolYear, idSchoolQuarter, criterias })
        }).then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Error al guardar criterios');
            return data;
        });
    }

    // =====================================================
    // FUNCIÓN PRINCIPAL: VALIDAR Y GUARDAR
    // Llamada directamente desde onclick del botón Guardar
    // =====================================================
    function validarYGuardar() {
        // DIAGNÓSTICO: confirmar que la función se ejecuta
        const allInputs = document.querySelectorAll('.grade-input');
        console.error('=== validarYGuardar LLAMADA ===')
        console.error('Total inputs encontrados:', allInputs.length);
        allInputs.forEach((inp, i) => console.error(`  Input[${i}] value="${inp.value}"`))
        // FIN DIAGNÓSTICO
        const idSubject = <?php echo $idSubject; ?>;
        const idSchoolYear = currentSchoolYearId;
        const idSchoolQuarter = currentSchoolQuarterId;

        if (!idSchoolYear || !idSchoolQuarter) {
            Swal.fire({ icon: 'warning', title: 'Sin período', text: 'No hay año escolar o trimestre configurado.' });
            return;
        }

        // VALIDAR RANGO 6-10
        const inputs = document.querySelectorAll('.grade-input');
        const errores = [];

        inputs.forEach(input => {
            const valor = input.value.trim();
            if (valor === '') {
                input.style.borderColor = '';
                input.style.backgroundColor = '';
                return;
            }
            const numero = parseFloat(valor);
            if (isNaN(numero) || numero < 6 || numero > 10) {
                const fila = input.closest('tr');
                const estudiante = fila && fila.cells[1] ? fila.cells[1].textContent.trim() : 'Alumno';
                errores.push(`${estudiante}: ${valor}`);
                input.style.borderColor = '#dc3545';
                input.style.backgroundColor = '#fff5f5';
            } else {
                input.style.borderColor = '';
                input.style.backgroundColor = '';
            }
        });

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: '¡Calificaciones fuera de rango!',
                html: `<p>Las calificaciones deben estar entre <strong>6 y 10</strong>.</p>
                       <p>Se encontraron <strong>${errores.length}</strong> valor(es) inválido(s):</p>
                       <ul style="text-align:left; max-height:200px; overflow-y:auto;">
                         ${errores.map(e => `<li>${e}</li>`).join('')}
                       </ul>`,
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#dc3545'
            });
            return; // NO GUARDAR
        }

        // TODO OK - GUARDAR
        guardarCriteriosEvaluacion(idSubject, idSchoolYear, idSchoolQuarter)
            .then(res => {
                if (!res.success) throw new Error(res.message);
                return fetch('saveGrades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        idSubject, idSchoolYear, idSchoolQuarter,
                        grades: recolectarCalificaciones()
                    })
                });
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Calificaciones guardadas correctamente',
                        confirmButtonColor: '#6f42c1'
                    });
                    loadEvaluationCriteria(idSubject, idSchoolYear, idSchoolQuarter, function() {
                        loadGrades(idSubject, idSchoolYear, idSchoolQuarter);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(e => {
                if (!e.isDuplicateError) {
                    Swal.fire({ icon: 'error', title: 'Error', text: e.message });
                }
            });
    }

    function recolectarCalificaciones() {
        const grades = [];
        document.querySelectorAll('#dataTable tbody tr').forEach(row => {
            const idStudent = row.getAttribute('data-student-id');
            if (!idStudent) return;
            const studentGrades = { idStudent, grades: {} };
            row.querySelectorAll('.grade-input').forEach(input => {
                const colIndex = input.getAttribute('data-col-index');
                const valor = input.value.trim();
                if (valor !== '') {
                    const select = document.getElementById(`C${colIndex}-percentage`);
                    const idEvalCriteria = input.getAttribute('data-criteria-id');
                    if (select && select.value && idEvalCriteria) {
                        studentGrades.grades[`C${colIndex}`] = {
                            grade: valor,
                            idEvalCriteria,
                            percentage: select.value
                        };
                    }
                }
            });
            grades.push(studentGrades);
        });
        return grades;
    }

    // -- VALIDACIÓN EN TIEMPO REAL --
    function validarCalificacion(input) {
        const valor = input.value.trim();
        if (valor === '') {
            input.classList.remove('is-invalid');
            input.style.borderColor = '';
            input.style.backgroundColor = '';
            return true;
        }
        const numero = parseFloat(valor);
        if (isNaN(numero) || numero < 6 || numero > 10) {
            input.classList.add('is-invalid');
            input.style.borderColor = '#dc3545';
            input.style.backgroundColor = '#fff5f5';
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.style.borderColor = '';
            input.style.backgroundColor = '';
            return true;
        }
    }

    function agregarValidacionCalificaciones() {
        document.querySelectorAll('.grade-input').forEach(input => {
            input.addEventListener('blur', function() { validarCalificacion(this); });
            input.addEventListener('input', function() { validarCalificacion(this); });
        });
    }

    function asignarCriteriaIdInputs() {
        document.querySelectorAll('.percentage-input').forEach((input, idx) => {
            const idEvalCriteria = input.getAttribute('data-criteria-id');
            const colIndex = idx + 1;
            document.querySelectorAll(`.grade-input[data-col-index='${colIndex}']`).forEach(gradeInput => {
                if (idEvalCriteria) gradeInput.setAttribute('data-criteria-id', idEvalCriteria);
            });
        });
    }

    // --- PROMEDIO DINÁMICO ---
    function calcularPromedioFila(row) {
        let sum = 0;
        let sumPercent = 0;
        row.querySelectorAll('.grade-input').forEach(input => {
            const grade = input.value.trim() !== '' ? parseFloat(input.value) : null;
            const colIndex = input.getAttribute('data-col-index');
            const select = document.getElementById(`C${colIndex}-percentage`);
            const percent = select && select.value ? parseFloat(select.value) : 0;
            if (!isNaN(percent)) {
                sumPercent += percent;
                if (grade !== null && !isNaN(grade)) sum += grade * (percent / 100);
            }
        });
        const promedioCell = row.querySelector('.promedio-cell');
        if (sumPercent > 0) {
            const promedio = (sumPercent === 100) ? sum : (sum / (sumPercent / 100));
            const promedioRedondeado = Math.ceil(promedio * 10) / 10;
            promedioCell.textContent = promedioRedondeado.toFixed(1);
            promedioCell.setAttribute('data-calculated-average', promedioRedondeado.toString());
        } else {
            promedioCell.textContent = '-';
            promedioCell.removeAttribute('data-calculated-average');
        }
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('grade-input') || e.target.classList.contains('percentage-input')) {
            document.querySelectorAll('#dataTable tbody tr').forEach(row => calcularPromedioFila(row));
        }
    });

    // --- RENOMBRAR CRITERIOS (doble clic) ---
    document.addEventListener('dblclick', function(e) {
        const header = e.target.closest('.criteria-header');
        if (!header) return;
        const nameSpan = header.querySelector('.criteria-name');
        if (!nameSpan) return;
        const currentName = nameSpan.textContent.trim();
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentName;
        input.className = 'form-control form-control-sm';
        input.style.maxWidth = '120px';
        nameSpan.replaceWith(input);
        input.focus();
        input.select();
        let isEditing = true;
        const saveEdit = () => {
            if (!isEditing) return;
            isEditing = false;
            const newName = input.value.trim() || currentName;
            const newSpan = document.createElement('span');
            newSpan.className = 'criteria-name';
            newSpan.textContent = newName;
            input.replaceWith(newSpan);
            header.setAttribute('data-criteria-name', newName);
        };
        input.addEventListener('blur', saveEdit);
        input.addEventListener('keypress', (e) => { if (e.key === 'Enter') saveEdit(); });
    });
</script>

</body>
</html>
