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
    $pdf->SetMargins(13, 15, 13);
    
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
    
    $pdf->Ln(8);
    
    // Asignar variables del estudiante
    $studentLastnamePa = $data['studentLastnamePa'] ?? '';
    $studentLastnameMa = $data['studentLastnameMa'] ?? '';
    $studentNames = $data['studentNames'] ?? '';
    $grade = $data['grade'] ?? '';
    $group = $data['group_'] ?? '';

    // ===== DATOS DEL ESTUDIANTE =====
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('DATOS DEL ESTUDIANTE'), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);
    
    // Información del estudiante en dos filas
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(45, 5, utf8_decode_safe('Apellido Paterno: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(50, 5, $studentLastnamePa, 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(35, 5, utf8_decode_safe('Apellido Materno: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, utf8_decode_safe($studentLastnameMa), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(30, 5, utf8_decode_safe('Nombre(s): '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(65, 5, utf8_decode_safe($studentNames), 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(20, 5, utf8_decode_safe('Grado: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(15, 5, utf8_decode_safe($grade), 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(15, 5, utf8_decode_safe('Grupo: '), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, utf8_decode_safe($group), 0, 1, 'L');
    
    $pdf->Ln(8);


    // ===== INFORMACIÓN DEL REPORTE =====
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('INFORMACIÓN DEL REPORTE'), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);
    
    // Fecha del reporte
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(50, 5, 'Fecha de creacion:', 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $fechaFormateada = date('d/m/Y', strtotime($data['fecha']));
    $pdf->Cell(0, 5, $fechaFormateada, 0, 1, 'L');
    
    $pdf->Ln(5);

    // Descripción del incidente
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 6, utf8_decode_safe('Descripción del incidente:'), 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->MultiCell(0, 4, utf8_decode_safe($data['descripcion']), 0, 'J');
    
    $pdf->Ln(5);
    
    // Observaciones (si existen)
    if (!empty($data['observaciones'])) {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Observaciones:', 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->MultiCell(0, 4, utf8_decode_safe($data['observaciones']), 0, 'J');
        $pdf->Ln(2);
    }
    
    $pdf->Ln(8);
    
    // ===== DOCENTE DE TURNO =====
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('DOCENTE DE TURNO'), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);
    
    // Nombre del docente
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(50, 5, utf8_decode_safe('Nombre:'), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $nombreDocente = ($data['teacherNames'] ?? '') . ' ' . ($data['teacherLastnamePa'] ?? '') . ' ' . ($data['teacherLastnameMa'] ?? '');
    $pdf->Cell(0, 5, utf8_decode_safe(trim($nombreDocente)), 0, 1, 'L');
    
    $pdf->Ln(5);

    // ===== DOCENTE TITULAR =====
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode_safe('DOCENTE TITULAR'), 0, 1, 'L');
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 1, '', 0, 1);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);

    // Nombre del docente titular
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(50, 5, utf8_decode_safe('Nombre:'), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 9);
    $nombreDocenteTitular = ($data['teacherTitleNames'] ?? '') . ' ' . ($data['teacherTitleLastnamePa'] ?? '') . ' ' . ($data['teacherTitleLastnameMa'] ?? '');
    $pdf->Cell(0, 5, utf8_decode_safe(trim($nombreDocenteTitular)), 0, 1, 'L');
    
    $pdf->Ln(20);
    
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
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetY($yLinea + 1);
        
        // Docente de Turno (M. Especial)
        $pdf->SetY($yLinea + 1);
        $pdf->SetX($inicioX);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Docente de Turno'), 0, 0, 'C');
        $pdf->SetY($yLinea + 5);
        $pdf->SetX($inicioX);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('(M. Especial)'), 0, 1, 'C');
        
        // Docente Titular (Escolarizado)
        $pdf->SetY($yLinea + 1);
        $pdf->SetX($inicioX + $anchoFirma + $espacioEntre);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Docente Titular'), 0, 0, 'C');
        $pdf->SetY($yLinea + 5);
        $pdf->SetX($inicioX + $anchoFirma + $espacioEntre);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('(Escolarizado)'), 0, 1, 'C');
        
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
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetY($yLinea + 1);
        
        // Docente de Turno (Escolarizado)
        $pdf->SetY($yLinea + 1);
        $pdf->SetX($inicioX);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Docente de Turno'), 0, 0, 'C');
        $pdf->SetY($yLinea + 5);
        $pdf->SetX($inicioX);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('(Escolarizado)'), 0, 1, 'C');
        
        // Dirección
        $pdf->SetY($yLinea + 1);
        $pdf->SetX($inicioX + $anchoFirma + $espacioEntre);
        $pdf->Cell($anchoFirma, 4, utf8_decode_safe('Dirección'), 0, 1, 'C');
        
        $pdf->SetY($yLinea + 10);
    }    
    
    $pdf->Ln(8);
    
    // Pie de página con información de generación del documento
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->Cell(0, 4, utf8_decode_safe('Documento generado el: ' . date('d/m/Y H:i', strtotime('now'))), 0, 1, 'R');
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