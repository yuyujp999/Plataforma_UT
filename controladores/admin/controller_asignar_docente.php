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
        $id_docente = $_POST['id_docente'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_grado = $_POST['id_grado'] ?? 0;
        $id_ciclo = $_POST['id_ciclo'] ?? 0;
        $grupo = trim($_POST['grupo'] ?? '');

        if (!$id_docente || !$id_materia || !$id_grado || !$id_ciclo || $grupo === '') {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $sql = "INSERT INTO asignaciones_docentes (id_docente, id_materia, id_grado, id_ciclo, grupo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
            exit;
        }

        $stmt->bind_param('iiiis', $id_docente, $id_materia, $id_grado, $id_ciclo, $grupo);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo agregar la asignación']);
        }
        $stmt->close();
        break;

    case 'editar':
        $id_asignacion = $_POST['id_asignacion'] ?? 0;
        $id_docente = $_POST['id_docente'] ?? 0;
        $id_materia = $_POST['id_materia'] ?? 0;
        $id_grado = $_POST['id_grado'] ?? 0;
        $id_ciclo = $_POST['id_ciclo'] ?? 0;
        $grupo = trim($_POST['grupo'] ?? '');

        if (!$id_asignacion || !$id_docente || !$id_materia || !$id_grado || !$id_ciclo || $grupo === '') {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $sql = "UPDATE asignaciones_docentes SET id_docente = ?, id_materia = ?, id_grado = ?, id_ciclo = ?, grupo = ? WHERE id_asignacion_docente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiiisi', $id_docente, $id_materia, $id_grado, $id_ciclo, $grupo, $id_asignacion);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar la asignación']);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id_asignacion = $_POST['id_asignacion'] ?? 0;

        if (!$id_asignacion) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            exit;
        }

        $sql = "DELETE FROM asignaciones_docentes WHERE id_asignacion_docente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_asignacion);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar la asignación']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

$conn->close();