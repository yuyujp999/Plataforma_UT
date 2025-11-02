<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/RecursosController.php";

$id = intval($_GET['id'] ?? 0);
$asig = intval($_GET['asig'] ?? 0);
$recurso = RecursosController::obtenerRecurso($id);
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $titulo = $_POST["titulo"] ?? "";
  $descripcion = $_POST["descripcion"] ?? "";
  $archivo = $_FILES["archivo"] ?? null;

  $resultado = RecursosController::editarRecurso($id, $titulo, $descripcion, $archivo);
  $mensaje = $resultado["mensaje"];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Recurso | UT Panel</title>
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/recursos_docente.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>
  <div class="container-form">
    <h2><i class="fa-solid fa-pen"></i> Editar recurso</h2>

    <?php if (!empty($mensaje)): ?>
      <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label>Título del recurso:</label>
      <input type="text" name="titulo" value="<?= htmlspecialchars($recurso['titulo'] ?? '') ?>" required>

      <label>Descripción (opcional):</label>
      <textarea name="descripcion" rows="4"><?= htmlspecialchars($recurso['descripcion'] ?? '') ?></textarea>

      <label>Archivo (opcional):</label>
      <input type="file" name="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx">

      <?php if (!empty($recurso["archivo"])): ?>
        <p><a href="/Plataforma_UT/<?= htmlspecialchars($recurso["archivo"]) ?>" target="_blank">
          <i class="fa-solid fa-file"></i> Ver archivo actual
        </a></p>
      <?php endif; ?>

      <button type="submit" class="btn-guardar">
        <i class="fa-solid fa-save"></i> Guardar cambios
      </button>
    </form>

    <a href="dashboardMateria.php?id=<?= $asig ?>" class="btn-volver">
      <i class="fa-solid fa-arrow-left"></i> Volver a la materia
    </a>
  </div>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
