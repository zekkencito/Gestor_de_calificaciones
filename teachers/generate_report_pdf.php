<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
date_default_timezone_set('America/Mexico_City');

require_once "check_session.php";
require_once "../conection.php";
require_once "../fpdf.php";

// Función auxiliar para codificación
function utf8_decode_safe($text) {
    return iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
}

// Función principal para generar el PDF del reporte
function generateReportPDF($idConductReport, $conexion) {
    $columnsResult = $conexion->query("SHOW COLUMNS FROM conductReports");
    $availableColumns = [];
    while ($col = $columnsResult->fetch_assoc()) {
        $availableColumns[] = $col['Field'];
    }
    
    $columnMapping = [
        'fecha' => ['date_', 'fecha', 'date', 'reportDate'],
        'tipo' => ['actionTaken', 'tipo', 'type', 'reportType'],
        'descripcion' => ['description', 'descripcion'],
        'observaciones' => ['feedback', 'observaciones', 'observations', 'comments'],
        'createdAt' => ['createdAt', 'created_at', 'dateCreated', 'created_date', 'date_created', 'timestamp']
    ];
    
    $selectConductReport = ['cr.idConductReport'];
    
    foreach ($columnMapping as $standardName => $possibleNames) {
        $found = false;
        foreach ($possibleNames as $colName) {
            if (in_array($colName, $availableColumns)) {
                $selectConductReport[] = "cr.$colName as $standardName";
                $found = true;
                break;
            }
        }
        if (!$found) {
            $selectConductReport[] = "NULL as $standardName";
        }
    }
    
    $conductReportSelect = implode(", ", $selectConductReport);
    
    // Obtener los datos del reporte con información del estudiante y docente que creó el reporte
    $sql = "SELECT 
                $conductReportSelect,
                s.idStudent,
                s.schoolNum,
                ui.names as studentNames,
                ui.lastnamePa as studentLastnamePa,
                ui.lastnameMa as studentLastnameMa,
                g.grade,
                g.group_,
                g.idGroup,
                s.curp,
                uit.names as teacherNames,
                uit.lastnamePa as teacherLastnamePa,
                uit.lastnameMa as teacherLastnameMa,
                t.profesionalID,
                t.typeTeacher
            FROM conductReports cr
            JOIN students s ON cr.idStudent = s.idStudent
            JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
            JOIN groups g ON s.idGroup = g.idGroup
            LEFT JOIN teachers t ON cr.idTeacher = t.idTeacher
            LEFT JOIN users u ON t.idUser = u.idUser
            LEFT JOIN usersInfo uit ON u.idUserInfo = uit.idUserInfo
            WHERE cr.idConductReport = ?";
    
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error en SQL PDF: $sql");
        error_log("Columnas disponibles: " . implode(', ', $availableColumns));
        throw new Exception("Error preparando consulta PDF: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $idConductReport);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        throw new Exception("No se encontró el reporte");
    }
    
    // Obtener el docente titular (Maestro de Escolarizado MS) del grupo del estudiante
    $idGroup = $data['idGroup'];
    
    // Primero intentar encontrar un maestro MS asignado al grupo
    $sqlTitular = "SELECT ui.names, ui.lastnamePa, ui.lastnameMa, t.typeTeacher
                   FROM teacherGroupsSubjects tgs
                   JOIN teachers t ON tgs.idTeacher = t.idTeacher
                   JOIN users u ON t.idUser = u.idUser
                   JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
                   WHERE tgs.idGroup = ? AND t.typeTeacher = 'MS'
                   LIMIT 1";
    $stmtTitular = $conexion->prepare($sqlTitular);
    $titular = null;
    if ($stmtTitular) {
        $stmtTitular->bind_param("i", $idGroup);
        $stmtTitular->execute();
        $resultTitular = $stmtTitular->get_result();
        $titular = $resultTitular->fetch_assoc();
        $stmtTitular->close();
    }
    
    // Si no hay maestro 'MS', buscar cualquier maestro asignado al grupo que no sea ME
    if (!$titular) {
        $sqlTitular2 = "SELECT DISTINCT ui.names, ui.lastnamePa, ui.lastnameMa, t.typeTeacher
                       FROM teacherGroupsSubjects tgs
                       JOIN teachers t ON tgs.idTeacher = t.idTeacher
                       JOIN users u ON t.idUser = u.idUser
                       JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
                       WHERE tgs.idGroup = ? AND t.typeTeacher != 'ME'
                       LIMIT 1";
        $stmtTitular2 = $conexion->prepare($sqlTitular2);
        if ($stmtTitular2) {
            $stmtTitular2->bind_param("i", $idGroup);
            $stmtTitular2->execute();
            $resultTitular2 = $stmtTitular2->get_result();
            $titular = $resultTitular2->fetch_assoc();
            $stmtTitular2->close();
        }
    }
    
    // Si aún no hay titular, buscar CUALQUIER maestro asignado al grupo
    if (!$titular) {
        $sqlTitular3 = "SELECT DISTINCT ui.names, ui.lastnamePa, ui.lastnameMa, t.typeTeacher
                       FROM teacherGroupsSubjects tgs
                       JOIN teachers t ON tgs.idTeacher = t.idTeacher
                       JOIN users u ON t.idUser = u.idUser
                       JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
                       WHERE tgs.idGroup = ?
                       LIMIT 1";
        $stmtTitular3 = $conexion->prepare($sqlTitular3);
        if ($stmtTitular3) {
            $stmtTitular3->bind_param("i", $idGroup);
            $stmtTitular3->execute();
            $resultTitular3 = $stmtTitular3->get_result();
            $titular = $resultTitular3->fetch_assoc();
            $stmtTitular3->close();
        }
    }
    
    // Si aún no hay titular, buscar el maestro MS logueado si existe
    if (!$titular && isset($_SESSION['user_id'])) {
        $sqlTitularLogueado = "SELECT ui.names, ui.lastnamePa, ui.lastnameMa, t.typeTeacher
                              FROM teachers t
                              JOIN users u ON t.idUser = u.idUser
                              JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
                              WHERE u.idUser = ? AND t.typeTeacher = 'MS'
                              LIMIT 1";
        $stmtTitularLogueado = $conexion->prepare($sqlTitularLogueado);
        if ($stmtTitularLogueado) {
            $stmtTitularLogueado->bind_param("i", $_SESSION['user_id']);
            $stmtTitularLogueado->execute();
            $resultTitularLogueado = $stmtTitularLogueado->get_result();
            $titular = $resultTitularLogueado->fetch_assoc();
            $stmtTitularLogueado->close();
        }
    }
    
    if ($titular) {
        $data['teacherTitleNames'] = $titular['names'];
        $data['teacherTitleLastnamePa'] = $titular['lastnamePa'];
        $data['teacherTitleLastnameMa'] = $titular['lastnameMa'];
    }
    
    // Crear PDF
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
    $pdf->SetFont('Times', '', 10);
    $pdf->Cell(0, 5, utf8_decode_safe('Escuela Primaria'), 0, 1, 'C');
    $pdf->SetFont('Times', '', 10);
    $pdf->Cell(0, 5, utf8_decode_safe('Gregorio Torres Quintero No.2308'), 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Título de reporte centrado
    $pdf->SetFont('Times', 'B', 14);
    $pdf->Cell(0, 10, utf8_decode_safe('BITÁCORA DE INCIDENCIA'), 0, 1, 'C');

    // Línea separadora
    $pdf->Ln(5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);


    // Datos del estudiante
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(0, 8, utf8_decode_safe('DATOS DEL ESTUDIANTE'), 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Times', '', 11);
    $pdf->SetFillColor(250, 250, 250);

    // Nombre completo del estudiante
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Nombre completo:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', '', 11);
    $nombreCompleto = $data['studentNames'] . ' ' . $data['studentLastnamePa'] . ' ' . $data['studentLastnameMa'];
    $pdf->Cell(0, 8, utf8_decode_safe($nombreCompleto), 1, 1, 'L');
    
    // Grado y grupo
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Grado y Grupo:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(0, 8, utf8_decode_safe($data['grade'] . '° ' . $data['group_']), 1, 1, 'L');
    
    $pdf->Ln(4);
    
    // Información del reporte
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(0, 8, utf8_decode_safe('INFORMACIÓN DEL REPORTE'), 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Times', '', 11);
    $pdf->SetFillColor(250, 250, 250);
    
    // Fecha del reporte
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, 'Fecha de creacion:', 1, 0, 'L', true);
    $pdf->SetFont('Times', '', 11);
    $fechaFormateada = date('d/m/Y', strtotime($data['fecha']));
    $pdf->Cell(0, 8, $fechaFormateada, 1, 1, 'L');
    
    $pdf->Ln(5);

    // Descripción del incidente
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(0, 7, utf8_decode_safe('Descripción del incidente:'), 0, 1, 'L');
    $pdf->SetFont('Times', '', 10);
    $pdf->MultiCell(0, 5, utf8_decode_safe($data['descripcion']), 0, 'J');
    
    $pdf->Ln(10);
    
    // Observaciones (si existen)
    if (!empty($data['observaciones'])) {
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(0, 6, 'Observaciones:', 0, 1, 'L');
        $pdf->SetFont('Times', '', 10);
        $pdf->MultiCell(0, 5, utf8_decode_safe($data['observaciones']), 0, 'J');
        $pdf->Ln(2);
    }
    
    $pdf->Ln(10);
    
    // Información del docente
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(0, 8, utf8_decode_safe('DOCENTE DE TURNO'), 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Times', '', 11);
    $pdf->SetFillColor(250, 250, 250);
    
    // Nombre del docente
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Nombre:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', '', 11);
    $nombreDocente = ($data['teacherNames'] ?? '') . ' ' . ($data['teacherLastnamePa'] ?? '') . ' ' . ($data['teacherLastnameMa'] ?? '');
    $pdf->Cell(0, 8, utf8_decode_safe(trim($nombreDocente)), 1, 1, 'L');
    
    // Fecha de creación del reporte
    /*$pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Fecha de creación:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', '', 11);
    
    // Si createdAt existe y es válido, usarlo; sino usar la fecha del reporte
    if (!empty($data['createdAt']) && $data['createdAt'] != '0000-00-00 00:00:00') {
        $fechaCreacion = date('d/m/Y H:i', strtotime($data['createdAt']));
    } elseif (!empty($data['fecha'])) {
        $fechaCreacion = date('d/m/Y', strtotime($data['fecha']));
    } else {
        $fechaCreacion = date('d/m/Y');
    }
    $pdf->Cell(0, 8, $fechaCreacion, 1, 1, 'L');
    */
    
    $pdf->Ln(4);

    //Información del Docente Titular
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(0, 8, utf8_decode_safe('DOCENTE TITULAR'), 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Times', '', 11);
    $pdf->SetFillColor(250, 250, 250);


    // Nombre del docente titular
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 8, utf8_decode_safe('Nombre:'), 1, 0, 'L', true);
    $pdf->SetFont('Times', '', 11);
    $nombreDocenteTitular = ($data['teacherTitleNames'] ?? '') . ' ' . ($data['teacherTitleLastnamePa'] ?? '') . ' ' . ($data['teacherTitleLastnameMa'] ?? '');
    $pdf->Cell(0, 8, utf8_decode_safe(trim($nombreDocenteTitular)), 1, 1, 'L');

    $pdf->Ln(30);
    
    // Espacio para firmas
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.3);
    
    // Verificar si el docente que creó la bitácora es de materia especial (ME)
    // typeTeacher puede ser 'ME' (Materia Especial) o 'MS' (Maestro Escolarizado)
    $typeTeacher = isset($data['typeTeacher']) ? strtoupper(trim($data['typeTeacher'])) : '';
    $esDocenteME = ($typeTeacher === 'ME');
    
    if ($esDocenteME) {
        // 3 firmas: Docente de Turno (ME), Docente Titular (MS), Director
        $anchoFirma = 50;
        $espacioEntre = 10;
        $inicioX = 20;
        $yLinea = $pdf->GetY();
        
        // Dibujar las 3 líneas primero (misma altura)
        $pdf->Line($inicioX, $yLinea, $inicioX + $anchoFirma, $yLinea);
        $pdf->Line($inicioX + $anchoFirma + $espacioEntre, $yLinea, $inicioX + ($anchoFirma * 2) + $espacioEntre, $yLinea);
        $pdf->Line($inicioX + ($anchoFirma * 2) + ($espacioEntre * 2), $yLinea, $inicioX + ($anchoFirma * 3) + ($espacioEntre * 2), $yLinea);
        
        // Textos debajo de las líneas
        $pdf->SetFont('Times', '', 9);
        $pdf->SetY($yLinea + 1);
        
        // Docente de Turno (M. Especial)
        $pdf->SetX($inicioX);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Docente de Turno'), 0, 0, 'C');
        $pdf->SetX($inicioX);
        $pdf->SetY($yLinea + 5);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('(M. Especial)'), 0, 0, 'C');
        
        // Docente Titular (Escolarizado)
        $pdf->SetY($yLinea + 1);
        $pdf->SetX($inicioX + $anchoFirma + $espacioEntre);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Docente Titular'), 0, 0, 'C');
        $pdf->SetY($yLinea + 5);
        $pdf->SetX($inicioX + $anchoFirma + $espacioEntre);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('(Escolarizado)'), 0, 0, 'C');
        
        // Dirección
        $pdf->SetY($yLinea + 1);
        $pdf->SetX($inicioX + ($anchoFirma * 2) + ($espacioEntre * 2));
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Dirección'), 0, 1, 'C');
        
        $pdf->SetY($yLinea + 10);
    } else {
        // 2 firmas: Docente de Turno (Escolarizado) y Director
        $anchoFirma = 60;
        $espacioEntre = 30;
        $inicioX = 30;
        $yLinea = $pdf->GetY();
        
        // Dibujar las 2 líneas primero (misma altura)
        $pdf->Line($inicioX, $yLinea, $inicioX + $anchoFirma, $yLinea);
        $pdf->Line($inicioX + $anchoFirma + $espacioEntre, $yLinea, $inicioX + ($anchoFirma * 2) + $espacioEntre, $yLinea);
        
        // Textos debajo de las líneas
        $pdf->SetFont('Times', '', 9);
        $pdf->SetY($yLinea + 1);
        
        // Docente de Turno (Escolarizado)
        $pdf->SetX($inicioX);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Docente de Turno'), 0, 0, 'C');
        $pdf->SetY($yLinea + 5);
        $pdf->SetX($inicioX);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('(Escolarizado)'), 0, 0, 'C');
        
        // Dirección
        $pdf->SetY($yLinea + 1);
        $pdf->SetX($inicioX + $anchoFirma + $espacioEntre);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Dirección'), 0, 1, 'C');
        
        $pdf->SetY($yLinea + 10);
    }    
    
    $pdf->Ln(8);
    
    // Pie de página con información de generación del documento
    
    $pdf->SetFont('Times', 'I', 8);
    // Obtener hora actual y restar 1 hora (diferencia de zona horaria)
    $mexicoTime = time() - 3600; // 3600 segundos = 1 hora
    $pdf->Cell(0, 4, utf8_decode_safe('Documento generado el: ' . date('d/m/Y H:i:s', $mexicoTime)), 0, 1, 'R');
    $pdf->Cell(0, 4, utf8_decode_safe('Sistema de Gestión de Calificaciones - Versión 1.0'), 0, 1, 'R');
    
    // Generar nombre del archivo para descarga
    $nombreEstudiante = trim($data['studentNames'] . '_' . $data['studentLastnamePa']);
    $nombreEstudiante = preg_replace('/[^a-zA-Z0-9_]/', '_', $nombreEstudiante);
    $filename = 'Reporte_Conducta_' . $nombreEstudiante . '.pdf';
    
    // Mostrar en navegador (inline, como las boletas)
    $pdf->Output('I', $filename);
}

// Si se llama directamente desde el navegador
if (isset($_GET['id'])) {
    $idConductReport = intval($_GET['id']);
    try {
        generateReportPDF($idConductReport, $conexion);
    } catch (Exception $e) {
        header('Content-Type: text/html; charset=UTF-8');
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Error</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body>
            <div class='container mt-5'>
                <div class='alert alert-danger text-center'>
                    <h4>Error al generar el reporte</h4>
                    <p>" . htmlspecialchars($e->getMessage()) . "</p>
                    <button onclick='window.close()' class='btn btn-secondary'>Cerrar</button>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
