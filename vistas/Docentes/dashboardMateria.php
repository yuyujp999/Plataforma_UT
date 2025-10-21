<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/docentes/docentecontroller.php";

$idAsignacion = intval($_GET['id'] ?? 0);
$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [];
$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));

// Obtener datos de la materia seleccionada
$conexion = $conn;
$sql = "
  SELECT 
    cnm.nombre AS codigo_materia,
    m.nombre_materia,
    cng.nombre AS grupo
  FROM asignaciones_docentes AS ad
  INNER JOIN cat_nombres_materias AS cnm
    ON ad.id_nombre_materia = cnm.id_nombre_materia
  LEFT JOIN asignar_materias AS am
    ON ad.id_nombre_materia = am.id_nombre_materia
  LEFT JOIN materias AS m
    ON am.id_materia = m.id_materia
  LEFT JOIN cat_nombres_grupo AS cng
    ON am.id_nombre_grupo_int = cng.id_nombre_grupo
  WHERE ad.id_asignacion_docente = $idAsignacion
  LIMIT 1
";
$materia = $conexion->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($materia['nombre_materia'] ?? 'Materia') ?> | UT Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/materia_docente.css">
  <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
</head>
<body>
  <div class="container">
    <!-- 🧭 SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
      </div>
    </div>

    <!-- 🧩 CONTENIDO PRINCIPAL -->
    <div class="main-content">

      <!-- 🔹 NAVBAR SUPERIOR DE MATERIA -->
      <div class="top-navbar">
        <div class="materia-info">
          <h2><?= htmlspecialchars($materia['nombre_materia'] ?? 'Materia') ?></h2>
          <span class="codigo"><?= htmlspecialchars($materia['codigo_materia'] ?? '') ?> - <?= htmlspecialchars($materia['grupo'] ?? '') ?></span>
        </div>
        <div class="docente-info">
          <i class="fa-solid fa-user"></i>
          <span><?= htmlspecialchars($usuarioNombre) ?></span>
        </div>
      </div>

      <!-- 🔙 BOTÓN VOLVER -->
      <a href="dashboardDocente.php" class="btn-outline"><i class="fa-solid fa-arrow-left"></i> Volver</a>

      <!-- 🔸 PANEL DE OPCIONES -->
      <div class="materia-panel">
        <div class="card">
          <i class="fa-solid fa-upload"></i>
          <h3>Subir Recursos</h3>
          <p>Agrega archivos y material para tus alumnos.</p>
          <a href="#" class="btn">Ir</a>
        </div>

        <div class="card">
          <i class="fa-solid fa-clipboard-check"></i>
          <h3>Calificar Tareas</h3>
          <p>Consulta las tareas entregadas por tus alumnos.</p>
          <a href="#" class="btn">Ir</a>
        </div>

        <div class="card">
          <i class="fa-solid fa-comments"></i>
          <h3>Comunicados</h3>
          <p>Envía avisos o mensajes al grupo.</p>
          <a href="#" class="btn">Ir</a>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
