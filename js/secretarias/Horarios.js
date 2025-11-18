document.addEventListener("DOMContentLoaded", () => {
  const tabla = document.getElementById("tablaHorarios");
  if (!tabla) return;

  console.log("Horarios.js cargado");

  const tbody = tabla.querySelector("tbody");
  const pagination = document.getElementById("paginationHorarios");
  const searchInput = document.getElementById("buscarHorario");

  const ROWS_PER_PAGE = 5;
  let currentPage = 1;
  const allRows = Array.from(tbody.querySelectorAll("tr"));

  const HORARIOS_URL =
    "/Plataforma_UT/controladores/secretarias/controller_horarios.php";

  // Mapeo de bloques a horas (ajusta si tus horas son otras)
  const BLOQUE_HORAS = {
    1: { inicio: "07:00", fin: "07:50" },
    2: { inicio: "07:50", fin: "08:40" },
    3: { inicio: "08:40", fin: "09:30" },
    4: { inicio: "09:30", fin: "10:20" },
    5: { inicio: "10:30", fin: "11:20" },
    6: { inicio: "11:20", fin: "12:10" },
    7: { inicio: "12:10", fin: "13:00" },
    8: { inicio: "13:00", fin: "13:50" },
  };

  const getFilteredRows = () => {
    const q = (searchInput?.value || "").trim().toLowerCase();
    if (!q) return allRows;
    return allRows.filter((tr) => tr.innerText.toLowerCase().includes(q));
  };

  const paginate = (rows, page, perPage) => {
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (page > totalPages) page = totalPages;
    if (page < 1) page = 1;

    allRows.forEach((tr) => {
      tr.style.display = "none";
    });

    const start = (page - 1) * perPage;
    const end = start + perPage;
    rows.slice(start, end).forEach((tr) => {
      tr.style.display = "";
    });

    renderPagination(totalPages, page);
    currentPage = page;
  };

  const renderPagination = (totalPages, page) => {
    if (!pagination) return;
    pagination.innerHTML = "";

    const mkBtn = (num, label = null, disabled = false, active = false) => {
      const b = document.createElement("button");
      b.className = "pagination-btn";
      b.textContent = label ?? num;
      if (active) b.classList.add("active");
      b.disabled = disabled;
      b.addEventListener("click", () => goToPage(num));
      return b;
    };

    pagination.appendChild(mkBtn(page - 1, "«", page === 1));

    const windowSize = 1;
    const addDots = () => {
      const s = document.createElement("span");
      s.textContent = "…";
      s.style.padding = "6px";
      s.style.color = "#999";
      pagination.appendChild(s);
    };

    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || Math.abs(i - page) <= windowSize) {
        pagination.appendChild(mkBtn(i, null, false, i === page));
      } else if (
        (i === 2 && page > windowSize + 2) ||
        (i === totalPages - 1 && page < totalPages - windowSize - 1)
      ) {
        addDots();
      }
    }

    pagination.appendChild(mkBtn(page + 1, "»", page === totalPages));
  };

  const goToPage = (p) => paginate(getFilteredRows(), p, ROWS_PER_PAGE);

  searchInput?.addEventListener("keyup", () => {
    paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
  });

  paginate(getFilteredRows(), 1, ROWS_PER_PAGE);

  const permisos = window.PERMISOS || {};

  // ===== MODAL NUEVO =====
  const btnNuevo = document.getElementById("btnNuevoHorario");
  const modalNuevo = document.getElementById("modalNuevoHorario");
  const closeModalNuevo = document.getElementById("closeModalNuevoHorario");
  const cancelModalNuevo = document.getElementById("cancelModalNuevoHorario");
  const formNuevo = document.getElementById("formNuevoHorario");

  const nuevoBloque = document.getElementById("nuevoBloque");
  const nuevoHoraInicio = document.getElementById("nuevoHoraInicio");
  const nuevoHoraFin = document.getElementById("nuevoHoraFin");

  const openModalNuevo = () => {
    if (!modalNuevo) return;
    modalNuevo.style.display = "flex";
    modalNuevo.classList.add("active");
  };

  const closeModalNuevoFn = () => {
    if (!modalNuevo) return;
    modalNuevo.style.display = "none";
    modalNuevo.classList.remove("active");
  };

  btnNuevo?.addEventListener("click", openModalNuevo);
  closeModalNuevo?.addEventListener("click", closeModalNuevoFn);
  cancelModalNuevo?.addEventListener("click", closeModalNuevoFn);

  // Autollenar horas según bloque (nuevo)
  nuevoBloque?.addEventListener("change", () => {
    const b = parseInt(nuevoBloque.value, 10);
    const cfg = BLOQUE_HORAS[b];
    if (cfg) {
      nuevoHoraInicio.value = cfg.inicio;
      nuevoHoraFin.value = cfg.fin;
    }
  });

  formNuevo?.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!permisos.crear) return;

    const formData = new FormData(formNuevo);
    formData.append("accion", "crear");

    try {
      const resp = await fetch(HORARIOS_URL, {
        method: "POST",
        body: formData,
      });
      const data = await resp.json();

      if (data.ok) {
        // 👉 cerrar modal y limpiar formulario
        closeModalNuevoFn();
        formNuevo.reset();

        await Swal.fire("Éxito", data.msg || "Horario creado", "success");
        location.reload();
      } else {
        Swal.fire("Error", data.msg || "No se pudo crear el horario", "error");
      }
    } catch (err) {
      console.error(err);
      Swal.fire("Error", "Error en la petición", "error");
    }
  });

  // ===== MODAL EDITAR =====
  const modalEditar = document.getElementById("modalEditarHorario");
  const closeModalEditar = document.getElementById("closeModalEditarHorario");
  const cancelModalEditar = document.getElementById("cancelModalEditarHorario");
  const formEditar = document.getElementById("formEditarHorario");

  const editIdHorario = document.getElementById("editIdHorario");
  const editCombo = document.getElementById("editCombo");
  const editIdAula = document.getElementById("editIdAula");
  const editDia = document.getElementById("editDia");
  const editBloque = document.getElementById("editBloque");
  const editHoraInicio = document.getElementById("editHoraInicio");
  const editHoraFin = document.getElementById("editHoraFin");

  const openModalEditar = () => {
    if (!modalEditar) return;
    modalEditar.style.display = "flex";
    modalEditar.classList.add("active");
  };

  const closeModalEditarFn = () => {
    if (!modalEditar) return;
    modalEditar.style.display = "none";
    modalEditar.classList.remove("active");
  };

  closeModalEditar?.addEventListener("click", closeModalEditarFn);
  cancelModalEditar?.addEventListener("click", closeModalEditarFn);

  // Autollenar horas según bloque (editar)
  editBloque?.addEventListener("change", () => {
    const b = parseInt(editBloque.value, 10);
    const cfg = BLOQUE_HORAS[b];
    if (cfg) {
      editHoraInicio.value = cfg.inicio;
      editHoraFin.value = cfg.fin;
    }
  });

  tbody.addEventListener("click", (e) => {
    const target = e.target;

    // BOTÓN EDITAR
    if (target.closest(".btn-editar-horario")) {
      if (!permisos.editar) return;

      const tr = target.closest("tr");
      const id = tr.dataset.id;
      const combo = tr.dataset.combo;
      const idAula = tr.dataset.aula;
      const dia = tr.dataset.dia;
      const bloque = tr.dataset.bloque;
      const inicio = tr.dataset.inicio;
      const fin = tr.dataset.fin;

      editIdHorario.value = id;
      editCombo.value = combo;
      editIdAula.value = idAula;
      editDia.value = dia;
      editBloque.value = bloque;
      editHoraInicio.value = inicio;
      editHoraFin.value = fin;

      openModalEditar();
    }

    // BOTÓN ELIMINAR
    if (target.closest(".btn-eliminar-horario")) {
      if (!permisos.eliminar) return;

      const tr = target.closest("tr");
      const id = tr.dataset.id;

      Swal.fire({
        title: "¿Eliminar horario?",
        text: "Esta acción no se puede deshacer",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
      }).then(async (result) => {
        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append("accion", "eliminar");
        formData.append("id_horario", id);

        try {
          const resp = await fetch(HORARIOS_URL, {
            method: "POST",
            body: formData,
          });
          const data = await resp.json();

          if (data.ok) {
            await Swal.fire(
              "Eliminado",
              data.msg || "Horario eliminado",
              "success"
            );
            location.reload();
          } else {
            Swal.fire(
              "Error",
              data.msg || "No se pudo eliminar el horario",
              "error"
            );
          }
        } catch (err) {
          console.error(err);
          Swal.fire("Error", "Error en la petición", "error");
        }
      });
    }
  });

  formEditar?.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!permisos.editar) return;

    const formData = new FormData(formEditar);
    formData.append("accion", "actualizar");

    try {
      const resp = await fetch(HORARIOS_URL, {
        method: "POST",
        body: formData,
      });
      const data = await resp.json();

      if (data.ok) {
        // 👉 cerrar modal antes de la alerta
        closeModalEditarFn();

        await Swal.fire("Éxito", data.msg || "Horario actualizado", "success");
        location.reload();
      } else {
        Swal.fire(
          "Error",
          data.msg || "No se pudo actualizar el horario",
          "error"
        );
      }
    } catch (err) {
      console.error(err);
      Swal.fire("Error", "Error en la petición", "error");
    }
  });
});
