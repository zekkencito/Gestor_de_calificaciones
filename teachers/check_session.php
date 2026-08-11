<?php
session_start();
// Verificar si se está accediendo directamente al archivo
if (basename($_SERVER['PHP_SELF']) === 'check_session.php') {
    die('No se puede acceder directamente a este archivo');
}

// Verificar si el usuario está logueado y es un profesor (DO) o maestro especial (ME)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['DO', 'ME', 'MS'])) {
    session_destroy();
    header("Location: /Gestor_de_calificaciones/index.php");
    exit();
}

// Obtener los datos del usuario
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
