document.addEventListener("DOMContentLoaded", () => {
  // ==========================
  // 🔧 UTILIDADES
  // ==========================
  const qs = (sel, ctx = document) => ctx.querySelector(sel);
  const qsa = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const PERM = Object.assign(
    { crear: false, editar: false, eliminar: false, exportar: false },
    window.PERMISOS || {}
  );

  // ==========================
  // 🪟 MODALES
  // ==========================
  const modalNuevo = qs("#modalNuevo");
  const modalEditar = qs("#modalEditar");
  const formNuevo = qs("#formNuevo");
  const formEditar = qs("#formEditar");

  const btnNuevo = qs("#btnNuevo");
  const btnCancelarNuevo = qs("#cancelModal");
  const btnCerrarNuevo = qs("#closeModal");
  const btnCancelarEditar = qs("#cancelModalEditar");
  const btnCerrarEditar = qs("#closeModalEditar");

  const abrirModal = (m) => m?.classList.add("active");
  const cerrarModal = (m, f) => {
    if (m) m.classList.remove("active");
    f?.reset?.();
  };

  // Botón "Nueva aula" solo si hay permiso
  if (PERM.crear) {
    btnNuevo?.addEventListener("click", () => abrirModal(modalNuevo));
  }

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

  // ==========================
  // 📋 TABLA / ELEMENTOS
  // ==========================
  const tablaAulasBody = qs("#tablaAulas tbody");
  const buscarInput = qs("#buscarAula");

  // Campos del modal Editar
  const editNombreAula = qs("#editNombreAula");
  const editCapacidad = qs("#editCapacidad");

  // ==========================
  // 🌐 ENDPOINT (ADMIN)
  // ==========================
  const url = "../../controladores/admin/controller_aulas.php";

  let idSeleccionado = null;

  // Helpers UI
  const setFormSubmitting = (form, isLoading) => {
    if (!form) return;
    const btn = form.querySelector("button[type='submit']");
    if (btn) {
      btn.disabled = isLoading;
      btn.dataset.prevText ??= btn.textContent;
      btn.textContent = isLoading ? "Guardando..." : btn.dataset.prevText;
    }
    qsa("input,select,textarea,button", form).forEach((el) => {
      if (el !== btn) el.disabled = isLoading;
    });
  };

  const showSuccessReload = (title, text) =>
    Swal.fire({
      icon: "success",
      title,
      text,
      timer: 1500,
      showConfirmButton: false,
    }).then(() => location.reload());

  const showError = (text, title = "Error") =>
    Swal.fire({
      icon: "error",
      title,
      text,
    });

  // ==========================
  // ➕ AGREGAR (solo si hay permiso)
  // ==========================
  if (PERM.crear) {
    formNuevo?.addEventListener("submit", async (e) => {
      e.preventDefault();
      const datos = new FormData(formNuevo);
      datos.append("accion", "agregar");

      try {
        setFormSubmitting(formNuevo, true);
        const resp = await fetch(url, { method: "POST", body: datos });
        const data = await resp.json();

        if (data.status === "success") {
          cerrarModal(modalNuevo, formNuevo);
          showSuccessReload(
            "Aula agregada",
            "Se agregó correctamente el aula."
          );
        } else {
          showError(data.message || "No se pudo agregar.");
        }
      } catch (err) {
        showError(err.message, "Error de red");
      } finally {
        setFormSubmitting(formNuevo, false);
      }
    });
  }

  // ==========================
  // ✏️ EDITAR (abrir modal con datos) — solo si hay permiso
  // ==========================
  if (PERM.editar) {
    tablaAulasBody?.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-editar");
      if (!btn) return;

      const fila = btn.closest("tr");
      if (!fila) return;

      idSeleccionado = fila.dataset.id || null;
      if (editNombreAula) editNombreAula.value = fila.dataset.nombre ?? "";
      if (editCapacidad) editCapacidad.value = fila.dataset.capacidad ?? "";

      abrirModal(modalEditar);
    });

    formEditar?.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!idSeleccionado) {
        showError("No se identificó el aula a editar.");
        return;
      }

      const datos = new FormData(formEditar);
      datos.append("accion", "editar");
      datos.append("id_aula", idSeleccionado);

      try {
        setFormSubmitting(formEditar, true);
        const resp = await fetch(url, { method: "POST", body: datos });
        const data = await resp.json();

        if (data.status === "success") {
          cerrarModal(modalEditar, formEditar);
          showSuccessReload(
            "Aula actualizada",
            "Se actualizó correctamente el aula."
          );
        } else {
          showError(data.message || "No se pudo actualizar.");
        }
      } catch (err) {
        showError(err.message, "Error de red");
      } finally {
        setFormSubmitting(formEditar, false);
      }
    });
  }

  // ==========================
  // 🗑️ ELIMINAR — Admin (también verificado en backend)
  // ==========================
  tablaAulasBody?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    if (!PERM.eliminar) {
      // Si por error dejaron el botón sin permiso, evitamos acción aquí
      showError("No tienes permiso para eliminar.", "Permiso denegado");
      return;
    }

    const fila = btn.closest("tr");
    const id = fila?.dataset.id;
    const nombre = fila?.dataset.nombre || "el aula";
    if (!id) return;

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
      datos.append("id_aula", id);

      try {
        const resp = await fetch(url, { method: "POST", body: datos });
        const data = await resp.json();

        if (data.status === "success") {
          showSuccessReload(
            "Aula eliminada",
            "Se eliminó correctamente el aula."
          );
        } else {
          showError(data.message || "No se pudo eliminar.");
        }
      } catch (err) {
        showError(err.message, "Error de red");
      }
    });
  });

  // ==========================
  // 🔎 BUSCAR (filtro rápido)
  // ==========================
  buscarInput?.addEventListener("keyup", function () {
    const filtro = this.value.toLowerCase();
    qsa("#tablaAulas tbody tr").forEach((fila) => {
      fila.style.display = fila.innerText.toLowerCase().includes(filtro)
        ? ""
        : "none";
    });
  });

  // ==========================
  // 📄 EXPORTAR (solo si hay permiso)
  // ==========================
  if (PERM.exportar) {
    qs("#btnExportar")?.addEventListener("click", function () {
      Swal.fire({
        title: "Generando PDF...",
        text: "Por favor espera mientras preparamos la descarga.",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
      });

      setTimeout(() => {
        window.location.href =
          "../../controladores/admin/exportar_aulas.php?tipo=pdf";
        setTimeout(() => {
          Swal.close();
          Swal.fire({
            icon: "success",
            title: "Descarga iniciada",
            text: "Tu archivo PDF se está descargando.",
            timer: 1500,
            showConfirmButton: false,
          });
        }, 1500);
      }, 500);
    });
  }

  // ==========================
  // 📃 PAGINACIÓN
  // ==========================
  function initTablePagination({
    tableId,
    searchInputId,
    paginationId,
    rowsPerPage = 5,
  }) {
    const table = document.getElementById(tableId);
    const paginationE = document.getElementById(paginationId);
    const searchE = document.getElementById(searchInputId);
    if (!table || !paginationE) return;

    const tbody = table.querySelector("tbody");
    const allRows = Array.from(tbody.querySelectorAll("tr"));
    if (allRows.length === 0) {
      paginationE.innerHTML = "";
      return;
    }

    let currentPage = 1;

    const getFilteredRows = () => {
      const q = (searchE?.value || "").trim().toLowerCase();
      return q
        ? allRows.filter((tr) => tr.innerText.toLowerCase().includes(q))
        : allRows;
    };

    const goToPage = (p) => paginate(getFilteredRows(), p, rowsPerPage);

    const renderPagination = (totalPages, page) => {
      paginationE.innerHTML = "";

      const mkBtn = (num, label = null, disabled = false, active = false) => {
        const b = document.createElement("button");
        b.className = "pagination-btn";
        b.textContent = label ?? num;
        if (active) b.classList.add("active");
        b.disabled = disabled;
        b.addEventListener("click", () => goToPage(num));
        return b;
      };

      // «
      paginationE.appendChild(mkBtn(page - 1, "«", page === 1));

      const windowSize = 1;
      const addDots = () => {
        const s = document.createElement("span");
        s.textContent = "…";
        s.style.padding = "6px";
        s.style.color = "#999";
        paginationE.appendChild(s);
      };

      for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || Math.abs(i - page) <= windowSize) {
          paginationE.appendChild(mkBtn(i, null, false, i === page));
        } else if (
          (i === 2 && page > windowSize + 2) ||
          (i === totalPages - 1 && page < totalPages - windowSize - 1)
        ) {
          addDots();
        }
      }

      // »
      paginationE.appendChild(mkBtn(page + 1, "»", page === totalPages));
    };

    const paginate = (rows, page, perPage) => {
      const total = rows.length;
      const totalPages = Math.max(1, Math.ceil(total / perPage));
      page = Math.min(Math.max(1, page), totalPages);

      allRows.forEach((tr) => (tr.style.display = "none"));

      const start = (page - 1) * perPage;
      rows
        .slice(start, start + perPage)
        .forEach((tr) => (tr.style.display = ""));

      renderPagination(totalPages, page);
      currentPage = page;
    };

    searchE?.addEventListener("keyup", () =>
      paginate(getFilteredRows(), 1, rowsPerPage)
    );

    paginate(getFilteredRows(), 1, rowsPerPage);
  }

  // Inicializa paginación para AULAS
  initTablePagination({
    tableId: "tablaAulas",
    searchInputId: "buscarAula",
    paginationId: "paginationAulas",
    rowsPerPage: 5,
  });
});
