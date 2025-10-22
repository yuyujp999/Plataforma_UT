<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/TareasController.php";

$idTarea = intval($_GET['id'] ?? 0);
$tarea = TareasController::obtenerTarea($idTarea);
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $titulo = $_POST['titulo'] ?? '';
  $descripcion = $_POST['descripcion'] ?? '';
  $fecha_entrega = $_POST['fecha_entrega'] ?? null;
  $archivo = $_FILES['archivo'] ?? null;

  $resultado = TareasController::editarTarea($idTarea, $titulo, $descripcion, $fecha_entrega, $archivo);
  $mensaje = $resultado["mensaje"];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Tarea | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/materia_docente.css">
  <link rel="stylesheet" href="../../css/docentes/editar_tarea.css">
</head>
<body>
  <div class="container">
    <div class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu"><div class="menu-heading">Menú</div></div>
    </div>

    <div class="main-content">
      <a href="dashboardMateria.php?id=<?= $tarea['id_asignacion_docente'] ?>" class="btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Volver
      </a>

      <div class="form-card">
        <h2><i class="fa-solid fa-pen-to-square"></i> Editar tarea</h2>

        <?php if ($mensaje): ?>
          <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($mensaje) ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="upload-form">
          <label for="titulo">Título:</label>
          <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($tarea['titulo']) ?>" required>

          <label for="descripcion">Descripción:</label>
          <textarea id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($tarea['descripcion']) ?></textarea>

          <label for="fecha_entrega">Fecha de entrega:</label>
          <input type="date" id="fecha_entrega" name="fecha_entrega" value="<?= htmlspecialchars($tarea['fecha_entrega']) ?>">

          <label for="archivo">Reemplazar archivo:</label>
          <input type="file" id="archivo" name="archivo" accept=".pdf,.doc,.docx,.zip,.rar">
          <?php if (!empty($tarea['archivo'])): ?>
            <p><a href="/Plataforma_UT/<?= htmlspecialchars($tarea['archivo']) ?>" target="_blank">
              <i class="fa-solid fa-file"></i> Ver archivo actual</a></p>
          <?php endif; ?>

          <button type="submit" class="btn">
            <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
