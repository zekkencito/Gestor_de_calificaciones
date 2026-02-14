<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error_log.txt');

require_once "check_session.php";
require_once "../conection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Validar que se reciban todos los datos necesarios
if (!isset($_POST['studentId']) || !isset($_POST['fecha']) || !isset($_POST['tipo']) || !isset($_POST['descripcion'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$studentId = intval($_POST['studentId']);
$fecha = $_POST['fecha'];
$tipo = trim($_POST['tipo']);
$descripcion = trim($_POST['descripcion']);
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';

// Obtener el ID del docente desde la sesión
$idUser = $_SESSION['user_id'];
$sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
$stmtTeacher = $conexion->prepare($sqlTeacher);
$stmtTeacher->bind_param("i", $idUser);
$stmtTeacher->execute();
$resultTeacher = $stmtTeacher->get_result();
$rowTeacher = $resultTeacher->fetch_assoc();

if (!$rowTeacher) {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró el docente'
    ]);
    exit;
}

$idTeacher = $rowTeacher['idTeacher'];
$stmtTeacher->close();

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
        'observaciones' => ['feedback', 'observaciones', 'observations', 'comments']
    ];
    
    $columnsToInsert = ['idStudent', 'idTeacher'];
    $valuesToInsert = [$studentId, $idTeacher];
    $types = "ii";
    
    foreach ($columnMapping['fecha'] as $colName) {
        if (in_array($colName, $availableColumns)) {
            $columnsToInsert[] = $colName;
            $valuesToInsert[] = $fecha;
            $types .= "s";
            break;
        }
    }
    
    foreach ($columnMapping['tipo'] as $colName) {
        if (in_array($colName, $availableColumns)) {
            $columnsToInsert[] = $colName;
            $valuesToInsert[] = $tipo;
            $types .= "s";
            break;
        }
    }
    
    foreach ($columnMapping['descripcion'] as $colName) {
        if (in_array($colName, $availableColumns)) {
            $columnsToInsert[] = $colName;
            $valuesToInsert[] = $descripcion;
            $types .= "s";
            break;
        }
    }
    
    foreach ($columnMapping['observaciones'] as $colName) {
        if (in_array($colName, $availableColumns)) {
            $columnsToInsert[] = $colName;
            $valuesToInsert[] = $observaciones;
            $types .= "s";
            break;
        }
    }
    
    $hasPdfPath = in_array('pdfPath', $availableColumns);
    $hasCreatedAt = in_array('createdAt', $availableColumns);
    $hasCreatedDate = in_array('created_date', $availableColumns);
    $hasDateCreated = in_array('date_created', $availableColumns);
    
    if ($hasCreatedAt) {
        $columnsToInsert[] = 'createdAt';
    } elseif ($hasCreatedDate) {
        $columnsToInsert[] = 'created_date';
    } elseif ($hasDateCreated) {
        $columnsToInsert[] = 'date_created';
    }
    
    $useTimestamp = ($hasCreatedAt || $hasCreatedDate || $hasDateCreated);
    
    // Construir query dinámicamente
    $columnsList = implode(', ', $columnsToInsert);
    $placeholders = implode(', ', array_fill(0, count($valuesToInsert), '?'));
    
    // Agregar NOW() si hay columna de timestamp
    if ($useTimestamp) {
        $placeholders .= ', NOW()';
    }
    
    $sql = "INSERT INTO conductReports ($columnsList) VALUES ($placeholders)";
    
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error en SQL: $sql");
        error_log("Columnas disponibles: " . implode(', ', $availableColumns));
        error_log("Columnas a insertar: " . implode(', ', $columnsToInsert));
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }
    
    // Bind dinámico
    $stmt->bind_param($types, ...$valuesToInsert);
    
    if ($stmt->execute()) {
        $reportId = $stmt->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Reporte guardado exitosamente',
            'reportId' => $reportId
        ]);
    } else {
        throw new Exception("Error al ejecutar la consulta");
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error al guardar reporte: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el reporte: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

if (isset($conexion)) {
    $conexion->close();
}
?>
