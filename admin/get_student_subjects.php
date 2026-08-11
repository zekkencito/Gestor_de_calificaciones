<?php
require_once "../conection.php";
require_once "check_session.php";

header('Content-Type: application/json');

try {
    $idStudent = isset($_GET['idStudent']) ? intval($_GET['idStudent']) : 0;
    $idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
    $idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;

    // LOG para depuración
    file_put_contents(__DIR__.'/debug_get_student_subjects.txt', date('Y-m-d H:i:s')." - idStudent=$idStudent, idSchoolYear=$idSchoolYear, idSchoolQuarter=$idSchoolQuarter\n", FILE_APPEND);

    $subjects = [];

    if ($idStudent) {
        // Obtener todas las materias asignadas al grupo del estudiante con sus áreas de aprendizaje
        $query = "SELECT DISTINCT s.idSubject, s.name, s.idLearningArea, la.name as learningAreaName
                  FROM subjects s
                  JOIN learningArea la ON s.idLearningArea = la.idLearningArea
                  JOIN teacherGroupsSubjects tgs ON s.idSubject = tgs.idSubject
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
        
        $query .= " ORDER BY la.name, s.name";
        
        file_put_contents(__DIR__.'/debug_get_student_subjects.txt', date('Y-m-d H:i:s')." - Query: $query\n", FILE_APPEND);
        file_put_contents(__DIR__.'/debug_get_student_subjects.txt', date('Y-m-d H:i:s')." - Params: ".json_encode($params)."\n", FILE_APPEND);
        
        $stmt = $conexion->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparando la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando la consulta: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        // LOG para depuración
        file_put_contents(__DIR__.'/debug_get_student_subjects.txt', date('Y-m-d H:i:s')." - SQL subjects: ".json_encode($subjects)."\n", FILE_APPEND);
    } else {
        file_put_contents(__DIR__.'/debug_get_student_subjects.txt', date('Y-m-d H:i:s')." - Error: No se proporcionó idStudent\n", FILE_APPEND);
    }

    echo json_encode(['success' => true, 'subjects' => $subjects]);
    
} catch (Exception $e) {
    file_put_contents(__DIR__.'/debug_get_student_subjects.txt', date('Y-m-d H:i:s')." - Exception: ".$e->getMessage()."\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
