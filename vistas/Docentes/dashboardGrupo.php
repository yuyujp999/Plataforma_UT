<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/GrupoController.php";
include_once __DIR__ . "/../../controladores/docentes/AsistenciasController.php";

// 🔹 ID del grupo
$idGrupo = intval($_GET['id'] ?? 0);

// 🔹 ID del docente (desde sesión)
$idDocente = intval($_SESSION['id_docente'] ?? ($_SESSION['usuario']['id_docente'] ?? 0));

// 🔹 Info del grupo
$grupoInfo = GrupoController::obtenerInfoGrupo($idGrupo);

// 🔹 Alumnos del grupo
$alumnos = GrupoController::obtenerAlumnosPorGrupo($idGrupo);

// 🔹 Materias asignadas a este docente en este grupo
$materias = GrupoController::obtenerMateriasPorDocenteYGrupo($idDocente, $idGrupo);

// 🔹 Filtros asistencia (materia + fecha)
$selectedAsign = intval($_GET['id_asignacion_docente'] ?? 0);
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// 🔹 Mapa de asistencias del día
$mapaAsistencias = [];
$fechasGuardadas = [];
if ($idGrupo > 0 && $selectedAsign > 0) {
    // fechas que ya tienen asistencia
    $fechasGuardadas = AsistenciasController::obtenerFechasRegistradas($idGrupo, $selectedAsign);

    if ($fecha) {
        $mapaAsistencias = AsistenciasController::obtenerMapaDia($idGrupo, $selectedAsign, $fecha);
    }
}

