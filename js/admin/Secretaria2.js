// ===== Modal NUEVO =====
const modal = document.getElementById("modalNuevo");
const btnNuevo = document.getElementById("btnNuevo");
const cancel = document.getElementById("cancelModal");
const closeX = document.getElementById("closeModal");
const form = document.getElementById("formNuevo");

function cerrarModal() {
  modal.classList.remove("active");
  form.reset();
}

btnNuevo.addEventListener("click", () => modal.classList.add("active"));
cancel.addEventListener("click", cerrarModal);
closeX.addEventListener("click", cerrarModal);

// ===== CREAR SECRETARIA =====
form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const datos = new FormData(form);
  datos.append("action", "create"); // importante para el PHP

  try {
    const resp = await fetch(
      "../../controladores/admin/controller_secretaria.php",
      {
        method: "POST",
        body: datos,
      }
    );

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModal(); // <--- cerrar modal antes de mostrar alerta
      Swal.fire({
        icon: "success",
        title: "Secretaria agregada",
        text: "La secretaria fue agregada correctamente.",
        html: `
          <p>La secretaria se registró correctamente.</p>
          <p><strong>Correo:</strong> ${data.correo}</p>
          <p><strong>Contraseña:</strong> ${data.password}</p>
        `,
        showConfirmButton: true,
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

function cerrarModalEditar() {
  modalEditar.classList.remove("active");
  formEditar.reset();
}

cancelEditar.addEventListener("click", cerrarModalEditar);
closeEditar.addEventListener("click", cerrarModalEditar);

// ===== ABRIR MODAL EDITAR =====
document.querySelectorAll(".btnEditar").forEach((btn) => {
  btn.addEventListener("click", async (e) => {
    e.preventDefault();
    const id = btn.dataset.id;
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

      // Llenar modal con los datos
      document.getElementById("editId").value = data.id_secretaria;
      document.getElementById("editNombre").value = data.nombre;
      document.getElementById("editApellidoP").value = data.apellido_paterno;
      document.getElementById("editApellidoM").value = data.apellido_materno;
      document.getElementById("editCurp").value = data.curp;
      document.getElementById("editRfc").value = data.rfc;
      document.getElementById("editFechaNac").value = data.fecha_nacimiento;
      document.getElementById("editSexo").value = data.sexo;
      document.getElementById("editTelefono").value = data.telefono;
      document.getElementById("editDireccion").value = data.direccion;
      document.getElementById("editDepartamento").value = data.departamento;
      document.getElementById("editFechaIngreso").value = data.fecha_ingreso;
      document.getElementById("editContactoEmergencia").value =
        data.contacto_emergencia;
      document.getElementById("editParentescoEmergencia").value =
        data.parentesco_emergencia;
      document.getElementById("editTelefonoEmergencia").value =
        data.telefono_emergencia;
      document.getElementById("editCorreo").value = data.correo_institucional;
      document.getElementById("editPassword").value = data.password;

      modalEditar.classList.add("active");
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
formEditar.addEventListener("submit", async (e) => {
  e.preventDefault();
  const datos = new FormData(formEditar);
  datos.append("action", "edit"); // importante para PHP

  try {
    const resp = await fetch(
      "../../controladores/admin/controller_secretaria.php",
      {
        method: "POST",
        body: datos,
      }
    );
    const data = await resp.json();

    if (data.status === "success") {
      cerrarModalEditar(); // <--- cerrar modal antes de alerta
      Swal.fire({
        icon: "success",
        title: "Secretaria actualizada",
        text: "La secretaria fue actualizada correctamente.",
        showConfirmButton: false,
        timer: 2000,
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
    const id = btn.dataset.id || fila.dataset.id;
    const nombre = fila.children[1]?.textContent.trim() || "la secretaria";

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
      if (result.isConfirmed) {
        try {
          const datos = new FormData();
          datos.append("action", "delete");
          datos.append("id_secretaria", id);

          const resp = await fetch(
            "../../controladores/admin/controller_secretaria.php",
            {
              method: "POST",
              body: datos,
            }
          );

          if (!resp.ok) throw new Error(`Error HTTP ${resp.status}`);
          const data = await resp.json();

          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Eliminada",
              text: "La secretaria fue eliminada correctamente.",
              showConfirmButton: false,
              timer: 2000,
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
      }
    });
  });
});

// ===== BUSCAR SECRETARIA =====
document
  .getElementById("buscarSecretaria")
  .addEventListener("keyup", function () {
    const filtro = this.value.toLowerCase();
    document.querySelectorAll("#tablaSecretarias tbody tr").forEach((fila) => {
      fila.style.display = fila.innerText.toLowerCase().includes(filtro)
        ? ""
        : "none";
    });
  });

// ===== EXPORTAR SECRETARIAS =====
document.getElementById("btnExportar")?.addEventListener("click", () => {
  Swal.fire({
    title: "Exportar tabla",
    text: "¿En qué formato quieres exportar?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Excel",
    cancelButtonText: "PDF",
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href =
        "../../controladores/admin/exportar_secretaria.php?tipo=excel";
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      window.location.href =
        "../../controladores/admin/exportar_secretaria.php?tipo=pdf";
    }
  });
});
