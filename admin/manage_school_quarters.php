<?php
require_once 'check_session.php';
require_once '../conection.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

// Obtener el año actual del servidor
$currentYear = date('Y');

// Listar los 3 trimestres del ciclo escolar actual
if ($action === 'list') {
    // Obtener el ciclo que contiene la fecha actual, incluso si cruza dos años calendario.
    $stmtYear = $conexion->prepare("SELECT idSchoolYear FROM schoolYear 
                                     WHERE CURDATE() BETWEEN startDate AND endDate
                                     ORDER BY startDate DESC
                                     LIMIT 1");
    if (!$stmtYear) {
        echo json_encode(['success' => false, 'error' => 'Error al preparar consulta: ' . $conexion->error]);
        exit;
    }
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
    
    if ($id && $startDate && $endDate && $startDate <= $endDate) {
        $stmtDates = $conexion->prepare("SELECT idSchoolYear FROM schoolQuarter WHERE idSchoolQuarter = ?");
        $stmtDates->bind_param('i', $id);
        $stmtDates->execute();
        $quarter = $stmtDates->get_result()->fetch_assoc();
        $stmtDates->close();

        if (!$quarter) {
            echo json_encode(['success' => false, 'error' => 'El bimestre no existe.']);
            exit;
        }

        $stmtOverlap = $conexion->prepare("SELECT idSchoolQuarter FROM schoolQuarter WHERE idSchoolYear = ? AND idSchoolQuarter <> ? AND startDate IS NOT NULL AND endDate IS NOT NULL AND NOT (? > endDate OR ? < startDate) LIMIT 1");
        $stmtOverlap->bind_param('iiss', $quarter['idSchoolYear'], $id, $startDate, $endDate);
        $stmtOverlap->execute();
        if ($stmtOverlap->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'error' => 'Las fechas se solapan con otro bimestre.']);
            exit;
        }
        $stmtOverlap->close();

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
    echo json_encode(['success' => false, 'error' => 'Las fechas son incompletas o la fecha de inicio es posterior a la fecha final.']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción inválida']);
