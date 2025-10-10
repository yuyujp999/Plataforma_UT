<?php
require_once __DIR__ . "/../../conexion/conexion.php";
$conexion = $conn; // ✅ unifica la variable


$id = $_POST['id'] ?? 0;
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$fecha_entrega = $_POST['fecha_entrega'] ?? '';

$sql = "UPDATE tareas 
        SET titulo='$titulo', descripcion='$descripcion', fecha_entrega='$fecha_entrega'
        WHERE id_tarea='$id'";

if (mysqli_query($conexion, $sql)) {
  echo json_encode(['ok' => true, 'msg' => 'Tarea actualizada']);
} else {
  echo json_encode(['ok' => false, 'msg' => 'Error SQL: ' . mysqli_error($conexion)]);
}
?>
