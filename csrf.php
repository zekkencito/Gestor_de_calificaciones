<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session_config.php';
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function get_csrf_token() {
    return generate_csrf_token();
}

function validate_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = '';
        
        // Verificar token en $_POST
        if (!empty($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } 
        // Verificar token en headers
        else {
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                foreach ($headers as $name => $value) {
                    if (strtolower($name) === 'x-csrf-token') {
                        $token = $value;
                        break;
                    }
                }
            }
            
            // Fallback común para Nginx/FPM
            if (empty($token) && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
        }
        
        $session_token = $_SESSION['csrf_token'] ?? '';
        
        if (empty($session_token) || empty($token) || !hash_equals($session_token, $token)) {
            http_response_code(403);
            
            // Determinar si es AJAX
            $isAjax = false;
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                $isAjax = true;
            } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                $isAjax = true;
            } elseif (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $isAjax = true;
            } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $isAjax = true;
            }
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF token', 'message' => 'Fallo en la validación de seguridad.']);
            } else {
                echo '403 Forbidden - Acceso denegado (CSRF)';
            }
            exit();
        }
    }
}
?>
