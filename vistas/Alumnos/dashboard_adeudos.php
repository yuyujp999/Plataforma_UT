<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/alumnos/AdeudosController.php";

$idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'alumno';

// 🔹 Cargar adeudos del alumno (por ahora de ejemplo)
$adeudos = AdeudosController::obtenerAdeudos($idAlumno);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>💰 Adeudos | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Alumnos/dashboard_adeudos.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
  <div class="container">
    <!-- 🧭 Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- 💰 Contenido principal -->
    <main class="main-content">
      <header class="adeudos-header">
        <h2><i class="fa-solid fa-wallet"></i> Mis Adeudos</h2>
        <p>Consulta tus adeudos escolares, pagos pendientes y su estado actual.</p>
      </header>

      <section class="adeudos-section">
        <?php if (!empty($adeudos)): ?>
          <div class="adeudos-lista">
            <?php foreach ($adeudos as $a): ?>
              <div class="adeudo-item <?= $a['estado'] === 'Pagado' ? 'adeudo-pagado' : 'adeudo-pendiente' ?>">
                <div class="adeudo-info">
                  <h3><i class="fa-solid fa-file-invoice-dollar"></i> <?= htmlspecialchars($a['concepto']) ?></h3>
                  <p><strong>Fecha límite:</strong> <?= htmlspecialchars($a['fecha_limite']) ?></p>
                  <p><strong>Monto:</strong> $<?= number_format($a['monto'], 2) ?></p>
                  <p class="estado">
                    Estado:
                    <span class="estado-tag <?= strtolower($a['estado']) ?>">
                      <?= htmlspecialchars($a['estado']) ?>
                    </span>
                  </p>
                </div>
                <div class="adeudo-icon">
                  <i class="fa-solid <?= $a['estado'] === 'Pagado' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty">
            <i class="fa-solid fa-circle-info"></i> 
            No tienes adeudos registrados por el momento.
          </div>
        <?php endif; ?>
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
