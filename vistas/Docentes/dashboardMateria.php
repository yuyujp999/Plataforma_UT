<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/docentecontroller.php";
include_once __DIR__ . "/../../controladores/docentes/TareasController.php";
include_once __DIR__ . "/../../controladores/docentes/RecursosController.php"; // 🔹 nuevo controlador

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

// tareas y recursos
$tareas = TareasController::obtenerTareasPorMateria($idAsignacion);
$recursos = RecursosController::obtenerRecursosPorMateria($idAsignacion);
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
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
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

      <!-- 🔸 CARDS PRINCIPALES -->
      <div class="materia-panel">
        <!-- Subir Tareas -->
        <div class="card">
          <i class="fa-solid fa-file-upload"></i>
          <h3>Subir Tareas</h3>
          <p>Publica nuevas tareas para tus alumnos.</p>
          <a href="subirTarea.php?id=<?= $idAsignacion ?>" class="btn">Ir</a>
        </div>

        <!-- Calificar Tareas -->
        <div class="card">
          <i class="fa-solid fa-clipboard-check"></i>
          <h3>Calificar Tareas</h3>
          <p>Consulta y evalúa las tareas entregadas.</p>
          <a href="#" class="btn">Ir</a>
        </div>

        <!-- Subir Recursos -->
        <div class="card">
          <i class="fa-solid fa-folder-plus"></i>
          <h3>Subir Recursos</h3>
          <p>Agrega materiales o archivos de apoyo al grupo.</p>
          <a href="subirRecurso.php?id=<?= $idAsignacion ?>" class="btn">Ir</a>
        </div>
      </div>

      <!-- 🔹 SECCIÓN TAREAS PUBLICADAS -->
      <div class="tareas-section">
        <h2><i class="fa-solid fa-tasks"></i> Tareas publicadas</h2>
        <?php if ($tareas && $tareas->num_rows > 0): ?>
          <div class="tareas-list">
            <?php while ($t = $tareas->fetch_assoc()): ?>
              <div class="tarea-card">
                <h4><?= htmlspecialchars($t['titulo']) ?></h4>
                <p><?= htmlspecialchars($t['descripcion']) ?></p>
                <p><strong>Entrega:</strong> <?= $t['fecha_entrega'] ?: 'Sin fecha' ?></p>

                <?php if (!empty($t['archivo'])): ?>
                  <a class="archivo" href="/Plataforma_UT/<?= htmlspecialchars($t['archivo']) ?>" target="_blank">
                    <i class="fa-solid fa-file-arrow-down"></i> Descargar
                  </a>
                <?php endif; ?>

                <div class="tarea-actions">
                  <a href="editarTarea.php?id=<?= $t['id_tarea'] ?>" class="btn-edit">
                    <i class="fa-solid fa-pen"></i> Editar
                  </a>
                  <a href="eliminarTarea.php?id=<?= $t['id_tarea'] ?>&asig=<?= $idAsignacion ?>" 
                     class="btn-delete"
                     onclick="return confirm('¿Seguro que deseas eliminar esta tarea?');">
                    <i class="fa-solid fa-trash"></i> Eliminar
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="no-tareas">Aún no se han publicado tareas para esta materia.</p>
        <?php endif; ?>
      </div>

      <!-- 🔹 SECCIÓN RECURSOS PUBLICADOS -->
      <div class="tareas-section">
        <h2><i class="fa-solid fa-folder-open"></i> Recursos publicados</h2>
        <?php if ($recursos && $recursos->num_rows > 0): ?>
          <div class="tareas-list">
            <?php while ($r = $recursos->fetch_assoc()): ?>
              <div class="tarea-card">
                <h4><?= htmlspecialchars($r['titulo']) ?></h4>
                <p><?= htmlspecialchars($r['descripcion']) ?></p>
                <p><strong>Fecha:</strong> <?= $r['fecha_creacion'] ?></p>

                <?php if (!empty($r['archivo'])): ?>
                  <a class="archivo" href="/Plataforma_UT/<?= htmlspecialchars($r['archivo']) ?>" target="_blank">
                    <i class="fa-solid fa-file-arrow-down"></i> Descargar
                  </a>
                <?php endif; ?>

                <div class="tarea-actions">
                  <!-- 🟢 BOTÓN EDITAR AÑADIDO -->
                  <a href="editarRecurso.php?id=<?= $r['id_recurso'] ?>&asig=<?= $idAsignacion ?>" class="btn-edit">
                    <i class="fa-solid fa-pen"></i> Editar
                  </a>

                  <!-- 🗑️ BOTÓN ELIMINAR -->
                  <a href="eliminarRecurso.php?id=<?= $r['id_recurso'] ?>&asig=<?= $idAsignacion ?>" 
                     class="btn-delete"
                     onclick="return confirm('¿Seguro que deseas eliminar este recurso?');">
                    <i class="fa-solid fa-trash"></i> Eliminar
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="no-tareas">Aún no se han publicado recursos para esta materia.</p>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
