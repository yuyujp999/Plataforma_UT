<?php
require_once __DIR__ . '/../../conexion/conexion.php';
session_start();

header('Content-Type: application/json');

$id = $_GET['id'] ?? '';
if (!$id) {
  echo json_encode(['ok' => false, 'msg' => 'ID no recibido']);
  exit;
}

$sql = "SELECT id_tarea AS id, titulo, descripcion, fecha_entrega, archivo, fecha_creacion AS creado_en
        FROM tareas WHERE id_tarea = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();

if ($t = $res->fetch_assoc()) {
  $t['archivo_url'] = $t['archivo'] ? '/Plataforma_UT/uploads/tareas/' . $t['archivo'] : '';
  echo json_encode(['ok' => true, 'data' => $t]);
} else {
  echo json_encode(['ok' => false, 'msg' => 'Tarea no encontrada']);
}
