<?php
// Habilitar logging para depuración
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'group_pdf_errors.log');

require_once "check_session.php";
include '../conection.php';

// --- VERIFICACIÓN DE FECHA LIMITE PARA DESCARGAS GRUPALES ---
$fechaLimite = null;
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $fechaLimite = $row['limitDate'];
}
$hoy = date('Y-m-d');
$descargasHabilitadas = ($fechaLimite && $hoy > date('Y-m-d', strtotime($fechaLimite . ' +0 day')));

// Si las descargas no están habilitadas, retornar error
if (!$descargasHabilitadas) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Las descargas se habilitarán después del ' . date('d/m/Y', strtotime($fechaLimite))
    ]);
    exit;
}

// Log para depuración
error_log("Iniciando generación de PDFs grupales");
error_log("POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método no permitido');
}

$idSchoolYear = isset($_POST['idSchoolYear']) ? intval($_POST['idSchoolYear']) : 0;
$idGroup = isset($_POST['idGroup']) ? intval($_POST['idGroup']) : 0;

if (!$idSchoolYear || !$idGroup) {
    http_response_code(400);
    die('Parámetros requeridos faltantes');
}

try {
    // Obtener información del grupo y año escolar
    $sqlGroupInfo = "SELECT CONCAT(g.grade, g.group_) as grupo, LEFT(sy.startDate, 4) as year 
                     FROM groups g 
                     INNER JOIN schoolYear sy ON sy.idSchoolYear = ? 
                     WHERE g.idGroup = ?";
    $stmtGroupInfo = $conexion->prepare($sqlGroupInfo);
    $stmtGroupInfo->bind_param("ii", $idSchoolYear, $idGroup);
    $stmtGroupInfo->execute();
    $groupInfo = $stmtGroupInfo->get_result()->fetch_assoc();
    
    if (!$groupInfo) {
        http_response_code(404);
        die('Grupo o año escolar no encontrado');
    }
    
    // Obtener trimestres del año escolar
    $sqlQuarters = "SELECT idSchoolQuarter FROM schoolQuarter WHERE idSchoolYear = ? OR idSchoolYear IS NULL ORDER BY idSchoolQuarter";
    $stmtQuarters = $conexion->prepare($sqlQuarters);
    $stmtQuarters->bind_param("i", $idSchoolYear);
    $stmtQuarters->execute();
    $quartersResult = $stmtQuarters->get_result();
    
    $quarters = [];
    while ($quarter = $quartersResult->fetch_assoc()) {
        $quarters[] = $quarter['idSchoolQuarter'];
    }
    
    if (empty($quarters)) {
        http_response_code(404);
        die('No se encontraron trimestres para este año escolar');
    }
    
    // Usar el primer trimestre encontrado para generar los PDFs
    $quarterId = $quarters[0];
    
    // Obtener estudiantes del grupo
    $sqlStudents = "SELECT s.idStudent, ui.names, ui.lastnamePa, ui.lastnameMa 
                    FROM students s 
                    INNER JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo 
                    WHERE s.idGroup = ? AND s.idSchoolYear = ? 
                    ORDER BY ui.lastnamePa, ui.lastnameMa, ui.names";
    
    $stmtStudents = $conexion->prepare($sqlStudents);
    $stmtStudents->bind_param("ii", $idGroup, $idSchoolYear);
    $stmtStudents->execute();
    $studentsResult = $stmtStudents->get_result();
    
    $students = [];
    while ($student = $studentsResult->fetch_assoc()) {
        $students[] = $student;
    }
    
    if (empty($students)) {
        error_log("No se encontraron estudiantes para grupo: $idGroup, año: $idSchoolYear");
        http_response_code(404);
        die('No se encontraron estudiantes en este grupo');
    }
    
    error_log("Encontrados " . count($students) . " estudiantes para procesar");
    
    // Crear directorio temporal para los PDFs
    $tempDir = sys_get_temp_dir() . '/group_pdfs_' . time();
    if (!mkdir($tempDir, 0755, true)) {
        error_log("No se pudo crear directorio temporal: $tempDir");
        http_response_code(500);
        die('No se pudo crear directorio temporal');
    }
    
    error_log("Directorio temporal creado: $tempDir");
    
    $generatedFiles = [];
    $errorCount = 0;
    
    // Incluir el generador original y definir constante para evitar ejecución directa
    define('CALLED_FROM_INCLUDE', true);
    require_once 'generate_boleta_pdf.php';
    
    // Generar PDF para cada estudiante usando la función
    foreach ($students as $student) {
        $studentName = trim($student['names'] . ' ' . $student['lastnamePa'] . ' ' . $student['lastnameMa']);
        $safeStudentName = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $studentName);
        $safeStudentName = preg_replace('/\s+/', '_', $safeStudentName);
        
        error_log("Procesando estudiante: $studentName (ID: {$student['idStudent']})");
        
        try {
            // Usar la función directamente
            $pdfContent = generateStudentPDF($student['idStudent'], $idSchoolYear, $quarterId, $conexion);
            
            if (!empty($pdfContent) && strlen($pdfContent) > 1000) {
                $filename = $tempDir . '/' . $safeStudentName . '.pdf';
                if (file_put_contents($filename, $pdfContent) !== false) {
                    $generatedFiles[] = $filename;
                    error_log("PDF generado exitosamente: $filename (" . strlen($pdfContent) . " bytes)");
                } else {
                    $errorCount++;
                    error_log("Error escribiendo archivo: $filename");
                }
            } else {
                $errorCount++;
                $contentLength = $pdfContent ? strlen($pdfContent) : 0;
                error_log("PDF inválido para estudiante {$student['idStudent']}: contenido de $contentLength bytes");
                if ($contentLength < 1000) {
                    error_log("Respuesta recibida: " . substr($pdfContent, 0, 500));
                }
            }
            
        } catch (Exception $e) {
            $errorCount++;
            error_log("Error generando PDF para estudiante " . $student['idStudent'] . ": " . $e->getMessage());
        }
    }
    
    if (empty($generatedFiles)) {
        error_log("No se pudo generar ningún PDF. Errores: $errorCount");
        // Limpiar directorio temporal
        array_map('unlink', glob($tempDir . '/*'));
        rmdir($tempDir);
        http_response_code(500);
        die("No se pudo generar ningún PDF. Se procesaron " . count($students) . " estudiantes con $errorCount errores.");
    }
    
    // Crear archivo ZIP
    $archiveName = "Boletas_Grupo_{$groupInfo['grupo']}_Año_{$groupInfo['year']}.zip";
    $archivePath = $tempDir . '/' . $archiveName;
    
    // Usar ZipArchive
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE) === TRUE) {
            foreach ($generatedFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            
            // Enviar el archivo al navegador
            if (file_exists($archivePath) && filesize($archivePath) > 0) {
                // Limpiar cualquier salida previa
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Establecer headers para descarga
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $archiveName . '"');
                header('Content-Length: ' . filesize($archivePath));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                
                // Enviar el archivo
                readfile($archivePath);
                
                // Limpiar archivos temporales
                array_map('unlink', $generatedFiles);
                unlink($archivePath);
                rmdir($tempDir);
                
                exit;
            } else {
                http_response_code(500);
                die('Error al crear el archivo ZIP');
            }
        } else {
            http_response_code(500);
            die('No se pudo crear el archivo ZIP');
        }
    } else {
        http_response_code(500);
        die('ZipArchive no está disponible en este servidor');
    }
    
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>