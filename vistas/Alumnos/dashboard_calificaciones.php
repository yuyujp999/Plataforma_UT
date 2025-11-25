<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";

$idAlumno    = $_SESSION['usuario']['id_alumno'] ?? 0;
$rolUsuario  = $_SESSION['rol'] ?? 'alumno';
$usuario     = $_SESSION['usuario'] ?? [];
$nombre      = $usuario['nombre'] ?? 'Alumno';
$apPaterno   = $usuario['apellido_paterno'] ?? '';
$nombreCompleto = trim($nombre . ' ' . $apPaterno);

/* ==========================================
   1. OBTENER MATERIAS / GRUPOS CON CALIFICACIONES
   ========================================== */
$sqlMaterias = "
    SELECT DISTINCT
        ad.id_asignacion_docente,
        m.nombre_materia,
        CONCAT(d.nombre, ' ', d.apellido_paterno) AS nombre_docente
    FROM asignaciones_docentes ad
    INNER JOIN asignar_materias am
        ON ad.id_nombre_materia = am.id_nombre_materia
    INNER JOIN materias m
        ON am.id_materia = m.id_materia
    INNER JOIN docentes d
        ON ad.id_docente = d.id_docente
    WHERE ad.id_asignacion_docente IN (
        -- TAREAS
        SELECT t.id_asignacion_docente
        FROM tareas_materias t
        INNER JOIN entregas_alumnos ea
            ON ea.id_tarea = t.id_tarea
        WHERE ea.id_alumno = ?

        UNION

        -- PROYECTOS / EVALUACIONES
        SELECT ev.id_asignacion_docente
        FROM evaluaciones_docente ev
        INNER JOIN entregas_evaluaciones_alumnos ee
            ON ee.id_evaluacion = ev.id_evaluacion
        WHERE ee.id_alumno = ?

        UNION

        -- EXÁMENES
        SELECT ex.id_asignacion_docente
        FROM examenes ex
        INNER JOIN examen_calificaciones ec
            ON ec.id_examen = ex.id_examen
        WHERE ec.id_alumno = ?

        UNION

        -- ASISTENCIA
        SELECT ca.id_asignacion_docente
        FROM calificaciones_asistencia ca
        WHERE ca.id_alumno = ?
    )
    ORDER BY m.nombre_materia ASC
";
$stmtMaterias = $conn->prepare($sqlMaterias);
$stmtMaterias->bind_param("iiii", $idAlumno, $idAlumno, $idAlumno, $idAlumno);
$stmtMaterias->execute();
$resMaterias = $stmtMaterias->get_result();
$materias    = $resMaterias->fetch_all(MYSQLI_ASSOC);

/* Asignación seleccionada (o la primera disponible) */
$selectedAsign = isset($_GET['id_asignacion_docente'])
    ? (int)$_GET['id_asignacion_docente']
    : 0;

if ($selectedAsign === 0 && !empty($materias)) {
    $selectedAsign = (int)$materias[0]['id_asignacion_docente'];
}

/* ==========================================
   2. FUNCIÓN PARA OBTENER CONFIGURACIÓN (PORCENTAJES)
   MISMO ORIGEN QUE EL DASH DE DOCENTE: calif_config
   ========================================== */
function obtenerConfig(mysqli $conn, int $idAsign): array {
    // Igual que en el dash de docente: usar el último updated_at
    $sql = "
        SELECT pct_tareas, pct_proyectos, pct_examenes
        FROM calif_config
        WHERE id_asignacion_docente = ?
        ORDER BY updated_at DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $idAsign);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $pctT = isset($row['pct_tareas'])     ? (float)$row['pct_tareas']     : 34.0;
            $pctP = isset($row['pct_proyectos']) ? (float)$row['pct_proyectos'] : 33.0;
            $pctE = isset($row['pct_examenes'])  ? (float)$row['pct_examenes']  : 33.0;
            return [$pctT, $pctP, $pctE];
        }
    }
    // Valores por defecto (igual que en el controlador)
    return [34.0, 33.0, 33.0];
}

/* ==========================================
   3. OBTENER DETALLE DE LA ASIGNACIÓN SELECCIONADA
   ========================================== */
$infoAsign   = null;
$tareas      = [];
$evaluaciones= [];
$examenes    = [];
$notaAsist   = null;
$promT = $promP = $promE = null;
$final = null;
$pctT = $pctP = $pctE = 0.0;

