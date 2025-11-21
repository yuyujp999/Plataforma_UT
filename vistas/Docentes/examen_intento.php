<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/ExamenesController.php";

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'docente';

$idExamen  = isset($_GET['id_examen']) ? (int)$_GET['id_examen'] : 0;
$idAlumno  = isset($_GET['id_alumno']) ? (int)$_GET['id_alumno'] : 0;

if ($idExamen <= 0 || $idAlumno <= 0) {
  header('Location: /Plataforma_UT/vistas/Docentes/examenes_envios.php');
  exit;
}

$data = ExamenesController::obtenerResultadoExamenAlumno($idDocente, $idExamen, $idAlumno);
if (!$data) {
  header('Location: /Plataforma_UT/vistas/Docentes/examenes_envios.php');
  exit;
}

$ex    = $data['examen'];
$al    = $data['alumno'];
$stats = $data['stats'];
$pregs = $data['preguntas'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📊 Respuestas de examen | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Docentes/examenes.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
<div class="container">
  <aside class="sidebar" id="sidebar">
    <div class="logo"><h1>UT<span>Panel</span></h1></div>
    <nav class="nav-menu" id="menu"></nav>
  </aside>

  <main class="main-content">
    <header class="evaluaciones-header">
      <div>
        <h2><i class="fa-solid fa-clipboard-check"></i> <?= htmlspecialchars($ex['titulo']) ?></h2>
        <p>
          Materia: <?= htmlspecialchars($ex['materia']) ?><br>
          Alumno: <?= htmlspecialchars($al['matricula'] . ' - ' . $al['nombre'] . ' ' . $al['apellidos']) ?><br>
          <?php if ($stats['total_preguntas_opcion'] > 0): ?>
            Resultado opción múltiple:
            <strong><?= $stats['correctas'] ?>/<?= $stats['total_preguntas_opcion'] ?></strong>
            (<?= $stats['porcentaje'] ?>%)
          <?php else: ?>
            Este examen no tiene preguntas de opción múltiple calificables.
          <?php endif; ?>
        </p>
      </div>
      <a href="/Plataforma_UT/vistas/Docentes/examenes_envios.php" class="btn-subir">
        <i class="fa-solid fa-arrow-left"></i> Volver a envíos
      </a>
    </header>

    <section class="evaluaciones-section">
      <?php foreach ($pregs as $idx => $p): ?>
        <article class="pregunta-card-doc">
          <h3>Pregunta <?= $idx + 1 ?></h3>
          <p class="texto-pregunta"><?= nl2br(htmlspecialchars($p['pregunta'])) ?></p>

          <?php if ($p['tipo'] === 'opcion'): ?>
            <ul class="lista-opciones docente">
              <?php foreach ($p['opciones'] as $opt): ?>
                <?php
                  $esCorrecta  = ($opt['es_correcta'] == 1);
                  $esMarcada   = ($p['opcion_marcada'] === $opt['id_opcion']);
                  $clase = '';
                  $icono = '';

                  if ($esMarcada && $esCorrecta) {
                    $clase = 'opcion-correcta';
                    $icono = '<i class="fa-solid fa-check-circle"></i>';
                  } elseif ($esMarcada && !$esCorrecta) {
                    $clase = 'opcion-incorrecta';
                    $icono = '<i class="fa-solid fa-times-circle"></i>';
                  } elseif (!$esMarcada && $esCorrecta) {
                    $clase = 'opcion-correcta-no-marcada';
                    $icono = '<i class="fa-regular fa-circle-check"></i>';
                  }
                ?>
                <li class="<?= $clase ?>">
                  <?= $icono ?>
                  <?= htmlspecialchars($opt['texto_opcion']) ?>
                  <?php if ($esMarcada): ?>
                    <span class="badge-marcada">Marcada</span>
                  <?php endif; ?>
                  <?php if ($esCorrecta): ?>
                    <span class="badge-correcta">Correcta</span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="respuesta-abierta">
              <strong>Respuesta del alumno:</strong>
              <p><?= nl2br(htmlspecialchars($p['respuesta_texto'] ?? '—')) ?></p>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
  </main>
</div>

<script>
  window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>

<style>
  .pregunta-card-doc {
    background: var(--card-bg, #fff);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 14px;
    border: 1px solid #e5e7eb;
  }
  .pregunta-card-doc h3 {
    margin-top: 0;
    margin-bottom: 6px;
  }
  .lista-opciones.docente {
    list-style: none;
    padding-left: 0;
    margin: 8px 0 0;
  }
  .lista-opciones.docente li {
    padding: 6px 10px;
    border-radius: 6px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
  }
  .opcion-correcta {
    background: #dcfce7;
    border: 1px solid #22c55e;
  }
  .opcion-incorrecta {
    background: #fee2e2;
    border: 1px solid #ef4444;
  }
  .opcion-correcta-no-marcada {
    background: #e0f2fe;
    border: 1px solid #3b82f6;
  }
  .badge-marcada,
  .badge-correcta {
    margin-left: auto;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 999px;
    background: #111827;
    color: #fff;
  }
  .respuesta-abierta {
    margin-top: 8px;
    font-size: 0.9rem;
  }
</style>
</body>
</html>
