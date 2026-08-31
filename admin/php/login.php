
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Debe ser lo PRIMERO en el archivo, sin espacios/blancos antes
session_start();
require_once dirname(__DIR__) . '/../enforce_post.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Incluir la conexión
    require_once "../../conection.php";
    
    // Verificar si la conexión se estableció
    if (!$conexion) {
        $_SESSION['error'] = 'Error de conexión a la base de datos';
        header('Location: ../../index.php');
        exit();
    }
    
    // Validar datos
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $_SESSION['error'] = 'Por favor, complete todos los campos';
        header('Location: ../../index.php');
        exit();
    }

    $username = mysqli_real_escape_string($conexion, $_POST['username']);
    $password = $_POST['password'];

    // Obtener el usuario
    $sql = "SELECT u.*, r.level_ as role, r.description as role_description 
            FROM users u 
            JOIN roles r ON u.idRole = r.idRole 
            WHERE u.username = ?";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        $_SESSION['error'] = 'Error en la consulta';
        header('Location: ../../index.php');
        exit();
    }
    
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Error al ejecutar la consulta';
        header('Location: ../../index.php');
        exit();
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Verificar la contraseña (priorizando el método seguro)
        $password_valid = false;
        
        // Verificar si el campo password_changed existe (compatibilidad)
        $password_changed_exists = isset($row['password_changed']);
        
        // Si el usuario aún no ha cambiado su contraseña (tiene raw_password)
        if (!empty($row['raw_password']) && (!$password_changed_exists || $row['password_changed'] == 0 || is_null($row['password_changed']))) {
            // Usar la contraseña temporal para autenticar
            $password_valid = ($password === $row['raw_password']);
            $needs_password_change = true;
            
            // Mitigación de timing attack (Dummy hash verification)
            password_verify($password, '$2y$10$abcdefghijklmnopqrstuv');
        } else {
            // Usar el hash seguro para autenticar
            $password_valid = password_verify($password, $row['password']);
            $needs_password_change = false;
        }
        
        if ($password_valid) {
            // Hardening de sesión (Fase 7)
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $row['idUser'];  
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['role_description'] = $row['role_description'];
            $_SESSION['idRole'] = $row['idRole'];
            $_SESSION['login_time'] = time();
            
            // Si necesita cambiar contraseña, redirigir a la página de cambio
            if ($needs_password_change) {
                $_SESSION['force_password_change'] = true;
                header("Location: ../../change_password.php");
                exit();
            }
            
            // Recordar sesión si el usuario marcó "Recordarme"
            if (isset($_POST['rememberMe'])) {
                // Generar un token seguro
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $userId = $row['idUser'];
                
                // Guardar el token hasheado en la base de datos usando sentencias preparadas
                $stmt_token = $conexion->prepare("INSERT INTO user_remember_tokens (idUser, token, expires) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY)) ON DUPLICATE KEY UPDATE token=?, expires=DATE_ADD(NOW(), INTERVAL 30 DAY)");
                $stmt_token->bind_param("iss", $userId, $tokenHash, $tokenHash);
                $stmt_token->execute();
                
                $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                if (!$isHttps && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                    $isHttps = true;
                }
                
                // Enviar el token RAW en la cookie
                setcookie('rememberMe', $token, [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'domain' => '',
                    'secure' => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            
            // Redirigir según el rol
            if (isset($_POST['test_mode'])) { echo 'OK'; exit; }
            if ($_SESSION['role'] === 'AD' || $_SESSION['idRole'] == 3) {
                header("Location: ../dashboard.php");
            } else {
                header("Location: ../../teachers/dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error'] = 'Usuario o contraseña incorrectos';
            header('Location: ../../index.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Usuario o contraseña incorrectos';
        echo "ERROR: " . $_SESSION['error'];
        exit();
    }
}
?>