<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/alumnos/AjustesController.php";

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nueva = $_POST['nueva_contra'] ?? '';
  $confirmar = $_POST['confirmar_contra'] ?? '';
  $idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;

  $mensaje = AjustesController::cambiarPassword($idAlumno, $nueva, $confirmar);
}

$rolUsuario = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>⚙️ Ajustes | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css" />
  <link rel="stylesheet" href="/Plataforma_UT/css/alumnos/dashboard_ajustes.css" />
</head>
<body>
  <div class="container">
    <!-- 🧭 Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- 📄 Contenido Principal -->
    <main class="main-content">
      <header class="ajustes-header">
        <h2><i class="fa-solid fa-gear"></i> Ajustes del Alumno</h2>
        <p>Administra tu cuenta y cambia tu contraseña de acceso.</p>
      </header>

      <?php if ($mensaje): ?>
        <div class="alert-message"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <section class="ajustes-section">
        <div class="ajuste-card">
          <h3><i class="fa-solid fa-lock"></i> Cambiar Contraseña</h3>
          <form method="POST" class="ajuste-form">
            <div class="form-group">
              <label>Nueva Contraseña</label>
              <input type="password" name="nueva_contra" placeholder="Ingresa tu nueva contraseña" required>
            </div>
            <div class="form-group">
              <label>Confirmar Contraseña</label>
              <input type="password" name="confirmar_contra" placeholder="Confirma tu nueva contraseña" required>
            </div>
            <button type="submit" class="btn-guardar">
              <i class="fa-solid fa-save"></i> Guardar Cambios
            </button>
          </form>
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
