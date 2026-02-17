<?php
require_once "check_session.php";
require_once "../force_password_check.php";
require_once "../conection.php";

// Obtener el idTeacher y el typeTeacher del usuario logueado
$user_id = $_SESSION['user_id'];
$sqlTeacher = "SELECT idTeacher, typeTeacher FROM teachers WHERE idUser = ?";
$stmtTeacher = $conexion->prepare($sqlTeacher);
$stmtTeacher->bind_param("i", $user_id);
$stmtTeacher->execute();
$resTeacher = $stmtTeacher->get_result();
$rowTeacher = $resTeacher->fetch_assoc();
$teacher_id = $rowTeacher ? $rowTeacher['idTeacher'] : null;
$typeTeacher = $rowTeacher ? $rowTeacher['typeTeacher'] : null;

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
    die("No se encontró un ciclo escolar para el año actual (" . $currentYear . "). Por favor, crea uno primero.");
}

// Obtener los trimestres del ciclo escolar actual
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

$subjects = [];
if ($teacher_id) {
    $query = "SELECT s.idSubject, s.name
              FROM teacherSubject ts
              JOIN subjects s ON ts.idSubject = s.idSubject
              JOIN schoolYear sy ON ts.idSchoolYear = sy.idSchoolYear
              WHERE ts.idTeacher = ?
              GROUP BY s.idSubject, sy.startDate, sy.endDate
              ORDER BY sy.startDate DESC, s.name ASC";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $uniqueSubjects = [];
    while ($row = $result->fetch_assoc()) {
        // Evitar duplicados por idSubject y ciclo escolar
        $key = $row['idSubject'];
        if (!isset($uniqueSubjects[$key])) {
            $uniqueSubjects[$key] = $row;
        }
    }
    $subjects = array_values($uniqueSubjects);
}
?>
<!-- update test -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones</title>
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
    <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>
    <?php include "../layouts/asideTeacher.php"; ?>
    <main class="flex-grow-1 col-9 p-0 ">
        <?php include "../layouts/headerTeacher.php"; ?>
        
        <!-- Header de la página -->
        <div class="container-fluid px-4" style="padding-top: 4rem; height: auto;">
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-3">
                        <h1 class="page-title">
                            <i class="bi bi-award me-3"></i>
                            Calificaciones
                        </h1>
                        <p class="page-subtitle text-muted">
                            Consulta y gestiona las calificaciones de tus estudiantes
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="container-fluid px-4">
            <!-- Panel de filtros -->
            <div class="row mb-4 pb-4">
                <div class="col-12">
                    <div class="filter-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-sliders me-2 text-primary"></i>
                                    Seleccionar Parámetros
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Alerta informativa del año actual -->
                                <div class="alert alert-info alert-dismissible fade show mb-2" role="alert">
                                    <i class="bi bi-calendar-check-fill me-2"></i>
                                    <strong>Año Escolar:</strong> <?php echo substr($currentSchoolYear['startDate'], 0, 4); ?>
                                    <span class="text-muted ms-2">(<?php echo date('d/m/Y', strtotime($currentSchoolYear['startDate'])); ?> - <?php echo date('d/m/Y', strtotime($currentSchoolYear['endDate'])); ?>)</span>
                                </div>
                                
                                <!-- Alerta informativa del trimestre actual -->
                                <?php if ($currentQuarter): ?>
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    <i class="bi bi-calendar3-fill me-2"></i>
                                    <strong>Trimestre Actual:</strong> <?php echo $currentQuarter['name']; ?>
                                    <?php if ($currentQuarter['startDate'] && $currentQuarter['endDate']): ?>
                                    <span class="text-muted ms-2">(<?php echo date('d/m/Y', strtotime($currentQuarter['startDate'])); ?> - <?php echo date('d/m/Y', strtotime($currentQuarter['endDate'])); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>Advertencia:</strong> No se pudo determinar el trimestre actual. Por favor, verifica las fechas de los trimestres.
                                </div>
                                <?php endif; ?>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label id="labelMatter" for="materia" class="form-label fw-semibold">
                                            <i class="bi bi-book me-1"></i>
                                            Seleccionar Materia:
                                        </label>
                                        <div class="form-select-container">
                                            <select class="form-select border-secondary" id="materia">
                                                <option value="" selected>Seleccionar Materia</option>
                                            </select>
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
                                    Lista de Estudiantes y Calificaciones
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="dataTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="fw-semibold">No.</th>
                                                <?php if(isset($typeTeacher) && $typeTeacher === 'ME'): ?>
                                                    <th class="fw-semibold">Grado</th>
                                                    <th class="fw-semibold">Grupo</th>
                                                <?php endif; ?>
                                                <th class="fw-semibold">Apellido Paterno</th>
                                                <th class="fw-semibold">Apellido Materno</th>
                                                <th class="fw-semibold">Nombres</th>
                                                <th class="fw-semibold">Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                        <tr>
                            <td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">Seleccione una materia para ver los estudiantes.</td>
                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Estado vacío -->
                                <div id="emptyState" class="text-center py-5" style="display: none;">
                                    <div class="mb-3">
                                        <i class="bi bi-inbox display-4 text-muted"></i>
                                    </div>
                                    <h5 class="text-muted">No hay estudiantes registrados</h5>
                                    <p class="text-muted">Selecciona una materia para ver los estudiantes del trimestre actual</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
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
    
    // Año escolar y trimestre actual obtenidos desde PHP
    const currentSchoolYearId = <?php echo $currentSchoolYear['idSchoolYear']; ?>;
    const currentSchoolQuarterId = <?php echo $currentQuarter ? $currentQuarter['idSchoolQuarter'] : 'null'; ?>;
    
    const materiaSelect = document.getElementById('materia');

    // Cargar materias automáticamente al cargar la página
    if (currentSchoolQuarterId) {
        cargarMaterias();
    }

    function cargarMaterias() {
        fetch(`get_subjects.php?idSchoolYear=${currentSchoolYearId}&idSchoolQuarter=${currentSchoolQuarterId}`)
            .then(response => response.json())
            .then(data => {
                materiaSelect.innerHTML = '<option value="" selected>Seleccionar Materia</option>';
                if (data.success) {
                    data.subjects.forEach(s => {
                        const option = document.createElement('option');
                        option.value = s.idSubject;
                        option.textContent = s.name;
                        materiaSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error al cargar materias:', error);
            });
    }

    // Cargar estudiantes cuando se seleccione una materia
    materiaSelect.addEventListener('change', function() {
        updateSelectIndicator(this);
        if (this.value) {
            cargarEstudiantesConPromedio();
        }
    });

    // Función para actualizar indicadores visuales
    function updateSelectIndicator(selectElement) {
        const container = selectElement.parentElement;
        if (selectElement.value && selectElement.value !== '') {
            container.style.setProperty('--show-indicator', '1');
        } else {
            container.style.setProperty('--show-indicator', '0');
        }
    }

    function cargarEstudiantesConPromedio() {
        const idSubject = materiaSelect.value;
        const tbody = document.querySelector('#dataTable tbody');
        
        if (!idSubject) {
            tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">Seleccione una materia.</td></tr>`;
            return;
        }
        
        if (!currentSchoolQuarterId) {
            tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">No se pudo determinar el trimestre actual.</td></tr>`;
            return;
        }
        
        tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">Cargando...</td></tr>`;
        fetch(`getStudentsBySubject.php?idSubject=${idSubject}&idSchoolYear=${currentSchoolYearId}&idSchoolQuarter=${currentSchoolQuarterId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.students.length > 0) {
                    tbody.innerHTML = '';
                    data.students.forEach((student, idx) => {
                        const tr = document.createElement('tr');
                        let rowHTML = `<td>${idx + 1}</td>`;
                        <?php if(isset($typeTeacher) && $typeTeacher === 'ME'): ?>
                        rowHTML += `<td>${student.grade ?? ''}</td><td>${student.group_ ?? ''}</td>`;
                        <?php endif; ?>
                        rowHTML += `
                            <td>${student.lastnamePa}</td>
                            <td>${student.lastnameMa}</td>
                            <td>${student.names}</td>
                            <td>${student.average !== null && student.average !== undefined ? (Math.ceil(Number(student.average) * 10) / 10).toFixed(1) : '-'}</td>
                        `;
                        tr.innerHTML = rowHTML;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">No hay estudiantes inscritos en esta materia para el trimestre actual.</td></tr>`;
                }
            })
            .catch(() => {
                tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">Error al cargar los estudiantes.</td></tr>`;
            });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/chartScript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
</body>
</html>