<?php
require_once 'check_api_session.php';
require_once '../conection.php';
header('Content-Type: application/json');

$currentYear = date('Y');
$events = [];

// 1. Find current school year
$stmtYear = $conexion->prepare("SELECT idSchoolYear FROM schoolYear WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? LIMIT 1");
$stmtYear->bind_param('ii', $currentYear, $currentYear);
$stmtYear->execute();
$rowYear = $stmtYear->get_result()->fetch_assoc();
$stmtYear->close();

if ($rowYear) {
    $idSY = (int)$rowYear['idSchoolYear'];

    // 2. Get quarters with dates
    $stmtQ = $conexion->prepare("SELECT name, startDate, endDate FROM schoolQuarter WHERE idSchoolYear = ? AND startDate IS NOT NULL ORDER BY startDate");
    $stmtQ->bind_param('i', $idSY);
    $stmtQ->execute();
    $resQ = $stmtQ->get_result();
    while ($q = $resQ->fetch_assoc()) {
        $name = $q['name'];
        if ($q['startDate']) {
            $events[] = [
                'title' => "Inicio $name",
                'start' => $q['startDate'],
                'allDay' => true,
                'color' => '#1a7f4b'
            ];
        }
        if ($q['endDate']) {
            $events[] = [
                'title' => "Fin $name",
                'start' => $q['endDate'],
                'allDay' => true,
                'color' => '#92600a'
            ];
        }
    }
    $stmtQ->close();
}

// 3. Deadline
$resDL = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $resDL->fetch_assoc()) {
    $dl = $row['limitDate'];
    if ($dl) {
        $events[] = [
            'title' => 'Cierre de Calificaciones',
            'start' => $dl,
            'allDay' => true,
            'color' => '#b91c1c'
        ];
    }
}

echo json_encode(['events' => $events]);
