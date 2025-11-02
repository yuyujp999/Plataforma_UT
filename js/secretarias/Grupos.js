document.addEventListener("DOMContentLoaded", () => {
  // ===== Permisos (inyectados desde PHP) =====
  const PERMISOS = window.PERMISOS || {
    crear: false,
    editar: false,
    eliminar: false,
  };

  // ===== ELEMENTOS DOM =====
  const modalNuevo = document.getElementById("modalNuevo");
  const modalEditar = document.getElementById("modalEditar");

  const formNuevo = document.getElementById("formNuevo");
  const formEditar = document.getElementById("formEditar");

  const btnNuevo = document.getElementById("btnNuevo");
  const btnCancelarNuevo = document.getElementById("cancelModal");
  const btnCerrarNuevo = document.getElementById("closeModal");
  const btnCancelarEditar = document.getElementById("cancelModalEditar");
  const btnCerrarEditar = document.getElementById("closeModalEditar");

  const tablaGrupos = document.querySelector("#tablaGrados tbody");

  // --- Campos NUEVO ---
  const nuevoNombreGrado = document.getElementById("nombre_grado"); // select (id_nombre_semestre)
  const nuevoNombreGrupo = document.getElementById("nombre_grupo"); // input readonly

  // --- Campos EDITAR ---
  const editNombreGrado = document.getElementById("editNombreGrado"); // select (id_nombre_semestre)
  const editNombreGrupo = document.getElementById("editNombreGrupo"); // input readonly

  let idSeleccionado = null;

  // ===== ENDPOINT (SECRETARÍAS) =====
  const url = "../../controladores/secretarias/controller_grupos.php";

  // ===== Helpers =====
  const abrirModal = (m) => m?.classList.add("active");
  const cerrarModal = (m, f) => {
    m?.classList.remove("active");
    f?.reset?.();
  };

  const setFormLoading = (form, loading) => {
    if (!form) return;
    form
      .querySelectorAll("button, input[type=submit]")
      .forEach((b) => (b.disabled = loading));
  };

  // Parse seguro (evita "Unexpected token <" cuando el server devuelve HTML por error)
  const parseJSON = async (resp) => {
    const text = await resp.text();
    try {
      return JSON.parse(text);
    } catch {
      throw new Error(text || "Respuesta no válida del servidor");
    }
  };

  // === Backend: sugerir primer hueco libre (G1, G2, ...) para un semestre ===
  async function sugerirNombreServidor(idNombreSemestre) {
    if (!idNombreSemestre) return "";
    const fd = new FormData();
    fd.append("accion", "sugerir_nombre");
    fd.append("id_nombre_semestre", String(idNombreSemestre));
    const resp = await fetch(url, { method: "POST", body: fd });
    const data = await parseJSON(resp);
    if (data.status === "success" && data.sugerido) return data.sugerido;
    throw new Error(data.message || "No se pudo sugerir el nombre del grupo");
  }

  // ===== Autollenado de nombre (NUEVO) =====
  async function autollenarNuevo() {
    if (!PERMISOS.crear) return;
    const idSem = parseInt(nuevoNombreGrado?.value || "0", 10);
    if (!idSem || !nuevoNombreGrupo) {
      if (nuevoNombreGrupo) nuevoNombreGrupo.value = "";
      return;
    }
    try {
      nuevoNombreGrupo.value = await sugerirNombreServidor(idSem);
    } catch (e) {
      console.error(e);
      nuevoNombreGrupo.value = "";
    }
  }

  // ===== Autollenado de nombre (EDITAR) cuando cambie el semestre =====
  async function autollenarEditarSiCorresponde() {
    if (!PERMISOS.editar) return;
    const idSem = parseInt(editNombreGrado?.value || "0", 10);
    if (!idSem || !editNombreGrupo) return;
    try {
      editNombreGrupo.value = await sugerirNombreServidor(idSem);
    } catch (e) {
      console.error(e);
    }
  }

  // ===== Enlaces de cambio =====
  nuevoNombreGrado?.addEventListener("change", autollenarNuevo);
  editNombreGrado?.addEventListener("change", autollenarEditarSiCorresponde);

  // ===== Abrir/Cerrar modales =====
  if (PERMISOS.crear) {
    btnNuevo?.addEventListener("click", async () => {
      abrirModal(modalNuevo);
      await autollenarNuevo();
    });
    btnCancelarNuevo?.addEventListener("click", () =>
      cerrarModal(modalNuevo, formNuevo)
    );
    btnCerrarNuevo?.addEventListener("click", () =>
      cerrarModal(modalNuevo, formNuevo)
    );
  } else {
    // Si no tiene permiso de crear, asegúrate de que el botón (si existe) no haga nada
    btnNuevo?.addEventListener("click", () =>
      Swal.fire(
        "Acción no permitida",
        "No tienes permiso para crear grupos.",
        "warning"
      )
    );
  }

  if (PERMISOS.editar) {
    btnCancelarEditar?.addEventListener("click", () =>
      cerrarModal(modalEditar, formEditar)
    );
    btnCerrarEditar?.addEventListener("click", () =>
      cerrarModal(modalEditar, formEditar)
    );
  }

  // ===== Filtro de búsqueda =====
  document
    .getElementById("buscarGrado")
    ?.addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      document.querySelectorAll("#tablaGrados tbody tr").forEach((fila) => {
        fila.style.display = fila.innerText.toLowerCase().includes(filtro)
          ? ""
          : "none";
      });
    });

  // ===== Crear grupo =====
  if (PERMISOS.crear && formNuevo) {
    formNuevo.addEventListener("submit", async (e) => {
      e.preventDefault();

      const idSem = parseInt(nuevoNombreGrado?.value || "0", 10);
      if (!idSem) {
        Swal.fire({ icon: "error", title: "Falta seleccionar semestre" });
        return;
      }

      // Reforzar sugerencia desde el server si está vacío
      if (!nuevoNombreGrupo?.value) {
        try {
          nuevoNombreGrupo.value = await sugerirNombreServidor(idSem);
        } catch {}
      }

      const datos = new FormData(formNuevo);
      datos.append("accion", "crear");
      // Enviar el nombre calculado (readonly)
      if (nuevoNombreGrupo) datos.set("nombre_grupo", nuevoNombreGrupo.value);

      try {
        setFormLoading(formNuevo, true);
        const resp = await fetch(url, { method: "POST", body: datos });
        const data = await parseJSON(resp);

        if (data.status === "success" || data.status === "ok") {
          cerrarModal(modalNuevo, formNuevo);
          Swal.fire({
            icon: "success",
            title: "Grupo agregado",
            text: `Se creó el grupo ${
              data.grupo?.nombre_grupo || nuevoNombreGrupo?.value || ""
            }`,
            timer: 1500,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo crear",
          });
        }
      } catch (err) {
        Swal.fire({ icon: "error", title: "Error de red", text: err.message });
      } finally {
        setFormLoading(formNuevo, false);
      }
    });
  }

  // ===== Abrir modal de edición =====
  tablaGrupos?.addEventListener("click", async (e) => {
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
    idSeleccionado = fila?.dataset.id || null;

    // Setea valores actuales
    const idSem = fila?.dataset.idNombreSemestre || "";
    if (idSem && editNombreGrado) editNombreGrado.value = idSem;
    if (editNombreGrupo)
      editNombreGrupo.value = fila?.dataset.nombreGrupo || "";

    abrirModal(modalEditar);
  });

  // ===== Editar grupo =====
  if (PERMISOS.editar && formEditar) {
    formEditar.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (!idSeleccionado) {
        Swal.fire({ icon: "error", title: "No se seleccionó ningún grupo" });
        return;
      }

      const datos = new FormData(formEditar);
      datos.append("accion", "editar");
      datos.append("id_grupo", idSeleccionado);

      try {
        setFormLoading(formEditar, true);
        const resp = await fetch(url, { method: "POST", body: datos });
        const data = await parseJSON(resp);

        if (data.status === "success" || data.status === "ok") {
          cerrarModal(modalEditar, formEditar);
          Swal.fire({
            icon: "success",
            title: "Grupo actualizado",
            text: `Grupo ${data.grupo?.nombre_grupo || ""} actualizado`,
            timer: 1500,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo actualizar",
          });
        }
      } catch (err) {
        Swal.fire({ icon: "error", title: "Error de red", text: err.message });
      } finally {
        setFormLoading(formEditar, false);
      }
    });
  }

  // ===== Eliminar grupo =====
  tablaGrupos?.addEventListener("click", (e) => {
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

    const fila = btn.closest("tr");
    const id = fila?.dataset.id;
    const nombre = fila?.dataset.nombreGrupo || "(sin nombre)";

    Swal.fire({
      title: `¿Eliminar "${nombre}"?"`,
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
      datos.append("id_grupo", id);

      try {
        const resp = await fetch(url, { method: "POST", body: datos });
        const data = await parseJSON(resp);

        if (data.status === "success" || data.status === "ok") {
          Swal.fire({
            icon: "success",
            title: "Grupo eliminado",
            text: "Grupo eliminado correctamente",
            timer: 1500,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo eliminar",
          });
        }
      } catch (err) {
        Swal.fire({ icon: "error", title: "Error de red", text: err.message });
      }
    });
  });
});
