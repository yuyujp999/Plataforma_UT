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

  const tablaAsignaciones = document.querySelector("#tablaAsignaciones tbody");
  const btnExportar = document.getElementById("btnExportar");
  const buscarInput = document.getElementById("buscarAsignacion");

  // --- Campos NUEVO (según el nuevo HTML) ---
  const materiaSelectN = document.getElementById("id_materia");
  const grupoSelectN = document.getElementById("id_nombre_grupo_int");
  const claveInputN = document.getElementById("clave_generada");
  const idNomMateriaNuevo = document.getElementById("id_nombre_materia_nuevo");

  // --- Campos EDITAR ---
  const materiaSelectE = document.getElementById("editMateria");
  const grupoSelectE = document.getElementById("editGrupo");
  const claveInputE = document.getElementById("editClave");
  const idNomMateriaEdit = document.getElementById("id_nombre_materia_editar");

  let idSeleccionado = null;
  const url = "../../controladores/admin/controller_asignar_materia.php";

  // ===== MODALES =====
  const abrirModal = (m) => m.classList.add("active");
  const cerrarModal = (m, f) => {
    m.classList.remove("active");
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

  // ===== UTILIDADES =====
  const normalizeTxt = (t) =>
    t
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^A-Za-z0-9\- ]/g, "")
      .toUpperCase();

  // Clave = 4 letras materia + '-' + nombreGrupo (texto de catálogo)
  function buildClave(nombreMateria, nombreGrupoTxt) {
    const base = normalizeTxt(nombreMateria).replace(/\s+/g, "").slice(0, 4);
    const grp = normalizeTxt(nombreGrupoTxt).replace(/\s+/g, "");
    return base && grp ? `${base}-${grp}` : "";
  }
  const selectedText = (selectEl) =>
    selectEl?.options[selectEl.selectedIndex]?.text || "";

  // ===== GENERAR CLAVE (NUEVO) =====
  function actualizarClaveNueva() {
    const materiaTxt = selectedText(materiaSelectN);
    const grupoTxt = selectedText(grupoSelectN);
    claveInputN.value = buildClave(materiaTxt, grupoTxt);
    if (idNomMateriaNuevo) idNomMateriaNuevo.value = "";
  }
  materiaSelectN?.addEventListener("change", actualizarClaveNueva);
  grupoSelectN?.addEventListener("change", actualizarClaveNueva);

  // ===== GENERAR CLAVE (EDITAR) =====
  function actualizarClaveEditar() {
    const materiaTxt = selectedText(materiaSelectE);
    const grupoTxt = selectedText(grupoSelectE);
    claveInputE.value = buildClave(materiaTxt, grupoTxt);
    if (idNomMateriaEdit) idNomMateriaEdit.value = "";
  }
  materiaSelectE?.addEventListener("change", actualizarClaveEditar);
  grupoSelectE?.addEventListener("change", actualizarClaveEditar);

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

    const id_materia = materiaSelectN?.value || "";
    const id_nombre_grupo_int = grupoSelectN?.value || "";
    const clave_generada = (claveInputN?.value || "").trim();

    if (!id_materia || !id_nombre_grupo_int) {
      Swal.fire("Error", "Selecciona una materia y un grupo.", "error");
      return;
    }

    const r = await enviar("agregar", {
      id_materia,
      id_nombre_grupo_int,
      clave_generada,
    });

    if (r.status === "success") {
      // Cerrar modal + limpiar antes del alert
      cerrarModal(modalNuevo, formNuevo);
      Swal.fire({
        icon: "success",
        title: "Asignación agregada",
        text: r.message,
        timer: 1400,
        showConfirmButton: false,
      }).then(() => location.reload());
    } else {
      Swal.fire("Error", r.message || "No se pudo agregar", "error");
    }
  });

  // ===== ABRIR EDITAR =====
  tablaAsignaciones?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (!btn) return;

    const tr = btn.closest("tr");
    idSeleccionado = tr.dataset.id || null;

    const idMateria =
      tr.dataset.idMateria || tr.getAttribute("data-id-materia") || "";
    const idNomGrupo =
      tr.dataset.idNombreGrupo || tr.getAttribute("data-id-nombre-grupo") || "";
    const clave = tr.dataset.clave || tr.getAttribute("data-clave") || "";

    if (materiaSelectE) materiaSelectE.value = idMateria;
    if (grupoSelectE) grupoSelectE.value = idNomGrupo;
    if (claveInputE)
      claveInputE.value =
        clave ||
        buildClave(selectedText(materiaSelectE), selectedText(grupoSelectE));

    abrirModal(modalEditar);
  });

  // ===== SUBMIT EDITAR =====
  formEditar?.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (!idSeleccionado) {
      Swal.fire("Error", "No se seleccionó ninguna asignación.", "error");
      return;
    }

    const id_materia = materiaSelectE?.value || "";
    const id_nombre_grupo_int = grupoSelectE?.value || "";
    const clave_generada = (claveInputE?.value || "").trim();

    if (!id_materia || !id_nombre_grupo_int) {
      Swal.fire("Error", "Selecciona materia y grupo.", "error");
      return;
    }

    const r = await enviar("editar", {
      id_asignacion: idSeleccionado,
      id_materia,
      id_nombre_grupo_int,
      clave_generada,
    });

    if (r.status === "success") {
      // Cerrar modal + limpiar antes del alert
      cerrarModal(modalEditar, formEditar);
      Swal.fire({
        icon: "success",
        title: "Actualizado",
        text: r.message,
        timer: 1400,
        showConfirmButton: false,
      }).then(() => location.reload());
    } else {
      Swal.fire("Error", r.message || "No se pudo actualizar", "error");
    }
  });

  // ===== ELIMINAR =====
  tablaAsignaciones?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    const tr = btn.closest("tr");
    const id = tr.dataset.id;
    const materiaTxt =
      tr.querySelector("td:nth-child(2)")?.innerText || "esta asignación";

    Swal.fire({
      title: `¿Eliminar "${materiaTxt}"?`,
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, eliminar",
    }).then(async (res) => {
      if (!res.isConfirmed) return;

      const r = await enviar("eliminar", { id_asignacion: id });
      if (r.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Eliminado",
          text: r.message,
          timer: 1400,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire("Error", r.message || "No se pudo eliminar", "error");
      }
    });
  });

  // ===== BUSCAR =====
  buscarInput?.addEventListener("keyup", function () {
    const filtro = this.value.toLowerCase();
    document.querySelectorAll("#tablaAsignaciones tbody tr").forEach((fila) => {
      fila.style.display = fila.innerText.toLowerCase().includes(filtro)
        ? ""
        : "none";
    });
  });

  // ===== EXPORTAR (solo PDF con spinner y confirmación) =====
  btnExportar?.addEventListener("click", function () {
    Swal.fire({
      title: "Generando PDF...",
      text: "Por favor espera mientras preparamos la descarga.",
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    setTimeout(() => {
      // Controlador de descarga de ASIGNACIONES (PDF)
      window.location.href =
        "../../controladores/admin/exportar_asignaciones.php?tipo=pdf";

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
