<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/GrupoController.php";

// 🔹 Obtener ID del grupo
$idGrupo = intval($_GET['id'] ?? 0);

// 🔹 Asegurar que obtenemos correctamente el ID del docente
$idDocente = intval($_SESSION['id_docente'] ?? ($_SESSION['usuario']['id_docente'] ?? 0));

// 🔹 Obtener info del grupo
$grupoInfo = GrupoController::obtenerInfoGrupo($idGrupo);

// 🔹 Obtener alumnos y materias asignadas
$alumnos = GrupoController::obtenerAlumnosPorGrupo($idGrupo);
$materias = GrupoController::obtenerMateriasPorDocenteYGrupo($idDocente, $idGrupo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($grupoInfo['nombre_grupo'] ?? 'Grupo') ?> | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/dashboard_grupo.css">
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

    <!-- CONTENIDO -->
    <div class="main-content">
      <div class="materia-navbar">
        <div class="materia-info">
          <h2><i class="fa-solid fa-users"></i> <?= htmlspecialchars($grupoInfo['nombre_grupo'] ?? 'Grupo sin nombre') ?></h2>
          <p><strong>Semestre:</strong> <?= htmlspecialchars($grupoInfo['semestre'] ?? '—') ?></p>
        </div>
        <div class="user-info">
          <i class="fa-solid fa-user-tie"></i>
          <span><?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Docente') ?></span>
        </div>
      </div>

      <!-- 🔹 SECCIÓN DE MATERIAS -->
      <section class="materias-section">
        <h2><i class="fa-solid fa-book"></i> Materias Asignadas</h2>
        <?php if (!empty($materias)): ?>
          <div class="materias-grid">
            <?php foreach ($materias as $m): ?>
              <div class="materia-card">
                <i class="fa-solid fa-book-open"></i>
                <h4><?= htmlspecialchars($m['nombre_materia']) ?></h4>
                <p><?= htmlspecialchars($m['codigo_materia']) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="no-materias">No tienes materias asignadas en este grupo.</p>
        <?php endif; ?>
      </section>

      <!-- 🔹 SECCIÓN DE ALUMNOS -->
      <section class="alumnos-section">
        <h2><i class="fa-solid fa-user-graduate"></i> Alumnos del Grupo</h2>
        <?php if (!empty($alumnos)): ?>
          <table class="tabla-alumnos">
            <thead>
              <tr>
                <th>Matrícula</th>
                <th>Nombre completo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($alumnos as $a): ?>
                <tr>
                  <td><?= htmlspecialchars($a['matricula']) ?></td>
                  <td>
                    <?= htmlspecialchars(($a['nombre'] ?? '') . ' ' . ($a['apellido_paterno'] ?? '') . ' ' . ($a['apellido_materno'] ?? '')) ?>
                  <</td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="no-alumnos">No hay alumnos registrados en este grupo.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
