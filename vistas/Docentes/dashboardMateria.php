<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/docentecontroller.php";
include_once __DIR__ . "/../../controladores/docentes/TareasController.php";

$idAsignacion = intval($_GET['id'] ?? 0);
$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [];
$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;

// datos de la materia
$conexion = $conn;
$sql = "
  SELECT 
    cnm.nombre AS codigo_materia,
    m.nombre_materia,
    cng.nombre AS grupo
  FROM asignaciones_docentes AS ad
  INNER JOIN cat_nombres_materias AS cnm
    ON ad.id_nombre_materia = cnm.id_nombre_materia
  LEFT JOIN asignar_materias AS am
    ON ad.id_nombre_materia = am.id_nombre_materia
  LEFT JOIN materias AS m
    ON am.id_materia = m.id_materia
  LEFT JOIN cat_nombres_grupo AS cng
    ON am.id_nombre_grupo_int = cng.id_nombre_grupo
  WHERE ad.id_asignacion_docente = $idAsignacion
  LIMIT 1
";
$materia = $conexion->query($sql)->fetch_assoc();

// tareas
$tareas = TareasController::obtenerTareasPorMateria($idAsignacion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($materia['nombre_materia'] ?? 'Materia') ?> | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/materia_docente.css">
</head>
<body>
  <div class="container">
    <!-- SIDEBAR (estructura que espera el JS) -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
        <!-- El JS inyecta aquí los items -->
      </div>
    </div>

    <!-- CONTENIDO -->
    <div class="main-content">
      <div class="materia-navbar">
        <div class="materia-info">
          <h2><i class="fa-solid fa-book"></i> <?= htmlspecialchars($materia['nombre_materia'] ?? 'Materia') ?></h2>
          <p><strong>Grupo:</strong> <?= htmlspecialchars($materia['grupo'] ?? '—') ?></p>
        </div>
        <div class="user-info">
          <i class="fa-solid fa-user-tie"></i>
          <span><?= htmlspecialchars($usuarioNombre) ?></span>
        </div>
      </div>

      <div class="materia-panel">
        <div class="card">
          <i class="fa-solid fa-upload"></i>
          <h3>Subir Recursos</h3>
          <p>Agrega archivos y material para tus alumnos.</p>
          <a href="subirTarea.php?id=<?= $idAsignacion ?>" class="btn">Ir</a>
        </div>

        <div class="card">
          <i class="fa-solid fa-clipboard-check"></i>
          <h3>Calificar Tareas</h3>
          <p>Consulta las tareas entregadas por tus alumnos.</p>
          <a href="#" class="btn">Ir</a>
        </div>

        <div class="card">
          <i class="fa-solid fa-comments"></i>
          <h3>Comunicados</h3>
          <p>Envía avisos o mensajes al grupo.</p>
          <a href="#" class="btn">Ir</a>
        </div>
      </div>

      <div class="tareas-section">
        <h2><i class="fa-solid fa-tasks"></i> Tareas publicadas</h2>
        <?php if ($tareas && $tareas->num_rows > 0): ?>
          <div class="tareas-list">
            <?php while ($t = $tareas->fetch_assoc()): ?>
              <div class="tarea-card">
                <h4><?= htmlspecialchars($t['titulo']) ?></h4>
                <p><?= htmlspecialchars($t['descripcion']) ?></p>
                <p><strong>Entrega:</strong> <?= $t['fecha_entrega'] ? htmlspecialchars($t['fecha_entrega']) : 'Sin fecha' ?></p>
                <?php if (!empty($t['archivo'])): ?>
                  <a class="archivo" href="/Plataforma_UT/<?= htmlspecialchars($t['archivo']) ?>" target="_blank">
                    <i class="fa-solid fa-file-arrow-down"></i> Descargar
                  </a>
                <?php endif; ?>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="no-tareas">Aún no se han publicado tareas para esta materia.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Pasa el rol al JS ANTES de cargar el script del menú -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
