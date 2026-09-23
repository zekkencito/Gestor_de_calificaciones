<?php
require_once 'check_session.php';
require_once '../conection.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$stmtTeacher = $conexion->prepare("SELECT idTeacher FROM teachers WHERE idUser = ?");
$stmtTeacher->bind_param('i', $userId);
$stmtTeacher->execute();
$teacher = $stmtTeacher->get_result()->fetch_assoc();
$stmtTeacher->close();

if (!$teacher) {
    echo json_encode(['success' => false, 'labels' => [], 'values' => []]);
    exit;
}

$currentYear = date('Y');
$sqlPeriod = "SELECT sy.idSchoolYear, sq.idSchoolQuarter
              FROM schoolYear sy
              LEFT JOIN schoolQuarter sq ON sq.idSchoolYear = sy.idSchoolYear
              WHERE (YEAR(sy.startDate) = ? OR YEAR(sy.endDate) = ?)
              AND (CURDATE() BETWEEN sq.startDate AND sq.endDate
                   OR sq.idSchoolQuarter IS NULL)
              ORDER BY sy.startDate DESC, sq.idSchoolQuarter ASC
              LIMIT 1";
$stmtPeriod = $conexion->prepare($sqlPeriod);
$stmtPeriod->bind_param('ii', $currentYear, $currentYear);
$stmtPeriod->execute();
$period = $stmtPeriod->get_result()->fetch_assoc();
$stmtPeriod->close();

if (!$period || !$period['idSchoolQuarter']) {
    echo json_encode(['success' => true, 'labels' => [], 'values' => []]);
    exit;
}

$sql = "SELECT sub.name AS subject_name, ROUND(AVG(a.average), 2) AS subject_average
        FROM teacherGroupsSubjects tgs
        JOIN subjects sub ON sub.idSubject = tgs.idSubject
        JOIN students s ON s.idGroup = tgs.idGroup
        JOIN average a ON a.idStudent = s.idStudent
                       AND a.idSubject = tgs.idSubject
        WHERE tgs.idTeacher = ?
          AND s.idSchoolYear = ?
          AND a.idSchoolYear = ?
          AND a.idSchoolQuarter = ?
          AND a.average IS NOT NULL
        GROUP BY sub.idSubject, sub.name
        ORDER BY sub.name";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('iiii', $teacher['idTeacher'], $period['idSchoolYear'], $period['idSchoolYear'], $period['idSchoolQuarter']);
$stmt->execute();
$result = $stmt->get_result();
$labels = [];
$values = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['subject_name'];
    $values[] = (float) $row['subject_average'];
}
$stmt->close();

echo json_encode(['success' => true, 'labels' => $labels, 'values' => $values]);
