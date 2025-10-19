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

  // Campos NUEVO
  const docenteNuevo = document.getElementById("docente");
  const materiaNuevo = document.getElementById("materia"); // value = id_nombre_materia
  const nombreProfesorGrupoNuevo = document.getElementById(
    "nombreProfesorGrupo"
  );

  // Campos EDITAR
  const editDocente = document.getElementById("editDocente");
  const editMateria = document.getElementById("editMateria"); // value = id_nombre_materia
  const nombreProfesorGrupoEditar = document.getElementById(
    "editNombreProfesorGrupo"
  );

  let idSeleccionado = null;

  const url = "../../controladores/admin/controller_asignar_docente.php";

  // ===== FUNCIONES MODAL =====
  const abrirModal = (m) => m.classList.add("active");
  const cerrarModal = (m, f) => {
    m.classList.remove("active");
    if (f) {
      f.reset();
      const inputNombre = f.querySelector(
        "input[name='nombre_profesor_materia_grupo']"
      );
      if (inputNombre) inputNombre.value = "";
    }
  };

  // Abrir/Cerrar modales
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

  // ===== VALIDAR DUPLICADO (nuevo esquema) =====
  async function validarDuplicado(
    idDocente,
    idNombreMateria,
    id_asignacion = 0
  ) {
    const form = new FormData();
    form.append("accion", "validarDuplicado");
    form.append("id_docente", idDocente);
    form.append("id_nombre_materia", idNombreMateria);
    if (id_asignacion) form.append("id_asignacion", id_asignacion);

    const resp = await fetch(url, { method: "POST", body: form });
    return await resp.json();
  }

  // ===== ARMAR TEXTO "Profesor {Docente} - {Clave}" =====
  async function actualizarNombreProfesorGrupo(
    docenteSelect,
    materiaSelect,
    outputInput,
    id_asignacion = 0
  ) {
    const docenteNombre =
      docenteSelect?.options[docenteSelect.selectedIndex]?.text || "";
    const idNombreMateria = materiaSelect?.value || "";
    const claveTxt =
      materiaSelect?.options[materiaSelect.selectedIndex]?.text || "";

    if (!docenteNombre || !idNombreMateria) {
      outputInput.value = "";
      return false;
    }

    const resDuplicado = await validarDuplicado(
      docenteSelect.value,
      idNombreMateria,
      id_asignacion
    );
    if (resDuplicado.status === "error") {
      Swal.fire({
        icon: "warning",
        title: "Duplicado",
        text: resDuplicado.message,
      });
      outputInput.value = "";
      return false;
    }

    outputInput.value = `Profesor ${docenteNombre} - ${claveTxt}`;
    return true;
  }

  // ===== Listeners para autogenerar texto =====
  docenteNuevo?.addEventListener("change", () =>
    actualizarNombreProfesorGrupo(
      docenteNuevo,
      materiaNuevo,
      nombreProfesorGrupoNuevo
    )
  );
  materiaNuevo?.addEventListener("change", () =>
    actualizarNombreProfesorGrupo(
      docenteNuevo,
      materiaNuevo,
      nombreProfesorGrupoNuevo
    )
  );

  editDocente?.addEventListener("change", () =>
    actualizarNombreProfesorGrupo(
      editDocente,
      editMateria,
      nombreProfesorGrupoEditar,
      idSeleccionado
    )
  );
  editMateria?.addEventListener("change", () =>
    actualizarNombreProfesorGrupo(
      editDocente,
      editMateria,
      nombreProfesorGrupoEditar,
      idSeleccionado
    )
  );

  // ===== AGREGAR =====
  formNuevo?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const ok = await actualizarNombreProfesorGrupo(
      docenteNuevo,
      materiaNuevo,
      nombreProfesorGrupoNuevo
    );
    if (!ok) return;

    const datos = new FormData(formNuevo);
    datos.append("accion", "agregar");

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        // cierra modal ANTES del alert
        cerrarModal(modalNuevo, formNuevo);
        Swal.fire({
          icon: "success",
          title: "Asignación agregada",
          text: "Se agregó correctamente la asignación del docente.",
          timer: 1500,
          showConfirmButton: false,
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.message || "No se pudo agregar",
        });
      }
    } catch (err) {
      Swal.fire({ icon: "error", title: "Error de red", text: err.message });
    }
  });

  // ===== ABRIR EDITAR (soporta data-* viejo y nuevo) =====
  tablaAsignaciones?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-editar");
    if (!btn) return;

    const fila = btn.closest("tr");
    idSeleccionado = fila.dataset.id;

    // id docente (nuevo: data-id-docente | viejo: data-docente)
    const idDoc = fila.dataset.idDocente || fila.dataset.docente || "";
    // id nombre materia (nuevo: data-id-nombre-materia | viejo: data-materia)
    const idNomMat = fila.dataset.idNombreMateria || fila.dataset.materia || "";
    // texto PMG (nuevo: data-cpmg-nombre | viejo: data-nombregrupo)
    const pmgTxt = fila.dataset.cpmgNombre || fila.dataset.nombregrupo || "";

    if (editDocente) editDocente.value = idDoc;
    if (editMateria) editMateria.value = idNomMat;
    if (nombreProfesorGrupoEditar) nombreProfesorGrupoEditar.value = pmgTxt;

    abrirModal(modalEditar);
  });

  // ===== EDITAR =====
  formEditar?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const ok = await actualizarNombreProfesorGrupo(
      editDocente,
      editMateria,
      nombreProfesorGrupoEditar,
      idSeleccionado
    );
    if (!ok) return;

    const datos = new FormData(formEditar);
    datos.append("accion", "editar");
    datos.append("id_asignacion", idSeleccionado);

    try {
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success") {
        // cierra modal ANTES del alert
        cerrarModal(modalEditar, formEditar);
        Swal.fire({
          icon: "success",
          title: "Asignación actualizada",
          text: "Se actualizó correctamente la asignación del docente.",
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
    }
  });

  // ===== ELIMINAR =====
  tablaAsignaciones?.addEventListener("click", async (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    const fila = btn.closest("tr");
    const id = fila.dataset.id;
    const docenteTxt =
      fila.querySelector("td:nth-child(2)")?.innerText || "esta asignación";

    const result = await Swal.fire({
      title: `¿Eliminar asignación de "${docenteTxt}"?`,
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, eliminar",
    });

    if (!result.isConfirmed) return;

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
          text: "Se eliminó correctamente la asignación del docente.",
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

  // ===== BUSCAR =====
  document
    .getElementById("buscarAsignacion")
    ?.addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      document
        .querySelectorAll("#tablaAsignaciones tbody tr")
        .forEach((fila) => {
          fila.style.display = fila.innerText.toLowerCase().includes(filtro)
            ? ""
            : "none";
        });
    });

  // ===== EXPORTAR (solo PDF con spinner y confirmación) =====
  document
    .getElementById("btnExportar")
    ?.addEventListener("click", function () {
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
        window.location.href =
          "../../controladores/admin/exportar_asignaciones_docentes.php?tipo=pdf";

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
