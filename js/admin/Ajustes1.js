/* ========= Helper: cerrar modal + alertar + recargar ========= */
function alertAndReload(
  {
    icon = "success",
    title = "",
    text = "",
    timer = 1500,
    showConfirmButton = false,
  },
  modalEl = null
) {
  if (modalEl) modalEl.style.display = "none";
  return Swal.fire({
    icon,
    title,
    text,
    timer,
    showConfirmButton,
  }).then(() => {
    // Cuando se cierre la alerta (por botón o por timer), recarga
    location.reload();
  });
}

/* ========= Ajustes de usuario ========= */
document.addEventListener("DOMContentLoaded", () => {
  const btnAjuste = document.querySelector(".btn-ajuste");
  const modal = document.getElementById("modalUsuario");
  const cerrarModal = document.getElementById("cerrarModal");
  const cerrarModalBtn = document.getElementById("cerrarModalBtn");
  const form = document.getElementById("formAjustes");

  if (btnAjuste && modal && cerrarModal && form) {
    // Abrir modal y cargar datos
    btnAjuste.addEventListener("click", () => {
      modal.style.display = "flex";

      fetch("../../controladores/admin/controller_ajustes.php", {
        method: "POST",
        body: new URLSearchParams({ accion: "getDatos" }),
      })
        .then((res) => res.text())
        .then((text) => {
          try {
            const data = JSON.parse(text);
            if (data.status === "success" && data.data) {
              const { nombre, apellido_paterno, apellido_materno } = data.data;
              document.getElementById("nombre").value = nombre || "";
              document.getElementById("apellido_paterno").value =
                apellido_paterno || "";
              document.getElementById("apellido_materno").value =
                apellido_materno || "";
            } else {
              alertAndReload(
                {
                  icon: "error",
                  title: "Error",
                  text: data.message || "No se pudieron obtener los datos.",
                },
                modal
              );
            }
          } catch (err) {
            console.error("Respuesta no JSON:", text);
            alertAndReload(
              {
                icon: "error",
                title: "Error en servidor",
                text: "Respuesta no válida del backend (ver consola)",
              },
              modal
            );
          }
        })
        .catch((err) => {
          console.error("Error en fetch getDatos:", err);
          alertAndReload(
            {
              icon: "error",
              title: "Error",
              text: "No se pudo conectar con el servidor.",
            },
            modal
          );
        });
    });

    // Cerrar modal (X y botón)
    cerrarModal.addEventListener("click", () => (modal.style.display = "none"));
    if (cerrarModalBtn)
      cerrarModalBtn.addEventListener(
        "click",
        () => (modal.style.display = "none")
      );

    // Enviar formulario (actualizar datos)
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      const formData = new FormData(form);
      formData.append("accion", "updateUsuario");

      fetch("../../controladores/admin/controller_ajustes.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.text())
        .then((text) => {
          try {
            const data = JSON.parse(text);
            if (data.status === "success") {
              alertAndReload(
                {
                  icon: "success",
                  title: "Datos actualizados",
                  timer: 1500,
                  showConfirmButton: false,
                },
                modal
              );
            } else {
              alertAndReload(
                {
                  icon: "error",
                  title: "Error",
                  text: data.message || "No se pudo actualizar",
                },
                modal
              );
            }
          } catch (err) {
            console.error("Respuesta no JSON:", text);
            alertAndReload(
              {
                icon: "error",
                title: "Error de servidor",
                text: "Ver consola (posible error PHP o ruta incorrecta)",
              },
              modal
            );
          }
        })
        .catch((err) => {
          console.error("Error en fetch updateUsuario:", err);
          alertAndReload(
            {
              icon: "error",
              title: "Error",
              text: "No se pudo conectar con el servidor.",
            },
            modal
          );
        });
    });
  }
});

