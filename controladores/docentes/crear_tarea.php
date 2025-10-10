<?php
require_once __DIR__ . '/../../conexion/conexion.php';
session_start();

header('Content-Type: application/json');

$id_docente = $_SESSION['id_docente'] ?? ($_SESSION['usuario']['id_docente'] ?? null);
if (!$id_docente) {
  echo json_encode(['ok' => false, 'msg' => 'No se detectó sesión de docente.']);
  exit;
}

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fecha_entrega = $_POST['fecha_entrega'] ?? '';
$archivo = '';

// === Subida de archivo (opcional) ===
if (!empty($_FILES['archivo']['name'])) {
  $permitidos = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/png',
    'image/jpeg'
  ];

  $tipo = $_FILES['archivo']['type'];
  if (!in_array($tipo, $permitidos)) {
    echo json_encode(['ok' => false, 'msg' => 'Tipo de archivo no permitido']);
    exit;
  }

  $nombreArchivo = time() . "_" . basename($_FILES['archivo']['name']);
  $carpetaDestino = __DIR__ . '/../../uploads/tareas/';
  $rutaDestino = $carpetaDestino . $nombreArchivo;

  if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0777, true);
  }

  if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
    $archivo = $nombreArchivo;
  } else {
    echo json_encode(['ok' => false, 'msg' => 'Error al subir el archivo']);
    exit;
  }
}

// === Insertar en BD ===
$sql = "INSERT INTO tareas (id_docente, titulo, descripcion, fecha_entrega, archivo) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('issss', $id_docente, $titulo, $descripcion, $fecha_entrega, $archivo);

if ($stmt->execute()) {
  echo json_encode(['ok' => true]);
} else {
  echo json_encode(['ok' => false, 'msg' => $conn->error]);
}
