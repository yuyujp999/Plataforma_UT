// =============== CONSTANTES Y SELECTORES BÁSICOS ===============
const CTRL_URL = "../../controladores/secretarias/controller_docente.php";
const EXPORT_URL = "../../controladores/secretarias/exportar_docente.php";

const modal = document.getElementById("modalNuevo");
const btnNuevo = document.getElementById("btnNuevo");
const cancel = document.getElementById("cancelModal");
const closeX = document.getElementById("closeModal");
const form = document.getElementById("formNuevo");

const tablaBody = document.getElementById("tablaBody"); // <tbody id="tablaBody">
const tabla = tablaBody; // delegación de eventos
const inputBuscar = document.getElementById("buscarDocente");

// =============== HELPERS GENERALES ===============
function cerrarModal() {
  modal?.classList.remove("active");
  if (form) form.reset();
}
function safe(v, fallback = "—") {
  return (v ?? "").toString().trim() || fallback;
}
function toastOK(titulo, texto = "", ms = 1500) {
  return Swal.fire({
    icon: "success",
    title: titulo,
    text: texto,
    timer: ms,
    showConfirmButton: false,
  });
}
function toastError(titulo = "Error", texto = "Ocurrió un error") {
  return Swal.fire(titulo, texto, "error");
}
async function postAccion(payloadFormData) {
  const resp = await fetch(CTRL_URL, { method: "POST", body: payloadFormData });
  let data;
  try {
    data = await resp.json();
  } catch {
    throw new Error("Respuesta inválida del servidor.");
  }
  if (!resp.ok) throw new Error(data.message || `HTTP ${resp.status}`);
  return data;
}
async function getDocentePorId(id) {
  const fd = new FormData();
  fd.append("action", "get");
  fd.append("id_docente", id);
  const d = await postAccion(fd);
  return d.docente || d.alumno || d;
}
async function confirmar({ title, confirmText }) {
  const res = await Swal.fire({
    title,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: "Cancelar",
  });
  return res.isConfirmed;
}

// =============== ESTILOS / DECORADORES ===============
function decorarFilaPorEstatus(tr) {
  const est = (tr?.dataset?.estatus || "").toLowerCase(); // activo | baja | suspendido | ''
  const nombreCell = tr.children?.[1];
  const btnBaja = tr.querySelector(".btn-baja");
  const btnSusp = tr.querySelector(".btn-suspender");

  tr.classList?.remove?.("row-baja", "row-suspendido");
  if (est === "baja") tr.classList.add("row-baja");
  if (est === "suspendido") tr.classList.add("row-suspendido");

  // badge junto al nombre
  if (nombreCell) {
    let badge = nombreCell.querySelector(".estado-badge");
    if (est && est !== "activo") {
      if (!badge) {
        badge = document.createElement("span");
        badge.className = `estado-badge ${est}`;
        badge.textContent = est;
        nombreCell.appendChild(badge);
      } else {
        badge.className = `estado-badge ${est}`;
        badge.textContent = est;
      }
    } else if (badge) {
      badge.remove();
    }
  }

  // Botón Baja/Reactivar
  if (btnBaja) {
    if (est === "baja") {
      btnBaja.innerHTML = '<i class="fas fa-user-check"></i> Reactivar';
      btnBaja.title = "Reactivar docente";
      btnBaja.dataset.modo = "reactivar";
    } else {
      btnBaja.innerHTML = '<i class="fas fa-user-slash"></i> Baja';
      btnBaja.title = "Dar de baja";
      btnBaja.dataset.modo = "baja";
    }
  }

  // Botón Suspender/Quitar
  if (btnSusp) {
    if (est === "suspendido") {
      btnSusp.innerHTML = '<i class="fas fa-user-check"></i> Quitar suspensión';
      btnSusp.title = "Quitar suspensión";
      btnSusp.dataset.modo = "quitar";
    } else {
      btnSusp.innerHTML = '<i class="fas fa-user-clock"></i> Suspender';
      btnSusp.title = "Suspender";
      btnSusp.dataset.modo = "suspender";
    }
  }
}

