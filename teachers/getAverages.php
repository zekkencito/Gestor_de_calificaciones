<?php
require_once "../conection.php";
require_once "check_session.php";
header('Content-Type: application/json');

$idSubject = isset($_GET['idSubject']) ? intval($_GET['idSubject']) : 0;
$idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;
$idSchoolQuarter = isset($_GET['idSchoolQuarter']) ? intval($_GET['idSchoolQuarter']) : 0;

if ($idSchoolYear && $idSchoolQuarter && $idSubject) {
    $query = "SELECT idStudent, average FROM average WHERE idSchoolYear = ? AND idSchoolQuarter = ? AND idSubject = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iii", $idSchoolYear, $idSchoolQuarter, $idSubject);
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