if ($selectedAsign > 0) {
    // Info básica de la asignación (materia + docente)
    foreach ($materias as $m) {
        if ((int)$m['id_asignacion_docente'] === $selectedAsign) {
            $infoAsign = $m;
            break;
        }
    }

    // Configuración de porcentajes (misma fuente que el docente)
    list($pctT, $pctP, $pctE) = obtenerConfig($conn, $selectedAsign);

    /* ----- TAREAS ----- */
    $sqlT = "
        SELECT 
            t.id_tarea,
            t.titulo,
            t.fecha_entrega,
            ea.calificacion
        FROM tareas_materias t
        LEFT JOIN entregas_alumnos ea
            ON ea.id_tarea = t.id_tarea
           AND ea.id_alumno = ?
        WHERE t.id_asignacion_docente = ?
        ORDER BY t.fecha_entrega ASC, t.id_tarea ASC
    ";
    $stmtT = $conn->prepare($sqlT);
    $stmtT->bind_param("ii", $idAlumno, $selectedAsign);
    $stmtT->execute();
    $resT   = $stmtT->get_result();
    $tareas = $resT->fetch_all(MYSQLI_ASSOC);

    /* ----- EVALUACIONES / PROYECTOS ----- */
    $sqlEvals = "
        SELECT 
            ev.id_evaluacion,
            ev.titulo,
            ev.tipo,
            ee.calificacion
        FROM evaluaciones_docente ev
        LEFT JOIN entregas_evaluaciones_alumnos ee
            ON ee.id_evaluacion = ev.id_evaluacion
           AND ee.id_alumno = ?
        WHERE ev.id_asignacion_docente = ?
        ORDER BY ev.fecha_cierre ASC, ev.id_evaluacion ASC
    ";
    $stmtEvals = $conn->prepare($sqlEvals);
    $stmtEvals->bind_param("ii", $idAlumno, $selectedAsign);
    $stmtEvals->execute();
    $resEvals      = $stmtEvals->get_result();
    $evaluaciones  = $resEvals->fetch_all(MYSQLI_ASSOC);

    /* ----- EXÁMENES ----- */
    $sqlEx = "
        SELECT 
            ex.id_examen,
            ex.titulo,
            ec.calificacion
        FROM examenes ex
        LEFT JOIN examen_calificaciones ec
            ON ec.id_examen = ex.id_examen
           AND ec.id_alumno = ?
        WHERE ex.id_asignacion_docente = ?
        ORDER BY ex.fecha_cierre ASC, ex.id_examen ASC
    ";
    $stmtEx = $conn->prepare($sqlEx);
    $stmtEx->bind_param("ii", $idAlumno, $selectedAsign);
    $stmtEx->execute();
    $resEx   = $stmtEx->get_result();
    $examenes= $resEx->fetch_all(MYSQLI_ASSOC);

    /* ----- ASISTENCIA (solo mostrar puntos) ----- */
    $sqlAsist = "
        SELECT puntos_asistencia
        FROM calificaciones_asistencia
        WHERE id_asignacion_docente = ? AND id_alumno = ?
        LIMIT 1
    ";
    $stmtA = $conn->prepare($sqlAsist);
    $stmtA->bind_param("ii", $selectedAsign, $idAlumno);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    if ($rowA = $resA->fetch_assoc()) {
        $notaAsist = (float)$rowA['puntos_asistencia']; // 0–10
    }

    /* ==========================================
       4. CÁLCULO DE PROMEDIOS Y FINAL
       (MISMA LÓGICA QUE EN EL DASH DE DOCENTE)
       ========================================== */
    // Tareas
    $sumT=0; $countT=0;
    foreach ($tareas as $t) {
        if ($t['calificacion'] !== null) {
            $sumT += (float)$t['calificacion'];
            $countT++;
        }
    }
    if ($countT > 0) {
        $promT = $sumT / $countT;
    }

    // Evaluaciones / proyectos
    $sumP=0; $countP=0;
    foreach ($evaluaciones as $e) {
        if ($e['calificacion'] !== null) {
            $sumP += (float)$e['calificacion'];
            $countP++;
        }
    }
    if ($countP > 0) {
        $promP = $sumP / $countP;
    }

    // Exámenes
    $sumE=0; $countE=0;
    foreach ($examenes as $ex) {
        if ($ex['calificacion'] !== null) {
            $sumE += (float)$ex['calificacion'];
            $countE++;
        }
    }
    if ($countE > 0) {
        $promE = $sumE / $countE;
    }

    // Promedio final ponderado 0–10 (tareas + proyectos + exámenes)
    // **igual que el docente**: solo se usan estos 3 porcentajes
    $totalPeso     = 0.0;
    $sumaPonderada = 0.0;

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

    if ($totalPeso > 0) {
        $final = $sumaPonderada / $totalPeso;
    }
}

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>⭐ Mis Calificaciones | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <style>
    .calif-main-header{
      display:flex;justify-content:space-between;align-items:center;
      margin-bottom:20px;
    }
    .calif-main-header h2{
      font-size:1.4rem;color:#064e3b;margin-bottom:4px;
    }
    .calif-card{
      background:#f9fafb;border-radius:16px;padding:20px;
      box-shadow:0 6px 12px rgba(15,23,42,0.08);margin-bottom:20px;
    }
    .calif-card h3{
      margin-top:0;font-size:1.1rem;color:#065f46;margin-bottom:10px;
      display:flex;align-items:center;gap:8px;
    }
    .calif-card h3 i{color:#059669;}
    .calif-select-row{
      display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-bottom:10px;
    }
    .calif-select-row label{
      font-size:.85rem;color:#4b5563;display:flex;flex-direction:column;gap:4px;
    }
    .calif-select-row select{
      padding:6px 10px;border-radius:999px;border:1px solid #d1d5db;
      background:#fff;min-width:260px;
    }
    .pct-badges{
      display:flex;flex-wrap:wrap;gap:8px;font-size:.8rem;color:#374151;
    }
    .pct-pill{
      background:#ecfdf5;border-radius:999px;padding:4px 10px;
      border:1px solid #6ee7b7;
    }
    .tabla-actividades{
      width:100%;border-collapse:collapse;background:#fff;border-radius:12px;
      overflow:hidden;margin-top:10px;
    }
    .tabla-actividades thead{
      background:#059669;color:#fff;
    }
    .tabla-actividades th,.tabla-actividades td{
      padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:.9rem;
    }
    .tabla-actividades tbody tr:nth-child(even){
      background:#f9fafb;
    }
    .tag-tipo{
      display:inline-flex;align-items:center;gap:4px;
      padding:2px 8px;border-radius:999px;font-size:.75rem;
      background:#eff6ff;color:#1d4ed8;
    }
    .resumen-grid{
      display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));
      gap:10px;margin-top:8px;
    }
    .resumen-item{
      background:#fff;border-radius:12px;padding:10px 12px;
      border:1px solid #e5e7eb;font-size:.85rem;
    }
    .resumen-label{color:#6b7280;font-size:.78rem;}
    .resumen-value{font-size:1.1rem;font-weight:600;color:#111827;}
    .resumen-final{
      background:#ecfdf5;border-color:#6ee7b7;
    }
  </style>
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
<div class="container">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="logo"><h1>UT<span>Panel</span></h1></div>
    <nav class="nav-menu" id="menu"></nav>
  </aside>

  <main class="main-content">
    <header class="calif-main-header">
      <div>
        <h2><i class="fa-solid fa-star"></i> Mis Calificaciones</h2>
        <p>Hola <?= esc($nombreCompleto) ?>, aquí puedes ver lo que tus docentes han capturado en el módulo de calificaciones.</p>
      </div>
    </header>

    <section class="calif-card">
      <h3><i class="fa-solid fa-book-open"></i> Materia / grupo</h3>

      <form method="GET" class="calif-select-row">
        <label>
          Materia:
          <select name="id_asignacion_docente" onchange="this.form.submit()">
            <?php if (empty($materias)): ?>
              <option value="0">No tienes calificaciones registradas aún</option>
            <?php else: ?>
              <?php foreach ($materias as $m): ?>
                <option value="<?= (int)$m['id_asignacion_docente'] ?>"
                  <?= $selectedAsign === (int)$m['id_asignacion_docente'] ? 'selected' : '' ?>>
                  <?= esc($m['nombre_materia']) ?> — <?= esc($m['nombre_docente']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </label>
      </form>

      <?php if ($selectedAsign > 0 && $infoAsign): ?>
        <div class="pct-badges">
          <span class="pct-pill"><strong>Tareas:</strong> <?= number_format($pctT, 1) ?>%</span>
          <span class="pct-pill"><strong>Proyectos / evaluaciones:</strong> <?= number_format($pctP, 1) ?>%</span>
          <span class="pct-pill"><strong>Exámenes:</strong> <?= number_format($pctE, 1) ?>%</span>
          <span style="font-size:.8rem;color:#6b7280;">(Los porcentajes los define tu docente en su dash)</span>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($selectedAsign > 0 && $infoAsign): ?>
      <!-- Tabla de actividades -->
      <section class="calif-card">
        <h3><i class="fa-solid fa-table-list"></i> Detalle de actividades</h3>

        <table class="tabla-actividades">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Actividad</th>
              <th>Fecha / descripción</th>
              <th style="text-align:center;">Calificación</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $hayFilas = false;

          // Tareas
          foreach ($tareas as $t):
              $hayFilas = true;
              $nota = $t['calificacion'] !== null ? number_format((float)$t['calificacion'], 1) : '—';
          ?>
            <tr>
              <td><span class="tag-tipo"><i class="fa-solid fa-list-check"></i> Tarea</span></td>
              <td><?= esc($t['titulo']) ?></td>
              <td><?= esc($t['fecha_entrega'] ?? '') ?></td>
              <td style="text-align:center;"><?= $nota ?></td>
            </tr>
          <?php endforeach; ?>

          <!-- Evaluaciones / proyectos -->
          <?php foreach ($evaluaciones as $e):
              $hayFilas = true;
              $nota = $e['calificacion'] !== null ? number_format((float)$e['calificacion'], 1) : '—';
              $tipo = $e['tipo'] ?? 'Proyecto / Eval.';
          ?>
            <tr>
              <td><span class="tag-tipo"><i class="fa-solid fa-diagram-project"></i> <?= esc($tipo) ?></span></td>
              <td><?= esc($e['titulo']) ?></td>
              <td><!-- Podrías mostrar fecha si la agregas --></td>
              <td style="text-align:center;"><?= $nota ?></td>
            </tr>
          <?php endforeach; ?>

          <!-- Exámenes -->
          <?php foreach ($examenes as $ex):
              $hayFilas = true;
              $nota = $ex['calificacion'] !== null ? number_format((float)$ex['calificacion'], 1) : '—';
          ?>
            <tr>
              <td><span class="tag-tipo"><i class="fa-solid fa-file-circle-question"></i> Examen</span></td>
              <td><?= esc($ex['titulo']) ?></td>
              <td><!-- Fecha de cierre si la necesitas --></td>
              <td style="text-align:center;"><?= $nota ?></td>
            </tr>
          <?php endforeach; ?>

          <!-- Asistencia (solo mostrar puntos) -->
          <?php if ($notaAsist !== null):
              $hayFilas = true;
          ?>
            <tr>
              <td><span class="tag-tipo"><i class="fa-solid fa-user-check"></i> Asistencia</span></td>
              <td>Puntos de asistencia</td>
              <td>Definidos por tu docente</td>
              <td style="text-align:center;"><?= number_format($notaAsist, 1) ?></td>
            </tr>
          <?php endif; ?>

          <?php if (!$hayFilas): ?>
            <tr>
              <td colspan="4" style="text-align:center; padding:16px; color:#6b7280;">
                Aún no hay calificaciones registradas en esta materia.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>

        <!-- Resumen de promedios -->
        <div class="resumen-grid">
          <div class="resumen-item">
            <div class="resumen-label">Promedio de tareas</div>
            <div class="resumen-value"><?= $promT !== null ? number_format($promT, 1) : '—' ?></div>
          </div>
          <div class="resumen-item">
            <div class="resumen-label">Promedio de proyectos / evaluaciones</div>
            <div class="resumen-value"><?= $promP !== null ? number_format($promP, 1) : '—' ?></div>
          </div>
          <div class="resumen-item">
            <div class="resumen-label">Promedio de exámenes</div>
            <div class="resumen-value"><?= $promE !== null ? number_format($promE, 1) : '—' ?></div>
          </div>
          <div class="resumen-item resumen-final">
            <div class="resumen-label">Promedio final ponderado</div>
            <div class="resumen-value"><?= $final !== null ? number_format($final, 1) : '—' ?></div>
          </div>
        </div>
      </section>
    <?php elseif (!empty($materias)): ?>
      <section class="calif-card">
        <p>No se encontró información de la materia seleccionada.</p>
      </section>
    <?php else: ?>
      <section class="calif-card">
        <p>Por ahora no tienes calificaciones registradas en el sistema.</p>
      </section>
    <?php endif; ?>

  </main>
</div>

<script>
  window.rolUsuarioPHP = "<?= esc($rolUsuario); ?>";
</script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
