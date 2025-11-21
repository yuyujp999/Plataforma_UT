<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/ExamenesController.php";

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'docente';

$envios = ExamenesController::listarEnviosDocente($idDocente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📊 Resultados de exámenes | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Docentes/examenes.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
<div class="container">
  <aside class="sidebar" id="sidebar">
    <div class="logo"><h1>UT<span>Panel</span></h1></div>
    <nav class="nav-menu" id="menu"></nav>
  </aside>

  <main class="main-content">
    <header class="evaluaciones-header">
      <div>
        <h2><i class="fa-solid fa-square-poll-horizontal"></i> Resultados de exámenes</h2>
        <p>Exámenes enviados por tus alumnos.</p>
      </div>
    </header>

    <section class="evaluaciones-section">
      <?php if (!empty($envios)): ?>
        <div class="tabla-responsive">
          <table class="tabla-examenes">
            <thead>
              <tr>
                <th>Examen</th>
                <th>Materia</th>
                <th>Alumno</th>
                <th>Matrícula</th>
                <th>Respuestas</th>
                <th>Último envío</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($envios as $e): ?>
              <tr>
                <td><?= htmlspecialchars($e['titulo']) ?></td>
                <td><?= htmlspecialchars($e['materia']) ?></td>
                <td><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido_paterno']) ?></td>
                <td><?= htmlspecialchars($e['matricula']) ?></td>
                <td><?= (int)$e['preguntas_respondidas'] ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($e['ultima_respuesta']))) ?></td>
                <td>
                  <a class="btn-ver"
                     href="/Plataforma_UT/vistas/Docentes/examen_intento.php?id_examen=<?= (int)$e['id_examen'] ?>&id_alumno=<?= (int)$e['id_alumno'] ?>">
                    <i class="fa-solid fa-eye"></i> Ver respuestas
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <i class="fa-solid fa-folder-open"></i>
          Aún no hay exámenes enviados por tus alumnos.
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>

<script>
  window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>

<style>
  .tabla-responsive { overflow-x:auto; }
  .tabla-examenes {
    width: 100%;
    border-collapse: collapse;
    background: var(--card-bg, #fff);
    border-radius: 10px;
    overflow: hidden;
  }
  .tabla-examenes th,
  .tabla-examenes td {
    padding: 10px 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.9rem;
  }
  .tabla-examenes th {
    background: #f1f5f9;
    text-align: left;
  }
  .tabla-examenes tr:hover {
    background: #f9fafb;
  }
</style>
</body>
</html>
