<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/TareasController.php";

$idTarea = intval($_GET['id'] ?? 0);
$idAsignacion = intval($_GET['asig'] ?? 0);

if ($idTarea > 0) {
  TareasController::eliminarTarea($idTarea);
}

header("Location: dashboardMateria.php?id=$idAsignacion");
exit;
?>