// =============== FILAS ===============
function crearFilaDocente(docente = {}) {
  const fila = document.createElement("tr");
  const idDocente = docente.id_docente ? String(docente.id_docente) : "";
  fila.dataset.id = idDocente;
  if (docente.estatus)
    fila.dataset.estatus = String(docente.estatus).toLowerCase();

  fila.innerHTML = `
    <td>${safe(docente.id_docente)}</td>
    <td>${safe(docente.nombre)}</td>
    <td>${safe(docente.apellido_paterno)}</td>
    <td>${safe(docente.apellido_materno)}</td>
    <td>${safe(docente.curp)}</td>
    <td>${safe(docente.rfc)}</td>
    <td>${safe(docente.fecha_nacimiento)}</td>
    <td>${safe(docente.sexo)}</td>
    <td>${safe(docente.telefono)}</td>
    <td>${safe(docente.direccion)}</td>
    <td>${safe(docente.correo_personal)}</td>
    <td>${safe(docente.matricula)}</td>
    <td>${safe(docente.nivel_estudios)}</td>
    <td>${safe(docente.area_especialidad)}</td>
    <td>${safe(docente.universidad_egreso)}</td>
    <td>${safe(docente.cedula_profesional)}</td>
    <td>${safe(docente.idiomas)}</td>
    <td>${safe(docente.puesto)}</td>
    <td>${safe(docente.tipo_contrato)}</td>
    <td>${safe(docente.fecha_ingreso)}</td>
    <td>${safe(docente.contacto_emergencia)}</td>
    <td>${safe(docente.parentesco_emergencia)}</td>
    <td>${safe(docente.telefono_emergencia)}</td>
    <td>${safe(docente.fecha_registro)}</td>
    <td>
      <div class="acciones-group" style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn btn-outline btn-sm btn-editar" title="Editar">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-outline btn-sm btn-baja" title="Dar de baja / Reactivar">
          <i class="fas fa-user-slash"></i> Baja
        </button>
        <button class="btn btn-outline btn-sm btn-suspender" title="Suspender / Quitar suspensión">
          <i class="fas fa-user-clock"></i> Suspender
        </button>
      </div>
    </td>
  `;
  decorarFilaPorEstatus(fila);
  return fila;
}

// =============== EVENTOS MODAL NUEVO ===============
btnNuevo?.addEventListener("click", () => modal?.classList.add("active"));
cancel?.addEventListener("click", cerrarModal);
closeX?.addEventListener("click", cerrarModal);

// =============== BUSCADOR EN TABLA (rápido) ===============
if (inputBuscar && tablaBody) {
  inputBuscar.addEventListener("keyup", function () {
    const filtro = inputBuscar.value.toLowerCase();
    const filas = tablaBody.getElementsByTagName("tr");
    for (let i = 0; i < filas.length; i++) {
      const celdas = filas[i].getElementsByTagName("td");
      let encontrado = false;
      for (let j = 0; j < celdas.length - 1; j++) {
        const valor = (celdas[j].textContent || "").toLowerCase();
        if (valor.indexOf(filtro) > -1) {
          encontrado = true;
          break;
        }
      }
      filas[i].style.display = encontrado ? "" : "none";
    }
  });
}

