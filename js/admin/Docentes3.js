// =============== SELECTORES BÁSICOS ===============
const modal = document.getElementById("modalNuevo");
const btnNuevo = document.getElementById("btnNuevo");
const cancel = document.getElementById("cancelModal");
const closeX = document.getElementById("closeModal");
const form = document.getElementById("formNuevo");

const tablaBody = document.getElementById("tablaBody"); // <tbody id="tablaBody">
const tabla = tablaBody; // para delegación de eventos

const inputBuscar = document.getElementById("buscarDocente");

// =============== HELPERS DE UI ===============
function cerrarModal() {
  modal.classList.remove("active");
  if (form) form.reset();
}

function safe(v, fallback = "—") {
  return (v ?? "").toString().trim() || fallback;
}

// Nota: el controller NO devuelve password en ningún GET.
// Si tu tabla tiene columna "password", la dejamos como "—".
function crearFilaDocente(docente = {}) {
  const fila = document.createElement("tr");
  const idDocente = docente.id_docente ? String(docente.id_docente) : "";
  fila.dataset.id = idDocente;

  // Orden de columnas ACTUAL (sin departamento ni num_empleado)
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
    <td>—</td>
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
      <button class="btn btn-outline btn-sm btn-editar" title="Editar"><i class="fas fa-edit"></i></button>
      <button class="btn btn-outline btn-sm btn-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
    </td>
  `;
  return fila;
}

// =============== EVENTOS MODAL NUEVO ===============
if (btnNuevo)
  btnNuevo.addEventListener("click", () => modal.classList.add("active"));
if (cancel) cancel.addEventListener("click", cerrarModal);
if (closeX) closeX.addEventListener("click", cerrarModal);

// =============== BUSCADOR EN TABLA ===============
if (inputBuscar) {
  inputBuscar.addEventListener("keyup", function () {
    const filtro = inputBuscar.value.toLowerCase();
    const filas = tablaBody.getElementsByTagName("tr");

    for (let i = 0; i < filas.length; i++) {
      const celdas = filas[i].getElementsByTagName("td");
      let encontrado = false;

      // -1 para ignorar la columna de "Acciones"
      for (let j = 0; j < celdas.length - 1; j++) {
        const valor = celdas[j].textContent.toLowerCase();
        if (valor.indexOf(filtro) > -1) {
          encontrado = true;
          break;
        }
      }
      filas[i].style.display = encontrado ? "" : "none";
    }
  });
}

// ===== EXPORTAR DOCENTES (solo PDF) =====
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
    // Redirige al controlador de descarga de DOCENTES
    window.location.href =
      "../../controladores/admin/exportar_docente.php?tipo=pdf";

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

// =============== CREAR DOCENTE ===============
if (form) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(form);
    datos.append("action", "create");

    try {
      const resp = await fetch(
        "../../controladores/admin/controller_docente.php",
        {
          method: "POST",
          body: datos,
        }
      );
      const data = await resp.json();

      if (data.status === "success") {
        // El controller devuelve: status, message, matricula, password_plano
        Swal.fire({
          title: "<strong>Docente agregado correctamente</strong>",
          icon: "success",
          html: `
            <div style="text-align:left; margin-top:10px;">
              <p>Matrícula: <b>${safe(data.matricula, "N/D")}</b></p>
              <p>Contraseña: <b>${safe(data.password_plano, "N/D")}</b></p>
              <small>Guárdala ahora. No se volverá a mostrar.</small>
            </div>
          `,
          confirmButtonText: "Aceptar",
        }).then(() => {
          cerrarModal();
          // Como el controller no regresa id_docente, recargamos para sincronizar ID/tabla.
          location.reload();
        });
      } else {
        Swal.fire(
          "Error",
          data.message || "No se pudo agregar el docente.",
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
}

// =============== MODAL EDITAR ===============
document.addEventListener("DOMContentLoaded", () => {
  const modalEditar = document.getElementById("modalEditar");
  const formEditar = document.getElementById("formEditar");
  const cancelEditar = document.getElementById("cancelEditar");
  const closeEditar = document.getElementById("closeEditar");

  let idDocenteSeleccionado = null;

  // Abrir modal con datos
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

    const formData = new FormData();
    formData.append("action", "get");
    formData.append("id_docente", idDocenteSeleccionado);

    try {
      const resp = await fetch(
        "../../controladores/admin/controller_docente.php",
        {
          method: "POST",
          body: formData,
        }
      );
      const data = await resp.json();
      if (data.status !== "success")
        throw new Error(data.message || "Error al obtener el docente.");

      // Campos a poblar en el modal de edición (SIN departamento, SIN num_empleado)
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
        const input = formEditar.querySelector(`#edit_${campo}`);
        if (input) input.value = data[campo] ?? "";
      });

      // matricula (viene en GET, de solo lectura)
      const inputMat = formEditar.querySelector("#edit_matricula");
      if (inputMat) inputMat.value = data.matricula ?? "";

      // password NO viene en GET (seguridad). Lo dejamos vacío.
      const inputPass = formEditar.querySelector("#edit_password");
      if (inputPass) {
        inputPass.value = "";
        inputPass.placeholder = "No disponible (no editable)";
        inputPass.readOnly = true;
      }

      modalEditar.classList.add("active");
    } catch (err) {
      Swal.fire(
        "Error",
        err.message || "No se pudieron cargar los datos.",
        "error"
      );
    }
  });

  // Cerrar modal editar
  function cerrarModalEditar() {
    modalEditar.classList.remove("active");
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
      const resp = await fetch(
        "../../controladores/admin/controller_docente.php",
        {
          method: "POST",
          body: formData,
        }
      );
      const data = await resp.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Docente actualizado",
          text: "Se actualizó correctamente",
          timer: 1500,
          showConfirmButton: false,
        }).then(() => {
          // Más fiable recargar para reflejar todos los cambios del servidor
          location.reload();
        });
        cerrarModalEditar();
      } else {
        Swal.fire("Error", data.message || "Error al actualizar", "error");
      }
    } catch (err) {
      Swal.fire("Error de red", err.message || "No se pudo conectar", "error");
    }
  });
});

