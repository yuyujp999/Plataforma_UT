<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/alumnos/ExamenesAlumnoController.php";

$idAlumno   = $_SESSION['usuario']['id_alumno'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'alumno';

$mensaje = $_SESSION['flash_examen'] ?? '';
unset($_SESSION['flash_examen']);

$examenes = ExamenesAlumnoController::obtenerExamenesDisponibles($idAlumno);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📘 Exámenes | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Alumnos/examenes.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
<div class="container">
  <aside class="sidebar" id="sidebar">
    <div class="logo"><h1>UT<span>Panel</span></h1></div>
    <nav class="nav-menu" id="menu"></nav>
  </aside>

  <main class="main-content">
    <?php if (!empty($mensaje)): ?>
      <div class="alert-message"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <header class="materia-header">
      <h2><i class="fa-solid fa-file-circle-question"></i> Exámenes disponibles</h2>
      <p>Exámenes activos asignados por tus docentes.</p>
    </header>

    <section class="section-content">
      <?php if (!empty($examenes)): ?>
        <div class="tareas-lista">
          <?php foreach ($examenes as $ex): ?>
            <div class="tarea-item">
              <div class="tarea-info">
                <h3><?= htmlspecialchars($ex['titulo']) ?></h3>
                <p><strong>Materia:</strong> <?= htmlspecialchars($ex['materia']) ?></p>
                <p>
                  <strong>Docente:</strong>
                  <?= htmlspecialchars($ex['nombre_docente'] . ' ' . $ex['apellido_docente']) ?>
                </p>
                <p>
                  <strong>Fecha de cierre:</strong>
                  <?= htmlspecialchars(date('d/m/Y', strtotime($ex['fecha_cierre']))) ?>
                </p>
                <?php if (!empty($ex['descripcion'])): ?>
                  <p><?= nl2br(htmlspecialchars($ex['descripcion'])) ?></p>
                <?php endif; ?>
              </div>
              <div class="tarea-actions">
                <a class="btn-ver"
                   href="/Plataforma_UT/vistas/Alumnos/examen_resolver.php?id_examen=<?= (int)$ex['id_examen'] ?>">
                  <i class="fa-solid fa-pen"></i> Presentar examen
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty">Por ahora no tienes exámenes activos.</p>
      <?php endif; ?>
    </section>
  </main>
</div>

<script>
  window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
