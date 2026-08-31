<?php
require_once "check_session.php";
require_once "../force_password_check.php";
require_once "../conection.php";

// --- FECHA LIMITE GLOBAL ---
$fechaLimite = null;
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $fechaLimite = $row['limitDate'];
}
$hoy = date('Y-m-d');
$fueraDePlazo = ($fechaLimite && $hoy > date('Y-m-d', strtotime($fechaLimite . ' +0 day')));

// Paso 1: Obtener el idTeacher del usuario logueado
$user_id = $_SESSION['user_id'];
$sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
$stmtTeacher = $conexion->prepare($sqlTeacher);
$stmtTeacher->bind_param("i", $user_id);
$stmtTeacher->execute();
$resTeacher = $stmtTeacher->get_result();
$rowTeacher = $resTeacher->fetch_assoc();
$teacher_id = $rowTeacher ? $rowTeacher['idTeacher'] : null;

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
if ($teacher_id && $currentSchoolYear && $currentQuarter) {
    // Paso 2: Obtener las materias asignadas a este docente para el año y trimestre actual
    // Para materias especiales, incluir grupo y grado
    $query = "SELECT DISTINCT
                s.idSubject, 
                s.name, 
                s.specialSubject, 
                s.description, 
                la.name AS learningAreaName,
                CASE WHEN s.specialSubject = 1 THEN CONCAT(g.grade, '°', g.group_, ' - ') ELSE '' END AS groupInfo
              FROM teacherSubject ts
              JOIN subjects s ON ts.idSubject = s.idSubject
              JOIN learningArea la ON s.idLearningArea = la.idLearningArea
              LEFT JOIN teacherGroupsSubjects tgs ON ts.idTeacher = tgs.idTeacher AND ts.idSubject = tgs.idSubject
              LEFT JOIN groups g ON tgs.idGroup = g.idGroup
              WHERE ts.idTeacher = ? 
                AND ts.idSchoolYear = ?
              ORDER BY s.name ASC, g.grade ASC, g.group_ ASC";
    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        die("Error al preparar consulta: " . $conexion->error);
    }
    $stmt->bind_param("ii", $teacher_id, $currentSchoolYear['idSchoolYear']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <title>Materias</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <!-- Design System -->
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/layout.css">
    <!-- Page styles -->
    <link rel="stylesheet" href="../css/teacher/subject.css">
    <!-- Tipografía -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/logo.ico">
</head>
<body class="page-tch-subjects">
    <!-- Preloader -->
    <div id="preloader">
        <img src="../img/logo.webp" alt="Cargando..." class="logo">
    </div>
    <?php include "../layouts/asideTeacher.php"; ?>
    <main class="ds-main">
        <?php include "../layouts/headerTeacher.php"; ?>
        
        <div class="page-content">

            <!-- Header de la página -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-journal-bookmark me-3"></i>
                    Mis Materias
                </h1>
                <p class="page-subtitle">
                    Gestiona y accede a todas tus materias asignadas
                </p>
            </div>

            <!-- Grid de materias -->
            <div class="sub-grid">
                <?php foreach ($subjects as $subject): ?>
                <div class="sub-card">
                    <div class="sub-card__header">
                        <h5 class="sub-card__title">
                            <?php echo htmlspecialchars($subject['name']); ?>
                            <?php if($subject['specialSubject'] && $subject['groupInfo']): ?>
                                <span class="sub-card__group"><?php echo htmlspecialchars($subject['groupInfo']); ?></span>
                            <?php endif; ?>
                        </h5>
                        <?php if($subject['specialSubject']): ?>
                        <span class="sub-badge sub-badge--special">
                            <i class="bi bi-star-fill"></i> Especial
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="sub-card__body">
                        <p class="sub-card__desc">
                            <?php echo htmlspecialchars($subject['description']); ?>
                        </p>

                        <div class="sub-card__meta">
                            <div class="sub-meta">
                                <span class="sub-meta__label">Campo Formativo</span>
                                <span class="sub-meta__value"><?php echo htmlspecialchars($subject['learningAreaName']); ?></span>
                            </div>
                            <div class="sub-meta">
                                <span class="sub-meta__label">Trimestre</span>
                                <span class="sub-meta__value"><?php echo $currentQuarter ? htmlspecialchars($currentQuarter['name']) : 'No definido'; ?></span>
                            </div>
                            <div class="sub-meta">
                                <span class="sub-meta__label">Ciclo Escolar</span>
                                <span class="sub-meta__value"><?php echo substr($currentSchoolYear['startDate'], 0, 4); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="sub-card__footer">
                        <?php if($fueraDePlazo): ?>
                        <button class="sub-btn sub-btn--outline" disabled>
                            <i class="bi bi-lock"></i>
                            Fuera de plazo
                        </button>
                        <span class="sub-card__hint">
                            Disponible hasta el <?php echo date('d/m/Y', strtotime($fechaLimite)); ?>
                        </span>
                        <?php else: ?>
                        <a href="./gradesSubject.php?idSubject=<?php echo $subject['idSubject']; ?>" class="sub-btn sub-btn--primary">
                            Ingresar a la materia
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($subjects)): ?>
                <div class="sub-empty">
                    <i class="bi bi-book sub-empty__icon"></i>
                    <h3 class="sub-empty__title">No tienes materias asignadas</h3>
                    <p class="sub-empty__text">Contacta al administrador para que te asigne materias.</p>
                </div>
                <?php endif; ?>
            </div>

        </div>    
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
</body>
</html>