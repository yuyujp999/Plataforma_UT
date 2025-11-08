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
  ORDER BY t.fecha_entrega DESC
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
    /* --- MODAL VISTA PREVIA --- */
    .modal-content.large {
      width: 90%;
      max-width: 1200px;
      height: 95vh;
      display: flex;
      flex-direction: column;
    }
    .vista-container {
      flex: 1;
      margin-top: 10px;
    }
    .vista-container iframe {
      width: 100%;
      height: 100%;
      border-radius: 10px;
      border: 1px solid #ddd;
      background: #fff;
    }

    /* --- BOTONES DE FILTRO --- */
    .filter-bar {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .filter-btn {
      background: var(--primary);
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .filter-btn:hover,
    .filter-btn.active {
      background: var(--secondary);
      transform: translateY(-2px);
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar (NO SE MODIFICA NADA) -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- Contenido principal -->
    <main class="main-content">
      <?php if (!empty($mensajeEntrega)): ?>
        <div class="alert-message"><?= htmlspecialchars($mensajeEntrega) ?></div>
      <?php endif; ?>

      <header class="materia-header">
        <div class="materia-info">
          <h2><i class="fa-solid fa-tasks"></i> Mis Tareas</h2>
          <p>Consulta y filtra tus tareas por estado.</p>
        </div>
      </header>

      <!-- Barra de filtros -->
      <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">Todas</button>
        <button class="filter-btn" data-filter="tarea-entregada">Entregadas</button>
        <button class="filter-btn" data-filter="tarea-pendiente">Pendientes</button>
        <button class="filter-btn" data-filter="tarea-fuera">Fuera de tiempo</button>
        <button class="filter-btn" data-filter="tarea-bloqueada">Cerradas</button>
      </div>

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
                } elseif (!empty($t['fecha_envio'])) {
                  $estadoClase = 'tarea-entregada';
                  $estadoTexto = '✅ Entregada';
                } elseif ($fueraTiempo) {
                  $estadoClase = 'tarea-fuera';
                  $estadoTexto = '⏰ Fuera de tiempo';
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
                        <h4><i class="fa-solid fa-chalkboard-user"></i> Retroalimentación</h4>
                        <?php if ($t['calificacion'] !== null): ?>
                          <div class="score"><?= htmlspecialchars($t['calificacion']) ?>/100</div>
                        <?php endif; ?>
                      </div>
                      <p><?= nl2br(htmlspecialchars($t['retroalimentacion'] ?? '')) ?></p>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="tarea-actions">
                  <?php if (!empty($t['archivo'])): ?>
                    <button class="btn-ver" 
                      data-archivo="/Plataforma_UT/<?= htmlspecialchars($t['archivo']) ?>"
                      data-titulo="<?= htmlspecialchars($t['titulo_tarea']) ?>">
                      <i class="fa-solid fa-file"></i> Ver archivo
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </section>
      <?php else: ?>
        <p class="empty">No hay tareas registradas.</p>
      <?php endif; ?>
    </main>
  </div>

  <!-- MODAL DE VISTA PREVIA -->
  <div id="visorArchivo" class="modal">
    <div class="modal-content large">
      <span class="close-btn">&times;</span>
      <h3 id="tituloArchivo"><i class="fa-solid fa-file"></i> Vista previa</h3>
      <div class="vista-container">
        <iframe id="iframeArchivo" src="" frameborder="0"></iframe>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
    // Filtro por estado
    const buttons = document.querySelectorAll(".filter-btn");
    const tareas = document.querySelectorAll(".tarea-item");
    buttons.forEach(btn => {
      btn.addEventListener("click", () => {
        buttons.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const filter = btn.dataset.filter;
        tareas.forEach(t => {
          if (filter === "all" || t.classList.contains(filter)) {
            t.style.display = "flex";
          } else {
            t.style.display = "none";
          }
        });
      });
    });

    // Modal visor de archivos (PDF, Word, imágenes)
    const visor = document.getElementById("visorArchivo");
    const iframe = document.getElementById("iframeArchivo");
    const tituloArchivo = document.getElementById("tituloArchivo");

    document.querySelectorAll(".btn-ver").forEach(btn => {
      btn.addEventListener("click", () => {
        const archivo = btn.dataset.archivo;
        const titulo = btn.dataset.titulo;
        tituloArchivo.innerHTML = `<i class='fa-solid fa-file'></i> ${titulo}`;

        const ext = archivo.split('.').pop().toLowerCase();
        if (['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
          iframe.src = archivo;
        } else if (ext === 'doc' || ext === 'docx') {
          iframe.src = `https://view.officeapps.live.com/op/embed.aspx?src=${window.location.origin + archivo}`;
        } else {
          iframe.src = "";
          alert("⚠️ Este tipo de archivo no se puede previsualizar.");
        }

        visor.classList.add("active");
      });
    });

    // Cerrar visor
    document.querySelectorAll(".close-btn").forEach(btn => {
      btn.addEventListener("click", () => btn.closest(".modal").classList.remove("active"));
    });
  });
  </script>
</body>
</html>
