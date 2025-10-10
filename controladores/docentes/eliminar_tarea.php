<?php
require_once __DIR__ . "/../../conexion/conexion.php";
$conexion = $conn; // ✅ unifica la variable


$id = $_POST['id'] ?? 0;
$sql = "DELETE FROM tareas WHERE id_tarea = $id";

if (mysqli_query($conexion, $sql)) {
  echo json_encode(['ok' => true, 'msg' => 'Tarea eliminada']);
} else {
  echo json_encode(['ok' => false, 'msg' => 'Error SQL: ' . mysqli_error($conexion)]);
}
?>
