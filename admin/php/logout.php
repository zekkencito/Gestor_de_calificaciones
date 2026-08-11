<?php
session_start();
// Eliminar cookie rememberMe y token de la base de datos si existe
if (isset($_COOKIE['rememberMe'])) {
    require_once '../../conection.php';
    $token = $_COOKIE['rememberMe'];
    // Borrar token de la base de datos
    $stmt = $conexion->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    // Borrar la cookie
    setcookie('rememberMe', '', time() - 3600, '/');
}
session_destroy();
header('Location: /Gestor_de_calificaciones/index.php');
exit();
?>
