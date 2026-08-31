<?php
// enforce_post.php
// Este archivo debe ser incluido en todos los endpoints que modifiquen estado.
// Garantiza que el método sea estrictamente POST y rechaza cualquier intento de method override.

$method = $_SERVER['REQUEST_METHOD'] ?? '';

// Detectar y bloquear cabeceras de Method Override
$overrideHeaders = [
    'HTTP_X_HTTP_METHOD', 
    'HTTP_X_HTTP_METHOD_OVERRIDE', 
    'HTTP_X_METHOD_OVERRIDE'
];

foreach ($overrideHeaders as $header) {
    if (isset($_SERVER[$header])) {
        header('Allow: POST');
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Method Override no permitido']);
        exit;
    }
}

// Bloquear cualquier método que no sea POST
if ($method !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    
    // Si la solicitud acepta JSON o es un script que devuelve JSON
    $isJson = false;
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        $isJson = true;
    }
    
    if ($isJson || basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.']);
    } else {
        // Fallback para endpoints tradicionales no-JSON como el login
        echo "405 Method Not Allowed - Se requiere POST";
    }
    exit;
}

// Bloquear Parameter Pollution y Array Injection
// Como la aplicación no usa arrays en forms, rechazamos cualquier array en $_POST o $_GET
foreach ([$_GET, $_POST] as $superglobal) {
    foreach ($superglobal as $key => $value) {
        if (is_array($value)) {
            header('Allow: POST');
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Estructura de parámetros inválida (Array no permitido)']);
            exit;
        }
    }
}
?>
