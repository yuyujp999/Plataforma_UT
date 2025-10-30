<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}
include_once __DIR__ . '/../../conexion/conexion.php';
$base = "/Plataforma_UT";
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>⚙️ Ajustes del Docente | UT Panel</title>

  <link rel="stylesheet" href="<?= $base ?>/css/styleD.css">
  <link rel="stylesheet" href="<?= $base ?>/css/docentes/DashboardAjustes.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo">
        <h1>UT<span>Panel</span></h1>
      </div>
      <div class="nav-menu" id="menu"></div>
    </aside>

    <!-- Header -->
    <header class="header">
      <div class="page-title">
        <h2 class="title"><i class="fa-solid fa-gear"></i> Ajustes del Docente</h2>
      </div>
      <div class="user-profile">
        <div class="profile-img"><i class="fa-solid fa-user"></i></div>
        <div class="user-info">
          <span class="user-name"><?= $_SESSION['usuario']['nombre'] ?? 'Docente' ?></span>
          <span class="user-role">Docente</span>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="main-content">
      <section class="content-area ajustes-lista">
        <div class="ajuste-item">
          <div class="ajuste-info">
            <i class="fa-solid fa-lock"></i>
            <span>Cambiar contraseña</span>
          </div>
          <button class="btn btn-outline" id="abrirModal">
            <i class="fa-solid fa-pen"></i> Abrir
          </button>
        </div>
      </section>
    </main>
  </div>

<!-- Modal para cambiar contraseña -->
<div id="modalPassword" class="modal">
  <div class="modal-content">
    <h3><i class="fa-solid fa-key"></i> Cambiar contraseña</h3>

    <form id="formPassword">
      <div class="form-group">
        <label for="actual">Contraseña actual</label>
        <input type="password" id="actual" name="actual" placeholder="Ingresa tu contraseña actual">
      </div>

      <div class="form-group password-toggle">
        <label for="nueva">Nueva contraseña</label>
        <div class="input-wrapper">
          <input type="password" id="nueva" name="nueva" placeholder="Ingresa tu nueva contraseña">
          <button type="button" class="toggle-visibility" data-target="nueva">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group password-toggle">
        <label for="confirmar">Confirmar nueva contraseña</label>
        <div class="input-wrapper">
          <input type="password" id="confirmar" name="confirmar" placeholder="Vuelve a escribir la nueva contraseña">
          <button type="button" class="toggle-visibility" data-target="confirmar">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" id="cerrarModal" class="btn btn-outline">
          <i class="fa-solid fa-xmark"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-save"></i> Guardar cambios
        </button>
      </div>
      <p id="msgPassword" class="msg"></p>
    </form>
  </div>
</div>


  <script>
    window.rolUsuarioPHP = "docente";
  </script>
  <script src="<?= $base ?>/js/Dashboard_Inicio.js"></script>

  <script>
    const modal = document.getElementById("modalPassword");
    const abrir = document.getElementById("abrirModal");
    const cerrar = document.getElementById("cerrarModal");

    abrir.addEventListener("click", () => modal.classList.add("active"));
    cerrar.addEventListener("click", () => modal.classList.remove("active"));
    window.addEventListener("click", (e) => { if (e.target === modal) modal.classList.remove("active"); });

    document.getElementById("formPassword").addEventListener("submit", async (e) => {
      e.preventDefault();
      const actual = document.getElementById("actual").value.trim();
      const nueva = document.getElementById("nueva").value.trim();
      const confirmar = document.getElementById("confirmar").value.trim();
      const msg = document.getElementById("msgPassword");

      if (!actual || !nueva || !confirmar) {
        msg.textContent = "Por favor completa todos los campos.";
        msg.className = "msg error";
        return;
      }
      if (nueva !== confirmar) {
        msg.textContent = "Las contraseñas no coinciden.";
        msg.className = "msg error";
        return;
      }

      const formData = new FormData();
      formData.append("actual", actual);
      formData.append("nueva", nueva);

      const response = await fetch("<?= $base ?>/controladores/docentes/AjustesController.php?action=cambiarPassword", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();
      msg.textContent = data.mensaje;
      msg.className = data.success ? "msg success" : "msg error";
      if (data.success) e.target.reset();
    });
    // === Mostrar / ocultar contraseñas ===
document.querySelectorAll(".toggle-visibility").forEach(btn => {
  btn.addEventListener("click", () => {
    const input = document.getElementById(btn.dataset.target);
    const icon = btn.querySelector("i");

    if (input.type === "password") {
      input.type = "text";
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    } else {
      input.type = "password";
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
  });
});

  </script>
</body>
</html>
