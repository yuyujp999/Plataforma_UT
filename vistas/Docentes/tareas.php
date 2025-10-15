<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
  header('Location: /Plataforma_UT/inicio.php');
  exit;
}

$rolUsuario = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? [
  'nombre' => 'Docente',
  'apellido_paterno' => '',
  'apellido_materno' => ''
];

$nombre = $usuario['nombre'] ?? 'Docente';
$apellido = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;
$iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UT Panel | Mis Tareas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../css/styleD.css">
  <link rel="stylesheet" href="../../css/docentes/dashboard_docente.css">
  <link rel="stylesheet" href="../../css/docentes/dashboard_docente_modal.css">
  <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <div class="container">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="overlay" id="overlay"></div>
      <div class="logo"><h1>UT<span>Panel</span></h1></div>
      <div class="nav-menu" id="menu">
        <div class="menu-heading">Menú</div>
      </div>
    </div>

    <!-- HEADER -->
    <div class="header">
      <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Buscar..." /></div>
      <div class="header-actions">
        <div class="user-profile">
          <div class="profile-img"><?= htmlspecialchars($iniciales) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($usuarioNombre) ?></div>
            <div class="user-role">Docente</div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN -->
    <div class="main-content">
      <div class="page-title">
        <div class="title">Mis Tareas</div>
        <div class="action-buttons">
          <button id="btnExport" class="btn btn-outline"><i class="fas fa-download"></i> Exportar</button>
          <button id="btnNueva" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva tarea</button>
        </div>
      </div>

      <div class="table-card" id="seccionTareas">
        <div class="card-title"><h3><i class="fas fa-list-check"></i> Lista de Tareas</h3></div>
        <div class="tareas-full">
          <table class="data-table" id="tablaTareas">
            <thead>
              <tr>
                <th>Título</th>
                <th>Descripción</th>
                <th>Entrega</th>
                <th>Archivo</th>
                <th>Creada</th>
                <th style="width:150px;">Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modales -->
  <div id="modalTarea">
    <div class="box">
      <h3 id="tituloModal">Nueva tarea</h3>
      <form id="formTarea" enctype="multipart/form-data">
        <input type="hidden" name="id" id="idTarea">
        <div><input type="text" name="titulo" id="titulo" placeholder="Título" required></div>
        <div><textarea name="descripcion" id="descripcion" placeholder="Descripción" rows="4"></textarea></div>
        <div>
          <label>Fecha de entrega</label>
          <input type="date" name="fecha_entrega" id="fecha_entrega" required>
        </div>
        <div>
          <label>Archivo adjunto (opcional)</label>
          <input type="file" name="archivo" id="archivo" accept=".pdf,.docx,.xlsx,.jpg,.png">
        </div>
        <div style="display:flex; gap:10px; margin-top:6px;">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
          <button type="button" class="btn btn-outline" id="btnCancelar">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <div id="vistaPreviaTarea" class="vista-modal" style="display:none;">
    <div class="vista-content animate__animated animate__fadeInRight">
      <button id="cerrarVista" class="btn btn-outline" style="float:right;margin-top:-10px;"><i class="fas fa-times"></i></button>
      <h2 id="vpTitulo"></h2>
      <p id="vpDescripcion"></p>
      <hr>
      <p><strong>Fecha de entrega:</strong> <span id="vpFecha"></span></p>
      <p><strong>Creada el:</strong> <span id="vpCreacion"></span></p>
      <div id="vpArchivo" style="margin-top:10px;"></div>
    </div>
  </div>

  <!-- JS de tareas -->
  <script>
    const API = "../../controladores/docentes/";
    const modal = document.getElementById("modalTarea");
    const form = document.getElementById("formTarea");

    function abrirModal(modo="crear"){
      modal.style.display="block";
      document.getElementById("tituloModal").textContent = (modo==="editar")?"Editar tarea":"Nueva tarea";
    }
    function cerrarModal(){ modal.style.display="none"; form.reset(); }
    document.getElementById("btnCancelar").onclick = cerrarModal;
    modal.onclick = e => { if(e.target===modal) cerrarModal(); }
    document.getElementById("btnNueva").onclick = ()=>abrirModal();

    async function listarTareas(){
      const tbody=document.querySelector("#tablaTareas tbody");
      tbody.innerHTML="<tr><td colspan='6'>Cargando...</td></tr>";
      const r=await fetch(API+"listar_tareas.php");
      const j=await r.json();

      if(!j.ok){ tbody.innerHTML="<tr><td colspan='6'>Error al cargar</td></tr>"; return; }
      if(j.data.length===0){ tbody.innerHTML="<tr><td colspan='6'>Sin tareas aún.</td></tr>"; return; }

      window.__tareas = j.data;
      tbody.innerHTML=j.data.map(t=>`
        <tr data-id="${t.id}">
          <td>${t.titulo}</td>
          <td>${t.descripcion || "—"}</td>
          <td>${t.fecha_entrega}</td>
          <td>${t.archivo_url?`<a href='${t.archivo_url}' target='_blank'>📎 Archivo</a>`:"—"}</td>
          <td>${t.creado_en}</td>
          <td>
            <button class="btn btn-sm btn-outline btn-editar"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-outline btn-eliminar" style="border-color:#e63946;color:#e63946"><i class="fas fa-trash"></i></button>
          </td>
        </tr>`).join("");
    }

    document.querySelector("#tablaTareas tbody").addEventListener("click", async (e) => {
      const tr = e.target.closest("tr[data-id]");
      if (!tr) return;
      const id = tr.dataset.id;

      if (e.target.closest(".btn-editar")) {
        const r = await fetch(API+"obtener_tarea.php?id="+encodeURIComponent(id));
        const d = await r.json();
        if (d.ok) {
          document.getElementById("idTarea").value = d.data.id;
          document.getElementById("titulo").value = d.data.titulo;
          document.getElementById("descripcion").value = d.data.descripcion || "";
          document.getElementById("fecha_entrega").value = d.data.fecha_entrega;
          abrirModal("editar");
        } else Swal.fire({ icon:"error", title:"Error", text:"No se pudo cargar la tarea" });
        return;
      }

      if (e.target.closest(".btn-eliminar")) {
        const conf = await Swal.fire({
          title:"¿Eliminar tarea?",
          text:"Esta acción no se puede deshacer.",
          icon:"warning",
          showCancelButton:true,
          confirmButtonColor:"#e63946",
          confirmButtonText:"Sí, eliminar",
          cancelButtonText:"Cancelar"
        });
        if(conf.isConfirmed){
          const resp = await fetch(API+"eliminar_tarea.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"id="+id});
          const data = await resp.json();
          if(data.ok){ Swal.fire({icon:"success",title:"Tarea eliminada",timer:1500,showConfirmButton:false}); listarTareas(); }
          else Swal.fire({icon:"error",title:"Error",text:data.msg||"No se pudo eliminar"});
        }
        return;
      }

      const tarea = (window.__tareas || []).find(t => String(t.id) === String(id));
      if (tarea) abrirVistaPrevia(tarea);
    });

    form.addEventListener("submit", async e=>{
      e.preventDefault();
      const fd=new FormData(form);
      const id=fd.get("id");
      const url=id?(API+"actualizar_tarea.php"):(API+"crear_tarea.php");
      const r=await fetch(url,{method:"POST",body:fd});
      const d=await r.json();
      if(!d.ok){ Swal.fire({icon:"error",title:"Error",text:d.msg||"No se pudo guardar"}); return; }
      Swal.fire({icon:"success",title:id?"Tarea actualizada":"Tarea creada",timer:1500,showConfirmButton:false});
      cerrarModal(); listarTareas();
    });

    function abrirVistaPrevia(t){
      const m=document.getElementById("vistaPreviaTarea");
      document.getElementById("vpTitulo").textContent=t.titulo;
      document.getElementById("vpDescripcion").textContent=t.descripcion||"Sin descripción";
      document.getElementById("vpFecha").textContent=t.fecha_entrega;
      document.getElementById("vpCreacion").textContent=t.creado_en;
      document.getElementById("vpArchivo").innerHTML=t.archivo_url
        ? `<a href='${t.archivo_url}' target='_blank' class='btn btn-outline'><i class='fas fa-file'></i> Ver archivo</a>`
        : `<p style='color:#999;'>Sin archivo adjunto</p>`;
      m.style.display="flex";
      document.body.style.overflow="hidden";
    }

    function cerrarVistaPrevia(){
      document.getElementById("vistaPreviaTarea").style.display="none";
      document.body.style.overflow="";
    }

    document.getElementById("cerrarVista").onclick = cerrarVistaPrevia;
    document.getElementById("vistaPreviaTarea").onclick = (e)=>{ if(e.target.id==="vistaPreviaTarea") cerrarVistaPrevia(); };
    document.addEventListener("keydown",(e)=>{ if(e.key==="Escape") cerrarVistaPrevia(); });

    listarTareas();
  </script>

  <!-- Menú -->
  <script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <script src="../../js/DashboardY.js"></script>
</body>
</html>
