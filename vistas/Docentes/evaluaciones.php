<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/EvaluacionesController.php";

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'docente';

// 🔹 Obtener evaluaciones (proyectos/exámenes)
$evaluaciones = EvaluacionesController::obtenerEvaluaciones($idDocente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📘 Evaluaciones Académicas | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Docentes/evaluaciones.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
  <div class="container">
    <!-- 🧭 Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- 📘 Contenido principal -->
    <main class="main-content">
      <header class="evaluaciones-header">
        <h2><i class="fa-solid fa-clipboard-list"></i> Evaluaciones Académicas</h2>
        <p>Sube y gestiona tus exámenes, proyectos finales o materiales de evaluación.</p>
        <button class="btn-subir" id="abrirModal"><i class="fa-solid fa-upload"></i> Subir nueva evaluación</button>
      </header>

      <!-- 📚 Lista de evaluaciones -->
      <section class="evaluaciones-section">
        <?php if (!empty($evaluaciones)): ?>
          <div class="evaluaciones-lista">
            <?php foreach ($evaluaciones as $ev): ?>
              <div class="evaluacion-item">
                <div class="evaluacion-info">
                  <h3><i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($ev['titulo']) ?></h3>
                  <p><strong>Tipo:</strong> <?= htmlspecialchars($ev['tipo']) ?></p>
                  <p><strong>Materia:</strong> <?= htmlspecialchars($ev['materia']) ?></p>
                  <p><strong>Fecha:</strong> <?= htmlspecialchars($ev['fecha']) ?></p>
                </div>
                <div class="evaluacion-actions">
                  <a href="/Plataforma_UT/uploads/evaluaciones/<?= htmlspecialchars($ev['archivo']) ?>" target="_blank" class="btn-ver">
                    <i class="fa-solid fa-eye"></i> Ver archivo
                  </a>
                  <button class="btn-eliminar" data-id="<?= $ev['id_evaluacion'] ?>">
                    <i class="fa-solid fa-trash"></i> Eliminar
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty">
            <i class="fa-solid fa-folder-open"></i> 
            No has subido evaluaciones aún.
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <!-- 🪄 Modal Subida -->
  <div class="modal" id="modalSubida">
    <div class="modal-content">
      <span class="close-btn" id="cerrarModal">&times;</span>
      <h3><i class="fa-solid fa-upload"></i> Subir Evaluación</h3>
      <form id="formSubirEvaluacion" enctype="multipart/form-data" method="POST" action="/Plataforma_UT/controladores/docentes/EvaluacionesController.php">
        <div class="form-group">
          <label for="titulo">Título:</label>
          <input type="text" name="titulo" id="titulo" required>
        </div>
        <div class="form-group">
          <label for="tipo">Tipo de evaluación:</label>
          <select name="tipo" id="tipo" required>
            <option value="Examen">Examen</option>
            <option value="Proyecto Final">Proyecto Final</option>
            <option value="Otro">Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label for="archivo">Archivo (PDF o ZIP):</label>
          <input type="file" name="archivo" id="archivo" accept=".pdf,.zip" required>
        </div>
        <button type="submit" name="accion" value="subir" class="btn-primary full">
          <i class="fa-solid fa-upload"></i> Subir Evaluación
        </button>
      </form>
    </div>
  </div>

  <!-- 🧠 JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>

  <script>
    // Modal funcionalidad
    const modal = document.getElementById('modalSubida');
    document.getElementById('abrirModal').onclick = () => modal.classList.add('active');
    document.getElementById('cerrarModal').onclick = () => modal.classList.remove('active');
    window.onclick = e => { if (e.target === modal) modal.classList.remove('active'); };
  </script>
</body>
</html>
