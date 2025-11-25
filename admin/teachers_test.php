<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Iniciando prueba...<br>";

try {
    echo "2. Incluyendo archivos...<br>";
    require_once "check_session.php";
    echo "3. check_session.php cargado<br>";
    
    require_once "../force_password_check.php";
    echo "4. force_password_check.php cargado<br>";
    
    include '../conection.php';
    echo "5. Conexión incluida<br>";
    
    if (!$conexion) {
        die("Error de conexión: " . mysqli_connect_error());
    }
    echo "6. Conexión exitosa<br>";
    
    $sql = "SELECT COUNT(*) as total FROM teachers";
    $result = $conexion->query($sql);
    
    if (!$result) {
        die("Error en consulta básica: " . $conexion->error);
    }
    
    $row = $result->fetch_assoc();
    echo "7. Prueba de consulta exitosa. Total de teachers: " . $row['total'] . "<br>";
    
    echo "8. Todo funciona correctamente!<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>