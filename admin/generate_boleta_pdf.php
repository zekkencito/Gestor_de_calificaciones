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

    // ─── 1. Información del estudiante ──────────────────────────────────
    $sqlStudent = "SELECT s.idStudent, s.schoolNum, ui.lastnamePa, ui.lastnameMa, ui.names,
                          g.grade, g.group_, s.curp,
                          sy.startDate as schoolYear, sy.endDate,
                          sq.name as quarterName
                   FROM students s
                   JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
                   JOIN groups g ON s.idGroup = g.idGroup
                   JOIN schoolYear sy ON s.idSchoolYear = sy.idSchoolYear
                   CROSS JOIN schoolQuarter sq
                   WHERE s.idStudent = ? AND s.idSchoolYear = ? AND sq.idSchoolQuarter = ?";
    $stmtStudent = $conexion->prepare($sqlStudent);
    if (!$stmtStudent) throw new Exception("Error preparando consulta student: " . $conexion->error);
    $stmtStudent->bind_param("iii", $idStudent, $idSchoolYear, $idSchoolQuarter);
    $stmtStudent->execute();
    $student = $stmtStudent->get_result()->fetch_assoc();
    $stmtStudent->close();
    if (!$student) throw new Exception("No se encontró información del estudiante");

    // ─── 2. Todos los bimestres del ciclo escolar ────────────────────────
    $stmtAllQ = $conexion->prepare(
        "SELECT idSchoolQuarter, name FROM schoolQuarter WHERE idSchoolYear = ? ORDER BY idSchoolQuarter ASC"
    );
    $stmtAllQ->bind_param('i', $idSchoolYear);
    $stmtAllQ->execute();
    $allQuarters = $stmtAllQ->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtAllQ->close();

    // Solo mostrar bimestres hasta el actual (inclusive)
    $quartersToShow = [];
    foreach ($allQuarters as $q) {
        $quartersToShow[] = $q;
        if ((int)$q['idSchoolQuarter'] === (int)$idSchoolQuarter) break;
    }
    // Columna "Promedio Final" solo cuando se muestran TODOS los bimestres
    $showFinalAverage = count($quartersToShow) === count($allQuarters) && count($allQuarters) >= 2;

    // ─── 3. Materias y calificaciones por bimestre ──────────────────────
    $sqlSubjects = "SELECT DISTINCT s.idSubject, s.name as subjectName, s.idLearningArea, la.name as learningAreaName
                    FROM subjects s
                    JOIN learningArea la ON s.idLearningArea = la.idLearningArea
                    JOIN teacherGroupsSubjects tgs ON s.idSubject = tgs.idSubject
                    JOIN students st ON st.idGroup = tgs.idGroup
                    WHERE st.idStudent = ? AND st.idSchoolYear = ?
                    ORDER BY la.name, s.name";
    $stmtSubjects = $conexion->prepare($sqlSubjects);
    if (!$stmtSubjects) throw new Exception("Error preparando consulta subjects: " . $conexion->error);
    $stmtSubjects->bind_param("ii", $idStudent, $idSchoolYear);
    $stmtSubjects->execute();
    $resultSubjects = $stmtSubjects->get_result();

    $learningAreas = [];
    $generalSumByQ = array_fill_keys(array_column($quartersToShow, 'idSchoolQuarter'), 0.0);
    $generalCntByQ = array_fill_keys(array_column($quartersToShow, 'idSchoolQuarter'), 0);

    while ($subject = $resultSubjects->fetch_assoc()) {
        $gradesByQ = [];
        foreach ($quartersToShow as $q) {
            $qid = (int)$q['idSchoolQuarter'];
            $stmtAvg = $conexion->prepare(
                "SELECT average FROM average WHERE idStudent=? AND idSubject=? AND idSchoolYear=? AND idSchoolQuarter=?"
            );
            if (!$stmtAvg) throw new Exception("Error preparando consulta average");
            $stmtAvg->bind_param('iiii', $idStudent, $subject['idSubject'], $idSchoolYear, $qid);
            $stmtAvg->execute();
            $avgData = $stmtAvg->get_result()->fetch_assoc();
            $stmtAvg->close();
            // null = sin calificación capturada (celda en blanco)
            $grade = ($avgData && $avgData['average'] !== null)
                ? ceil(floatval($avgData['average']) * 10) / 10
                : null;
            $gradesByQ[$qid] = $grade;
            if ($grade !== null) {
                $generalSumByQ[$qid] += $grade;
                $generalCntByQ[$qid]++;
            }
        }

        $nonNull = array_filter($gradesByQ, fn($v) => $v !== null);
        $finalGrade = count($nonNull) > 0 ? round(array_sum($nonNull) / count($nonNull), 1) : null;

        $subjectData = [
            'name'          => $subject['subjectName'],
            'gradesByQ'     => $gradesByQ,
            'finalGrade'    => $finalGrade,
            'idLearningArea'=> $subject['idLearningArea'],
        ];

        $areaId = $subject['idLearningArea'];
        if (!isset($learningAreas[$areaId])) {
            $learningAreas[$areaId] = [
                'name'         => $subject['learningAreaName'],
                'subjects'     => [],
                'sumByQ'       => array_fill_keys(array_column($quartersToShow, 'idSchoolQuarter'), 0.0),
                'cntByQ'       => array_fill_keys(array_column($quartersToShow, 'idSchoolQuarter'), 0),
                'subjectCount' => 0,
            ];
        }
        $learningAreas[$areaId]['subjects'][] = $subjectData;
        foreach ($quartersToShow as $q) {
            $qid = (int)$q['idSchoolQuarter'];
            if ($gradesByQ[$qid] !== null) {
                $learningAreas[$areaId]['sumByQ'][$qid] += $gradesByQ[$qid];
                $learningAreas[$areaId]['cntByQ'][$qid]++;
            }
        }
        $learningAreas[$areaId]['subjectCount']++;
    }
    $stmtSubjects->close();

    foreach ($learningAreas as &$area) {
        $area['avgByQ'] = [];
        $nonNullAreaAvgs = [];
        foreach ($quartersToShow as $q) {
            $qid = (int)$q['idSchoolQuarter'];
            $avg = $area['cntByQ'][$qid] > 0
                ? round($area['sumByQ'][$qid] / $area['cntByQ'][$qid], 1)
                : null;
            $area['avgByQ'][$qid] = $avg;
            if ($avg !== null) $nonNullAreaAvgs[] = $avg;
        }
        $area['finalAverage'] = count($nonNullAreaAvgs) > 0
            ? round(array_sum($nonNullAreaAvgs) / count($nonNullAreaAvgs), 1)
            : null;
    }
    unset($area);

    $generalAvgByQ  = [];
    $nonNullGenAvgs = [];
    foreach ($quartersToShow as $q) {
        $qid = (int)$q['idSchoolQuarter'];
        $avg = $generalCntByQ[$qid] > 0
            ? round($generalSumByQ[$qid] / $generalCntByQ[$qid], 1)
            : null;
        $generalAvgByQ[$qid] = $avg;
        if ($avg !== null) $nonNullGenAvgs[] = $avg;
    }
    $generalFinalAvg = count($nonNullGenAvgs) > 0
        ? round(array_sum($nonNullGenAvgs) / count($nonNullGenAvgs), 1)
        : null;

    if (empty($learningAreas)) {
        $learningAreas[0] = [
            'name'         => 'Sin áreas asignadas',
            'subjects'     => [['name' => 'No se encontraron materias asignadas', 'gradesByQ' => [], 'finalGrade' => 0]],
            'avgByQ'       => [],
            'finalAverage' => 0,
            'subjectCount' => 1,
        ];
    }

    if (empty($student['names'])) throw new Exception("Error: Información del estudiante incompleta");

    // ─── 4. Generar PDF ─────────────────────────────────────────────────
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetMargins(20, 15, 20);

    $logoPaths = ['../img/logo.png','../img/logo.jpg','../img/logo.jpeg','../img/logo.gif'];
    $pageWidth = 216;
    $logoWidth = 20;
    $logoX = ($pageWidth - $logoWidth) / 2;
    foreach ($logoPaths as $logoPath) {
        if (file_exists($logoPath)) {
            try { $pdf->Image($logoPath, $logoX, 12, $logoWidth, $logoWidth); break; }
            catch (Exception $e) { error_log("Error al cargar logo: " . $e->getMessage()); }
        }
    }

    $pdf->SetY(35);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 5, utf8_decode_safe('Escuela Primaria'), 0, 1, 'C');
    $pdf->Cell(0, 5, utf8_decode_safe('Gregorio Torres Quintero No.2308'), 0, 1, 'C');
    $pdf->Ln(8);

    $lastnamePa = $student['lastnamePa'] ?? '';
    $lastnameMa = $student['lastnameMa'] ?? '';
    $names      = $student['names']      ?? '';
    $grade      = $student['grade']      ?? '';
    $group      = $student['group_']     ?? '';

    // DATOS DEL ALUMNO
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('DATOS DEL ALUMNO'), 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(45, 5, utf8_decode_safe('Apellido Paterno: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(50, 5, utf8_decode_safe($lastnamePa), 0, 0, 'L');
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

    // DATOS DE LA ESCUELA
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('DATOS DE LA ESCUELA'), 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);

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

    // TABLA DE CALIFICACIONES
    $totalW       = 176;
    $numGradeCols = count($quartersToShow) + ($showFinalAverage ? 1 : 0);
    $subjectColW  = match ($numGradeCols) {
        1       => 116,
        2       => 100,
        3       => 90,
        default => 82,
    };
    $gradeColW = ($totalW - $subjectColW) / max(1, $numGradeCols);

    $colLabels = [];
    foreach ($quartersToShow as $i => $q) {
        $colLabels[] = 'Bim. ' . ($i + 1);
    }
    if ($showFinalAverage) $colLabels[] = 'Prom. Final';

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell($subjectColW, 7, utf8_decode_safe('CAMPO FORMATIVO / MATERIA'), 1, 0, 'C');
    foreach ($colLabels as $lbl) {
        $pdf->Cell($gradeColW, 7, utf8_decode_safe($lbl), 1, 0, 'C');
    }
    $pdf->Ln();

    $pdf->SetTextColor(0, 0, 0);
    foreach ($learningAreas as $area) {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetFillColor(160, 160, 160);
        $pdf->Cell($subjectColW, 9, utf8_decode_safe($area['name']), 1, 0, 'C', true);
        foreach ($quartersToShow as $q) {
            $qid = (int)$q['idSchoolQuarter'];
            $avg = $area['avgByQ'][$qid] ?? null;
            $val = $avg !== null ? number_format($avg, 1) : '';
            $pdf->Cell($gradeColW, 9, $val, 1, 0, 'C', true);
        }
        if ($showFinalAverage) {
            $val = $area['finalAverage'] !== null ? number_format($area['finalAverage'], 1) : '';
            $pdf->Cell($gradeColW, 9, $val, 1, 0, 'C', true);
        }
        $pdf->Ln();

        foreach ($area['subjects'] as $subject) {
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell($subjectColW, 6, utf8_decode_safe($subject['name']), 1, 0, 'L');
            foreach ($quartersToShow as $q) {
                $qid = (int)$q['idSchoolQuarter'];
                $g = $subject['gradesByQ'][$qid] ?? null;
                $val = $g !== null ? number_format($g, 1) : '';
                $pdf->Cell($gradeColW, 6, $val, 1, 0, 'C');
            }
            if ($showFinalAverage) {
                $val = $subject['finalGrade'] !== null ? number_format($subject['finalGrade'], 1) : '';
                $pdf->Cell($gradeColW, 6, $val, 1, 0, 'C');
            }
            $pdf->Ln();
        }
    }

    // PROMEDIO GENERAL
    $pdf->Ln(8);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($subjectColW, 8, utf8_decode_safe('PROMEDIO GENERAL'), 1, 0, 'C', true);
    foreach ($quartersToShow as $q) {
        $qid = (int)$q['idSchoolQuarter'];
        $avg = $generalAvgByQ[$qid] ?? null;
        $val = $avg !== null ? number_format($avg, 1) : '';
        $pdf->Cell($gradeColW, 8, $val, 1, 0, 'C', true);
    }
    if ($showFinalAverage) {
        $val = $generalFinalAvg !== null ? number_format($generalFinalAvg, 1) : '';
        $pdf->Cell($gradeColW, 8, $val, 1, 0, 'C', true);
    }
    $pdf->Ln(15);

    // ESCALA DE CALIFICACIONES
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