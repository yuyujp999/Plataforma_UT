document.addEventListener("DOMContentLoaded", () => {
  // === MODALES ===
  const modalNuevo = document.getElementById("modalNuevo");
  const modalEditar = document.getElementById("modalEditar");

  const formNuevo = document.getElementById("formNuevo");
  const formEditar = document.getElementById("formEditar");

  const btnNuevo = document.getElementById("btnNuevo");
  const btnCancelarNuevo = document.getElementById("cancelModal");
  const btnCerrarNuevo = document.getElementById("closeModal");
  const btnCancelarEditar = document.getElementById("cancelModalEditar");
  const btnCerrarEditar = document.getElementById("closeModalEditar");

  const tablaCalificaciones = document.querySelector(
    "#tablaCalificaciones tbody"
  );

  // Campos editar
  const editAlumno = document.getElementById("editAlumno");
  const editAsignacion = document.getElementById("editAsignacion");
  const editCalificacion = document.getElementById("editCalificacion");
  const editObservaciones = document.getElementById("editObservaciones");

  let idSeleccionado = null;

  // URL del controlador
  const url = "../../controladores/admin/controller_calificaciones.php";

  // ===== FUNCIONES MODAL =====
  const abrirModal = (m) => m.classList.add("active");
  const cerrarModal = (m, f) => {
    m.classList.remove("active");
    f.reset();
  };

  // Abrir modal NUEVO
  btnNuevo.addEventListener("click", () => abrirModal(modalNuevo));
  btnCancelarNuevo.addEventListener("click", () =>
    cerrarModal(modalNuevo, formNuevo)
  );
  btnCerrarNuevo.addEventListener("click", () =>
    cerrarModal(modalNuevo, formNuevo)
  );

  // Abrir modal EDITAR
  btnCancelarEditar.addEventListener("click", () =>
    cerrarModal(modalEditar, formEditar)
  );
  btnCerrarEditar.addEventListener("click", () =>
    cerrarModal(modalEditar, formEditar)
  );

  // ===== AGREGAR CALIFICACIÓN =====
  formNuevo.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(formNuevo);
    datos.append("accion", "agregar");

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Calificación agregada",
          text: "Se agregó correctamente la calificación.",
          timer: 1500,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire({ icon: "error", title: "Error", text: data.message });
      }
    } catch (err) {
      Swal.fire({ icon: "error", title: "Error de red", text: err.message });
    }
  });

  // ===== EDITAR CALIFICACIÓN =====
  tablaCalificaciones.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (btn) {
      const fila = btn.closest("tr");
      idSeleccionado = fila.dataset.id;

      editAlumno.value = fila.dataset.alumno;
      editAsignacion.value = fila.dataset.asignacion;
      editCalificacion.value = fila.dataset.calificacion;
      editObservaciones.value = fila.dataset.observaciones || "";

      abrirModal(modalEditar);
    }
  });

  formEditar.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(formEditar);
    datos.append("accion", "editar");
    datos.append("id_calificacion", idSeleccionado);

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Calificación actualizada",
          text: "Se actualizó correctamente la calificación.",
          timer: 1500,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire({ icon: "error", title: "Error", text: data.message });
      }
    } catch (err) {
      Swal.fire({ icon: "error", title: "Error de red", text: err.message });
    }
  });

  // ===== ELIMINAR CALIFICACIÓN =====
  tablaCalificaciones.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (btn) {
      const fila = btn.closest("tr");
      const id = fila.dataset.id;
      const alumno = fila.querySelector("td:nth-child(2)").textContent;

      Swal.fire({
        title: `¿Eliminar calificación de "${alumno}"?`,
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
      }).then(async (result) => {
        if (result.isConfirmed) {
          const datos = new FormData();
          datos.append("accion", "eliminar");
          datos.append("id_calificacion", id);

          try {
            const resp = await fetch(url, { method: "POST", body: datos });
            const data = await resp.json();

            if (data.status === "success") {
              Swal.fire({
                icon: "success",
                title: "Calificación eliminada",
                text: "Se eliminó correctamente la calificación.",
                timer: 1500,
                showConfirmButton: false,
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: "error", title: "Error", text: data.message });
            }
          } catch (err) {
            Swal.fire({
              icon: "error",
              title: "Error de red",
              text: err.message,
            });
          }
        }
      });
    }
  });

  // ===== BUSCAR =====
  document
    .getElementById("buscarCalificacion")
    .addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      const filas = document.querySelectorAll("#tablaCalificaciones tbody tr");
      filas.forEach((fila) => {
        fila.style.display = fila.innerText.toLowerCase().includes(filtro)
          ? ""
          : "none";
      });
    });
});

// ===== EXPORTAR CALIFICACIONES =====
document.getElementById("btnExportar").addEventListener("click", function () {
  Swal.fire({
    title: "Exportar calificaciones",
    text: "¿En qué formato quieres exportar?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Excel",
    cancelButtonText: "PDF",
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      // Exportar a Excel
      window.location.href =
        "../../controladores/admin/exportar_calificaciones.php?tipo=excel";
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      // Exportar a PDF
      window.location.href =
        "../../controladores/admin/exportar_calificaciones.php?tipo=pdf";
    }
  });
});
