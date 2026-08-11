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

// Obtener alumnos asignados a materias del maestro
$sql = "SELECT DISTINCT s.idStudent FROM students s
JOIN groups g ON s.idGroup = g.idGroup
JOIN teacherGroupsSubjects tgs ON tgs.idGroup = g.idGroup AND tgs.idTeacher = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $idTeacher);
$stmt->execute();
$res = $stmt->get_result();
$alumnos = [];
while($row = $res->fetch_assoc()) {
    $alumnos[] = $row['idStudent'];
}
if (empty($alumnos)) {
    echo json_encode(['success' => true, 'total' => 0, 'aprobados' => 0, 'porcentaje' => 0]);
    exit;
}
$in = implode(',', $alumnos);
$sqlAvg = "SELECT COUNT(*) as total, SUM(CASE WHEN average >= 70 OR (average < 70 AND average >= 7) THEN 1 ELSE 0 END) as aprobados FROM average WHERE idStudent IN ($in)";
$resAvg = $conexion->query($sqlAvg);
$rowAvg = $resAvg->fetch_assoc();
$total = intval($rowAvg['total']);
$aprobados = intval($rowAvg['aprobados']);
$porcentaje = ($total > 0) ? round($aprobados * 100 / $total, 2) : 0;
echo json_encode(['success' => true, 'total' => $total, 'aprobados' => $aprobados, 'porcentaje' => $porcentaje]);
