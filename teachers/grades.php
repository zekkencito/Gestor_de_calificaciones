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
                                <div class="row g-3">
                                    <div class="col-md-3" id="contenedorYear">
                                        <label id="labelDocente" for="schoolYearSelect" class="form-label fw-semibold">
                                            <i class="bi bi-calendar-date me-1"></i>
                                            Año escolar:
                                        </label>
                                        <div class="form-select-container">
                                            <select class="form-select border-secondary" id="schoolYearSelect">
                                                <option value="" selected>Seleccionar año</option>
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
                                    
                                    <div class="col-md-3" id="contenedorQuarter" style="display:none;">
                                        <label id="labelQuarter" for="schoolQuarterSelect" class="form-label fw-semibold">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            Trimestre:
                                        </label>
                                        <div class="form-select-container">
                                            <select class="form-select border-secondary" id="schoolQuarterSelect">
                                                <option value="" selected>Seleccionar trimestre</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3" id="contenedorMateria" style="display:none;">
                                        <label id="labelMatter" for="materia" class="form-label fw-semibold">
                                            <i class="bi bi-book me-1"></i>
                                            Materia:
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
                            <td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">Seleccione una materia, un año escolar y un trimestre.</td>
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
                                    <p class="text-muted">Selecciona un año, trimestre y materia para ver los estudiantes</p>
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
    const contQuarter = document.getElementById('contenedorQuarter');
    const contMateria = document.getElementById('contenedorMateria');
    const yearSelect = document.getElementById('schoolYearSelect');
    const quarterSelect = document.getElementById('schoolQuarterSelect');
    const materiaSelect = document.getElementById('materia');

    yearSelect.addEventListener('change', function() {
        const idSchoolYear = this.value;
        quarterSelect.innerHTML = '<option value="" selected>Seleccionar trimestre</option>';
        contQuarter.style.display = idSchoolYear ? '' : 'none';
        contMateria.style.display = 'none';
        materiaSelect.innerHTML = '<option value="" selected>Seleccionar Materia</option>';
        
        // Actualizar indicadores visuales
        updateSelectIndicator(this);
        updateSelectIndicator(quarterSelect);
        updateSelectIndicator(materiaSelect);
        
        if (!idSchoolYear) return;
        fetch(`get_quarters.php?idSchoolYear=${idSchoolYear}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.quarters.forEach(q => {
                        const option = document.createElement('option');
                        option.value = q.idSchoolQuarter;
                        option.textContent = q.name;
                        quarterSelect.appendChild(option);
                    });
                }
            });
    });

    // Mostrar materias al seleccionar trimestre
    quarterSelect.addEventListener('change', function() {
        const idSchoolYear = yearSelect.value;
        const idSchoolQuarter = this.value;
        materiaSelect.innerHTML = '<option value="" selected>Seleccionar Materia</option>';
        contMateria.style.display = idSchoolQuarter ? '' : 'none';
        
        // Actualizar indicadores visuales
        updateSelectIndicator(this);
        updateSelectIndicator(materiaSelect);
        
        if (!idSchoolQuarter) return;
        fetch(`get_subjects.php?idSchoolYear=${idSchoolYear}&idSchoolQuarter=${idSchoolQuarter}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.subjects.forEach(s => {
                        const option = document.createElement('option');
                        option.value = s.idSubject;
                        option.textContent = s.name;
                        materiaSelect.appendChild(option);
                    });
                }
            });
    });

    // Mostrar alumnos solo cuando los 3 selects tienen valor
    materiaSelect.addEventListener('change', function() {
        updateSelectIndicator(this);
        cargarEstudiantesConPromedio();
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
        const idSchoolYear = yearSelect.value;
        const idSchoolQuarter = quarterSelect.value;
        const tbody = document.querySelector('#dataTable tbody');
        if (!idSubject || !idSchoolYear || !idSchoolQuarter) {
            tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">Seleccione año, trimestre y materia.</td></tr>`;
            return;
        }
        tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">Cargando...</td></tr>`;
        fetch(`getStudentsBySubject.php?idSubject=${idSubject}&idSchoolYear=${idSchoolYear}&idSchoolQuarter=${idSchoolQuarter}`)
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
                    tbody.innerHTML = `<tr><td colspan="<?php echo (isset($typeTeacher) && $typeTeacher === 'ME') ? 7 : 5; ?>" class="text-center">No hay estudiantes inscritos en esta materia, año escolar o trimestre.</td></tr>`;
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