<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/csrf.php';
validate_csrf_token();
require_once __DIR__ . '/conection.php';

$isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';

if (!$isPreview && !isset($_SESSION['user_id'])) {
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

// Obtener datos del docente / usuario para personalizar la vista
$teacherName = $isPreview ? 'Prof. Roberto González (Vista Previa)' : '';
if (!$isPreview && isset($_SESSION['user_id'])) {
    $stmtUser = $conexion->prepare("SELECT u.username, ui.names, ui.lastnamePa FROM users u LEFT JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo WHERE u.idUser = ? LIMIT 1");
    if ($stmtUser) {
        $stmtUser->bind_param("i", $_SESSION['user_id']);
        $stmtUser->execute();
        $userData = $stmtUser->get_result()->fetch_assoc();
        if ($userData) {
            $teacherName = trim(($userData['names'] ?? '') . ' ' . ($userData['lastnamePa'] ?? ''));
            if (empty($teacherName)) {
                $teacherName = $userData['username'] ?? 'Docente';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Por favor completa todos los campos para continuar.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
        $message_type = 'error';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $message = 'La nueva contraseña debe contener al menos una letra mayúscula.';
        $message_type = 'error';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $message = 'La nueva contraseña debe contener al menos un número.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Las nuevas contraseñas no coinciden.';
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

                    // Limpiar sesión de forma segura y redirigir con confirmación
                    $_SESSION = [];
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000,
                            $params["path"], $params["domain"],
                            $params["secure"], $params["httponly"]
                        );
                    }
                    session_destroy();
                    header("Location: index.php?pw_changed=1");
                    exit();
                } else {
                    $message = 'Ocurrió un error al actualizar la contraseña en el servidor.';
                    $message_type = 'error';
                }
            } else {
                $message = 'La contraseña actual ingresada es incorrecta.';
                $message_type = 'error';
            }
        } else {
            $message = 'Usuario no encontrado en la base de datos.';
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
    <title>Establecer Contraseña — Escuela Primaria Gregorio Torres Quintero</title>
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">

    <link rel="icon" href="./img/logo.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/change_password.css">
</head>
<body class="cp-body">
    <div id="preloader">
        <img src="./img/logo.webp" alt="Logo" class="logo" onerror="this.src='./img/logo.png'">
    </div>

    <div class="cp-layout">
        <!-- ==================== LEFT BRAND PANEL ==================== -->
        <aside class="cp-layout__brand">
            <div class="cp-brand__header">
                <div class="cp-brand__pill">
                    <i class="bi bi-shield-lock-fill"></i> Seguridad Institucional
                </div>
                <div class="cp-brand__emblem-wrap">
                    <img src="./img/logo.webp" alt="Logo Escuela Gregorio Torres Quintero" class="cp-brand__logo" onerror="this.outerHTML='<div class=\'cp-brand__fallback\'><i class=\'bi bi-mortarboard-fill\'></i></div>'">
                    <div>
                        <p class="cp-brand__school">Escuela Primaria</p>
                        <h2 class="cp-brand__title">Gregorio Torres Quintero</h2>
                    </div>
                </div>
            </div>

            <div class="cp-brand__features">
                <div class="cp-brand__feature-item">
                    <div class="cp-brand__feature-icon">
                        <i class="bi bi-person-fill-lock"></i>
                    </div>
                    <div class="cp-brand__feature-text">
                        <h4>Protección de Cuenta Docente</h4>
                        <p>Personaliza tu clave provisoria para garantizar la confidencialidad de tus grupos.</p>
                    </div>
                </div>
                <div class="cp-brand__feature-item">
                    <div class="cp-brand__feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="cp-brand__feature-text">
                        <h4>Políticas de Seguridad SEP</h4>
                        <p>Contraseñas robustas con verificación en tiempo real para evitar accesos no autorizados.</p>
                    </div>
                </div>
                <div class="cp-brand__feature-item">
                    <div class="cp-brand__feature-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="cp-brand__feature-text">
                        <h4>Activación Inmediata</h4>
                        <p>Una vez guardada, tu cuenta quedará activa para acceder al panel de calificaciones.</p>
                    </div>
                </div>
            </div>

            <div class="cp-brand__footer">
                <span>Gestor de Calificaciones v2.0</span>
                <span class="cp-brand__security-badge">
                    <i class="bi bi-lock-fill"></i> Conexión Cifrada SSL
                </span>
            </div>
        </aside>

        <!-- ==================== RIGHT MAIN AREA ==================== -->
        <main class="cp-layout__main">
            <!-- Mobile Brand Header -->
            <div class="cp-mobile-brand">
                <img src="./img/logo.webp" alt="Logo" class="cp-mobile-brand__logo" onerror="this.style.display='none'">
                <p class="cp-mobile-brand__school">Escuela Primaria Gregorio Torres Quintero</p>
                <h2 class="cp-mobile-brand__title">Gestor de Calificaciones</h2>
            </div>

            <div class="cp-card">
                <div class="cp-card__header">
                    <div class="cp-card__icon-badge">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div class="cp-card__titles">
                        <h3 class="cp-card__title">Establecer contraseña</h3>
                        <p class="cp-card__subtitle">Configuración requerida de contraseña personal</p>
                    </div>
                </div>

                <div class="cp-card__body">
                    <!-- Personalized Welcome Banner -->
                    <div class="cp-welcome-banner">
                        <div class="cp-welcome-banner__icon">
                            <i class="bi bi-person-fill-lock"></i>
                        </div>
                        <div class="cp-welcome-banner__content">
                            <h4>Hola, <?php echo htmlspecialchars($teacherName); ?></h4>
                            <p>Por seguridad y privacidad institucional, es necesario que personalices tu contraseña provisional antes de continuar a tu panel de calificaciones.</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="login-alert login-alert--<?php echo $message_type === 'error' ? 'error' : 'success'; ?>">
                            <i class="bi <?php echo $message_type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'; ?>"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="password-form" class="cp-form" method="POST" action="change_password.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

                        <!-- Current password -->
                        <div class="cp-field">
                            <label class="cp-label" for="current_password">Contraseña actual (o provisoria)</label>
                            <div class="cp-input-wrap">
                                <i class="bi bi-key-fill cp-input-icon"></i>
                                <input
                                    type="password"
                                    class="cp-input"
                                    id="current_password"
                                    name="current_password"
                                    placeholder="Ingresa la contraseña con la que accediste"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="cp-eye-toggle" data-target="current_password" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- New password -->
                        <div class="cp-field">
                            <label class="cp-label" for="new_password">Nueva contraseña</label>
                            <div class="cp-input-wrap">
                                <i class="bi bi-lock-fill cp-input-icon"></i>
                                <input
                                    type="password"
                                    class="cp-input"
                                    id="new_password"
                                    name="new_password"
                                    placeholder="Crea una contraseña segura"
                                    required
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <button type="button" class="cp-eye-toggle" data-target="new_password" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password strength meter -->
                        <div class="cp-strength-wrap" id="cp-strength-wrap" style="display: none;">
                            <div class="cp-strength-bar">
                                <div class="cp-strength-segment" id="cp-str-1"></div>
                                <div class="cp-strength-segment" id="cp-str-2"></div>
                                <div class="cp-strength-segment" id="cp-str-3"></div>
                                <div class="cp-strength-segment" id="cp-str-4"></div>
                            </div>
                            <div class="cp-strength-label">
                                <span>Seguridad:</span>
                                <span id="cp-str-text" style="font-weight: 600;">Débil</span>
                            </div>
                        </div>

                        <!-- Requirements checklist -->
                        <div class="cp-requirements" id="password-requirements">
                            <div class="cp-requirements__title">Requisitos de seguridad</div>
                            <ul class="cp-requirements__list">
                                <li class="cp-requirements__item" id="req-length">
                                    <i class="bi bi-circle"></i>
                                    <span>Mínimo 6 caracteres</span>
                                </li>
                                <li class="cp-requirements__item" id="req-upper">
                                    <i class="bi bi-circle"></i>
                                    <span>Al menos una mayúscula</span>
                                </li>
                                <li class="cp-requirements__item" id="req-number">
                                    <i class="bi bi-circle"></i>
                                    <span>Al menos un número</span>
                                </li>
                                <li class="cp-requirements__item" id="req-match">
                                    <i class="bi bi-circle"></i>
                                    <span>Las contraseñas coinciden</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Confirm password -->
                        <div class="cp-field">
                            <label class="cp-label" for="confirm_password">Confirmar nueva contraseña</label>
                            <div class="cp-input-wrap">
                                <i class="bi bi-shield-check cp-input-icon"></i>
                                <input
                                    type="password"
                                    class="cp-input"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Repite la nueva contraseña"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="cp-eye-toggle" data-target="confirm_password" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="cp-btn-primary" id="submit-btn">
                            <i class="bi bi-shield-check" id="submit-icon"></i>
                            <span id="submit-text">Guardar y activar cuenta</span>
                        </button>
                    </form>

                    <div class="cp-card__footer">
                        <a href="admin/php/logout.php" class="cp-logout-link">
                            <i class="bi bi-box-arrow-left"></i> Cerrar sesión y salir
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== CLIENT INTERACTION SCRIPT ==================== -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        /* ---- Eye toggles with tactile feedback ---- */
        document.querySelectorAll('.cp-eye-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (!input) return;
                var icon = this.querySelector('i');
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                if (icon) {
                    icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                }
                this.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        });

        /* ---- Live Requirements and Strength Evaluator ---- */
        var newPass = document.getElementById('new_password');
        var confirm = document.getElementById('confirm_password');
        var currentPass = document.getElementById('current_password');
        var form = document.getElementById('password-form');
        var submitBtn = document.getElementById('submit-btn');
        var submitText = document.getElementById('submit-text');
        var submitIcon = document.getElementById('submit-icon');

        var reqLength = document.getElementById('req-length');
        var reqUpper = document.getElementById('req-upper');
        var reqNumber = document.getElementById('req-number');
        var reqMatch = document.getElementById('req-match');

        var strWrap = document.getElementById('cp-strength-wrap');
        var strSegments = [
            document.getElementById('cp-str-1'),
            document.getElementById('cp-str-2'),
            document.getElementById('cp-str-3'),
            document.getElementById('cp-str-4')
        ];
        var strText = document.getElementById('cp-str-text');

        function checkRequirements() {
            var val = (newPass && newPass.value) || '';
            var confVal = (confirm && confirm.value) || '';

            var lenOk = val.length >= 6;
            var upperOk = /[A-Z]/.test(val);
            var numOk = /[0-9]/.test(val);
            var matchOk = confVal.length > 0 && val === confVal;

            function updateItem(el, ok) {
                if (!el) return;
                el.classList.toggle('met', ok);
                var icon = el.querySelector('i');
                if (icon) {
                    icon.className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                }
            }

            updateItem(reqLength, lenOk);
            updateItem(reqUpper, upperOk);
            updateItem(reqNumber, numOk);
            updateItem(reqMatch, matchOk);

            // Strength calculation
            if (val.length > 0 && strWrap) {
                strWrap.style.display = 'flex';
                var score = 0;
                if (lenOk) score++;
                if (upperOk) score++;
                if (numOk) score++;
                if (/[^A-Za-z0-9]/.test(val) || val.length >= 10) score++;

                var labels = ['Muy débil', 'Débil', 'Media', 'Fuerte', 'Excelente'];
                var classes = ['', 'active-weak', 'active-fair', 'active-good', 'active-strong'];

                if (strText) strText.textContent = labels[score] || 'Débil';

                strSegments.forEach(function(seg, i) {
                    if (!seg) return;
                    seg.className = 'cp-strength-segment';
                    if (i < score) {
                        seg.classList.add(classes[score]);
                    }
                });
            } else if (strWrap) {
                strWrap.style.display = 'none';
            }

            return lenOk && upperOk && numOk && matchOk;
        }

        if (newPass) newPass.addEventListener('input', checkRequirements);
        if (confirm) confirm.addEventListener('input', checkRequirements);

        if (form) {
            form.addEventListener('submit', function(e) {
                var currentVal = (currentPass && currentPass.value.trim()) || '';
                var isValid = checkRequirements();

                if (!currentVal || !isValid) {
                    e.preventDefault();
                    if (!currentVal && currentPass) currentPass.focus();
                    else if (newPass) newPass.focus();
                    return;
                }

                if (submitBtn) submitBtn.disabled = true;
                if (submitIcon) submitIcon.className = 'cp-spinner';
                if (submitText) submitText.textContent = 'Guardando y verificando...';
            });
        }
    });

    // Remove preloader smoothly on load
    window.addEventListener('load', function() {
        var preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.classList.add('loaded');
            setTimeout(function() { preloader.remove(); }, 350);
        }
    });
    </script>
</body>
</html>
