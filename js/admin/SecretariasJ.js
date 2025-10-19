// ===== Util: sólo números en inputs de teléfono =====
function enforceNumericInput(el) {
  if (!el) return;
  el.setAttribute("inputmode", "numeric");
  el.setAttribute("pattern", "\\d*");
  el.setAttribute("maxlength", "15");
  el.addEventListener("input", () => {
    el.value = el.value.replace(/\D/g, "");
  });
}

// ===== Modal NUEVO =====
const modal = document.getElementById("modalNuevo");
const btnNuevo = document.getElementById("btnNuevo");
const cancel = document.getElementById("cancelModal");
const closeX = document.getElementById("closeModal");
const form = document.getElementById("formNuevo");

function abrirModalNuevo() {
  modal.classList.add("active");
}
function cerrarModalNuevo() {
  modal.classList.remove("active");
  form.reset();
}

btnNuevo?.addEventListener("click", abrirModalNuevo);
cancel?.addEventListener("click", cerrarModalNuevo);
closeX?.addEventListener("click", cerrarModalNuevo);

// Forzar numérico en teléfonos (modal nuevo)
enforceNumericInput(document.getElementById("telefono"));
enforceNumericInput(document.getElementById("telefono_emergencia"));

// ===== CREAR SECRETARIA =====
form?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const datos = new FormData(form);
  datos.append("action", "create"); // importante para el PHP

  try {
    const resp = await fetch(
      "../../controladores/admin/controller_secretaria.php",
      { method: "POST", body: datos }
    );

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModalNuevo(); // cerrar modal antes de alerta
      Swal.fire({
        icon: "success",
        title: "Secretaria agregada",
        html: `
          <p>La secretaria fue registrada correctamente.</p>
          <p><strong>Correo:</strong> ${data.correo}</p>
          <p><strong>Contraseña:</strong> ${data.password_plano}</p>
          <small>⚠️ Guarda la contraseña, sólo se muestra una vez.</small>
        `,
        confirmButtonText: "Aceptar",
      }).then(() => location.reload());
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message || "No se pudo agregar la secretaria.",
      });
    }
  } catch (err) {
    Swal.fire({
      icon: "error",
      title: "Error de red",
      text: err.message || "No se pudo conectar con el servidor.",
    });
  }
});

// ===== Modal EDITAR =====
const modalEditar = document.getElementById("modalEditar");
const formEditar = document.getElementById("formEditar");
const cancelEditar = document.getElementById("cancelModalEditar");
const closeEditar = document.getElementById("closeModalEditar");

function abrirModalEditar() {
  modalEditar.classList.add("active");
}
function cerrarModalEditar() {
  modalEditar.classList.remove("active");
  formEditar.reset();
}

cancelEditar?.addEventListener("click", cerrarModalEditar);
closeEditar?.addEventListener("click", cerrarModalEditar);

// Forzar numérico en teléfonos (modal editar)
enforceNumericInput(document.getElementById("editTelefono"));
enforceNumericInput(document.getElementById("editTelefonoEmergencia"));

