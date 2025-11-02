<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";

$alumno = $_SESSION['usuario'] ?? [];
$idAlumno = $alumno['id_alumno'] ?? null;
$nombre = $alumno['nombre'] ?? 'Alumno';
$apellido = $alumno['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;
$rolUsuario = $_SESSION['rol'] ?? '';

// 🔹 Obtener el grupo del alumno
$sqlGrupo = "
  SELECT g.id_grupo, cng.nombre AS nombre_grupo, cns.nombre AS nombre_semestre
  FROM asignaciones_grupo_alumno aga
  INNER JOIN grupos g ON aga.id_grupo = g.id_grupo
  INNER JOIN cat_nombres_grupo cng ON g.id_nombre_grupo = cng.id_nombre_grupo
  INNER JOIN cat_nombres_semestre cns ON g.id_nombre_semestre = cns.id_nombre_semestre
  WHERE aga.id_alumno = $idAlumno
  LIMIT 1
";
$resGrupo = $conn->query($sqlGrupo);
$grupo = $resGrupo->fetch_assoc();
$idGrupo = $grupo['id_grupo'] ?? null;

// 🔹 Obtener materias asignadas a ese grupo
$materias = [];
if ($idGrupo) {
  $sqlMaterias = "
    SELECT 
      ad.id_asignacion_docente AS id_asignacion,
      m.nombre_materia,
      cnm.nombre AS codigo_materia,
      cng.nombre AS grupo
    FROM asignaciones_docentes ad
    INNER JOIN cat_nombres_materias cnm ON ad.id_nombre_materia = cnm.id_nombre_materia
    LEFT JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
    LEFT JOIN materias m ON am.id_materia = m.id_materia
    LEFT JOIN cat_nombres_grupo cng ON am.id_nombre_grupo_int = cng.id_nombre_grupo
    WHERE am.id_nombre_grupo_int = (
      SELECT g.id_nombre_grupo 
      FROM grupos g
      WHERE g.id_grupo = $idGrupo
    )
  ";
  $materias = $conn->query($sqlMaterias);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UT Panel | Alumno</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/alumnos/dashboard_alumnos.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu"><div class="menu-heading">Menú</div></div>
    </div>

    <!-- HEADER -->
    <div class="header">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar materia...">
      </div>
      <div class="header-actions">
        <div class="notification"><i class="fas fa-bell"></i></div>
        <div class="user-profile">
          <div class="profile-img"><?= strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1)) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($usuarioNombre) ?></div>
            <div class="user-role">Alumno</div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <div class="page-title">
        <div class="title">Mis Materias Asignadas</div>
      </div>

      <div class="grupo-info">
        <?php if ($grupo): ?>
          <h3><i class="fa-solid fa-users"></i> Grupo: <?= htmlspecialchars($grupo['nombre_grupo']) ?></h3>
          <p><i class="fa-solid fa-graduation-cap"></i> Semestre: <?= htmlspecialchars($grupo['nombre_semestre']) ?></p>
        <?php else: ?>
          <p class="no-grupo">Aún no estás asignado a ningún grupo.</p>
        <?php endif; ?>
      </div>

      <div class="materias-grid">
        <?php if ($materias && $materias->num_rows > 0): ?>
          <?php while ($m = $materias->fetch_assoc()): ?>
            <a href="dashboardMateria.php?id=<?= $m['id_asignacion'] ?>" class="materia-card">
              <div class="materia-icon"><i class="fa-solid fa-book"></i></div>
              <div class="materia-info">
                <h4><?= htmlspecialchars($m['nombre_materia']) ?></h4>
                <p><strong>Código:</strong> <?= htmlspecialchars($m['codigo_materia']) ?></p>
                <p><strong>Grupo:</strong> <?= htmlspecialchars($m['grupo']) ?></p>
              </div>
            </a>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="no-materias">No hay materias asignadas a tu grupo.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
