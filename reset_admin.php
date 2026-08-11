<?php
require_once "conection.php";

$username = 'admin25';
$newPasswordPlain = 'admin123';
$hashedPassword = password_hash($newPasswordPlain, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ?, raw_password = NULL, password_changed = 1 WHERE username = ?";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $hashedPassword, $username);
    if ($stmt->execute()) {
        echo "<h1>¡Éxito!</h1>";
        echo "<p>La contraseña del usuario <b>$username</b> ha sido reseteada exitosamente.</p>";
        echo "<p>Tu nueva contraseña temporal es: <b>$newPasswordPlain</b></p>";
        echo "<p><a href='index.php'>Ir al inicio de sesión</a></p>";
        echo "<p><i>Nota: Por seguridad, elimina este archivo (reset_admin.php) de tu proyecto antes de subirlo a producción.</i></p>";
    } else {
        echo "Error al actualizar la contraseña: " . $stmt->error;
    }
} else {
    echo "Error al preparar la consulta: " . $conexion->error;
}
?>
