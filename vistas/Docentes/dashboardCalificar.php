<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/CalificarTareasController.php";

$idAsignacion = intval($_GET['id'] ?? 0);
$mensaje = "";

// 🧾 Calificar entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $idEntrega = intval($_POST['id_entrega'] ?? 0);
  $calificacion = floatval($_POST['calificacion'] ?? 0);
  $retro = $_POST['retroalimentacion'] ?? '';
  $mensaje = CalificarTareasController::calificarEntrega($idEntrega, $calificacion, $retro);
}

// 📦 Obtener entregas de esa materia
$entregas = CalificarTareasController::obtenerEntregasPorMateria($idAsignacion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Calificar Tareas | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/docentes/dashboard_calificar.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu"></div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <?php if ($mensaje): ?>
        <div class="alert-message"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <div class="page-title">
        <h2><i class="fa-solid fa-clipboard-check"></i> Calificar Tareas</h2>
        <a href="dashboardMateria.php?id=<?= $idAsignacion ?>" class="btn-outline">
          <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
      </div>

      <div class="table-card">
        <?php if ($entregas && $entregas->num_rows > 0): ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Alumno</th>
                <th>Tarea</th>
                <th>Archivo</th>
                <th>Fecha Entrega</th>
                <th>Calificación</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($e = $entregas->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($e['nombre_alumno']) ?></td>
                  <td><?= htmlspecialchars($e['titulo_tarea']) ?></td>
                  <td>
                    <span class="file-name"><?= basename($e['archivo']) ?></span><br>
                    <button class="btn-sm btn-ver"
                            data-archivo="/Plataforma_UT/<?= htmlspecialchars($e['archivo']) ?>"
                            data-nombre="<?= basename($e['archivo']) ?>">
                      <i class="fa-solid fa-eye"></i> Vista previa
                    </button>
                  </td>
                  <td><?= htmlspecialchars($e['fecha_entrega']) ?></td>
                  <td><?= $e['calificacion'] !== null ? $e['calificacion'] : '—' ?></td>
                  <td>
                    <button class="btn-primary btn-calificar"
                            data-id="<?= $e['id_entrega'] ?>"
                            data-alumno="<?= htmlspecialchars($e['nombre_alumno']) ?>"
                            data-tarea="<?= htmlspecialchars($e['titulo_tarea']) ?>">
                      <i class="fa-solid fa-pen"></i> Calificar
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="empty">Aún no hay entregas registradas para esta materia.</p>
        <?php endif; ?>
      </div>
    </div>
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
        <button type="submit" class="btn-primary full">
          <i class="fa-solid fa-paper-plane"></i> Guardar Calificación
        </button>
      </form>
    </div>
  </div>

  <!-- Modal Vista Previa -->
  <div id="modalVista" class="modal">
    <div class="modal-content large">
      <span class="close-btn">&times;</span>
      <h3><i class="fa-solid fa-file"></i> <span id="vistaNombre"></span></h3>
      <div class="vista-container">
        <iframe id="vistaIframe" src="" frameborder="0"></iframe>
        <p id="vistaError" style="display:none; text-align:center; color:#888; padding:20px;">
          ⚠️ Este tipo de archivo no puede visualizarse directamente.
        </p>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";

    // --- Modal Calificar ---
    const modal = document.getElementById("modalCalificar");
    const closeBtn = modal.querySelector(".close-btn");
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

    closeBtn.addEventListener("click", () => modal.classList.remove("active"));
    window.addEventListener("click", e => { if (e.target === modal) modal.classList.remove("active"); });

    // --- Modal de Vista Previa ---
    const modalVista = document.getElementById("modalVista");
    const vistaIframe = document.getElementById("vistaIframe");
    const vistaNombre = document.getElementById("vistaNombre");
    const vistaError = document.getElementById("vistaError");
    const closeVista = modalVista.querySelector(".close-btn");

    document.querySelectorAll(".btn-ver").forEach(btn => {
      btn.addEventListener("click", () => {
        const archivo = btn.dataset.archivo;
        const nombre = btn.dataset.nombre;
        vistaNombre.textContent = nombre;
        
        const ext = nombre.split(".").pop().toLowerCase();
        const visores = ["pdf", "png", "jpg", "jpeg"];

        if (visores.includes(ext)) {
          vistaIframe.src = archivo;
          vistaIframe.style.display = "block";
          vistaError.style.display = "none";
        } else if (ext === "doc" || ext === "docx") {
          vistaIframe.src = `https://view.officeapps.live.com/op/embed.aspx?src=${window.location.origin + archivo}`;
          vistaIframe.style.display = "block";
          vistaError.style.display = "none";
        } else {
          vistaIframe.src = "";
          vistaIframe.style.display = "none";
          vistaError.style.display = "block";
        }

        modalVista.classList.add("active");
      });
    });

    closeVista.addEventListener("click", () => modalVista.classList.remove("active"));
    window.addEventListener("click", e => { if (e.target === modalVista) modalVista.classList.remove("active"); });
  </script>

  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
