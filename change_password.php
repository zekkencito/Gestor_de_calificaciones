<?php
session_start();

// Verificar que el usuario esté logueado y necesite cambiar contraseña
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Si no necesita cambio de contraseña, redirigir al dashboard
if (!isset($_SESSION['force_password_change']) || $_SESSION['force_password_change'] !== true) {
    if ($_SESSION['role'] === 'AD' || $_SESSION['idRole'] === 3) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: teachers/dashboard.php");
    }
    exit();
}

require_once "conection.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (strlen($new_password) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Las contraseñas nuevas no coinciden';
    } else {
        // Verificar contraseña actual
        $sql = "SELECT raw_password FROM users WHERE idUser = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($current_password === $user['raw_password']) {
                // Contraseña correcta, actualizar
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $updateSql = "UPDATE users SET 
                             password = ?, 
                             raw_password = NULL, 
                             password_changed = 1,
                             password_change_date = NOW()
                             WHERE idUser = ?";
                $updateStmt = $conexion->prepare($updateSql);
                $updateStmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($updateStmt->execute()) {
                    // Eliminar la bandera de cambio forzoso
                    unset($_SESSION['force_password_change']);
                    
                    // Redirigir al dashboard
                    if ($_SESSION['role'] === 'AD' || $_SESSION['idRole'] === 3) {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: teachers/dashboard.php");
                    }
                    exit();
                } else {
                    $error = 'Error al actualizar la contraseña';
                }
            } else {
                $error = 'La contraseña actual es incorrecta';
            }
        } else {
            $error = 'Usuario no encontrado';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Contraseña Obligatorio - Gestor de Calificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .change-password-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            color: #333;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .form-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        
        .security-warning {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .form-label {
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-change-password {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-change-password:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.85rem;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="logo-container">
            <img src="img/logo.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        
        <h1 class="form-title">Cambio de Contraseña</h1>
        <p class="form-subtitle">Por su seguridad, debe cambiar su contraseña temporal</p>
        
        <div class="security-warning">
            <i class="fas fa-shield-alt me-2"></i>
            Su cuenta utiliza una contraseña temporal. Por razones de seguridad, debe establecer una nueva contraseña antes de continuar.
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="current_password" class="form-label">
                    <i class="fas fa-key me-2"></i>Contraseña Actual (Temporal)
                </label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
                <div class="invalid-feedback">
                    Por favor ingrese su contraseña actual.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Nueva Contraseña
                </label>
                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                <div class="invalid-feedback">
                    La contraseña debe tener al menos 6 caracteres.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Confirmar Nueva Contraseña
                </label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <div class="invalid-feedback">
                    Por favor confirme su nueva contraseña.
                </div>
            </div>
            
            <div class="password-requirements">
                <strong>Requisitos de la contraseña:</strong>
                <ul>
                    <li>Mínimo 6 caracteres</li>
                    <li>Se recomienda usar una combinación de letras, números y símbolos</li>
                    <li>No debe ser igual a contraseñas anteriores</li>
                </ul>
            </div>
            
            <button type="submit" class="btn btn-change-password mt-4">
                <i class="fas fa-shield-alt me-2"></i>Cambiar Contraseña
            </button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Verificar que las contraseñas coincidan
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>