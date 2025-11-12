<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/alumnos/MateriasAlumnoController.php";
include_once __DIR__ . "/../../controladores/alumnos/EntregasAlumnoController.php";

$idAsignacion = intval($_GET['id'] ?? 0);
$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [];
$nombre = $usuario['nombre'] ?? 'Alumno';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;

$materia = MateriasAlumnoController::obtenerDatosMateria($idAsignacion);
$tareas = MateriasAlumnoController::obtenerTareas($idAsignacion);
$recursos = MateriasAlumnoController::obtenerRecursos($idAsignacion);

$mensajeEntrega = "";

// 🧾 Procesar entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_entrega'])) {
  $idTarea = intval($_POST['id_tarea'] ?? 0);
  $idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;

  if ($idTarea > 0 && $idAlumno > 0) {
    $resultado = EntregasAlumnoController::subirEntrega($idTarea, $idAlumno, $_FILES['archivo_entrega']);
    $mensajeEntrega = $resultado['mensaje'];
  } else {
    $mensajeEntrega = "❌ No se pudo identificar la tarea o el alumno.";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($materia['nombre_materia'] ?? 'Materia') ?> | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/alumnos/dashboard_materia.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu"></div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <?php if (!empty($mensajeEntrega)): ?>
        <div class="alert-message"><?= htmlspecialchars($mensajeEntrega) ?></div>
      <?php endif; ?>

      <div class="materia-header">
        <div class="materia-info">
          <h2><i class="fa-solid fa-book"></i> <?= htmlspecialchars($materia['nombre_materia'] ?? 'Materia') ?></h2>
          <p><i class="fa-solid fa-layer-group"></i> <strong>Tu grupo:</strong> <?= htmlspecialchars($usuario['grupo'] ?? ($materia['grupo'] ?? '—')) ?></p>
          <p><i class="fa-solid fa-user-tie"></i> <strong>Docente:</strong> <?= htmlspecialchars(($materia['nombre_docente'] ?? 'Sin asignar') . ' ' . ($materia['apellido_docente'] ?? '')) ?></p>
        </div>
        <a href="dashboardAlumnos.php" class="btn-outline"><i class="fa-solid fa-arrow-left"></i> Volver</a>
      </div>

      <!-- 🔹 SECCIÓN DE TAREAS -->
      <section class="section-content">
        <h3><i class="fa-solid fa-tasks"></i> Tareas publicadas</h3>
        <div class="grid-items">
          <?php if ($tareas && $tareas->num_rows > 0): ?>
            <?php while ($t = $tareas->fetch_assoc()): ?>
              <?php
                $fechaLimite = $t['fecha_entrega'];
                $ahora = new DateTime('now', new DateTimeZone('America/Mexico_City'));
                $fechaLimiteObj = $fechaLimite ? new DateTime($fechaLimite) : null;

                $fueraDeTiempo = $fechaLimiteObj && $ahora > $fechaLimiteObj;
                $bloqueado = false;
                if ($fueraDeTiempo) {
                  $diasPasados = $fechaLimiteObj->diff($ahora)->days;
                  if ($diasPasados > 7) $bloqueado = true;
                }

                $idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;
                $entrega = EntregasAlumnoController::obtenerEntregaAlumno($t['id_tarea'], $idAlumno);

                // 🎨 Determinar clase visual
                $estadoClase = $bloqueado ? 'tarea-bloqueada' :
                               ($fueraDeTiempo ? 'tarea-fuera' :
                               ($entrega ? 'tarea-entregada' : 'tarea-pendiente'));
              ?>
              <div class="item-card <?= $estadoClase ?>">
                <h4><?= htmlspecialchars($t['titulo']) ?></h4>
                <p><?= htmlspecialchars($t['descripcion']) ?></p>
                <p><strong>Entrega límite:</strong> <?= $fechaLimite ?: 'Sin fecha' ?></p>

                <?php if ($entrega): ?>
                  <p class="tag-success">✅ Entregado el <?= date("d/m/Y H:i", strtotime($entrega['fecha_entrega'])) ?></p>
                <?php elseif ($bloqueado): ?>
                  <p class="tag-danger">🚫 Entrega cerrada</p>
                <?php elseif ($fueraDeTiempo): ?>
                  <p class="tag-warning">⏰ Fuera de tiempo (aún puedes entregar)</p>
                <?php else: ?>
                  <p class="tag-info">📬 Pendiente de entrega</p>
                <?php endif; ?>

                <?php if (!empty($t['archivo'])): ?>
                  <button class="btn-ver" data-archivo="/Plataforma_UT/<?= htmlspecialchars($t['archivo']) ?>">
                    <i class="fa-solid fa-file"></i> Ver archivo
                  </button>
                <?php endif; ?>

                <?php if (!$bloqueado): ?>
                  <?php if ($entrega): ?>
                    <button class="btn-editar" data-tarea="<?= $t['id_tarea'] ?>" data-titulo="<?= htmlspecialchars($t['titulo']) ?>">
                      <i class="fa-solid fa-pen-to-square"></i> Editar entrega
                    </button>
                  <?php else: ?>
                    <button class="btn-entregar" data-tarea="<?= $t['id_tarea'] ?>" data-titulo="<?= htmlspecialchars($t['titulo']) ?>">
                      <i class="fa-solid fa-upload"></i> Entregar Tarea
                    </button>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="empty">No hay tareas publicadas.</p>
          <?php endif; ?>
        </div>
      </section>

      <!-- 🔹 SECCIÓN DE RECURSOS -->
      <section class="section-content">
        <h3><i class="fa-solid fa-folder-open"></i> Recursos del docente</h3>
        <div class="grid-items">
          <?php if ($recursos && $recursos->num_rows > 0): ?>
            <?php while ($r = $recursos->fetch_assoc()): ?>
              <div class="item-card">
                <h4><?= htmlspecialchars($r['titulo']) ?></h4>
                <p><?= htmlspecialchars($r['descripcion']) ?></p>
                <?php if (!empty($r['archivo'])): ?>
                  <button class="btn-ver" data-archivo="/Plataforma_UT/<?= htmlspecialchars($r['archivo']) ?>">
                    <i class="fa-solid fa-file"></i> Ver archivo
                  </button>
                <?php endif; ?>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="empty">No hay recursos disponibles.</p>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </div>

  <!-- 🔸 MODAL DE ENTREGA -->
  <div id="modalEntrega" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h3><i class="fa-solid fa-upload"></i> Entregar tarea</h3>
      <form id="formEntrega" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_tarea" id="modal_id_tarea">
        <div class="form-group">
          <label>Tarea seleccionada:</label>
          <input type="text" id="modal_titulo_tarea" readonly>
        </div>
        <div class="form-group">
          <label>Archivo a subir:</label>
          <input type="file" name="archivo_entrega" required accept=".pdf,.docx,.zip,.rar">
        </div>
        <button type="submit" class="btn-primary full">
          <i class="fa-solid fa-paper-plane"></i> Enviar Entrega
        </button>
      </form>
    </div>
  </div>

  <!-- 🔸 VISOR DE ARCHIVOS -->
  <div id="visorArchivo" class="modal">
    <div class="modal-content large">
      <span class="close-btn">&times;</span>
      <iframe id="iframeArchivo" src="" width="100%" height="600px" frameborder="0"></iframe>
    </div>
  </div>

  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";

    const modalEntrega = document.getElementById("modalEntrega");
    const modalVisor = document.getElementById("visorArchivo");
    const iframeArchivo = document.getElementById("iframeArchivo");

    // Abrir visor de archivos
    document.querySelectorAll(".btn-ver").forEach(btn => {
      btn.addEventListener("click", () => {
        iframeArchivo.src = btn.dataset.archivo;
        modalVisor.classList.add("active");
      });
    });

    // Modal entrega (entregar/editar)
    document.querySelectorAll(".btn-entregar, .btn-editar").forEach(btn => {
      if (!btn.disabled) {
        btn.addEventListener("click", () => {
          document.getElementById("modal_titulo_tarea").value = btn.dataset.titulo;
          document.getElementById("modal_id_tarea").value = btn.dataset.tarea;
          modalEntrega.classList.add("active");
        });
      }
    });

    // Cerrar modales
    document.querySelectorAll(".close-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        btn.closest(".modal").classList.remove("active");
        iframeArchivo.src = "";
      });
    });
  </script>

  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
