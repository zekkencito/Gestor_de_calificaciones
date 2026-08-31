<?php
require_once "../conection.php";
require_once "check_session.php";
header('Content-Type: application/json');

$idSubject = isset($_GET['idSubject']) ? intval($_GET['idSubject']) : 0;
$idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
$idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;

if ($idSchoolYear && $idSchoolQuarter && $idSubject) {
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

    $query = "SELECT idStudent, average FROM average WHERE idSchoolYear = ? AND idSchoolQuarter = ? AND idSubject = ?
              AND EXISTS (
                  SELECT 1 FROM teacherGroupsSubjects tgs 
                  JOIN students s ON s.idGroup = tgs.idGroup
                  WHERE tgs.idSubject = average.idSubject 
                  AND tgs.idTeacher = ?
                  AND s.idStudent = average.idStudent
              )";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iiii", $idSchoolYear, $idSchoolQuarter, $idSubject, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $averages = [];
    while ($row = $result->fetch_assoc()) {
        $averages[$row['idStudent']] = $row['average'];
    }
    echo json_encode(['success' => true, 'data' => $averages]);
} else {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
}
