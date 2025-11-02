// ====== SELECTORES GLOBALES ======
const modalNuevo = document.getElementById("modalNuevoAlumno");
const btnNuevo = document.getElementById("btnNuevoAlumno");
const cancelNuevo = document.getElementById("cancelModal");
const closeNuevo = document.getElementById("closeModal");
const formNuevo = document.getElementById("formNuevo");

const modalEditar = document.getElementById("modalEditar");
const formEditar = document.getElementById("formEditar");
const cancelEditar = document.getElementById("cancelEditar");
const closeEditar = document.getElementById("closeEditar");

const tablaBody = document.getElementById("tablaBody");
const inputBuscar =
  document.getElementById("buscarAlumno") ||
  document.getElementById("buscarAdmin");

const btnExportar = document.getElementById("btnExportar");

let idAlumnoSeleccionado = null;

// ====== HELPERS ======
function abrirModal(modal) {
  modal?.classList.add("active");
}
function cerrarModal(modal, form) {
  modal?.classList.remove("active");
  form?.reset?.();
}
function safe(v, fb = "—") {
  return (v ?? "").toString().trim() || fb;
}
function getSelectedText(sel) {
  const el = typeof sel === "string" ? document.querySelector(sel) : sel;
  const opt = el?.options?.[el.selectedIndex];
  return opt ? opt.textContent.trim() : "";
}

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
// FIX: TRIM al estatus y colores asegurados por clase de fila
// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
function decorarFilaPorEstatus(tr) {
  const est = (tr?.dataset?.estatus || "").trim().toLowerCase(); // <-- trim()
  if (!est || est === "activo") return;

  if (est === "baja") tr.classList.add("row-baja");
  if (est === "suspendido") tr.classList.add("row-suspendido");

  const nombreCell = tr.children?.[1];
  if (nombreCell && !nombreCell.querySelector(".estado-badge")) {
    const badge = document.createElement("span");
    badge.className = `estado-badge ${est}`;
    badge.textContent = est;
    nombreCell.appendChild(badge);
  }

  // Ajusta textos de botones si existen
  const btnBaja = tr.querySelector(".btn-baja");
  if (btnBaja && est === "baja") {
    btnBaja.innerHTML = `<i class="fas fa-user-check"></i> Reactivar`;
  }
  const btnSusp = tr.querySelector(".btn-susp");
  if (btnSusp && est === "suspendido") {
    btnSusp.innerHTML = `<i class="fas fa-user-check"></i> Activar`;
  }
}

// ====== ABRIR / CERRAR MODALES ======
btnNuevo?.addEventListener("click", () => abrirModal(modalNuevo));
cancelNuevo?.addEventListener("click", () =>
  cerrarModal(modalNuevo, formNuevo)
);
closeNuevo?.addEventListener("click", () => cerrarModal(modalNuevo, formNuevo));
cancelEditar?.addEventListener("click", () =>
  cerrarModal(modalEditar, formEditar)
);
closeEditar?.addEventListener("click", () =>
  cerrarModal(modalEditar, formEditar)
);

// ====== RENDER (para uso opcional si generas filas por JS) ======
function crearFilaAlumno(alumno) {
  const fila = document.createElement("tr");
  fila.dataset.id = alumno.id_alumno || "---";
  if (alumno.estatus) fila.dataset.estatus = alumno.estatus;

  const semTexto =
    alumno.nombre_semestre ||
    alumno.nombre_grado ||
    alumno.id_nombre_semestre ||
    "---";

  fila.innerHTML = `
    <td>${safe(alumno.id_alumno)}</td>
    <td>${safe(alumno.nombre)}</td>
    <td>${safe(alumno.apellido_paterno)}</td>
    <td>${safe(alumno.apellido_materno)}</td>
    <td>${safe(alumno.curp)}</td>
    <td>${safe(alumno.fecha_nacimiento)}</td>
    <td>${safe(alumno.sexo)}</td>
    <td>${safe(alumno.telefono)}</td>
    <td>${safe(alumno.direccion)}</td>
    <td>${safe(alumno.correo_personal)}</td>
    <td>${safe(alumno.matricula)}</td>
    <td>${safe(alumno.password)}</td>
    <td>${safe(semTexto)}</td>
    <td>${safe(alumno.contacto_emergencia)}</td>
    <td>${safe(alumno.parentesco_emergencia)}</td>
    <td>${safe(alumno.telefono_emergencia)}</td>
    <td>${safe(alumno.fecha_registro)}</td>
    <td>
      <button class="btn btn-outline btn-sm btn-editar" title="Editar"><i class="fas fa-edit"></i></button>
      <button class="btn btn-outline btn-sm btn-baja" title="Dar de baja / Reactivar"><i class="fas fa-user-slash"></i></button>
      <button class="btn btn-outline btn-sm btn-susp" title="Suspender / Activar"><i class="fas fa-user-clock"></i></button>
      <button class="btn btn-outline btn-sm btn-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
    </td>
  `;
  decorarFilaPorEstatus(fila);
  return fila;
}

