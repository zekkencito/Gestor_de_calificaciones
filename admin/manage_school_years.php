<?php
require_once '../conection.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

// Obtener el año actual del servidor
$currentYear = date('Y');

// Verificar el ciclo escolar actual basado en el año del servidor
if ($action === 'getCurrentYear') {
    // Buscar si existe un ciclo escolar para el año actual
    $stmt = $conexion->prepare("SELECT idSchoolYear, startDate, endDate, YEAR(startDate) as year 
                                 FROM schoolYear 
                                 WHERE YEAR(startDate) = ? OR YEAR(endDate) = ?
                                 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $currentYear, $currentYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true, 
                'exists' => true, 
                'year' => $row,
                'currentServerYear' => $currentYear
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'exists' => false, 
                'currentServerYear' => $currentYear
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al preparar consulta: ' . $conexion->error
        ]);
    }
    exit;
}

if ($action === 'add') {
    $start = $_POST['startDate'] ?? '';
    $end = $_POST['endDate'] ?? '';
    
    if ($start && $end) {
        // Verificar que las fechas sean del año actual
        $startYear = date('Y', strtotime($start));
        $endYear = date('Y', strtotime($end));
        
        // Verificar que no exista ya un ciclo para este año
        $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM schoolYear 
                                     WHERE YEAR(startDate) = ? OR YEAR(endDate) = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Error al preparar consulta: ' . $conexion->error]);
            exit;
        }
        $stmt->bind_param('ii', $currentYear, $currentYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'error' => 'Ya existe un ciclo escolar para el año actual']);
            exit;
        }
        
        // Insertar el nuevo ciclo escolar
        $stmt = $conexion->prepare('INSERT INTO schoolYear (startDate, endDate) VALUES (?, ?)');
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Error al preparar inserción: ' . $conexion->error]);
            exit;
        }
        $stmt->bind_param('ss', $start, $end);
        $ok = $stmt->execute();
        
        if ($ok) {
            $newId = $conexion->insert_id;
            // Crear automáticamente los 3 trimestres
            $trimestres = [
                ['name' => 'Primer Trimestre', 'description' => 'Primer período de evaluación'],
                ['name' => 'Segundo Trimestre', 'description' => 'Segundo período de evaluación'],
                ['name' => 'Tercer Trimestre', 'description' => 'Tercer período de evaluación']
            ];
            
            $stmtTrimestre = $conexion->prepare('INSERT INTO schoolQuarter (name, description, idSchoolYear, startDate, endDate) VALUES (?, ?, ?, ?, ?)');
            if ($stmtTrimestre) {
                foreach ($trimestres as $t) {
                    // Fechas temporales vacías, el usuario las definirá después
                    $emptyDate = null;
                    $stmtTrimestre->bind_param('ssiss', $t['name'], $t['description'], $newId, $emptyDate, $emptyDate);
                    $stmtTrimestre->execute();
                }
                $stmtTrimestre->close();
            }
            
            echo json_encode(['success' => true, 'message' => 'Ciclo escolar creado con 3 trimestres']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear el ciclo escolar']);
        }
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

if ($action === 'edit') {
    $id = $_POST['idSchoolYear'] ?? '';
    $start = $_POST['startDate'] ?? '';
    $end = $_POST['endDate'] ?? '';
    
    if ($id && $start && $end) {
        $stmt = $conexion->prepare('UPDATE schoolYear SET startDate = ?, endDate = ? WHERE idSchoolYear = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Error al preparar actualización: ' . $conexion->error]);
            exit;
        }
        $stmt->bind_param('ssi', $start, $end, $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok, 'message' => 'Ciclo escolar actualizado correctamente']);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción inválida']);
