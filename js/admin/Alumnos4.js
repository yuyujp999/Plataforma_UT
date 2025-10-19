// ===== VARIABLES GLOBALES =====
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

// ===== UTILIDADES =====
function abrirModal(modal) {
  modal.classList.add("active");
}
function cerrarModal(modal, form) {
  modal.classList.remove("active");
  form.reset();
}
function getSelectedText(sel) {
  const el = typeof sel === "string" ? document.querySelector(sel) : sel;
  const opt = el?.options?.[el.selectedIndex];
  return opt ? opt.textContent.trim() : "";
}

// ===== ABRIR / CERRAR MODALES =====
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

// ===== RENDER TABLA =====
function crearFilaAlumno(alumno) {
  const fila = document.createElement("tr");
  fila.dataset.id = alumno.id_alumno || "---";
  const semTexto =
    alumno.nombre_semestre ||
    alumno.nombre_grado ||
    alumno.id_nombre_semestre ||
    "---";

  fila.innerHTML = `
    <td>${alumno.id_alumno || "---"}</td>
    <td>${alumno.nombre || "---"}</td>
    <td>${alumno.apellido_paterno || "---"}</td>
    <td>${alumno.apellido_materno || "---"}</td>
    <td>${alumno.curp || "---"}</td>
    <td>${alumno.fecha_nacimiento || "---"}</td>
    <td>${alumno.sexo || "---"}</td>
    <td>${alumno.telefono || "---"}</td>
    <td>${alumno.direccion || "---"}</td>
    <td>${alumno.correo_personal || "---"}</td>
    <td>${alumno.matricula || "---"}</td>
    <td>${alumno.password || "---"}</td>
    <td>${semTexto}</td>
    <td>${alumno.contacto_emergencia || "---"}</td>
    <td>${alumno.parentesco_emergencia || "---"}</td>
    <td>${alumno.telefono_emergencia || "---"}</td>
    <td>${alumno.fecha_registro || "---"}</td>
    <td>
      <button class="btn btn-outline btn-sm btn-editar" title="Editar"><i class="fas fa-edit"></i></button>
      <button class="btn btn-outline btn-sm btn-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
    </td>
  `;
  return fila;
}

// ===== BUSQUEDA =====
inputBuscar?.addEventListener("keyup", function () {
  const filtro = (inputBuscar.value || "").toLowerCase();
  const filas = Array.from(tablaBody.getElementsByTagName("tr"));
  filas.forEach((fila) => {
    const textoFila = fila.innerText.toLowerCase();
    fila.style.display = textoFila.includes(filtro) ? "" : "none";
  });
  mostrarPagina(1);
});

// ===== EXPORTAR ALUMNOS (solo PDF) =====
document.getElementById("btnExportar")?.addEventListener("click", function () {
  Swal.fire({
    title: "Generando PDF...",
    text: "Por favor espera mientras preparamos la descarga.",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading(); // spinner de carga
    },
  });

  // Pequeño retraso para una UX suave (opcional)
  setTimeout(() => {
    // Redirige al controlador de descarga de ALUMNOS
    window.location.href =
      "../../controladores/admin/exportar_alumno.php?tipo=pdf";

    // Cierra el spinner y muestra confirmación
    setTimeout(() => {
      Swal.close();
      Swal.fire({
        icon: "success",
        title: "Descarga iniciada",
        text: "Tu archivo PDF se está descargando.",
        timer: 1800,
        showConfirmButton: false,
      });
    }, 1500);
  }, 500);
});

// ===== CREAR ALUMNO =====
formNuevo?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const datos = new FormData(formNuevo);
  datos.append("action", "create");

  try {
    const resp = await fetch(
      "../../controladores/admin/controller_alumno.php",
      {
        method: "POST",
        body: datos,
      }
    );
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModal(modalNuevo, formNuevo); // 🔒 cerrar modal antes del alert
      Swal.fire({
        title: "Alumno agregado correctamente",
        html: `<p><strong>Matrícula:</strong> ${data.matricula}</p>
               <p><strong>Contraseña:</strong> ${data.password}</p>
               <p style="color:gray;font-size:0.9em;">Guarda estos datos antes de continuar.</p>`,
        icon: "success",
        confirmButtonText: "Aceptar",
      }).then(() => {
        location.reload(); // 🔄 recarga después de aceptar
      });
    } else {
      Swal.fire(
        "Error",
        data.message || "No se pudo agregar el alumno.",
        "error"
      );
    }
  } catch (err) {
    Swal.fire("Error de red", err.message, "error");
  }
});

