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
    'config'       => [
        'pct_tareas'    => 34.0,
        'pct_proyectos' => 33.0,
        'pct_examenes'  => 33.0,
    ],
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
$config       = $matriz['config'];

$pctT = (float)$config['pct_tareas'];
$pctP = (float)$config['pct_proyectos'];
$pctE = (float)$config['pct_examenes'];
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
        <p>
          Define el porcentaje de <strong>tareas, proyectos y exámenes</strong>
          y captura las calificaciones por alumno. El promedio final se calcula automáticamente en escala 0–10.
        </p>
      </div>
    </header>

    <section class="evaluaciones-section">
      <!-- Filtro de materia/grupo -->
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
        <!-- FORM PRINCIPAL: porcentajes + calificaciones -->
        <form method="POST">
          <!-- Porcentajes -->
          <div class="filtros-row" style="justify-content: flex-start; align-items:flex-end;">
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
              <div>
                <label style="font-size:0.85rem;">Tareas (%)<br>
                  <input type="number" step="0.1" min="0" max="100"
                         name="pct_tareas" value="<?= htmlspecialchars($pctT) ?>"
                         class="input-nota" style="width:80px;">
                </label>
              </div>
              <div>
                <label style="font-size:0.85rem;">Proyectos / Evaluaciones (%)<br>
                  <input type="number" step="0.1" min="0" max="100"
                         name="pct_proyectos" value="<?= htmlspecialchars($pctP) ?>"
                         class="input-nota" style="width:80px;">
                </label>
              </div>
              <div>
                <label style="font-size:0.85rem;">Exámenes (%)<br>
                  <input type="number" step="0.1" min="0" max="100"
                         name="pct_examenes" value="<?= htmlspecialchars($pctE) ?>"
                         class="input-nota" style="width:80px;">
                </label>
              </div>
              <div style="align-self:center; font-size:0.8rem; color:#6b7280;">
                Sugerido: que la suma sea 100%.
              </div>
            </div>
          </div>

          <!-- TABLA -->
          <div class="tabla-wrapper">
            <table class="calif">
              <thead>
              <tr>
                <th rowspan="2" class="sticky">Alumno</th>

                <?php if ($tareas): ?>
                  <th colspan="<?= count($tareas) ?>" class="section-title">Tareas</th>
                <?php endif; ?>

                <?php if ($evaluaciones): ?>
                  <th colspan="<?= count($evaluaciones) ?>" class="section-title">Proyectos / Evaluaciones</th>
                <?php endif; ?>

                <?php if ($examenes): ?>
                  <th colspan="<?= count($examenes) ?>" class="section-title">Exámenes</th>
                <?php endif; ?>

                <!-- columnas de promedio -->
                <th colspan="4" class="section-title">Promedios</th>
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

                <th class="subheader">Prom. tareas</th>
                <th class="subheader">Prom. proyectos</th>
                <th class="subheader">Prom. exámenes</th>
                <th class="subheader">Final</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($alumnos as $al): ?>
                <?php
                  $idAl = (int)$al['id_alumno'];
                  $nombreAl = trim(($al['apellido_paterno'] ?? '').' '.($al['apellido_materno'] ?? '').' '.$al['nombre']);

                  // acumuladores para promedios
                  $sumT = 0; $countT = 0;
                  $sumP = 0; $countP = 0;
                  $sumE = 0; $countE = 0;
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
                        if ($val !== '' && $val !== null) {
                            $sumT += (float)$val;
                            $countT++;
                        }
                      ?>
                      <td>
                        <input type="number" step="0.01" min="0" max="10"
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
                        if ($val !== '' && $val !== null) {
                            $sumP += (float)$val;
                            $countP++;
                        }
                      ?>
                      <td>
                        <input type="number" step="0.01" min="0" max="10"
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
                        if ($val !== '' && $val !== null) {
                            $sumE += (float)$val;
                            $countE++;
                        }
                      ?>
                      <td>
                        <input type="number" step="0.01" min="0" max="10"
                               class="input-nota"
                               name="nota_examen[<?= $idAl ?>][<?= $idEx ?>]"
                               value="<?= htmlspecialchars($val) ?>">
                      </td>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php
                    // Promedios simples por tipo (0–10)
                    $promT = $countT > 0 ? $sumT / $countT : null;
                    $promP = $countP > 0 ? $sumP / $countP : null;
                    $promE = $countE > 0 ? $sumE / $countE : null;

                    // Promedio final ponderado en escala 0–10
                    $totalPeso     = 0;
                    $sumaPonderada = 0;

                    if ($promT !== null) {
                        $sumaPonderada += $promT * $pctT;
                        $totalPeso     += $pctT;
                    }
                    if ($promP !== null) {
                        $sumaPonderada += $promP * $pctP;
                        $totalPeso     += $pctP;
                    }
                    if ($promE !== null) {
                        $sumaPonderada += $promE * $pctE;
                        $totalPeso     += $pctE;
                    }

                    $final = ($totalPeso > 0)
                        ? $sumaPonderada / $totalPeso
                        : null;
                  ?>

                  <td style="text-align:center;">
                    <?= $promT !== null ? number_format($promT, 1) : '—' ?>
                  </td>
                  <td style="text-align:center;">
                    <?= $promP !== null ? number_format($promP, 1) : '—' ?>
                  </td>
                  <td style="text-align:center;">
                    <?= $promE !== null ? number_format($promE, 1) : '—' ?>
                  </td>
                  <td style="text-align:center; font-weight:600;">
                    <?= $final !== null ? number_format($final, 1) : '—' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="calif-actions">
            <button type="submit" class="btn-subir">
              <i class="fa-solid fa-save"></i> Guardar porcentajes y calificaciones
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
