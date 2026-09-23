<?php
require_once 'check_session.php';
require_once '../conection.php';
header('Content-Type: application/json');

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

$sql = "SELECT CONCAT(g.grade, '° ', g.group_) AS group_name,
               ROUND(AVG(student_averages.student_average), 2) AS group_average
        FROM groups g
        JOIN (
            SELECT s.idStudent, s.idGroup, AVG(a.average) AS student_average
            FROM students s
            JOIN average a ON a.idStudent = s.idStudent
            WHERE s.idSchoolYear = ?
              AND a.idSchoolYear = ?
              AND a.idSchoolQuarter = ?
              AND a.average IS NOT NULL
            GROUP BY s.idStudent, s.idGroup
        ) AS student_averages ON student_averages.idGroup = g.idGroup
        GROUP BY g.idGroup, g.grade, g.group_
        ORDER BY g.grade, g.group_";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('iii', $period['idSchoolYear'], $period['idSchoolYear'], $period['idSchoolQuarter']);
$stmt->execute();
$result = $stmt->get_result();
$labels = [];
$values = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['group_name'];
    $values[] = (float) $row['group_average'];
}
$stmt->close();

echo json_encode(['success' => true, 'labels' => $labels, 'values' => $values]);
