<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/ChatController.php";

$usuario = $_SESSION['usuario'] ?? [];
$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chats | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/dashboard_chat.css">
  <link rel="stylesheet" href="../../css/docentes/chat_docentes.css">
</head>
<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu"><div class="menu-heading">Menú</div></div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <div class="materia-navbar">
        <div class="materia-info">
          <h2><i class="fa-solid fa-comments"></i> Chats</h2>
          <p>Comunícate con tus alumnos en tiempo real.</p>
        </div>
        <div class="user-info">
          <i class="fa-solid fa-user-tie"></i>
          <span><?= htmlspecialchars($usuarioNombre) ?></span>
        </div>
      </div>

      <div class="chat-dashboard">
        <div class="chat-sidebar">
          <h3><i class="fa-solid fa-users"></i> Conversaciones</h3>
          <div class="chat-list">
            <!-- Aquí se listarán los chats activos -->
            <p class="placeholder">Aún no hay conversaciones.</p>
          </div>
        </div>

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

  <!-- JS  js para el chat-->
   <script src="/Plataforma_UT/js/docentes/chat_docente.js"></script>

  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol']); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