/* ========= Cambio de contraseña ========= */
document.addEventListener("DOMContentLoaded", () => {
  const btnPassword = document.querySelector(".btn-contraseña");
  const modalPassword = document.getElementById("modalPassword");
  const cerrarPassword = document.getElementById("cerrarPassword");
  const cancelPassword = document.getElementById("cancelPassword");
  const formPassword = document.getElementById("formPassword");

  if (!(btnPassword && modalPassword && cerrarPassword && formPassword)) return;

  // Abrir modal
  btnPassword.addEventListener("click", () => {
    modalPassword.style.display = "flex";
  });

  // Cerrar modal (X y cancelar)
  [cerrarPassword, cancelPassword].forEach((btn) => {
    if (btn)
      btn.addEventListener("click", () => {
        modalPassword.style.display = "none";
        formPassword.reset();
      });
  });

  // Enviar formulario
  formPassword.addEventListener("submit", (e) => {
    e.preventDefault();

    const actual = document.getElementById("actual").value.trim();
    const nueva = document.getElementById("nueva").value.trim();
    const confirmar = document.getElementById("confirmar").value.trim();

    if (nueva !== confirmar) {
      alertAndReload(
        {
          icon: "error",
          title: "Error",
          text: "La nueva contraseña y la confirmación no coinciden.",
        },
        modalPassword
      );
      return;
    }

    const formData = new FormData();
    formData.append("accion", "cambiarPassword");
    formData.append("actual", actual);
    formData.append("nueva", nueva);

    fetch("../../controladores/admin/controller_ajustes.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.text())
      .then((text) => {
        try {
          const data = JSON.parse(text);
          if (data.status === "success") {
            alertAndReload(
              {
                icon: "success",
                title: "Contraseña actualizada",
                text: "La contraseña fue actualizada correctamente",
                timer: 1500,
                showConfirmButton: false,
              },
              modalPassword
            );
            formPassword.reset(); // por si el reload no ocurre (navegador antiguo)
          } else {
            alertAndReload(
              {
                icon: "error",
                title: "Error",
                text: data.message || "No se pudo actualizar la contraseña.",
              },
              modalPassword
            );
          }
        } catch (err) {
          console.error("Respuesta no JSON:", text);
          alertAndReload(
            {
              icon: "error",
              title: "Error de servidor",
              text: "Ver consola (posible error PHP o ruta incorrecta)",
            },
            modalPassword
          );
        }
      })
      .catch((err) => {
        console.error("Error en fetch cambiarPassword:", err);
        alertAndReload(
          {
            icon: "error",
            title: "Error",
            text: "No se pudo conectar con el servidor.",
          },
          modalPassword
        );
      });
  });
});

/* ========= Eliminar cuenta ========= */
document.addEventListener("DOMContentLoaded", () => {
  const btnEliminar = document.querySelector(".btn-eliminar");
  if (!btnEliminar) return;

  btnEliminar.addEventListener("click", () => {
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sí, eliminar cuenta",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (!result.isConfirmed) return;

      const formData = new FormData();
      formData.append("accion", "eliminarCuenta");

      fetch("../../controladores/admin/controller_ajustes.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.text())
        .then((text) => {
          try {
            const data = JSON.parse(text);
            if (data.status === "success") {
              // En eliminar cuenta mantenemos la redirección
              Swal.fire({
                icon: "success",
                title: "Cuenta eliminada",
                text: "Tu cuenta fue eliminada correctamente",
                showConfirmButton: false,
                timer: 1500,
              }).then(() => {
                window.location.href =
                  "http://localhost/Plataforma_UT/inicio.php";
              });
            } else {
              // Si falla: cerrar cualquier modal que tuvieras y luego recargar
              alertAndReload(
                {
                  icon: "error",
                  title: "Error",
                  text: data.message || "No se pudo eliminar la cuenta.",
                },
                document.getElementById("modalUsuario") // por si estaba abierto
              );
            }
          } catch (err) {
            console.error("Respuesta no JSON:", text);
            alertAndReload(
              {
                icon: "error",
                title: "Error de servidor",
                text: "Ver consola (posible error PHP o ruta incorrecta)",
              },
              document.getElementById("modalUsuario")
            );
          }
        })
        .catch((err) => {
          console.error("Error en fetch eliminarCuenta:", err);
          alertAndReload(
            {
              icon: "error",
              title: "Error",
              text: "No se pudo conectar con el servidor.",
            },
            document.getElementById("modalUsuario")
          );
        });
    });
  });
});

/* ========= Toggle mostrar/ocultar contraseñas ========= */
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".toggle-password").forEach((icon) => {
    icon.addEventListener("click", () => {
      const input = document.querySelector(icon.getAttribute("toggle"));
      if (!input) return;
      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    });
  });
});
