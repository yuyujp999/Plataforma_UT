document.addEventListener("DOMContentLoaded", () => {
  // ====== PERMISOS DESDE PHP ======
  const PERM = Object.assign(
    { crear: false, editar: false, eliminar: false },
    window.PERMISOS || {}
  );

  // === MODALES PRINCIPALES ===
  const modalNuevo = document.getElementById("modalNuevo");
  const modalEditar = document.getElementById("modalEditar");

  const formNuevo = document.getElementById("formNuevo");
  const formEditar = document.getElementById("formEditar");

  const btnNuevo = document.getElementById("btnNuevo");
  const btnCancelarNuevo = document.getElementById("cancelModal");
  const btnCerrarNuevo = document.getElementById("closeModal");
  const btnCancelarEditar = document.getElementById("cancelModalEditar");
  const btnCerrarEditar = document.getElementById("closeModalEditar");

  const tablaPagosBody = document.querySelector("#tablaPagos tbody");

  // Campos NUEVO
  const nuevoMatricula = document.getElementById("matricula");
  const nuevoPeriodo = document.getElementById("periodo");
  const nuevoConcepto = document.getElementById("concepto");
  const nuevoMonto = document.getElementById("monto");
  const nuevoAdeudo = document.getElementById("adeudo");
  const nuevoPago = document.getElementById("pago");
  const nuevoCondonacion = document.getElementById("condonacion");

  // Campos EDITAR
  const editId = document.getElementById("editId");
  const editMatricula = document.getElementById("editMatricula");
  const editPeriodo = document.getElementById("editPeriodo");
  const editConcepto = document.getElementById("editConcepto");
  const editMonto = document.getElementById("editMonto");
  const editAdeudo = document.getElementById("editAdeudo");
  const editPago = document.getElementById("editPago");
  const editCondonacion = document.getElementById("editCondonacion");

  // URL del controlador (SECRETARÍAS - PAGOS)
  const url = "../../controladores/secretarias/controller_pagos.php";

  // ====== MODAL BUSCAR ALUMNO ======
  const modalBuscar = document.getElementById("modalBuscarAlumno");
  const inputBuscarAlumno = document.getElementById("inputBuscarAlumno");
  const tbodyResultados = document.querySelector(
    "#tablaResultadosAlumnos tbody"
  );
  const btnBuscarNuevo = document.getElementById("btnBuscarMatriculaNuevo");
  const btnBuscarEditar = document.getElementById("btnBuscarMatriculaEditar");
  const inputMatriculaNuevo = document.getElementById("matricula");
  const inputMatriculaEditar = document.getElementById("editMatricula");
  const closeBuscar = document.getElementById("closeModalBuscarAlumno");
  const cancelBuscar = document.getElementById("cancelModalBuscarAlumno");

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
    !tablaPagosBody ||
    !nuevoMatricula ||
    !nuevoPeriodo ||
    !nuevoConcepto ||
    !nuevoMonto ||
    !nuevoAdeudo ||
    !nuevoPago ||
    !nuevoCondonacion ||
    !editId ||
    !editMatricula ||
    !editPeriodo ||
    !editConcepto ||
    !editMonto ||
    !editAdeudo ||
    !editPago ||
    !editCondonacion
  ) {
    console.error("Faltan elementos del DOM requeridos por Pagos.js");
    return;
  }

  // ===== Helpers =====
  const abrirModal = (m) => m.classList.add("active");
  const cerrarModal = (m, f) => {
    m.classList.remove("active");
    f.reset();
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
  if (PERM.crear && btnNuevo) {
    btnNuevo.addEventListener("click", () => {
      abrirModal(modalNuevo);
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
            title: "Pago/Cargo registrado",
            text: data.message || "Registro agregado correctamente",
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
    tablaPagosBody.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-editar");
      if (!btn) return;

      const fila = btn.closest("tr");
      idSeleccionado = fila.dataset.id;

      editId.value = idSeleccionado;
      editMatricula.value = fila.dataset.matricula || "";
      editPeriodo.value = fila.dataset.periodo || "";
      editConcepto.value = fila.dataset.concepto || "";
      editMonto.value = fila.dataset.monto || 0;
      editAdeudo.value = fila.dataset.adeudo || 0;
      editPago.value = fila.dataset.pago || 0;
      editCondonacion.value = fila.dataset.condonacion || 0;

      abrirModal(modalEditar);
    });

    // ===== EDITAR (enviar) =====
    formEditar.addEventListener("submit", async (e) => {
      e.preventDefault();
      const datos = new FormData(formEditar);
      datos.append("accion", "editar");
      datos.append("id", idSeleccionado);

      try {
        const data = await postJSON(datos);
        if (data.status === "success") {
          cerrarModal(modalEditar, formEditar);
          Swal.fire({
            icon: "success",
            title: "Registro actualizado",
            text: data.message || "Pago/Cargo actualizado correctamente",
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

  // ===== ELIMINAR — si se habilita permiso en PHP =====
  tablaPagosBody.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-eliminar");
    if (!btn) return;

    if (!PERM.eliminar) {
      Swal.fire({
        icon: "error",
        title: "Permiso denegado",
        text: "No puedes eliminar registros de pagos.",
      });
      return;
    }

    const fila = btn.closest("tr");
    const id = fila.dataset.id;
    const concepto = fila.dataset.concepto || `ID ${id}`;

    Swal.fire({
      title: `¿Eliminar el registro "${concepto}"?`,
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
        datos.append("id", id);

        try {
          const data = await postJSON(datos);
          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Registro eliminado",
              text: data.message || "Registro eliminado correctamente",
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

  // ===== BUSCADOR GENERAL DE PAGOS (input de arriba) =====
  const buscador = document.getElementById("buscarPago");
  if (buscador) {
    buscador.addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();
      document.querySelectorAll("#tablaPagos tbody tr").forEach((fila) => {
        fila.style.display = fila.innerText.toLowerCase().includes(filtro)
          ? ""
          : "none";
      });
    });
  }

  // ================== LÓGICA MODAL BUSCAR ALUMNO ==================
  let modoBusqueda = null; // "nuevo" | "editar"
  let timeoutBuscar = null;

  const abrirModalBuscar = (modo) => {
    modoBusqueda = modo;
    modalBuscar.classList.remove("is-hidden");
    inputBuscarAlumno.value = "";
    tbodyResultados.innerHTML = "";
    inputBuscarAlumno.focus();
  };

  const cerrarModalBuscar = () => {
    modalBuscar.classList.add("is-hidden");
    modoBusqueda = null;
  };

  btnBuscarNuevo?.addEventListener("click", () => abrirModalBuscar("nuevo"));
  btnBuscarEditar?.addEventListener("click", () => abrirModalBuscar("editar"));
  closeBuscar?.addEventListener("click", cerrarModalBuscar);
  cancelBuscar?.addEventListener("click", cerrarModalBuscar);

  // Cerrar al hacer click fuera del contenido
  modalBuscar?.addEventListener("click", (e) => {
    if (e.target === modalBuscar) cerrarModalBuscar();
  });

  // Buscar alumnos mientras escribe
  inputBuscarAlumno?.addEventListener("keyup", () => {
    const texto = inputBuscarAlumno.value.trim();
    clearTimeout(timeoutBuscar);

    if (texto.length < 2) {
      tbodyResultados.innerHTML = "";
      return;
    }

    timeoutBuscar = setTimeout(async () => {
      try {
        const resp = await fetch(
          "../../controladores/secretarias/buscar_alumnos.php?q=" +
            encodeURIComponent(texto)
        );
        const data = await resp.json();

        tbodyResultados.innerHTML = "";
        if (!Array.isArray(data) || data.length === 0) {
          const tr = document.createElement("tr");
          tr.innerHTML = '<td colspan="2">Sin resultados</td>';
          tbodyResultados.appendChild(tr);
          return;
        }

        data.forEach((a) => {
          const tr = document.createElement("tr");
          const nombreCompleto = `${a.nombre} ${a.apellido_paterno} ${a.apellido_materno}`;
          tr.dataset.matricula = a.matricula;
          tr.dataset.nombre = nombreCompleto;

          tr.innerHTML = `
            <td class="matricula-cell">
              <span class="matricula-text">${a.matricula}</span>
              <button type="button" class="btnSelectAlumno">USAR</button>
            </td>
            <td class="nombre-cell">${nombreCompleto}</td>
          `;
          tbodyResultados.appendChild(tr);
        });
      } catch (err) {
        console.error("Error al buscar alumnos:", err);
      }
    }, 300);
  });

  // Seleccionar alumno (clic en fila o en chip USAR)
  tbodyResultados?.addEventListener("click", (e) => {
    const tr = e.target.closest("tr");
    if (!tr || !tr.dataset.matricula) return;

    const matricula = tr.dataset.matricula;

    if (modoBusqueda === "nuevo" && inputMatriculaNuevo) {
      inputMatriculaNuevo.value = matricula;
    } else if (modoBusqueda === "editar" && inputMatriculaEditar) {
      inputMatriculaEditar.value = matricula;
    }

    cerrarModalBuscar();
  });
});
