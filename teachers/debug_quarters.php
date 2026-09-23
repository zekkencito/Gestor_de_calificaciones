<?php
require_once dirname(__DIR__) . '/conection.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== FECHA ACTUAL ===\n";
echo "PHP date(): " . date('Y-m-d') . "\n";
$r = $conexion->query("SELECT NOW() as now_db, CURDATE() as curdate_db");
$row = $r->fetch_assoc();
echo "MySQL NOW(): " . $row['now_db'] . "\n";
echo "MySQL CURDATE(): " . $row['curdate_db'] . "\n\n";

echo "=== CICLOS ESCOLARES ===\n";
$r = $conexion->query("SELECT idSchoolYear, startDate, endDate FROM schoolYear ORDER BY startDate DESC");
while ($row2 = $r->fetch_assoc()) {
    echo "ID: {$row2['idSchoolYear']} | start: {$row2['startDate']} | end: {$row2['endDate']}\n";
}
echo "\n";

$year = date('Y');
$stmt = $conexion->prepare("SELECT idSchoolYear, startDate, endDate FROM schoolYear WHERE YEAR(startDate) = ? OR YEAR(endDate) = ? ORDER BY startDate DESC LIMIT 1");
$stmt->bind_param('ii', $year, $year);
$stmt->execute();
$sy = $stmt->get_result()->fetch_assoc();
echo "=== CICLO DETECTADO ===\n";
if ($sy) {
    echo "ID: {$sy['idSchoolYear']} | start: {$sy['startDate']} | end: {$sy['endDate']}\n\n";
    echo "=== BIMESTRES DEL CICLO {$sy['idSchoolYear']} ===\n";
    $stmt2 = $conexion->prepare("SELECT idSchoolQuarter, name, startDate, endDate FROM schoolQuarter WHERE idSchoolYear = ? ORDER BY idSchoolQuarter");
    $stmt2->bind_param('i', $sy['idSchoolYear']);
    $stmt2->execute();
    $res = $stmt2->get_result();
    $curdate = $row['curdate_db'];
    echo "Comparando contra fecha MySQL: $curdate\n";
    while ($q = $res->fetch_assoc()) {
        $vigente = ($q['startDate'] && $q['endDate'] && $curdate >= $q['startDate'] && $curdate <= $q['endDate']) ? " <--- VIGENTE" : "";
        echo "ID: {$q['idSchoolQuarter']} | {$q['name']} | start: {$q['startDate']} | end: {$q['endDate']}{$vigente}\n";
    }
} else {
    echo "NO SE ENCONTRO CICLO ESCOLAR\n";
}
