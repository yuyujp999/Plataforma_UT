<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        $nombre = $_POST['nombre_materia'] ?? '';

        if (!$nombre) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO materias (nombre_materia) VALUES (?)");
        if ($stmt->execute([$nombre])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo agregar la materia']);
        }
        break;

    case 'editar':
        $id = $_POST['id_materia'] ?? null;
        $nombre = $_POST['nombre_materia'] ?? '';

        if (!$id || !$nombre) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE materias SET nombre_materia = ? WHERE id_materia = ?");
        if ($stmt->execute([$nombre, $id])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar la materia']);
        }
        break;

    case 'eliminar':
        $id = $_POST['id_materia'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID no válido']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM materias WHERE id_materia = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar la materia']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida']);
        break;
}