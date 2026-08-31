<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/csrf.php';
validate_csrf_token();
require_once __DIR__ . '/conection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';

if (isset($_SESSION['password_change_message'])) {
    $message = $_SESSION['password_change_message']['text'];
    $message_type = $_SESSION['password_change_message']['type'];
    unset($_SESSION['password_change_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Todos los campos son obligatorios.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Las contraseñas nuevas no coinciden.';
        $message_type = 'error';
    } else {
        $sql = "SELECT password, raw_password FROM users WHERE idUser = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            $is_valid_password = false;
            if (!empty($user['raw_password'])) {
                $is_valid_password = ($current_password === $user['raw_password']);
            } else {
                $is_valid_password = password_verify($current_password, $user['password']);
            }

            if ($is_valid_password) {
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
                    unset($_SESSION['force_password_change']);
                    
                    // Invalidar tokens RememberMe para este usuario
                    $delTokens = $conexion->prepare("DELETE FROM user_remember_tokens WHERE idUser = ?");
                    if ($delTokens) {
                        $delTokens->bind_param("i", $_SESSION['user_id']);
                        $delTokens->execute();
                    }

                    // Forzar reautenticación por seguridad
                    $_SESSION['success'] = 'Contraseña actualizada correctamente. Por favor, inicia sesión nuevamente.';
                    header("Location: admin/php/logout.php");
                    exit();
                } else {
                    $message = 'Error al actualizar la contraseña.';
                    $message_type = 'error';
                }
            } else {
                $message = 'La contraseña actual es incorrecta.';
                $message_type = 'error';
            }
        } else {
            $message = 'Usuario no encontrado.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña — Gestor de Calificaciones</title>
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">

    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/change_password.css">
</head>
<body>
    <div id="preloader">
        <img src="assets/img/logo.png" alt="Logo" class="logo">
    </div>

    <div class="cp-layout">
        <!-- Branding panel -->
        <div class="cp-layout__brand">
            <img
                src="assets/img/logo.png"
                alt="Logo"
                class="cp-brand__logo"
                onerror="this.style.display='none'; document.getElementById('brand-fallback').style.display='flex';"
            >
            <div id="brand-fallback" class="cp-brand__fallback" style="display:none;">
                <i class="bi bi-person-lock"></i>
            </div>
            <h1 class="cp-brand__title">Gestor de Calificaciones</h1>
            <p class="cp-brand__subtitle">Actualiza tu contraseña para proteger tu cuenta.</p>
        </div>

        <!-- Main form area -->
        <div class="cp-layout__main">
            <div class="cp-card">
                <!-- Form state -->
                <div id="form-state">
                    <div class="cp-card__header">
                        <h5><i class="bi bi-shield-lock"></i> Cambiar contraseña</h5>
                    </div>

                    <div class="cp-card__body">
                        <form id="password-form" class="cp-form" method="POST" action="" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            <?php if ($message): ?>
                                <div class="ds-alert ds-alert--<?php echo $message_type === 'error' ? 'error' : 'success'; ?> cp-alert">
                                    <i class="bi <?php echo $message_type === 'error' ? 'bi-exclamation-triangle' : 'bi-check-circle'; ?>"></i>
                                    <span><?php echo htmlspecialchars($message); ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Current password -->
                            <div class="ds-form-group">
                                <label class="ds-label" for="current_password">Contraseña actual</label>
                                <div class="cp-input-wrapper">
                                    <input
                                        type="password"
                                        class="ds-input"
                                        id="current_password"
                                        name="current_password"
                                        required
                                        autocomplete="current-password"
                                    >
                                    <button type="button" class="cp-eye-toggle" data-target="current_password" aria-label="Mostrar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New password -->
                            <div class="ds-form-group">
                                <label class="ds-label" for="new_password">Nueva contraseña</label>
                                <div class="cp-input-wrapper">
                                    <input
                                        type="password"
                                        class="ds-input"
                                        id="new_password"
                                        name="new_password"
                                        required
                                        minlength="6"
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="cp-eye-toggle" data-target="new_password" aria-label="Mostrar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Requirements checklist -->
                            <div class="cp-requirements" id="password-requirements">
                                <div class="cp-requirements__title">Requisitos de seguridad</div>
                                <ul class="cp-requirements__list">
                                    <li class="cp-requirements__item" id="req-length">
                                        <i class="bi bi-x-circle"></i>
                                        <span>Mínimo 6 caracteres</span>
                                    </li>
                                    <li class="cp-requirements__item" id="req-upper">
                                        <i class="bi bi-x-circle"></i>
                                        <span>Al menos una letra mayúscula</span>
                                    </li>
                                    <li class="cp-requirements__item" id="req-number">
                                        <i class="bi bi-x-circle"></i>
                                        <span>Al menos un número</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Confirm password -->
                            <div class="ds-form-group">
                                <label class="ds-label" for="confirm_password">Confirmar contraseña</label>
                                <div class="cp-input-wrapper">
                                    <input
                                        type="password"
                                        class="ds-input"
                                        id="confirm_password"
                                        name="confirm_password"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="cp-eye-toggle" data-target="confirm_password" aria-label="Mostrar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="cp-submit-wrap">
                                <button type="submit" class="ds-btn ds-btn--primary" id="submit-btn">
                                    <i class="bi bi-check-lg"></i>
                                    <span>Guardar contraseña</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Success state -->
                <div id="success-state" class="cp-success" style="display: none;">
                    <div class="cp-success__icon">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h5>Contraseña actualizada</h5>
                    <p>Tu contraseña ha sido cambiada correctamente.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---- Eye toggle ---- */
        document.querySelectorAll('.cp-eye-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var input = document.getElementById(this.dataset.target);
                var icon  = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'bi bi-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'bi bi-eye';
                }
            });
        });

        /* ---- Requirements validation ---- */
        var newPass = document.getElementById('new_password');
        var confirm = document.getElementById('confirm_password');

        function checkRequirements(value) {
            var met = {
                length: value.length >= 6,
                upper: /[A-Z]/.test(value),
                number: /[0-9]/.test(value)
            };
            for (var key in met) {
                var el = document.getElementById('req-' + key);
                var icon = el.querySelector('i');
                if (met[key]) {
                    el.classList.add('met');
                    icon.className = 'bi bi-check-circle';
                } else {
                    el.classList.remove('met');
                    icon.className = 'bi bi-x-circle';
                }
            }
            return met;
        }

        if (newPass) {
            newPass.addEventListener('input', function() {
                checkRequirements(this.value);
            });
        }

        /* ---- Confirm match indicator ---- */
        if (confirm) {
            confirm.addEventListener('input', function() {
                var match = this.value.length > 0 && this.value === newPass.value;
                this.style.borderColor = match ? 'var(--ds-success)' : '';
                this.style.boxShadow   = match ? '0 0 0 3px var(--ds-success-bg)' : '';
            });
        }

        /* ---- Success redirect ---- */
        <?php if ($message_type === 'success'): ?>
        setTimeout(function() {
            document.getElementById('form-state').style.display = 'none';
            document.getElementById('success-state').style.display = 'block';
        }, 100);
        <?php endif; ?>
    });
    </script>

    <script>
    window.addEventListener('load', function() {
        var preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.classList.add('loaded');
            setTimeout(function() { preloader.remove(); }, 500);
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
