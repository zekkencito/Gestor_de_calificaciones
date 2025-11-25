<?php
// Script para recalcular promedios existentes
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "check_session.php";
require_once "../conection.php";

echo "<h2>Recalcular Promedio - Estudiante 24, Ciencias Naturales</h2>";

$idStudent = 24;
$idSubject = 18; // Ciencias Naturales
$idSchoolYear = 1;
$idSchoolQuarter = 1;

// 1. Obtener las calificaciones actuales
$sql = "SELECT gs.grade, ec.percentage 
        FROM gradesSubject gs
        JOIN evaluation_criteria ec ON gs.idEvalCriteria = ec.idEvalCriteria
        WHERE gs.idStudent = ? AND gs.idSubject = ? AND gs.idSchoolYear = ? AND gs.idSchoolQuarter = ? 
        AND gs.status = 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iiii", $idStudent, $idSubject, $idSchoolYear, $idSchoolQuarter);
$stmt->execute();
$result = $stmt->get_result();

$sum = 0;
$sumPercent = 0;
$grades = [];

echo "<h3>Calificaciones encontradas:</h3>";
echo "<table border='1'>";
echo "<tr><th>Calificación</th><th>Porcentaje</th><th>Contribución</th></tr>";

while ($row = $result->fetch_assoc()) {
    $grade = floatval($row['grade']);
    $percentage = floatval($row['percentage']);
    $contribution = $grade * ($percentage / 100);
    
    $grades[] = $row;
    $sumPercent += $percentage;
    if (is_numeric($row['grade']) && $row['grade'] !== '') {
        $sum += $contribution;
    }
    
    echo "<tr><td>{$row['grade']}</td><td>{$percentage}%</td><td>" . number_format($contribution, 4) . "</td></tr>";
}
echo "</table>";

// 2. Calcular nuevo promedio con redondeo hacia arriba
echo "<h3>Cálculo del promedio:</h3>";
echo "<p>Suma ponderada: $sum</p>";
echo "<p>Suma de porcentajes: $sumPercent%</p>";

if ($sumPercent > 0) {
    if ($sumPercent === 100) {
        $average = ceil($sum * 10) / 10; // Redondear hacia arriba a 1 decimal
        echo "<p>Fórmula: ceil($sum * 10) / 10 = <strong>$average</strong></p>";
    } else {
        $normalizedSum = $sum / ($sumPercent / 100);
        $average = ceil($normalizedSum * 10) / 10; // Redondear hacia arriba
        echo "<p>Promedio normalizado: $sum / ($sumPercent / 100) = $normalizedSum</p>";
        echo "<p>Fórmula: ceil($normalizedSum * 10) / 10 = <strong>$average</strong></p>";
    }
} else {
    $average = 0.0;
    echo "<p>No hay porcentajes válidos, promedio = 0</p>";
}

// 3. Actualizar la tabla average
$updateSql = "UPDATE average SET average = ? WHERE idStudent = ? AND idSubject = ? AND idSchoolYear = ? AND idSchoolQuarter = ?";
$updateStmt = $conexion->prepare($updateSql);
$updateStmt->bind_param("diiii", $average, $idStudent, $idSubject, $idSchoolYear, $idSchoolQuarter);

if ($updateStmt->execute()) {
    echo "<p style='color: green;'><strong>✓ Promedio actualizado exitosamente a $average</strong></p>";
    echo "<p>Ahora puedes recargar gradesSubject.php y debería mostrar 5.1</p>";
} else {
    echo "<p style='color: red;'>✗ Error al actualizar: " . $updateStmt->error . "</p>";
}

$updateStmt->close();
$stmt->close();
?>