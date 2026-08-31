<?php

// ── Load .env from project root ──────────────────────────────────────
function loadEnvFile(string $filePath): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name]   = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnvFile(__DIR__ . '/.env');

// ── Validate required DB variables ───────────────────────────────────
$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$dbName = getenv('DB_NAME');
$dbPort = getenv('DB_PORT');

if ($dbHost === false || $dbUser === false || $dbName === false) {
    die("Error de configuración: faltan variables DB_* en .env. Consulta .env.example para referencia.");
}

// ── Timezone ─────────────────────────────────────────────────────────
date_default_timezone_set('America/Mexico_City');

// ── Database connection ──────────────────────────────────────────────
$servidor = $dbHost;
$port     = $dbPort ?: '3306';
$user     = $dbUser;
$password = $dbPass !== false ? $dbPass : '';
$db       = $dbName;

$conexion = new mysqli($servidor, $user, $password, $db, $port);
if ($conexion->connect_error) {
    error_log("Error de conexión a la base de datos: " . $conexion->connect_error);
    die("Error al conectar con el servidor. Por favor, intente más tarde.");
}
$conexion->set_charset("utf8mb4");
$conexion->query("SET time_zone = '-06:00'");
