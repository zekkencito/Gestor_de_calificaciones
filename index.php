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

if (isset($_GET['expired'])) {
    $pageMessage = 'Tu sesión ha expirado por inactividad. Por favor inicia sesión nuevamente.';
    $pageMessageType = 'info';
} elseif (isset($_GET['updated']) || isset($_GET['pw_changed'])) {
    $pageMessage = 'Tu contraseña ha sido actualizada correctamente. Inicia sesión con tus nuevas credenciales.';
    $pageMessageType = 'success';
}

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
                    <!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    </head>
                    <body style="margin:0; padding:0; background-color:#f8fafc; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color:#0f172a; line-height:1.6;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; padding:32px 16px;">
                            <tr>
                                <td align="center">
                                    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:540px; background:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                                        <tr>
                                            <td style="background:linear-gradient(145deg, #0c1624 0%, #112038 50%, #192E4E 100%); padding:28px 32px; text-align:center; color:#ffffff;">
                                                <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:#adc4df; margin-bottom:4px;">Escuela Primaria Gregorio Torres Quintero</div>
                                                <div style="font-size:22px; font-weight:700; color:#ffffff; letter-spacing:-0.01em;">Gestor de Calificaciones</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:32px 32px 28px 32px;">
                                                <h2 style="font-size:18px; font-weight:700; color:#0f172a; margin:0 0 16px 0;">Recuperación de contraseña</h2>
                                                <p style="font-size:14px; color:#334155; margin:0 0 16px 0;">Hola <strong>' . $safeName . '</strong>,</p>
                                                <p style="font-size:14px; color:#475569; margin:0 0 24px 0;">Recibimos una solicitud para restablecer la contraseña de tu cuenta institucional. Haz clic en el botón siguiente para crear una nueva clave de acceso:</p>
                                                <div style="text-align:center; margin:28px 0;">
                                                    <a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; background:#192E4E; color:#ffffff; text-decoration:none; font-size:14px; font-weight:600; padding:14px 28px; border-radius:10px; box-shadow:0 2px 6px rgba(25,46,78,0.25);">Restablecer mi contraseña</a>
                                                </div>
                                                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin:24px 0 0 0;">
                                                    <p style="font-size:12px; color:#64748b; margin:0;"><strong>Importante:</strong> Este enlace de un solo uso expirará en <strong>60 minutos</strong> por seguridad.</p>
                                                </div>
                                                <p style="font-size:12px; color:#94a3b8; margin:20px 0 0 0; line-height:1.5;">Si tú no solicitaste este cambio, puedes ignorar este mensaje; tu contraseña actual seguirá siendo segura.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 32px; text-align:center; font-size:11px; color:#94a3b8;">
                                                Plataforma Académica Oficial • Sistema de Control Escolar
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
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
    <title>Gestor de Calificaciones — Escuela Primaria Gregorio Torres Quintero</title>
    <link rel="icon" href="./img/logo.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./css/design-system.css">
    <link rel="stylesheet" href="./css/components.css">
    <link rel="stylesheet" href="./css/login.css">
</head>