// 🔹 Flash asistencia
$flashAsistencia = $_SESSION['flash_asistencia'] ?? '';
unset($_SESSION['flash_asistencia']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($grupoInfo['nombre_grupo'] ?? 'Grupo') ?> | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/dashboard_grupo.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
</head>
<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo">
        <h1>UT<span>Panel</span></h1>
      </div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
      </div>
    </div>

    <!-- CONTENIDO -->
    <div class="main-content">
      <!-- Barra superior del grupo -->
      <div class="materia-navbar">
        <div class="materia-info">
          <h2><i class="fa-solid fa-users"></i> <?= htmlspecialchars($grupoInfo['nombre_grupo'] ?? 'Grupo sin nombre') ?></h2>
          <p><strong>Semestre:</strong> <?= htmlspecialchars($grupoInfo['semestre'] ?? '—') ?></p>
        </div>
        <div class="user-info">
          <i class="fa-solid fa-user-tie"></i>
          <span><?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Docente') ?></span>
        </div>
      </div>

      <?php if (!empty($flashAsistencia)): ?>
        <div class="alert-message">
          <?= htmlspecialchars($flashAsistencia) ?>
        </div>
      <?php endif; ?>

      <!-- 📘 MATERIAS DE ESTE DOCENTE EN EL GRUPO -->
      <section class="materias-grupo-section">
        <h2><i class="fa-solid fa-book"></i> Materias asignadas en este grupo</h2>
        <?php if (!empty($materias)): ?>
          <div class="materias-grupo-grid">
            <?php foreach ($materias as $m): ?>
              <div class="materia-btn">
                <i class="fa-solid fa-book-open"></i>
                <div>
                  <div><?= htmlspecialchars($m['nombre_materia']) ?></div>
                  <small><?= htmlspecialchars($m['codigo_materia'] ?? '') ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="no-materias">No tienes materias asignadas en este grupo.</p>
        <?php endif; ?>
      </section>

      <!-- 🎓 ALUMNOS + ASISTENCIA -->
      <section class="alumnos-section">
        <h2><i class="fa-solid fa-user-graduate"></i> Lista de alumnos y asistencias</h2>

        <?php if (!empty($alumnos)): ?>

          <!-- Filtros para asistencia (materia + fecha) -->
          <form method="GET" class="asistencia-filtros" id="formFiltrosAsistencia">
            <input type="hidden" name="id" value="<?= (int)$idGrupo ?>">

            <label>
              Materia:
              <select name="id_asignacion_docente" id="selectMateria" required>
                <option value="">Selecciona…</option>
                <?php foreach ($materias as $m): ?>
                  <option value="<?= (int)$m['id_asignacion_docente'] ?>"
                    <?= $selectedAsign === (int)$m['id_asignacion_docente'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['nombre_materia']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              Fecha:
              <input type="date" name="fecha" id="inputFecha" value="<?= htmlspecialchars($fecha) ?>" required>
            </label>

            <?php if (!empty($fechasGuardadas)): ?>
              <label>
                Fechas con asistencia:
                <select id="selectFechaGuardada">
                  <option value="">Selecciona fecha…</option>
                  <?php foreach ($fechasGuardadas as $f): ?>
                    <?php
                      $selected = ($f === $fecha) ? 'selected' : '';
                      $label = date('d/m/Y', strtotime($f));
                    ?>
                    <option value="<?= htmlspecialchars($f) ?>" <?= $selected ?>>
                      <?= htmlspecialchars($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
            <?php endif; ?>

            <button type="submit" class="btn-subir">
              <i class="fa-solid fa-calendar-check"></i> Actualizar
            </button>
          </form>

          <?php if ($selectedAsign > 0): ?>
            <!-- Tabla + selects de asistencia -->
            <form method="POST" action="/Plataforma_UT/controladores/docentes/AsistenciasController.php">
              <input type="hidden" name="id_grupo" value="<?= (int)$idGrupo ?>">
              <input type="hidden" name="id_asignacion_docente" value="<?= (int)$selectedAsign ?>">
              <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">

              <table class="tabla-alumnos">
                <thead>
                  <tr>
                    <th>Matrícula</th>
                    <th>Nombre completo</th>
                    <th style="width:160px;">Asistencia (<?= htmlspecialchars($fecha) ?>)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($alumnos as $a): ?>
                    <?php
                      $idAl = (int)$a['id_alumno'];
                      $estadoActual = $mapaAsistencias[$idAl] ?? 'P'; // default Presente
                      $nombreCompleto = trim(
                        ($a['nombre'] ?? '') . ' ' .
                        ($a['apellido_paterno'] ?? '') . ' ' .
                        ($a['apellido_materno'] ?? '')
                      );
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($a['matricula']) ?></td>
                      <td><?= htmlspecialchars($nombreCompleto) ?></td>
                      <td>
                        <select name="estado[<?= $idAl ?>]" class="select-asistencia">
                          <option value="P" <?= $estadoActual === 'P' ? 'selected' : '' ?>>Presente</option>
                          <option value="A" <?= $estadoActual === 'A' ? 'selected' : '' ?>>Ausente</option>
                          <option value="J" <?= $estadoActual === 'J' ? 'selected' : '' ?>>Justificado</option>
                          <option value="R" <?= $estadoActual === 'R' ? 'selected' : '' ?>>Retardo</option>
                        </select>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

              <div class="asistencia-actions">
                <button type="submit" class="btn-subir">
                  <i class="fa-solid fa-save"></i> Guardar asistencia
                </button>
              </div>

              <p style="margin-top:8px; font-size:0.8rem; color:#6b7280;">
                <strong>Nota:</strong> P = Presente, A = Ausente, J = Justificado, R = Retardo.
              </p>
            </form>
          <?php else: ?>
            <p class="no-alumnos">
              Selecciona una materia para mostrar la lista de alumnos con asistencia del día.
            </p>
          <?php endif; ?>

        <?php else: ?>
          <p class="no-alumnos">No hay alumnos registrados en este grupo.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>

  <script>
    const formFiltros    = document.getElementById('formFiltrosAsistencia');
    const selMateria     = document.getElementById('selectMateria');
    const inputFecha     = document.getElementById('inputFecha');
    const selFechaGuard  = document.getElementById('selectFechaGuardada');

    // Auto-enviar al cambiar materia
    if (selMateria) {
      selMateria.addEventListener('change', () => {
        if (selMateria.value) {
          formFiltros.submit();
        }
      });
    }

    // Auto-enviar al cambiar fecha manual
    if (inputFecha) {
      inputFecha.addEventListener('change', () => {
        if (selMateria.value) {
          formFiltros.submit();
        }
      });
    }

    // Al elegir una fecha guardada se copia al input date y se envía
    if (selFechaGuard) {
      selFechaGuard.addEventListener('change', () => {
        const val = selFechaGuard.value;
        if (val) {
          inputFecha.value = val;
          formFiltros.submit();
        }
      });
    }
  </script>
</body>
</html>
