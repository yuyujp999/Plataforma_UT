<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

$rolUsuario = $_SESSION['rol'] ?? '';
$usuario = $_SESSION['usuario'] ?? [
  'id_docente' => null,
  'nombre' => 'Docente',
  'apellido_paterno' => '',
  'apellido_materno' => ''
];

$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));

$idDocente = $_SESSION["id_docente"] ?? ($usuario["id_docente"] ?? null);

// Importar controlador
include_once __DIR__ . "/../../controladores/docentes/DocenteController.php";
$resultado = $idDocente ? DocenteController::obtenerMateriasAsignadas($idDocente) : false;
$grupos = $idDocente ? DocenteController::obtenerGruposAsignados($idDocente) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UT Panel | Docente</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/dashboard_docente.css">
  <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
</head>
<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu"><div class="menu-heading">Menú</div></div>
    </div>

    <!-- HEADER -->
    <div class="header">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar..." />
      </div>
      <div class="header-actions">
        <div class="notification"><i class="fas fa-bell"></i><div class="badge">3</div></div>
        <div class="notification"><i class="fas fa-envelope"></i><div class="badge">5</div></div>
        <div class="user-profile">
          <div class="profile-img"><?= htmlspecialchars($iniciales) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($usuarioNombre) ?></div>
            <div class="user-role">Docente</div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <div class="page-title">
        <div class="title">Dashboard Docente</div>
      </div>

      <!-- =====================
           📚 MATERIAS ASIGNADAS
      ====================== -->
      <h3 style="margin-bottom: 20px;">Mis Materias Asignadas</h3>

      <div class="materias-modern-grid">
        <?php
        if ($resultado && $resultado->num_rows > 0) {
          while ($fila = $resultado->fetch_assoc()) {
            $idMateria = $fila['id_asignacion_docente'];
            $codigo = htmlspecialchars($fila['codigo_materia']);
            $nombreMat = htmlspecialchars($fila['nombre_materia']);
            $grupo = htmlspecialchars($fila['grupo']);

            echo "
              <a href='dashboardMateria.php?id=$idMateria' class='materia-modern-card'>
                <div class='materia-modern-bg'></div>
                <div class='materia-modern-icon'><i class='fa-solid fa-book-open'></i></div>
                <div class='materia-modern-content'>
                  <h4>$codigo - $grupo</h4>
                  <p>$nombreMat</p>
                </div>
              </a>
            ";
          }
        } else {
          echo "<p class='no-asignaciones'>No tienes asignaciones registradas aún.</p>";
        }
        ?>
      </div>

      <!-- =====================
           👥 GRUPOS ASIGNADOS
      ====================== -->
      <section class="grupos-section">
        <h2><i class="fa-solid fa-users"></i> Grupos Asignados</h2>
        <?php if (!empty($grupos)): ?>
          <div class="grupos-modern-grid">
            <?php foreach ($grupos as $g): ?>
              <a href="dashboardGrupo.php?id=<?= $g['id_grupo'] ?>" class="grupo-modern-card">
                <div class="grupo-modern-bg"></div>
                <div class="grupo-modern-body">
                  <div class="grupo-modern-icon">
                    <i class="fa-solid fa-users"></i>
                  </div>
                  <div class="grupo-modern-content">
                    <h4><?= htmlspecialchars($g['nombre']) ?></h4>
                    <p><?= htmlspecialchars($g['semestre']) ?></p>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="no-grupos">Aún no tienes grupos asignados.</p>
        <?php endif; ?>
      </section>

    </div>
  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
