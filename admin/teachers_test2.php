<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "check_session.php";
require_once "../force_password_check.php";
include '../conection.php';

echo "Probando la consulta compleja...<br>";

// Probar la consulta SQL exacta que usa teachers.php
$sql = "SELECT 
    t.idTeacher,
    t.profesionalID,
    t.ine,
    t.typeTeacher,
    ui.names,
    ui.lastnamePa,
    ui.lastnameMa,
    ui.gender,
    ui.phone,
    ui.email,
    ui.street,
    ts.description AS status,
    u.username,
    u.password,
    u.raw_password,
    GROUP_CONCAT(DISTINCT CONCAT(g.grade, '°', g.group_)) AS grupos,
    GROUP_CONCAT(DISTINCT s.name) AS materias
FROM teachers t
INNER JOIN users u ON t.idUser = u.idUser
INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
INNER JOIN teacherStatus ts ON t.idTeacherStatus = ts.idTeacherStatus
LEFT JOIN teacherGroupsSubjects tgs ON t.idTeacher = tgs.idTeacher
LEFT JOIN groups g ON tgs.idGroup = g.idGroup
LEFT JOIN subjects s ON tgs.idSubject = s.idSubject
GROUP BY t.idTeacher";

echo "Ejecutando consulta...<br>";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error en la consulta SQL: " . $conexion->error);
}

echo "Consulta ejecutada exitosamente. Número de filas: " . $resultado->num_rows . "<br>";

echo "Probando el bucle de datos...<br>";
$count = 0;
while($fila = mysqli_fetch_array($resultado)){
    $count++;
    echo "Procesando fila $count: " . $fila['names'] . " " . $fila['lastnamePa'] . "<br>";
    
    // Probar el procesamiento de grupos
    $grupos = $fila['grupos'];
    echo "Grupos: " . ($grupos ? $grupos : 'NULL') . "<br>";
    
    // Probar el procesamiento de materias
    $materias = $fila['materias'];
    echo "Materias: " . ($materias ? $materias : 'NULL') . "<br>";
    
    if ($count > 5) {
        echo "Deteniendo después de 5 registros para evitar sobrecarga...<br>";
        break;
    }
}

echo "Bucle completado exitosamente!<br>";
?>