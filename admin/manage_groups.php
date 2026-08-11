<?php
require_once "../conection.php";
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
if ($action == 'list') {
    $res = $conexion->query("SELECT * FROM groups ORDER BY grade, group_");
    $groups = [];
    while ($row = $res->fetch_assoc()) $groups[] = $row;
    echo json_encode(['success'=>true, 'groups'=>$groups]);
} elseif ($action == 'add') {
    $group_ = $conexion->real_escape_string($_POST['group_'] ?? '');
    $grade = $conexion->real_escape_string($_POST['grade'] ?? '');
    if (!$group_ || !$grade) {
        echo json_encode(['success'=>false, 'error'=>'Datos incompletos']);
        exit;
    }
    $conexion->query("INSERT INTO groups (group_, grade) VALUES ('$group_', '$grade')");
    echo json_encode(['success'=>true]);
} elseif ($action == 'delete') {
    $id = intval($_POST['idGroup'] ?? 0);
    if ($id) $conexion->query("DELETE FROM groups WHERE idGroup=$id");
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>'Acción no válida']);
}
