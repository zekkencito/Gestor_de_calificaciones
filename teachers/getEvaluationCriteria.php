<?php
require_once "../conection.php";
require_once "check_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $idSubject = isset($_GET['idSubject']) ? intval($_GET['idSubject']) : 0;
    $idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
    $idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;
    
    if (!$idSubject || !$idSchoolYear || !$idSchoolQuarter) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos. Por favor, verifique la información.']);
        exit;
    }

    try {
        $stmt = $conexion->prepare("SELECT idEvalCriteria, criteria, porcentage FROM evaluationCriteria 
                                   WHERE idSubject = ? AND idSchoolYear = ? AND idSchoolQuarter = ? 
                                   ORDER BY idEvalCriteria");
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

        echo json_encode(['success' => true, 'data' => $criterias]);
    } catch (Exception $e) {
        error_log('Error en getEvaluationCriteria: ' . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al cargar los criterios de evaluación.']);
    }
}
?> 