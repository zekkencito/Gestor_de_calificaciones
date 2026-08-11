<?php
require_once '../conection.php';
session_start();
$user_id = $_SESSION['user_id'];

// Obtener idTeacher
$sqlTeacher = "SELECT idTeacher FROM teachers WHERE idUser = ?";
$stmtT = $conexion->prepare($sqlTeacher);
$stmtT->bind_param('i', $user_id);
$stmtT->execute();
$resT = $stmtT->get_result();
$rowT = $resT->fetch_assoc();
$idTeacher = $rowT['idTeacher'];

// Obtener grupos asignados al maestro
$sqlGroups = "SELECT DISTINCT idGroup FROM teacherGroupsSubjects WHERE idTeacher = ?";
$stmtG = $conexion->prepare($sqlGroups);
$stmtG->bind_param('i', $idTeacher);
$stmtG->execute();
$resG = $stmtG->get_result();
$groups = [];
while($rowG = $resG->fetch_assoc()) {
    $groups[] = $rowG['idGroup'];
}

if (empty($groups)) {
    echo json_encode(['success' => true, 'total' => 0, 'aprobados' => 0, 'porcentaje' => 0]);
    exit;
}

// Por cada grupo, obtener el promedio general (de todos los alumnos y materias de ese grupo)
$total = 0;
$aprobados = 0;
foreach ($groups as $idGroup) {
    // Obtener alumnos del grupo
    $sqlAlumnos = "SELECT idStudent FROM students WHERE idGroup = ?";
    $stmtA = $conexion->prepare($sqlAlumnos);
    $stmtA->bind_param('i', $idGroup);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    $alumnos = [];
    while($rowA = $resA->fetch_assoc()) {
        $alumnos[] = $rowA['idStudent'];
    }
    if (empty($alumnos)) continue;
    $in = implode(',', $alumnos);
    // Obtener promedios finales de cada alumno (último año y trimestre)
    $sqlAvg = "SELECT AVG(average) as prom FROM average WHERE idStudent IN ($in)";
    $resAvg = $conexion->query($sqlAvg);
    $rowAvg = $resAvg->fetch_assoc();
    $prom = floatval($rowAvg['prom']);
    $total++;
    // Considera aprobado si el promedio es >= 7 o >= 70
    if ($prom >= 70 || ($prom < 70 && $prom >= 7)) {
        $aprobados++;
    }
}
$porcentaje = ($total > 0) ? round($aprobados * 100 / $total, 2) : 0;
echo json_encode(['success' => true, 'total' => $total, 'aprobados' => $aprobados, 'porcentaje' => $porcentaje]);
