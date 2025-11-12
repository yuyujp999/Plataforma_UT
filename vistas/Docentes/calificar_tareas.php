<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/CalificarTareasController.php";

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
$mensaje = "";

// 🧾 Calificar o devolver entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $idEntrega = intval($_POST['id_entrega'] ?? 0);
  $retro = trim($_POST['retroalimentacion'] ?? '');

  if (isset($_POST['accion']) && $_POST['accion'] === 'devolver') {
    $mensaje = CalificarTareasController::devolverEntrega($idEntrega, $retro);
  } elseif (isset($_POST['accion']) && $_POST['accion'] === 'calificar') {
    $calificacion = floatval($_POST['calificacion'] ?? 0);
    $mensaje = CalificarTareasController::calificarEntrega($idEntrega, $calificacion, $retro);
  }
}

// 📦 Obtener todas las entregas del docente
$entregas = CalificarTareasController::obtenerTodasLasEntregasDocente($idDocente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>📘 Calificar Tareas | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/docentes/dashboard_calificar.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu"></div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
      <?php if (!empty($mensaje)): ?>
        <div class="alert-message"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <div class="page-title">
        <h2><i class="fa-solid fa-list-check"></i> Todas las entregas</h2>
        <p>Revisa, califica o devuelve tareas de tus materias.</p>
      </div>

      <div class="table-card">
        <?php if ($entregas && $entregas->num_rows > 0): ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Materia</th>
                <th>Tarea</th>
                <th>Alumno</th>
                <th>Archivo</th>
                <th>Fecha límite</th>
                <th>Fecha entrega</th>
                <th>Estado</th>
                <th>Calificación</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($e = $entregas->fetch_assoc()): ?>
                <?php
                  // Calcular si fue fuera de tiempo o después de devolución
                  $estadoExtra = '';
                  if (!empty($e['fecha_envio']) && !empty($e['fecha_limite'])) {
                    $limite = new DateTime($e['fecha_limite']);
                    $envio = new DateTime($e['fecha_envio']);
                    if ($envio > $limite) {
                      $estadoExtra = ' (⏰ fuera de tiempo)';
                    }
                  }
                  if ($e['estado'] === 'Devuelta') {
                    $estadoClase = 'tag warning';
                  } elseif ($e['estado'] === 'Calificada') {
                    $estadoClase = 'tag success';
                  } else {
                    $estadoClase = 'tag info';
                  }
                ?>
                <tr>
                  <td><?= htmlspecialchars($e['nombre_materia'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($e['titulo_tarea'] ?? '—') ?></td>
                  <td><?= htmlspecialchars(($e['nombre_alumno'] ?? '') . ' ' . ($e['apellido_paterno'] ?? '')) ?></td>
                  <td>
                    <?php if (!empty($e['archivo'])): ?>
                      <button class="btn-sm btn-ver"
                        data-archivo="/Plataforma_UT/<?= htmlspecialchars($e['archivo']) ?>"
                        data-nombre="<?= basename($e['archivo']) ?>">
                        <i class="fa-solid fa-eye"></i> Ver
                      </button>
                    <?php else: ?>
                      <span style="color:#888;">Sin archivo</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($e['fecha_limite'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($e['fecha_envio'] ?? '—') ?></td>
                  <td><span class="<?= $estadoClase ?>"><?= htmlspecialchars($e['estado'] . $estadoExtra) ?></span></td>
                  <td><?= $e['calificacion'] !== null ? htmlspecialchars($e['calificacion']) : '—' ?></td>
                  <td>
                    <?php if ($e['estado'] !== 'Calificada'): ?>
                      <button class="btn-primary btn-calificar"
                        data-id="<?= $e['id_entrega'] ?>"
                        data-alumno="<?= htmlspecialchars(($e['nombre_alumno'] ?? '') . ' ' . ($e['apellido_paterno'] ?? '')) ?>"
                        data-tarea="<?= htmlspecialchars($e['titulo_tarea'] ?? '') ?>">
                        <i class="fa-solid fa-pen"></i> Calificar
                      </button>
                    <?php else: ?>
                      <span style="color:#777;">✔️ Ya calificada</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="empty">No hay entregas registradas aún.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Modal Calificación -->
  <div id="modalCalificar" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h3><i class="fa-solid fa-pen"></i> Calificar entrega</h3>
      <form method="POST">
        <input type="hidden" name="id_entrega" id="modal_id_entrega">
        <div class="form-group">
          <label>Alumno:</label>
          <input type="text" id="modal_alumno" readonly>
        </div>
        <div class="form-group">
          <label>Tarea:</label>
          <input type="text" id="modal_tarea" readonly>
        </div>
        <div class="form-group">
          <label>Calificación:</label>
          <input type="number" name="calificacion" min="0" max="100" step="0.1" required>
        </div>
        <div class="form-group">
          <label>Retroalimentación:</label>
          <textarea name="retroalimentacion" rows="3" placeholder="Comentarios del docente..."></textarea>
        </div>
        <div class="modal-buttons">
          <button type="submit" name="accion" value="calificar" class="btn-primary full">
            <i class="fa-solid fa-check"></i> Calificar
          </button>
          <button type="submit" name="accion" value="devolver" class="btn-devolver full">
            <i class="fa-solid fa-rotate-left"></i> Devolver
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Vista -->
  <div id="modalVista" class="modal">
    <div class="modal-content large">
      <span class="close-btn">&times;</span>
      <h3><i class="fa-solid fa-file"></i> <span id="vistaNombre"></span></h3>
      <iframe id="vistaIframe" src="" frameborder="0" style="width:100%;height:600px;"></iframe>
    </div>
  </div>

  <!-- JS -->
  <script>
    // Sidebar dinámica
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8'); ?>";

    // Modal de calificación
    const modal = document.getElementById("modalCalificar");
    const idInput = document.getElementById("modal_id_entrega");
    const alumnoInput = document.getElementById("modal_alumno");
    const tareaInput = document.getElementById("modal_tarea");

    document.querySelectorAll(".btn-calificar").forEach(btn => {
      btn.addEventListener("click", () => {
        idInput.value = btn.dataset.id;
        alumnoInput.value = btn.dataset.alumno;
        tareaInput.value = btn.dataset.tarea;
        modal.classList.add("active");
      });
    });

    // Cerrar modal
    document.querySelectorAll(".close-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        btn.closest(".modal").classList.remove("active");
      });
    });

    // Modal de vista previa
    document.querySelectorAll(".btn-ver").forEach(btn => {
      btn.addEventListener("click", () => {
        const iframe = document.getElementById("vistaIframe");
        const nombre = document.getElementById("vistaNombre");
        iframe.src = btn.dataset.archivo;
        nombre.textContent = btn.dataset.nombre;
        document.getElementById("modalVista").classList.add("active");
      });
    });
  </script>

  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
