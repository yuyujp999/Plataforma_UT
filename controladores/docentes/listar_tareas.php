<?php
require_once __DIR__ . '/../../conexion/conexion.php';
session_start();

header('Content-Type: application/json');

$id_docente = $_SESSION['id_docente'] ?? ($_SESSION['usuario']['id_docente'] ?? null);
if (!$id_docente) {
  echo json_encode(['ok' => false, 'msg' => 'No se detectó sesión de docente.']);
  exit;
}

$sql = "SELECT id_tarea AS id, titulo, descripcion, fecha_entrega, archivo, fecha_creacion AS creado_en
        FROM tareas
        WHERE id_docente = ?
        ORDER BY fecha_creacion DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_docente);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
  // Agregar ruta pública al archivo si existe
  if (!empty($row['archivo'])) {
    $row['archivo_url'] = '/Plataforma_UT/uploads/tareas/' . $row['archivo'];
  } else {
    $row['archivo_url'] = '';
  }
  $data[] = $row;
}

echo json_encode(['ok' => true, 'data' => $data]);
