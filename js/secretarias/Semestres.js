document.addEventListener("DOMContentLoaded", () => {
  // ====== PERMISOS DESDE PHP ======
  const PERM = Object.assign(
    { crear: false, editar: false, eliminar: false },
    window.PERMISOS || {}
  );

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

  const tablaGrados = document.querySelector("#tablaGrados tbody");

  // Campos NUEVO
  const nuevoSemestre = document.getElementById("semestre");
  const nuevoIdCarrera = document.getElementById("id_carrera");

  // Campos EDITAR
  const editNumero = document.getElementById("editSemestre");
  const editIdCarrera = document.getElementById("editIdCarrera");

  // URL del controlador (SECRETARÍAS)
  const url = "../../controladores/secretarias/controller_semestres.php";

  // Chequeos mínimos
  if (
    !modalNuevo ||
    !modalEditar ||
    !formNuevo ||
    !formEditar ||
    !btnCancelarNuevo ||
    !btnCerrarNuevo ||
    !btnCancelarEditar ||
    !btnCerrarEditar ||
    !tablaGrados ||
    !nuevoSemestre ||
    !nuevoIdCarrera ||
    !editNumero ||
    !editIdCarrera
  ) {
    console.error("Faltan elementos del DOM requeridos por Semestres.js");
    return;
  }

  // ===== Helpers =====
  const isOpen = (modal) => modal.classList.contains("active");
  const getPreviewNuevo = () =>
    formNuevo?.querySelector(
      'input#id_nombre_semestre[name="id_nombre_semestre"]'
    ) || null;
  const getPreviewEditar = () =>
    formEditar?.querySelector(
      'input#editNombreSemestre[name="id_nombre_semestre"]'
    ) || null;

  const abrirModal = (m) => m.classList.add("active");
  const cerrarModal = (m, f) => {
    m.classList.remove("active");
    f.reset();
    const pN = getPreviewNuevo();
    if (pN) pN.value = "";
    const pE = getPreviewEditar();
    if (pE) pE.value = "";
  };

  // Autocompletar (solo cuando el modal está abierto)
  const actualizarNombreNuevo = () => {
    if (!isOpen(modalNuevo)) return;
    const preview = getPreviewNuevo();
    if (!preview) return;
    const carreraTexto = nuevoIdCarrera?.selectedOptions?.[0]?.text || "";
    const semestreValor = nuevoSemestre?.value || "";
    preview.value =
      carreraTexto && semestreValor ? `${carreraTexto} ${semestreValor}` : "";
  };

  const actualizarNombreEditar = () => {
    if (!isOpen(modalEditar)) return;
    const preview = getPreviewEditar();
    if (!preview) return;
    const carreraTexto = editIdCarrera?.selectedOptions?.[0]?.text || "";
    const semestreValor = editNumero?.value || "";
    preview.value =
      carreraTexto && semestreValor ? `${carreraTexto} ${semestreValor}` : "";
  };

  // Fetch seguro (evita Unexpected token '<')
  const postJSON = async (formData) => {
    const resp = await fetch(url, { method: "POST", body: formData });
    const text = await resp.text();
    try {
      return JSON.parse(text);
    } catch {
      throw new Error(text);
    }
  };

  // ===== Abrir/Cerrar modales =====
  if (PERM.crear) {
    btnNuevo?.addEventListener("click", () => {
      abrirModal(modalNuevo);
      actualizarNombreNuevo();
    });
  }
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

  // Listeners de autocompletar
  nuevoIdCarrera.addEventListener("change", actualizarNombreNuevo);
  nuevoSemestre.addEventListener("input", actualizarNombreNuevo);
  editIdCarrera.addEventListener("change", actualizarNombreEditar);
  editNumero.addEventListener("input", actualizarNombreEditar);

  // ===== AGREGAR (solo si hay permiso) =====
  if (PERM.crear) {
    formNuevo.addEventListener("submit", async (e) => {
      e.preventDefault();
      const datos = new FormData(formNuevo);
      datos.append("accion", "crear");

      try {
        const data = await postJSON(datos);
        if (data.status === "success") {
          cerrarModal(modalNuevo, formNuevo);
          Swal.fire({
            icon: "success",
            title: "Semestre agregado",
            text: data.message || "Semestre agregado correctamente",
            timer: 1500,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "Ocurrió un problema.",
          });
        }
      } catch (err) {
        Swal.fire({ icon: "error", title: "Error de red", text: err.message });
      }
    });
  }

  // ===== EDITAR (abrir) — solo si hay permiso =====
  let idSeleccionado = null;
  if (PERM.editar) {
    tablaGrados.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-editar");
      if (!btn) return;

      const fila = btn.closest("tr");
      idSeleccionado = fila.dataset.id;

      editNumero.value = fila.dataset.semestre || "";
      editIdCarrera.value = fila.dataset.carrera || "";

      abrirModal(modalEditar);

      const preview = getPreviewEditar();
      if (preview) {
        preview.value = fila.dataset.nombre || "";
        if (!preview.value) actualizarNombreEditar();
      }
    });

    // ===== EDITAR (enviar) =====
    formEditar.addEventListener("submit", async (e) => {
      e.preventDefault();
      const datos = new FormData(formEditar);
      datos.append("accion", "editar");
      datos.append("id_semestre", idSeleccionado);

      try {
        const data = await postJSON(datos);
        if (data.status === "success") {
          cerrarModal(modalEditar, formEditar);
          Swal.fire({
            icon: "success",
            title: "Semestre actualizado",
            text: data.message || "Semestre actualizado correctamente",
            timer: 1500,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "Ocurrió un problema.",
          });
        }
      } catch (err) {
        Swal.fire({ icon: "error", title: "Error de red", text: err.message });
      }
    });
  }

  // ===== ELIMINAR — solo Admin (si no hay permiso, bloquea) =====
  tablaGrados.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    if (!PERM.eliminar) {
      Swal.fire({
        icon: "error",
        title: "Permiso denegado",
        text: "No puedes eliminar semestres.",
      });
      return;
    }

    const fila = btn.closest("tr");
    const id = fila.dataset.id;
    const nombre = fila.dataset.nombre || `ID ${id}`;

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
        datos.append("id_semestre", id);

        try {
          const data = await postJSON(datos);
          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Semestre eliminado",
              text: data.message || "Semestre eliminado correctamente",
              timer: 1500,
              showConfirmButton: false,
            }).then(() => location.reload());
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: data.message || "Ocurrió un problema.",
            });
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
  const buscador = document.getElementById("buscarGrado");
  if (buscador) {
    buscador.addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      document.querySelectorAll("#tablaGrados tbody tr").forEach((fila) => {
        fila.style.display = fila.innerText.toLowerCase().includes(filtro)
          ? ""
          : "none";
      });
    });
  }
});