// ===== ABRIR MODAL EDITAR =====
document.querySelectorAll(".btnEditar").forEach((btn) => {
  btn.addEventListener("click", async (e) => {
    e.preventDefault();
    const id = btn.dataset.id || btn.getAttribute("data-id");
    if (!id) {
      Swal.fire({ icon: "error", title: "Error", text: "ID no válido." });
      return;
    }
    try {
      const resp = await fetch(
        "../../controladores/admin/controller_secretaria.php",
        {
          method: "POST",
          body: new URLSearchParams({ action: "get", id_secretaria: id }),
        }
      );
      const data = await resp.json();
      if (data.status !== "success") throw new Error(data.message);

      // Llenar modal con los datos (el backend devuelve campos en la raíz)
      document.getElementById("editId").value = data.id_secretaria;
      document.getElementById("editNombre").value = data.nombre || "";
      document.getElementById("editApellidoP").value =
        data.apellido_paterno || "";
      document.getElementById("editApellidoM").value =
        data.apellido_materno || "";
      document.getElementById("editCurp").value = data.curp || "";
      document.getElementById("editRfc").value = data.rfc || "";
      document.getElementById("editFechaNac").value =
        data.fecha_nacimiento || "";
      document.getElementById("editSexo").value = data.sexo || "Masculino";
      document.getElementById("editTelefono").value = data.telefono || "";
      document.getElementById("editDireccion").value = data.direccion || "";
      document.getElementById("editDepartamento").value =
        data.departamento || "";

      // Campos de emergencia (¡aquí estaba el faltante!)
      document.getElementById("editContactoEmergencia").value =
        data.contacto_emergencia || "";
      document.getElementById("editParentescoEmergencia").value =
        data.parentesco_emergencia || "";
      document.getElementById("editTelefonoEmergencia").value =
        data.telefono_emergencia || "";

      // Solo lectura
      document.getElementById("editCorreo").value =
        data.correo_institucional || "";
      // (No se rellena password, no se expone)

      abrirModalEditar();
    } catch (err) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: err.message || "No se pudo cargar la información.",
      });
    }
  });
});

// ===== ACTUALIZAR SECRETARIA =====
formEditar?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const datos = new FormData(formEditar);
  datos.append("action", "edit"); // importante para PHP

  try {
    const resp = await fetch(
      "../../controladores/admin/controller_secretaria.php",
      { method: "POST", body: datos }
    );
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModalEditar(); // cerrar antes de alerta
      Swal.fire({
        icon: "success",
        title: "Secretaria actualizada",
        text: "La secretaria fue actualizada correctamente.",
        showConfirmButton: false,
        timer: 1800,
      }).then(() => location.reload());
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message || "No se pudo actualizar la secretaria.",
      });
    }
  } catch (err) {
    Swal.fire({
      icon: "error",
      title: "Error de red",
      text: err.message || "No se pudo conectar con el servidor.",
    });
  }
});

// ===== ELIMINAR SECRETARIA =====
document.querySelectorAll(".btnEliminar").forEach((btn) => {
  btn.addEventListener("click", async (e) => {
    e.preventDefault();

    const fila = e.target.closest("tr");
    const id = btn.dataset.id || fila?.dataset.id;
    const nombre = fila?.children[1]?.textContent.trim() || "la secretaria";

    if (!id) {
      Swal.fire({ icon: "error", title: "Error", text: "ID no válido." });
      return;
    }

    Swal.fire({
      title: `¿Eliminar a "${nombre}"?`,
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then(async (result) => {
      if (!result.isConfirmed) return;

      try {
        const datos = new FormData();
        datos.append("action", "delete");
        datos.append("id_secretaria", id);

        const resp = await fetch(
          "../../controladores/admin/controller_secretaria.php",
          { method: "POST", body: datos }
        );

        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        const data = await resp.json();

        if (data.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Eliminada",
            text: "La secretaria fue eliminada correctamente.",
            showConfirmButton: false,
            timer: 1800,
          }).then(() => location.reload());
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo eliminar la secretaria.",
          });
        }
      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Error de red",
          text: error.message || "No se pudo conectar con el servidor.",
        });
      }
    });
  });
});

// ===== EXPORTAR SECRETARIAS (solo PDF) =====
document.getElementById("btnExportar")?.addEventListener("click", function () {
  Swal.fire({
    title: "Generando PDF...",
    text: "Por favor espera mientras preparamos la descarga.",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading(); // spinner de carga
    },
  });

  // Pequeño retraso para una UX suave (opcional)
  setTimeout(() => {
    // Redirige al controlador de descarga de SECRETARIAS
    window.location.href =
      "../../controladores/admin/exportar_secretaria.php?tipo=pdf";

    // Cierra el spinner y muestra confirmación
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
