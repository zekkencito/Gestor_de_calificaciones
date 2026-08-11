<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error_log.txt');

require_once "check_session.php";
require_once "../conection.php";

header('Content-Type: application/json');

if (!isset($_GET['studentId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de estudiante no proporcionado'
    ]);
    exit;
}

$studentId = intval($_GET['studentId']);

try {
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
        'pdfPath' => ['pdfPath', 'pdf_path', 'filePath'],
        'createdAt' => ['createdAt', 'created_at', 'dateCreated', 'created_date', 'date_created', 'timestamp']
    ];
    
    $selectColumns = ['cr.idConductReport'];
    $columnAlias = [
        'idConductReport' => 'cr.idConductReport'
    ];
    
    foreach ($columnMapping as $standardName => $possibleNames) {
        foreach ($possibleNames as $colName) {
            if (in_array($colName, $availableColumns)) {
                $selectColumns[] = "cr.$colName as $standardName";
                $columnAlias[$standardName] = "cr.$colName";
                break;
            }
        }
        if (!isset($columnAlias[$standardName])) {
            $selectColumns[] = "NULL as $standardName";
        }
    }
    
    $selectClause = implode(", ", $selectColumns);
    
    // Obtener todos los reportes del estudiante
    $sql = "SELECT $selectClause,
                   uit.names as teacherNames, uit.lastnamePa as teacherLastnamePa, uit.lastnameMa as teacherLastnameMa
            FROM conductReports cr
            LEFT JOIN teachers t ON cr.idTeacher = t.idTeacher
            LEFT JOIN users u ON t.idUser = u.idUser
            LEFT JOIN usersInfo uit ON u.idUserInfo = uit.idUserInfo
            WHERE cr.idStudent = ?
            ORDER BY cr.idConductReport DESC";
    
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error en SQL: $sql");
        error_log("Columnas disponibles: " . implode(', ', $availableColumns));
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        // Guardar fecha original antes de formatear
        $fechaOriginal = $row['fecha'];
        
        // Formatear la fecha
        if ($row['fecha']) {
            $row['fecha'] = date('d/m/Y', strtotime($row['fecha']));
        }
        
        // Formatear fecha de creación (si existe, sino usar la fecha del reporte)
        if (!empty($row['createdAt']) && $row['createdAt'] != '0000-00-00 00:00:00' && $row['createdAt'] !== null) {
            $row['createdAt'] = date('d/m/Y H:i', strtotime($row['createdAt']));
        } elseif (!empty($fechaOriginal) && $fechaOriginal != '0000-00-00') {
            // Si no hay createdAt, usar la fecha del reporte (usando la fecha SIN formatear)
            $row['createdAt'] = date('d/m/Y', strtotime($fechaOriginal));
        } else {
            $row['createdAt'] = 'No disponible';
        }
        
        // Nombre completo del docente
        $row['teacherFullName'] = trim(($row['teacherNames'] ?? '') . ' ' . ($row['teacherLastnamePa'] ?? '') . ' ' . ($row['teacherLastnameMa'] ?? ''));
        
        $reports[] = $row;
    }
    
    if (count($reports) > 0) {
        echo json_encode([
            'success' => true,
            'hasReport' => true,
            'reports' => $reports,
            'count' => count($reports)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'hasReport' => false
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error al obtener reporte: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar el reporte: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

if (isset($conexion)) {
    $conexion->close();
}
?>
