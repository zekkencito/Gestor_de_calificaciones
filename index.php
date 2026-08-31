<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/csrf.php';
validate_csrf_token();

require_once __DIR__ . "/conection.php";

function buildBaseUrl(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    return $scheme . '://' . $host . $basePath;
}

$authAction = $_GET['action'] ?? '';
$resetToken = trim($_GET['token'] ?? $_POST['token'] ?? '');
$showRecoveryForm = $authAction === 'recover';
$showResetForm = false;
$resetUserId = null;
$pageMessage = '';
$pageMessageType = '';

function getMailConfig(): array
{
    return [
        'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port' => (int) (getenv('MAIL_PORT') ?: 587),
        'encryption' => strtolower(getenv('MAIL_ENCRYPTION') ?: 'tls'),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: (getenv('MAIL_USERNAME') ?: ''),
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Gestor de Calificaciones',
        'reply_to' => getenv('MAIL_REPLY_TO') ?: (getenv('MAIL_USERNAME') ?: ''),
    ];
}

function smtpReadResponse($socket): string
{
    $response = '';

    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    return $response;
}

function smtpExpect($socket, array $codes, string $context): void
{
    $response = smtpReadResponse($socket);
    $code = (int) substr($response, 0, 3);

    if (!in_array($code, $codes, true)) {
        throw new RuntimeException($context . ': ' . trim($response));
    }
}

