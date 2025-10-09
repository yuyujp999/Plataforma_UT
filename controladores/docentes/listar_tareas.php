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

$docenteId = getDocenteId($conn);
if ($docenteId === 0) { echo json_encode(['ok'=>true,'data'=>[]]); exit; }

$stmt = $conn->prepare("SELECT id, titulo, descripcion, fecha_entrega, creado_en FROM tareas WHERE docente_id=? ORDER BY creado_en DESC");
$stmt->bind_param("i", $docenteId);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['ok'=>true,'data'=>$data]);
