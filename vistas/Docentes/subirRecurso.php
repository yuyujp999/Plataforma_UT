<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/RecursosController.php";

$idAsignacion = intval($_GET['id'] ?? 0);
$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [];
$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo = $_POST['titulo'] ?? '';
  $descripcion = $_POST['descripcion'] ?? '';
  $archivo = $_FILES['archivo'] ?? null;

  $resultado = RecursosController::subirRecurso($idAsignacion, $titulo, $descripcion, $archivo);
  $mensaje = $resultado['mensaje'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Subir Recurso | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/recursos_docente.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
</head>
<body>
  <div class="container">
    <!-- 🧭 SIDEBAR dinámica -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
        <!-- El JS inyecta aquí los ítems -->
      </div>
    </div>

    <!-- 📄 CONTENIDO PRINCIPAL -->
    <div class="main-content">
      <!-- 🔹 Encabezado superior -->
      <div class="materia-navbar">
        <div class="materia-info">
          <h2><i class="fa-solid fa-folder-plus"></i> Subir nuevo recurso</h2>
        </div>
        <div class="user-info">
          <i class="fa-solid fa-user-tie"></i>
          <span><?= htmlspecialchars($usuarioNombre) ?></span>
        </div>
      </div>

      <!-- 🔹 FORMULARIO dentro del layout -->
      <div class="content-inner">
        <div class="container-form">
          <?php if (!empty($mensaje)): ?>
            <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
          <?php endif; ?>

          <form action="" method="POST" enctype="multipart/form-data">
            <label>Título del recurso:</label>
            <input type="text" name="titulo" required>

            <label>Descripción (opcional):</label>
            <textarea name="descripcion" rows="4"></textarea>

            <label>Archivo (PDF, Word, Excel):</label>
            <input type="file" name="archivo" id="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
            <small id="archivoNombre" style="color: var(--text-light); font-size: 0.9rem;"></small>

            <button type="submit" class="btn-guardar">
              <i class="fa-solid fa-cloud-upload-alt"></i> Subir Recurso
            </button>
          </form>

          <a href="dashboardMateria.php?id=<?= $idAsignacion ?>" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver a la materia
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- 🔸 SCRIPT: Sidebar dinámica -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";

    // Previsualizar nombre del archivo
    const archivoInput = document.getElementById("archivo");
    const archivoNombre = document.getElementById("archivoNombre");
    if (archivoInput) {
      archivoInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        archivoNombre.textContent = file ? `Archivo seleccionado: ${file.name}` : "";
      });
    }
  </script>

  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
</body>
</html>