// =============== ELIMINAR DOCENTE ===============
tabla.addEventListener("click", async (e) => {
  const btnEliminar = e.target.closest(".btn-eliminar");
  if (!btnEliminar) return;

  const fila = btnEliminar.closest("tr");
  const id =
    fila?.dataset?.id || fila?.children?.[0]?.textContent?.trim() || "";
  const nombre = fila?.children?.[1]?.textContent || "";
  if (!id) return;

  Swal.fire({
    title: `¿Eliminar a "${nombre}"?`,
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "No, cancelar",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("id_docente", id);

        const resp = await fetch(
          "../../controladores/admin/controller_docente.php",
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
            showConfirmButton: false,
            timer: 1500,
          }).then(() => location.reload());
        } else {
          Swal.fire(
            "Error",
            data.message || "No se pudo eliminar el docente.",
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
    }
  });
});
// ================= PAGINACIÓN + BUSCADOR (DOCENTES) =================
document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("tablaDocentes");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const pagination = document.getElementById("paginationDocentes"); // <div class="pagination-container" id="paginationDocentes"></div>
  const searchInput = document.getElementById("buscarDocente");

  const ROWS_PER_PAGE = 5; // cámbialo a 5 si quieres menos filas por página
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

    // Ocultar todo
    allRows.forEach((tr) => {
      tr.style.display = "none";
    });

    // Mostrar solo las filas correspondientes
    const start = (page - 1) * perPage;
    const end = start + perPage;
    rows.slice(start, end).forEach((tr) => {
      tr.style.display = "";
    });

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

    // Botón «
    pagination.appendChild(mkBtn(page - 1, "«", page === 1));

    // Ventana de números con puntos (…)
    const windowSize = 1; // muestra el actual ±1
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

    // Botón »
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
