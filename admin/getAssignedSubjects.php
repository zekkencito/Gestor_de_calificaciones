<?php
require_once 'check_session.php';
require_once '../conection.php';
header('Content-Type: application/json');

$idTG = isset($_GET['idTG']) ? (int)$_GET['idTG'] : 0;

if ($idTG <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT sub.idSubject 
        FROM teacherGroupsSubjects tgs
        INNER JOIN subjects sub ON tgs.idSubject = sub.idSubject
        INNER JOIN teacherGroup tg ON tg.idTeacher = tgs.idTeacher AND tg.idGroup = tgs.idGroup
        WHERE tg.idTeacherGroup = $idTG";
$result = $conexion->query($sql);

$ids = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['idSubject'];
    }
}
echo json_encode($ids);
