<?php
require_once "../conection.php";
require_once "check_session.php"; // Verificación de sesión de admin
header('Content-Type: application/json');

try {
    $idSubject = isset($_GET['idSubject']) ? intval($_GET['idSubject']) : 0;
    $idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
    $idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;
    $idStudent = isset($_GET['idStudent']) ? intval($_GET['idStudent']) : 0;

    if (!$idSchoolYear || !$idSchoolQuarter || !$idStudent || !$idSubject) {
        echo json_encode([
            'success' => false,
            'error' => 'Faltan parámetros requeridos: idStudent, idSubject, idSchoolYear, idSchoolQuarter'
        ]);
        exit;
    }
    
    // Consulta simplificada: obtener el promedio específico de la tabla average
    $sql = "SELECT 
                s.idStudent, 
                ui.lastnamePa, 
                ui.lastnameMa, 
                ui.names, 
                g.grade, 
                g.group_, 
                COALESCE(a.average, 0) as average,
                sub.name as subject_name,
                sub.idSubject
            FROM students s
            JOIN usersInfo ui ON s.idUserInfo = ui.idUserInfo
            JOIN groups g ON s.idGroup = g.idGroup
            JOIN subjects sub ON sub.idSubject = ?
            LEFT JOIN average a ON a.idStudent = s.idStudent 
                AND a.idSubject = ?
                AND a.idSchoolYear = ?
                AND a.idSchoolQuarter = ?
            WHERE s.idStudent = ?
            AND s.idSchoolYear = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiiiii", $idSubject, $idSubject, $idSchoolYear, $idSchoolQuarter, $idStudent, $idSchoolYear);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students,  // Cambiar 'data' por 'students'
        'count' => count($students)
    ]);
    
} catch (Exception $e) {
    error_log("Error en getAveragesBySubject.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
