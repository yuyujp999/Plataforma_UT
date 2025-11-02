<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/TareasController.php";

$idAsignacion = intval($_GET['id'] ?? 0);
$mensaje = "";

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $titulo = $_POST['titulo'] ?? '';
  $descripcion = $_POST['descripcion'] ?? '';
  $fecha_entrega = $_POST['fecha_entrega'] ?? null;
  $archivo = $_FILES['archivo'] ?? [];

  $resultado = TareasController::subirTarea($idAsignacion, $titulo, $descripcion, $fecha_entrega, $archivo);
  $mensaje = $resultado["mensaje"];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Subir Tarea | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/materia_docente.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
</head>
<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
      </div>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="main-content">
      <a href="dashboardMateria.php?id=<?= $idAsignacion ?>" class="btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Volver
      </a>

      <div class="form-card">
        <h2><i class="fa-solid fa-upload"></i> Subir nueva tarea</h2>

        <?php if ($mensaje): ?>
          <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($mensaje) ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="upload-form">
          <div class="form-group">
            <label for="titulo"><i class="fa-solid fa-heading"></i> Título de la tarea</label>
            <input type="text" id="titulo" name="titulo" required placeholder="Ejemplo: Investigación sobre energías renovables">
          </div>

          <div class="form-group">
            <label for="descripcion"><i class="fa-solid fa-align-left"></i> Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4" placeholder="Describe brevemente las instrucciones para tus alumnos..."></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="fecha_entrega"><i class="fa-solid fa-calendar-days"></i> Fecha de entrega</label>
              <input type="date" id="fecha_entrega" name="fecha_entrega">
            </div>

            <div class="form-group">
              <label for="archivo"><i class="fa-solid fa-paperclip"></i> Archivo adjunto</label>
              <input type="file" id="archivo" name="archivo" accept=".pdf,.doc,.docx,.zip,.rar">
            </div>
          </div>

          <button type="submit" class="btn-submit">
            <i class="fa-solid fa-paper-plane"></i> Publicar tarea
          </button>
        </form>
      </div>
    </div>
  </div>
<script>
  window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>

<script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
