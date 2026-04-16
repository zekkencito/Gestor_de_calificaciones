<?php
// Mostrar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../conection.php";
require_once "check_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
        exit;
    }

    $idSubject = $data['idSubject'];
    $idSchoolYear = $data['idSchoolYear'];
    $idSchoolQuarter = $data['idSchoolQuarter'];
    $newCriterias = $data['criterias'];

    // --- VALIDAR CRITERIOS DUPLICADOS EN EL SERVIDOR ---
    $nombres = [];
    foreach ($newCriterias as $criteria) {
        $nombreLower = strtolower(trim($criteria['name']));
        if (in_array($nombreLower, $nombres)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Ya existe un criterio llamado "' . $criteria['name'] . '". Por favor, usa nombres únicos para cada criterio.'
            ]);
            exit;
        }
        $nombres[] = $nombreLower;
    }

    try {
        // Obtener criterios existentes para este subject, year y quarter
        $stmt = $conexion->prepare("SELECT idEvalCriteria FROM evaluationCriteria 
                                   WHERE idSubject = ? AND idSchoolYear = ? AND idSchoolQuarter = ?");
        $stmt->bind_param("iii", $idSubject, $idSchoolYear, $idSchoolQuarter);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingIds = [];
        while ($row = $result->fetch_assoc()) {
            $existingIds[] = $row['idEvalCriteria'];
        }

        // Array de IDs que se están guardando actualmente (solo los que NO son nuevos)
        $incomingIds = [];
        $newCriteriosCount = 0;
        foreach ($newCriterias as $criteria) {
            if (!empty($criteria['idEvalCriteria'])) {
                $incomingIds[] = $criteria['idEvalCriteria'];
            } else {
                // Es un criterio nuevo, no agregar a incomingIds
                $newCriteriosCount++;
            }
        }
        
        error_log("DEBUG: Criterios nuevos: $newCriteriosCount, Criterios existentes a mantener: " . json_encode($incomingIds));

        // Eliminar criterios que ya no se mencionan (no existen en el array incoming)
        foreach ($existingIds as $idEvalCriteria) {
            if (!in_array($idEvalCriteria, $incomingIds)) {
                // Este criterio fue eliminado, borrar sus calificaciones primero
                $stmtDelete = $conexion->prepare("DELETE FROM gradesSubject WHERE idEvalCriteria = ?");
                $stmtDelete->bind_param("i", $idEvalCriteria);
                $stmtDelete->execute();
                
                // Luego borrar el criterio
                $stmtDeleteCrit = $conexion->prepare("DELETE FROM evaluationCriteria WHERE idEvalCriteria = ?");
                $stmtDeleteCrit->bind_param("i", $idEvalCriteria);
                $stmtDeleteCrit->execute();
            }
        }

        // Actualizar o insertar criterios
        foreach ($newCriterias as $criteria) {
            $criteriaName = $criteria['name'];
            $percentage = $criteria['percentage'];
            $idEvalCriteria = $criteria['idEvalCriteria'];

            if (!empty($idEvalCriteria)) {
                // Actualizar criterio existente
                $stmtUpdate = $conexion->prepare("UPDATE evaluationCriteria SET criteria = ?, porcentage = ? WHERE idEvalCriteria = ?");
                $stmtUpdate->bind_param("sii", $criteriaName, $percentage, $idEvalCriteria);
                $stmtUpdate->execute();
            } else {
                // Insertar nuevo criterio
                $stmtInsert = $conexion->prepare("INSERT INTO evaluationCriteria (criteria, porcentage, idSubject, idSchoolYear, idSchoolQuarter) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->bind_param("siiii", $criteriaName, $percentage, $idSubject, $idSchoolYear, $idSchoolQuarter);
                $stmtInsert->execute();
            }
        }

        // Obtener los criterios finales con sus IDs
        $stmt = $conexion->prepare("SELECT idEvalCriteria, criteria, porcentage FROM evaluationCriteria WHERE idSubject = ? AND idSchoolYear = ? AND idSchoolQuarter = ? ORDER BY idEvalCriteria");
        $stmt->bind_param("iii", $idSubject, $idSchoolYear, $idSchoolQuarter);
        $stmt->execute();
        $result = $stmt->get_result();
        $criterias = [];
        while ($row = $result->fetch_assoc()) {
            $criterias[] = [
                'idEvalCriteria' => $row['idEvalCriteria'],
                'name' => $row['criteria'],
                'percentage' => $row['porcentage']
            ];
        }
        echo json_encode(['success' => true, 'message' => 'Criterios guardados correctamente', 'data' => $criterias]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
    }
}
?> 