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

  const tablaCarreras = document.querySelector("#tablaCarreras tbody");

  // Campos editar
  const editNombreCarrera = document.getElementById("editNombreCarrera");
  const editDescripcion = document.getElementById("editDescripcion");
  const editDuracionAnios = document.getElementById("editDuracionAnios");

  let idSeleccionado = null;

  // URL del controlador
  const url = "../../controladores/admin/controller_carreras.php";

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

  // ===== AGREGAR CARRERA =====
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
          title: "Carrera agregada",
          text: "Se agregó correctamente la carrera.",
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

  // ===== EDITAR CARRERA =====
  tablaCarreras.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (!btn) return;

    const fila = btn.closest("tr");
    idSeleccionado = fila.dataset.id;

    // Rellenar modal con datos
    editNombreCarrera.value = fila.dataset.nombre;
    editDescripcion.value = fila.dataset.descripcion;
    editDuracionAnios.value = fila.dataset.duracion;

    abrirModal(modalEditar);
  });

  formEditar.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(formEditar);
    datos.append("accion", "editar");
    datos.append("id_carrera", idSeleccionado);

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Carrera actualizada",
          text: "Se actualizó correctamente la carrera.",
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

  // ===== ELIMINAR CARRERA =====
  tablaCarreras.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    const fila = btn.closest("tr");
    const id = fila.dataset.id;
    const nombre = fila.dataset.nombre;

    Swal.fire({
      title: `¿Eliminar "${nombre}"?`,
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
        datos.append("id_carrera", id);

        try {
          const resp = await fetch(url, { method: "POST", body: datos });
          const data = await resp.json();

          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Carrera eliminada",
              text: "Se eliminó correctamente la carrera.",
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
  });

  // ===== BUSCAR =====
  document
    .getElementById("buscarCarrera")
    .addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      const filas = document.querySelectorAll("#tablaCarreras tbody tr");
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
          "../../controladores/admin/exportar_carreras.php?tipo=excel";
      } else if (r.dismiss === Swal.DismissReason.cancel) {
        window.location.href =
          "../../controladores/admin/exportar_carreras.php?tipo=pdf";
      }
    });
  });
});
