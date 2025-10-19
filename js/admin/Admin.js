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

  const tablaAdmins = document.querySelector("#tablaAdmins tbody");

  // Campos editar
  const editNombre = document.getElementById("editNombre");
  const editApellidoPaterno = document.getElementById("editApellidoPaterno");
  const editApellidoMaterno = document.getElementById("editApellidoMaterno");
  const editCorreo = document.getElementById("editCorreo");
  const editPassword = document.getElementById("editPassword");

  let idSeleccionado = null;
  const url = "../../controladores/admin/controller_admin.php";

  // ===== UTILIDADES =====
  const abrirModal = (m) => {
    m.classList.add("active");
    m.classList.remove("fade-out");
  };

  const cerrarModal = (m, f) => {
    // animación suave de salida
    m.classList.add("fade-out");
    setTimeout(() => {
      m.classList.remove("active", "fade-out");
      if (f) f.reset();
    }, 250); // duración del fade-out
  };

  const showSuccess = (title, text) => {
    // Espera un poquito para que la transición se vea suave
    setTimeout(() => {
      Swal.fire({
        icon: "success",
        title,
        text,
        timer: 1600,
        showConfirmButton: false,
      }).then(() => location.reload());
    }, 300);
  };

  const showError = (text) =>
    Swal.fire({ icon: "error", title: "Error", text });

  const showNetworkError = (err) =>
    Swal.fire({ icon: "error", title: "Error de red", text: err.message });

  // Cerrar modal al hacer click fuera
  [modalNuevo, modalEditar].forEach((modal) => {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        const form = modal === modalNuevo ? formNuevo : formEditar;
        cerrarModal(modal, form);
      }
    });
  });

  // Botones abrir/cerrar modal
  btnNuevo.addEventListener("click", () => abrirModal(modalNuevo));
  btnCancelarNuevo.addEventListener("click", () =>
    cerrarModal(modalNuevo, formNuevo)
  );
  btnCerrarNuevo.addEventListener("click", () =>
    cerrarModal(modalNuevo, formNuevo)
  );
  btnCancelarEditar.addEventListener("click", () =>
    cerrarModal(modalEditar, formEditar)
  );
  btnCerrarEditar.addEventListener("click", () =>
    cerrarModal(modalEditar, formEditar)
  );

  // ===== AGREGAR ADMIN =====
  formNuevo.addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = formNuevo.querySelector("[type='submit']");
    btn && (btn.disabled = true);

    const datos = new FormData(formNuevo);
    datos.append("accion", "agregar");

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        cerrarModal(modalNuevo, formNuevo);
        showSuccess(
          "Administrador agregado",
          "Se agregó correctamente el administrador."
        );
      } else {
        showError(data.message || "No se pudo agregar el administrador.");
      }
    } catch (err) {
      showNetworkError(err);
    } finally {
      btn && (btn.disabled = false);
    }
  });

  // ===== EDITAR ADMIN =====
  tablaAdmins.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (!btn) return;

    const fila = btn.closest("tr");
    idSeleccionado = fila.dataset.id;

    editNombre.value = fila.dataset.nombre || "";
    editApellidoPaterno.value = fila.dataset.apellidoP || "";
    editApellidoMaterno.value = fila.dataset.apellidoM || "";
    editCorreo.value = fila.dataset.correo || "";
    if (editPassword) editPassword.value = "";

    abrirModal(modalEditar);
  });

  formEditar.addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = formEditar.querySelector("[type='submit']");
    btn && (btn.disabled = true);

    const datos = new FormData(formEditar);
    datos.append("accion", "editar");
    datos.append("id_admin", idSeleccionado);

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        cerrarModal(modalEditar, formEditar);
        showSuccess(
          "Administrador actualizado",
          "Se actualizó correctamente el administrador."
        );
      } else {
        showError(data.message || "No se pudo actualizar el administrador.");
      }
    } catch (err) {
      showNetworkError(err);
    } finally {
      btn && (btn.disabled = false);
    }
  });

  // ===== ELIMINAR ADMIN =====
  tablaAdmins.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    const fila = btn.closest("tr");
    const id = fila.dataset.id;
    const nombre = fila.dataset.nombre;

    Swal.fire({
      title: `¿Eliminar a "${nombre}"?`,
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
        datos.append("id_admin", id);

        try {
          const resp = await fetch(url, { method: "POST", body: datos });
          const data = await resp.json();

          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Administrador eliminado",
              text: "Se eliminó correctamente el administrador.",
              timer: 1500,
              showConfirmButton: false,
            }).then(() => location.reload());
          } else {
            showError(data.message || "No se pudo eliminar el administrador.");
          }
        } catch (err) {
          showNetworkError(err);
        }
      }
    });
  });

  // ===== EXPORTAR =====
  document.getElementById("btnExportar").addEventListener("click", function () {
    Swal.fire({
      title: "Generando PDF...",
      text: "Por favor espera mientras preparamos la descarga.",
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading(); // 🔹 muestra spinner de carga
      },
    });

    // Simula pequeño retraso (opcional, mejora UX)
    setTimeout(() => {
      // 🔹 Redirige al controlador de descarga
      window.location.href =
        "../../controladores/admin/exportar_admin.php?tipo=pdf";

      // 🔹 Cierra la alerta 1.5 segundos después
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
});
