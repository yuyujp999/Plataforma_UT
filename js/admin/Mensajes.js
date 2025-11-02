// /js/admin/Mensajes.js
document.addEventListener("DOMContentLoaded", () => {
  // ====== ELEMENTOS DOM ======
  const overlayNuevo = document.getElementById("overlayNuevo");
  const overlayDetalle = document.getElementById("overlayDetalle");
  const modalDetalle = document.getElementById("modalDetalle");

  // Nuevo
  const btnNuevo = document.getElementById("btnNuevo");
  const btnCerrarNuevo = document.getElementById("cerrarNuevo");
  const btnCancelNuevo = document.getElementById("cancelNuevo");
  const btnGuardar = document.getElementById("guardarMensaje");

  // Detalle
  const btnCerrarDetalle = document.getElementById("cerrarDetalle");
  const btnCerrarDetalle2 = document.getElementById("cerrarDetalleBtn");
  const btnEditarMensaje = document.getElementById("btnEditarMensaje");
  const btnEliminarMensaje = document.getElementById("btnEliminarMensaje");
  const btnGuardarCambios = document.getElementById("btnGuardarCambios");
  const inputIdDetalle = document.getElementById("detalleIdMensaje");

  const tablaBody = document.getElementById("tablaBodyMensajes");
  const buscarInput = document.getElementById("buscarMateria");

  // Formulario Nuevo
  const formNuevo = document.getElementById("formNuevoMensaje");
  const inputTitulo = document.getElementById("titulo");
  const textareaCuerpo = document.getElementById("cuerpo");
  const selectPrioridad = document.getElementById("prioridad");
  const radiosDestino = document.querySelectorAll('input[name="destino"]');
  const selectSecretarias = document.getElementById("secretarias");
  const selectContainer = document.getElementById("selectSecretariasContainer");
  const contadorSecretarias = document.getElementById("contadorSecretarias");
  const resumenSeleccion = document.getElementById("resumenSeleccion");
  const chipsContainer = document.getElementById("chipsContainer");
  const btnEditarSeleccion = document.getElementById("editarSeleccion");

  // Campos del Detalle (mensaje)
  const detalleTitulo = document.getElementById("detalleTitulo");
  const detalleCuerpo = document.getElementById("detalleCuerpo");
  const detallePrioridad = document.getElementById("detallePrioridad");
  const detalleFecha = document.getElementById("detalleFecha");

  // Campos del Detalle (destinataria simple)
  const detalleSecretariasContainer =
    document.getElementById("editarDestinatariasBlock") ||
    document.getElementById("detalleSecretariasContainer");
  const detalleSecretarias =
    document.getElementById("selEditarSecretarias") ||
    document.getElementById("detalleSecretarias");
  const detalleContadorSecretarias =
    document.getElementById("contadorEditarSecretarias") ||
    document.getElementById("detalleContadorSecretarias");
  const detalleResumenSeleccion =
    document.getElementById("resumenEditarSeleccion") ||
    document.getElementById("detalleResumenSeleccion");
  const destinatariaSeleccionadaTexto = document.getElementById(
    "destinatariaSeleccionadaTexto"
  );
  const detalleEditarSeleccion =
    document.getElementById("editarSeleccionDetalle") ||
    document.getElementById("detalleEditarSeleccion");
  const notaTodas = document.getElementById("notaTodas");

  const tablaDestinatarias = document.querySelector(
    "#tablaDestinatarias tbody"
  );

  // Cache de secretarías para conteos y comparación
  let SECRETARIAS_CACHE = [];

  // ====== CONFIG ======
  const URL_API = "../../controladores/admin/controller_mensajes.php";
  const VAL_TODAS = "__TODAS__";

  // ====== UTILS ======
  const openOverlay = (ov) => {
    ov?.classList.add("active");
    document.body.style.overflow = "hidden";
  };
  const closeOverlay = (ov) => {
    ov?.classList.remove("active");
    document.body.style.overflow = "";
  };

  // cerrar por click fuera
  [overlayNuevo, overlayDetalle].forEach((ov) => {
    ov?.addEventListener("click", (e) => {
      if (e.target === ov) closeOverlay(ov);
    });
  });

  // cerrar por ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (overlayNuevo?.classList.contains("active"))
        closeOverlay(overlayNuevo);
      if (overlayDetalle?.classList.contains("active")) {
        setEditMode(false);
        closeOverlay(overlayDetalle);
      }
    }
  });

  function badgePrioridad(p) {
    const high = (p || "").toLowerCase() === "alta";
    return `<span class="btn ${
      high ? "btn-danger" : "btn-secondary"
    }" style="padding:3px 8px; border-radius:999px; font-size:12px;">${
      high ? "Alta" : "Normal"
    }</span>`;
  }

  function fmtFecha(dt) {
    if (!dt) return "-";
    const d = new Date(dt);
    return isNaN(d.getTime()) ? dt : d.toLocaleString();
  }

  async function enviar(accion, payload = {}) {
    const fd = new FormData();
    fd.append("accion", accion);
    Object.entries(payload).forEach(([k, v]) => {
      if (Array.isArray(v)) v.forEach((val) => fd.append(`${k}[]`, val));
      else fd.append(k, v);
    });
    try {
      const resp = await fetch(URL_API, { method: "POST", body: fd });
      return await resp.json();
    } catch (e) {
      Swal.fire("Error de red", e.message, "error");
      return { status: "error", message: e.message };
    }
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // ====== LISTA INICIAL ======
  cargarMensajes();

  async function cargarMensajes() {
    tablaBody.innerHTML = `<tr><td colspan="5">Cargando...</td></tr>`;
    const r = await enviar("listar_mensajes");
    if (r.status !== "success") {
      tablaBody.innerHTML = `<tr><td colspan="5">No se pudieron cargar los mensajes</td></tr>`;
      return;
    }
    const data = Array.isArray(r.data) ? r.data : [];
    if (!data.length) {
      tablaBody.innerHTML = `<tr><td colspan="5">Sin mensajes enviados</td></tr>`;
      return;
    }
    tablaBody.innerHTML = data
      .map((m) => {
        const total = Number(m.total_destinatarias || 0);
        const leidos = Number(m.total_leidos || 0);
        const pct = total > 0 ? Math.round((leidos / total) * 100) : 0;
        return `
        <tr data-id="${m.id_mensaje}">
          <td>${escapeHtml(m.titulo)}</td>
          <td>${badgePrioridad(m.prioridad)}</td>
          <td>${escapeHtml(fmtFecha(m.fecha_envio))}</td>
          <td>${leidos}/${total} (${pct}%)</td>
          <td>
            <button class="btn btn table-btn btn-ver" title="Ver detalle">
              <i class="fas fa-eye"></i>
            </button>
          </td>
        </tr>`;
      })
      .join("");
  }

  // ====== NUEVO MENSAJE ======
  btnNuevo?.addEventListener("click", async () => {
    await cargarSecretariasNuevo();
    openOverlay(overlayNuevo);
  });
  btnCerrarNuevo?.addEventListener("click", () => cerrarNuevo());
  btnCancelNuevo?.addEventListener("click", () => cerrarNuevo());
  btnEditarSeleccion?.addEventListener("click", () => {
    selectSecretarias.style.display = "";
    resumenSeleccion.style.display = "none";
    selectSecretarias.focus();
  });

  function cerrarNuevo() {
    closeOverlay(overlayNuevo);
    formNuevo?.reset();
    if (selectSecretarias) {
      [...selectSecretarias.options].forEach((o) => (o.selected = false));
      contadorSecretarias.textContent = "0 seleccionadas";
      selectSecretarias.style.display = "";
      resumenSeleccion.style.display = "none";
    }
    selectContainer.style.display = "none";
    const rbTodas = document.querySelector(
      'input[name="destino"][value="todas"]'
    );
    rbTodas && (rbTodas.checked = true);
  }

  // Radios: mostrar/ocultar selector (Nuevo)
  radiosDestino.forEach((rb) => {
    rb.addEventListener("change", (e) => {
      if (e.target.value === "especificas") {
        selectContainer.style.display = "block";
        selectSecretarias.style.display = "";
        resumenSeleccion.style.display = "none";
      } else {
        selectContainer.style.display = "none";
      }
    });
  });

  // Al cambiar selección (Nuevo)
  selectSecretarias?.addEventListener("change", () => {
    const selected = [...selectSecretarias.options].filter((o) => o.selected);
    contadorSecretarias.textContent = `${selected.length} seleccionadas`;

    chipsContainer.innerHTML = selected
      .map((o) => `<span class="chip">${escapeHtml(o.textContent)}</span>`)
      .join("");

    if (selected.length > 0) {
      selectSecretarias.style.display = "none";
      resumenSeleccion.style.display = "block";
    } else {
      selectSecretarias.style.display = "";
      resumenSeleccion.style.display = "none";
    }
  });

  // Enviar (Nuevo)
  btnGuardar?.addEventListener("click", async () => {
    const titulo = inputTitulo.value.trim();
    const cuerpo = textareaCuerpo.value.trim();
    if (!titulo || !cuerpo) {
      Swal.fire(
        "Faltan datos",
        "Título y contenido son obligatorios.",
        "warning"
      );
      return;
    }

    const destino = [...radiosDestino].find((r) => r.checked)?.value || "todas";
    const idsSecretarias =
      destino === "especificas"
        ? [...selectSecretarias.options]
            .filter((o) => o.selected)
            .map((o) => o.value)
        : [];

    if (destino === "especificas" && idsSecretarias.length === 0) {
      Swal.fire("Selecciona", "Elige al menos una secretaría.", "info");
      return;
    }

    const { isConfirmed } = await Swal.fire({
      title: "¿Enviar mensaje?",
      text:
        destino === "todas"
          ? "Se enviará a todas las secretarías."
          : `Se enviará a ${idsSecretarias.length} secretarías.`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, enviar",
      cancelButtonText: "Cancelar",
    });
    if (!isConfirmed) return;

    const r = await enviar("crear_mensaje", {
      titulo,
      cuerpo,
      prioridad: selectPrioridad.value || "normal",
      destino,
      secretarias: idsSecretarias,
    });

    if (r.status === "success") {
      closeOverlay(overlayNuevo);
      Swal.fire({
        icon: "success",
        title: "Mensaje enviado",
        text: r.message || "Se envió correctamente.",
        timer: 1400,
        showConfirmButton: false,
      });
      // limpiar
      formNuevo?.reset();
      if (selectSecretarias) {
        [...selectSecretarias.options].forEach((o) => (o.selected = false));
        contadorSecretarias.textContent = "0 seleccionadas";
        selectSecretarias.style.display = "";
        resumenSeleccion.style.display = "none";
      }
      selectContainer.style.display = "none";
      const rbTodas = document.querySelector(
        'input[name="destino"][value="todas"]'
      );
      rbTodas && (rbTodas.checked = true);
      // refrescar
      cargarMensajes();
    } else {
      Swal.fire("Error", r.message || "No se pudo enviar el mensaje.", "error");
    }
  });

  // Cargar secretarías (Nuevo) una sola vez
  async function cargarSecretariasNuevo() {
    if (!selectSecretarias) return;
    if (selectSecretarias.options.length > 0) return;
    const r = await enviar("listar_secretarias");
    if (r.status !== "success") return;

    const lista = Array.isArray(r.data) ? r.data : [];
    const frag = document.createDocumentFragment();
    lista.forEach((s) => {
      const opt = document.createElement("option");
      opt.value = s.id_secretaria;
      const nombre = [s.nombre, s.apellido_paterno, s.apellido_materno]
        .filter(Boolean)
        .join(" ");
      opt.textContent = nombre || `Secretaría #${s.id_secretaria}`;
      frag.appendChild(opt);
    });
    selectSecretarias.appendChild(frag);
  }

  // ====== DETALLE ======
  tablaBody?.addEventListener("click", async (e) => {
    const btn = e.target.closest(".btn-ver");
    if (!btn) return;

    const tr = btn.closest("tr");
    const id = tr?.dataset?.id;
    if (!id) return;

    const r = await enviar("detalle_mensaje", { id_mensaje: id });
    if (r.status !== "success" || !r.data) {
      Swal.fire(
        "Error",
        r.message || "No se pudo obtener el detalle.",
        "error"
      );
      return;
    }

    const { mensaje, destinatarias } = r.data;

    // Set id oculto
    inputIdDetalle.value = mensaje?.id_mensaje || id;

    // Rellenar campos
    detalleTitulo.value = mensaje?.titulo || "";
    detalleCuerpo.value = mensaje?.cuerpo || "";
    detallePrioridad.value = (mensaje?.prioridad || "normal").toUpperCase();
    detalleFecha.value = fmtFecha(mensaje?.fecha_envio);

    // Tabla de lectura
    tablaDestinatarias.innerHTML = (destinatarias || [])
      .map((d) => {
        const nombre = d.nombre_secretaria || d.nombre || `#${d.id_secretaria}`;
        const leido = d.leido_en ? "Sí" : "No";
        return `
        <tr>
          <td>${escapeHtml(nombre)}</td>
          <td>${leido}</td>
          <td>${escapeHtml(fmtFecha(d.leido_en || ""))}</td>
        </tr>`;
      })
      .join("");

    // Cargar y preseleccionar destinataria (simple)
    await cargarSecretariasDetalle();
    preseleccionarDestinatariaSimple(destinatarias || []);

    // Mostrar resumen (colapsado) por defecto
    if (detalleSecretariasContainer)
      detalleSecretariasContainer.style.display = "none";
    if (detalleSecretarias) detalleSecretarias.style.display = "none";
    if (detalleResumenSeleccion)
      detalleResumenSeleccion.style.display = "block";

    setEditMode(false);
    openOverlay(overlayDetalle);
  });

  btnCerrarDetalle?.addEventListener("click", () => {
    setEditMode(false);
    closeOverlay(overlayDetalle);
  });
  btnCerrarDetalle2?.addEventListener("click", () => {
    setEditMode(false);
    closeOverlay(overlayDetalle);
  });

  // ====== EDITAR / GUARDAR / ELIMINAR ======
  function setEditMode(on) {
    if (!modalDetalle) return;
    if (on) {
      modalDetalle.classList.add("is-editing");
      detalleTitulo.readOnly = false;
      detalleCuerpo.readOnly = false;
      detallePrioridad.readOnly = false;
      if (detalleSecretariasContainer)
        detalleSecretariasContainer.style.display = "block";
      if (detalleSecretarias) detalleSecretarias.style.display = "";
      if (detalleResumenSeleccion)
        detalleResumenSeleccion.style.display = "none";
      detalleTitulo.focus();
    } else {
      modalDetalle.classList.remove("is-editing");
      detalleTitulo.readOnly = true;
      detalleCuerpo.readOnly = true;
      detallePrioridad.readOnly = true;
      if (detalleSecretarias) detalleSecretarias.style.display = "none";
      if (detalleSecretariasContainer)
        detalleSecretariasContainer.style.display = "none";
      if (detalleResumenSeleccion)
        detalleResumenSeleccion.style.display = "block";
    }
  }

  // Lápiz (toggle edición)
  btnEditarMensaje?.addEventListener("click", () => {
    const editing = modalDetalle.classList.contains("is-editing");
    setEditMode(!editing);
  });

  // “Cambiar destinataria” (mostrar select)
  detalleEditarSeleccion?.addEventListener("click", () => {
    if (!modalDetalle.classList.contains("is-editing")) {
      modalDetalle.classList.add("is-editing");
      detalleTitulo.readOnly = false;
      detalleCuerpo.readOnly = false;
      detallePrioridad.readOnly = false;
    }
    if (detalleSecretariasContainer)
      detalleSecretariasContainer.style.display = "block";
    if (detalleSecretarias) detalleSecretarias.style.display = "";
    if (detalleResumenSeleccion) detalleResumenSeleccion.style.display = "none";
    detalleSecretarias?.focus();
  });

  // Al cambiar la destinataria (simple)
  detalleSecretarias?.addEventListener("change", () => {
    pintarResumenDestinataria();
  });

  function pintarResumenDestinataria() {
    if (!detalleSecretarias) return;
    const val = detalleSecretarias.value || "";
    const txt =
      val === VAL_TODAS
        ? "Todas las secretarías"
        : detalleSecretarias.selectedOptions[0]
        ? detalleSecretarias.selectedOptions[0].textContent
        : "—";

    if (destinatariaSeleccionadaTexto)
      destinatariaSeleccionadaTexto.textContent = txt;

    if (detalleContadorSecretarias) {
      if (val === VAL_TODAS) {
        detalleContadorSecretarias.textContent = "Todas seleccionadas";
      } else if (val) {
        detalleContadorSecretarias.textContent = "1 seleccionada";
      } else {
        detalleContadorSecretarias.textContent = "0 seleccionadas";
      }
    }

    if (notaTodas) {
      notaTodas.style.display = val === VAL_TODAS ? "block" : "none";
    }
  }

  btnGuardarCambios?.addEventListener("click", async () => {
    const id = inputIdDetalle.value;
    const titulo = (detalleTitulo.value || "").trim();
    const cuerpo = (detalleCuerpo.value || "").trim();
    let prioridad = (detallePrioridad.value || "").trim().toLowerCase();

    if (!titulo || !cuerpo) {
      Swal.fire(
        "Faltan datos",
        "Título y contenido son obligatorios.",
        "warning"
      );
      return;
    }
    if (!["normal", "alta"].includes(prioridad)) {
      if (prioridad === "ALTA") prioridad = "alta";
      else prioridad = "normal";
    }

    // Selección de destinataria simple (todas o una)
    const val = detalleSecretarias ? detalleSecretarias.value : "";
    if (!val) {
      await Swal.fire({
        title: "Sin destinataria",
        text: "Elige una destinataria o 'Todas las secretarías'.",
        icon: "info",
      });
      return;
    }

    const { isConfirmed } = await Swal.fire({
      title: "¿Guardar cambios?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, guardar",
      cancelButtonText: "Cancelar",
    });
    if (!isConfirmed) return;

    // 1) Actualizar texto/prioridad del mensaje
    const r1 = await enviar("actualizar_mensaje", {
      id_mensaje: id,
      titulo,
      cuerpo,
      prioridad,
    });
    if (r1.status !== "success") {
      Swal.fire(
        "Error",
        r1.message || "No se pudo actualizar el mensaje.",
        "error"
      );
      return;
    }

    // 2) Actualizar destinatarias
    let payloadDest;
    if (val === VAL_TODAS) {
      payloadDest = { id_mensaje: id, destino: "todas" };
    } else {
      payloadDest = {
        id_mensaje: id,
        destino: "especificas",
        secretarias: [val],
      };
    }
    const r2 = await enviar("actualizar_destinatarias", payloadDest);
    if (r2.status !== "success") {
      Swal.fire(
        "Error",
        r2.message || "No se pudo actualizar la destinataria.",
        "error"
      );
      return;
    }

    // Éxito total
    closeOverlay(overlayDetalle);
    Swal.fire({
      icon: "success",
      title: "Cambios guardados",
      text: "Se actualizo correctamente el mensaje",
      timer: 1400,
      showConfirmButton: false,
    });
    cargarMensajes();
  });

  btnEliminarMensaje?.addEventListener("click", async () => {
    const id = inputIdDetalle.value;
    const { isConfirmed } = await Swal.fire({
      title: "¿Eliminar este mensaje?",
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    });
    if (!isConfirmed) return;

    const r = await enviar("eliminar_mensaje", { id_mensaje: id });
    if (r.status === "success") {
      closeOverlay(overlayDetalle);
      Swal.fire({
        icon: "success",
        title: "Eliminado",
        text: "Se elimino correctamente el mensaje",
        timer: 1400,
        showConfirmButton: false,
      });
      cargarMensajes();
    } else {
      Swal.fire("Error", r.message || "No se pudo eliminar.", "error");
    }
  });

  // ====== BUSCADOR ======
  buscarInput?.addEventListener("keyup", function () {
    const filtro = this.value.toLowerCase();
    document.querySelectorAll("#tablaMensajes tbody tr").forEach((fila) => {
      fila.style.display = fila.innerText.toLowerCase().includes(filtro)
        ? ""
        : "none";
    });
  });

  // ====== Helpers detalle: cargar y preseleccionar (simple/TODAS) ======
  async function cargarSecretariasDetalle() {
    if (!detalleSecretarias) return;
    detalleSecretarias.innerHTML = "";

    const r = await enviar("listar_secretarias");
    if (r.status !== "success") return;

    const lista = Array.isArray(r.data) ? r.data : [];
    SECRETARIAS_CACHE = lista.slice();

    // Opción "Todas"
    const optAll = document.createElement("option");
    optAll.value = VAL_TODAS;
    optAll.textContent = "Todas las secretarías";
    detalleSecretarias.appendChild(optAll);

    // Opciones individuales
    const frag = document.createDocumentFragment();
    lista.forEach((s) => {
      const opt = document.createElement("option");
      opt.value = s.id_secretaria;
      const nombre = [s.nombre, s.apellido_paterno, s.apellido_materno]
        .filter(Boolean)
        .join(" ");
      opt.textContent = nombre || `Secretaría #${s.id_secretaria}`;
      frag.appendChild(opt);
    });
    detalleSecretarias.appendChild(frag);
  }

  function preseleccionarDestinatariaSimple(destinatarias) {
    if (!detalleSecretarias) return;

    // Si llega con TODAS: coincide el largo con el total
    if (
      SECRETARIAS_CACHE.length > 0 &&
      destinatarias.length === SECRETARIAS_CACHE.length
    ) {
      detalleSecretarias.value = VAL_TODAS;
    } else if (destinatarias.length >= 1) {
      // Tomamos la primera como “la destinataria”
      detalleSecretarias.value = String(destinatarias[0].id_secretaria || "");
      // Si no existe esa opción (inconsistencia), fallback a TODAS
      if (!detalleSecretarias.value) detalleSecretarias.value = VAL_TODAS;
    } else {
      // Sin datos: fallback a TODAS
      detalleSecretarias.value = VAL_TODAS;
    }

    pintarResumenDestinataria();
  }
});
