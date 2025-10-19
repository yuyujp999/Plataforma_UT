document.addEventListener("DOMContentLoaded", () => {
  const CTRL_URL =
    window.CTRL_ASIG_URL ||
    "../../controladores/admin/controlador_asignaciones_alumnos.php";

  const selGrupo = document.getElementById("selectGrupo");
  const btnAsignar = document.getElementById("btnAsignar");
  const tbody = document.querySelector("#tablaAlumnos tbody");
  const resumen = document.getElementById("resumenAsignaciones");
  const buscar = document.getElementById("buscarAsignacion");

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

  // Dibuja las mini tarjetas de resumen (persisten hasta que cambies de grupo)
  function pintarResumen(res) {
    if (!resumen || !res) return;
    const { grupo1, grupo2, pendientes } = res;
    resumen.innerHTML = "";

    const makeCard = (titulo, alumnos) => {
      const d = document.createElement("div");
      d.className = "mini-card";
      let rows = "";
      (alumnos || []).forEach((a, i) => {
        rows += `<tr><td>${i + 1}</td><td>${a.nombre}</td><td>${
          a.matricula || ""
        }</td></tr>`;
      });
      d.innerHTML = `<h4>${titulo}</h4>
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
  }

  // Cargar grupos
  (async function cargarGrupos() {
    try {
      const j = await fetchJSON(`${CTRL_URL}?action=lista_grupos`);
      if (!j.ok) throw new Error(j.msg || "No se pudieron cargar grupos");
      selGrupo.innerHTML = '<option value="">Seleccionar grupo</option>';
      (j.data || []).forEach((g) => {
        const o = document.createElement("option");
        o.value = g.id_grupo;
        o.textContent = g.nombre_grupo;
        selGrupo.appendChild(o);
      });
    } catch (e) {
      selGrupo.innerHTML = '<option value="">Error cargando grupos</option>';
      console.error(e);
      Swal.fire({ icon: "error", title: "Ups", text: e.message });
    }
  })();

  // Cambiar grupo -> disponibles + resumen (persistente)
  selGrupo.addEventListener("change", async function () {
    const idGrupo = this.value;
    btnAsignar.disabled = true;
    tbody.innerHTML = '<tr><td colspan="4">Cargando...</td></tr>';

    if (!idGrupo) {
      tbody.innerHTML =
        '<tr><td colspan="4">Selecciona un grupo para listar alumnos.</td></tr>';
      resumen && (resumen.innerHTML = "");
      return;
    }

    try {
      const j = await fetchJSON(
        `${CTRL_URL}?action=alumnos_por_grupo&id_grupo=${encodeURIComponent(
          idGrupo
        )}`
      );
      if (!j.ok) throw new Error(j.msg || "No se pudieron cargar alumnos");

      // pintar resumen persistente
      pintarResumen(j.resumen);

      // pintar disponibles (ya excluye los que están asignados a cualquier grupo)
      const alumnos = j.data || [];
      if (alumnos.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="4">No hay alumnos disponibles (ya asignados o sin registros).</td></tr>';
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
          <td>${a.matricula || ""}</td>
          <td><button class="btn btn-outline btn-sm" data-action="ver"><i class="fas fa-eye"></i> Ver</button></td>
        `;
        tbody.appendChild(tr);
      });
      btnAsignar.disabled = false;
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="4">Error cargando alumnos.</td></tr>';
      console.error(e);
      Swal.fire({ icon: "error", title: "Ups", text: e.message });
    }
  });

  // Asignar (cupo 30 + resto a Grupo 2). Luego actualizo resumen y borro filas asignadas.
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

      // 1) Actualizar resumen sumando lo recién asignado
      if (j.resumen) {
        pintarResumen(j.resumen); // si deseas MERGE con lo previo, puedes primero pedir alumnos_por_grupo de nuevo y repintar
      }

      // 2) Remover de la tabla a los alumnos asignados (los del grupo1 + grupo2 recibidos)
      const idsAsignados = new Set([
        ...(j.resumen?.grupo1?.alumnos || []).map((a) => String(a.id_alumno)),
        ...(j.resumen?.grupo2?.alumnos || []).map((a) => String(a.id_alumno)),
      ]);
      document.querySelectorAll("#tablaAlumnos tbody tr").forEach((tr) => {
        if (idsAsignados.has(tr.dataset.idAlumno)) tr.remove();
      });

      // Si quedó vacía, mostrar mensaje
      if (!document.querySelector("#tablaAlumnos tbody tr")) {
        tbody.innerHTML =
          '<tr><td colspan="4">No hay más alumnos disponibles.</td></tr>';
        btnAsignar.disabled = true;
      }
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
});
