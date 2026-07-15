<?php
// Verificar si se está accediendo directamente al archivo
if (basename($_SERVER['PHP_SELF']) === 'check_session.php') {
    die('No se puede acceder directamente a este archivo');
}

session_start();

// Verificar si el usuario está logueado y es administrador (AD) o tiene idRole 3
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['idRole']) || 
    ($_SESSION['role'] !== 'AD' && $_SESSION['idRole'] !== 3)) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Obtener los datos del usuario
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
