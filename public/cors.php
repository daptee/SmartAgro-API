<?php
/**
 * Manejador CORS - Intercepta peticiones OPTIONS y agrega headers CORS
 * Este archivo se ejecuta desde .htaccess para manejar peticiones preflight
 */

// Origen permitido
$allowed_origin = 'https://app.smartagro.io';

// Obtener el origen de la petición
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Verificar si el origen es permitido
if ($origin === $allowed_origin) {
    header('Access-Control-Allow-Origin: ' . $allowed_origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-API-KEY, Accept-Language, Content-Language');
    header('Access-Control-Expose-Headers: Content-Length, Content-Type, X-JSON-Response, Authorization');
    header('Access-Control-Max-Age: 86400');
    header('Access-Control-Allow-Credentials: true');
}

// Si es una petición OPTIONS, responde 200 y termina
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Para cualquier otra petición, también agregag los headers y continúa normalmente
exit;
?>

