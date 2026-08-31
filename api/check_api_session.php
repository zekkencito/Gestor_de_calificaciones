<?php
// api/check_api_session.php
require_once dirname(__DIR__) . '/session_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit();
}
?>
