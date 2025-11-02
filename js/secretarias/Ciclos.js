document.addEventListener("DOMContentLoaded", () => {
  // ===== Permisos inyectados desde PHP =====
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
  const tbody = document.querySelector("#tablaCiclos tbody");

  // --- Campos NUEVO ---
  const claveN = document.getElementById("clave");
  const fechaInicioN = document.getElementById("fecha_inicio");
  const fechaFinN = document.getElementById("fecha_fin");
  const activoN = document.getElementById("activo");

  // --- Campos EDITAR ---
  const idEdit = document.getElementById("editIdCiclo");
  const claveE = document.getElementById("editClave");
  const fechaInicioE = document.getElementById("editFechaInicio");
  const fechaFinE = document.getElementById("editFechaFin");
  const activoE = document.getElementById("editActivo");

  // ===== ENDPOINT (SECRETARÍAS) =====
  const url = "../../controladores/secretarias/controller_ciclos.php";

  let idSeleccionado = null;

  // ===== Helpers =====
  const abrirModal = (m) => m?.classList.add("active");
  const cerrarModal = (m, f) => {
    m?.classList.remove("active");
    f?.reset?.();
  };

  function syncMinFin(fechaInicioEl, fechaFinEl) {
    if (!fechaInicioEl || !fechaFinEl) return;
    fechaFinEl.min = fechaInicioEl.value || "";
    if (
      fechaFinEl.value &&
      fechaInicioEl.value &&
      fechaFinEl.value < fechaInicioEl.value
    ) {
      fechaFinEl.value = fechaInicioEl.value;
    }
  }

  async function enviar(accion, payload) {
    const fd = new FormData();
    fd.append("accion", accion);
    Object.entries(payload || {}).forEach(([k, v]) => fd.append(k, v));
    try {
      const resp = await fetch(url, { method: "POST", body: fd });
      const text = await resp.text(); // evita "Unexpected token <"
      try {
        return JSON.parse(text);
      } catch {
        // intenta parsear JSON
        throw new Error(text || "Respuesta no válida del servidor");
      }
    } catch (e) {
      Swal.fire("Error de red", e.message, "error");
      return { status: "error", message: e.message };
    }
  }

  // ===== Enlaces de min de fecha =====
  fechaInicioN?.addEventListener("change", () =>
    syncMinFin(fechaInicioN, fechaFinN)
  );
  fechaInicioE?.addEventListener("change", () =>
    syncMinFin(fechaInicioE, fechaFinE)
  );

  // ===== Crear =====
  if (PERMISOS.crear && btnNuevo) {
    btnNuevo.addEventListener("click", () => abrirModal(modalNuevo));
  }
  if (PERMISOS.crear && btnCancelarNuevo) {
    btnCancelarNuevo.addEventListener("click", () =>
      cerrarModal(modalNuevo, formNuevo)
    );
  }
  if (PERMISOS.crear && btnCerrarNuevo) {
    btnCerrarNuevo.addEventListener("click", () =>
      cerrarModal(modalNuevo, formNuevo)
    );
  }
  if (PERMISOS.crear && formNuevo) {
    formNuevo.addEventListener("submit", async (e) => {
      e.preventDefault();
      const clave = (claveN?.value || "").trim();
      const fecha_inicio = fechaInicioN?.value || "";
      const fecha_fin = fechaFinN?.value || "";
      const activo = activoN?.checked ? "1" : "0";

      if (!clave || !fecha_inicio || !fecha_fin) {
        Swal.fire(
          "Error",
          "Completa clave, fecha inicio y fecha fin.",
          "error"
        );
        return;
      }

      const r = await enviar("agregar", {
        clave,
        fecha_inicio,
        fecha_fin,
        activo,
      });
      if (r.status === "success") {
        cerrarModal(modalNuevo, formNuevo);
        Swal.fire({
          icon: "success",
          title: "Ciclo agregado",
          text: r.message || "El ciclo se guardó correctamente.",
          timer: 1400,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire("Error", r.message || "No se pudo agregar el ciclo", "error");
      }
    });
  }

  // ===== Editar (abrir) =====
  if (tbody) {
    tbody.addEventListener("click", (e) => {
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

      const tr = btn.closest("tr");
      idSeleccionado = tr?.dataset.id || null;

      const clave = tr?.dataset.clave || tr?.getAttribute("data-clave") || "";
      const fi =
        tr?.dataset.fechaInicio || tr?.getAttribute("data-fecha-inicio") || "";
      const ff =
        tr?.dataset.fechaFin || tr?.getAttribute("data-fecha-fin") || "";
      const act = tr?.dataset.activo || tr?.getAttribute("data-activo") || "0";

      if (idEdit) idEdit.value = idSeleccionado || "";
      if (claveE) claveE.value = clave;
      if (fechaInicioE) fechaInicioE.value = fi;
      if (fechaFinE) fechaFinE.value = ff;
      if (activoE) activoE.checked = act === "1";

      syncMinFin(fechaInicioE, fechaFinE);
      abrirModal(modalEditar);
    });
  }

  // Cerrar modal editar
  if (PERMISOS.editar && btnCancelarEditar) {
    btnCancelarEditar.addEventListener("click", () =>
      cerrarModal(modalEditar, formEditar)
    );
  }
  if (PERMISOS.editar && btnCerrarEditar) {
    btnCerrarEditar.addEventListener("click", () =>
      cerrarModal(modalEditar, formEditar)
    );
  }

  // ===== Editar (submit) =====
  if (PERMISOS.editar && formEditar) {
    formEditar.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (!idSeleccionado) {
        Swal.fire("Error", "No se seleccionó ningún ciclo.", "error");
        return;
      }

      const id_ciclo = idEdit?.value || idSeleccionado;
      const clave = (claveE?.value || "").trim();
      const fecha_inicio = fechaInicioE?.value || "";
      const fecha_fin = fechaFinE?.value || "";
      const activo = activoE?.checked ? "1" : "0";

      if (!id_ciclo || !clave || !fecha_inicio || !fecha_fin) {
        Swal.fire("Error", "Completa todos los campos obligatorios.", "error");
        return;
      }

      const r = await enviar("editar", {
        id_ciclo,
        clave,
        fecha_inicio,
        fecha_fin,
        activo,
      });
      if (r.status === "success") {
        cerrarModal(modalEditar, formEditar);
        Swal.fire({
          icon: "success",
          title: "Actualizado",
          text: r.message || "El ciclo se actualizó correctamente.",
          timer: 1400,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire(
          "Error",
          r.message || "No se pudo actualizar el ciclo",
          "error"
        );
      }
    });
  }

  // ===== Eliminar (solo si tiene permiso) =====
  if (tbody) {
    tbody.addEventListener("click", (e) => {
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

      const tr = btn.closest("tr");
      const id_ciclo = tr?.dataset.id;
      const claveTxt =
        tr?.querySelector("td:nth-child(2)")?.innerText || "este ciclo";

      Swal.fire({
        title: `¿Eliminar "${claveTxt}"?`,
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
      }).then(async (res) => {
        if (!res.isConfirmed) return;
        const r = await enviar("eliminar", { id_ciclo });
        if (r.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Eliminado",
            text: r.message || "El ciclo fue eliminado.",
            timer: 1400,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire("Error", r.message || "No se pudo eliminar", "error");
        }
      });
    });
  }

  // ===== Toggle ACTIVO con click en la badge (Secretaría y Admin, usan PERMISOS.editar) =====
  if (tbody) {
    tbody.addEventListener("click", async (e) => {
      const badge = e.target.closest("td:nth-child(5) .badge"); // columna "Activo"
      if (!badge) return;

      if (!PERMISOS.editar) {
        Swal.fire(
          "Acción no permitida",
          "No tienes permiso para actualizar el estado.",
          "warning"
        );
        return;
      }

      const tr = badge.closest("tr");
      if (!tr) return;

      const id_ciclo = tr.dataset.id;
      const activoActual = tr.dataset.activo === "1";
      const nuevo = activoActual ? "0" : "1";

      const r = await enviar("toggle_activo", { id_ciclo, activo: nuevo });
      if (r.status === "success") {
        // Feedback sin recargar
        tr.dataset.activo = nuevo;
        if (nuevo === "1") {
          badge.classList.remove("badge-no");
          badge.classList.add("badge-ok");
          badge.textContent = "Sí";
        } else {
          badge.classList.remove("badge-ok");
          badge.classList.add("badge-no");
          badge.textContent = "No";
        }
      } else {
        Swal.fire(
          "Error",
          r.message || "No se pudo actualizar el estado",
          "error"
        );
      }
    });
  }
});