// ===== CLICK EN TABLA: EDITAR / ELIMINAR =====
tablaBody?.addEventListener("click", async (e) => {
  const btnEditar = e.target.closest(".btn-editar");
  const btnEliminar = e.target.closest(".btn-eliminar");
  const fila = e.target.closest("tr");
  if (!fila) return;
  const id = fila.dataset.id;

  // === EDITAR ===
  if (btnEditar) {
    idAlumnoSeleccionado = id;
    const formData = new FormData();
    formData.append("action", "get");
    formData.append("id_alumno", id);

    try {
      const resp = await fetch(
        "../../controladores/admin/controller_alumno.php",
        {
          method: "POST",
          body: formData,
        }
      );
      const data = await resp.json();
      if (data.status !== "success" || !data.alumno)
        throw new Error(data.message);

      const a = data.alumno;
      Object.keys(a).forEach((campo) => {
        const input = document.querySelector(`#edit_${campo}`);
        if (input) input.value = a[campo] ?? "";
      });
      abrirModal(modalEditar);
    } catch (err) {
      Swal.fire("Error", err.message, "error");
    }
  }

  // === ELIMINAR ===
  if (btnEliminar) {
    Swal.fire({
      title: `¿Eliminar a "${fila.children[1]?.textContent}"?`,
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then(async (result) => {
      if (!result.isConfirmed) return;

      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("id_alumno", id);

      try {
        const resp = await fetch(
          "../../controladores/admin/controller_alumno.php",
          {
            method: "POST",
            body: formData,
          }
        );
        const data = await resp.json();
        if (data.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Eliminado",
            text: data.message,
            timer: 1500,
            showConfirmButton: false,
          }).then(() => {
            location.reload(); // 🔄 recarga después de eliminar
          });
        } else {
          Swal.fire("Error", data.message || "No se pudo eliminar.", "error");
        }
      } catch (err) {
        Swal.fire("Error de red", err.message, "error");
      }
    });
  }
});

// ===== EDITAR (SUBMIT) =====
formEditar?.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (!idAlumnoSeleccionado) return;

  const formData = new FormData(formEditar);
  formData.append("action", "edit");
  formData.append("id_alumno", idAlumnoSeleccionado);

  try {
    const resp = await fetch(
      "../../controladores/admin/controller_alumno.php",
      {
        method: "POST",
        body: formData,
      }
    );
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModal(modalEditar, formEditar); // 🔒 cerrar modal antes del alert
      Swal.fire({
        icon: "success",
        title: "Alumno actualizado",
        html: `<p style="color:gray;font-size:0.9em;margin-top:8px;">
                Los cambios se han guardado correctamente.
              </p>`,
        timer: 1800,
        showConfirmButton: false,
      }).then(() => {
        location.reload(); // 🔄 recarga después de cerrar alerta
      });
    } else {
      Swal.fire("Error", data.message || "Error al actualizar", "error");
    }
  } catch (err) {
    Swal.fire("Error de red", err.message, "error");
  }
});
// ===== PAGINACIÓN Y BUSCADOR DE ALUMNOS =====
document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("tablaAlumnos");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const pagination = document.getElementById("paginationAlumno");
  const searchInput = document.getElementById("buscarAlumno");

  const ROWS_PER_PAGE = 5;
  let currentPage = 1;
  const allRows = Array.from(tbody.querySelectorAll("tr"));

  // ===== Helpers =====
  const getFilteredRows = () => {
    const q = (searchInput?.value || "").trim().toLowerCase();
    if (!q) return allRows;
    return allRows.filter((tr) => tr.innerText.toLowerCase().includes(q));
  };

  const paginate = (rows, page, perPage) => {
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (page > totalPages) page = totalPages;
    if (page < 1) page = 1;

    // Ocultar todas las filas
    allRows.forEach((tr) => {
      tr.style.display = "none";
    });

    // Mostrar las que correspondan
    const start = (page - 1) * perPage;
    const end = start + perPage;
    rows.slice(start, end).forEach((tr) => {
      tr.style.display = "";
    });

    renderPagination(totalPages, page);
    currentPage = page;
  };

  const renderPagination = (totalPages, page) => {
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

    // Botón « anterior
    pagination.appendChild(mkBtn(page - 1, "«", page === 1));

    // Números con puntos suspensivos
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

    // Botón » siguiente
    pagination.appendChild(mkBtn(page + 1, "»", page === totalPages));
  };

  const goToPage = (p) => {
    paginate(getFilteredRows(), p, ROWS_PER_PAGE);
  };

  // ===== Buscador =====
  if (searchInput) {
    searchInput.addEventListener("keyup", () => {
      paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
    });
  }

  // ===== Inicializar =====
  paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
});
