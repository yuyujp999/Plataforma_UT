<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/alumnos/MateriasAlumnoController.php";
include_once __DIR__ . "/../../controladores/alumnos/EntregasAlumnoController.php";

$idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;
$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [];
$nombre = $usuario['nombre'] ?? 'Alumno';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;

// 🧾 Procesar entrega
$mensajeEntrega = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_entrega'])) {
  $idTarea = intval($_POST['id_tarea'] ?? 0);
  if ($idTarea > 0 && $idAlumno > 0) {
    $resultado = EntregasAlumnoController::subirEntrega($idTarea, $idAlumno, $_FILES['archivo_entrega']);
    $mensajeEntrega = $resultado['mensaje'];
  } else {
    $mensajeEntrega = "❌ No se pudo identificar la tarea o el alumno.";
  }
}

// 🔍 Obtener todas las tareas del alumno
$stmt = $conn->prepare("
  SELECT 
      t.id_tarea, t.titulo AS titulo_tarea, t.descripcion, t.fecha_entrega,
      m.nombre_materia,
      d.nombre AS nombre_docente, d.apellido_paterno AS apellido_docente,
      e.id_entrega, e.archivo, e.fecha_entrega AS fecha_envio, 
      e.calificacion, e.estado, e.retroalimentacion
  FROM tareas_materias t
  INNER JOIN asignaciones_docentes ad 
      ON t.id_asignacion_docente = ad.id_asignacion_docente
  INNER JOIN asignar_materias am 
      ON ad.id_nombre_materia = am.id_nombre_materia
  INNER JOIN materias m 
      ON am.id_materia = m.id_materia
  INNER JOIN docentes d 
      ON ad.id_docente = d.id_docente
  LEFT JOIN entregas_alumnos e 
      ON e.id_tarea = t.id_tarea AND e.id_alumno = ?
  ORDER BY 
      CASE 
        WHEN e.estado = 'Devuelta' THEN 1
        WHEN e.calificacion IS NOT NULL THEN 2
        WHEN e.fecha_entrega IS NOT NULL THEN 3
        ELSE 4
      END, 
      t.fecha_entrega DESC
");

$stmt->bind_param("i", $idAlumno);
$stmt->execute();
$tareas = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📋 Tareas | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/alumnos/dashboard_tareas.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
  <style>
    .btn-reentregar {
      background-color: #ff9800;
      color: #fff;
    }
    .btn-reentregar:hover {
      background-color: #e68900;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- 🧭 Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- 📄 Contenido principal -->
    <main class="main-content">
      <?php if (!empty($mensajeEntrega)): ?>
        <div class="alert-message"><?= htmlspecialchars($mensajeEntrega) ?></div>
      <?php endif; ?>

      <header class="materia-header">
        <div class="materia-info">
          <h2><i class="fa-solid fa-tasks"></i> Mis Tareas</h2>
          <p>Consulta todas tus tareas, entregas y calificaciones.</p>
        </div>
      </header>

      <?php if ($tareas->num_rows > 0): ?>
        <section class="section-content">
          <div class="tareas-lista">
            <?php while ($t = $tareas->fetch_assoc()): ?>
              <?php
                $ahora = new DateTime('now', new DateTimeZone('America/Mexico_City'));
                $fechaLimite = $t['fecha_entrega'] ? new DateTime($t['fecha_entrega']) : null;
                $fueraTiempo = $fechaLimite && $ahora > $fechaLimite;
                $bloqueado = $fueraTiempo && $fechaLimite->diff($ahora)->days > 7;

                if ($bloqueado) {
                  $estadoClase = 'tarea-bloqueada';
                  $estadoTexto = '🚫 Entrega cerrada';
                } elseif ($t['estado'] === 'Devuelta') {
                  $estadoClase = 'tarea-devuelta';
                  $estadoTexto = '🔄 Devuelta para corrección';
                } elseif ($t['calificacion'] !== null) {
                  $estadoClase = 'tarea-calificada';
                  $estadoTexto = '⭐ Calificada';
                } elseif ($fueraTiempo && !$bloqueado) {
                  $estadoClase = 'tarea-fuera';
                  $estadoTexto = '⏰ Fuera de tiempo';
                } elseif (!empty($t['fecha_envio'])) {
                  $estadoClase = 'tarea-entregada';
                  $estadoTexto = '✅ Entregada';
                } else {
                  $estadoClase = 'tarea-pendiente';
                  $estadoTexto = '📬 Pendiente de entrega';
                }
              ?>
              <div class="tarea-item <?= $estadoClase ?>">
                <div class="tarea-info">
                  <h3><?= htmlspecialchars($t['titulo_tarea']) ?></h3>
                  <p><strong>Materia:</strong> <?= htmlspecialchars($t['nombre_materia']) ?></p>
                  <p><strong>Docente:</strong> <?= htmlspecialchars($t['nombre_docente'] . ' ' . $t['apellido_docente']) ?></p>
                  <p><strong>Fecha límite:</strong> <?= $t['fecha_entrega'] ?: '—' ?></p>
                  <p class="estado"><?= $estadoTexto ?></p>

                  <?php if ($t['calificacion'] !== null || !empty($t['retroalimentacion'])): ?>
                    <div class="info-docente">
                      <div class="head">
                        <h4><i class="fa-solid fa-chalkboard-user"></i> Retroalimentación del docente</h4>
                        <?php if ($t['calificacion'] !== null): ?>
                          <div class="score"><?= htmlspecialchars($t['calificacion']) ?>/100</div>
                        <?php endif; ?>
                      </div>
                      <?php if (!empty($t['retroalimentacion'])): ?>
                        <p><?= nl2br(htmlspecialchars($t['retroalimentacion'])) ?></p>
                      <?php else: ?>
                        <p style="color:#777;">Sin comentarios adicionales.</p>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="tarea-actions">
                  <?php if (!empty($t['archivo'])): ?>
                    <button class="btn-ver" data-archivo="/Plataforma_UT/<?= htmlspecialchars($t['archivo']) ?>">
                      <i class="fa-solid fa-file"></i> Ver archivo
                    </button>
                  <?php endif; ?>

                  <?php if (!$bloqueado && $t['calificacion'] === null): ?>
                    <?php if ($t['estado'] === 'Devuelta'): ?>
                      <button class="btn-reentregar" 
                        data-tarea="<?= $t['id_tarea'] ?>" 
                        data-titulo="<?= htmlspecialchars($t['titulo_tarea']) ?>"
                        data-materia="<?= htmlspecialchars($t['nombre_materia']) ?>"
                        data-docente="<?= htmlspecialchars($t['nombre_docente'] . ' ' . $t['apellido_docente']) ?>">
                        <i class="fa-solid fa-rotate-right"></i> Volver a entregar
                      </button>
                    <?php elseif (!empty($t['fecha_envio'])): ?>
                      <button class="btn-editar" 
                        data-tarea="<?= $t['id_tarea'] ?>" 
                        data-titulo="<?= htmlspecialchars($t['titulo_tarea']) ?>"
                        data-materia="<?= htmlspecialchars($t['nombre_materia']) ?>"
                        data-docente="<?= htmlspecialchars($t['nombre_docente'] . ' ' . $t['apellido_docente']) ?>">
                        <i class="fa-solid fa-pen-to-square"></i> Editar entrega
                      </button>
                    <?php else: ?>
                      <button class="btn-entregar" 
                        data-tarea="<?= $t['id_tarea'] ?>" 
                        data-titulo="<?= htmlspecialchars($t['titulo_tarea']) ?>"
                        data-materia="<?= htmlspecialchars($t['nombre_materia']) ?>"
                        data-docente="<?= htmlspecialchars($t['nombre_docente'] . ' ' . $t['apellido_docente']) ?>">
                        <i class="fa-solid fa-upload"></i> Entregar Tarea
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </section>
      <?php else: ?>
        <p class="empty">No hay tareas registradas actualmente.</p>
      <?php endif; ?>
    </main>
  </div>

  <!-- 🔸 MODALES -->
  <div id="modalEntrega" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h3><i class="fa-solid fa-upload"></i> Entregar tarea</h3>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_tarea" id="modal_id_tarea">
        <div class="form-group">
          <label>Tarea:</label>
          <input type="text" id="modal_titulo_tarea" readonly>
        </div>
        <div class="form-group">
          <label>Materia:</label>
          <input type="text" id="modal_materia_tarea" readonly>
        </div>
        <div class="form-group">
          <label>Docente:</label>
          <input type="text" id="modal_docente_tarea" readonly>
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

  <div id="visorArchivo" class="modal">
    <div class="modal-content large">
      <span class="close-btn">&times;</span>
      <iframe id="iframeArchivo" src="" width="100%" height="600px" frameborder="0"></iframe>
    </div>
  </div>

  <!-- 🧠 JS -->
  <script>
    let modalEntrega = document.getElementById("modalEntrega");
    let modalVisor = document.getElementById("visorArchivo");
    let iframeArchivo = document.getElementById("iframeArchivo");

    document.querySelectorAll(".btn-ver").forEach(btn => {
      btn.addEventListener("click", () => {
        iframeArchivo.src = btn.dataset.archivo;
        modalVisor.classList.add("active");
      });
    });

    document.querySelectorAll(".btn-entregar, .btn-editar, .btn-reentregar").forEach(btn => {
      btn.addEventListener("click", () => {
        document.getElementById("modal_id_tarea").value = btn.dataset.tarea;
        document.getElementById("modal_titulo_tarea").value = btn.dataset.titulo;
        document.getElementById("modal_materia_tarea").value = btn.dataset.materia;
        document.getElementById("modal_docente_tarea").value = btn.dataset.docente;
        modalEntrega.classList.add("active");
      });
    });

    document.querySelectorAll(".close-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        btn.closest(".modal").classList.remove("active");
        iframeArchivo.src = "";
      });
    });
  </script>

<script>
  window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
</script>

<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const menu = document.getElementById("menu");
    if (!menu || menu.innerHTML.trim() === "") {
      console.warn("⚠️ Sidebar vacía — recargando Dashboard_Inicio.js");
      const script = document.createElement("script");
      script.src = "/Plataforma_UT/js/Dashboard_Inicio.js?v=" + Date.now();
      script.onload = () => console.log("✅ Dashboard_Inicio.js recargado correctamente");
      document.body.appendChild(script);
    }
  });
</script>
</body>
</html>
