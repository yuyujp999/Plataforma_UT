<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

$rolUsuario = $_SESSION['rol'];

// Asegurar que usuario sea un array
$usuario = $_SESSION['usuario'] ?? [
  'nombre' => 'Docente',
  'apellido_paterno' => '',
  'apellido_materno' => ''
];

// Extraer nombre y apellido
$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';

// Crear variable que falta
$usuarioNombre = $nombre . ' ' . $apellido;

// Generar iniciales (ej. "Carlos Pérez" → "CP")
$iniciales = strtoupper(
  substr($nombre, 0, 1) . substr($apellido, 0, 1)
);
?>


<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UT Panel | Docente</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../../css/styleD.css" />
  <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    /* Modal mínimo para no tocar tu CSS global */
    #modalTarea {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .35);
      z-index: 9999;
    }

    #modalTarea .box {
      max-width: 560px;
      margin: 7% auto;
      background: #fff;
      border-radius: 10px;
      padding: 22px;
    }

    #modalTarea input,
    #modalTarea textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 8px;
    }
  </style>
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

        <a class="nav-item active" href="#">
          <i class="fas fa-gauge"></i> <span>Dashboard</span>
        </a>
        <a class="nav-item" href="#" id="goTareas">
          <i class="fas fa-list-check"></i> <span>Mis Tareas</span>
        </a>
        <a class="nav-item" href="#" id="btnNuevaSidebar">
          <i class="fas fa-plus"></i> <span>Nueva Tarea</span>
        </a>

        <!-- Puedes activar estos después -->
        <!--
      <div class="menu-heading">Clase</div>
      <a class="nav-item" href="#"><i class="fas fa-folder-open"></i> Recursos</a>
      <a class="nav-item" href="#"><i class="fas fa-file-arrow-up"></i> Entregas</a>
      <a class="nav-item" href="#"><i class="fas fa-clipboard-check"></i> Calificaciones</a>
      -->

        <div class="menu-heading">Cuenta</div>
        <a class="nav-item logout" href="../../controladores/logout.php">
          <i class="fas fa-right-from-bracket"></i> Cerrar sesión
        </a>

      </div>
    </div>

    <!-- HEADER -->
    <div class="header">
      <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
      <div class="search-bar">
        <i class="fas fa-search"></i><input type="text" placeholder="Buscar..." />
      </div>
      <div class="header-actions">
        <div class="notification"><i class="fas fa-bell"></i>
          <div class="badge">3</div>
        </div>
        <div class="notification"><i class="fas fa-envelope"></i>
          <div class="badge">5</div>
        </div>

        <div class="user-profile" id="userProfile" data-nombre="<?= htmlspecialchars($usuarioNombre) ?>"
          data-rol="<?= htmlspecialchars($rolUsuario) ?>">
          <div class="profile-img"><?= htmlspecialchars($iniciales) ?></div>
                    <div class=" user-info">
            <div class="user-name"><?= htmlspecialchars($usuarioNombre) ?></div>
            <div class="user-role">Docente</div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN -->
    <div class="main-content">
      <div class="page-title">
        <div class="title">Dashboard Docente</div>
        <div class="action-buttons">
          <button id="btnExport" class="btn btn-outline"><i class="fas fa-download"></i> Export</button>
          <button id="btnNueva" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva tarea</button>
        </div>
      </div>

      <!-- Tus cards (las dejé sin valores para que las llenes luego) -->
      <div class="stats-cards">
        <div class="stat-card">
          <div class="card-header">
            <div>
              <div class="card-value">—</div>
              <div class="card-label">Tareas activas</div>
            </div>
            <div class="card-icon purple"><i class="fas fa-users"></i></div>
          </div>
          <div class="card-change positive"><i class="fas fa-arrow-up"></i><span>—</span></div>
        </div>
        <div class="stat-card">
          <div class="card-header">
            <div>
              <div class="card-value">—</div>
              <div class="card-label">Entregas hoy</div>
            </div>
            <div class="card-icon blue"><i class="fas fa-dollar-sign"></i></div>
          </div>
          <div class="card-change positive"><i class="fas fa-arrow-up"></i><span>—</span></div>
        </div>
        <div class="stat-card">
          <div class="card-header">
            <div>
              <div class="card-value">—</div>
              <div class="card-label">Pendientes por calificar</div>
            </div>
            <div class="card-icon green"><i class="fas fa-shopping-cart"></i></div>
          </div>
          <div class="card-change negative"><i class="fas fa-arrow-down"></i><span>—</span></div>
        </div>
        <div class="stat-card">
          <div class="card-header">
            <div>
              <div class="card-value">—</div>
              <div class="card-label">Promedio general</div>
            </div>
            <div class="card-icon orange"><i class="fas fa-chart-line"></i></div>
          </div>
          <div class="card-change positive"><i class="fas fa-arrow-up"></i><span>—</span></div>
        </div>
      </div>

      <!-- ===== CRUD TAREAS ===== -->
      <div class="table-card" id="seccionTareas">
        <div class="card-title">
          <h3><i class="fas fa-list-check"></i> Mis Tareas</h3>
        </div>
        <div class="p-4" style="padding:18px;">
          <table class="data-table" id="tablaTareas">
            <thead>
              <tr>
                <th>Título</th>
                <th>Entrega</th>
                <th>Creada</th>
                <th style="width:190px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <!-- Se llena por JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Modal Crear/Editar ===== -->
  <div id="modalTarea">
    <div class="box">
      <h3 id="tituloModal" style="margin-bottom:10px;">Nueva tarea</h3>
      <form id="formTarea">
        <input type="hidden" name="id" id="idTarea">
        <div style="margin:10px 0;">
          <input type="text" name="titulo" id="titulo" placeholder="Título" required>
        </div>
        <div style="margin:10px 0;">
          <textarea name="descripcion" id="descripcion" placeholder="Descripción" rows="4"></textarea>
        </div>
        <div style="margin:10px 0;">
          <label style="font-size:13px; color:#6c757d;">Fecha de entrega</label>
          <input type="date" name="fecha_entrega" id="fecha_entrega" required>
        </div>
        <div style="display:flex; gap:10px; margin-top:6px;">
          <button type="submit" class="btn btn-primary" id="btnGuardar"><i class="fas fa-save"></i>
            Guardar</button>
          <button type="button" class="btn btn-outline" id="btnCancelar">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // ===== Utilidad UI =====
    const API = "../../controladores/docentes/";
    const modal = document.getElementById('modalTarea');
    const form = document.getElementById('formTarea');
    const tituloModal = document.getElementById('tituloModal');
    const btnCancelar = document.getElementById('btnCancelar');
    const btnNueva = document.getElementById('btnNueva');
    const btnNuevaSidebar = document.getElementById('btnNuevaSidebar');

    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2200,
      timerProgressBar: true
    });

    function abrirModal(modo = "crear") {
      modal.style.display = 'block';
      tituloModal.textContent = (modo === 'editar') ? 'Editar tarea' : 'Nueva tarea';
      // Sugerencia UX: min hoy
      const d = document.getElementById('fecha_entrega');
      if (d && !d.value) d.min = new Date().toISOString().slice(0, 10);
    }

    function cerrarModal() {
      modal.style.display = 'none';
      form.reset();
      document.getElementById('idTarea').value = '';
    }
    btnCancelar?.addEventListener('click', cerrarModal);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) cerrarModal();
    });
    btnNueva?.addEventListener('click', () => abrirModal('crear'));
    btnNuevaSidebar?.addEventListener('click', () => abrirModal('crear'));

    function escapeHtml(s) {
      return s ? s.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;') : '';
    }

    function fmtFecha(iso) {
      return iso;
    } // ya te llega YYYY-MM-DD (simple)

    // Estado según fecha de entrega
    function estadoTarea(fecha) {
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);
      const f = new Date(fecha + 'T00:00:00');
      const dif = (f - hoy) / 86400000; // días
      if (dif < 0) return {
        txt: 'Vencida',
        cls: 'chip-danger',
        icon: 'fa-triangle-exclamation'
      };
      if (dif === 0) return {
        txt: 'Vence hoy',
        cls: 'chip-warning',
        icon: 'fa-sun'
      };
      return {
        txt: 'Próxima',
        cls: 'chip-success',
        icon: 'fa-circle-check'
      };
    }

    // Cargar tabla + actualizar cards
    async function listarTareas() {
      const tbody = document.querySelector('#tablaTareas tbody');
      // loading skeleton UX
      tbody.innerHTML = `
      <tr><td colspan="4">
        <div class="skeleton" style="width:60%;margin:8px 0"></div>
        <div class="skeleton" style="width:45%;margin:8px 0"></div>
        <div class="skeleton" style="width:70%;margin:8px 0"></div>
      </td></tr>`;

      let j;
      try {
        const r = await fetch(API + 'listar_tareas.php');
        j = await r.json();
      } catch (e) {
        console.error(e);
      }

      if (!j || !j.ok) {
        tbody.innerHTML = `<tr><td colspan="4">No se pudo cargar.</td></tr>`;
        return;
      }

      // Si no hay tareas: empty state
      if (j.data.length === 0) {
        tbody.innerHTML = `
        <tr><td colspan="4">
          <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <p>Aún no has creado tareas.</p>
            <button class="btn btn-primary" id="ctaCrear">Crear mi primera tarea</button>
          </div>
        </td></tr>`;
        document.getElementById('ctaCrear')?.addEventListener('click', () => abrirModal('crear'));
        // Actualiza cards con ceros
        setCards(0, 0, 0, '—');
        return;
      }

      // Pintar filas bonitas
      let vencidas = 0,
        hoy = 0,
        proximas = 0;
      tbody.innerHTML = j.data.map(t => {
        const est = estadoTarea(t.fecha_entrega);
        if (est.txt === 'Vencida') vencidas++;
        else if (est.txt === 'Vence hoy') hoy++;
        else proximas++;
        return `
        <tr data-id="${t.id}">
          <td>
            <div style="font-weight:600">${escapeHtml(t.titulo)}</div>
            <div style="color:#6c757d;font-size:13px">${escapeHtml(t.descripcion || '')}</div>
          </td>
          <td>
            <span class="badge-chip ${est.cls}">
              <i class="fas ${est.icon}"></i> ${fmtFecha(t.fecha_entrega)} · ${est.txt}
            </span>
          </td>
          <td>${escapeHtml(t.creado_en)}</td>
          <td class="table-actions">
            <button class="btn btn-sm btn-outline btn-editar"><i class="fas fa-edit"></i> Editar</button>
            <button class="btn btn-sm btn-outline btn-eliminar" style="border-color:#e63946;color:#e63946">
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </td>
        </tr>`;
      }).join('');

      // Actualiza las cards de arriba (solo números “fake útiles” por ahora)
      const totalActivas = proximas + hoy; // consideramos activas = no vencidas
      const promedio = '—'; // lo llenaremos cuando tengamos calificaciones
      setCards(totalActivas, hoy, vencidas, promedio);
    }

    function setCards(activas, hoy, pendientes, promedio) {
      const vals = document.querySelectorAll('.card-value');
      const labels = document.querySelectorAll('.card-label');
      if (vals[0]) {
        vals[0].textContent = activas;
        labels[0].textContent = 'Tareas activas';
      }
      if (vals[1]) {
        vals[1].textContent = hoy;
        labels[1].textContent = 'Entregas hoy';
      }
      if (vals[2]) {
        vals[2].textContent = pendientes;
        labels[2].textContent = 'Pendientes por calificar';
      }
      if (vals[3]) {
        vals[3].textContent = promedio;
        labels[3].textContent = 'Promedio general';
      }
    }

    // Editar / Eliminar
    document.addEventListener('click', async (e) => {
      const tr = e.target.closest('tr');
      if (!tr || !tr.dataset.id) return;
      const id = tr.dataset.id;

      // Editar
      if (e.target.closest('.btn-editar')) {
        const r = await fetch(API + 'obtener_tarea.php?id=' + encodeURIComponent(id));
        const j = await r.json();
        if (j.ok) {
          document.getElementById('idTarea').value = j.data.id;
          document.getElementById('titulo').value = j.data.titulo;
          document.getElementById('descripcion').value = j.data.descripcion || '';
          document.getElementById('fecha_entrega').value = j.data.fecha_entrega;
          abrirModal('editar');
        } else {
          Toast.fire({
            icon: 'error',
            title: 'No se pudo cargar la tarea'
          });
        }
      }

      // Eliminar (con confirmación bonita)
      if (e.target.closest('.btn-eliminar')) {
        const conf = await Swal.fire({
          title: '¿Eliminar tarea?',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#e63946',
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar'
        });
        if (conf.isConfirmed) {
          await fetch(API + 'eliminar_tarea.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + encodeURIComponent(id)
          });
          Toast.fire({
            icon: 'success',
            title: 'Tarea eliminada'
          });
          listarTareas();
        }
      }
    });

    // Crear / Actualizar
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const id = fd.get('id');
      const url = id ? (API + 'actualizar_tarea.php') : (API + 'crear_tarea.php');

      try {
        const r = await fetch(url, {
          method: 'POST',
          body: fd
        });
        const isJson = r.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await r.json() : null;

        if (!r.ok || (data && data.ok === false)) {
          const msg = data?.msg || 'No se pudo guardar';
          Toast.fire({
            icon: 'error',
            title: msg
          });
          return;
        }

        Toast.fire({
          icon: 'success',
          title: id ? 'Tarea actualizada' : 'Tarea creada'
        });
        cerrarModal();
        listarTareas();
      } catch (err) {
        console.error(err);
        Toast.fire({
          icon: 'error',
          title: 'Error de red'
        });
      }
    });


    // Go!
    listarTareas();
  </script>

</body>

</html>