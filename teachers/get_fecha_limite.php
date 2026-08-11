<?php
header('Content-Type: application/json');
require_once "../conection.php";
$res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo json_encode(['success' => true, 'fechaLimite' => $row['limitDate']]);
} else {
    echo json_encode(['success' => false, 'fechaLimite' => null]);
}
?>
