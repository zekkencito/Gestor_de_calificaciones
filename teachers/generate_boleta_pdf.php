<?php
// Configuración para PDF - suprimir notices y warnings que interfieren con la salida
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Configurar zona horaria para México
date_default_timezone_set('America/Mexico_City');

// Verificar acceso autorizado antes de cualquier output
require_once "../conection.php";

// --- VERIFICACIÓN DE FECHA LIMITE PARA DESCARGAS ---
$fechaLimite = null;
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $fechaLimite = $row['limitDate'];
}
$hoy = date('Y-m-d');
$descargasHabilitadas = ($fechaLimite && $hoy > date('Y-m-d', strtotime($fechaLimite . ' +0 day')));

// Si las descargas no están habilitadas, mostrar mensaje y salir
if (!$descargasHabilitadas) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Descarga no disponible</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='alert alert-info text-center'>
                <h4><i class='bi bi-info-circle'></i> Descarga no disponible</h4>
                <p>Las descargas de boletas se habilitarán después del <strong>" . date('d/m/Y', strtotime($fechaLimite)) . "</strong></p>
                <button onclick='window.close()' class='btn btn-primary'>Cerrar</button>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

// Funciones auxiliares
function utf8_decode_safe($text) {
    return iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
}

// Función para generar PDF programáticamente (para uso desde otros archivos)
function generateStudentPDF($idStudent, $idSchoolYear, $idSchoolQuarter, $conexion) {
    require_once "../fpdf.php";
    
    // Obtener información del estudiante
    $sqlStudent = "SELECT s.idStudent, s.schoolNum, ui.lastnamePa, ui.lastnameMa, ui.names, g.grade, g.group_, s.curp,
                   sy.startDate as schoolYear, sy.endDate,
                   sq.name as quarterName
                   FROM students s
                   JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
                   JOIN groups g ON s.idGroup = g.idGroup
                   JOIN schoolYear sy ON s.idSchoolYear = sy.idSchoolYear
                   CROSS JOIN schoolQuarter sq
                   WHERE s.idStudent = ? AND s.idSchoolYear = ? AND sq.idSchoolQuarter = ?";

    $stmtStudent = $conexion->prepare($sqlStudent);
    if (!$stmtStudent) {
        throw new Exception("Error preparando consulta student: " . $conexion->error);
    }

    $stmtStudent->bind_param("iii", $idStudent, $idSchoolYear, $idSchoolQuarter);
    $stmtStudent->execute();
    $result = $stmtStudent->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        throw new Exception("No se encontró información del estudiante");
    }

    // Obtener las materias del estudiante basándose en su grupo con sus áreas de aprendizaje
    $sqlSubjects = "SELECT DISTINCT s.idSubject, s.name as subjectName, s.idLearningArea, la.name as learningAreaName
                    FROM subjects s
                    JOIN learningArea la ON s.idLearningArea = la.idLearningArea
                    JOIN teacherGroupsSubjects tgs ON s.idSubject = tgs.idSubject
                    JOIN students st ON st.idGroup = tgs.idGroup
                    WHERE st.idStudent = ? AND st.idSchoolYear = ?
                    ORDER BY la.name, s.name";

    $stmtSubjects = $conexion->prepare($sqlSubjects);
    if (!$stmtSubjects) {
        throw new Exception("Error preparando consulta subjects: " . $conexion->error);
    }
    $stmtSubjects->bind_param("ii", $idStudent, $idSchoolYear);
    $stmtSubjects->execute();
    $resultSubjects = $stmtSubjects->get_result();

    $learningAreas = [];
    $subjects = [];
    $totalGrade = 0;
    $validGrades = 0;

    while ($subject = $resultSubjects->fetch_assoc()) {
        // Obtener el promedio calculado correctamente desde la tabla average
        $sqlAverage = "SELECT a.average
                       FROM average a
                       WHERE a.idStudent = ? AND a.idSubject = ? AND a.idSchoolYear = ? AND a.idSchoolQuarter = ?";
        
        $stmtAverage = $conexion->prepare($sqlAverage);
        if (!$stmtAverage) {
            throw new Exception("Error preparando consulta average: " . $conexion->error);
        }
        $stmtAverage->bind_param("iiii", $idStudent, $subject['idSubject'], $idSchoolYear, $idSchoolQuarter);
        $stmtAverage->execute();
        $resultAverage = $stmtAverage->get_result();
        $averageData = $resultAverage->fetch_assoc();
        $stmtAverage->close();
        
        // Usar directamente el promedio calculado o 0 si no existe
        $average = ($averageData && isset($averageData['average']) && $averageData['average'] !== null) 
                   ? ceil(floatval($averageData['average']) * 10) / 10  // Redondear hacia arriba como en saveGrades.php
                   : 0;
        
        $subjectData = [
            'name' => $subject['subjectName'],
            'average' => $average,
            'idLearningArea' => $subject['idLearningArea'],
            'learningAreaName' => $subject['learningAreaName']
        ];
        
        $subjects[] = $subjectData;
        
        // Agrupar por área de aprendizaje
        $areaId = $subject['idLearningArea'];
        if (!isset($learningAreas[$areaId])) {
            $learningAreas[$areaId] = [
                'name' => $subject['learningAreaName'],
                'subjects' => [],
                'totalGrade' => 0,
                'subjectCount' => 0
            ];
        }
        
        $learningAreas[$areaId]['subjects'][] = $subjectData;
        $learningAreas[$areaId]['totalGrade'] += $average;
        $learningAreas[$areaId]['subjectCount']++;
        
        // Incluir TODAS las materias en el promedio general (vacías como 0)
        $totalGrade += $average;
        $validGrades++;
    }
    $stmtSubjects->close();

    // Calcular promedio por área de aprendizaje
    foreach ($learningAreas as $areaId => &$area) {
        $area['average'] = $area['subjectCount'] > 0 ? ceil(($area['totalGrade'] / $area['subjectCount']) * 10) / 10 : 0;
    }
    unset($area); // Liberar la referencia para evitar problemas en bucles posteriores

    // Si no se encontraron materias, agregar mensaje explicativo
    if (empty($learningAreas)) {
        $learningAreas[0] = [
            'name' => 'Sin áreas asignadas',
            'subjects' => [[
                'name' => 'No se encontraron materias asignadas',
                'average' => 0
            ]],
            'average' => 0,
            'totalGrade' => 0,
            'subjectCount' => 1
        ];
    }

    // Calcular promedio general
    $generalAverage = $validGrades > 0 ? round($totalGrade / $validGrades, 1) : 0;

    // Verificar que tenemos información mínima para generar el PDF
    if (empty($student['names'])) {
        throw new Exception("Error: Información del estudiante incompleta");
    }

    // Crear el PDF con soporte para UTF-8
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetMargins(20, 15, 20);

    // Logo y encabezado (FPDF solo soporta JPG, PNG, GIF)
    $logoPaths = [
        '../img/logo.png',
        '../img/logo.jpg',
        '../img/logo.jpeg',
        '../img/logo.gif'
    ];
    
    // Encabezado profesional con logo centrado
    $pageWidth = 216; // Ancho de página Letter en mm
    $logoWidth = 20;
    $logoX = ($pageWidth - $logoWidth) / 2;
    
    foreach ($logoPaths as $logoPath) {
        if (file_exists($logoPath)) {
            try {
                $pdf->Image($logoPath, $logoX, 12, $logoWidth, $logoWidth);
                break;
            } catch (Exception $e) {
                error_log("Error al cargar logo: " . $e->getMessage());
            }
        }
    }

    // Nombre de la escuela centrado debajo del logo
    $pdf->SetY(35);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 5, utf8_decode_safe('Escuela Primaria'), 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 5, utf8_decode_safe('Gregorio Torres Quintero No.2308'), 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Título de boleta centrado
    //$pdf->SetFont('Helvetica', 'B', 12);
    //$pdf->Cell(0, 10, utf8_decode_safe('BOLETA DE CALIFICACIONES'), 0, 1, 'C');

    $pdf->Ln(8);

    // Asignar variables del estudiante
    $lastnamePa = $student['lastnamePa'] ?? '';
    $lastnameMa = $student['lastnameMa'] ?? '';
    $names = $student['names'] ?? '';
    $grade = $student['grade'] ?? '';
    $group = $student['group_'] ?? '';

    // ===== DATOS DEL ALUMNO =====
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('DATOS DEL ALUMNO'), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);
    
    // Información del estudiante en dos filas
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(45, 5, utf8_decode_safe('Apellido Paterno: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(50, 5, $lastnamePa, 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(35, 5, utf8_decode_safe('Apellido Materno: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, utf8_decode_safe($lastnameMa), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(30, 5, utf8_decode_safe('Nombre(s): '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(65, 5, utf8_decode_safe($names), 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(20, 5, utf8_decode_safe('Grado: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(15, 5, utf8_decode_safe($grade), 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(15, 5, utf8_decode_safe('Grupo: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, utf8_decode_safe($group), 0, 1, 'L');
    
    $pdf->Ln(8);
    
    // ===== DATOS DE LA ESCUELA =====
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('DATOS DE LA ESCUELA'), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);
    
    // Ciclo escolar, período, turno
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(50, 5, utf8_decode_safe('Ciclo Escolar: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $yearDisplay = date('Y', strtotime($student['schoolYear'])) . '-' . date('Y', strtotime($student['endDate']));
    $pdf->Cell(40, 5, $yearDisplay, 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(35, 5, utf8_decode_safe('Período: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, utf8_decode_safe($student['quarterName']), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(50, 5, utf8_decode_safe('Turno: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, utf8_decode_safe('Matutino'), 0, 1, 'L');
    
    $pdf->Ln(10);

    // Tabla de calificaciones
    // Encabezado de la tabla
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetFillColor(100, 100, 100);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(100, 7, utf8_decode_safe('CAMPO FORMATIVO / MATERIA'), 1, 0, 'C', true);
    $pdf->Cell(90, 7, utf8_decode_safe('CALIFICACIÓN'), 1, 1, 'C', true);

    // Filas de la tabla por áreas de aprendizaje
    $pdf->SetTextColor(0, 0, 0);

    foreach ($learningAreas as $area) {
        // Fila del campo formativo (encabezado de grupo)
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetFillColor(160, 160, 160);
        $pdf->Cell(100, 9, utf8_decode_safe($area['name']), 1, 0, 'C', true);
        
        // Celda de calificación del área
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(90, 9, number_format($area['average'], 1), 1, 1, 'C', true);
        
        // Filas de materias dentro del área
        foreach ($area['subjects'] as $subject) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(100, 6, utf8_decode_safe($subject['name']), 1, 0, 'C');
            
            // Celda de calificación
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(90, 6, number_format($subject['average'], 1), 1, 1, 'C');
        }
    }

    $pdf->Ln(8);

    // Promedio General (después de la tabla)
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(144, 8, utf8_decode_safe('PROMEDIO GENERAL: '), 0, 0, 'R');
    $pdf->SetFillColor(100, 100, 100);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(46, 8, number_format($generalAverage, 1), 1, 1, 'C', true);
    $pdf->Ln(15);

    // Escala de calificaciones
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 6, utf8_decode_safe('ESCALA DE CALIFICACIONES:'), 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 5, utf8_decode_safe('Excelente: 9.0 - 10.0  |  Bien: 7.0 - 8.9  |  Suficiente: 6.0 - 6.9  |  Insuficiente: 0.0 - 5.9'), 0, 1, 'L');
    $pdf->Ln(10);

    $pdf->SetFont('Helvetica', 'I', 8);
    // Obtener hora actual
    $pdf->Cell(0, 4, utf8_decode_safe('Documento generado el: ' . date('d/m/Y H:i:s')), 0, 1, 'R');
    $pdf->Cell(0, 4, utf8_decode_safe('Sistema de Gestión de Calificaciones - Versión 1.0'), 0, 1, 'R');

    return $pdf->Output('S'); // Devolver como string
}

// Si se está llamando este archivo directamente (no desde include), ejecutar el flujo normal
if (!defined('CALLED_FROM_INCLUDE')) {
    // Iniciar buffer de salida para capturar cualquier output no deseado
    ob_start();

    require_once "check_session.php";
    require_once "../conection.php";
    require_once "../fpdf.php";

    // Validar que el id esté en la sesión
    if (!isset($_SESSION['user_id'])) {
        error_log("Error de sesión: No se encontró el ID del usuario");
        die("Error de autenticación. Por favor, inicie sesión nuevamente.");
    }

    // Obtener parámetros de la URL/POST y validarlos
    $idStudent = isset($_GET['idStudent']) ? intval($_GET['idStudent']) : (isset($_POST['idStudent']) ? intval($_POST['idStudent']) : 0);
    $idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : (isset($_POST['idSchoolYear']) ? intval($_POST['idSchoolYear']) : 0);
    $idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : (isset($_POST['idSchoolQuarter']) ? intval($_POST['idSchoolQuarter']) : 0);
    $downloadMode = isset($_GET['download']) ? intval($_GET['download']) : (isset($_POST['download']) ? intval($_POST['download']) : 0);

    if (!$idStudent || !$idSchoolYear || !$idSchoolQuarter) {
        die("Parámetros faltantes para generar la boleta. Student: $idStudent, Year: $idSchoolYear, Quarter: $idSchoolQuarter");
    }

    try {
        // Usar la función para generar el PDF
        $pdfContent = generateStudentPDF($idStudent, $idSchoolYear, $idSchoolQuarter, $conexion);
        
        // Limpiar cualquier output buffer antes de generar el PDF
        ob_end_clean();

        // Generar el PDF según el modo
        if ($downloadMode === 1) {
            // Modo para descarga programática (devolver contenido)
            echo $pdfContent;
        } else {
            // Modo normal (mostrar en navegador)
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Boleta_Estudiante_' . $idStudent . '.pdf"');
            echo $pdfContent;
        }

    } catch (Exception $e) {
        die("Error generando PDF: " . $e->getMessage());
    }
}
?>