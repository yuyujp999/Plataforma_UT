document.addEventListener("DOMContentLoaded", () => {
  // ===== Permisos inyectados desde PHP (fallback seguro) =====
  const PERMISOS = window.PERMISOS || {
    crear: true,
    editar: true,
    eliminar: false,
  };

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

  const tablaMaterias = document.querySelector("#tablaMaterias tbody");

  // Campo editar
  const editNombre = document.getElementById("editNombreMateria");

  let idSeleccionado = null;

  // URL del controlador (SECRETARÍAS)
  const url = "../../controladores/secretarias/controller_materias.php";

  // ===== Helpers =====
  const abrirModal = (m) => m && m.classList.add("active");
  const cerrarModal = (m, f) => {
    if (m) m.classList.remove("active");
    if (f && typeof f.reset === "function") f.reset();
  };

  const setSubmitting = (form, loading) => {
    if (!form) return;
    const btn = form.querySelector("button[type='submit']");
    if (btn) {
      if (loading) {
        btn.dataset.prevText ??= btn.textContent;
        btn.textContent = "Guardando...";
      } else if (btn.dataset.prevText) {
        btn.textContent = btn.dataset.prevText;
      }
      btn.disabled = loading;
    }
    form
      .querySelectorAll("input,select,textarea,button")
      .forEach((el) => (el.disabled = loading));
  };

  const successReload = (title, text) =>
    Swal.fire({
      icon: "success",
      title,
      text,
      timer: 1500,
      showConfirmButton: false,
    }).then(() => location.reload());

  const errorMsg = (text, title = "Error") =>
    Swal.fire({ icon: "error", title, text });

  const postJSON = async (fd) => {
    const resp = await fetch(url, { method: "POST", body: fd });
    const text = await resp.text();
    try {
      return JSON.parse(text);
    } catch {
      throw new Error(text || "Respuesta no válida del servidor");
    }
  };

  // ===== NUEVO =====
  if (PERMISOS.crear) {
    btnNuevo?.addEventListener("click", () => abrirModal(modalNuevo));
    btnCancelarNuevo?.addEventListener("click", () =>
      cerrarModal(modalNuevo, formNuevo)
    );
    btnCerrarNuevo?.addEventListener("click", () =>
      cerrarModal(modalNuevo, formNuevo)
    );

    formNuevo?.addEventListener("submit", async (e) => {
      e.preventDefault();
      const datos = new FormData(formNuevo);
      datos.append("accion", "agregar");

      try {
        setSubmitting(formNuevo, true);
        const data = await postJSON(datos);
        if (data.status === "success") {
          cerrarModal(modalNuevo, formNuevo);
          successReload(
            "Materia agregada",
            data.message || "Se agregó correctamente la materia."
          );
        } else {
          errorMsg(data.message || "No se pudo agregar.");
        }
      } catch (err) {
        errorMsg(err.message, "Error de red");
      } finally {
        setSubmitting(formNuevo, false);
      }
    });
  } else {
    // Si no tiene permiso para crear, asegura que no pueda abrir el modal
    btnNuevo?.addEventListener("click", () =>
      Swal.fire(
        "Acción no permitida",
        "No tienes permiso para agregar.",
        "warning"
      )
    );
  }

  // ===== EDITAR =====
  if (tablaMaterias) {
    tablaMaterias.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-editar");
      if (!btn) return;

      if (!PERMISOS.editar) {
        Swal.fire(
          "Acción no permitida",
          "No tienes permiso para editar.",
          "warning"
        );
        return;
      }

      const fila = btn.closest("tr");
      if (!fila) return;

      idSeleccionado = fila.dataset.id;
      if (editNombre) editNombre.value = fila.dataset.nombre || "";

      abrirModal(modalEditar);
    });
  }

  if (PERMISOS.editar) {
    btnCancelarEditar?.addEventListener("click", () =>
      cerrarModal(modalEditar, formEditar)
    );
    btnCerrarEditar?.addEventListener("click", () =>
      cerrarModal(modalEditar, formEditar)
    );

    formEditar?.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!idSeleccionado) {
        errorMsg("No se identificó la materia a editar.");
        return;
      }

      const datos = new FormData(formEditar);
      datos.append("accion", "editar");
      datos.append("id_materia", idSeleccionado);

      try {
        setSubmitting(formEditar, true);
        const data = await postJSON(datos);
        if (data.status === "success") {
          cerrarModal(modalEditar, formEditar);
          successReload(
            "Materia actualizada",
            data.message || "Se actualizó correctamente la materia."
          );
        } else {
          errorMsg(data.message || "No se pudo actualizar.");
        }
      } catch (err) {
        errorMsg(err.message, "Error de red");
      } finally {
        setSubmitting(formEditar, false);
      }
    });
  }

  // ===== ELIMINAR (bloqueado para secretarías) =====
  if (tablaMaterias) {
    tablaMaterias.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-eliminar");
      if (!btn) return;

      if (!PERMISOS.eliminar) {
        Swal.fire(
          "Acción no permitida",
          "No tienes permiso para eliminar.",
          "warning"
        );
        return;
      }

      // Si en algún momento das permiso a eliminar, este bloque funcionará:
      const fila = btn.closest("tr");
      const id = fila?.dataset.id;
      const nombre = fila?.dataset.nombre;

      Swal.fire({
        title: `¿Eliminar "${nombre}"?`,
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
      }).then(async (result) => {
        if (!result.isConfirmed) return;
        const datos = new FormData();
        datos.append("accion", "eliminar");
        datos.append("id_materia", id);

        try {
          const data = await postJSON(datos);
          if (data.status === "success") {
            successReload(
              "Materia eliminada",
              data.message || "Se eliminó correctamente la materia."
            );
          } else {
            errorMsg(data.message || "No se pudo eliminar.");
          }
        } catch (err) {
          errorMsg(err.message, "Error de red");
        }
      });
    });
  }

  // ===== BUSCAR =====
  document
    .getElementById("buscarMateria")
    ?.addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      document.querySelectorAll("#tablaMaterias tbody tr").forEach((fila) => {
        fila.style.display = fila.innerText.toLowerCase().includes(filtro)
          ? ""
          : "none";
      });
    });

  // ===== EXPORTAR (si tienes botón) =====
  document
    .getElementById("btnExportar")
    ?.addEventListener("click", function () {
      Swal.fire({
        title: "Descargar Tabla",
        text: "Formato de descarga en PDF",
        icon: "question",
        showCancelButton: true,
        cancelButtonText: "PDF",
        reverseButtons: true,
      }).then((r) => {
        if (r.isConfirmed) {
          window.location.href =
            "../../controladores/secretarias/exportar_materias.php?tipo=excel";
        } else if (r.dismiss === Swal.DismissReason.cancel) {
          window.location.href =
            "../../controladores/secretarias/exportar_materias.php?tipo=pdf";
        }
      });
    });
});
