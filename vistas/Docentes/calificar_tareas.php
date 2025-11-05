<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/CalificarTareasController.php";

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
$mensaje = "";

// 📦 Obtener materias y tareas asignadas al docente
$sqlMaterias = "
  SELECT ad.id_asignacion_docente, m.nombre_materia
  FROM asignaciones_docentes ad
  INNER JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
  INNER JOIN materias m ON am.id_materia = m.id_materia
  WHERE ad.id_docente = ?
";
$stmt = $conn->prepare($sqlMaterias);
$stmt->bind_param("i", $idDocente);
$stmt->execute();
$materias = $stmt->get_result();

// Si selecciona una materia
$tareas = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_asignacion'])) {
  $idAsignacion = intval($_POST['id_asignacion']);
  $sqlTareas = "
    SELECT t.id_tarea, t.titulo, t.fecha_entrega,
           COUNT(e.id_entrega) AS total_entregas
    FROM tareas_materias t
    LEFT JOIN entregas_alumnos e ON e.id_tarea = t.id_tarea
    WHERE t.id_asignacion_docente = ?
    GROUP BY t.id_tarea
    ORDER BY t.fecha_entrega DESC
  ";
  $stmt2 = $conn->prepare($sqlTareas);
  $stmt2->bind_param("i", $idAsignacion);
  $stmt2->execute();
  $tareas = $stmt2->get_result();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📋 Calificar Tareas | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/docentes/calificar_tareas.css">
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- Contenido -->
    <main class="main-content">
      <?php if (!empty($mensaje)): ?>
        <div class="alert-message"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <header class="page-title">
        <h2><i class="fa-solid fa-clipboard-check"></i> Calificar Tareas</h2>
        <p>Selecciona una materia para revisar y calificar las tareas enviadas.</p>
      </header>

      <!-- Selección de materia -->
      <section class="materia-selector">
        <form method="POST">
          <label for="id_asignacion"><i class="fa-solid fa-book"></i> Selecciona materia:</label>
          <select name="id_asignacion" id="id_asignacion" required>
            <option value="">-- Selecciona --</option>
            <?php while ($m = $materias->fetch_assoc()): ?>
              <option value="<?= $m['id_asignacion_docente'] ?>" <?= isset($idAsignacion) && $idAsignacion == $m['id_asignacion_docente'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['nombre_materia']) ?>
              </option>
            <?php endwhile; ?>
          </select>
          <button type="submit" class="btn-primary">
            <i class="fa-solid fa-eye"></i> Ver tareas
          </button>
        </form>
      </section>

      <!-- Tabla de tareas -->
      <?php if ($tareas): ?>
        <div class="table-card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Título</th>
                <th>Fecha Límite</th>
                <th>Total Entregas</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($t = $tareas->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($t['titulo']) ?></td>
                  <td><?= htmlspecialchars($t['fecha_entrega']) ?></td>
                  <td><?= intval($t['total_entregas']) ?></td>
                  <td>
                    <a href="dashboardCalificar.php?id=<?= $idAsignacion ?>" class="btn-primary">
                      <i class="fa-solid fa-pen-to-square"></i> Calificar
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p class="empty">No hay tareas registradas en esta materia.</p>
      <?php endif; ?>
    </main>
  </div>

  <!-- JS base -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>

  <!-- Fallback Sidebar -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const menu = document.getElementById("menu");
      if (!menu || menu.innerHTML.trim() === "") {
        const script = document.createElement("script");
        script.src = "/Plataforma_UT/js/Dashboard_Inicio.js?v=" + Date.now();
        document.body.appendChild(script);
      }
    });
  </script>
</body>
</html>
