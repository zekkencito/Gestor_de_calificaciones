<?php
require_once 'check_session.php';
require_once "../conection.php";
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
if ($action == 'list') {
    $res = $conexion->query("SELECT * FROM groups ORDER BY grade, group_");
    $groups = [];
    while ($row = $res->fetch_assoc()) $groups[] = $row;
    echo json_encode(['success'=>true, 'groups'=>$groups]);
} elseif ($action == 'add') {
    $group_ = trim($_POST['group_'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    if (!$group_ || !$grade) {
        echo json_encode(['success'=>false, 'error'=>'Datos incompletos']);
        exit;
    }
    $stmt = $conexion->prepare("INSERT INTO groups (group_, grade) VALUES (?, ?)");
    $stmt->bind_param("ss", $group_, $grade);
    $stmt->execute();
    echo json_encode(['success'=>true]);
} elseif ($action == 'delete') {
    $id = intval($_POST['idGroup'] ?? 0);
    if ($id) {
        $stmt = $conexion->prepare("DELETE FROM groups WHERE idGroup = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>'Acción no válida']);
}