function smtpSendEmail(array $config, string $toEmail, string $subject, string $htmlMessage, string $plainMessage): bool
{
    if ($config['username'] === '' || $config['password'] === '' || $config['from_email'] === '') {
        throw new RuntimeException('Configura MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD y MAIL_FROM_EMAIL.');
    }

    $transport = ($config['encryption'] === 'ssl') ? 'ssl' : 'tcp';
    $socket = stream_socket_client(
        $transport . '://' . $config['host'] . ':' . $config['port'],
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        throw new RuntimeException('No se pudo conectar al servidor SMTP: ' . $errstr);
    }

    stream_set_timeout($socket, 20);
    smtpExpect($socket, [220], 'SMTP no respondió correctamente');

    fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
    smtpExpect($socket, [250], 'EHLO falló');

    if ($config['encryption'] === 'tls') {
        fwrite($socket, "STARTTLS\r\n");
        smtpExpect($socket, [220], 'STARTTLS falló');

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new RuntimeException('No se pudo activar TLS en SMTP');
        }

        fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        smtpExpect($socket, [250], 'EHLO tras STARTTLS falló');
    }

    fwrite($socket, "AUTH LOGIN\r\n");
    smtpExpect($socket, [334], 'AUTH LOGIN falló');

    fwrite($socket, base64_encode($config['username']) . "\r\n");
    smtpExpect($socket, [334], 'SMTP username rechazado');

    fwrite($socket, base64_encode($config['password']) . "\r\n");
    smtpExpect($socket, [235], 'SMTP password rechazado');

    $fromEmail = $config['from_email'];
    $fromName = $config['from_name'];
    $replyTo = $config['reply_to'] ?: $fromEmail;

    fwrite($socket, "MAIL FROM:<{$fromEmail}>\r\n");
    smtpExpect($socket, [250], 'MAIL FROM falló');

    fwrite($socket, "RCPT TO:<{$toEmail}>\r\n");
    smtpExpect($socket, [250, 251], 'RCPT TO falló');

    fwrite($socket, "DATA\r\n");
    smtpExpect($socket, [354], 'DATA falló');

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $replyTo,
        'To: <' . $toEmail . '>',
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="alt_' . bin2hex(random_bytes(8)) . '"',
        '',
    ];

    $boundary = 'alt_' . bin2hex(random_bytes(8));
    $message = implode("\r\n", [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $replyTo,
        'To: <' . $toEmail . '>',
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        '',
        '--' . $boundary,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        '',
        $plainMessage,
        '',
        '--' . $boundary,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        '',
        $htmlMessage,
        '',
        '--' . $boundary . '--',
        '.',
    ]);

    fwrite($socket, $message . "\r\n");
    smtpExpect($socket, [250], 'Mensaje SMTP rechazado');

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recovery_email'])) {
    $email = trim($_POST['recovery_email']);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Ingresa un correo válido.';
        header('Location: index.php?action=recover');
        exit();
    }
    
    // ----------------------------------------------------
    // RATE LIMITING CHECK
    // ----------------------------------------------------
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $action_limit = 'recovery';
    $sql_limit = "SELECT scope, attempts, last_attempt, identifier FROM rate_limits 
                  WHERE action = ? AND ((scope = 'ip' AND identifier = ?) OR (scope = 'email' AND identifier = ?))";
    $stmt_limit = $conexion->prepare($sql_limit);
    if ($stmt_limit) {
        $stmt_limit->bind_param("sss", $action_limit, $ip_address, $email);
        $stmt_limit->execute();
        $res_limit = $stmt_limit->get_result();
        
        $blocked = false;
        while ($row_limit = $res_limit->fetch_assoc()) {
            $last = strtotime($row_limit['last_attempt']);
            if (time() - $last >= 3600) { // 1 hora
                $sql_reset = "DELETE FROM rate_limits WHERE action = ? AND scope = ? AND identifier = ?";
                $stmt_reset = $conexion->prepare($sql_reset);
                if ($stmt_reset) {
                    $stmt_reset->bind_param("sss", $action_limit, $row_limit['scope'], $row_limit['identifier']);
                    $stmt_reset->execute();
                }
            } else if ($row_limit['attempts'] >= 3) {
                $blocked = true;
                break;
            }
        }
        
        if ($blocked) {
            http_response_code(429);
            $_SESSION['error'] = 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.';
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
            echo '<meta http-equiv="refresh" content="0;url=index.php?action=recover">';
            echo '<title>429 Too Many Requests</title></head><body>';
            echo 'Demasiados intentos. Redirigiendo... <a href="index.php?action=recover">Volver</a>';
            echo '</body></html>';
            exit();
        }
    }
    
    // RECORD ATTEMPT (regardless of if the email exists)
    $sql_fail = "INSERT INTO rate_limits (scope, identifier, action, attempts, last_attempt) 
                 VALUES (?, ?, ?, 1, NOW()) 
                 ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()";
    $stmt_fail = $conexion->prepare($sql_fail);
    if ($stmt_fail) {
        $scope_ip = 'ip';
        $stmt_fail->bind_param("sss", $scope_ip, $ip_address, $action_limit);
        $stmt_fail->execute();
        
        $scope_email = 'email';
        $stmt_fail->bind_param("sss", $scope_email, $email, $action_limit);
        $stmt_fail->execute();
    }
    // ----------------------------------------------------

    $sql = "SELECT u.idUser, u.username, CONCAT(ui.names, ' ', ui.lastnamePa, ' ', ui.lastnameMa) AS full_name, ui.email
            FROM users u
            INNER JOIN usersInfo ui ON u.idUserInfo = ui.idUserInfo
            WHERE ui.email = ?
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && !empty($user['email'])) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $saveSql = "INSERT INTO password_reset_tokens (idUser, token_hash, expires, used_at, created_at)
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE), NULL, NOW())
                    ON DUPLICATE KEY UPDATE
                        token_hash = VALUES(token_hash),
                        expires = VALUES(expires),
                        used_at = NULL,
                        created_at = NOW()";
        $saveStmt = $conexion->prepare($saveSql);
        if ($saveStmt) {
            $saveStmt->bind_param('is', $user['idUser'], $tokenHash);
            if ($saveStmt->execute()) {
                $resetLink = buildBaseUrl() . '/index.php?token=' . urlencode($token);
                $subject = 'Recuperación de contraseña - Gestor de Calificaciones';
                $safeName = htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES, 'UTF-8');

                $htmlMessage = '
                    <html>
                    <head><meta charset="UTF-8"></head>
                    <body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
                        <div style="max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px;">
                            <h2 style="color: #1e3a8a; margin-top: 0;">Recuperación de contraseña</h2>
                            <p>Hola ' . $safeName . ',</p>
                            <p>Recibimos una solicitud para restablecer tu contraseña. Da clic en el siguiente enlace para crear una nueva contraseña:</p>
                            <p style="margin: 24px 0;">
                                <a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; background:#1e3a8a; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px;">Restablecer contraseña</a>
                            </p>
                            <p>Este enlace expira en 60 minutos.</p>
                            <p style="font-size: 0.9rem; color: #6b7280;">Si no solicitaste este cambio, puedes ignorar este correo.</p>
                        </div>
                    </body>
                    </html>
                ';

                $headers = [];
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=UTF-8';
                $headers[] = 'From: Gestor de Calificaciones <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>';
                $headers[] = 'Reply-To: no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

                $plainMessage = "Hola {$user['full_name']},\n\n" .
                    "Recibimos una solicitud para restablecer tu contraseña.\n" .
                    "Abre este enlace para crear una nueva contraseña:\n" .
                    $resetLink . "\n\n" .
                    "Este enlace expira en 60 minutos.\n\n" .
                    "Si no solicitaste este cambio, ignora este correo.";

                try {
                    $mailConfig = getMailConfig();
                    smtpSendEmail($mailConfig, $email, $subject, $htmlMessage, $plainMessage);
                    $_SESSION['success'] = 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.';
                } catch (Throwable $mailException) {
                    error_log('Password recovery email failed for ' . $email . ': ' . $mailException->getMessage());
                    $_SESSION['error'] = 'No pudimos enviar el correo en este momento. Inténtalo nuevamente más tarde.';
                }
            } else {
                $_SESSION['error'] = 'No se pudo generar el enlace de recuperación. Intenta más tarde.';
            }
        } else {
            $_SESSION['error'] = 'No se pudo preparar el enlace de recuperación.';
        }
    } else {
        $_SESSION['success'] = 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.';
    }

    header('Location: index.php?action=recover');
    exit();
}

