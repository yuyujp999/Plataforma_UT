document.addEventListener("DOMContentLoaded", () => {
  // === ELEMENTOS DOM ===
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

  let idSeleccionado = null;
  const url = "../../controladores/admin/ciclos_controller.php";

  // ===== MODALES =====
  const abrirModal = (m) => m?.classList.add("active");
  const cerrarModal = (m, f) => {
    m?.classList.remove("active");
    if (f) f.reset();
  };

  btnNuevo?.addEventListener("click", () => abrirModal(modalNuevo));
  btnCancelarNuevo?.addEventListener("click", () =>
    cerrarModal(modalNuevo, formNuevo)
  );
  btnCerrarNuevo?.addEventListener("click", () =>
    cerrarModal(modalNuevo, formNuevo)
  );
  btnCancelarEditar?.addEventListener("click", () =>
    cerrarModal(modalEditar, formEditar)
  );
  btnCerrarEditar?.addEventListener("click", () =>
    cerrarModal(modalEditar, formEditar)
  );

  // ===== VALIDACIONES RÁPIDAS UI =====
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
  fechaInicioN?.addEventListener("change", () =>
    syncMinFin(fechaInicioN, fechaFinN)
  );
  fechaInicioE?.addEventListener("change", () =>
    syncMinFin(fechaInicioE, fechaFinE)
  );

  // ===== Fetch helper =====
  async function enviar(accion, payload) {
    const fd = new FormData();
    fd.append("accion", accion);
    Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
    try {
      const resp = await fetch(url, { method: "POST", body: fd });
      return await resp.json();
    } catch (e) {
      Swal.fire("Error de red", e.message, "error");
      return { status: "error", message: e.message };
    }
  }

  // ===== SUBMIT NUEVO =====
  formNuevo?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const clave = (claveN?.value || "").trim();
    const fecha_inicio = fechaInicioN?.value || "";
    const fecha_fin = fechaFinN?.value || "";
    const activo = activoN?.checked ? "1" : "0";

    if (!clave || !fecha_inicio || !fecha_fin) {
      Swal.fire("Error", "Completa clave, fecha inicio y fecha fin.", "error");
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

  // ===== ABRIR EDITAR =====
  tbody?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (!btn) return;

    const tr = btn.closest("tr");
    idSeleccionado = tr?.dataset.id || null;

    const clave = tr?.dataset.clave || tr?.getAttribute("data-clave") || "";
    const fi =
      tr?.dataset.fechaInicio || tr?.getAttribute("data-fecha-inicio") || "";
    const ff = tr?.dataset.fechaFin || tr?.getAttribute("data-fecha-fin") || "";
    const act = tr?.dataset.activo || tr?.getAttribute("data-activo") || "0";

    if (idEdit) idEdit.value = idSeleccionado || "";
    if (claveE) claveE.value = clave;
    if (fechaInicioE) fechaInicioE.value = fi;
    if (fechaFinE) fechaFinE.value = ff;
    if (activoE) activoE.checked = act === "1";

    // Sincroniza min de fecha fin
    syncMinFin(fechaInicioE, fechaFinE);

    abrirModal(modalEditar);
  });

  // ===== SUBMIT EDITAR =====
  formEditar?.addEventListener("submit", async (e) => {
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

  // ===== ELIMINAR =====
  tbody?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

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

  // ===== (Opcional) Toggle ACTIVO haciendo click en la insignia =====
  tbody?.addEventListener("click", async (e) => {
    const badge = e.target.closest("td:nth-child(5) .badge"); // Columna Activo
    if (!badge) return;

    const tr = badge.closest("tr");
    if (!tr) return;

    const id_ciclo = tr.dataset.id;
    const activoActual = tr.dataset.activo === "1";
    const nuevo = activoActual ? "0" : "1";

    const r = await enviar("toggle_activo", { id_ciclo, activo: nuevo });
    if (r.status === "success") {
      // Feedback rápido sin recargar (actualiza badge y data-*)
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
});