<body class="login-layout">

    <!-- ==================== LEFT BRAND PANEL ==================== -->
    <aside class="login-layout__brand">
        <div class="login-brand__header">
            <div class="login-brand__pill">
                <i class="bi bi-shield-lock-fill"></i> Acceso Institucional
            </div>
            <div class="login-brand__emblem-wrap">
                <img src="./img/logo.webp" alt="Logo Escuela Gregorio Torres Quintero" class="login-brand__logo" onerror="this.outerHTML='<div class=\'login-brand__fallback\'><i class=\'bi bi-mortarboard-fill\'></i></div>'">
                <div>
                    <p class="login-brand__school">Escuela Primaria</p>
                    <h2 class="login-brand__title">Gregorio Torres Quintero</h2>
                </div>
            </div>
        </div>

        <div class="login-brand__features">
            <div class="login-brand__feature-item">
                <div class="login-brand__feature-icon">
                    <i class="bi bi-person-workspace"></i>
                </div>
                <div class="login-brand__feature-text">
                    <h4>Gestión Docente y Grupos</h4>
                    <p>Captura y administración ágil de evaluaciones por periodos escolares.</p>
                </div>
            </div>
            <div class="login-brand__feature-item">
                <div class="login-brand__feature-icon">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                </div>
                <div class="login-brand__feature-text">
                    <h4>Boletas y Reportes Oficiales</h4>
                    <p>Emisión automatizada de reportes individuales y concentrados de aprovechamiento.</p>
                </div>
            </div>
            <div class="login-brand__feature-item">
                <div class="login-brand__feature-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="login-brand__feature-text">
                    <h4>Seguridad y Control Institucional</h4>
                    <p>Acceso seguro con cifrado y trazabilidad para docentes y directivos.</p>
                </div>
            </div>
        </div>

        <div class="login-brand__footer">
            <span>Gestor de Calificaciones v2.0</span>
            <span class="login-brand__security-badge">
                <i class="bi bi-lock-fill"></i> Conexión Segura SSL
            </span>
        </div>
    </aside>

    <!-- ==================== RIGHT MAIN AREA ==================== -->
    <main class="login-layout__main">

        <!-- Mobile Brand (Header shown on tablet/phone) -->
        <div class="login-mobile-brand">
            <img src="./img/logo.webp" alt="Logo" class="login-mobile-brand__logo" onerror="this.style.display='none'">
            <p class="login-mobile-brand__school">Escuela Primaria Gregorio Torres Quintero</p>
            <h2 class="login-mobile-brand__title">Gestor de Calificaciones</h2>
        </div>

        <?php if ($showResetForm): ?>
        <!-- ==================== VIEW: RESET PASSWORD ==================== -->
        <div class="login-card">
            <div class="login-card__header">
                <div class="login-card__icon-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div class="login-card__titles">
                    <h3 class="login-card__title">Restablecer contraseña</h3>
                    <p class="login-card__subtitle">Configura una nueva contraseña segura para tu cuenta</p>
                </div>
            </div>

            <div class="login-card__body">
                <?php if ($pageMessage): ?>
                <div class="login-alert login-alert--<?php echo $pageMessageType === 'success' ? 'success' : ($pageMessageType === 'info' ? 'info' : 'error'); ?>">
                    <i class="bi bi-<?php echo $pageMessageType === 'success' ? 'check-circle-fill' : ($pageMessageType === 'info' ? 'info-circle-fill' : 'exclamation-triangle-fill'); ?>"></i>
                    <span><?php echo htmlspecialchars($pageMessage); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($resetUserId): ?>
                <form method="POST" action="index.php" class="login-form" id="resetPasswordForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- New Password -->
                    <div class="login-field">
                        <label for="new_password" class="login-label">Nueva contraseña</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-lock-fill login-input-icon"></i>
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                class="login-input"
                                placeholder="Mínimo 6 caracteres"
                                required
                                minlength="6"
                                autocomplete="new-password"
                            >
                            <button type="button" class="login-eye-toggle" aria-label="Mostrar contraseña" data-target="new_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Password strength bar -->
                    <div class="login-strength-wrap" id="reset-strength-wrap" style="display: none;">
                        <div class="login-strength-bar">
                            <div class="login-strength-segment" id="reset-str-1"></div>
                            <div class="login-strength-segment" id="reset-str-2"></div>
                            <div class="login-strength-segment" id="reset-str-3"></div>
                            <div class="login-strength-segment" id="reset-str-4"></div>
                        </div>
                        <div class="login-strength-label">
                            <span>Seguridad:</span>
                            <span id="reset-str-text" style="font-weight: 600;">Débil</span>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="login-field">
                        <label for="confirm_password" class="login-label">Confirmar nueva contraseña</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-shield-check login-input-icon"></i>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="login-input"
                                placeholder="Repite la nueva contraseña"
                                required
                                minlength="6"
                                autocomplete="new-password"
                            >
                            <button type="button" class="login-eye-toggle" aria-label="Mostrar contraseña" data-target="confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Live Requirements Checklist -->
                    <div class="login-requirements" id="passwordRequirements">
                        <div class="login-requirements__title">Requisitos de seguridad</div>
                        <ul class="login-requirements__list">
                            <li class="login-requirements__item" id="req-length">
                                <i class="bi bi-circle"></i> <span>Mínimo 6 caracteres</span>
                            </li>
                            <li class="login-requirements__item" id="req-upper">
                                <i class="bi bi-circle"></i> <span>Al menos una letra mayúscula</span>
                            </li>
                            <li class="login-requirements__item" id="req-number">
                                <i class="bi bi-circle"></i> <span>Al menos un número</span>
                            </li>
                            <li class="login-requirements__item" id="req-match">
                                <i class="bi bi-circle"></i> <span>Las contraseñas coinciden</span>
                            </li>
                        </ul>
                    </div>

                    <button type="submit" class="login-btn-primary" id="btnResetSubmit">
                        <i class="bi bi-check2-circle"></i>
                        <span id="btnResetText">Actualizar contraseña</span>
                    </button>

                    <div class="login-card__footer">
                        <a href="index.php" class="login-back-link">
                            <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="login-token-error">
                    <div class="login-token-error__icon">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <h5>Enlace no válido o expirado</h5>
                    <p><?php echo htmlspecialchars($pageMessage ?: 'El enlace ya expiró o ya fue utilizado previamente. Por favor solicita uno nuevo.'); ?></p>
                    <a href="index.php?action=recover" class="login-btn-primary" style="max-width: 260px; margin: 0 auto;">
                        <i class="bi bi-arrow-repeat"></i> Solicitar nuevo enlace
                    </a>
                </div>
                <div class="login-card__footer">
                    <a href="index.php" class="login-back-link">
                        <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($showRecoveryForm): ?>
        <!-- ==================== VIEW: RECOVER PASSWORD ==================== -->
        <div class="login-card">
            <div class="login-card__header">
                <div class="login-card__icon-badge">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div class="login-card__titles">
                    <h3 class="login-card__title">Recuperar contraseña</h3>
                    <p class="login-card__subtitle">Solicitud de restablecimiento de cuenta</p>
                </div>
            </div>

            <div class="login-card__body">
                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="login-alert login-alert--error"><i class="bi bi-exclamation-triangle-fill"></i> <span>' . htmlspecialchars($_SESSION['error']) . '</span></div>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo '<div class="login-alert login-alert--success"><i class="bi bi-check-circle-fill"></i> <span>' . htmlspecialchars($_SESSION['success']) . '</span></div>';
                    unset($_SESSION['success']);
                }
                ?>

                <div class="login-callout">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>Ingresa el correo electrónico institucional registrado. Te enviaremos un enlace seguro con vigencia de <strong>60 minutos</strong>.</p>
                </div>

                <form method="POST" action="index.php?action=recover" class="login-form" id="recoveryForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

                    <div class="login-field">
                        <label for="recovery_email" class="login-label">Correo electrónico institucional</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-envelope-fill login-input-icon"></i>
                            <input
                                type="email"
                                id="recovery_email"
                                name="recovery_email"
                                class="login-input login-input--no-toggle"
                                placeholder="ejemplo@escuela.edu.mx"
                                required
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <button type="submit" class="login-btn-primary" id="btnRecovery">
                        <i class="bi bi-send-fill"></i>
                        <span id="btnRecoveryText">Enviar enlace de recuperación</span>
                    </button>

                    <div class="login-card__footer">
                        <a href="index.php" class="login-back-link">
                            <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- ==================== VIEW: LOGIN ==================== -->
        <div class="login-card">
            <div class="login-card__header">
                <div class="login-card__icon-badge">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="login-card__titles">
                    <h3 class="login-card__title">Iniciar sesión</h3>
                    <p class="login-card__subtitle">Ingresa tus credenciales para acceder a la plataforma</p>
                </div>
            </div>

            <div class="login-card__body">
                <?php
                if (!empty($pageMessage)) {
                    $alertClass = $pageMessageType === 'success' ? 'login-alert--success' : ($pageMessageType === 'info' ? 'login-alert--info' : 'login-alert--error');
                    $iconClass = $pageMessageType === 'success' ? 'bi-check-circle-fill' : ($pageMessageType === 'info' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill');
                    echo '<div class="login-alert ' . $alertClass . '"><i class="bi ' . $iconClass . '"></i> <span>' . htmlspecialchars($pageMessage) . '</span></div>';
                }
                if (isset($_SESSION['error'])) {
                    echo '<div class="login-alert login-alert--error"><i class="bi bi-exclamation-triangle-fill"></i> <span>' . htmlspecialchars($_SESSION['error']) . '</span></div>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo '<div class="login-alert login-alert--success"><i class="bi bi-check-circle-fill"></i> <span>' . htmlspecialchars($_SESSION['success']) . '</span></div>';
                    unset($_SESSION['success']);
                }
                ?>

                <!-- Dynamic client validation alert -->
                <div id="message" class="login-alert login-alert--error login-alert--hidden"></div>

                <form id="loginForm" action="./admin/php/login.php" method="POST" class="login-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

                    <!-- Username Field -->
                    <div class="login-field">
                        <label for="username" class="login-label">Usuario</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-person-fill login-input-icon"></i>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="login-input login-input--no-toggle"
                                placeholder="Ingresa tu usuario"
                                required
                                autocomplete="username"
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="login-field">
                        <label for="password" class="login-label">Contraseña</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-lock-fill login-input-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="login-input"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="login-eye-toggle" aria-label="Mostrar u ocultar contraseña" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Options Row: Remember Me & Forgot Password -->
                    <div class="login-options-row">
                        <label class="login-remember" for="rememberMe">
                            <input type="checkbox" name="rememberMe" id="rememberMe" value="1" class="login-checkbox">
                            <span class="login-checkbox-custom"><i class="bi bi-check"></i></span>
                            <span class="login-remember-text">Recordar mi sesión</span>
                        </label>

                        <a href="index.php?action=recover" class="login-forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="login-btn-primary" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right" id="loginIcon"></i>
                        <span id="buttonText">Acceder a la plataforma</span>
                    </button>
                </form>

                <div class="login-card__footer">
                    <span style="font-size: 0.75rem; color: var(--ds-gray-400, #94a3b8); display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="bi bi-shield-check"></i> Acceso seguro para personal docente y directivo
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <!-- ==================== CLIENT INTERACTIVITY SCRIPT ==================== -->
    <script>
    (function () {
        'use strict';

        /* ---- Eye Toggles with tactile feedback ---- */
        document.querySelectorAll('.login-eye-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var target = document.getElementById(targetId);
                if (!target) return;
                var icon = btn.querySelector('i');
                var isPassword = target.type === 'password';
                target.type = isPassword ? 'text' : 'password';
                if (icon) {
                    icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                }
                btn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        });

        /* ---- Login Form Validation & Loading State ---- */
        var loginForm = document.getElementById('loginForm');
        var usernameInput = document.getElementById('username');
        var passwordInput = document.getElementById('password');
        var messageDiv = document.getElementById('message');
        var loginBtn = document.getElementById('loginBtn');
        var buttonText = document.getElementById('buttonText');
        var loginIcon = document.getElementById('loginIcon');

        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                var username = usernameInput ? usernameInput.value.trim() : '';
                var password = passwordInput ? passwordInput.value.trim() : '';

                hideMessage();

                if (!username || !password) {
                    e.preventDefault();
                    showMessage('Por favor completa todos los campos para continuar.', 'error');
                    if (!username && usernameInput) usernameInput.focus();
                    else if (!password && passwordInput) passwordInput.focus();
                    return;
                }

                // Loading feedback
                setLoading(true);
            });
        }

        function showMessage(text, type) {
            if (!messageDiv) return;
            messageDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> <span>' + text + '</span>';
            messageDiv.className = 'login-alert login-alert--' + (type === 'success' ? 'success' : 'error');
        }

        function hideMessage() {
            if (messageDiv) messageDiv.className = 'login-alert login-alert--error login-alert--hidden';
        }

        function setLoading(loading) {
            if (!loginBtn) return;
            loginBtn.disabled = loading;
            if (loading) {
                if (loginIcon) loginIcon.className = 'login-spinner';
                if (buttonText) buttonText.textContent = 'Verificando credenciales...';
            } else {
                if (loginIcon) loginIcon.className = 'bi bi-box-arrow-in-right';
                if (buttonText) buttonText.textContent = 'Acceder a la plataforma';
            }
        }

        /* ---- Recovery Form Validation ---- */
        var recoveryForm = document.getElementById('recoveryForm');
        var recoveryEmail = document.getElementById('recovery_email');
        var btnRecovery = document.getElementById('btnRecovery');
        var btnRecoveryText = document.getElementById('btnRecoveryText');

        if (recoveryForm) {
            recoveryForm.addEventListener('submit', function (e) {
                var email = recoveryEmail ? recoveryEmail.value.trim() : '';
                if (!email) {
                    e.preventDefault();
                    if (recoveryEmail) recoveryEmail.focus();
                    return;
                }
                if (btnRecovery) btnRecovery.disabled = true;
                if (btnRecoveryText) btnRecoveryText.innerHTML = '<span class="login-spinner" style="margin-right: 6px;"></span> Enviando enlace...';
            });
        }

        /* ---- Reset Form Live Password Strength & Requirements ---- */
        var newPw = document.getElementById('new_password');
        var confirmPw = document.getElementById('confirm_password');
        var resetForm = document.getElementById('resetPasswordForm');
        var btnResetSubmit = document.getElementById('btnResetSubmit');
        var btnResetText = document.getElementById('btnResetText');

        var reqLength = document.getElementById('req-length');
        var reqUpper = document.getElementById('req-upper');
        var reqNumber = document.getElementById('req-number');
        var reqMatch = document.getElementById('req-match');

        var strWrap = document.getElementById('reset-strength-wrap');
        var strSegments = [
            document.getElementById('reset-str-1'),
            document.getElementById('reset-str-2'),
            document.getElementById('reset-str-3'),
            document.getElementById('reset-str-4')
        ];
        var strText = document.getElementById('reset-str-text');

        function updateResetRequirements() {
            if (!newPw) return;
            var val = newPw.value || '';
            var conf = (confirmPw && confirmPw.value) || '';

            var lenOk = val.length >= 6;
            var upperOk = /[A-Z]/.test(val);
            var numOk = /[0-9]/.test(val);
            var matchOk = conf.length > 0 && val === conf;

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
                    seg.className = 'login-strength-segment';
                    if (i < score) {
                        seg.classList.add(classes[score]);
                    }
                });
            } else if (strWrap) {
                strWrap.style.display = 'none';
            }
        }

        if (newPw) newPw.addEventListener('input', updateResetRequirements);
        if (confirmPw) confirmPw.addEventListener('input', updateResetRequirements);

        if (resetForm) {
            resetForm.addEventListener('submit', function (e) {
                var p1 = newPw ? newPw.value : '';
                var p2 = confirmPw ? confirmPw.value : '';

                if (p1.length < 6 || p1 !== p2) {
                    e.preventDefault();
                    updateResetRequirements();
                    return;
                }

                if (btnResetSubmit) btnResetSubmit.disabled = true;
                if (btnResetText) btnResetText.innerHTML = '<span class="login-spinner" style="margin-right: 6px;"></span> Actualizando...';
            });
        }
    })();
    </script>

</body>
</html>