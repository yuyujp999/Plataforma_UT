<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/CalificacionesController.php";

$idDocente  = $_SESSION['usuario']['id_docente'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'docente';

$asignaciones = CalificacionesController::obtenerAsignacionesDocente($idDocente);

$selectedAsign = isset($_GET['id_asignacion_docente'])
  ? (int)$_GET['id_asignacion_docente']
  : 0;

$flash = $_SESSION['flash_calificaciones'] ?? '';
unset($_SESSION['flash_calificaciones']);

/* Si viene POST, guardar y recargar misma asignación */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedAsign > 0) {
    $res = CalificacionesController::guardar($idDocente, $selectedAsign, $_POST);
    $_SESSION['flash_calificaciones'] = $res['mensaje'] ?? '';
    header("Location: /Plataforma_UT/vistas/Docentes/calificaciones.php?id_asignacion_docente=".$selectedAsign);
    exit;
}

/* Matriz vacía por defecto */
$matriz = [
    'alumnos'      => [],
    'tareas'       => [],
    'evaluaciones' => [],
    'examenes'     => [],
    'cal_tareas'   => [],
    'cal_evals'    => [],
    'cal_exams'    => [],
];

if ($selectedAsign > 0) {
    $matriz = CalificacionesController::obtenerMatriz($idDocente, $selectedAsign);
}

$alumnos      = $matriz['alumnos'];
$tareas       = $matriz['tareas'];
$evaluaciones = $matriz['evaluaciones'];
$examenes     = $matriz['examenes'];
$calT         = $matriz['cal_tareas'];
$calEv        = $matriz['cal_evals'];
$calEx        = $matriz['cal_exams'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📊 Calificaciones | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Docentes/calificaciones.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
<div class="container">
  <aside class="sidebar" id="sidebar">
    <div class="logo"><h1>UT<span>Panel</span></h1></div>
    <nav class="nav-menu" id="menu"></nav>
  </aside>

  <main class="main-content calif-page">
    <?php if (!empty($flash)): ?>
      <div class="alert-message"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <header class="evaluaciones-header">
      <div class="calif-header-text">
        <h2><i class="fa-solid fa-table"></i> Calificaciones</h2>
        <p>Resumen de calificaciones por alumno y por actividad (tareas, proyectos, exámenes).</p>
      </div>
    </header>

    <section class="evaluaciones-section">
      <form method="GET" class="filtros-row">
        <label>
          Materia / grupo:
          <select name="id_asignacion_docente" onchange="this.form.submit()">
            <option value="0">Selecciona…</option>
            <?php foreach ($asignaciones as $as): ?>
              <option value="<?= (int)$as['id_asignacion_docente'] ?>"
                <?= $selectedAsign === (int)$as['id_asignacion_docente'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($as['materia']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </form>

      <?php if ($selectedAsign > 0 && !empty($alumnos)): ?>
        <form method="POST">
          <div class="tabla-wrapper">
            <table class="calif">
              <thead>
              <tr>
                <th rowspan="2" class="sticky">Alumno</th>

                <?php if ($tareas): ?>
                  <th colspan="<?= count($tareas) ?>" class="section-title">Tareas</th>
                <?php endif; ?>

                <?php if ($evaluaciones): ?>
                  <th colspan="<?= count($evaluaciones) ?>" class="section-title">Evaluaciones / Proyectos</th>
                <?php endif; ?>

                <?php if ($examenes): ?>
                  <th colspan="<?= count($examenes) ?>" class="section-title">Exámenes</th>
                <?php endif; ?>
              </tr>
              <tr>
                <?php if ($tareas): ?>
                  <?php foreach ($tareas as $t): ?>
                    <th class="subheader"><?= htmlspecialchars($t['titulo']) ?></th>
                  <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($evaluaciones): ?>
                  <?php foreach ($evaluaciones as $e): ?>
                    <th class="subheader"><?= htmlspecialchars($e['titulo']) ?></th>
                  <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($examenes): ?>
                  <?php foreach ($examenes as $ex): ?>
                    <th class="subheader"><?= htmlspecialchars($ex['titulo']) ?></th>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($alumnos as $al): ?>
                <?php
                  $idAl = (int)$al['id_alumno'];
                  $nombreAl = trim(($al['apellido_paterno'] ?? '').' '.($al['apellido_materno'] ?? '').' '.$al['nombre']);
                ?>
                <tr>
                  <td class="sticky">
                    <div class="alumno-cell">
                      <strong><?= htmlspecialchars($nombreAl) ?></strong>
                      <small><?= htmlspecialchars($al['matricula'] ?? '') ?></small>
                    </div>
                  </td>

                  <?php if ($tareas): ?>
                    <?php foreach ($tareas as $t): ?>
                      <?php
                        $idT = (int)$t['id_tarea'];
                        $val = $calT[$idAl][$idT] ?? '';
                      ?>
                      <td>
                        <input type="number" step="0.01" min="0" max="100"
                               class="input-nota"
                               name="nota_tarea[<?= $idAl ?>][<?= $idT ?>]"
                               value="<?= htmlspecialchars($val) ?>">
                      </td>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if ($evaluaciones): ?>
                    <?php foreach ($evaluaciones as $e): ?>
                      <?php
                        $idEv = (int)$e['id_evaluacion'];
                        $val = $calEv[$idAl][$idEv] ?? '';
                      ?>
                      <td>
                        <input type="number" step="0.01" min="0" max="100"
                               class="input-nota"
                               name="nota_eval[<?= $idAl ?>][<?= $idEv ?>]"
                               value="<?= htmlspecialchars($val) ?>">
                      </td>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if ($examenes): ?>
                    <?php foreach ($examenes as $ex): ?>
                      <?php
                        $idEx = (int)$ex['id_examen'];
                        $val  = $calEx[$idAl][$idEx] ?? '';
                      ?>
                      <td>
                        <input type="number" step="0.01" min="0" max="100"
                               class="input-nota"
                               name="nota_examen[<?= $idAl ?>][<?= $idEx ?>]"
                               value="<?= htmlspecialchars($val) ?>">
                      </td>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="calif-actions">
            <button type="submit" class="btn-subir">
              <i class="fa-solid fa-save"></i> Guardar calificaciones
            </button>
          </div>
        </form>
      <?php elseif ($selectedAsign > 0): ?>
        <div class="empty">
          <i class="fa-solid fa-user-slash"></i>
          No se encontraron alumnos asignados a esta materia/grupo todavía.
        </div>
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
