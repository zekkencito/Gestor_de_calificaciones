<?php
require_once "../conection.php";

// Establecer encabezados para permitir acceso y evitar problemas de caché
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$idSchoolYear = isset($_GET['idSchoolYear']) ? intval($_GET['idSchoolYear']) : 0;

$quarters = [];
$debugInfo = "idSchoolYear recibido: " . $idSchoolYear . "\n";

// Primero veamos si hay trimestres en la tabla
$checkSql = "SELECT COUNT(*) as total FROM schoolQuarter";
$checkResult = $conexion->query($checkSql);
if ($checkResult) {
    $row = $checkResult->fetch_assoc();
    $debugInfo .= "Total de trimestres en la base: " . $row['total'] . "\n";
}

// Si no se proporciona un año escolar específico o si es 0, devolver todos los trimestres
if ($idSchoolYear > 0) {
    $sql = "SELECT idSchoolQuarter, name FROM schoolQuarter WHERE idSchoolYear = ? OR idSchoolYear IS NULL ORDER BY name";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idSchoolYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $debugInfo .= "SQL con filtro: " . $sql . " (parámetro: " . $idSchoolYear . ")\n";
} else {
    $sql = "SELECT idSchoolQuarter, name FROM schoolQuarter ORDER BY name";
    $result = $conexion->query($sql);
    $debugInfo .= "SQL sin filtro: " . $sql . "\n";
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $quarters[] = $row;
    }
    $debugInfo .= "Trimestres encontrados: " . count($quarters) . "\n";
    if (isset($stmt)) {
        $stmt->close();
    }
} else {
    $debugInfo .= "Error en la consulta: " . $conexion->error . "\n";
}

// Si no se encontraron trimestres, devolver algunos predeterminados para pruebas
if (count($quarters) == 0) {
    $debugInfo .= "Agregando trimestres predeterminados para pruebas\n";
    $quarters = [
        ['idSchoolQuarter' => '1', 'name' => 'Primer Trimestre'],
        ['idSchoolQuarter' => '2', 'name' => 'Segundo Trimestre'],
        ['idSchoolQuarter' => '3', 'name' => 'Tercer Trimestre']
    ];
}

// Devolver una respuesta estructurada
echo json_encode([
    'success' => true, 
    'quarters' => $quarters,
    'debug' => $debugInfo,
    'timestamp' => time()
]);
?>
