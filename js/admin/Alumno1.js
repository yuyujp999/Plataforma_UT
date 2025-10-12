// ===== Modal NUEVO =====
const modal = document.getElementById("modalNuevoAlumno");
const btnNuevo = document.getElementById("btnNuevoAlumno");
const cancel = document.getElementById("cancelModal");
const closeX = document.getElementById("closeModal");
const form = document.getElementById("formNuevo");
const tabla = document.querySelector("#tablaAlumnos tbody");
const inputBuscar = document.getElementById("buscarAlumno");
const tablaBody = document.getElementById("tablaBody");

function cerrarModal() {
  modal.classList.remove("active");
  form.reset();
}

btnNuevo?.addEventListener("click", () => modal.classList.add("active"));
cancel?.addEventListener("click", cerrarModal);
closeX?.addEventListener("click", cerrarModal);

// ===== FUNCIONES =====
function crearFilaAlumno(alumno) {
  const fila = document.createElement("tr");
  fila.dataset.id = alumno.id_alumno || "---";
  fila.innerHTML = `
    <td>${alumno.id_alumno || "---"}</td>
    <td>${alumno.nombre || "---"}</td>
    <td>${alumno.apellido_paterno || "---"}</td>
    <td>${alumno.apellido_materno || "---"}</td>
    <td>${alumno.matricula || "---"}</td>
    <td>${alumno.curp || "---"}</td>
    <td>${alumno.fecha_nacimiento || "---"}</td>
    <td>${alumno.sexo || "---"}</td>
    <td>${alumno.telefono || "---"}</td>
    <td>${alumno.correo_personal || "---"}</td>
    <td>${alumno.carrera || "---"}</td>
    <td>${alumno.semestre || "---"}</td>
    <td>${alumno.grupo || "---"}</td>
    <td>${alumno.direccion || "---"}</td>
    <td>
      <button class="btn btn-outline btn-sm btn-editar"><i class="fas fa-edit"></i></button>
      <button class="btn btn-outline btn-sm btn-eliminar"><i class="fas fa-trash"></i></button>
    </td>
  `;
  return fila;
}

// ===== BUSCAR =====
inputBuscar?.addEventListener("keyup", function () {
  const filtro = inputBuscar.value.toLowerCase();
  const filas = tablaBody.getElementsByTagName("tr");
  for (let i = 0; i < filas.length; i++) {
    const celdas = filas[i].getElementsByTagName("td");
    let encontrado = false;
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
        "../../controladores/admin/exportar_alumno.php?tipo=excel";
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      window.location.href =
        "../../controladores/admin/exportar_alumno.php?tipo=pdf";
    }
  });
});

// ===== CREAR ALUMNO =====
form?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const datos = new FormData(form);

  // Cambiar correo → correo_personal
  if (datos.has("correo")) {
    datos.set("correo_personal", datos.get("correo"));
    datos.delete("correo");
  }

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
      // Mostrar matrícula y contraseña
      Swal.fire({
        title: "Alumno agregado correctamente",
        html: `
          <p><strong>Matrícula:</strong> ${data.matricula}</p>
          <p><strong>Contraseña:</strong> ${data.password}</p>
          <p style="color: gray; font-size: 0.9em;">Guarda estos datos antes de continuar.</p>
        `,
        icon: "success",
        confirmButtonText: "Aceptar",
      }).then(() => {
        // Cerrar modal y recargar página
        cerrarModal();
        location.reload(); // recarga la página
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

// ===== Modal Editar =====
document.addEventListener("DOMContentLoaded", () => {
  const modalEditar = document.getElementById("modalEditar");
  const formEditar = document.getElementById("formEditar");
  const cancelEditar = document.getElementById("cancelEditar");
  const closeEditar = document.getElementById("closeEditar");

  const tabla = document.querySelector("table");
  let idAlumnoSeleccionado = null;

  tabla?.addEventListener("click", async (e) => {
    if (!e.target.closest(".btn-editar")) return;
    const fila = e.target.closest("tr");
    idAlumnoSeleccionado = fila.dataset.id;

    const formData = new FormData();
    formData.append("action", "get");
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
      if (data.status !== "success") throw new Error(data.message);

      const camposEditar = [
        "nombre",
        "apellido_paterno",
        "apellido_materno",
        "matricula",
        "curp",
        "fecha_nacimiento",
        "sexo",
        "telefono",
        "correo_personal",
        "carrera",
        "semestre",
        "grupo",
        "direccion",
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
      Swal.fire("Error", err.message, "error");
    }
  });

  cancelEditar?.addEventListener("click", () => {
    modalEditar.classList.remove("active");
    formEditar.reset();
  });
  closeEditar?.addEventListener("click", () => {
    modalEditar.classList.remove("active");
    formEditar.reset();
  });

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
        Swal.fire({
          icon: "success",
          title: "Alumno actualizado",
          timer: 1500,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire("Error", data.message || "Error al actualizar", "error");
      }
    } catch (err) {
      Swal.fire("Error de red", err.message, "error");
    }
  });
});

// ===== ELIMINAR ALUMNO =====
tabla?.addEventListener("click", async (e) => {
  const btnEliminar = e.target.closest(".btn-eliminar");
  if (!btnEliminar) return;

  const fila = e.target.closest("tr");
  const id = fila.dataset.id || "";
  const nombre = fila.children[1]?.textContent || "";

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
        formData.append("id_alumno", id);

        const resp = await fetch(
          "../../controladores/admin/controller_alumno.php",
          { method: "POST", body: formData }
        );
        const data = await resp.json();

        if (data.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Eliminado",
            text: data.message,
            timer: 1500,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire("Error", data.message, "error");
        }
      } catch (err) {
        Swal.fire("Error de red", err.message, "error");
      }
    }
  });
});

// ===== PAGINACIÓN =====
const rowsPerPage = 10;
let currentPage = 1;
function mostrarPagina(page = 1) {
  const filas = Array.from(tablaBody.querySelectorAll("tr"));
  const totalPages = Math.ceil(filas.length / rowsPerPage);
  filas.forEach((fila, index) => {
    fila.style.display =
      index >= (page - 1) * rowsPerPage && index < page * rowsPerPage
        ? ""
        : "none";
  });
  const pagination = document.getElementById("paginationAlumno");
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
document.addEventListener("DOMContentLoaded", () => mostrarPagina(currentPage));
