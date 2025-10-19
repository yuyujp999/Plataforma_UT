<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [
  'nombre' => 'Docente',
  'apellido_paterno' => '',
  'apellido_materno' => ''
];

$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UT Panel | Docente</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/dashboard_docente.css">
  <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
</head>

<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo">
        <h1>UT<span>Panel</span></h1>
      </div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
      </div>
    </div>

    <!-- HEADER -->
    <div class="header">
      <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Buscar..." /></div>
      <div class="header-actions">
        <div class="notification"><i class="fas fa-bell"></i>
          <div class="badge">3</div>
        </div>
        <div class="notification"><i class="fas fa-envelope"></i>
          <div class="badge">5</div>
        </div>
        <div class="user-profile">
          <div class="profile-img"><?= htmlspecialchars($iniciales) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($usuarioNombre) ?></div>
            <div class="user-role">Docente</div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN -->
    <div class="main-content">
      <div class="page-title">
        <div class="title">Dashboard Docente</div>
      </div>

      <section class="resumen-docente">
        <h3>Bienvenido, <?= htmlspecialchars($usuarioNombre) ?> 👋</h3>
        <p>Desde aquí puedes acceder a tus asignaturas, tareas y grupos.</p>

        <div class="resumen-cards">
          <div class="card">
            <i class="fas fa-list-check"></i>
            <h4>Mis Tareas</h4>
            <p>Consulta, edita o crea nuevas tareas.</p>
          </div>
          <div class="card">
            <i class="fas fa-chalkboard-teacher"></i>
            <h4>Mis Asignaturas</h4>
            <p>Gestiona tus clases activas y grupos.</p>
          </div>
          <div class="card">
            <i class="fas fa-calendar"></i>
            <h4>Horario</h4>
            <p>Visualiza tus horarios y sesiones.</p>
          </div>
        </div>
      </section>
    </div>
  </div>

  <!-- Rol y JS de menú -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>

</html>