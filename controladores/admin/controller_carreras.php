<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../conexion/conexion.php';

// Verificar sesión
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Determinar acción
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'listar':
        listarCarreras($pdo);
        break;
    case 'agregar':
        agregarCarrera($pdo);
        break;
    case 'editar':
        editarCarrera($pdo);
        break;
    case 'eliminar':
        eliminarCarrera($pdo);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

// ================= FUNCIONES =================

function listarCarreras($pdo)
{
    try {
        $stmt = $pdo->query("SELECT id_carrera, nombre_carrera, descripcion, duracion_anios, fecha_creacion FROM carreras ORDER BY id_carrera DESC");
        $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $carreras]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function agregarCarrera($pdo)
{
    $nombre = trim($_POST['nombre_carrera'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion = intval($_POST['duracion_anios'] ?? 3);

    if ($nombre === '') {
        echo json_encode(['status' => 'error', 'message' => 'El nombre de la carrera es obligatorio']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO carreras (nombre_carrera, descripcion, duracion_anios) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $descripcion ?: null, $duracion]);
        echo json_encode(['status' => 'success', 'message' => 'Carrera agregada correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function editarCarrera($pdo)
{
    $id = intval($_POST['id_carrera'] ?? 0);
    $nombre = trim($_POST['nombre_carrera'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion = intval($_POST['duracion_anios'] ?? 3);

    if ($id <= 0 || $nombre === '') {
        echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE carreras SET nombre_carrera = ?, descripcion = ?, duracion_anios = ? WHERE id_carrera = ?");
        $stmt->execute([$nombre, $descripcion ?: null, $duracion, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Carrera actualizada correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function eliminarCarrera($pdo)
{
    $id = intval($_POST['id_carrera'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM carreras WHERE id_carrera = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Carrera eliminada correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}