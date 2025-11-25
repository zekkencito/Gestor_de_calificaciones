<?php
session_start();

// Revisar si hay cookie rememberMe y no hay sesión activa
if (!isset($_SESSION['user_id']) && isset($_COOKIE['rememberMe'])) {
    require_once __DIR__ . "/conection.php";
    $token = $_COOKIE['rememberMe'];
    $sql = "SELECT idUser FROM user_remember_tokens WHERE token = ? AND expires > NOW()";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Recuperar datos del usuario
        $userId = $row['idUser'];
        $sqlUser = "SELECT u.*, r.level_ as role, r.description as role_description FROM users u JOIN roles r ON u.idRole = r.idRole WHERE u.idUser = ?";
        $stmtUser = $conexion->prepare($sqlUser);
        $stmtUser->bind_param("i", $userId);
        $stmtUser->execute();
        $user = $stmtUser->get_result()->fetch_assoc();
        if ($user) {
            $_SESSION['user_id'] = $user['idUser'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['role_description'] = $user['role_description'];
            $_SESSION['idRole'] = $user['idRole'];
            // Redirigir según el rol
            if ($_SESSION['role'] === 'AD' || $_SESSION['idRole'] === 3) {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: teachers/dashboard.php");
            }
            exit();
        }
    }
}

// Si el usuario ya está logueado, redirigirlo al dashboard correspondiente
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'AD') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: teachers/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Escolar</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="./img/logo.ico">
    
    <style>
        /* Estilos personalizados para login */
        body {
            background: linear-gradient(to right,#192E4E,rgb(27, 84, 168));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 0;
            font-family: "League Spartan", sans-serif;
            }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color:#1f2937;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: black;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .input-group {
            margin-bottom: 1.25rem;
        }
        .input-label {
            display: block;
            font-size: 1.2rem;
            color:#1f2937;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .text-input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            font-size: 1rem;
            color: #1f2937;
            transition: border-color 0.2s ease;
        }
        .text-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            font-size: 1rem;
            color: #1f2937;
            transition: border-color 0.2s ease;
        }
        .password-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .toggle-password-visibility {
            position: absolute;
            right: 0.75rem;
            cursor: pointer;
            color: #4b5563;
            transition: color 0.2s ease;
            background: none;
            border: none;
            padding: 0.5rem;
        }
        .toggle-password-visibility:hover {
            color: #2563eb;
        }

        .login-button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.375rem;
            background-color: #192E4E;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 1rem;
        }
        .login-button:hover {
            background-color: rgb(27, 84, 168);
        }
        .login-button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
        }
        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            margin-top: 0rem;
        }
        .logo {
            width: 120px;
            height: 120px;
        }
        .header-content {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 1.5rem;
        }
        .titles {
            flex: 1;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            text-align: center;
        }
        /* Estilos para el degradado en la imagen */
        .gradient-image-wrapper {
            position: relative; /* Para posicionar el degradado absolutamente */
            width: 100%;
            height: 100%;
        }
        .gradient-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Asegura que la imagen cubra el contenedor */
            object-position: left;
        }
        .gradient-overlay {
            position: absolute;
            top: 0;
            right: 0;
            width: 20%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,1)); /* Degradado de transparente a blanco */
        }
    </style>
</head>
<body class="h-screen flex justify-center items-center bg-gray-100 p-4">
    <div class="w-full max-w-6xl flex flex-col md:flex-row bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Imagen de fondo -->
        <div class="md:w-[65%] gradient-image-wrapper">
            <img src="./img/fondoE.jpeg" alt="Fondo" class="w-full h-full object-cover min-h-[300px] md:min-h-[550px] object-left">
             <div class="gradient-overlay"></div>
        </div>
        
        <!-- Formulario -->
        <div class="p-10 w-full md:w-1/2 flex flex-col justify-center">
            <form id="contenedor" action="./admin/php/login.php" method="POST">
                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                ?>
                <div class="logo-container">
                <div class="header-content">
                    <img src="./img/logo.webp" alt="Logo" class="logo">
                    <div class="titles">
                        <h1 class="login-title">INICIAR SESIÓN</h1>
                        <p class="welcome-text">¡Bienvenido de nuevo!</p>
                    </div>
                </div>
                </div>            
                    <div class="input-group">
                        <label for="username" class="input-label">Nombre de Usuario</label>
                        <input type="text"  id="username" name="username" class="text-input" placeholder="Ingrese su nombre de usuario" required>
                    </div>
                
                    <div class="input-group">
                        <label for="password" class="input-label">Contraseña</label>
                        <div class="password-input-wrapper">
                            <input type="password"  id="password" name="password" class="password-input" placeholder="Ingrese su contraseña" required>
                            <button type="button" class="toggle-password-visibility" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-button">Iniciar Sesión</button>
                </form>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePasswordButton = document.getElementById('togglePassword');
        const eyeIcon = togglePasswordButton.querySelector('i');

        togglePasswordButton.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
