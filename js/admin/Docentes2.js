// ===== Modal NUEVO =====
const modal = document.getElementById("modalNuevo");
const btnNuevo = document.getElementById("btnNuevo");
const cancel = document.getElementById("cancelModal");
const closeX = document.getElementById("closeModal");
const form = document.getElementById("formNuevo");
const tabla = document.querySelector("#tablaDocentes tbody");
const inputBuscar = document.getElementById("buscarDocente");
const tablaBody = document.getElementById("tablaBody");

function cerrarModal() {
  modal.classList.remove("active");
  form.reset();
}

btnNuevo.addEventListener("click", () => modal.classList.add("active"));
cancel.addEventListener("click", cerrarModal);
closeX.addEventListener("click", cerrarModal);

// ===== FUNCIONES UTILES =====
function crearFilaDocente(docente) {
  const fila = document.createElement("tr");
  fila.dataset.id = docente.id_docente || "---";
  fila.innerHTML = `
    <td>${docente.id_docente || "---"}</td>
    <td>${docente.nombre || "---"}</td>
    <td>${docente.apellido_paterno || "---"}</td>
    <td>${docente.apellido_materno || "---"}</td>
    <td>${docente.curp || "---"}</td>
    <td>${docente.rfc || "---"}</td>
    <td>${docente.fecha_nacimiento || "---"}</td>
    <td>${docente.sexo || "---"}</td>
    <td>${docente.telefono || "---"}</td>
    <td>${docente.direccion || "---"}</td>
    <td>${docente.correo_personal || "---"}</td>
    <td>${docente.matricula || "---"}</td>
    <td>${docente.password || "---"}</td>
    <td>${docente.nivel_estudios || "---"}</td>
    <td>${docente.area_especialidad || "---"}</td>
    <td>${docente.universidad_egreso || "---"}</td>
    <td>${docente.cedula_profesional || "---"}</td>
    <td>${docente.idiomas || "---"}</td>
    <td>${docente.departamento || "---"}</td>
    <td>${docente.puesto || "---"}</td>
    <td>${docente.tipo_contrato || "---"}</td>
    <td>${docente.fecha_ingreso || "---"}</td>
    <td>${docente.num_empleado || "---"}</td>
    <td>${docente.contacto_emergencia || "---"}</td>
    <td>${docente.parentesco_emergencia || "---"}</td>
    <td>${docente.telefono_emergencia || "---"}</td>
    <td>${docente.fecha_registro || "---"}</td>
    <td>
      <button class="btn btn-outline btn-sm btn-editar"><i class="fas fa-edit"></i></button>
      <button class="btn btn-outline btn-sm btn-eliminar"><i class="fas fa-trash"></i></button>
    </td>
  `;
  return fila;
}

