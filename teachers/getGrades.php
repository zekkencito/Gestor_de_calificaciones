<?php
require_once "../conection.php";
require_once "check_session.php";

header('Content-Type: application/json');

try {
    if (!isset($_GET['idSubject'], $_GET['idSchoolYear'], $_GET['idSchoolQuarter'])) {
        throw new Exception('Parámetros incompletos');
    }

    $idSubject = intval($_GET['idSubject']);
    $idSchoolYear = intval($_GET['idSchoolYear']);
    $idSchoolQuarter = intval($_GET['idSchoolQuarter']);

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

    // Obtener las calificaciones, filtrando solo para estudiantes asignados a este profesor en esta materia
    $query = "SELECT gs.grade, gs.idStudent, gs.idEvalCriteria
              FROM gradesSubject gs
              WHERE gs.idSubject = ? 
              AND gs.idSchoolYear = ? 
              AND gs.idSchoolQuarter = ?
              AND EXISTS (
                  SELECT 1 FROM teacherGroupsSubjects tgs 
                  JOIN students s ON s.idGroup = tgs.idGroup
                  WHERE tgs.idSubject = gs.idSubject 
                  AND tgs.idTeacher = ?
                  AND s.idStudent = gs.idStudent
              )";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iiii", $idSubject, $idSchoolYear, $idSchoolQuarter, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($grades[$row['idStudent']])) {
            $grades[$row['idStudent']] = [];
        }
        $grades[$row['idStudent']][$row['idEvalCriteria']] = $row['grade'];
    }

    echo json_encode(['success' => true, 'data' => $grades]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 