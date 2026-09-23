<?php
require_once 'check_session.php';
require_once '../conection.php';
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
$sqlGroups = "SELECT DISTINCT tgs.idGroup
              FROM teacherGroupsSubjects tgs
              JOIN schoolYear sy ON CURDATE() BETWEEN sy.startDate AND sy.endDate
              WHERE tgs.idTeacher = ?";
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
        $sqlAlumnos = "SELECT s.idStudent
                                     FROM students s
                                     JOIN schoolYear sy ON s.idSchoolYear = sy.idSchoolYear
                                     WHERE s.idGroup = ?
                                         AND CURDATE() BETWEEN sy.startDate AND sy.endDate";
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
        // Obtener promedios del ciclo y bimestre vigentes.
        $sqlAvg = "SELECT AVG(a.average) AS prom
                             FROM average a
                             JOIN schoolYear sy ON a.idSchoolYear = sy.idSchoolYear
                             JOIN schoolQuarter sq ON a.idSchoolQuarter = sq.idSchoolQuarter
                             WHERE a.idStudent IN ($in)
                                 AND CURDATE() BETWEEN sy.startDate AND sy.endDate
                                 AND CURDATE() BETWEEN sq.startDate AND sq.endDate";
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
