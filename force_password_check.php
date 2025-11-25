<?php
/**
 * Verificación de cambio de contraseña obligatorio
 * Incluir este archivo en cualquier página que requiera autenticación
 * DEBE incluirse DESPUÉS de iniciar la sesión
 */

// Verificar si el usuario necesita cambiar contraseña obligatoriamente
if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === true) {
    // Determinar la ruta correcta hacia change_password.php según la ubicación del archivo actual
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    
    if (strpos($script_dir, '/admin') !== false) {
        // Estamos en una carpeta admin
        $redirect_url = "../change_password.php";
    } elseif (strpos($script_dir, '/teachers') !== false) {
        // Estamos en una carpeta teachers
        $redirect_url = "../change_password.php";
    } else {
        // Estamos en la raíz
        $redirect_url = "change_password.php";
    }
    
    header("Location: " . $redirect_url);
    exit();
}
?>