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

$subjects = [];
if ($teacher_id) {
    // Paso 2: Obtener las materias asignadas a este docente (sin repetir por idSubject)
    $query = "SELECT 
                s.idSubject, 
                s.name, 
                s.specialSubject, 
                s.description, 
                la.name AS learningAreaName,
                sy.startDate,
                sy.endDate,
                ts.idTeacherSubject
              FROM teacherSubject ts
              JOIN subjects s ON ts.idSubject = s.idSubject
              JOIN learningArea la ON s.idLearningArea = la.idLearningArea
              JOIN schoolYear sy ON ts.idSchoolYear = sy.idSchoolYear
              WHERE ts.idTeacher = ?
              GROUP BY s.idSubject, sy.startDate, sy.endDate
              ORDER BY sy.startDate DESC, s.name ASC";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Formatear ciclo escolar como 'YYYY-YYYY'
        
        $row['schoolYearFormatted'] = $row['startDate'] . '   -   ' . $row['endDate'];
        $subjects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materias</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/teacher/subject.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css">
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
        
        <!-- Título mejorado -->
        <div class="container-fluid px-4" style="padding-top: 8rem; height: auto;">
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-3">
                        <h1 class="page-title">
                            <i class="bi bi-journal-bookmark me-3"></i>
                            Mis Materias
                        </h1>
                        <p class="page-subtitle text-muted">
                            Gestiona y accede a todas tus materias asignadas
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenedor principal de las materias -->
        <div class="container-fluid px-4">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="subjects-grid">
                        <?php foreach ($subjects as $subject): ?>
                        <div class="subject-card">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0 fw-bold">
                                            <i class="bi bi-book me-2"></i>
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                        </h5>
                                        <?php if($subject['specialSubject']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-star-fill"></i> Especial
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="subject-description mb-3">
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-file-text me-2"></i>
                                            <?php echo htmlspecialchars($subject['description']); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="subject-details mb-3">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="detail-item">
                                                    <i class="bi bi-diagram-3 text-info me-2"></i>
                                                    <span class="fw-semibold">Campo Formativo:</span>
                                                    <span class="ms-1"><?php echo htmlspecialchars($subject['learningAreaName']); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="detail-item">
                                                    <i class="bi bi-calendar-range text-success me-2"></i>
                                                    <span class="fw-semibold">Ciclo Escolar:</span>
                                                    <span class="ms-1"><?php echo htmlspecialchars($subject['schoolYearFormatted']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Botón de acción -->
                                    <div class="mt-auto">
                                        <?php if($fueraDePlazo): ?>
                                        <button class="btn btn-secondary w-100 btn-lg disabled" disabled>
                                            <i class="bi bi-lock me-2"></i>
                                            Fuera de plazo
                                        </button>
                                        <small class="text-muted mt-2 d-block text-center">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Disponible hasta el <?php echo date('d/m/Y', strtotime($fechaLimite)); ?>
                                        </small>
                                        <?php else: ?>
                                        <a href="./gradesSubject.php?idSubject=<?php echo $subject['idSubject']; ?>" 
                                           class="btn btn-primary w-100 btn-lg btn-access">
                                            <i class="bi bi-arrow-right-circle me-2"></i>
                                            Ingresar a la materia
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($subjects)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-book display-1 text-muted mb-3"></i>
                                    <h3 class="text-muted">No tienes materias asignadas</h3>
                                    <p class="text-muted">Contacta al administrador para que te asigne materias.</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estilos CSS personalizados -->
        <style>
            /* Estilos para el título */
            .page-header {
                text-align: center;
                padding: 1.5rem 0 1rem 0;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 15px;
                margin-bottom: 1.5rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .page-title {
                color: #192E4E;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 0.3rem;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .page-subtitle {
                font-size: 1.1rem;
                margin-bottom: 0;
                opacity: 0.8;
            }
            
            /* Grid de materias */
            .subjects-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 1.5rem;
                padding: 1rem 0 2rem 0;
            }
            
            .subject-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .subject-card:hover {
                transform: translateY(-5px);
            }
            
            .subject-card .card {
                border-radius: 15px;
                overflow: hidden;
                background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            }
            
            .subject-card .card-header {
                background: linear-gradient(135deg, #192E4E 0%, #264B82 100%) !important;
                border: none;
                padding: 1.25rem;
            }
            
            .subject-card .card-body {
                padding: 1.5rem;
                min-height: 250px;
            }
            
            .detail-item {
                padding: 0.5rem 0;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .detail-item:last-child {
                border-bottom: none;
            }
            
            .btn-access {
                background: linear-gradient(135deg, #264B82 0%, #192E4E 100%);
                border: none;
                border-radius: 10px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(25, 46, 78, 0.2);
            }
            
            .btn-access:hover {
                background: linear-gradient(135deg, #1a3d6b 0%, #142439 100%);
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(25, 46, 78, 0.3);
            }
            
            .empty-state {
                max-width: 400px;
                margin: 0 auto;
            }
            
            /* Responsividad mejorada */
            @media (max-width: 768px) {
                .page-title {
                    font-size: 2rem;
                }
                
                .page-header {
                    padding: 1rem 0 0.75rem 0;
                    margin-bottom: 1rem;
                }
                
                .subjects-grid {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                    padding: 0.5rem 0 1.5rem 0;
                }
                
                .subject-card .card-body {
                    min-height: auto;
                }
            }
            
            @media (min-width: 1200px) {
                .subjects-grid {
                    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
                }
            }
        </style>    
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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