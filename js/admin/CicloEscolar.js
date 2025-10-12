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

  const tablaCiclos = document.querySelector("#tablaCiclos tbody");

  // Campos editar
  const editNombre = document.getElementById("editNombreCiclo");
  const editFechaInicio = document.getElementById("editFechaInicio");
  const editFechaFin = document.getElementById("editFechaFin");

  let idSeleccionado = null;

  // URL del controlador
  const url = "../../controladores/admin/controller_ciclo_escolar.php";

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

  // ===== AGREGAR CICLO =====
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
          title: "Ciclo agregado",
          text: "Se agregó correctamente el ciclo escolar.",
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

  // ===== EDITAR CICLO =====
  tablaCiclos.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (btn) {
      const fila = btn.closest("tr");
      idSeleccionado = fila.dataset.id;

      editNombre.value = fila.dataset.nombre;
      editFechaInicio.value = fila.dataset.fecha_inicio || "";
      editFechaFin.value = fila.dataset.fecha_fin || "";

      abrirModal(modalEditar);
    }
  });

  formEditar.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(formEditar);
    datos.append("accion", "editar");
    datos.append("id_ciclo", idSeleccionado);

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Ciclo actualizado",
          text: "Se actualizó correctamente el ciclo escolar.",
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

  // ===== ELIMINAR CICLO =====
  tablaCiclos.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (btn) {
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
          datos.append("id_ciclo", id);

          try {
            const resp = await fetch(url, { method: "POST", body: datos });
            const data = await resp.json();

            if (data.status === "success") {
              Swal.fire({
                icon: "success",
                title: "Ciclo eliminado",
                text: "Se eliminó correctamente el ciclo escolar.",
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

  // ===== ACTIVAR/DESACTIVAR =====
  tablaCiclos.addEventListener("click", async (e) => {
    const toggleBtn = e.target.closest(".btn-toggle");
    if (!toggleBtn) return;

    const id = toggleBtn.dataset.id;
    let activo = toggleBtn.dataset.activo === "1" ? 1 : 0;

    try {
      const resp = await fetch(url, {
        method: "POST",
        body: new URLSearchParams({
          accion: "toggle",
          id_ciclo: id,
          activo: activo,
        }),
      });
      const data = await resp.json();

      if (data.status === "success") {
        // actualizar dataset y clases
        toggleBtn.dataset.activo = data.nuevo ? "1" : "0";
        toggleBtn.textContent = data.nuevo ? "Desactivar" : "Activar";
        toggleBtn.classList.toggle("activo", data.nuevo);
        toggleBtn.classList.toggle("inactivo", !data.nuevo);
      } else {
        Swal.fire({ icon: "error", title: "Error", text: data.message });
      }
    } catch (err) {
      Swal.fire({ icon: "error", title: "Error de red", text: err.message });
    }
  });

  // ===== BUSCAR =====
  document.getElementById("buscarCiclo").addEventListener("keyup", function () {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll("#tablaCiclos tbody tr");
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
          "../../controladores/admin/exportar_ciclo_escolar.php?tipo=excel";
      } else if (r.dismiss === Swal.DismissReason.cancel) {
        window.location.href =
          "../../controladores/admin/exportar_ciclo_escolar.php?tipo=pdf";
      }
    });
  });
});