// ===== EXPORTAR DOCENTES (PDF) =====
document.getElementById("btnExportar")?.addEventListener("click", function () {
  Swal.fire({
    title: "Generando PDF...",
    text: "Por favor espera mientras preparamos la descarga.",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });

  setTimeout(() => {
    window.location.href = `${EXPORT_URL}?tipo=pdf`;
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

// =============== CREAR DOCENTE ===============
if (form) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(form);
    datos.append("action", "create");

    try {
      const data = await postAccion(datos);

      if (data.status === "success") {
        Swal.fire({
          title: "<strong>Docente agregado correctamente</strong>",
          icon: "success",

          html: `
            <div style="text-align:left; margin-top:10px;">
              <p>Matrícula: <b>${safe(data.matricula, "N/D")}</b></p>
              <p>Contraseña: <b>${safe(
                data.password_plano || data.password,
                "N/D"
              )}</b></p>
              <small>Guárdala ahora. No se volverá a mostrar.</small>
            </div>
          `,
          confirmButtonText: "Aceptar",
        }).then(() => {
          cerrarModal();
          location.reload(); // El backend no devuelve todos los campos; recargamos
        });
      } else {
        toastError("Error", data.message || "No se pudo agregar el docente.");
      }
    } catch (err) {
      toastError(
        "Error de red",
        err.message || "No se pudo conectar con el servidor."
      );
    }
  });
}

// =============== MODAL EDITAR ===============
document.addEventListener("DOMContentLoaded", () => {
  const modalEditar = document.getElementById("modalEditar");
  const formEditar = document.getElementById("formEditar");
  const cancelEditar = document.getElementById("cancelEditar");
  const closeEditar = document.getElementById("closeEditar");

  let idDocenteSeleccionado = null;

  // Abrir modal con datos
  if (tabla) {
    tabla.addEventListener("click", async (e) => {
      const btn = e.target.closest(".btn-editar");
      if (!btn) return;

      const fila = btn.closest("tr");
      idDocenteSeleccionado =
        fila?.dataset?.id || fila?.children?.[0]?.textContent?.trim() || null;
      if (!idDocenteSeleccionado) {
        Swal.fire(
          "Aviso",
          "No se pudo identificar el ID del docente.",
          "warning"
        );
        return;
      }

      try {
        const d = await getDocentePorId(idDocenteSeleccionado);

        const camposEditar = [
          "nombre",
          "apellido_paterno",
          "apellido_materno",
          "curp",
          "rfc",
          "fecha_nacimiento",
          "sexo",
          "telefono",
          "direccion",
          "correo_personal",
          "nivel_estudios",
          "area_especialidad",
          "universidad_egreso",
          "cedula_profesional",
          "idiomas",
          "puesto",
          "tipo_contrato",
          "fecha_ingreso",
          "contacto_emergencia",
          "parentesco_emergencia",
          "telefono_emergencia",
        ];

        camposEditar.forEach((campo) => {
          const input = formEditar?.querySelector(`#edit_${campo}`);
          if (input) input.value = d[campo] ?? "";
        });

        const inputMat = formEditar?.querySelector("#edit_matricula");
        if (inputMat) inputMat.value = d.matricula ?? "";

        const inputPass = formEditar?.querySelector("#edit_password");
        if (inputPass) {
          inputPass.value = "";
          inputPass.placeholder = "No disponible (no editable)";
          inputPass.readOnly = true;
        }

        modalEditar?.classList.add("active");
      } catch (err) {
        toastError("Error", err.message || "No se pudieron cargar los datos.");
      }
    });
  }

  // Cerrar modal editar
  function cerrarModalEditar() {
    modalEditar?.classList.remove("active");
    formEditar?.reset();
  }
  cancelEditar?.addEventListener("click", cerrarModalEditar);
  closeEditar?.addEventListener("click", cerrarModalEditar);

  // Guardar cambios
  formEditar?.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!idDocenteSeleccionado) return;

    const formData = new FormData(formEditar);
    formData.append("action", "edit");
    formData.append("id_docente", idDocenteSeleccionado);

    try {
      const data = await postAccion(formData);
      if (data.status === "success") {
        toastOK("Docente actualizado", "Se actualizó correctamente").then(() =>
          location.reload()
        );
        cerrarModalEditar();
      } else {
        toastError("Error", data.message || "Error al actualizar");
      }
    } catch (err) {
      toastError("Error de red", err.message || "No se pudo conectar");
    }
  });
});

