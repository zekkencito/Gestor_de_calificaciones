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
    
    // Obtener los datos del reporte con información del estudiante y docente
    $sql = "SELECT 
                $conductReportSelect,
                s.idStudent,
                s.schoolNum,
                ui.names as studentNames,
                ui.lastnamePa as studentLastnamePa,
                ui.lastnameMa as studentLastnameMa,
                g.grade,
                g.group_,
                s.curp,
                uit.names as teacherNames,
                uit.lastnamePa as teacherLastnamePa,
                uit.lastnameMa as teacherLastnameMa,
                t.profesionalID
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
    
    foreach ($logoPaths as $logoPath) {
        if (file_exists($logoPath)) {
            try {
                $pdf->Image($logoPath, 15, 15, 25);
                break;
            } catch (Exception $e) {
                error_log("Error al cargar logo: " . $e->getMessage());
            }
        }
    }
    
    // Título principal
    $pdf->SetFont('Times', 'B', 18);
    $pdf->SetY(18);
    $pdf->Cell(0, 10, utf8_decode_safe('REPORTE DE CONDUCTA'), 0, 1, 'C');
    
    // Subtítulo
    $pdf->SetFont('Times', 'I', 12);
    $pdf->Cell(0, 8, utf8_decode_safe('Escuela Primaria Gregorio Torres Quintero NO.2308'), 0, 1, 'C');
    $pdf->Ln(15);
    
    $pdf->SetFont('Times', 'I', 6);
    $pdf->Cell(0, 5, utf8_decode_safe('Generado el ' . date('d/m/Y H:i')), 0, 0, 'C');
    $pdf->Ln(15);


    // Datos del estudiante
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode_safe('DATOS DEL ESTUDIANTE'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    // Nombre completo del estudiante
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(50, 7, utf8_decode_safe('Nombre completo:'), 0, 0, 'L');
    $pdf->SetFont('Times', '', 11);
    $nombreCompleto = $data['studentNames'] . ' ' . $data['studentLastnamePa'] . ' ' . $data['studentLastnameMa'];
    $pdf->Cell(0, 7, utf8_decode_safe($nombreCompleto), 0, 1, 'L');
    
    // Grado y grupo
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(50, 7, utf8_decode_safe('Grado y Grupo:'), 0, 0, 'L');
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(50, 7, utf8_decode_safe($data['grade'] . '° ' . $data['group_']), 0, 0, 'L');
    
    
    $pdf->Ln(6);
    
    // Información del reporte
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode_safe('INFORMACIÓN DEL REPORTE'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    // Fecha del reporte
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(40, 7, 'Fecha:', 0, 0, 'L');
    $pdf->SetFont('Times', '', 11);
    $fechaFormateada = date('d/m/Y', strtotime($data['fecha']));
    $pdf->Cell(0, 7, $fechaFormateada, 0, 1, 'L');
    
    // Tipo de reporte
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(40, 7, 'Tipo de reporte:', 0, 0, 'L');
    $pdf->SetFont('Times', '', 11);
    $pdf->Cell(0, 7, utf8_decode_safe($data['tipo']), 0, 1, 'L');
    
    $pdf->Ln(3);
    
    // Descripción del incidente
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(0, 7, utf8_decode_safe('Descripción del incidente:'), 0, 1, 'L');
    $pdf->SetFont('Times', '', 10);
    $pdf->MultiCell(0, 5, utf8_decode_safe($data['descripcion']), 0, 'J');
    
    $pdf->Ln(3);
    
    // Observaciones (si existen)
    if (!empty($data['observaciones'])) {
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(0, 7, 'Observaciones:', 0, 1, 'L');
        $pdf->SetFont('Times', '', 10);
        $pdf->MultiCell(0, 5, utf8_decode_safe($data['observaciones']), 0, 'J');
        $pdf->Ln(3);
    }
    
    $pdf->Ln(6);
    
    // Información del docente
    $pdf->SetFillColor(25, 46, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode_safe('DOCENTE QUE REPORTA'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    // Nombre del docente
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(50, 7, utf8_decode_safe('Docente:'), 0, 0, 'L');
    $pdf->SetFont('Times', '', 11);
    $nombreDocente = ($data['teacherNames'] ?? '') . ' ' . ($data['teacherLastnamePa'] ?? '') . ' ' . ($data['teacherLastnameMa'] ?? '');
    $pdf->Cell(0, 7, utf8_decode_safe(trim($nombreDocente)), 0, 1, 'L');
    
    // Fecha de creación del reporte
    $pdf->SetFont('Times', 'B', 11);
    $pdf->Cell(50, 7, utf8_decode_safe('Fecha de creación:'), 0, 0, 'L');
    $pdf->SetFont('Times', '', 11);
    
    // Si createdAt existe y es válido, usarlo; sino usar la fecha del reporte
    if (!empty($data['createdAt']) && $data['createdAt'] != '0000-00-00 00:00:00') {
        $fechaCreacion = date('d/m/Y H:i', strtotime($data['createdAt']));
    } elseif (!empty($data['fecha'])) {
        $fechaCreacion = date('d/m/Y', strtotime($data['fecha']));
    } else {
        $fechaCreacion = date('d/m/Y');
    }
    $pdf->Cell(0, 7, $fechaCreacion, 0, 1, 'L');
    
    $pdf->Ln(10);
    
    // Espacio para firmas
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.3);
    
    // Firma del docente
    $pdf->Line(30, $pdf->GetY(), 90, $pdf->GetY());
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(70, 5, utf8_decode_safe('Firma del docente'), 0, 0, 'C');
    
    // Firma del director
    $pdf->Line(120, $pdf->GetY(), 180, $pdf->GetY());
    $pdf->SetX(110);
    $pdf->Cell(70, 5, utf8_decode_safe('Dirección'), 0, 1, 'C');    
    
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
