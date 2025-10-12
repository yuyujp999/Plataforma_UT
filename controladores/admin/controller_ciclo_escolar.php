<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/conexion.php';

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        $nombre = trim($_POST['nombre_ciclo'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_fin = $_POST['fecha_fin'] ?? null;

        if ($nombre === '') {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del ciclo es obligatorio']);
            exit;
        }

        $sql = "INSERT INTO ciclos_escolares (nombre_ciclo, fecha_inicio, fecha_fin) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
            exit;
        }

        $stmt->bind_param('sss', $nombre, $fecha_inicio, $fecha_fin);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo agregar el ciclo']);
        }
        $stmt->close();
        break;

    case 'editar':
        $id = $_POST['id_ciclo'] ?? 0;
        $nombre = trim($_POST['nombre_ciclo'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_fin = $_POST['fecha_fin'] ?? null;

        if (!$id || $nombre === '') {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $sql = "UPDATE ciclos_escolares SET nombre_ciclo = ?, fecha_inicio = ?, fecha_fin = ? WHERE id_ciclo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $nombre, $fecha_inicio, $fecha_fin, $id);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el ciclo']);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id = $_POST['id_ciclo'] ?? 0;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            exit;
        }

        $sql = "DELETE FROM ciclos_escolares WHERE id_ciclo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el ciclo']);
        }
        $stmt->close();
        break;

    case 'toggle':
        $id = $_POST['id_ciclo'] ?? 0;
        $activo = $_POST['activo'] ?? 0;

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            exit;
        }

        $nuevo = $activo ? 0 : 1;
        $sql = "UPDATE ciclos_escolares SET activo = ? WHERE id_ciclo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $nuevo, $id);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            echo json_encode(['status' => 'success', 'nuevo' => $nuevo]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo cambiar el estado']);
        }
        $stmt->close();
        break;

}

$conn->close();