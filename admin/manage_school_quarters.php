<?php
require_once '../conection.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

// Obtener el año actual del servidor
$currentYear = date('Y');

// Listar los 3 trimestres del ciclo escolar actual
if ($action === 'list') {
    // Primero obtener el ciclo escolar del año actual
    $stmtYear = $conexion->prepare("SELECT idSchoolYear FROM schoolYear 
                                     WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? 
                                     LIMIT 1");
    if (!$stmtYear) {
        echo json_encode(['success' => false, 'error' => 'Error al preparar consulta: ' . $conexion->error]);
        exit;
    }
    $stmtYear->bind_param('ii', $currentYear, $currentYear);
    $stmtYear->execute();
    $resultYear = $stmtYear->get_result();
    
    if ($rowYear = $resultYear->fetch_assoc()) {
        $idSchoolYear = $rowYear['idSchoolYear'];
        
        // Obtener los 3 trimestres de este ciclo escolar
        $sql = "SELECT idSchoolQuarter, name, description, startDate, endDate
                FROM schoolQuarter 
                WHERE idSchoolYear = ?
                ORDER BY idSchoolQuarter ASC";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $idSchoolYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $quarters = [];
        while ($row = $result->fetch_assoc()) {
            $quarters[] = $row;
        }
        
        echo json_encode(['success' => true, 'quarters' => $quarters, 'currentYear' => $currentYear]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No existe un ciclo escolar para el año actual. Créalo primero.', 'quarters' => []]);
    }
    exit;
}

// Editar fechas de un trimestre
if ($action === 'edit') {
    $id = $_POST['idSchoolQuarter'] ?? '';
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    
    if ($id && $startDate && $endDate) {
        $stmt = $conexion->prepare('UPDATE schoolQuarter SET startDate = ?, endDate = ? WHERE idSchoolQuarter = ?');
        $stmt->bind_param('ssi', $startDate, $endDate, $id);
        $ok = $stmt->execute();
        
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Fechas del trimestre actualizadas correctamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $stmt->error]);
        }
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción inválida']);
