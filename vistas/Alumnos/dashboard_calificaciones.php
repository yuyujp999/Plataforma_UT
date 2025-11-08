<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";

$idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;
$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [];
$nombre = $usuario['nombre'] ?? 'Alumno';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;

// 🔍 Obtener todas las materias del alumno
$sqlMaterias = "
  SELECT DISTINCT m.id_materia, m.nombre_materia
  FROM materias m
  INNER JOIN asignar_materias am ON m.id_materia = am.id_materia
  INNER JOIN asignaciones_docentes ad ON am.id_nombre_materia = ad.id_nombre_materia
  INNER JOIN tareas_materias t ON ad.id_asignacion_docente = t.id_asignacion_docente
  INNER JOIN entregas_alumnos e ON t.id_tarea = e.id_tarea
  WHERE e.id_alumno = ?
  ORDER BY m.nombre_materia ASC
";
$stmtMaterias = $conn->prepare($sqlMaterias);
$stmtMaterias->bind_param("i", $idAlumno);
$stmtMaterias->execute();
$materias = $stmtMaterias->get_result()->fetch_all(MYSQLI_ASSOC);

// 🔍 Obtener todas las tareas con calificaciones
$sqlTareas = "
  SELECT 
    t.titulo AS titulo_tarea, t.fecha_entrega, 
    m.nombre_materia,
    e.calificacion, e.retroalimentacion, e.estado
  FROM tareas_materias t
  INNER JOIN asignaciones_docentes ad ON t.id_asignacion_docente = ad.id_asignacion_docente
  INNER JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
  INNER JOIN materias m ON am.id_materia = m.id_materia
  LEFT JOIN entregas_alumnos e ON e.id_tarea = t.id_tarea AND e.id_alumno = ?
  ORDER BY m.nombre_materia ASC, t.fecha_entrega DESC
";
$stmtTareas = $conn->prepare($sqlTareas);
$stmtTareas->bind_param("i", $idAlumno);
$stmtTareas->execute();
$tareas = $stmtTareas->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>⭐ Calificaciones | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Alumnos/dashboard_calificaciones.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
  <div class="container">
    <!-- 🧭 Sidebar (NO SE TOCA) -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <main class="main-content">
      <header class="materia-header">
        <div class="materia-info">
          <h2><i class="fa-solid fa-star"></i> Mis Calificaciones</h2>
          <p>Consulta tus calificaciones y retroalimentaciones por materia.</p>
        </div>
      </header>

      <div class="calificaciones-container">
        <div class="filter-materia">
          <button class="filter-btn active" data-materia="all">Todas</button>
          <?php foreach ($materias as $m): ?>
            <button class="filter-btn" data-materia="<?= htmlspecialchars($m['nombre_materia']) ?>">
              <?= htmlspecialchars($m['nombre_materia']) ?>
            </button>
          <?php endforeach; ?>
        </div>

        <table>
          <thead>
            <tr>
              <th>Materia</th>
              <th>Tarea</th>
              <th>Fecha límite</th>
              <th>Calificación</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($t = $tareas->fetch_assoc()): ?>
              <?php
                $estadoClase = strtolower($t['estado'] ?? 'pendiente');
                $calif = $t['calificacion'] ?? '—';
              ?>
              <tr class="fila-tarea" 
                  data-materia="<?= htmlspecialchars($t['nombre_materia']) ?>"
                  data-retro="<?= htmlspecialchars($t['retroalimentacion'] ?? 'Sin comentarios.') ?>"
                  data-titulo="<?= htmlspecialchars($t['titulo_tarea']) ?>">
                <td><?= htmlspecialchars($t['nombre_materia']) ?></td>
                <td><?= htmlspecialchars($t['titulo_tarea']) ?></td>
                <td><?= htmlspecialchars($t['fecha_entrega']) ?></td>
                <td><?= $calif ?></td>
                <td class="estado <?= $estadoClase ?>"><?= htmlspecialchars($t['estado'] ?? 'Pendiente') ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- Modal Retroalimentación -->
  <div class="modal" id="modalRetro">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h3><i class="fa-solid fa-comments"></i> Retroalimentación</h3>
      <p id="retroTexto"></p>
    </div>
  </div>

  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
    // Filtro por materia
    const filterBtns = document.querySelectorAll(".filter-btn");
    const filas = document.querySelectorAll(".fila-tarea");

    filterBtns.forEach(btn => {
      btn.addEventListener("click", () => {
        filterBtns.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const materia = btn.dataset.materia;
        filas.forEach(f => {
          f.style.display = (materia === "all" || f.dataset.materia === materia) ? "" : "none";
        });
      });
    });

    // Modal retroalimentación
    const modal = document.getElementById("modalRetro");
    const retroTexto = document.getElementById("retroTexto");
    const closeBtn = document.querySelector(".close-btn");

    document.querySelectorAll(".fila-tarea").forEach(fila => {
      fila.addEventListener("click", () => {
        const tarea = fila.dataset.titulo;
        const retro = fila.dataset.retro;
        retroTexto.innerHTML = `<strong>${tarea}</strong><br><br>${retro}`;
        modal.classList.add("active");
      });
    });

    closeBtn.addEventListener("click", () => modal.classList.remove("active"));
  });
  </script>
</body>
</html>