// ====== BUSCADOR ======
inputBuscar?.addEventListener("keyup", function () {
  const filtro = (inputBuscar.value || "").toLowerCase();
  const filas = Array.from(tablaBody?.getElementsByTagName("tr") || []);
  filas.forEach((fila) => {
    const textoFila = (fila.innerText || "").toLowerCase();
    fila.style.display = textoFila.includes(filtro) ? "" : "none";
  });
  // Recalcula paginación al buscar
  if (typeof mostrarPagina === "function") mostrarPagina(1);
});

// ====== EXPORTAR (PDF) ======
btnExportar?.addEventListener("click", function () {
  Swal.fire({
    title: "Generando PDF...",
    text: "Por favor espera mientras preparamos la descarga.",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });

  setTimeout(() => {
    window.location.href =
      "../../controladores/secretarias/exportar_alumno.php?tipo=pdf";

    setTimeout(() => {
      Swal.close();
      Swal.fire({
        icon: "success",
        title: "Descarga iniciada",
        text: "Tu archivo PDF se está descargando.",
        timer: 1800,
        showConfirmButton: false,
      });
    }, 1200);
  }, 400);
});

// ====== CREAR ALUMNO ======
formNuevo?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const datos = new FormData(formNuevo);
  datos.append("action", "create");

  try {
    const resp = await fetch(
      "../../controladores/secretarias/controller_alumno.php",
      { method: "POST", body: datos }
    );
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModal(modalNuevo, formNuevo);
      Swal.fire({
        title: "Alumno agregado correctamente",
        html: `
          <p><strong>Matrícula:</strong> ${safe(data.matricula, "N/D")}</p>
          <p><strong>Contraseña:</strong> ${safe(
            data.password_plano || data.password,
            "N/D"
          )}</p>
          <p style="color:gray;font-size:0.9em;">Guarda estos datos antes de continuar.</p>
        `,
        icon: "success",
        confirmButtonText: "Aceptar",
      }).then(() => location.reload());
    } else {
      Swal.fire(
        "Error",
        data.message || "No se pudo agregar el alumno.",
        "error"
      );
    }
  } catch (err) {
    Swal.fire(
      "Error de red",
      err.message || "No se pudo conectar con el servidor.",
      "error"
    );
  }
});

// ====== CARGA INICIAL: DECORAR FILAS POR ESTADO ======
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#tablaBody tr").forEach(decorarFilaPorEstatus);
});

