<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

include_once __DIR__ . "/../../controladores/docentes/ExamenesController.php";

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
$rolUsuario = $_SESSION['rol'] ?? 'docente';

$flash = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

$examenes     = ExamenesController::listarExamenesDocente($idDocente);
$asignaciones = ExamenesController::obtenerAsignacionesDocente($idDocente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📘 Exámenes | UT Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/Docentes/examenes.css">
  <link rel="icon" href="../../img/ut_logo.png" type="image/png">

  <!-- Ajustes de layout del modal -->
  <style>
    #modalExamen .modal-content {
      max-width: 1100px;
      width: 96%;
      max-height: 95vh;
      display: flex;
      flex-direction: column;
      padding-bottom: 16px;
    }
    #formNuevoExamen {
      flex: 1;
      overflow-y: auto;
      padding-right: 4px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .modal-grid-top {
      display: grid;
      grid-template-columns: minmax(0, 2.1fr) minmax(0, 1.2fr);
      gap: 18px;
      align-items: flex-start;
    }
    .editor-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(0, 1.3fr);
      gap: 18px;
      align-items: stretch;
    }
    .card-block {
      background: #f8fafc;
      border-radius: 10px;
      padding: 12px 14px;
      border: 1px solid #e2e8f0;
    }
    .card-block h4 {
      margin-top: 0;
      margin-bottom: 8px;
      color: #0f172a;
    }
    #questionsList {
      max-height: 280px;
      overflow-y: auto;
      padding-right: 4px;
    }
    .question-card {
      background: #ffffff;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      padding: 8px 10px;
      margin-bottom: 8px;
      font-size: 0.9rem;
    }
    .question-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 4px;
      gap: 8px;
    }
    .question-header strong {
      font-weight: 600;
      color: #0f172a;
    }
    .question-card ul {
      margin: 4px 0 0;
      padding-left: 18px;
      font-size: 0.9rem;
    }
    #opciones_wrapper {
      background: #f1f5f9;
    }
    #listaOpciones .opcion-row {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr) auto auto;
      align-items: center;
      gap: 6px;
      margin-bottom: 6px;
    }
    #listaOpciones .opcion-label {
      font-weight: 600;
      min-width: 20px;
      text-align: right;
    }
    #listaOpciones .input-opcion {
      width: 100%;
      padding: 5px 8px;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      font-size: 0.9rem;
    }
    .radio-correcta {
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }
    .btn-icon {
      background: transparent;
      border: none;
      cursor: pointer;
      padding: 4px;
      border-radius: 999px;
    }
    .btn-icon i { font-size: 0.8rem; }
    .btn-icon:hover { background: #fee2e2; }
    #btnAddQuestionToList { margin-top: 6px; }
    #emptyQuestionsMsg {
      border-radius: 8px;
      border: 1px dashed #cbd5e1;
      background: #f8fafc;
    }
    .modal-footer-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 4px;
    }

    /* Modal de previsualización */
    #modalPreview .modal-content {
      max-width: 900px;
      width: 96%;
      max-height: 90vh;
      overflow-y: auto;
      padding-bottom: 16px;
    }

    @media (max-width: 960px) {
      .modal-grid-top,
      .editor-grid { grid-template-columns: 1fr; }
      #modalExamen .modal-content,
      #modalPreview .modal-content { max-width: 100%; width: 100%; }
    }
  </style>
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <main class="main-content">
      <?php if (!empty($flash)): ?>
        <div class="alert-message"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <header class="evaluaciones-header">
        <div>
          <h2><i class="fa-solid fa-file-circle-question"></i> Exámenes</h2>
          <p>Crea exámenes y administra sus preguntas.</p>
        </div>
        <button class="btn-subir" id="abrirModal">
          <i class="fa-solid fa-plus"></i> Nuevo examen
        </button>
      </header>

      <section class="evaluaciones-section">
        <?php if (!empty($examenes)): ?>
          <div class="evaluaciones-lista">
            <?php foreach ($examenes as $ex): ?>
              <?php
                $cerrado = ($ex['estado'] === 'Cerrado' || date('Y-m-d') > $ex['fecha_cierre']);
                $badge = $cerrado
                  ? '<span class="badge badge-danger">Cerrado</span>'
                  : '<span class="badge badge-ok">Activo</span>';
              ?>
              <div class="evaluacion-item <?= $cerrado ? 'is-closed' : '' ?>">
                <div class="evaluacion-info">
                  <h3>
                    <i class="fa-solid fa-file-pen"></i>
                    <?= htmlspecialchars($ex['titulo']) ?> <?= $badge ?>
                  </h3>
                  <p><strong>Materia:</strong> <?= htmlspecialchars($ex['materia'] ?? '—') ?></p>
                  <p><strong>Fecha cierre:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($ex['fecha_cierre']))) ?></p>
                  <?php if (!empty($ex['descripcion'])): ?>
                    <p class="desc"><?= nl2br(htmlspecialchars($ex['descripcion'])) ?></p>
                  <?php endif; ?>
                </div>
                <div class="evaluacion-actions">
                  <!-- BOTÓN PREGUNTAS -> PREVISUALIZAR EXAMEN -->
                  <button
                    type="button"
                    class="btn-ver btn-preview-examen"
                    data-examen-id="<?= (int)$ex['id_examen'] ?>"
                  >
                    <i class="fa-solid fa-list-check"></i> Preguntas
                  </button>

                  <form method="POST" action="/Plataforma_UT/controladores/docentes/ExamenesController.php"
                        onsubmit="return confirm('¿Eliminar este examen y todas sus preguntas?');">
                    <input type="hidden" name="accion" value="eliminar_examen">
                    <input type="hidden" name="id_examen" value="<?= (int)$ex['id_examen'] ?>">
                    <button type="submit" class="btn-eliminar">
                      <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty">
            <i class="fa-solid fa-folder-open"></i>
            No has creado exámenes aún.
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <!-- Modal crear examen -->
  <div class="modal" id="modalExamen">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h3><i class="fa-solid fa-plus"></i> Nuevo examen</h3>

      <div id="modalError" class="alert-message" style="display:none; margin-bottom:10px;"></div>

      <form method="POST"
            action="/Plataforma_UT/controladores/docentes/ExamenesController.php"
            id="formNuevoExamen">

        <input type="hidden" name="accion" value="crear_examen">
        <input type="hidden" name="questions_json" id="questions_json" value="[]">

        <!-- Datos generales -->
        <div class="modal-grid-top card-block">
          <div>
            <div class="form-group">
              <label for="id_asignacion_docente">Materia:</label>
              <select name="id_asignacion_docente" id="id_asignacion_docente" required>
                <option value="">Selecciona…</option>
                <?php foreach ($asignaciones as $as): ?>
                  <option value="<?= (int)$as['id_asignacion_docente'] ?>">
                    <?= htmlspecialchars($as['materia']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="titulo">Título del examen:</label>
              <input type="text" name="titulo" id="titulo" required>
            </div>

            <div class="form-group">
              <label for="descripcion">Descripción / instrucciones:</label>
              <textarea name="descripcion" id="descripcion" rows="3" placeholder="Instrucciones generales del examen..."></textarea>
            </div>
          </div>

          <div>
            <div class="form-group">
              <label for="fecha_cierre">Fecha de cierre:</label>
              <input type="date" name="fecha_cierre" id="fecha_cierre" required>
            </div>
            <p style="font-size:0.85rem; color:#555; margin-top:4px;">
              La fecha de cierre es el último día en que el alumno podrá responder el examen.
            </p>
          </div>
        </div>

        <!-- Editor de preguntas -->
        <div class="editor-grid">
          <!-- Editor -->
          <div class="card-block">
            <h4>Editor de pregunta</h4>

            <div class="form-group">
              <label for="q_text">Texto de la pregunta:</label>
              <textarea id="q_text" rows="3" placeholder="Escribe aquí la pregunta que verán los alumnos..."></textarea>
            </div>

            <div class="form-group" style="display:flex; gap:10px; align-items:center;">
              <div style="flex:1;">
                <label for="q_type">Tipo de pregunta:</label>
                <select id="q_type">
                  <option value="abierta">Pregunta abierta</option>
                  <option value="opcion">Opción múltiple</option>
                </select>
              </div>
            </div>

            <div id="opciones_wrapper" style="display:none; border:1px dashed #ddd; padding:10px; border-radius:8px; margin-bottom:12px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span><strong>Opciones de respuesta</strong></span>
                <button type="button" id="btnAgregarOpcion" class="btn-primary" style="padding:4px 10px; font-size:12px;">
                  <i class="fa-solid fa-plus"></i> Añadir opción
                </button>
              </div>
              <div id="listaOpciones"></div>
              <small style="font-size:12px; color:#555;">Selecciona una opción como correcta.</small>
            </div>

            <button type="button" id="btnAddQuestionToList" class="btn-primary full">
              <i class="fa-solid fa-plus"></i> Agregar pregunta al examen
            </button>
          </div>

          <!-- Lista -->
          <div class="card-block">
            <h4>Preguntas del examen</h4>
            <div id="questionsList">
              <div id="emptyQuestionsMsg" class="empty" style="margin-top:4px;">
                Aún no has agregado preguntas.
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer-actions">
          <button type="button" class="btn-eliminar" onclick="document.getElementById('modalExamen').classList.remove('active');">
            Cancelar
          </button>
          <button type="submit" class="btn-primary">
            <i class="fa-solid fa-save"></i> Crear examen
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal PREVISUALIZACIÓN examen -->
  <div class="modal" id="modalPreview">
    <div class="modal-content">
      <span class="close-btn close-preview">&times;</span>
      <h3><i class="fa-solid fa-eye"></i> Previsualización de examen</h3>
      <div id="previewBody" style="margin-top:10px;"></div>
    </div>
  </div>

  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
  <script src="/Plataforma_UT/js/modeToggle.js"></script>

  <script>
    // ===== Helpers para previsualización =====
    function escapeHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
    function nl2brHtml(str) {
      return escapeHtml(str).replace(/\n/g, '<br>');
    }

    // ===== MODAL CREAR EXAMEN =====
    const modal = document.getElementById('modalExamen');
    const openBtn = document.getElementById('abrirModal');
    const closeBtn = modal.querySelector('.close-btn');

    openBtn.addEventListener('click', () => modal.classList.add('active'));
    closeBtn.addEventListener('click', () => modal.classList.remove('active'));

    // ===== MODAL PREVIEW EXAMEN =====
    const previewModal  = document.getElementById('modalPreview');
    const previewBody   = document.getElementById('previewBody');
    const previewClose  = previewModal.querySelector('.close-preview');
    const previewButtons = document.querySelectorAll('.btn-preview-examen');

    previewClose.addEventListener('click', () => previewModal.classList.remove('active'));

    window.addEventListener('click', e => {
      if (e.target === modal) modal.classList.remove('active');
      if (e.target === previewModal) previewModal.classList.remove('active');
    });

    previewButtons.forEach(btn => {
      btn.addEventListener('click', async () => {
        const examenId = btn.dataset.examenId;
        previewBody.innerHTML = '<p>Cargando previsualización...</p>';
        previewModal.classList.add('active');

        const fd = new FormData();
        fd.append('accion', 'obtener_examen_json');
        fd.append('id_examen', examenId);
        fd.append('ajax', '1');

        try {
          const resp = await fetch('/Plataforma_UT/controladores/docentes/ExamenesController.php', {
            method: 'POST',
            body: fd
          });

          const ct = resp.headers.get('content-type') || '';
          let data;
          if (ct.includes('application/json')) {
            data = await resp.json();
          } else {
            const text = await resp.text();
            throw new Error(text.substring(0, 2000));
          }

          if (!data.success) {
            previewBody.innerHTML = '<p style="color:#b91c1c;">⚠️ No se pudo obtener el examen.</p>';
            return;
          }

          const examen    = data.examen || {};
          const preguntas = data.preguntas || [];

          let html = `
            <div class="card-block">
              <h4>${escapeHtml(examen.titulo || '')}</h4>
              <p><strong>Materia:</strong> ${escapeHtml(examen.materia || '—')}</p>
              <p><strong>Fecha de cierre:</strong> ${escapeHtml(examen.fecha_cierre_formateada || examen.fecha_cierre || '')}</p>
              ${examen.descripcion ? '<p>' + nl2brHtml(examen.descripcion) + '</p>' : ''}
            </div>
            <div class="card-block" style="margin-top:10px;">
              <h4>Preguntas</h4>
          `;

          if (!preguntas.length) {
            html += '<p>No hay preguntas registradas para este examen.</p>';
          } else {
            preguntas.forEach((p, idx) => {
              const tipo = p.tipo === 'opcion' ? 'Opción múltiple' : 'Abierta';
              html += `
                <div class="question-card">
                  <div class="question-header">
                    <strong>${idx + 1}. ${escapeHtml(p.pregunta || '')}</strong>
                  </div>
                  <small>Tipo: ${tipo}</small>
              `;

              if (p.tipo === 'opcion' && Array.isArray(p.opciones) && p.opciones.length) {
                html += '<ul>';
                p.opciones.forEach((opt, i) => {
                  const letra = String.fromCharCode(65 + i);
                  const esCorrecta = (String(opt.es_correcta) === '1' || opt.es_correcta === 1);
                  html += `<li>${letra}) ${escapeHtml(opt.texto_opcion || '')}${esCorrecta ? ' <strong>(correcta)</strong>' : ''}</li>`;
                });
                html += '</ul>';
              }

              html += '</div>'; // question-card
            });
          }

          html += '</div>'; // card-block preguntas
          previewBody.innerHTML = html;

        } catch (err) {
          console.error(err);
          previewBody.innerHTML =
            '<p style="color:#b91c1c;">⚠️ Error al obtener la previsualización del examen.</p>';
        }
      });
    });

    // ===== PREGUNTAS (FRONT CREACIÓN) =====
    let questions = [];

    const qText           = document.getElementById('q_text');
    const qType           = document.getElementById('q_type');
    const opcionesWrapper = document.getElementById('opciones_wrapper');
    const listaOpciones   = document.getElementById('listaOpciones');
    const addOpcionBtn    = document.getElementById('btnAgregarOpcion');
    const addPreguntaBtn  = document.getElementById('btnAddQuestionToList');
    const questionsList   = document.getElementById('questionsList');
    const emptyMsg        = document.getElementById('emptyQuestionsMsg');
    const questionsJson   = document.getElementById('questions_json');
    const modalError      = document.getElementById('modalError');
    const formExamen      = document.getElementById('formNuevoExamen');

    qType.addEventListener('change', () => {
      opcionesWrapper.style.display = (qType.value === 'opcion') ? 'block' : 'none';
    });

    function crearFilaOpcion(texto = "") {
      const index = listaOpciones.children.length;
      const letra = String.fromCharCode(65 + index);

      const row = document.createElement('div');
      row.classList.add('opcion-row');

      row.innerHTML = `
        <span class="opcion-label">${letra})</span>
        <input type="text" class="input-opcion" value="${texto}">
        <label class="radio-correcta">
          <input type="radio" name="correcta_temp">
          Correcta
        </label>
        <button type="button" class="btn-icon" title="Eliminar opción">
          <i class="fa-solid fa-xmark"></i>
        </button>
      `;

      const radio = row.querySelector('input[type="radio"]');
      radio.value = index;

      row.querySelector('.btn-icon').onclick = () => {
        row.remove();
        reordenarOpciones();
      };

      radio.addEventListener('change', () => {
        document.querySelectorAll('#listaOpciones input[type="radio"]').forEach(r => {
          if (r !== radio) r.checked = false;
        });
      });

      listaOpciones.appendChild(row);
    }

    function reordenarOpciones() {
      [...listaOpciones.children].forEach((row, i) => {
        row.querySelector('.opcion-label').textContent = String.fromCharCode(65+i) + ')';
      });
    }

    addOpcionBtn.addEventListener('click', () => crearFilaOpcion());

    function limpiarPreguntaActual() {
      qText.value = "";
      qType.value = "abierta";
      opcionesWrapper.style.display = "none";
      listaOpciones.innerHTML = "";
    }

    function renderQuestions() {
      questionsList.innerHTML = "";
      if (questions.length === 0) {
        questionsList.appendChild(emptyMsg);
        return;
      }

      questions.forEach((q, idx) => {
        const card = document.createElement('div');
        card.classList.add('question-card');

        let opcionesHTML = "";
        if (q.tipo === "opcion" && q.opciones.length) {
          opcionesHTML = "<ul>" + q.opciones.map((opt, i) => {
            const letra = String.fromCharCode(65 + i);
            const check = (i === q.correcta) ? " <strong>(correcta)</strong>" : "";
            return `<li>${letra}) ${opt}${check}</li>`;
          }).join("") + "</ul>";
        }

        card.innerHTML = `
          <div class="question-header">
            <strong>${idx+1}. ${q.pregunta}</strong>
            <button type="button" class="btn-icon" title="Eliminar pregunta">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
          <small>Tipo: ${q.tipo === 'opcion' ? 'Opción múltiple' : 'Abierta'}</small>
          ${opcionesHTML}
        `;

        card.querySelector('.btn-icon').onclick = () => {
          questions.splice(idx, 1);
          renderQuestions();
        };

        questionsList.appendChild(card);
      });

      questionsJson.value = JSON.stringify(questions);
    }

    addPreguntaBtn.addEventListener('click', () => {
      modalError.style.display = 'none';
      modalError.textContent = "";

      const texto = qText.value.trim();
      if (!texto) {
        modalError.textContent = "Escribe el texto de la pregunta.";
        modalError.style.display = "block";
        return;
      }

      const tipo = qType.value;

      const pregunta = {
        tipo,
        pregunta: texto,
        opciones: [],
        correcta: null
      };

      if (tipo === "opcion") {
        const filas = [...listaOpciones.children];
        if (filas.length < 2) {
          modalError.textContent = "Agrega al menos 2 opciones para la pregunta.";
          modalError.style.display = "block";
          return;
        }

        let correctIndex = -1;

        filas.forEach((row, i) => {
          const txt = row.querySelector('.input-opcion').value.trim();
          const radio = row.querySelector('input[type="radio"]');

          if (txt !== "") {
            pregunta.opciones.push(txt);
            if (radio.checked) correctIndex = i;
          }
        });

        if (pregunta.opciones.length < 2) {
          modalError.textContent = "Las opciones no pueden estar vacías.";
          modalError.style.display = "block";
          return;
        }

        if (correctIndex === -1) {
          modalError.textContent = "Selecciona una opción correcta para la pregunta.";
          modalError.style.display = "block";
          return;
        }

        pregunta.correcta = correctIndex;
      }

      questions.push(pregunta);
      renderQuestions();
      limpiarPreguntaActual();
    });

    // ===== ENVIAR FORM (AJAX, SIN CERRAR MODAL) =====
    formExamen.addEventListener('submit', async function (e) {
      e.preventDefault();

      modalError.style.display = 'none';
      modalError.textContent   = "";

      const materia  = this.id_asignacion_docente.value.trim();
      const titulo   = this.titulo.value.trim();
      const fechaFin = this.fecha_cierre.value;

      const errores = [];
      if (!materia)  errores.push("Selecciona una materia.");
      if (!titulo)   errores.push("Escribe un título para el examen.");
      if (!fechaFin) errores.push("Indica la fecha de cierre del examen.");

      if (errores.length > 0) {
        modalError.innerHTML = errores.join("<br>");
        modalError.display = "block";
        modalError.style.display = "block";
        return;
      }

      questionsJson.value = JSON.stringify(questions);

      const fd = new FormData(this);
      fd.append('ajax', '1');

      try {
        const resp = await fetch(this.action, {
          method: 'POST',
          body: fd
        });

        const ct = resp.headers.get('content-type') || '';
        let data;

        if (ct.includes('application/json')) {
          data = await resp.json();
        } else {
          const text = await resp.text();
          throw new Error(text.substring(0, 4000));
        }

        if (!data.success) {
          modalError.innerHTML = data.mensaje || "⚠️ Error al crear el examen.";
          modalError.style.display = "block";
          return;
        }

        // Si todo bien, recargamos lista
        location.reload();

      } catch (err) {
        console.error(err);
        modalError.innerHTML = "⚠️ Error del servidor:<br>" +
          (err.message || "No se pudo procesar la respuesta.");
        modalError.style.display = "block";
      }
    });
  </script>
</body>
</html>
