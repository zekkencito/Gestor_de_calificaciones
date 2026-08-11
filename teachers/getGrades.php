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

    // Obtener las calificaciones
    $query = "SELECT gs.grade, gs.idStudent, gs.idEvalCriteria
              FROM gradesSubject gs
              WHERE gs.idSubject = ? 
              AND gs.idSchoolYear = ? 
              AND gs.idSchoolQuarter = ?";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iii", $idSubject, $idSchoolYear, $idSchoolQuarter);
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