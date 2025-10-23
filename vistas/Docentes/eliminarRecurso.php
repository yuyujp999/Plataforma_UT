<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/RecursosController.php";

$id = intval($_GET["id"] ?? 0);
$asig = intval($_GET["asig"] ?? 0);

if ($id > 0) {
  RecursosController::eliminarRecurso($id);
}

header("Location: dashboardMateria.php?id=" . $asig);
exit;
?>
