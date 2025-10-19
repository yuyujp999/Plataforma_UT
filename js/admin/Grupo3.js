document.addEventListener("DOMContentLoaded", () => {
  // --- MODALES ---
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

  // --- Campos nuevo ---
  const nuevoNombreGrado = document.getElementById("nombre_grado"); // <select value = id_nombre_semestre>
  const nuevoNombreGrupo = document.getElementById("nombre_grupo"); // <input readonly>

  // --- Campos editar ---
  const editNombreGrado = document.getElementById("editNombreGrado"); // <select value = id_nombre_semestre>
  const editNombreGrupo = document.getElementById("editNombreGrupo"); // <input readonly>

  let idSeleccionado = null;
  const url = "../../controladores/admin/controller_grupos.php";

  // --- FUNCIONES MODAL ---
  const abrirModal = (m) => m.classList.add("active");
  const cerrarModal = (m, f) => {
    m.classList.remove("active");
    if (f) f.reset();
  };

  // === Backend: sugerir primer hueco libre (G1, G2, ...) para el semestre ===
  async function sugerirNombreServidor(idNombreSemestre) {
    if (!idNombreSemestre) return "";
    const fd = new FormData();
    fd.append("accion", "sugerir_nombre");
    fd.append("id_nombre_semestre", String(idNombreSemestre));
    const resp = await fetch(url, { method: "POST", body: fd });
    const data = await resp.json();
    if (data.status === "success" && data.sugerido) return data.sugerido;
    throw new Error(data.message || "No se pudo sugerir el nombre del grupo");
  }

  // Rellena el input del modal NUEVO con la sugerencia del servidor
  async function autollenarNuevo() {
    const idSem = parseInt(nuevoNombreGrado.value || "0", 10);
    if (!idSem) {
      nuevoNombreGrupo.value = "";
      return;
    }
    try {
      nuevoNombreGrupo.value = await sugerirNombreServidor(idSem);
    } catch (e) {
      console.error(e);
      nuevoNombreGrupo.value = ""; // evita mostrar un cálculo local errado
    }
  }

  // Rellena el input del modal EDITAR cuando cambie el semestre
  async function autollenarEditarSiCorresponde() {
    const idSem = parseInt(editNombreGrado.value || "0", 10);
    if (!idSem) return;
    try {
      // Como el input es readonly, asumimos modo "auto"; traemos la sugerencia del server
      editNombreGrupo.value = await sugerirNombreServidor(idSem);
    } catch (e) {
      console.error(e);
    }
  }

  // --- Abrir/Cerrar modales ---
  btnNuevo.addEventListener("click", async () => {
    abrirModal(modalNuevo);
    await autollenarNuevo();
  });
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

  // --- Cambios en selects: pedir sugerencia al backend ---
  nuevoNombreGrado.addEventListener("change", autollenarNuevo);
  editNombreGrado.addEventListener("change", autollenarEditarSiCorresponde);

  // --- Filtro de búsqueda ---
  const bus = document.getElementById("buscarGrado");
  bus?.addEventListener("keyup", function () {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll("#tablaGrados tbody tr");
    filas.forEach((fila) => {
      fila.style.display = fila.innerText.toLowerCase().includes(filtro)
        ? ""
        : "none";
    });
  });

  // Utilitario para deshabilitar/rehabilitar botones de formulario
  const setFormLoading = (form, loading) => {
    const btns = form.querySelectorAll("button, input[type=submit]");
    btns.forEach((b) => (b.disabled = loading));
  };

  // --- Agregar grupo ---
  formNuevo.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Forzamos que el nombre enviado venga del servidor (primer hueco libre)
    const idSem = parseInt(nuevoNombreGrado.value || "0", 10);
    if (!idSem) {
      Swal.fire({ icon: "error", title: "Falta seleccionar semestre" });
      return;
    }

    // Si por alguna razón el input viene vacío, vuelve a pedir sugerencia
    if (!nuevoNombreGrupo.value) {
      try {
        nuevoNombreGrupo.value = await sugerirNombreServidor(idSem);
      } catch {}
    }

    const datos = new FormData(formNuevo);
    datos.append("accion", "crear");
    // Asegura que se envíe el nombre visible (readonly) calculado por el server
    datos.set("nombre_grupo", nuevoNombreGrupo.value);

    try {
      setFormLoading(formNuevo, true);
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

      if (data.status === "success" || data.status === "ok") {
        cerrarModal(modalNuevo, formNuevo);
        Swal.fire({
          icon: "success",
          title: "Grupo agregado",
          text: `Se creó el grupo ${
            data.grupo?.nombre_grupo || nuevoNombreGrupo.value
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

  // --- Abrir modal de edición ---
  tablaGrupos.addEventListener("click", async (e) => {
    const btn = e.target.closest(".btn-editar");
    if (!btn) return;
    const fila = btn.closest("tr");
    idSeleccionado = fila.dataset.id;

    // setea valores actuales
    const idSem = fila.dataset.idNombreSemestre || "";
    if (idSem) editNombreGrado.value = idSem;
    editNombreGrupo.value = fila.dataset.nombreGrupo || "";

    abrirModal(modalEditar);
  });

  // --- Editar grupo ---
  formEditar.addEventListener("submit", async (e) => {
    e.preventDefault();
    const datos = new FormData(formEditar);
    datos.append("accion", "editar");
    datos.append("id_grupo", idSeleccionado);

    try {
      setFormLoading(formEditar, true);
      const resp = await fetch(url, { method: "POST", body: datos });
      const data = await resp.json();

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

  // --- Eliminar grupo ---
  tablaGrupos.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    const fila = btn.closest("tr");
    const id = fila.dataset.id;
    const nombre = fila.dataset.nombreGrupo || "(sin nombre)";

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
        datos.append("id_grupo", id);

        try {
          const resp = await fetch(url, { method: "POST", body: datos });
          const data = await resp.json();
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
          Swal.fire({
            icon: "error",
            title: "Error de red",
            text: err.message,
          });
        }
      }
    });
  });
});
