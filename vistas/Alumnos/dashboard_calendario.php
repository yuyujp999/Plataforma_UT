<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

$rolUsuario = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>📅 Calendario Académico | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/Alumnos/dashboard_calendario.css" />
</head>
<body>
  <div class="container">
    <!-- 🧭 Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- 📅 Contenido principal -->
    <main class="main-content">
      <header class="calendario-header">
        <h2><i class="fa-solid fa-calendar-days"></i> Calendario Académico</h2>
        <p>Consulta tus eventos, clases, exámenes y actividades programadas.</p>
      </header>

      <section class="calendario-section">
        <div class="empty">
          <i class="fa-solid fa-hourglass-half"></i> 
          No hay eventos registrados por el momento.
        </div>
      </section>
    </main>
  </div>

  <!-- 🧠 JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const menu = document.getElementById("menu");
      if (!menu || menu.innerHTML.trim() === "") {
        console.warn("⚠️ Sidebar vacía — recargando Dashboard_Inicio.js");
        const script = document.createElement("script");
        script.src = "/Plataforma_UT/js/Dashboard_Inicio.js?v=" + Date.now();
        document.body.appendChild(script);
      }
    });
  </script>
</body>
</html>
