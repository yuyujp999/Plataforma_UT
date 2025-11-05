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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chats | UT Panel Alumno</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/alumnos/dashboard_alumnos.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/alumnos/ChatsAlumnos.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/docentes/dashboard_chat.css">
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

    <!-- 📩 CONTENIDO PRINCIPAL: CHAT -->
     <div class="main-content">
  <!-- 🔹 Barra superior con título y usuario -->
  <div class="materia-navbar">
    <div class="materia-info">
      <h2><i class="fa-solid fa-comments"></i> Chats</h2>
      <p>Comunícate con tus docentes en tiempo real.</p>
    </div>
    <div class="user-info">
      <i class="fa-solid fa-user-graduate"></i>
      <span><?= htmlspecialchars($usuarioNombre) ?></span>
    </div>
  </div>

  <!-- 🔹 Panel del chat -->
  <div class="chat-dashboard">
    <!-- Sidebar de docentes -->
    <div class="chat-sidebar">
      <h3><i class="fa-solid fa-user-tie"></i> Conversaciones</h3>
      <div class="chat-list">
        <p class="placeholder">Cargando docentes...</p>
      </div>
    </div>

    <!-- Área principal del chat -->
    <div class="chat-main">
      <div class="chat-header">
        <h3>Selecciona una conversación</h3>
      </div>

      <div class="chat-body">
        <p class="placeholder">Selecciona un chat para comenzar a chatear.</p>
      </div>

      <div class="chat-input">
        <input type="text" placeholder="Escribe un mensaje..." disabled>
        <button disabled><i class="fa-solid fa-paper-plane"></i></button>
      </div>
    </div>
  </div>
</div>





  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
  <script src="/Plataforma_UT/js/alumnos/chat_alumno.js"></script>
</body>
</html>
