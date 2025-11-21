<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/alumnos/ExamenesAlumnoController.php";

$idAlumno   = $_SESSION['usuario']['id_alumno'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'alumno';

$idExamen = isset($_GET['id_examen'])
  ? (int)$_GET['id_examen']
  : (int)($_POST['id_examen'] ?? 0);

if ($idExamen <= 0) {
  header('Location: /Plataforma_UT/vistas/Alumnos/examenesAlumno.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_examen'])) {
  $resultado = ExamenesAlumnoController::guardarRespuestas($idExamen, $idAlumno, $_POST);
  $_SESSION['flash_examen'] = $resultado['mensaje'] ?? '✅ Examen enviado.';
  header('Location: /Plataforma_UT/vistas/Alumnos/examenesAlumno.php');
  exit;
}

$mensaje = ''; // ya no usamos mensaje local

$examen = ExamenesAlumnoController::obtenerExamenConPreguntas($idExamen);
if (!$examen) {
  $_SESSION['flash_examen'] = "❌ Examen no encontrado.";
  header('Location: /Plataforma_UT/vistas/Alumnos/examenesAlumno.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>✏️ Resolver examen | UT Panel</title>
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
      <h2><i class="fa-solid fa-pen"></i> <?= htmlspecialchars($examen['titulo']) ?></h2>
      <p>
        <?= htmlspecialchars($examen['materia']) ?> ·
        Docente:
        <?php
          $nombreDoc = trim(
            ($examen['nombre_docente'] ?? '') . ' ' .
            ($examen['apellido_docente'] ?? '')
          );
          echo htmlspecialchars($nombreDoc);
        ?><br>
        Cierre: <?= htmlspecialchars(date('d/m/Y', strtotime($examen['fecha_cierre']))) ?>
      </p>
      <a href="/Plataforma_UT/vistas/Alumnos/examenesAlumno.php" class="btn-volver">
        <i class="fa-solid fa-arrow-left"></i> Volver a exámenes
      </a>
    </header>

    <form method="POST" class="form-examen">
      <input type="hidden" name="id_examen" value="<?= (int)$idExamen ?>">

      <?php if (!empty($examen['preguntas'])): ?>
        <?php foreach ($examen['preguntas'] as $index => $p): ?>
          <div class="pregunta-resolver">
            <h3>Pregunta <?= $index + 1 ?></h3>
            <p class="texto-pregunta"><?= nl2br(htmlspecialchars($p['pregunta'])) ?></p>

            <?php if ($p['tipo'] === 'abierta'): ?>
              <textarea
                name="resp_abierta[<?= (int)$p['id_pregunta'] ?>]"
                rows="3"
                placeholder="Escribe tu respuesta aquí..."></textarea>
            <?php else: ?>
              <?php if (!empty($p['opciones'])): ?>
                <ul class="lista-opciones alumno">
                  <?php foreach ($p['opciones'] as $opt): ?>
                    <li>
                      <label>
                        <input type="radio"
                               name="resp_opcion[<?= (int)$p['id_pregunta'] ?>]"
                               value="<?= (int)$opt['id_opcion'] ?>">
                        <?= htmlspecialchars($opt['texto']) ?>
                      </label>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="empty">
                  Esta pregunta de opción múltiple no tiene opciones configuradas.
                </p>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="empty">Este examen aún no tiene preguntas.</p>
      <?php endif; ?>

      <button type="submit" name="enviar_examen" class="btn-primary full">
        <i class="fa-solid fa-paper-plane"></i> Enviar examen
      </button>
    </form>
  </main>
</div>

<script>
  window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
