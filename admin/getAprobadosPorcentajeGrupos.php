<?php
require_once '../conection.php';
// Para el dashboard admin: porcentaje de grupos aprobados (promedio >= 7 o >= 70)
$sqlGroups = "SELECT idGroup FROM groups";
$resG = $conexion->query($sqlGroups);
$total = 0;
$aprobados = 0;
while($rowG = $resG->fetch_assoc()) {
    $idGroup = $rowG['idGroup'];
    $sqlAlumnos = "SELECT idStudent FROM students WHERE idGroup = $idGroup";
    $resA = $conexion->query($sqlAlumnos);
    $alumnos = [];
    while($rowA = $resA->fetch_assoc()) {
        $alumnos[] = $rowA['idStudent'];
    }
    if (empty($alumnos)) continue;
    $in = implode(',', $alumnos);
    $sqlAvg = "SELECT AVG(average) as prom FROM average WHERE idStudent IN ($in)";
    $resAvg = $conexion->query($sqlAvg);
    $rowAvg = $resAvg->fetch_assoc();
    $prom = floatval($rowAvg['prom']);
    $total++;
    if ($prom >= 70 || ($prom < 70 && $prom >= 7)) {
        $aprobados++;
    }
}
$porcentaje = ($total > 0) ? round($aprobados * 100 / $total, 2) : 0;
echo json_encode(['success' => true, 'total' => $total, 'aprobados' => $aprobados, 'porcentaje' => $porcentaje]);
