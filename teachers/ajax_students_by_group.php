<?php
require_once "check_session.php";
require_once "../conection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No session found']);
    exit;
}

$schoolYear = isset($_GET['schoolYear']) ? intval($_GET['schoolYear']) : 0;
$teacher = isset($_GET['teacher']) ? intval($_GET['teacher']) : 0;

if (!$schoolYear || !$teacher) {
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

try {
    // Consulta para obtener los grupos
    $query = "SELECT DISTINCT g.idGroup, g.grade, g.group_
             FROM teacherGroupsSubjects tgs
             JOIN groups g ON tgs.idGroup = g.idGroup
             JOIN students s ON s.idGroup = g.idGroup
             WHERE tgs.idTeacher = ? 
             AND s.idSchoolYear = ?
             ORDER BY g.grade, g.group_";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $teacher, $schoolYear);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }

    echo json_encode([
        'success' => true,
        'groups' => $groups
    ]);

} catch (Exception $e) {
    error_log("Error en ajax_students_by_group.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener los grupos'
    ]);
}
