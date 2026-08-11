<?php
require_once "../conection.php";
header('Content-Type: application/json');

if (isset($_POST['quitarLimite']) && $_POST['quitarLimite'] == '1') {
    if ($conexion->query("UPDATE limitDate SET limitDate = NULL WHERE idLimitDate = 1")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la fecha.']);
    }
    exit;
}
if (isset($_POST['fechaLimite']) && $_POST['fechaLimite'] !== '') {
    $fecha = $_POST['fechaLimite'];
    $stmt = $conexion->prepare("UPDATE limitDate SET limitDate = ? WHERE idLimitDate = 1");
    $stmt->bind_param("s", $fecha);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la fecha.']);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Petición inválida.']);
exit;
?>
