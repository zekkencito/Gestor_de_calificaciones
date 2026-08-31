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
        // Obtener el ID del docente desde la sesión
        $user_id = $_SESSION['user_id'];
        $sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
        $stmtTeacher = $conexion->prepare($sqlTeacher);
        $stmtTeacher->bind_param("i", $user_id);
        $stmtTeacher->execute();
        $resTeacher = $stmtTeacher->get_result();
        $rowTeacher = $resTeacher->fetch_assoc();
        $teacher_id = $rowTeacher ? $rowTeacher['idTeacher'] : null;
        $stmtTeacher->close();

        if (!$teacher_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Error de autorización: Profesor no encontrado']);
            exit;
        }

        $stmt = $conexion->prepare("SELECT idEvalCriteria, criteria, porcentage FROM evaluationCriteria 
                                   WHERE idSubject = ? AND idSchoolYear = ? AND idSchoolQuarter = ? 
                                   AND EXISTS (
                                       SELECT 1 FROM teacherGroupsSubjects tgs 
                                       WHERE tgs.idSubject = evaluationCriteria.idSubject 
                                       AND tgs.idTeacher = ?
                                   )
                                   ORDER BY idEvalCriteria");
        $stmt->bind_param("iiii", $idSubject, $idSchoolYear, $idSchoolQuarter, $teacher_id);
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