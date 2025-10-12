<?php
session_start();
header('Content-Type: application/json');

// Mostrar errores para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar sesión
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso']);
    exit;
}

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => "Error de conexión: " . $e->getMessage()]);
    exit;
}

// Leer acción
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'crear':
        $nombre = $_POST['nombre_grado'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $id_carrera = $_POST['id_carrera'] ?? '';

        if (!$nombre || !$numero || !$id_carrera) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO grados (nombre_grado, numero, id_carrera) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $numero, $id_carrera]);

        echo json_encode(['status' => 'success', 'message' => 'Grado creado']);
        break;

    case 'editar':
        $id = $_POST['id_grado'] ?? '';
        $nombre = $_POST['nombre_grado'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $id_carrera = $_POST['id_carrera'] ?? '';

        if (!$id || !$nombre || !$numero || !$id_carrera) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE grados SET nombre_grado = ?, numero = ?, id_carrera = ? WHERE id_grado = ?");
        $stmt->execute([$nombre, $numero, $id_carrera, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Grado actualizado']);
        break;

    case 'eliminar':
        $id = $_POST['id_grado'] ?? '';

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID no válido']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM grados WHERE id_grado = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Grado eliminado']);
        break;

    case 'listar':
        $stmt = $pdo->query("
            SELECT g.id_grado, g.nombre_grado, g.numero, g.id_carrera, c.nombre_carrera
            FROM grados g
            LEFT JOIN carreras c ON g.id_carrera = c.id_carrera
        ");
        $grados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $grados]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}