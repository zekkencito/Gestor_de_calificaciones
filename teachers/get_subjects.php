<?php
require_once "../conection.php";
require_once "check_session.php";
header('Content-Type: application/json');

$idStudent = isset($_GET['idStudent']) ? intval($_GET['idStudent']) : 0;
$idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
$idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;

$subjects = [];

if ($idStudent) {
    // Obtener todas las materias asignadas al grupo del estudiante con su área de aprendizaje
    // (no solo las que tienen calificaciones, para mostrar todas las materias)
    
    // Filtrar solo las materias del teacher logueado
    $user_id = $_SESSION['user_id'];
    $sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
    $stmtTeacher = $conexion->prepare($sqlTeacher);
    $stmtTeacher->bind_param("i", $user_id);
    $stmtTeacher->execute();
    $resTeacher = $stmtTeacher->get_result();
    $rowTeacher = $resTeacher->fetch_assoc();
    $teacher_id = $rowTeacher ? $rowTeacher['idTeacher'] : null;
    $stmtTeacher->close();
    
    $query = "SELECT DISTINCT s.idSubject, s.name, s.idLearningArea, la.name AS learningAreaName
              FROM subjects s
              JOIN learningArea la ON s.idLearningArea = la.idLearningArea
              WHERE s.idSubject IN (
                  SELECT DISTINCT tgs.idSubject 
                  FROM teacherGroupsSubjects tgs 
                  JOIN students st ON tgs.idGroup = st.idGroup 
                  WHERE st.idStudent = ?";
    
    $params = [$idStudent];
    $types = "i";
    
    // Si se especifica año escolar, filtrar por él
    if ($idSchoolYear) {
        $query .= " AND st.idSchoolYear = ?";
        $params[] = $idSchoolYear;
        $types = "ii";
    }
    
    // Agregar filtro de teacher
    if ($teacher_id) {
        $query .= " AND tgs.idTeacher = ?";
        $params[] = $teacher_id;
        $types = ($idSchoolYear) ? "iii" : "ii";
    }
    
    $query .= ") ORDER BY la.name, s.name";

    $stmt = $conexion->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        $stmt->close();
    } else {
        error_log('Error preparando statement en get_subjects.php: ' . $conexion->error);
    }
} else {
    // Lógica original para profesores
    $user_id = $_SESSION['user_id'];
    $sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
    $stmtTeacher = $conexion->prepare($sqlTeacher);
    $stmtTeacher->bind_param("i", $user_id);
    $stmtTeacher->execute();
    $resTeacher = $stmtTeacher->get_result();
    $rowTeacher = $resTeacher->fetch_assoc();
    $teacher_id = $rowTeacher ? $rowTeacher['idTeacher'] : null;

    if ($teacher_id) {
        $query = "SELECT DISTINCT s.idSubject, s.name, s.idLearningArea, la.name AS learningAreaName
                  FROM teacherSubject ts
                  JOIN subjects s ON ts.idSubject = s.idSubject
                  JOIN learningArea la ON s.idLearningArea = la.idLearningArea
                  WHERE ts.idTeacher = ?
                  ORDER BY la.name, s.name";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
}

if (empty($subjects)) {
    echo json_encode(['success' => false, 'message' => 'No se encontraron materias para este estudiante']);
} else {
    echo json_encode(['success' => true, 'subjects' => $subjects]);
}
