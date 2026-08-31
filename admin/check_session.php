<?php
// Verificar si se está accediendo directamente al archivo
if (basename($_SERVER['PHP_SELF']) === 'check_session.php') {
    die('No se puede acceder directamente a este archivo');
}

// Debe ser lo PRIMERO en el archivo
require_once dirname(__DIR__) . '/session_config.php';
require_once dirname(__DIR__) . '/csrf.php';
validate_csrf_token();

// Verificar si el usuario está logueado y tiene rol base
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['idRole']) || 
    ($_SESSION['role'] !== 'AD' && $_SESSION['idRole'] !== 3)) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Timeout de Inactividad (30 minutos = 1800 segundos)
$timeout_duration = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?expired=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Timeout Absoluto (12 horas = 43200 segundos)
$absolute_timeout = 43200;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $absolute_timeout) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?expired=1");
    exit();
}

// Validación contra BD (Status, Rol, Cambio de Contraseña)
require_once dirname(__DIR__) . '/conection.php';
$stmt_check = $conexion->prepare("SELECT idRole, status, password_change_date FROM users WHERE idUser = ? LIMIT 1");
if ($stmt_check) {
    $stmt_check->bind_param("i", $_SESSION['user_id']);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    
    if ($res_check->num_rows !== 1) {
        // Usuario eliminado
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
    
    $user_check = $res_check->fetch_assoc();
    
    // Validar status activo y rol
    if ($user_check['status'] != 1 || $user_check['idRole'] != $_SESSION['idRole']) {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
    
    // Validar que la contraseña no haya sido cambiada DESPUÉS de haber iniciado sesión
    if (!empty($user_check['password_change_date']) && isset($_SESSION['login_time'])) {
        $change_time = strtotime($user_check['password_change_date']);
        if ($change_time > $_SESSION['login_time']) {
            session_destroy();
            header("Location: ../index.php");
            exit();
        }
    }
}

// Obtener los datos del usuario
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