if ($resetToken !== '') {
    $showResetForm = true;
    $showRecoveryForm = false;

    $tokenHash = hash('sha256', $resetToken);
    $sqlReset = "SELECT prt.idUser, u.username
                 FROM password_reset_tokens prt
                 INNER JOIN users u ON u.idUser = prt.idUser
                 WHERE prt.token_hash = ?
                   AND prt.used_at IS NULL
                   AND prt.expires > NOW()
                 LIMIT 1";
    $stmtReset = $conexion->prepare($sqlReset);
    if ($stmtReset) {
        $stmtReset->bind_param('s', $tokenHash);
        $stmtReset->execute();
        $resultReset = $stmtReset->get_result();

        if ($rowReset = $resultReset->fetch_assoc()) {
            $resetUserId = (int) $rowReset['idUser'];
        } else {
            $pageMessage = 'El enlace ya expiró o ya fue usado. Solicita uno nuevo.';
            $pageMessageType = 'error';
        }
    } else {
        $pageMessage = 'No se pudo validar el enlace de recuperación.';
        $pageMessageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $submittedToken = trim($_POST['token'] ?? '');
    $tokenHash = hash('sha256', $submittedToken);

    $sqlReset = "SELECT prt.idUser
                 FROM password_reset_tokens prt
                 WHERE prt.token_hash = ?
                   AND prt.used_at IS NULL
                   AND prt.expires > NOW()
                 LIMIT 1";
    $stmtReset = $conexion->prepare($sqlReset);
    $stmtReset->bind_param('s', $tokenHash);
    $stmtReset->execute();
    $resultReset = $stmtReset->get_result();

    if ($rowReset = $resultReset->fetch_assoc()) {
        $resetUserId = (int) $rowReset['idUser'];
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 6) {
            $pageMessage = 'La contraseña debe tener al menos 6 caracteres.';
            $pageMessageType = 'error';
            $showResetForm = true;
        } elseif ($newPassword !== $confirmPassword) {
            $pageMessage = 'Las contraseñas no coinciden.';
            $pageMessageType = 'error';
            $showResetForm = true;
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $conexion->begin_transaction();

            try {
                $updateUserSql = "UPDATE users SET password = ?, raw_password = NULL, password_changed = 1, password_change_date = NOW() WHERE idUser = ?";
                $updateUserStmt = $conexion->prepare($updateUserSql);
                $updateUserStmt->bind_param('si', $hashedPassword, $resetUserId);
                $updateUserStmt->execute();

                $invalidateSql = "UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ? AND idUser = ?";
                $invalidateStmt = $conexion->prepare($invalidateSql);
                $invalidateStmt->bind_param('si', $tokenHash, $resetUserId);
                $invalidateStmt->execute();

                // Invalidar tokens RememberMe
                $delTokens = $conexion->prepare("DELETE FROM user_remember_tokens WHERE idUser = ?");
                if ($delTokens) {
                    $delTokens->bind_param("i", $resetUserId);
                    $delTokens->execute();
                }

                $conexion->commit();

                $_SESSION['success'] = 'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.';
                header('Location: index.php');
                exit();
            } catch (Throwable $e) {
                $conexion->rollback();
                $pageMessage = 'No se pudo actualizar la contraseña. Intenta de nuevo.';
                $pageMessageType = 'error';
                $showResetForm = true;
                error_log('Error al restablecer contraseña: ' . $e->getMessage());
            }
        }
    } else {
        $pageMessage = 'El enlace ya expiró o ya fue usado. Solicita uno nuevo.';
        $pageMessageType = 'error';
        $showResetForm = true;
    }
}