// ====== CLICK EN TABLA: EDITAR / BAJA / SUSPENDER / ELIMINAR ======
tablaBody?.addEventListener("click", async (e) => {
  const fila = e.target.closest("tr");
  if (!fila) return;

  const id = fila.dataset.id || fila.children?.[0]?.textContent?.trim() || "";
  const nombre = (fila.children?.[1]?.textContent || "").trim();

  // --- EDITAR ---
  const btnEditar = e.target.closest(".btn-editar");
  if (btnEditar) {
    idAlumnoSeleccionado = id;
    const fd = new FormData();
    fd.append("action", "get");
    fd.append("id_alumno", id);

    try {
      const resp = await fetch(
        "../../controladores/secretarias/controller_alumno.php",
        { method: "POST", body: fd }
      );
      const data = await resp.json();
      if (data.status !== "success" || !data.alumno)
        throw new Error(data.message || "No se encontró el alumno.");

      const a = data.alumno;

      const map = (idSel, v) => {
        const el = document.querySelector("#" + idSel);
        if (el) el.value = v ?? "";
      };
      map("edit_id_alumno", a.id_alumno);
      map("edit_nombre", a.nombre);
      map("edit_apellido_paterno", a.apellido_paterno);
      map("edit_apellido_materno", a.apellido_materno);
      map("edit_curp", a.curp);
      map("edit_fecha_nacimiento", a.fecha_nacimiento);
      map("edit_sexo", a.sexo);
      map("edit_telefono", a.telefono);
      map("edit_direccion", a.direccion);
      map("edit_correo_personal", a.correo_personal);
      map("edit_matricula", a.matricula);
      map("edit_password", a.password); // solo lectura
      map("edit_id_nombre_semestre", a.id_nombre_semestre);
      map("edit_contacto_emergencia", a.contacto_emergencia);
      map("edit_parentesco_emergencia", a.parentesco_emergencia);
      map("edit_telefono_emergencia", a.telefono_emergencia);

      abrirModal(modalEditar);
    } catch (err) {
      Swal.fire(
        "Error",
        err.message || "No se pudieron cargar los datos.",
        "error"
      );
    }
    return;
  }

  // --- BAJA / REACTIVAR ---
  const btnBaja = e.target.closest(".btn-baja");
  if (btnBaja) {
    try {
      const fdGet = new FormData();
      fdGet.append("action", "get");
      fdGet.append("id_alumno", id);
      const r = await fetch(
        "../../controladores/secretarias/controller_alumno.php",
        { method: "POST", body: fdGet }
      );
      const d = await r.json();
      if (d.status !== "success")
        throw new Error(d.message || "No se pudo obtener el alumno.");

      const al = d.alumno || d;
      const estatus = (al.estatus || "").trim().toLowerCase();
      const esActivo = estatus === "activo" || estatus === "";

      const accion = esActivo ? "baja" : "reactivar";
      const titulo = esActivo
        ? `¿Dar de baja a "${nombre}"?`
        : `¿Reactivar a "${nombre}"?`;
      const confirmText = esActivo ? "Sí, dar de baja" : "Sí, reactivar";

      const { isConfirmed } = await Swal.fire({
        title: titulo,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: "Cancelar",
      });
      if (!isConfirmed) return;

      const fd = new FormData();
      fd.append("action", accion);
      fd.append("id_alumno", id);

      const resp = await fetch(
        "../../controladores/secretarias/controller_alumno.php",
        { method: "POST", body: fd }
      );
      const j = await resp.json();

      if (j.status === "success") {
        Swal.fire({
          icon: "success",
          title: esActivo ? "Alumno dado de baja" : "Alumno reactivado",
          timer: 1400,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire(
          "Error",
          j.message || "No se pudo completar la operación.",
          "error"
        );
      }
    } catch (err) {
      Swal.fire(
        "Error de red",
        err.message || "No se pudo conectar con el servidor.",
        "error"
      );
    }
    return;
  }

  // --- SUSPENDER / ACTIVAR ---
  const btnSusp = e.target.closest(".btn-susp");
  if (btnSusp) {
    try {
      const fdGet = new FormData();
      fdGet.append("action", "get");
      fdGet.append("id_alumno", id);
      const r = await fetch(
        "../../controladores/secretarias/controller_alumno.php",
        { method: "POST", body: fdGet }
      );
      const d = await r.json();
      if (d.status !== "success")
        throw new Error(d.message || "No se pudo obtener el alumno.");

      const al = d.alumno || d;
      const estatus = (al.estatus || "").trim().toLowerCase();
      const esSuspendido = estatus === "suspendido";

      const accion = esSuspendido ? "quitar_suspension" : "suspender";
      const titulo = esSuspendido
        ? `¿Activar a "${nombre}"?`
        : `¿Suspender a "${nombre}"?`;
      const confirmText = esSuspendido ? "Sí, activar" : "Sí, suspender";

      const { isConfirmed } = await Swal.fire({
        title: titulo,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: "Cancelar",
      });
      if (!isConfirmed) return;

      const fd = new FormData();
      fd.append("action", accion);
      fd.append("id_alumno", id);

      const resp = await fetch(
        "../../controladores/secretarias/controller_alumno.php",
        { method: "POST", body: fd }
      );
      const j = await resp.json();

      if (j.status === "success") {
        Swal.fire({
          icon: "success",
          title: esSuspendido ? "Alumno activado" : "Alumno suspendido",
          timer: 1400,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire(
          "Error",
          j.message || "No se pudo completar la operación.",
          "error"
        );
      }
    } catch (err) {
      Swal.fire(
        "Error de red",
        err.message || "No se pudo conectar con el servidor.",
        "error"
      );
    }
    return;
  }

  // --- ELIMINAR ---
  const btnEliminar = e.target.closest(".btn-eliminar");
  if (btnEliminar) {
    Swal.fire({
      title: `¿Eliminar a "${nombre}"?`,
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then(async (res) => {
      if (!res.isConfirmed) return;
      const fd = new FormData();
      fd.append("action", "delete");
      fd.append("id_alumno", id);
      try {
        const resp = await fetch(
          "../../controladores/secretarias/controller_alumno.php",
          { method: "POST", body: fd }
        );
        const j = await resp.json();
        if (j.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Eliminado",
            text: j.message,
            timer: 1500,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire("Error", j.message || "No se pudo eliminar.", "error");
        }
      } catch (err) {
        Swal.fire(
          "Error de red",
          err.message || "No se pudo conectar.",
          "error"
        );
      }
    });
  }
});

// ====== EDITAR (SUBMIT) ======
formEditar?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const idHidden =
    document.getElementById("edit_id_alumno")?.value || idAlumnoSeleccionado;
  if (!idHidden) return;

  const formData = new FormData(formEditar);
  formData.append("action", "edit");
  formData.append("id_alumno", idHidden);

  try {
    const resp = await fetch(
      "../../controladores/secretarias/controller_alumno.php",
      { method: "POST", body: formData }
    );
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModal(modalEditar, formEditar);
      Swal.fire({
        icon: "success",
        title: "Alumno actualizado",
        html: `<p style="color:gray;font-size:0.9em;margin-top:8px;">Los cambios se han guardado correctamente.</p>`,
        timer: 1800,
        showConfirmButton: false,
      }).then(() => location.reload());
    } else {
      Swal.fire("Error", data.message || "Error al actualizar", "error");
    }
  } catch (err) {
    Swal.fire("Error de red", err.message || "No se pudo conectar", "error");
  }
});

// ====== PAGINACIÓN + BUSCADOR ======
document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("tablaAlumnos");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const pagination = document.getElementById("paginationAlumno");
  const searchInput = document.getElementById("buscarAlumno");

  const ROWS_PER_PAGE = 5;
  let currentPage = 1;
  const allRows = Array.from(tbody.querySelectorAll("tr"));

  const getFilteredRows = () => {
    const q = (searchInput?.value || "").trim().toLowerCase();
    if (!q) return allRows;
    return allRows.filter((tr) => tr.innerText.toLowerCase().includes(q));
  };

  const paginate = (rows, page, perPage) => {
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    page = Math.min(Math.max(page, 1), totalPages);

    allRows.forEach((tr) => (tr.style.display = "none"));
    const start = (page - 1) * perPage;
    const end = start + perPage;
    rows.slice(start, end).forEach((tr) => (tr.style.display = ""));

    renderPagination(totalPages, page);
    currentPage = page;
  };

  const renderPagination = (totalPages, page) => {
    if (!pagination) return;
    pagination.innerHTML = "";

    const mkBtn = (num, label = null, disabled = false, active = false) => {
      const b = document.createElement("button");
      b.className = "pagination-btn";
      b.textContent = label ?? num;
      if (active) b.classList.add("active");
      b.disabled = disabled;
      b.addEventListener("click", () => goToPage(num));
      return b;
    };

    pagination.appendChild(mkBtn(page - 1, "«", page === 1));

    const windowSize = 1;
    const addDots = () => {
      const s = document.createElement("span");
      s.textContent = "…";
      s.style.padding = "6px";
      s.style.color = "#999";
      pagination.appendChild(s);
    };

    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || Math.abs(i - page) <= windowSize) {
        pagination.appendChild(mkBtn(i, null, false, i === page));
      } else if (
        (i === 2 && page > windowSize + 2) ||
        (i === totalPages - 1 && page < totalPages - windowSize - 1)
      ) {
        addDots();
      }
    }

    pagination.appendChild(mkBtn(page + 1, "»", page === totalPages));
  };

  const goToPage = (p) => paginate(getFilteredRows(), p, ROWS_PER_PAGE);

  searchInput?.addEventListener("keyup", () =>
    paginate(getFilteredRows(), 1, ROWS_PER_PAGE)
  );

  paginate(getFilteredRows(), 1, ROWS_PER_PAGE);

  // Al finalizar la paginación inicial, asegurar decoración por si cambia display
  document.querySelectorAll("#tablaBody tr").forEach(decorarFilaPorEstatus);
});
