<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/EvaluacionesController.php";

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'docente';

$flash = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

$evaluaciones = EvaluacionesController::obtenerEvaluaciones($idDocente);
$asignaciones = EvaluacionesController::obtenerAsignacionesDocente($idDocente);
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
      <?php if (!empty($flash)): ?>
        <div class="alert-message"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <header class="evaluaciones-header">
        <div>
          <h2><i class="fa-solid fa-clipboard-list"></i> Evaluaciones Académicas</h2>
          <p>Sube y gestiona tus exámenes, proyectos finales o materiales de evaluación.</p>
        </div>
        <button class="btn-subir" id="abrirModal"><i class="fa-solid fa-upload"></i> Subir nueva evaluación</button>
      </header>

      <!-- 📚 Lista de evaluaciones -->
      <section class="evaluaciones-section">
        <?php if (!empty($evaluaciones)): ?>
          <div class="evaluaciones-lista">
            <?php foreach ($evaluaciones as $ev): ?>
              <?php
                $cerrada = (int)($ev['cerrada'] ?? 0) === 1;

                $badge = $cerrada
                  ? '<span class="badge badge-danger">Cerrada</span>'
                  : '<span class="badge badge-ok">Activa</span>';

                $archivoHref = !empty($ev['archivo'])
                  ? '/Plataforma_UT/' . ltrim($ev['archivo'], '/\\')
                  : '';

                // ⏱️ Fecha de cierre: viene con hora en BD, se muestra solo la fecha
                $fechaBruta = $ev['fecha_cierre'] ?? null;
                if (!empty($fechaBruta) && $fechaBruta !== '0000-00-00 00:00:00') {
                  $ts = strtotime($fechaBruta);
                  $fechaCierreTexto = date('d/m/Y', $ts);    // solo fecha para mostrar
                  $fechaCierreInput = date('Y-m-d', $ts);    // solo fecha para el input
                } else {
                  $fechaCierreTexto = '—';
                  $fechaCierreInput = '';
                }
              ?>
              <div class="evaluacion-item <?= $cerrada ? 'is-closed' : '' ?>">
                <div class="evaluacion-info">
                  <h3>
                    <i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($ev['titulo']) ?>
                    <?= $badge ?>
                  </h3>
                  <p><strong>Tipo:</strong> <?= htmlspecialchars($ev['tipo']) ?></p>
                  <p><strong>Materia:</strong> <?= htmlspecialchars($ev['materia'] ?? '—') ?></p>
                  <p><strong>Fecha de cierre:</strong> <?= htmlspecialchars($fechaCierreTexto) ?></p>
                  <?php if (!empty($ev['descripcion'])): ?>
                    <p class="desc"><strong>Instrucciones:</strong> <?= nl2br(htmlspecialchars($ev['descripcion'])) ?></p>
                  <?php endif; ?>
                </div>
                <div class="evaluacion-actions">
                  <?php if (!empty($archivoHref)): ?>
                    <a href="<?= htmlspecialchars($archivoHref) ?>" target="_blank" class="btn-ver">
                      <i class="fa-solid fa-eye"></i> Ver archivo
                    </a>
                  <?php endif; ?>

                  <button class="btn-editar"
                    data-id="<?= (int)$ev['id_evaluacion'] ?>"
                    data-id_asig="<?= (int)$ev['id_asignacion_docente'] ?>"
                    data-titulo="<?= htmlspecialchars($ev['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                    data-tipo="<?= htmlspecialchars($ev['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                    data-descripcion="<?= htmlspecialchars($ev['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-fecha_cierre="<?= htmlspecialchars($fechaCierreInput, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-pen"></i> Editar
                  </button>

                  <form method="POST" action="/Plataforma_UT/controladores/docentes/EvaluacionesController.php" onsubmit="return confirm('¿Eliminar esta evaluación?');">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_evaluacion" value="<?= (int)$ev['id_evaluacion'] ?>">
                    <button type="submit" class="btn-eliminar"><i class="fa-solid fa-trash"></i> Eliminar</button>
                  </form>
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
      <span class="close-btn" data-close="#modalSubida">&times;</span>
      <h3><i class="fa-solid fa-upload"></i> Subir Evaluación</h3>
      <form id="formSubirEvaluacion" enctype="multipart/form-data" method="POST" action="/Plataforma_UT/controladores/docentes/EvaluacionesController.php" onsubmit="return validarAlta(this);">
        <input type="hidden" name="accion" value="subir">
        <div class="form-group">
          <label for="id_asignacion_docente">Materia / Asignación:</label>
          <select name="id_asignacion_docente" id="id_asignacion_docente" required>
            <option value="">Selecciona materia…</option>
            <?php foreach ($asignaciones as $as): ?>
              <option value="<?= (int)$as['id_asignacion_docente'] ?>"><?= htmlspecialchars($as['materia']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="titulo">Título:</label>
          <input type="text" name="titulo" id="titulo" required>
        </div>
        <div class="form-group">
          <label for="tipo">Tipo de evaluación:</label>
          <select name="tipo" id="tipo" required>
            <option value="Proyecto Final">Proyecto Final</option>
            <option value="Examen">Examen</option>
            <option value="Otro">Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label for="descripcion">Instrucciones/Descripción:</label>
          <textarea name="descripcion" id="descripcion" rows="3" placeholder="Indica requisitos, rúbrica, formato…"></textarea>
        </div>
        <div class="form-group">
          <label for="fecha_cierre">Fecha de cierre:</label>
          <input type="date" name="fecha_cierre" id="fecha_cierre" required>
        </div>
        <div class="form-group">
          <label for="archivo">Archivo (PDF o ZIP):</label>
          <input type="file" name="archivo" id="archivo" accept=".pdf,.zip">
        </div>
        <button type="submit" class="btn-primary full">
          <i class="fa-solid fa-upload"></i> Subir Evaluación
        </button>
      </form>
    </div>
  </div>

  <!-- 📝 Modal Edición -->
  <div class="modal" id="modalEditar">
    <div class="modal-content">
      <span class="close-btn" data-close="#modalEditar">&times;</span>
      <h3><i class="fa-solid fa-pen"></i> Editar Evaluación</h3>
      <form id="formEditarEvaluacion" enctype="multipart/form-data" method="POST" action="/Plataforma_UT/controladores/docentes/EvaluacionesController.php" onsubmit="return validarEdicion(this);">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id_evaluacion" id="edit_id_evaluacion">
        <div class="form-group">
          <label for="edit_id_asignacion_docente">Materia / Asignación:</label>
          <select name="id_asignacion_docente" id="edit_id_asignacion_docente" required>
            <option value="">Selecciona materia…</option>
            <?php foreach ($asignaciones as $as): ?>
              <option value="<?= (int)$as['id_asignacion_docente'] ?>"><?= htmlspecialchars($as['materia']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_titulo">Título:</label>
          <input type="text" name="titulo" id="edit_titulo" required>
        </div>
        <div class="form-group">
          <label for="edit_tipo">Tipo de evaluación:</label>
          <select name="tipo" id="edit_tipo" required>
            <option value="Proyecto Final">Proyecto Final</option>
            <option value="Examen">Examen</option>
            <option value="Otro">Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_descripcion">Instrucciones/Descripción:</label>
          <textarea name="descripcion" id="edit_descripcion" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="edit_fecha_cierre">Fecha de cierre:</label>
          <input type="date" name="fecha_cierre" id="edit_fecha_cierre" required>
        </div>
        <div class="form-group">
          <label for="edit_archivo">Reemplazar archivo (PDF o ZIP):</label>
          <input type="file" name="archivo" id="edit_archivo" accept=".pdf,.zip">
        </div>
        <button type="submit" class="btn-primary full">
          <i class="fa-solid fa-save"></i> Guardar cambios
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
    const $ = (q, ctx=document) => ctx.querySelector(q);
    const $$ = (q, ctx=document) => [...ctx.querySelectorAll(q)];

    // Abrir / cerrar modales
    $('#abrirModal')?.addEventListener('click', () => $('#modalSubida')?.classList.add('active'));
    $$('.close-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const t = btn.getAttribute('data-close');
        if (t) $(t)?.classList.remove('active');
        else btn.closest('.modal')?.classList.remove('active');
      });
    });
    window.addEventListener('click', (e) => {
      $$('.modal').forEach(m => { if (e.target === m) m.classList.remove('active'); });
    });

    // Rellenar modal de edición
    $$('.btn-editar').forEach(btn => {
      btn.addEventListener('click', () => {
        $('#edit_id_evaluacion').value = btn.dataset.id || '';
        $('#edit_titulo').value = btn.dataset.titulo || '';
        $('#edit_tipo').value = btn.dataset.tipo || 'Proyecto Final';
        $('#edit_descripcion').value = btn.dataset.descripcion || '';
        $('#edit_fecha_cierre').value = btn.dataset.fecha_cierre || '';
        const asig = btn.dataset.id_asig || '';
        if (asig) $('#edit_id_asignacion_docente').value = asig;
        $('#modalEditar').classList.add('active');
      });
    });

    // Validaciones simples
    function validarAlta(form){
      if(!form.id_asignacion_docente.value){ alert('Selecciona una materia.'); return false; }
      if(!form.titulo.value.trim()){ alert('Escribe un título.'); return false; }
      if(!form.fecha_cierre.value){ alert('Indica la fecha de cierre.'); return false; }
      const f = form.archivo.files[0];
      if (f) {
        const ok = ['pdf','zip'].includes((f.name.split('.').pop()||'').toLowerCase());
        if(!ok){ alert('Solo PDF o ZIP.'); return false; }
      }
      return true;
    }
    function validarEdicion(form){
      if(!form.id_asignacion_docente.value){ alert('Selecciona una materia.'); return false; }
      if(!form.titulo.value.trim()){ alert('Escribe un título.'); return false; }
      if(!form.fecha_cierre.value){ alert('Indica la fecha de cierre.'); return false; }
      const f = form.archivo.files[0];
      if (f) {
        const ok = ['pdf','zip'].includes((f.name.split('.').pop()||'').toLowerCase());
        if(!ok){ alert('Solo PDF o ZIP.'); return false; }
      }
      return true;
    }
  </script>
</body>
</html>
      