// Revisar si hay cookie rememberMe y no hay sesión activa
if (!$showRecoveryForm && !$showResetForm && !isset($_SESSION['user_id']) && isset($_COOKIE['rememberMe'])) {
    $tokenRaw = $_COOKIE['rememberMe'];
    $tokenHash = hash('sha256', $tokenRaw);
    $sql = "SELECT idUser FROM user_remember_tokens WHERE token = ? AND expires > NOW()";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $tokenHash);
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
            // Rotación del token Remember Me
            $newTokenRaw = bin2hex(random_bytes(32));
            $newTokenHash = hash('sha256', $newTokenRaw);
            $stmtUpdate = $conexion->prepare("UPDATE user_remember_tokens SET token = ?, expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE idUser = ?");
            $stmtUpdate->bind_param("si", $newTokenHash, $userId);
            $stmtUpdate->execute();
            
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            if (!$isHttps && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $isHttps = true;
            }
            setcookie('rememberMe', $newTokenRaw, [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Hardening de sesión auto-login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['idUser'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['role_description'] = $user['role_description'];
            $_SESSION['idRole'] = $user['idRole'];
            $_SESSION['login_time'] = time();

            // Redirigir según el rol
            if ($_SESSION['role'] === 'AD' || $_SESSION['idRole'] == 3) {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: teachers/dashboard.php");
            }
            exit();
        }
    } else {
        // Token inválido o expirado, borrar la cookie
        setcookie('rememberMe', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    }
}

// Si el usuario ya está logueado, redirigirlo al dashboard correspondiente
if (!$showRecoveryForm && !$showResetForm && isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
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
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <title>Inicio de Sesión - Gestor de Calificaciones</title>
    <link rel="icon" href="./img/logo.ico">
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./css/design-system.css">
    <link rel="stylesheet" href="./css/components.css">
    <link rel="stylesheet" href="./css/login.css">
</head>

<body class="login-layout">

    <aside class="login-layout__brand">
        <img src="./img/logo.webp" alt="Logo" class="login-brand__logo" onerror="this.outerHTML='<div class=\'login-brand__fallback\'><i class=\'bi bi-mortarboard-fill\'></i></div>'">
        <h1 class="login-brand__title">Gestor de Calificaciones</h1>
        <p class="login-brand__subtitle">Plataforma de administración académica</p>
    </aside>

    <main class="login-layout__main">

        <?php if ($showResetForm): ?>
        <!-- ==================== RESET PASSWORD ==================== -->
        <div class="login-card">
            <div class="login-card__header">
                <h5><i class="bi bi-shield-lock"></i> Restablecer contraseña</h5>
            </div>
            <div class="login-card__body">

                <?php if ($pageMessage): ?>
                <div class="ds-alert ds-alert--<?php echo $pageMessageType === 'success' ? 'success' : 'error'; ?> login-alert">
                    <i class="bi bi-<?php echo $pageMessageType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
                    <?php echo htmlspecialchars($pageMessage); ?>
                </div>
                <?php endif; ?>

                <?php if ($resetUserId): ?>
                <form method="POST" action="index.php" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="ds-form-group">
                        <label for="new_password" class="ds-label">Nueva contraseña</label>
                        <div class="login-input-wrapper">
                            <input type="password" id="new_password" name="new_password" class="ds-input" placeholder="Mínimo 6 caracteres" required minlength="6">
                            <button type="button" class="login-eye-toggle" aria-label="Mostrar contraseña" data-target="new_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="ds-form-group">
                        <label for="confirm_password" class="ds-label">Confirmar contraseña</label>
                        <div class="login-input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" class="ds-input" placeholder="Repite la nueva contraseña" required minlength="6">
                            <button type="button" class="login-eye-toggle" aria-label="Mostrar contraseña" data-target="confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="login-requirements" id="passwordRequirements">
                        <p class="login-requirements__title">Requisitos</p>
                        <ul class="login-requirements__list">
                            <li class="login-requirements__item" id="req-length">
                                <i class="bi bi-circle"></i> Mínimo 6 caracteres
                            </li>
                            <li class="login-requirements__item" id="req-match">
                                <i class="bi bi-circle"></i> Las contraseñas coinciden
                            </li>
                        </ul>
                    </div>

                    <button type="submit" class="ds-btn ds-btn--primary login-submit">
                        <i class="bi bi-check-lg"></i> Actualizar contraseña
                    </button>

                    <div class="login-back">
                        <a href="index.php"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
                    </div>
                </form>
                <?php else: ?>
                <div class="login-token-error">
                    <div class="login-token-error__icon">
                        <i class="bi bi-x-lg"></i>
                    </div>
                    <h5>Enlace no válido</h5>
                    <p><?php echo htmlspecialchars($pageMessage ?: 'El enlace ya expiró o ya fue usado. Solicita uno nuevo.'); ?></p>
                    <div class="login-back login-back--spaced">
                        <a href="index.php?action=recover"><i class="bi bi-arrow-left"></i> Solicitar nuevo enlace</a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <?php elseif ($showRecoveryForm): ?>
        <!-- ==================== RECOVER PASSWORD ==================== -->
        <div class="login-card">
            <div class="login-card__header">
                <h5><i class="bi bi-envelope-lock"></i> Recuperar contraseña</h5>
            </div>
            <div class="login-card__body">

                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="ds-alert ds-alert--error login-alert"><i class="bi bi-exclamation-triangle-fill"></i> ' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo '<div class="ds-alert ds-alert--success login-alert"><i class="bi bi-check-circle-fill"></i> ' . $_SESSION['success'] . '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <div class="login-recovery-desc">
                    <p>Escribe el correo registrado para enviarte un enlace de recuperación temporal.</p>
                </div>

                <form method="POST" action="index.php?action=recover" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="ds-form-group">
                        <label for="recovery_email" class="ds-label">Correo electrónico</label>
                        <input type="email" id="recovery_email" name="recovery_email" class="ds-input" placeholder="correo@ejemplo.com" required>
                    </div>

                    <button type="submit" class="ds-btn ds-btn--primary login-submit">
                        <i class="bi bi-send"></i> Enviar enlace
                    </button>

                    <div class="login-back">
                        <a href="index.php"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
                    </div>
                </form>

            </div>
        </div>

        <?php else: ?>
        <!-- ==================== LOGIN ==================== -->
        <div class="login-card">
            <div class="login-card__header">
                <h5><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</h5>
            </div>
            <div class="login-card__body">

                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="ds-alert ds-alert--error login-alert"><i class="bi bi-exclamation-triangle-fill"></i> ' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo '<div class="ds-alert ds-alert--success login-alert"><i class="bi bi-check-circle-fill"></i> ' . $_SESSION['success'] . '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <div id="message" class="ds-alert ds-alert--error login-alert login-alert--hidden"></div>

                <form id="loginForm" action="./admin/php/login.php" method="POST" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="ds-form-group">
                        <label for="username" class="ds-label">Usuario</label>
                        <input type="text" id="username" name="username" class="ds-input" placeholder="Ingresa tu usuario" required>
                    </div>

                    <div class="ds-form-group">
                        <label for="password" class="ds-label">Contraseña</label>
                        <div class="login-input-wrapper">
                            <input type="password" id="password" name="password" class="ds-input" placeholder="••••••••" required>
                            <button type="button" class="login-eye-toggle" aria-label="Mostrar contraseña" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="ds-btn ds-btn--primary login-submit" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span id="buttonText">Acceder</span>
                    </button>

                    <div class="login-link-row">
                        <a href="index.php?action=recover">¿Olvidaste tu contraseña?</a>
                    </div>
                </form>

            </div>
        </div>
        <?php endif; ?>

    </main>

    <script>
    (function () {
        'use strict';

        /* ---- Eye toggles (all views) ---- */
        document.querySelectorAll('.login-eye-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = document.getElementById(btn.dataset.target);
                if (!target) return;
                var icon = btn.querySelector('i');
                var isPassword = target.type === 'password';
                target.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        });

        /* ---- Login form validation ---- */
        var form = document.getElementById('loginForm');
        var usernameInput = document.getElementById('username');
        var passwordInput = document.getElementById('password');
        var messageDiv = document.getElementById('message');
        var loginBtn = document.getElementById('loginBtn');
        var buttonText = document.getElementById('buttonText');

        if (form) {
            form.addEventListener('submit', function (e) {
                var username = usernameInput ? usernameInput.value.trim() : '';
                var password = passwordInput ? passwordInput.value.trim() : '';

                hideMessage();

                if (!username || !password) {
                    e.preventDefault();
                    showMessage('Por favor completa todos los campos', 'error');
                    return;
                }

                setLoading(true);
            });
        }

        function showMessage(text, type) {
            if (!messageDiv) return;
            messageDiv.textContent = text;
            messageDiv.className = 'ds-alert ds-alert--' + type + ' login-alert';
        }

        function hideMessage() {
            if (messageDiv) messageDiv.className = 'ds-alert ds-alert--error login-alert login-alert--hidden';
        }

        function setLoading(loading) {
            if (loginBtn) loginBtn.disabled = loading;
            if (buttonText) buttonText.textContent = loading ? 'Verificando...' : 'Acceder';
        }

        /* ---- Reset form: live password requirements ---- */
        var newPw = document.getElementById('new_password');
        var confirmPw = document.getElementById('confirm_password');
        var reqLength = document.getElementById('req-length');
        var reqMatch = document.getElementById('req-match');

        if (newPw && confirmPw && reqLength && reqMatch) {
            function updateReqs() {
                var lenOk = newPw.value.length >= 6;
                var matchOk = confirmPw.value.length > 0 && newPw.value === confirmPw.value;

                reqLength.classList.toggle('met', lenOk);
                reqLength.querySelector('i').className = lenOk ? 'bi bi-check-circle-fill' : 'bi bi-circle';

                reqMatch.classList.toggle('met', matchOk);
                reqMatch.querySelector('i').className = matchOk ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            }
            newPw.addEventListener('input', updateReqs);
            confirmPw.addEventListener('input', updateReqs);
        }
    })();
    </script>

</body>
</html>