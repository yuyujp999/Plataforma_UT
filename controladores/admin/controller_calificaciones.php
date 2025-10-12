<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Conexión
require_once __DIR__ . '/../../conexion/conexion.php';

$accion = $_POST['accion'] ?? '';

switch ($accion) {

    // ==================== AGREGAR CALIFICACIÓN ====================
    case 'agregar':
        $id_alumno = $_POST['id_alumno'] ?? 0;
        $id_asignacion = $_POST['id_asignacion_docente'] ?? 0;
        $calificacion_final = $_POST['calificacion_final'] ?? null;
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (!$id_alumno || !$id_asignacion || $calificacion_final === null) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $sql = "INSERT INTO calificaciones (id_alumno, id_asignacion_docente, calificacion_final, observaciones, fecha_registro) 
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
            exit;
        }

        $stmt->bind_param('iids', $id_alumno, $id_asignacion, $calificacion_final, $observaciones);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo agregar la calificación']);
        }

        $stmt->close();
        break;

    // ==================== EDITAR CALIFICACIÓN ====================
    case 'editar':
        $id_calificacion = $_POST['id_calificacion'] ?? 0;
        $id_alumno = $_POST['id_alumno'] ?? 0;
        $id_asignacion = $_POST['id_asignacion_docente'] ?? 0;
        $calificacion_final = $_POST['calificacion_final'] ?? null;
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (!$id_calificacion || !$id_alumno || !$id_asignacion || $calificacion_final === null) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $sql = "UPDATE calificaciones 
                SET id_alumno = ?, id_asignacion_docente = ?, calificacion_final = ?, observaciones = ? 
                WHERE id_calificacion = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
            exit;
        }

        $stmt->bind_param('iidsi', $id_alumno, $id_asignacion, $calificacion_final, $observaciones, $id_calificacion);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar la calificación']);
        }

        $stmt->close();
        break;

    // ==================== ELIMINAR CALIFICACIÓN ====================
    case 'eliminar':
        $id_calificacion = $_POST['id_calificacion'] ?? 0;
        if (!$id_calificacion) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            exit;
        }

        $sql = "DELETE FROM calificaciones WHERE id_calificacion = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
            exit;
        }

        $stmt->bind_param('i', $id_calificacion);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar la calificación']);
        }

        $stmt->close();
        break;

    // ==================== ACCIÓN NO VÁLIDA ====================
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

$conn->close();