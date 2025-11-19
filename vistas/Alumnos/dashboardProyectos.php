<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";
include_once __DIR__ . "/../../controladores/alumnos/ProyectosAlumnoController.php";

$idAlumno   = $_SESSION['usuario']['id_alumno'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'alumno';
$usuario    = $_SESSION['usuario'] ?? [];
$nombre     = $usuario['nombre'] ?? 'Alumno';
$apellido   = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = trim($nombre.' '.$apellido);

$mensajeEntrega = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_evaluacion']) && isset($_FILES['archivo_proyecto'])) {
  $idEval = (int)($_POST['id_evaluacion'] ?? 0);
  if ($idEval > 0 && $idAlumno > 0) {
    $res = ProyectosAlumnoController::subirEntregaProyecto($idEval, $idAlumno, $_FILES['archivo_proyecto']);
    $mensajeEntrega = $res['mensaje'];
  } else {
    $mensajeEntrega = "❌ No se pudo identificar el proyecto o el alumno.";
  }
}

$proyectos = ProyectosAlumnoController::obtenerProyectosAlumno($idAlumno);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📁 Proyectos Finales | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/alumnos/dashboard_proyectos.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- Main -->
    <main class="main-content">
      <?php if (!empty($mensajeEntrega)): ?>
        <div class="alert-message"><?= htmlspecialchars($mensajeEntrega) ?></div>
      <?php endif; ?>

      <header class="proy-header">
        <div>
          <h2><i class="fa-solid fa-folder-open"></i> Proyectos Finales</h2>
          <p>Revisa las instrucciones, descarga el archivo del docente y entrega tu proyecto antes del cierre.</p>
        </div>
      </header>

      <!-- Filtros -->
      <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">Todos</button>
        <button class="filter-btn" data-filter="proy-activo">Activos</button>
        <button class="filter-btn" data-filter="proy-cerrado">Cerrados</button>
        <button class="filter-btn" data-filter="proy-entregado">Entregados</button>
        <button class="filter-btn" data-filter="proy-devuelto">Devueltos</button>
      </div>

      <?php if ($proyectos && $proyectos->num_rows > 0): ?>
        <section class="section-content">
          <div class="proy-lista">
            <?php while ($p = $proyectos->fetch_assoc()): ?>
              <?php
                $cerrado = false;
                if (!empty($p['fecha_cierre'])) {
                  $ahora  = new DateTime('now', new DateTimeZone('America/Mexico_City'));
                  $cierre = new DateTime($p['fecha_cierre'], new DateTimeZone('America/Mexico_City'));
                  $cerrado = ($ahora > $cierre);
                }

                // Estado visual para filtro
                if ($cerrado) {
                  $estadoClase = 'proy-cerrado';
                  $estadoTexto = '🚫 Cerrado';
                } elseif ($p['estado'] === 'Devuelta') {
                  $estadoClase = 'proy-devuelto';
                  $estadoTexto = '🔄 Devuelto para corrección';
                } elseif (!empty($p['calificacion'])) {
                  $estadoClase = 'proy-calificado';
                  $estadoTexto = '⭐ Calificado';
                } elseif (!empty($p['fecha_envio'])) {
                  // Diferenciamos reentrega
                  if ($p['estado'] === 'Reentregada') {
                    $estadoClase = 'proy-reentregado';
                    $estadoTexto = '♻️ Reentregado';
                  } else {
                    $estadoClase = 'proy-entregado';
                    $estadoTexto = '✅ Entregado';
                  }
                } else {
                  $estadoClase = 'proy-activo';
                  $estadoTexto = '📬 Pendiente';
                }

                $archivoDocHref = !empty($p['archivo_docente']) ? "/Plataforma_UT/".ltrim($p['archivo_docente'],'/\\') : '';
              ?>
              <div class="proy-item <?= $estadoClase ?>">
                <div class="proy-info">
                  <h3><?= htmlspecialchars($p['titulo']) ?></h3>
                  <p><strong>Materia:</strong> <?= htmlspecialchars($p['materia'] ?? '—') ?></p>
                  <p><strong>Docente:</strong> <?= htmlspecialchars(($p['nombre_docente'] ?? '').' '.($p['apellido_docente'] ?? '')) ?></p>
                  <p><strong>Cierra:</strong> <?= htmlspecialchars($p['fecha_cierre'] ? date('d/m/Y H:i', strtotime($p['fecha_cierre'])) : '—') ?></p>
                  <?php if (!empty($p['descripcion'])): ?>
                    <div class="proy-desc"><strong>Instrucciones:</strong> <?= nl2br(htmlspecialchars($p['descripcion'])) ?></div>
                  <?php endif; ?>
                  <p class="estado"><?= $estadoTexto ?></p>

                  <?php if ($p['calificacion'] !== null || !empty($p['retroalimentacion'])): ?>
                    <div class="info-docente">
                      <div class="head">
                        <h4><i class="fa-solid fa-chalkboard-user"></i> Retroalimentación</h4>
                        <?php if ($p['calificacion'] !== null): ?>
                          <div class="score"><?= htmlspecialchars($p['calificacion']) ?>/100</div>
                        <?php endif; ?>
                      </div>
                      <p><?= nl2br(htmlspecialchars($p['retroalimentacion'] ?? '')) ?></p>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="proy-actions">
                  <?php if (!empty($archivoDocHref)): ?>
                    <a class="btn-ver" href="<?= htmlspecialchars($archivoDocHref) ?>" target="_blank">
                      <i class="fa-solid fa-file"></i> Ver archivo
                    </a>
                  <?php endif; ?>

                  <!-- Mi entrega anterior -->
                  <?php if (!empty($p['fecha_envio'])): ?>
                    <div class="archivo-previo">
                      <i class="fa-solid fa-paperclip"></i>
                      <strong>Mi entrega:</strong>
                      <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($p['fecha_envio']))) ?></span>
                    </div>
                  <?php endif; ?>

                  <!-- Form de entrega -->
                  <?php
                    $puedeEntregar = !$cerrado && ($p['calificacion'] === null); // cerrado duro, y si no está calificado
                    // permitir reentrega si Devuelta
                    if ($p['estado'] === 'Devuelta' && !$cerrado) $puedeEntregar = true;
                  ?>

                  <?php if ($puedeEntregar): ?>
                    <div class="entrega-box">
                      <h4><i class="fa-solid fa-upload"></i> Entregar proyecto</h4>
                      <div class="pill <?= $cerrado ? 'blocked' : 'ok' ?>">
                        <?php if ($cerrado): ?>
                          <i class="fa-solid fa-ban"></i> Cerrado
                        <?php else: ?>
                          <i class="fa-regular fa-circle-check"></i> Dentro del tiempo
                        <?php endif; ?>
                      </div>

                      <form method="POST" enctype="multipart/form-data" onsubmit="return validarProyecto(this)">
                        <input type="hidden" name="id_evaluacion" value="<?= (int)$p['id_evaluacion'] ?>">
                        <div class="entrega-row">
                          <input type="file" name="archivo_proyecto" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar" required>
                          <button type="submit" class="btn-enviar">
                            <i class="fa-solid fa-paper-plane"></i> Enviar
                          </button>
                        </div>
                        <div class="nota-tolerancia">Máx. 20MB. Formatos: PDF, DOC(X), PPT(X), JPG/PNG, ZIP/RAR.</div>
                      </form>
                    </div>
                  <?php elseif ($cerrado): ?>
                    <div class="entrega-box">
                      <div class="pill blocked"><i class="fa-solid fa-lock"></i> Entrega cerrada</div>
                    </div>
                  <?php else: ?>
                    <div class="entrega-box">
                      <div class="pill"><i class="fa-solid fa-circle-info"></i> Ya enviaste o está calificado.</div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </section>
      <?php else: ?>
        <p class="empty">No hay proyectos publicados por tus docentes.</p>
      <?php endif; ?>
    </main>
  </div>

  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>
  <script>
    function validarProyecto(form){
      const f = form.querySelector('input[type="file"]').files[0];
      if(!f){ alert('Selecciona un archivo.'); return false; }
      const max = 20 * 1024 * 1024;
      if(f.size > max){ alert('El archivo supera 20MB.'); return false; }
      return true;
    }

    // Filtros
    document.addEventListener('DOMContentLoaded', () => {
      const btns = document.querySelectorAll('.filter-btn');
      const cards = document.querySelectorAll('.proy-item');
      btns.forEach(b => b.addEventListener('click', () => {
        btns.forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        const f = b.dataset.filter;
        cards.forEach(c => {
          c.style.display = (f === 'all' || c.classList.contains(f)) ? 'flex' : 'none';
        });
      }));
    });
  </script>
</body>
</html>