// =============== BAJA / REACTIVAR / SUSPENDER / QUITAR SUSPENSIÓN ===============
if (tabla) {
  tabla.addEventListener("click", async (e) => {
    // --- Baja / Reactivar ---
    const btnBaja = e.target.closest(".btn-baja");
    if (btnBaja) {
      const fila = btnBaja.closest("tr");
      const id =
        fila?.dataset?.id || fila?.children?.[0]?.textContent?.trim() || "";
      const nombre = fila?.children?.[1]?.textContent || "";
      if (!id) return;

      try {
        const doc = await getDocentePorId(id);
        const estatus = (doc.estatus || "").toLowerCase();
        const esActivo = estatus === "activo" || estatus === "";

        const accion = esActivo ? "baja" : "reactivar";
        const titulo = esActivo
          ? `¿Dar de baja a "${nombre}"?`
          : `¿Reactivar a "${nombre}"?`;
        const confirmText = esActivo ? "Sí, dar de baja" : "Sí, reactivar";

        if (!(await confirmar({ title: titulo, confirmText }))) return;

        const fd = new FormData();
        fd.append("action", accion);
        fd.append("id_docente", id);

        const data = await postAccion(fd);
        if (data.status === "success") {
          toastOK(
            esActivo ? "Docente dado de baja" : "Docente reactivado"
          ).then(() => location.reload());
        } else {
          toastError(
            "Error",
            data.message || "No se pudo completar la operación."
          );
        }
      } catch (err) {
        toastError(
          "Error de red",
          err.message || "No se pudo conectar con el servidor."
        );
      }
      return; // evita caer en handler de suspender
    }

    // --- Suspender / Quitar suspensión ---
    const btnSusp = e.target.closest(".btn-suspender");
    if (btnSusp) {
      const fila = btnSusp.closest("tr");
      const id =
        fila?.dataset?.id || fila?.children?.[0]?.textContent?.trim() || "";
      const nombre = fila?.children?.[1]?.textContent || "";
      if (!id) return;

      try {
        const doc = await getDocentePorId(id);
        const estatus = (doc.estatus || "").toLowerCase();

        const estaSuspendido = estatus === "suspendido";
        const accion = estaSuspendido ? "quitar_suspension" : "suspender";
        const titulo = estaSuspendido
          ? `¿Quitar suspensión a "${nombre}"?`
          : `¿Suspender temporalmente a "${nombre}"?`;
        const confirmText = estaSuspendido ? "Sí, quitar" : "Sí, suspender";

        if (!(await confirmar({ title: titulo, confirmText }))) return;

        const fd = new FormData();
        fd.append("action", accion);
        fd.append("id_docente", id);

        const data = await postAccion(fd);
        if (data.status === "success") {
          toastOK(
            estaSuspendido ? "Suspensión retirada" : "Docente suspendido"
          ).then(() => location.reload());
        } else {
          toastError(
            "Error",
            data.message || "No se pudo completar la operación."
          );
        }
      } catch (err) {
        toastError(
          "Error de red",
          err.message || "No se pudo conectar con el servidor."
        );
      }
    }
  });
}

// ================= PAGINACIÓN + BUSCADOR (DOCENTES) =================
document.addEventListener("DOMContentLoaded", () => {
  // Decorar todas las filas por estatus al cargar
  document.querySelectorAll("#tablaBody tr").forEach(decorarFilaPorEstatus);

  const table = document.getElementById("tablaDocentes");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const pagination = document.getElementById("paginationDocentes");
  const searchInput = document.getElementById("buscarDocente");

  const ROWS_PER_PAGE = 5;
  let currentPage = 1;
  const allRows = Array.from(tbody.querySelectorAll("tr"));

  const getFilteredRows = () => {
    const q = (searchInput?.value || "").trim().toLowerCase();
    if (!q) return allRows;
    return allRows.filter((tr) =>
      (tr.innerText || "").toLowerCase().includes(q)
    );
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
});
