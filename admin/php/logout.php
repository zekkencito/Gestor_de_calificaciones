<?php
require_once dirname(__DIR__, 2) . '/session_config.php';
// Eliminar cookie rememberMe y token de la base de datos si existe
if (isset($_COOKIE['rememberMe'])) {
    require_once '../../conection.php';
    $tokenHash = hash('sha256', $_COOKIE['rememberMe']);
    // Borrar token de la base de datos
    $stmt = $conexion->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    // Borrar la cookie
    setcookie('rememberMe', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Vaciar todas las variables de sesión
$_SESSION = [];

// Si se desea destruir la sesión completamente, borre también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión final
session_destroy();

header('Location: ../../index.php');
exit();
?>