inputBuscar.addEventListener("keyup", function () {
  const filtro = inputBuscar.value.toLowerCase();
  const filas = tablaBody.getElementsByTagName("tr");

  for (let i = 0; i < filas.length; i++) {
    const celdas = filas[i].getElementsByTagName("td");
    let encontrado = false;

    // Recorremos todas las celdas de la fila
    for (let j = 0; j < celdas.length - 1; j++) {
      // -1 para ignorar la columna de "Acciones"
      const valor = celdas[j].textContent.toLowerCase();
      if (valor.indexOf(filtro) > -1) {
        encontrado = true;
        break;
      }
    }

    filas[i].style.display = encontrado ? "" : "none";
  }
});
// ===== EXPORTAR =====
document.getElementById("btnExportar")?.addEventListener("click", () => {
  Swal.fire({
    title: "Exportar tabla",
    text: "¿En qué formato quieres exportar?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Excel",
    cancelButtonText: "PDF",
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href =
        "../../controladores/admin/exportar_docente.php?tipo=excel";
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      window.location.href =
        "../../controladores/admin/exportar_docente.php?tipo=pdf";
    }
  });
});
// ===== CREAR DOCENTE =====
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
      Swal.fire({
        title: "<strong>Docente agregado correctamente</strong>",
        icon: "success",
        html: `
          <div style="text-align:left; margin-top:10px;">
            <p>Matrícula: <b>${data.matricula}</b></p>
            <p>Contraseña: <b>${data.password}</b></p>
          </div>
        `,
        confirmButtonText: "Aceptar",
      }).then(() => {
        cerrarModal();
        location.reload();
        const nuevaFila = crearFilaDocente({
          ...Object.fromEntries(datos),
          matricula: data.matricula,
          password: data.password,
        });
        tabla.prepend(nuevaFila);
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

// ===== Modal Editar =====
document.addEventListener("DOMContentLoaded", () => {
  const modalEditar = document.getElementById("modalEditar");
  const formEditar = document.getElementById("formEditar");
  const cancelEditar = document.getElementById("cancelEditar");
  const closeEditar = document.getElementById("closeEditar");

  let idDocenteSeleccionado = null;

  tabla.addEventListener("click", async (e) => {
    if (!e.target.closest(".btn-editar")) return;

    const fila = e.target.closest("tr");
    idDocenteSeleccionado = fila.dataset.id;

    // Obtener datos del servidor para asegurar matrícula y password actualizados
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
      if (data.status !== "success") throw new Error(data.message);

      const camposEditar = [
        "nombre",
        "apellido_paterno",
        "apellido_materno",
        "curp",
        "rfc",
        "fecha_nacimiento",
        "sexo",
        "telefono",
        "correo_personal",
        "matricula",
        "password",
        "nivel_estudios",
        "area_especialidad",
        "universidad_egreso",
        "cedula_profesional",
        "idiomas",
        "departamento",
        "puesto",
        "tipo_contrato",
        "fecha_ingreso",
        "num_empleado",
        "contacto_emergencia",
        "parentesco_emergencia",
        "telefono_emergencia",
      ];

      camposEditar.forEach((campo) => {
        const input = formEditar.querySelector(`#edit_${campo}`);
        if (input) input.value = data[campo] || "";
      });

      modalEditar.classList.add("active");
    } catch (err) {
      Swal.fire(
        "Error",
        err.message || "No se pudieron cargar los datos.",
        "error"
      );
    }
  });

  cancelEditar.addEventListener("click", () => {
    modalEditar.classList.remove("active");
    formEditar.reset();
  });

  if (closeEditar) {
    closeEditar.addEventListener("click", () => {
      modalEditar.classList.remove("active");
      formEditar.reset();
    });
  }

  formEditar.addEventListener("submit", async (e) => {
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
        const fila = tabla.querySelector(
          `tr[data-id='${idDocenteSeleccionado}']`
        );
        if (!fila) return;

        // Actualizar fila (excepto matrícula y contraseña no editables)
        const campos = [
          "id_docente",
          "nombre",
          "apellido_paterno",
          "apellido_materno",
          "curp",
          "rfc",
          "fecha_nacimiento",
          "sexo",
          "telefono",
          "correo_personal",
          "matricula",
          "nivel_estudios",
          "area_especialidad",
          "universidad_egreso",
          "cedula_profesional",
          "idiomas",
          "departamento",
          "puesto",
          "tipo_contrato",
          "fecha_ingreso",
          "num_empleado",
          "contacto_emergencia",
          "parentesco_emergencia",
          "telefono_emergencia",
        ];

        campos.forEach((campo, idx) => {
          if (fila.children[idx]) {
            if (campo === "matricula")
              fila.children[idx].textContent =
                data.matricula || fila.children[idx].textContent;
            else if (campo === "password")
              return; // password no se muestra en la tabla
            else
              fila.children[idx].textContent =
                formData.get(campo) || fila.children[idx].textContent;
          }
        });

        Swal.fire({
          icon: "success",
          title: "Docente actualizado",
          text: "Se actualizó correctamente",
          timer: 1500,

          showConfirmButton: false,
        }).then(() => {
          setTimeout(() => {
            location.reload(); // recarga la página
          }, 5); // espera medio segundo antes de recargar
        });

        modalEditar.classList.remove("active");
      } else {
        Swal.fire("Error", data.message || "Error al actualizar", "error");
      }
    } catch (err) {
      Swal.fire("Error de red", err.message || "No se pudo conectar", "error");
    }
  });
});

// ===== ELIMINAR DOCENTE =====
tabla.addEventListener("click", async (e) => {
  const btnEliminar = e.target.closest(".btn-eliminar");
  if (!btnEliminar) return;

  const fila = e.target.closest("tr");
  const id = fila.dataset.id || "";
  const nombre = fila.children[1]?.textContent || "";
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
            timer: 2000,
          }).then(() => {
            setTimeout(() => {
              location.reload(); // recarga la página después de mostrar la alerta
            }, 5);
          });
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
// ===== PAGINACIÓN =====
const rowsPerPage = 10; // filas por página
let currentPage = 1;

function mostrarPagina(page = 1) {
  const tablaBody = document.querySelector("#tablaBody");
  const filas = Array.from(tablaBody.querySelectorAll("tr"));
  const totalPages = Math.ceil(filas.length / rowsPerPage);

  // Mostrar/ocultar filas según página
  filas.forEach((fila, index) => {
    fila.style.display =
      index >= (page - 1) * rowsPerPage && index < page * rowsPerPage
        ? ""
        : "none";
  });

  // Generar botones de paginación
  const pagination = document.getElementById("pagination");
  pagination.innerHTML = "";

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.textContent = i;
    btn.className = "pagination-btn" + (i === page ? " active" : "");
    btn.addEventListener("click", () => {
      currentPage = i;
      mostrarPagina(i);
    });
    pagination.appendChild(btn);
  }
}

// Ejecutar al cargar
document.addEventListener("DOMContentLoaded", () => {
  mostrarPagina(currentPage);
});
