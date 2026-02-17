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
    <title>Inicio de Sesión - Gestor de Calificaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="icon" href="./img/logo.ico">
    
    <style>
        :root {
            /* Paleta minimalista - Azul marino, blanco y gris */
            --navy: #1e3a8a;
            --navy-light: #3b82f6;
            --navy-dark: #1e293b;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            
            /* Estados */
            --success: #059669;
            --error: #dc2626;
            
            /* Sombras */
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 70%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        /* === ANIMACIONES === */
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
            25% { transform: translateY(-10px) translateX(5px) rotate(1deg); }
            50% { transform: translateY(-8px) translateX(-3px) rotate(-0.5deg); }
            75% { transform: translateY(-12px) translateX(4px) rotate(1.5deg); }
        }

        @keyframes logoHover {
            0% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.12) rotate(-2deg); }
            100% { transform: scale(1.1) rotate(3deg); }
        }

        @keyframes decorativeFloat1 {
            0%, 100% { transform: translateY(0px) translateX(0px) scale(1); opacity: 0.1; }
            33% { transform: translateY(-15px) translateX(8px) scale(1.1); opacity: 0.15; }
            66% { transform: translateY(-10px) translateX(-5px) scale(1.05); opacity: 0.12; }
        }

        @keyframes decorativeFloat2 {
            0%, 100% { transform: translateX(0px) translateY(0px) scale(1); opacity: 0.08; }
            40% { transform: translateX(10px) translateY(-8px) scale(1.05); opacity: 0.12; }
            80% { transform: translateX(-6px) translateY(5px) scale(1.08); opacity: 0.1; }
        }

        .container {
            position: relative;
            z-index: 1;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .image-section {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .logo-container {
            position: relative;
            width: 200px;
            height: 200px;
            background: var(--white);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-xl);
            padding: 20px;
            animation: logoFloat 3s ease-in-out infinite;
        }

        .logo-container:hover {
            animation: logoHover 0.6s ease-in-out;
            transform: scale(1.1) rotate(3deg);
        }

        .logo-main {
            width: 150px;
            height: 150px;
            object-fit: contain;
            filter: opacity(0.9);
            transition: all 0.3s ease;
        }

        .logo-container:hover .logo-main {
            filter: opacity(1) brightness(1.1);
        }

        .decorative-elements {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .decorative-elements::before {
            content: '';
            position: absolute;
            top: 20%;
            left: 10%;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: decorativeFloat1 4s ease-in-out infinite;
        }

        .decorative-elements::after {
            content: '';
            position: absolute;
            bottom: 30%;
            right: 15%;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: decorativeFloat2 3.5s ease-in-out infinite reverse;
        }

        .login-section {
            padding: 80px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--white);
        }

        .title {
            font-size: 2.1rem;
            font-weight: 1200;
            color: var(--navy-dark);
            text-align: center;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .subtitle {
            font-size: 1rem;
            color: var(--gray-400);
            text-align: center;
            margin-bottom: 40px;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-600);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            font-size: 0.95rem;
            color: var(--gray-800);
            background: var(--white);
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.08);
        }

        .form-input::placeholder {
            color: var(--gray-300);
            font-weight: 400;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-300);
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--navy);
        }

        .login-button {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-dark) 100%);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 16px;
            font-family: inherit;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(30, 58, 138, 0.3);
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-button:hover {
            background: linear-gradient(135deg, var(--navy-dark) 0%, #0f1729 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(30, 58, 138, 0.4), 0 0 20px rgba(30, 58, 138, 0.2);
            filter: blur(0) brightness(1.1);
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .message {
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 24px;
            display: none;
            font-weight: 500;
        }

        .message.error {
            background: rgba(220, 38, 38, 0.1);
            color: var(--error);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .message.success {
            background: rgba(5, 150, 105, 0.1);
            color: var(--success);
            border: 1px solid rgba(5, 150, 105, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                margin: 20px;
                border-radius: 16px;
            }

            .image-section {
                display: none;
            }

            .login-section {
                padding: 60px 40px;
            }

            .title {
                font-size: 1.75rem;
            }
        }

        /* Estados de validación más sutiles */
        .form-input.valid {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.08);
        }

        .form-input.invalid {
            border-color: var(--error);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.08);
        }

        /* Estilos para errores PHP */
        .php-error {
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 24px;
            background: rgba(220, 38, 38, 0.1);
            color: var(--error);
            border: 1px solid rgba(220, 38, 38, 0.2);
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="image-section">
            <div class="decorative-elements"></div>
            <div class="logo-container">
                <img src="./img/logo.webp" alt="Logo" class="logo-main">
            </div>
        </div>
        
        <div class="login-section">
            <h1 class="title">¡Bienvenido!</h1>
            <p class="subtitle">Ingresa tus credenciales para acceder</p>
            
            <form id="loginForm" action="./admin/php/login.php" method="POST">
                <!-- Mostrar errores PHP si existen -->
                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="php-error">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                ?>
                <div id="message" class="message"></div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Ingresa tu usuario"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="••••••••"
                            required
                        >
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-button">
                    <div class="loading-spinner" id="loadingSpinner"></div>
                    <span id="buttonText">Acceder</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const toggleIcon = toggleButton.querySelector('i');

        toggleButton.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.classList.toggle('fa-eye');
            toggleIcon.classList.toggle('fa-eye-slash');
        });

        // Form validation and submission
        const form = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');
        const messageDiv = document.getElementById('message');
        const loginButton = document.querySelector('.login-button');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const buttonText = document.getElementById('buttonText');

        // Real-time validation
        [usernameInput, passwordInput].forEach(input => {
            input.addEventListener('input', () => {
                validateField(input);
                clearFieldErrors(); // Limpiar errores al escribir
            });
            input.addEventListener('blur', () => validateField(input));
        });

        function validateField(field) {
            const value = field.value.trim();
            
            field.classList.remove('valid', 'invalid');
            
            if (value.length === 0) {
                return;
            }
            
            field.classList.add('valid');
        }

        // Función para limpiar errores cuando el usuario empiece a escribir
        function clearFieldErrors() {
            usernameInput.classList.remove('invalid');
            passwordInput.classList.remove('invalid');
            hideMessage();
        }

        // Form submission con validación JavaScript
        form.addEventListener('submit', (e) => {
            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();
            
            hideMessage();
            
            // Validation
            if (!username || !password) {
                e.preventDefault(); // Prevenir envío
                showMessage('Por favor completa todos los campos', 'error');
                // Marcar campos vacíos como inválidos
                if (!username) usernameInput.classList.add('invalid');
                if (!password) passwordInput.classList.add('invalid');
                return;
            }
            
            // Limpiar estados de error antes de enviar
            usernameInput.classList.remove('invalid');
            passwordInput.classList.remove('invalid');
            
            // Mostrar loading (el formulario se enviará normalmente)
            setLoading(true);
        });

        function showMessage(text, type) {
            messageDiv.textContent = text;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';
        }

        function hideMessage() {
            messageDiv.style.display = 'none';
        }

        function setLoading(loading) {
            loginButton.disabled = loading;
            loadingSpinner.style.display = loading ? 'inline-block' : 'none';
            buttonText.textContent = loading ? 'Verificando...' : 'Ingresar';
        }
    </script>
</body>
</html>