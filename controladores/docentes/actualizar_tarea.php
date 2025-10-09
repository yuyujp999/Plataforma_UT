<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (($_SESSION['rol'] ?? '') !== 'docente') { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
require_once __DIR__ . "/../../conexion/conexion.php";

function getDocenteId(mysqli $conn): int {
  $uid = (int)($_SESSION['uid'] ?? 0);
  if ($uid > 0) { $q=$conn->prepare("SELECT id_docente FROM docentes WHERE id_docente=? LIMIT 1"); $q->bind_param("i",$uid); $q->execute(); if($q->get_result()->fetch_row()){ $q->close(); return $uid; } $q->close(); }
  $mat = trim($_SESSION['matricula'] ?? ''); if ($mat!==''){ $q=$conn->prepare("SELECT id_docente FROM docentes WHERE matricula=? LIMIT 1"); $q->bind_param("s",$mat); $q->execute(); if($r=$q->get_result()->fetch_assoc()){ $q->close(); return (int)$r['id_docente']; } $q->close(); }
  $email = trim($_SESSION['email'] ?? ''); if ($email!==''){ $q=$conn->prepare("SELECT id_docente FROM docentes WHERE email=? LIMIT 1"); $q->bind_param("s",$email); $q->execute(); if($r=$q->get_result()->fetch_assoc()){ $q->close(); return (int)$r['id_docente']; } $q->close(); }
  return 0;
}

$id = (int)($_POST['id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fecha = $_POST['fecha_entrega'] ?? '';

if(!$id || $titulo==='' || $fecha===''){ http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }

$docenteId = getDocenteId($conn);
$stmt = $conn->prepare("UPDATE tareas SET titulo=?, descripcion=?, fecha_entrega=? WHERE id=? AND docente_id=?");
$stmt->bind_param("sssii", $titulo, $descripcion, $fecha, $id, $docenteId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['ok'=>$ok]);
