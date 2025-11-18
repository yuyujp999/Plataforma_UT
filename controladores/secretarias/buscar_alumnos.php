<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Silenciar notices/warnings en salida (para no romper JSON)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/* ======================= Helpers JSON ======================= */
function send_json(array $payload, int $http_status = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($http_status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function json_error(string $msg, int $http_status = 200): void
{
    send_json(['status' => 'error', 'message' => $msg], $http_status);
}

/* ======================= Sesión / Permisos ======================= */
if (!isset($_SESSION['rol'])) {
    json_error('No tienes permiso', 403);
}
$rol = strtolower(trim($_SESSION['rol'] ?? ''));

// solo admin/secretaría pueden usar este buscador
if (!in_array($rol, ['admin', 'secretaria', 'secretarías', 'secretarias', 'secretaría'], true)) {
    json_error('Acceso restringido', 403);
}

/* ======================= Conexión PDO ======================= */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    json_error("Error de conexión: " . $e->getMessage(), 500);
}

/* ======================= Búsqueda ======================= */
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    // si no mandan texto, regresamos arreglo vacío
    send_json([]);
}

try {
    $sql = $pdo->prepare("
        SELECT 
            matricula,
            nombre,
            apellido_paterno,
            apellido_materno
        FROM alumnos
        WHERE 
              matricula       LIKE :bus
           OR nombre          LIKE :bus
           OR apellido_paterno LIKE :bus
           OR apellido_materno LIKE :bus
        ORDER BY apellido_paterno, apellido_materno, nombre
        LIMIT 20
    ");

    $like = '%' . $q . '%';
    $sql->bindValue(':bus', $like, PDO::PARAM_STR);
    $sql->execute();

    $alumnos = $sql->fetchAll(PDO::FETCH_ASSOC);

    send_json($alumnos);
} catch (PDOException $e) {
    json_error('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    json_error('Error del servidor: ' . $e->getMessage(), 500);
}