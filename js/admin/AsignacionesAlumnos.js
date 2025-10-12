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

  const tablaAsignaciones = document.querySelector("#tablaAsignaciones tbody");

  // Campos editar
  const editAlumno = document.getElementById("editAlumno");
  const editCarrera = document.getElementById("editCarrera");
  const editGrado = document.getElementById("editGrado");
  const editCiclo = document.getElementById("editCiclo");
  const editGrupo = document.getElementById("editGrupo");

  let idSeleccionado = null;

  // URL del controlador
  const url = "../../controladores/admin/controlador_asignaciones_alumnos.php";

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

  // ===== AGREGAR ASIGNACIÓN =====
  formNuevo.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(formNuevo);
    datos.append("accion", "crear");

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Asignación agregada",
          text: data.message,
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

  // ===== EDITAR ASIGNACIÓN =====
  tablaAsignaciones.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (btn) {
      const fila = btn.closest("tr");
      idSeleccionado = fila.dataset.id;

      editAlumno.value = fila.dataset.alumno;
      editCarrera.value = fila.dataset.carrera;
      editGrado.value = fila.dataset.grado;
      editCiclo.value = fila.dataset.ciclo;
      editGrupo.value = fila.dataset.grupo;

      abrirModal(modalEditar);
    }
  });

  formEditar.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(formEditar);
    datos.append("accion", "editar");
    datos.append("id_asignacion", idSeleccionado);

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Asignación actualizada",
          text: data.message,
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

  // ===== ELIMINAR ASIGNACIÓN =====
  tablaAsignaciones.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (btn) {
      const fila = btn.closest("tr");
      const id = fila.dataset.id;
      const alumno = fila.dataset.alumno_nombre;

      Swal.fire({
        title: `¿Eliminar asignación de "${alumno}"?`,
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
          datos.append("id_asignacion", id);

          try {
            const resp = await fetch(url, { method: "POST", body: datos });
            const data = await resp.json();

            if (data.status === "success") {
              Swal.fire({
                icon: "success",
                title: "Asignación eliminada",
                text: data.message,
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
    .getElementById("buscarAsignacion")
    .addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      const filas = document.querySelectorAll("#tablaAsignaciones tbody tr");
      filas.forEach((fila) => {
        fila.style.display = fila.innerText.toLowerCase().includes(filtro)
          ? ""
          : "none";
      });
    });

  // ===== EXPORTAR =====
  document.getElementById("btnExportar").addEventListener("click", function () {
    Swal.fire({
      title: "Exportar tabla",
      text: "¿En qué formato quieres exportar?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Excel",
      cancelButtonText: "PDF",
      reverseButtons: true,
    }).then((r) => {
      if (r.isConfirmed) {
        window.location.href =
          "../../controladores/admin/exportar_asignaciones_alumnos.php?tipo=excel";
      } else if (r.dismiss === Swal.DismissReason.cancel) {
        window.location.href =
          "../../controladores/admin/exportar_asignaciones_alumnos.php?tipo=pdf";
      }
    });
  });
});
