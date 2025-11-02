document.addEventListener("DOMContentLoaded", () => {
  const CTRL_URL =
    window.CTRL_ASIG_URL ||
    "../../controladores/admin/controlador_asignaciones_alumnos.php";

  // === Selectores (grupos/asignaciones) ===
  const selGrupo = document.getElementById("selectGrupo");
  const btnAsignar = document.getElementById("btnAsignar");
  const tbody = document.querySelector("#tablaAlumnos tbody");
  const wrapResumen = document.getElementById("wrapResumen");
  const resumen = document.getElementById("resumenAsignaciones");
  const btnCerrarResumen = document.getElementById("btnCerrarResumen");
  const buscar = document.getElementById("buscarAsignacion");

  // === Selectores (ciclos) ===
  const selCiclo = document.getElementById("selectCiclo");
  const selGrupoDestino = document.getElementById("selectGrupoDestino");
  const btnAsignarCiclo = document.getElementById("btnAsignarCiclo");
  const tbodyPreviewCiclo = document.querySelector("#tablaPreviewCiclo tbody");

  // === Tablero "grupos con ciclo" ===
  const gridCiclos = document.getElementById("gridCiclos");

  if (!selGrupo || !btnAsignar || !tbody) return;

  async function fetchJSON(url, opts) {
    const resp = await fetch(url, opts);
    const txt = await resp.text();
    try {
      return JSON.parse(txt);
    } catch {
      throw new Error(
        `Respuesta no JSON (${resp.status}). ${txt.slice(0, 120)}`
      );
    }
  }

  // Helpers UI
  function showResumen() {
    wrapResumen?.classList.remove("is-hidden");
  }
  function hideResumen() {
    wrapResumen?.classList.add("is-hidden");
  }
  btnCerrarResumen?.addEventListener("click", hideResumen);

  // Resumen (cards)
  function pintarResumen(res) {
    if (!resumen || !res) return;
    const { grupo1, grupo2, pendientes } = res;
    resumen.innerHTML = "";

    const makeCard = (titulo, alumnos) => {
      const d = document.createElement("div");
      d.className = "mini-card";
      const rows = (alumnos || [])
        .map(
          (a, i) =>
            `<tr><td>${i + 1}</td><td>${a.nombre}</td><td>${
              a.matricula || ""
            }</td></tr>`
        )
        .join("");
      d.innerHTML = `
        <h4>${titulo}</h4>
        <span class="subtle">${(alumnos || []).length} alumno(s)</span>
        <table>
          <thead><tr><th>#</th><th>Alumno</th><th>Matrícula</th></tr></thead>
          <tbody>${
            rows || `<tr><td colspan="3" class="subtle">Sin alumnos.</td></tr>`
          }</tbody>
        </table>`;
      resumen.appendChild(d);
    };

    if (grupo1) makeCard(`Asignados a: ${grupo1.titulo}`, grupo1.alumnos);
    if (grupo2) makeCard(`Asignados a: ${grupo2.titulo}`, grupo2.alumnos);
    if (typeof pendientes === "number" && pendientes > 0) {
      const p = document.createElement("div");
      p.className = "mini-card";
      p.innerHTML = `<h4>Pendientes</h4><p class="subtle">Hay ${pendientes} alumno(s) sin asignar por falta de cupo y/o Grupo 2.</p>`;
      resumen.appendChild(p);
    }
    showResumen();
  }

  // Cargar grupos
  (async function cargarGrupos() {
    try {
      const j = await fetchJSON(`${CTRL_URL}?action=lista_grupos`);
      if (!j.ok) throw new Error(j.msg || "No se pudieron cargar grupos");
      selGrupo.innerHTML = '<option value="">Seleccionar grupo</option>';
      selGrupoDestino.innerHTML =
        '<option value="">(elige un grupo destino)</option>';
      (j.data || []).forEach((g) => {
        const o1 = document.createElement("option");
        o1.value = g.id_grupo;
        o1.textContent = g.nombre_grupo;
        selGrupo.appendChild(o1);
        const o2 = document.createElement("option");
        o2.value = g.id_grupo;
        o2.textContent = g.nombre_grupo;
        selGrupoDestino.appendChild(o2);
      });
    } catch (e) {
      selGrupo.innerHTML = '<option value="">Error cargando grupos</option>';
      selGrupoDestino.innerHTML =
        '<option value="">Error cargando grupos</option>';
      console.error(e);
      Swal.fire({ icon: "error", title: "Ups", text: e.message });
    }
  })();

  // Cambio de grupo -> disponibles + resumen + preview
  selGrupo.addEventListener("change", async function () {
    const idGrupo = this.value;
    btnAsignar.disabled = true;
    tbody.innerHTML = '<tr><td colspan="3">Cargando...</td></tr>';
    await cargarPreviewCiclo();

    if (!idGrupo) {
      tbody.innerHTML =
        '<tr><td colspan="3">Selecciona un grupo para listar alumnos.</td></tr>';
      resumen && (resumen.innerHTML = "");
      hideResumen();
      return;
    }

    try {
      const j = await fetchJSON(
        `${CTRL_URL}?action=alumnos_por_grupo&id_grupo=${encodeURIComponent(
          idGrupo
        )}`
      );
      if (!j.ok) throw new Error(j.msg || "No se pudieron cargar alumnos");

      pintarResumen(j.resumen);

      const alumnos = j.data || [];
      if (alumnos.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="3">No hay alumnos disponibles (ya asignados o sin registros).</td></tr>';
        btnAsignar.disabled = true;
        return;
      }
      tbody.innerHTML = "";
      alumnos.forEach((a) => {
        const tr = document.createElement("tr");
        tr.dataset.idAlumno = a.id_alumno;
        tr.innerHTML = `
          <td>${a.id_alumno}</td>
          <td>${a.nombre || ""} ${a.apellido_paterno || ""}</td>
          <td>${a.matricula || ""}</td>`;
        tbody.appendChild(tr);
      });
      btnAsignar.disabled = false;
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="3">Error cargando alumnos.</td></tr>';
      console.error(e);
      Swal.fire({ icon: "error", title: "Ups", text: e.message });
    }
  });

  // Asignar alumnos a grupo
  btnAsignar.addEventListener("click", async () => {
    const idGrupo = selGrupo.value;
    if (!idGrupo) return;

    const alumnos = Array.from(
      document.querySelectorAll("#tablaAlumnos tbody tr")
    )
      .map((tr) => tr.dataset.idAlumno)
      .filter(Boolean);

    if (alumnos.length === 0) {
      Swal.fire({
        icon: "info",
        title: "Sin alumnos",
        text: "No hay alumnos para asignar.",
      });
      return;
    }

    const confirm = await Swal.fire({
      icon: "question",
      title: "Confirmar asignación",
      text: `Se intentará asignar a ${alumnos.length} alumno(s). (Cupo por grupo: 30)`,
      showCancelButton: true,
      confirmButtonText: "Asignar",
      cancelButtonText: "Cancelar",
    });
    if (!confirm.isConfirmed) return;

    try {
      const body = new URLSearchParams();
      body.append("id_grupo", idGrupo);
      alumnos.forEach((a) => body.append("alumnos[]", a));

      const j = await fetchJSON(`${CTRL_URL}?action=asignar_grupo`, {
        method: "POST",
        body,
      });
      if (!j.ok) throw new Error(j.msg || "No se pudo asignar");

      Swal.fire({ icon: "success", title: "Listo", text: j.msg });

      if (j.resumen) pintarResumen(j.resumen);

      const idsAsignados = new Set([
        ...(j.resumen?.grupo1?.alumnos || []).map((a) => String(a.id_alumno)),
        ...(j.resumen?.grupo2?.alumnos || []).map((a) => String(a.id_alumno)),
      ]);
      document.querySelectorAll("#tablaAlumnos tbody tr").forEach((tr) => {
        if (idsAsignados.has(tr.dataset.idAlumno)) tr.remove();
      });

      if (!document.querySelector("#tablaAlumnos tbody tr")) {
        tbody.innerHTML =
          '<tr><td colspan="3">No hay más alumnos disponibles.</td></tr>';
        btnAsignar.disabled = true;
      }

      await cargarPreviewCiclo();
    } catch (e) {
      Swal.fire({ icon: "error", title: "Error", text: e.message });
    }
  });

  // Buscador local
  if (buscar) {
    buscar.addEventListener("keyup", function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll("#tablaAlumnos tbody tr").forEach((tr) => {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? "" : "none";
      });
    });
  }

  // ================= Ciclos =================

  // Cargar ciclos
  (async function cargarCiclos() {
    if (!selCiclo) return;
    try {
      const j = await fetchJSON(`${CTRL_URL}?action=lista_ciclos`);
      if (!j.ok) throw new Error(j.msg || "No se pudieron cargar ciclos");
      selCiclo.innerHTML = '<option value="">Selecciona un ciclo</option>';
      (j.data || []).forEach((c) => {
        const o = document.createElement("option");
        o.value = c.id_ciclo;
        o.textContent = `${c.nombre_ciclo} (${c.fecha_inicio} a ${
          c.fecha_fin
        })${Number(c.activo) ? " • ACTIVO" : ""}`;
        selCiclo.appendChild(o);
      });
    } catch (e) {
      selCiclo.innerHTML = '<option value="">Error cargando ciclos</option>';
      console.error(e);
      Swal.fire({ icon: "error", title: "Ups", text: e.message });
    }
  })();

  // Tablero de TODOS los grupos con ciclo (cards)
  async function cargarCiclosAsignados() {
    if (!gridCiclos) return;
    try {
      const j = await fetchJSON(`${CTRL_URL}?action=grupos_ciclo_cards`);
      if (!j.ok) throw new Error(j.msg || "No se pudo cargar el tablero");
      const data = j.data || [];
      if (!data.length) {
        gridCiclos.innerHTML = `<div class="subtle">Sin registros.</div>`;
        return;
      }
      gridCiclos.innerHTML = "";
      data.forEach((g) => {
        const card = document.createElement("div");
        card.className = "mini-card";
        const rows = (g.alumnos || [])
          .map(
            (a, i) =>
              `<tr><td>${i + 1}</td><td>${a.nombre}</td><td>${
                a.matricula || ""
              }</td></tr>`
          )
          .join("");
        card.innerHTML = `
          <h4>${g.titulo}</h4>
          <span class="subtle">${g.ciclo} • ${g.total} alumno(s)</span>
          <table>
            <thead><tr><th>#</th><th>Alumno</th><th>Matrícula</th></tr></thead>
            <tbody>${
              rows ||
              `<tr><td colspan="3" class="subtle">Sin alumnos.</td></tr>`
            }</tbody>
          </table>`;
        gridCiclos.appendChild(card);
      });
    } catch (e) {
      gridCiclos.innerHTML = `<div class="subtle">Error cargando.</div>`;
      console.error(e);
    }
  }
  cargarCiclosAsignados();

  function toggleBtnCiclo() {
    if (!btnAsignarCiclo) return;
    btnAsignarCiclo.disabled = !(selGrupoDestino?.value && selCiclo?.value);
  }
  selGrupoDestino?.addEventListener("change", async () => {
    toggleBtnCiclo();
    await cargarPreviewCiclo();
  });
  selCiclo?.addEventListener("change", async () => {
    toggleBtnCiclo();
    await cargarPreviewCiclo();
  });

  // PREVIEW ciclo
  async function cargarPreviewCiclo() {
    if (!tbodyPreviewCiclo) return;
    const idGrupo = selGrupoDestino?.value;
    const idCiclo = selCiclo ? selCiclo.value : "";
    if (!idGrupo || !idCiclo) {
      tbodyPreviewCiclo.innerHTML = `<tr><td colspan="4" class="subtle">Selecciona grupo y ciclo para ver el preview.</td></tr>`;
      return;
    }
    try {
      tbodyPreviewCiclo.innerHTML = `<tr><td colspan="4">Cargando...</td></tr>`;
      const j = await fetchJSON(
        `${CTRL_URL}?action=preview_ciclo_grupo&id_grupo=${encodeURIComponent(
          idGrupo
        )}&id_ciclo=${encodeURIComponent(idCiclo)}`
      );
      if (!j.ok) throw new Error(j.msg || "No se pudo cargar el preview");
      const rows = j.data || [];
      if (!rows.length) {
        tbodyPreviewCiclo.innerHTML = `<tr><td colspan="4" class="subtle">El grupo no tiene alumnos asignados.</td></tr>`;
        return;
      }
      let i = 0;
      tbodyPreviewCiclo.innerHTML = rows
        .map(
          (a) => `
          <tr>
            <td>${++i}</td>
            <td>${a.nombre}</td>
            <td>${a.matricula || ""}</td>
            <td>${Number(a.tiene_ciclo) ? "Sí" : "No"}</td>
          </tr>`
        )
        .join("");
    } catch (e) {
      tbodyPreviewCiclo.innerHTML = `<tr><td colspan="4">Error cargando preview.</td></tr>`;
      console.error(e);
    }
  }

  // Asignar ciclo (bloqueo + reinscripción)
  btnAsignarCiclo &&
    btnAsignarCiclo.addEventListener("click", async () => {
      const idGrupo = selGrupoDestino?.value;
      const idCiclo = selCiclo ? selCiclo.value : "";
      if (!idGrupo || !idCiclo) return;

      const confirm = await Swal.fire({
        icon: "question",
        title: "Asignar ciclo al grupo",
        text: "Se creará/confirmará el ciclo para todos los alumnos del grupo (sin duplicar).",
        showCancelButton: true,
        confirmButtonText: "Asignar",
        cancelButtonText: "Cancelar",
      });
      if (!confirm.isConfirmed) return;

      const postAsignar = (reinscribir = 0) => {
        const body = new URLSearchParams();
        body.append("id_grupo", idGrupo);
        body.append("id_ciclo", idCiclo);
        body.append("reinscribir", String(reinscribir));
        return fetchJSON(`${CTRL_URL}?action=asignar_ciclo_grupo`, {
          method: "POST",
          body,
        });
      };

      try {
        let j = await postAsignar(0);

        if (!j.ok && j.code === "BLOQUEO_CICLO") {
          const r = await Swal.fire({
            icon: "warning",
            title: "Alumnos con ciclo vigente",
            html: `${j.msg}<br><br>¿Deseas <b>REINSCRIBIR</b> y mover al nuevo ciclo?`,
            showCancelButton: true,
            confirmButtonText: "Reinscribir",
            cancelButtonText: "Cancelar",
          });
          if (!r.isConfirmed) return;
          j = await postAsignar(1);
        }

        if (!j.ok) throw new Error(j.msg || "No se pudo asignar el ciclo");

        Swal.fire({ icon: "success", title: "Listo", text: j.msg });
        await cargarCiclosAsignados();
        await cargarPreviewCiclo();
      } catch (e) {
        Swal.fire({ icon: "error", title: "Error", text: e.message });
      }
    });
});
