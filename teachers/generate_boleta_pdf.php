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
function generateStudentPDF($idStudent, $idSchoolYear, $idSchoolQuarter, $idTeacher, $conexion) {
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
                    WHERE st.idStudent = ? AND st.idSchoolYear = ? AND tgs.idTeacher = ?
                    ORDER BY la.name, s.name";

    $stmtSubjects = $conexion->prepare($sqlSubjects);
    if (!$stmtSubjects) {
        throw new Exception("Error preparando consulta subjects: " . $conexion->error);
    }
    $stmtSubjects->bind_param("iii", $idStudent, $idSchoolYear, $idTeacher);
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
    $pdf = new FPDF();
    $pdf->AddPage();

    // Logo y encabezado (FPDF solo soporta JPG, PNG, GIF)
    $logoPaths = [
        '../img/logo.png',
        '../img/logo.jpg',
        '../img/logo.jpeg',
        '../img/logo.gif'
    ];
    
    foreach ($logoPaths as $logoPath) {
        if (file_exists($logoPath)) {
            try {
                $pdf->Image($logoPath, 15, 15, 25, 25);
                break;
            } catch (Exception $e) {
                error_log("Error al cargar logo: " . $e->getMessage());
            }
        }
    }

    // Título del documento - posicionado al lado derecho del logo
    $pdf->SetXY(45, 18);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode_safe('ESCUELA PRIMARIA'), 0, 1, 'L');
    $pdf->SetXY(45, 26);
    $pdf->Cell(0, 8, utf8_decode_safe('GREGORIO TORRES QUINTERO NO.2308'), 0, 1, 'L');
    $pdf->Ln(10);
    
    // Título de boleta de calificaciones centrado
    $pdf->SetFont('Times', 'B', 19);
    $pdf->Cell(0, 10, utf8_decode_safe('BOLETA DE CALIFICACIONES'), 0, 1, 'C');

    // Línea separadora
    $pdf->Ln(15);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(10);

    // Información del estudiante
    $pdf->SetFont('Times', 'B', 14);
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, utf8_decode_safe('DATOS DEL ESTUDIANTE'), 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(250, 250, 250);

    // Primera fila
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Nombre completo:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(140, 8, utf8_decode_safe($student['names'] . ' ' . $student['lastnamePa'] . ' ' . $student['lastnameMa']), 1, 1, 'L');

    // Segunda fila
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Grado y Grupo:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(65, 8, utf8_decode_safe($student['grade'] . '° ' . $student['group_']), 1, 0, 'L');
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(32, 8, utf8_decode_safe('Año Escolar:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(43, 8, substr($student['schoolYear'], 0, 4), 1, 1, 'L');

    // Tercera fila
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Trimestre:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(65, 8, utf8_decode_safe($student['quarterName']), 1, 0, 'L');

    if (!empty($student['curp'])) {
        $pdf->SetFont('Times', '', 11);
        $pdf->Cell(32, 8, 'CURP:', 1, 0, 'L', true);
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(43, 8, utf8_decode_safe($student['curp']), 1, 1, 'L');
    } else {
        $pdf->Cell(65, 8, '', 1, 1, 'L');
    }

    $pdf->Ln(10);

    // Promedio General
    $pdf->SetFont('Times', 'B', 14);

    // Determinar color del promedio general
    if ($generalAverage >= 9) {
        $pdf->SetFillColor(76, 194, 23); // Verde fuerte
    } elseif ($generalAverage >= 7) {
        $pdf->SetFillColor(255, 251, 23); // Amarillo fuerte
    } elseif ($generalAverage >= 0) {
        $pdf->SetFillColor(242, 74, 41); // Rojo fuerte (incluir 0)
    } else {
        $pdf->SetFillColor(158, 158, 158); // Gris fuerte
    }

    $pdf->Cell(0, 12, utf8_decode_safe('PROMEDIO GENERAL: ' . number_format($generalAverage, 1)), 1, 1, 'C', true);
    $pdf->Ln(8);

    // Tabla de calificaciones
    $pdf->SetFont('Times', 'B', 14);
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, utf8_decode_safe('CALIFICACIONES POR MATERIA'), 1, 1, 'C', true);

    // Encabezado de la tabla
    $pdf->SetFont('Times', 'B', 12);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(60, 10, utf8_decode_safe('CAMPO FORMATIVO'), 1, 0, 'C', true);
    $pdf->Cell(80, 10, utf8_decode_safe('MATERIA'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, utf8_decode_safe('CALIFICACIÓN'), 1, 1, 'C', true);

    // Filas de la tabla por áreas de aprendizaje
    $pdf->SetFont('Times', '', 10);

    foreach ($learningAreas as $area) {
        $subjectCount = count($area['subjects']);
        
        // Determinar color de fondo para el área
        $areaAverage = $area['average'];
        if ($areaAverage >= 9) {
            $pdf->SetFillColor(76, 194, 23); // Verde fuerte
        } elseif ($areaAverage >= 7) {
            $pdf->SetFillColor(255, 251, 23); // Amarillo fuerte
        } elseif ($areaAverage >= 0) {
            $pdf->SetFillColor(242, 74, 41); // Rojo fuerte
        } else {
            $pdf->SetFillColor(158, 158, 158); // Gris fuerte
        }
        
        // Primera fila: Área de aprendizaje con su promedio
        $pdf->SetFont('Times', 'B', 10);
        $y = $pdf->GetY();
        
        // Celda del área (usando rowspan simulado)
        $areaText = utf8_decode_safe($area['name']);
        
        // Calcular altura necesaria para el área
        $rowHeight = 8;
        $totalHeight = $rowHeight * $subjectCount;
        
        // Dibujar celda del área manualmente para simular rowspan
        $pdf->Rect($pdf->GetX(), $y, 60, $totalHeight, 'FD');
        
        // Centrar texto del área verticalmente
        $middleY = $y + ($totalHeight / 2) - 2;
        $pdf->SetXY($pdf->GetX() + 2, $middleY - 1);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(56, 4, $areaText, 0, 0, 'C');
        
        // Ahora dibujar las materias y calificaciones
        $currentY = $y;
        foreach ($area['subjects'] as $index => $subject) {
            // Determinar color de fondo según la calificación de la materia
            if ($subject['average'] >= 9) {
                $pdf->SetFillColor(76, 194, 23); // Verde fuerte
            } elseif ($subject['average'] >= 7) {
                $pdf->SetFillColor(255, 251, 23); // Amarillo fuerte
            } elseif ($subject['average'] >= 0) {
                $pdf->SetFillColor(242, 74, 41); // Rojo fuerte
            } else {
                $pdf->SetFillColor(158, 158, 158); // Gris fuerte
            }
            
            // Posicionarse para la materia
            $pdf->SetXY(70, $currentY);
            $pdf->SetFont('Times', 'B', 10);
            
            // Celda de materia
            $pdf->Cell(80, $rowHeight, utf8_decode_safe($subject['name']), 1, 0, 'C', true);
            
            // Celda de calificación
            $pdf->Cell(50, $rowHeight, number_format($subject['average'], 1), 1, 0, 'C', true);
            
            $currentY += $rowHeight;
        }
        
        // Posicionarse después del área completa
        $pdf->SetXY(10, $y + $totalHeight);
        
        // Añadir un pequeño espacio entre áreas
        $pdf->Ln(2);
    }

    // Información adicional
    $pdf->Ln(15);

    // Línea separadora
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    // Escala de calificaciones
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(0, 6, utf8_decode_safe('ESCALA DE CALIFICACIONES:'), 0, 1, 'L');
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(0, 5, utf8_decode_safe('Excelente: 9.0 - 10.0  |  Bien: 7.0 - 8.9  |  Suficiente: 6.0 - 6.9  |  Insuficiente: 0.0 - 5.9'), 0, 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('Times', 'I', 8);
    // Obtener hora actual y restar 1 hora (diferencia entre Perú y México)
    $mexicoTime = time() - 3600; // 3600 segundos = 1 hora
    $pdf->Cell(0, 4, utf8_decode_safe('Documento generado el: ' . date('d/m/Y H:i:s', $mexicoTime)), 0, 1, 'R');
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

    // Obtener el idTeacher usando el idUser de la sesión
    $idUser = $_SESSION['user_id'];
    $sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
    $stmtTeacher = $conexion->prepare($sqlTeacher);
    if (!$stmtTeacher) {
        die("Error preparando consulta teacher: " . $conexion->error);
    }
    $stmtTeacher->bind_param("i", $idUser);
    $stmtTeacher->execute();
    $resTeacher = $stmtTeacher->get_result();
    $rowTeacher = $resTeacher->fetch_assoc();
    if (!$rowTeacher) {
        die("No se pudo cargar la información del docente para user ID: $idUser");
    }
    $idTeacher = $rowTeacher['idTeacher'];
    $stmtTeacher->close();

    try {
        // Usar la función para generar el PDF
        $pdfContent = generateStudentPDF($idStudent, $idSchoolYear, $idSchoolQuarter, $idTeacher, $conexion);
        
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
    die("Error preparando consulta student: " . $conexion->error);
}
$stmtStudent->bind_param("iii", $idStudent, $idSchoolYear, $idSchoolQuarter);
$stmtStudent->execute();
$resultStudent = $stmtStudent->get_result();
$student = $resultStudent->fetch_assoc();
$stmtStudent->close();

if (!$student) {
    die("No se encontró información del estudiante con ID: $idStudent, Year: $idSchoolYear, Quarter: $idSchoolQuarter");
}

// Obtener las materias del estudiante basándose en su grupo con sus áreas de aprendizaje
// Filtrar solo las materias del teacher logueado
$sqlSubjects = "SELECT DISTINCT s.idSubject, s.name as subjectName, s.idLearningArea, la.name as learningAreaName
                FROM subjects s
                JOIN learningArea la ON s.idLearningArea = la.idLearningArea
                WHERE s.idSubject IN (
                    SELECT DISTINCT tgs.idSubject 
                    FROM teacherGroupsSubjects tgs 
                    JOIN students st ON st.idGroup = tgs.idGroup 
                    WHERE st.idStudent = ? AND st.idSchoolYear = ? AND tgs.idTeacher = ?
                )
                ORDER BY la.name, s.name";

$stmtSubjects = $conexion->prepare($sqlSubjects);
if (!$stmtSubjects) {
    die("Error preparando consulta subjects: " . $conexion->error);
}

$stmtSubjects->bind_param("iii", $idStudent, $idSchoolYear, $idTeacher);
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
        error_log("Error preparando consulta average: " . $conexion->error);
        continue;
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
}
$stmtSubjects->close();

// Calcular promedio por área de aprendizaje
foreach ($learningAreas as $areaId => &$area) {
    $area['average'] = $area['subjectCount'] > 0 ? ceil(($area['totalGrade'] / $area['subjectCount']) * 10) / 10 : 0;
}
unset($area); // Liberar la referencia para evitar problemas en bucles posteriores

// Calcular promedio general basado en promedios de áreas
$areaAverages = array_column($learningAreas, 'average');
$generalAverage = count($areaAverages) > 0 ? ceil((array_sum($areaAverages) / count($areaAverages)) * 10) / 10 : 0;

// Si no se encontraron materias, agregar mensaje explicativo
if (empty($subjects)) {
    $subjects[] = [
        'name' => 'No se encontraron materias asignadas',
        'average' => 0
    ];
}

// Verificar que tenemos información mínima para generar el PDF
if (empty($student['names'])) {
    die("Error: Información del estudiante incompleta");
}

try {
    // Crear el PDF con soporte para UTF-8
    $pdf = new FPDF();
    $pdf->AddPage();

    // Logo y encabezado (FPDF solo soporta JPG, PNG, GIF)
    $logoPaths = [
        '../img/logo.png',
        '../img/logo.jpg',
        '../img/logo.jpeg',
        '../img/logo.gif'
    ];
    
    foreach ($logoPaths as $logoPath) {
        if (file_exists($logoPath)) {
            try {
                $pdf->Image($logoPath, 15, 15, 25, 25);
                break;
            } catch (Exception $e) {
                error_log("Error al cargar logo: " . $e->getMessage());
            }
        }
    }

    // Título del documento - posicionado al lado derecho del logo
    $pdf->SetXY(45, 18);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode_safe('ESCUELA PRIMARIA'), 0, 1, 'L');
    $pdf->SetXY(45, 26);
    $pdf->Cell(0, 8, utf8_decode_safe('GREGORIO TORRES QUINTERO NO.2308'), 0, 1, 'L');
    $pdf->Ln(10);
    
    // Título de boleta de calificaciones centrado
    $pdf->SetFont('Times', 'B', 19);
    $pdf->Cell(0, 10, utf8_decode_safe('BOLETA DE CALIFICACIONES'), 0, 1, 'C');

    // Línea separadora
    $pdf->Ln(15);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(10);

// Información del estudiante
$pdf->SetFont('Times', 'B', 14);
$pdf->SetFillColor(25, 46, 78);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, utf8_decode_safe('DATOS DEL ESTUDIANTE'), 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Times', '', 11);
$pdf->SetFillColor(250, 250, 250);

// Primera fila
$pdf->Cell(50, 8, utf8_decode_safe('Nombre completo:'), 1, 0, 'L', true);
$pdf->SetFont('Times', '', 11);
$pdf->Cell(140, 8, utf8_decode_safe($student['names'] . ' ' . $student['lastnamePa'] . ' ' . $student['lastnameMa']), 1, 1, 'L');

// Segunda fila
$pdf->SetFont('Times', '', 11);
$pdf->Cell(50, 8, utf8_decode_safe('Grado y Grupo:'), 1, 0, 'L', true);
$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(65, 8, utf8_decode_safe($student['grade'] . '° ' . $student['group_']), 1, 0, 'L');
$pdf->SetFont('Times', '', 11);
$pdf->Cell(32, 8, utf8_decode_safe('Año Escolar:'), 1, 0, 'L', true);
$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(43, 8, substr($student['schoolYear'], 0, 4), 1, 1, 'L');

// Tercera fila
$pdf->SetFont('Times', '', 11);
$pdf->Cell(50, 8, utf8_decode_safe('Trimestre:'), 1, 0, 'L', true);
$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(65, 8, utf8_decode_safe($student['quarterName']), 1, 0, 'L');

if (!empty($student['curp'])) {
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(32, 8, 'CURP:', 1, 0, 'L', true);
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(43, 8, utf8_decode_safe($student['curp']), 1, 1, 'L');
} else {
    $pdf->Cell(65, 8, '', 1, 1, 'L');
}

$pdf->Ln(10);

// Promedio General
$pdf->SetFont('Times', 'B', 14);

// Determinar color del promedio general
if ($generalAverage >= 9) {
    $pdf->SetFillColor(76, 194, 23); // Verde fuerte
} elseif ($generalAverage >= 7) {
    $pdf->SetFillColor(240, 240, 67); // Amarillo fuerte
} elseif ($generalAverage >= 0) {
    $pdf->SetFillColor(242, 74, 41); // Rojo fuerte (incluir 0)
} else {
    $pdf->SetFillColor(158, 158, 158); // Gris fuerte
}

$pdf->Cell(0, 12, utf8_decode_safe('PROMEDIO GENERAL: ' . number_format($generalAverage, 1)), 1, 1, 'C', true);
$pdf->Ln(8);

// Tabla de calificaciones
$pdf->SetFont('Times', 'B', 14);
$pdf->SetFillColor(25, 46, 78);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, utf8_decode_safe('CALIFICACIONES POR MATERIA'), 1, 1, 'C', true);

// Encabezado de la tabla con áreas de aprendizaje
$pdf->SetFont('Times', 'B', 12);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(60, 10, utf8_decode_safe('CAMPO FORMATIVO'), 1, 0, 'C', true);
$pdf->Cell(80, 10, utf8_decode_safe('MATERIA'), 1, 0, 'C', true);
$pdf->Cell(50, 10, utf8_decode_safe('CALIFICACIÓN'), 1, 1, 'C', true);

// Restablecer color de texto
$pdf->SetTextColor(0, 0, 0);

// Filas de la tabla por áreas de aprendizaje
$pdf->SetFont('Times', 'B', 10);

foreach ($learningAreas as $area) {
    $subjectCount = count($area['subjects']);
    
    // Determinar color de fondo para el área
    $areaAverage = $area['average'];
    if ($areaAverage >= 9) {
        $pdf->SetFillColor(76, 194, 23); // Verde fuerte
    } elseif ($areaAverage >= 7) {
        $pdf->SetFillColor(240, 240, 67); // Amarillo fuerte
    } elseif ($areaAverage >= 0) {
        $pdf->SetFillColor(242, 74, 41); // Rojo fuerte
    } else {
        $pdf->SetFillColor(158, 158, 158); // Gris fuerte
    }
    
    // Primera fila: Área de aprendizaje con su promedio
    $pdf->SetFont('Times', 'B', 10);
    $y = $pdf->GetY();
    
    // Celda del área (usando rowspan simulado)
    $areaText = utf8_decode_safe($area['name']);
    
    // Calcular altura necesaria para el área
    $rowHeight = 8;
    $totalHeight = $rowHeight * $subjectCount;
    
    // Dibujar celda del área manualmente para simular rowspan
    $pdf->Rect($pdf->GetX(), $y, 60, $totalHeight, 'FD');
    
    // Centrar texto del área verticalmente
    $middleY = $y + ($totalHeight / 2) - 2;
    $pdf->SetXY($pdf->GetX() + 2, $middleY - 1);
    $pdf->Cell(56, 4, $areaText, 0, 0, 'C');
    
    // Ahora dibujar las materias y calificaciones
    $currentY = $y;
    foreach ($area['subjects'] as $index => $subject) {
        // Determinar color de fondo según la calificación de la materia
        if ($subject['average'] >= 9) {
            $pdf->SetFillColor(76, 194, 23); // Verde fuerte
        } elseif ($subject['average'] >= 7) {
            $pdf->SetFillColor(240, 240, 67); // Amarillo fuerte
        } elseif ($subject['average'] >= 0) {
            $pdf->SetFillColor(242, 74, 41); // Rojo fuerte
        } else {
            $pdf->SetFillColor(158, 158, 158); // Gris fuerte
        }
        
        // Posicionarse para la materia
        $pdf->SetXY(70, $currentY);
        $pdf->SetFont('Times', '', 10);
        
        // Celda de materia
        $pdf->Cell(80, $rowHeight, utf8_decode_safe($subject['name']), 1, 0, 'L', true);
        
        // Celda de calificación
        $pdf->Cell(50, $rowHeight, number_format($subject['average'], 1), 1, 0, 'C', true);
        
        $currentY += $rowHeight;
    }
    
    // Posicionarse después del área completa
    $pdf->SetXY(10, $y + $totalHeight);
    
    // Añadir un pequeño espacio entre áreas
    $pdf->Ln(2);
}

// Información adicional
$pdf->Ln(15);

// Línea separadora
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

// Escala de calificaciones
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 6, utf8_decode_safe('ESCALA DE CALIFICACIONES:'), 0, 1, 'L');
$pdf->SetFont('Times', '', 9);
$pdf->Cell(0, 5, utf8_decode_safe('Excelente: 9.0 - 10.0  |  Bien: 7.0 - 8.9  |  Suficiente: 6.0 - 6.9  |  Insuficiente: 0.0 - 5.9'), 0, 1, 'L');
$pdf->Ln(5);

$pdf->SetFont('Times', 'I', 8);
// Obtener hora actual y restar 1 hora (diferencia entre Perú y México)
$mexicoTime = time() - 3600; // 3600 segundos = 1 hora
$pdf->Cell(0, 4, utf8_decode_safe('Documento generado el: ' . date('d/m/Y H:i:s', $mexicoTime)), 0, 1, 'R');
$pdf->Cell(0, 4, utf8_decode_safe('Sistema de Gestión de Calificaciones - Versión 1.0'), 0, 1, 'R');

// Limpiar cualquier output buffer antes de generar el PDF
ob_end_clean();

// Generar el PDF
$pdf->Output('I', 'Boleta_' . utf8_decode_safe($student['names']) . '_' . utf8_decode_safe($student['lastnamePa']) . '.pdf');

} catch (Exception $e) {
    die("Error generando PDF: " . $e->getMessage());
}
?>
