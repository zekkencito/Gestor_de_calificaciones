<?php
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

// DEBUG: imprime los grupos y el año escolar para verificar qué se consulta
file_put_contents(__DIR__.'/debug_grupos.txt', "Grupos: ".json_encode($groupIds)." | Año: ".$selectedYear."\n", FILE_APPEND);

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

// DEBUG: imprime los alumnos encontrados (forzando creación del archivo)
file_put_contents(__DIR__.'/debug_alumnos.txt', var_export($students, true) . "\n", FILE_APPEND);

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
    // Detectar el trimestre actual basado en la fecha
    if ($quarter['startDate'] && $quarter['endDate']) {
        if ($currentDate >= $quarter['startDate'] && $currentDate <= $quarter['endDate']) {
            $currentQuarter = $quarter;
        }
    }
}
$stmtQuarters->close();

// Si no se encontró trimestre actual por fecha, usar el primero disponible
if (!$currentQuarter && count($schoolQuarters) > 0) {
    $currentQuarter = $schoolQuarters[0];
}

$idSchoolQuarter = $currentQuarter ? $currentQuarter['idSchoolQuarter'] : null;

// --- Obtener promedios guardados para los estudiantes en este trimestre y ciclo ---
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
<!-- update test -->

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
                                            <button class="btn  flex-fill" id="removeColumnBtn">
                                                <i class="bi bi-trash3-fill me-2"></i>
                                                Eliminar
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
                                                <th class="fw-semibold">No.</th>
                                                <th class="fw-semibold">Apellido Paterno</th>
                                                <th class="fw-semibold">Apellido Materno</th>
                                                <th class="fw-semibold">Nombres</th>
                                                <th class="fw-semibold">Grado</th>
                                                <th class="fw-semibold">Grupo</th>
                                                <?php
                                                    // Determinar cuántos criterios hay por las columnas C en el header
                                                    $num_criterios = 0;
                                                    foreach ([183,184,185,/*...*/] as $col) {
                                                        // Solo cuenta las columnas que empiezan con C
                                                        // (en producción, deberías obtener esto dinámicamente del backend)
                                                        $num_criterios++;
                                                    }
                                                    // Por ahora, usa 3 como mínimo (C1, C2, C3)
                                                    $num_criterios = max(3, $num_criterios);
                                                    for ($c = 1; $c <= $num_criterios; $c++):
                                                ?>
                                                <th class="fw-semibold text-center">C<?php echo $c; ?></th>
                                                <?php endfor; ?>
                                                <th class="fw-semibold text-center">Promedio</th>
                                            </tr>
                                            <tr id="percentageRow" class="bg-light">
                                                <th colspan="4" class="fw-semibold text-primary">
                                                    <i class="bi bi-percent me-1"></i>
                                                    Porcentajes (%)
                                                </th> 
                                                <th></th>
                                                <th></th>
                                                <?php for ($c = 1; $c <= $num_criterios; $c++): ?>
                                                <th class="text-center">
                                                    <select class="form-select form-select-sm percentage-select" id="C<?php echo $c; ?>-percentage"></select>
                                                </th>
                                                <?php endfor; ?>
                                                <th class="text-center text-muted">-</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $i => $student): ?>
                    <tr data-student-id="<?php echo htmlspecialchars($student['idStudent']); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($student['lastnamePa']); ?></td>
                        <td><?php echo htmlspecialchars($student['lastnameMa']); ?></td>
                        <td><?php echo htmlspecialchars($student['names']); ?></td>
                        <td><?php echo htmlspecialchars($student['grade']); ?>°</td>
                        <td><?php echo htmlspecialchars($student['group_']); ?></td>
                        <?php
                            for ($c = 1; $c <= $num_criterios; $c++):
                        ?>
                        <td style="width:10%"><input type="text" class="form-control grade-input" data-col-index="<?php echo $c; ?>" data-criteria-id=""></td>
                        <?php endfor; ?>
                                            <td class="promedio-cell text-center fw-bold">
                                                <?php
                                                    $avg = isset($studentAverages[$student['idStudent']]) ? $studentAverages[$student['idStudent']] : null;
                                                    if ($avg !== null && $avg !== '') {
                                                        $avgValue = number_format($avg, 1);
                                                        $colorClass = $avg >= 7 ? 'text-success' : ($avg >= 6 ? 'text-warning' : 'text-danger');
                                                        echo "<span class='$colorClass'>$avgValue</span>";
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
                            <button id="guardar" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-floppy2-fill me-2"></i>
                                Guardar
                            </button>
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
    <script>
        // Constantes PHP para año y trimestre actual
        const currentSchoolYearId = <?php echo $selectedYear; ?>;
        const currentSchoolQuarterId = <?php echo $idSchoolQuarter ? $idSchoolQuarter : 'null'; ?>;
        const currentSchoolYearName = "<?php echo substr($currentSchoolYear['startDate'], 0, 4) . ' - ' . substr($currentSchoolYear['endDate'], 0, 4); ?>";
        const currentQuarterName = "<?php echo $currentQuarter ? htmlspecialchars($currentQuarter['name']) : 'No definido'; ?>";
        
        // -- OPCIONES DE PORCENTAJE --
    const percentageOptions = [ 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100];

    // -- FUNCIÓN PARA AGREGAR OPCIONES A UN SELECT --
    function fillPercentageSelect(select) {
        select.innerHTML = ''; 
        // Agregar opción vacía por defecto
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Seleccionar';
        select.appendChild(defaultOption);
        
        percentageOptions.forEach(value => {
            const option = document.createElement('option');
            option.value = value.toString(); // <-- valor como string
            option.textContent = `${value}%`;
            select.appendChild(option);
        });
        select.addEventListener('change', validatePercentages); 
    }

    // -- VALIDACIÓN DE PORCENTAJES --
    function validatePercentages() {
    const selects = document.querySelectorAll('.percentage-select');
    let totalPercentage = 0;

    // Sumar todos los valores
    selects.forEach(select => {
        totalPercentage += parseInt(select.value) || 0;
    });

    if (totalPercentage > 100) {
        alert(`El total de los porcentajes es ${totalPercentage}%. Se ajustará automáticamente.`);

        // Determinar el último select modificado
        const lastChanged = [...selects].find(select => select === document.activeElement);

        if (lastChanged) {
            const exceso = totalPercentage - 100;
            lastChanged.value = Math.max(0, parseInt(lastChanged.value) - exceso);
        }
    }
}

    // -- AÑADIR CELDA --
    document.getElementById('addColumnBtn').addEventListener('click', function () {
    const table = document.getElementById('dataTable');
    const headerRow = table.querySelector('thead tr');
    const percentageRow = document.getElementById('percentageRow');
    const bodyRows = table.querySelectorAll('tbody tr');

    const existingColumns = Array.from(headerRow.children).filter(th => th.textContent.startsWith('C')).length;
    const newColIndex = existingColumns + 1;
    const newColumnName = `C${newColIndex}`;

    // Nueva cabecera
    const newHeader = document.createElement('th');
    newHeader.textContent = newColumnName;
    newHeader.style.width = "10%";
    headerRow.insertBefore(newHeader, headerRow.children[headerRow.children.length - 1]);

    // Nueva celda de porcentaje
    const newPercentageCell = document.createElement('th');
    const newPercentageSelect = document.createElement('select');
    newPercentageSelect.className = 'form-select percentage-select';
    newPercentageSelect.id = `C${newColIndex}-percentage`;
    fillPercentageSelect(newPercentageSelect);
    newPercentageSelect.value = ''; // Valor por defecto: "Seleccionar"
    newPercentageCell.appendChild(newPercentageSelect);
    percentageRow.insertBefore(newPercentageCell, percentageRow.children[percentageRow.children.length - 1]);

    // Nueva celda en cada fila del cuerpo
    bodyRows.forEach(row => {
        const newCell = document.createElement('td');
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control grade-input';
        input.setAttribute('data-col-index', newColIndex); // ¡Ahora sí es correlativo!
        input.setAttribute('data-criteria-id', '');
        newCell.appendChild(input);
        row.insertBefore(newCell, row.children[row.children.length - 1]);
    });

    validatePercentages();
    asignarCriteriaIdInputs();
});

    // -- ELIMINAR CELDA --
    document.getElementById('removeColumnBtn').addEventListener('click', function () {
        const table = document.getElementById('dataTable');
        const headerRow = table.querySelector('thead tr');
        const percentageRow = document.getElementById('percentageRow');
        const bodyRows = table.querySelectorAll('tbody tr');

        const columnHeaders = Array.from(headerRow.children).filter(th => th.textContent.startsWith('C'));

        if (columnHeaders.length > 0) {
            const lastColumnIndex = Array.from(headerRow.children).indexOf(columnHeaders[columnHeaders.length - 1]);

            headerRow.removeChild(columnHeaders[columnHeaders.length - 1]);
            percentageRow.removeChild(percentageRow.children[percentageRow.children.length - 2]);

            bodyRows.forEach(row => {
                row.removeChild(row.children[lastColumnIndex]);
            });

            validatePercentages();
            asignarCriteriaIdInputs();
        } else {
            alert("⚠️ No hay más columnas para eliminar.");
        }
    });

    // -- CREAR FILA DE PORCENTAJES SI NO EXISTE --
    function createPercentageRow() {
        const table = document.getElementById('dataTable');
        const thead = table.querySelector('thead');

        const percentageRow = document.createElement('tr');
        percentageRow.id = 'percentageRow';

        const emptyCells = document.createElement('th');
        emptyCells.colSpan = 4;
        percentageRow.appendChild(emptyCells);

        const emptyCells2 = document.createElement('th');
        emptyCells2.colSpan = 1;
        percentageRow.appendChild(emptyCells2);

        const emptyCells3 = document.createElement('th');
        emptyCells3.colSpan = 1;
        percentageRow.appendChild(emptyCells3);

        const promedioCell = document.createElement('th');
        promedioCell.textContent = '-'; 
        percentageRow.appendChild(promedioCell);

        thead.insertBefore(percentageRow, thead.children[1]);

        return percentageRow;
    }

    // -- INICIALIZACIÓN AL CARGAR LA PÁGINA --

    const idGroup = <?php echo json_encode($groupIds); ?>;
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.querySelector('#dataTable tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">Cargando estudiantes del período actual...</td></tr>';
        }
    });

    // Event listener removido - ahora se carga automáticamente
    // document.getElementById('schoolQuarterSelect').addEventListener('change', function() {
    function loadStudentsForCurrentPeriod() {
        const selectedQuarter = currentSchoolQuarterId;
        const selectedYear = currentSchoolYearId;
        const idSubject = <?php echo $idSubject; ?>;
        
        if (selectedQuarter && selectedYear) {
            fetch(`getStudentsBySubject.php?idSubject=${idSubject}&idSchoolYear=${selectedYear}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#dataTable tbody');
                    tbody.innerHTML = '';
                    if (data.success && Array.isArray(data.students)) {
                        data.students.forEach((student, index) => {
                            const row = document.createElement('tr');
                            row.setAttribute('data-student-id', student.idStudent);
                            
                            // Crear las celdas básicas
                            let rowHTML = `
                                <td>${index + 1}</td>
                                <td>${student.lastnamePa}</td>
                                <td>${student.lastnameMa}</td>
                                <td>${student.names}</td>
                                <td>${student.grade}°</td>
                                <td>${student.group_}</td>
                            `;

                            // Obtener el número actual de columnas de calificaciones
                            const headerRow = document.querySelector('#dataTable thead tr');
                            const numCriterias = Array.from(headerRow.children)
                                .filter(th => th.textContent.startsWith('C')).length;

                            // Añadir celdas para cada criterio
                            for (let i = 1; i <= Math.max(numCriterias, 3); i++) {
                                rowHTML += `<td style="width:10%"><input type="text" class="form-control grade-input" data-col-index="${i}" data-criteria-id=""></td>`;
                            }

                            // Añadir celda de promedio
                            rowHTML += `<td class="promedio-cell">-</td>`;
                            
                            row.innerHTML = rowHTML;
                            tbody.appendChild(row);
                        });
                    }
                });
        } else {
            const tbody = document.querySelector('#dataTable tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="100%" class="text-center">No hay período escolar configurado.</td></tr>';
            }
        }
    }

    // Función para resetear la estructura de la tabla a 3 columnas
    function resetTableStructure() {
        const table = document.getElementById('dataTable');
        const headerRow = table.querySelector('thead tr:first-child');
        const percentageRow = document.getElementById('percentageRow');
        const bodyRows = table.querySelectorAll('tbody tr');

        // Mantener solo las primeras 5 columnas fijas (No., Paterno, Materno, Nombres, Grupo, Grado)
        while (headerRow.children.length > 11) { // 5 fijas + 3 criterios + promedio
            headerRow.removeChild(headerRow.children[headerRow.children.length - 2]); // Antes del promedio
            percentageRow.removeChild(percentageRow.children[percentageRow.children.length - 2]); // Antes del promedio
            bodyRows.forEach(row => {
                row.removeChild(row.children[row.children.length - 2]); // Antes del promedio
            });
        }

        // Asegurarse de que los selects de porcentaje estén inicializados con valor vacío
        document.querySelectorAll('.percentage-select').forEach(select => {
            fillPercentageSelect(select);
            select.value = '';
        });
    }

    // Función para obtener los criterios guardados
    function loadEvaluationCriteria(idSubject, idSchoolYear, idSchoolQuarter, callback) {
        // Primero resetear la tabla a su estructura base (3 columnas)
        resetTableStructure();
        
        fetch(`getEvaluationCriteria.php?idSubject=${idSubject}&idSchoolYear=${idSchoolYear}&idSchoolQuarter=${idSchoolQuarter}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.length > 0) {
                        // Asegurarse de que hay suficientes columnas para los criterios
                        const currentColumns = document.querySelectorAll('.percentage-select').length;
                        const neededColumns = data.data.length;
                        
                        // Añadir columnas si es necesario
                        if (neededColumns > currentColumns) {
                            const difference = neededColumns - currentColumns;
                            for (let i = 0; i < difference; i++) {
                                document.getElementById('addColumnBtn').click();
                            }
                        }

                        // Esperar un momento para que se creen los elementos
                        setTimeout(() => {
                            // Establecer los porcentajes guardados
                            data.data.forEach((criteria, index) => {
                                const columnNumber = index + 1;
                                const percentageSelect = document.querySelector(`#C${columnNumber}-percentage`);
                                if (percentageSelect) {
                                    percentageSelect.value = criteria.percentage.toString(); // <-- valor como string
                                    percentageSelect.setAttribute('data-criteria-id', criteria.idEvalCriteria);
                                    // Asignar data-criteria-id a todos los inputs de la columna correspondiente
                                    document.querySelectorAll(`.grade-input[data-col-index='${columnNumber}']`).forEach(input => {
                                        input.setAttribute('data-criteria-id', criteria.idEvalCriteria);
                                    });
                                }
                            });
                            
                            // Habilitar los inputs
                            const inputs = document.querySelectorAll('.grade-input');
                            inputs.forEach(input => {
                                input.disabled = false;
                            });
                            asignarCriteriaIdInputs();
                            if (typeof callback === 'function') callback();
                        }, 150);
                    }
                }
            })
            .catch(error => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error al cargar los criterios de evaluación' });
            });
    }

    // Función para cargar las calificaciones
    function loadGrades(idSubject, idSchoolYear, idSchoolQuarter) {
        // 1. Cargar calificaciones normales
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
                        // Recalcular el promedio después de cargar las calificaciones
                        calcularPromedioFila(row);
                    });
                }
            });

        // 2. Cargar promedios guardados y actualizar la tabla solo si es necesario
        // Comentado temporalmente para que prevalezcan los promedios calculados en el frontend
        /*
        fetch(`getAverages.php?idSubject=${idSubject}&idSchoolYear=${idSchoolYear}&idSchoolQuarter=${idSchoolQuarter}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const averages = data.data;
                    document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                        const idStudent = row.getAttribute('data-student-id');
                        const promedioCell = row.querySelector('.promedio-cell');
                        if (promedioCell) {
                            if (averages[idStudent] !== undefined && averages[idStudent] !== null) {
                                promedioCell.textContent = Number(averages[idStudent]).toFixed(2);
                            } else {
                                promedioCell.textContent = '-';
                            }
                        }
                    });
                }
            });
        */
    }

    // Función para cargar período actual - event listener removido
    // document.getElementById('schoolQuarterSelect').addEventListener('change', function() {
    function reloadCurrentPeriod() {
        const selectedQuarter = currentSchoolQuarterId;
        const selectedYear = currentSchoolYearId;
        const idSubject = <?php echo $idSubject; ?>;
        
        if (selectedQuarter && selectedYear) {
            // Primero cargar los estudiantes
            loadStudentsForCurrentPeriod();
            // Luego cargar criterios y calificaciones
            loadEvaluationCriteria(idSubject, selectedYear, selectedQuarter, function() {
                loadGrades(idSubject, selectedYear, selectedQuarter);
            });
        } else {
            resetTableStructure();
            document.querySelectorAll('.grade-input').forEach(input => {
                input.value = '';
            });
        }
    }

    // Asegura que el botón de guardar calificaciones siempre tenga el listener
    function asignarListenerGuardarCalificaciones(idSubject, idSchoolYear, idSchoolQuarter) {
        const btn = document.getElementById('guardar');
        if (btn) {
            // Elimina listeners previos para evitar duplicados
            btn.replaceWith(btn.cloneNode(true));
            const newBtn = document.getElementById('guardar');
            newBtn.addEventListener('click', function() {
                const currentYear = currentSchoolYearId;
                const currentQuarter = currentSchoolQuarterId;
                guardarCalificaciones(idSubject, currentYear, currentQuarter);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Asigna el listener al cargar la página
        asignarListenerGuardarCalificaciones(<?php echo $idSubject; ?>, currentSchoolYearId, currentSchoolQuarterId);
        
        // Cargar automáticamente los estudiantes, criterios y calificaciones del período actual
        const idSubject = <?php echo $idSubject; ?>;
        if (idSubject > 0 && currentSchoolYearId && currentSchoolQuarterId) {
            // Primero cargar los estudiantes
            loadStudentsForCurrentPeriod();
            // Luego cargar criterios y calificaciones
            loadEvaluationCriteria(idSubject, currentSchoolYearId, currentSchoolQuarterId, function() {
                loadGrades(idSubject, currentSchoolYearId, currentSchoolQuarterId);
            });
        }
    });

    // Llama a esta función después de cualquier recarga de tabla o criterios
    // Ejemplo: después de loadEvaluationCriteria(...)
    // asignarListenerGuardarCalificaciones(idSubject, idSchoolYear, idSchoolQuarter);

    // Modificar el botón de guardar
    function guardarCalificaciones(idSubject, idSchoolYear, idSchoolQuarter) {
        if (!idSchoolYear) {
            Swal.fire({ icon: 'warning', title: 'Año escolar requerido', text: 'Por favor selecciona un año escolar antes de guardar.' });
            return;
        }
        if (!idSchoolQuarter) {
            Swal.fire({ icon: 'warning', title: 'Trimestre requerido', text: 'Por favor selecciona un trimestre antes de guardar.' });
            return;
        }
        // Primero guardar criterios
        guardarCriteriosEvaluacion(idSubject, idSchoolYear, idSchoolQuarter)
        .then(res => {
            if (!res.success) throw new Error(res.message || 'Error al guardar criterios');
            // --- ACTUALIZA LOS NUEVOS idEvalCriteria EN LOS SELECTS E INPUTS ---
            if (res.data && Array.isArray(res.data)) {
                res.data.forEach((crit, idx) => {
                    // Actualiza el select
                    const select = document.getElementById(`C${idx + 1}-percentage`);
                    if (select) select.setAttribute('data-criteria-id', crit.idEvalCriteria);
                    // Actualiza los inputs de cada fila de la columna correspondiente
                    document.querySelectorAll(`.grade-input[data-col-index='${idx + 1}']`).forEach(input => {
                        input.setAttribute('data-criteria-id', crit.idEvalCriteria);
                    });
                });
            }
            // Luego guardar calificaciones
            const grades = [];
            const rows = document.querySelectorAll('#dataTable tbody tr');
            rows.forEach(row => {
                const idStudent = row.getAttribute('data-student-id');
                if (!idStudent) return;
                const studentGrades = { idStudent: idStudent, grades: {} };
                const inputs = row.querySelectorAll('.grade-input');
                inputs.forEach((input) => {
                    const select = document.getElementById(`C${input.getAttribute('data-col-index')}-percentage`);
                    const idEvalCriteria = input.getAttribute('data-criteria-id');
                    if (select && select.value && idEvalCriteria) {
                        studentGrades.grades[`C${input.getAttribute('data-col-index')}`] = {
                            grade: input.value,
                            idEvalCriteria: idEvalCriteria,
                            percentage: select.value // <-- AÑADIDO: porcentaje enviado
                        };
                    }
                });
                grades.push(studentGrades);
            });
            if (grades.length === 0 || grades.every(g => Object.keys(g.grades).length === 0)) {
                alert('No se recolectaron calificaciones. Revisa los criterios y los inputs.');
                return;
            }
            // Guardar calificaciones
            return fetch('saveGrades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idSubject: idSubject,
                    idSchoolYear: idSchoolYear,
                    idSchoolQuarter: idSchoolQuarter,
                    grades: grades
                })
            });
        })
        .then(response => response ? response.json() : null)
        .then(data => {
            if (!data) return;
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Éxito', text: 'Calificaciones y criterios guardados correctamente' });
                loadGrades(idSubject, idSchoolYear, idSchoolQuarter); // Recarga la tabla y promedios
            } else {
                throw new Error(data.message || 'Error al guardar los datos');
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Error al guardar los datos' });
        });
    }

    // --- NUEVA FUNCIÓN: Guardar criterios de evaluación ---
    function guardarCriteriosEvaluacion(idSubject, idSchoolYear, idSchoolQuarter) {
        const criterias = [];
        document.querySelectorAll('.percentage-select').forEach((select, idx) => {
            criterias.push({
                name: `C${idx + 1}`,
                percentage: parseInt(select.value) || 0
            });
        });
        return fetch('saveEvaluationCriteria.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idSubject: idSubject,
                idSchoolYear: idSchoolYear,
                idSchoolQuarter: idSchoolQuarter,
                criterias: criterias
            })
        }).then(response => response.json());
    }

    // --- MODIFICAR guardarCalificaciones para guardar criterios antes de calificaciones ---
    function guardarCalificaciones(idSubject, idSchoolYear, idSchoolQuarter) {
        if (!idSchoolYear) {
            Swal.fire({ icon: 'warning', title: 'Año escolar requerido', text: 'Por favor selecciona un año escolar antes de guardar.' });
            return;
        }
        if (!idSchoolQuarter) {
            Swal.fire({ icon: 'warning', title: 'Trimestre requerido', text: 'Por favor selecciona un trimestre antes de guardar.' });
            return;
        }
        // Primero guardar criterios
        guardarCriteriosEvaluacion(idSubject, idSchoolYear, idSchoolQuarter)
        .then(res => {
            if (!res.success) throw new Error(res.message || 'Error al guardar criterios');
            // --- ACTUALIZA LOS NUEVOS idEvalCriteria EN LOS SELECTS E INPUTS ---
            if (res.data && Array.isArray(res.data)) {
                res.data.forEach((crit, idx) => {
                    // Actualiza el select
                    const select = document.getElementById(`C${idx + 1}-percentage`);
                    if (select) select.setAttribute('data-criteria-id', crit.idEvalCriteria);
                    // Actualiza los inputs de cada fila de la columna correspondiente
                    document.querySelectorAll(`.grade-input[data-col-index='${idx + 1}']`).forEach(input => {
                        input.setAttribute('data-criteria-id', crit.idEvalCriteria);
                    });
                });
            }
            // Luego guardar calificaciones
            const grades = [];
            const rows = document.querySelectorAll('#dataTable tbody tr');
            rows.forEach(row => {
                const idStudent = row.getAttribute('data-student-id');
                if (!idStudent) return;
                const studentGrades = { idStudent: idStudent, grades: {} };
                const inputs = row.querySelectorAll('.grade-input');
                inputs.forEach((input) => {
                    const select = document.getElementById(`C${input.getAttribute('data-col-index')}-percentage`);
                    const idEvalCriteria = input.getAttribute('data-criteria-id');
                    if (select && select.value && idEvalCriteria) {
                        studentGrades.grades[`C${input.getAttribute('data-col-index')}`] = {
                            grade: input.value,
                            idEvalCriteria: idEvalCriteria,
                            percentage: select.value // <-- AÑADIDO: porcentaje enviado
                        };
                    }
                });
                grades.push(studentGrades);
            });
            if (grades.length === 0 || grades.every(g => Object.keys(g.grades).length === 0)) {
                alert('No se recolectaron calificaciones. Revisa los criterios y los inputs.');
                return;
            }
            // Guardar calificaciones
            return fetch('saveGrades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idSubject: idSubject,
                    idSchoolYear: idSchoolYear,
                    idSchoolQuarter: idSchoolQuarter,
                    grades: grades
                })
            });
        })
        .then(response => response ? response.json() : null)
        .then(data => {
            if (!data) return;
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Éxito', text: 'Calificaciones y criterios guardados correctamente' });
                loadGrades(idSubject, idSchoolYear, idSchoolQuarter); // Recarga la tabla y promedios
            } else {
                throw new Error(data.message || 'Error al guardar los datos');
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Error al guardar los datos' });
        });
    }

    // --- Cargar calificaciones automáticamente al seleccionar año y trimestre ---
    function checkAndLoadGrades() {
        const idSubject = <?php echo $idSubject; ?>;
        const idSchoolYear = currentSchoolYearId;
        const idSchoolQuarter = currentSchoolQuarterId;
        if (idSubject && idSchoolYear && idSchoolQuarter) {
            loadGrades(idSubject, idSchoolYear, idSchoolQuarter);
        }
    }

    // Ejecutar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        checkAndLoadGrades();
    });
    // Event listeners removidos - ahora se usa auto-detección
    // document.getElementById('schoolYearSelect').addEventListener('change', checkAndLoadGrades);
    // document.getElementById('schoolQuarterSelect').addEventListener('change', checkAndLoadGrades);

    // Refuerza la asignación de data-criteria-id a los inputs después de cargar criterios y de que la tabla esté lista
    function asignarCriteriaIdInputs() {
        // Obtener criterios actuales del DOM
        document.querySelectorAll('.percentage-select').forEach((select, idx) => {
            const idEvalCriteria = select.getAttribute('data-criteria-id');
            const colIndex = idx + 1;
            document.querySelectorAll(`.grade-input[data-col-index='${colIndex}']`).forEach(input => {
                if (idEvalCriteria) {
                    input.setAttribute('data-criteria-id', idEvalCriteria);
                }
            });
        });
    }

    // Llama a esta función después de cargar criterios y después de generar la tabla
    // Ejemplo: después de loadEvaluationCriteria(...)
    // asignarCriteriaIdInputs();

    // NUEVA FUNCIÓN: Cargar todo
    function cargarTodo(idSubject, idSchoolYear, idSchoolQuarter) {
        // Primero cargar los estudiantes
        loadStudentsForCurrentPeriod();
        // Luego cargar criterios y calificaciones
        loadEvaluationCriteria(idSubject, idSchoolYear, idSchoolQuarter, function() {
            loadGrades(idSubject, idSchoolYear, idSchoolQuarter);
        });
    }

    // Event listeners removidos - ahora se carga automáticamente con valores auto-detectados
    /*
    // Llama a cargarTodo cuando selecciones materia, ciclo y trimestre
    document.getElementById('schoolQuarterSelect').addEventListener('change', function() {
        const selectedQuarter = this.value;
        const selectedYear = document.getElementById('schoolYearSelect').value;
        const idSubject = <?php echo $idSubject; ?>;
        
        if (selectedQuarter && selectedYear) {
            cargarTodo(idSubject, selectedYear, selectedQuarter);
        } else {
            resetTableStructure();
            document.querySelectorAll('.grade-input').forEach(input => {
                input.value = '';
            });
        }
    });

    // Mostrar el select de trimestre cuando se seleccione un año escolar
    document.getElementById('schoolYearSelect').addEventListener('change', function() {
        const tbody = document.querySelector('#dataTable tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">Seleccione un año escolar y luego un trimestre para ver los estudiantes.</td></tr>';
        }
        const selectedYear = this.value;
        const quarterSelectContainer = document.getElementById('quarterSelectContainer');
        const quarterSelect = document.getElementById('schoolQuarterSelect');
        // Limpiar y ocultar el select de trimestre si no hay año escolar seleccionado
        if (!selectedYear) {
            quarterSelectContainer.style.display = 'none';
            quarterSelect.innerHTML = '<option value="" disabled selected>Seleccione un trimestre</option>';
            return;
        }
        // Mostrar y cargar los trimestres para el año escolar seleccionado
        fetch(`get_quarters.php?idSchoolYear=${selectedYear}`)
            .then(response => response.json())
            .then(data => {
                quarterSelect.innerHTML = '<option value="" disabled selected>Seleccione un trimestre</option>';
                if (data.success && data.quarters.length > 0) {
                    data.quarters.forEach(quarter => {
                        const option = document.createElement('option');
                        option.value = quarter.idSchoolQuarter;
                        option.textContent = quarter.name;
                        quarterSelect.appendChild(option);
                    });
                    quarterSelectContainer.style.display = 'block';
                } else {
                    quarterSelectContainer.style.display = 'none';
                }
            });
    });
    */

    // --- FUNCIÓN PARA CALCULAR Y MOSTRAR PROMEDIO DINÁMICO ---
    function calcularPromedioFila(row) {
        let sum = 0;
        let sumPercent = 0;
        const inputs = row.querySelectorAll('.grade-input');
        
        // Para debugging
        console.log("Calculando promedio para fila:", row);
        
        inputs.forEach(input => {
            const grade = input.value.trim() !== '' ? parseFloat(input.value) : null;
            const colIndex = input.getAttribute('data-col-index');
            const select = document.getElementById(`C${colIndex}-percentage`);
            const percent = select && select.value ? parseFloat(select.value) : 0;
            
            // Para debugging
            console.log("Columna:", colIndex, "Calificación:", grade, "Porcentaje:", percent);
            
            // Si el porcentaje existe, sumar al total de porcentaje
            if (!isNaN(percent)) {
                sumPercent += percent;
                // Solo sumar al promedio si la calificación existe
                if (grade !== null && !isNaN(grade)) {
                    sum += grade * (percent / 100);
                }
                // Si la calificación no existe pero el porcentaje sí, se considera el porcentaje
                // pero no suma nada al promedio (equivalente a calificación 0)
            }
        });
        
        // Para debugging
        console.log("Suma total:", sum, "Porcentaje total:", sumPercent);
        
        const promedioCell = row.querySelector('.promedio-cell');
        if (sumPercent > 0) {
            // Normalizar el promedio para que sea sobre 10, considerando el porcentaje total
            const promedio = (sumPercent === 100) ? sum : (sum / (sumPercent / 100));
            console.log("Promedio calculado:", promedio);
            
            // Redondear hacia arriba igual que el backend (PHP ceil)
            const promedioRedondeado = Math.ceil(promedio * 10) / 10;
            promedioCell.textContent = promedioRedondeado.toFixed(1);
            
            // Almacenar el promedio como un atributo de datos para referencia
            promedioCell.setAttribute('data-calculated-average', promedioRedondeado.toString());
        } else {
            promedioCell.textContent = '-';
            promedioCell.removeAttribute('data-calculated-average');
        }
    }

    // Escucha cambios en inputs y selects para actualizar promedio dinámico
    // (agrega este listener una sola vez)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('grade-input') || e.target.classList.contains('percentage-select')) {
            document.querySelectorAll('#dataTable tbody tr').forEach(row => calcularPromedioFila(row));
        }
    });
    </script>

</body>
</html>