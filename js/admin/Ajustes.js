document.addEventListener("DOMContentLoaded", () => {
  const btnAjuste = document.querySelector(".btn-ajuste");
  const modal = document.getElementById("modalUsuario");
  const cerrarModal = document.getElementById("cerrarModal");
  const cerrarModalBtn = document.getElementById("cerrarModalBtn"); // NUEVO
  const form = document.getElementById("formAjustes");

  if (!btnAjuste || !modal || !cerrarModal || !form) return;

  // === Abrir modal y cargar datos ===
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
            document.getElementById("nombre").value = nombre;
            document.getElementById("apellido_paterno").value =
              apellido_paterno;
            document.getElementById("apellido_materno").value =
              apellido_materno;
          } else {
            console.error("Error en getDatos:", data.message || text);
            Swal.fire({
              icon: "error",
              title: "Error",
              text: data.message || "No se pudieron obtener los datos.",
            });
          }
        } catch (err) {
          console.error("Respuesta no JSON:", text);
          Swal.fire({
            icon: "error",
            title: "Error en servidor",
            text: "Respuesta no válida del backend (ver consola)",
          });
        }
      })
      .catch((err) => console.error("Error en fetch getDatos:", err));
  });

  // === Cerrar modal ===
  cerrarModal.addEventListener("click", () => (modal.style.display = "none"));
  if (cerrarModalBtn) {
    cerrarModalBtn.addEventListener(
      "click",
      () => (modal.style.display = "none")
    );
  }

  // === Enviar formulario (actualizar datos) ===
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
            Swal.fire({
              icon: "success",
              title: "Datos actualizados",
              showConfirmButton: false,
              timer: 1500,
            });
            modal.style.display = "none";
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: data.message || "No se pudo actualizar",
            });
          }
        } catch (err) {
          console.error("Respuesta no JSON:", text);
          Swal.fire({
            icon: "error",
            title: "Error de servidor",
            text: "Ver consola (posible error PHP o ruta incorrecta)",
          });
        }
      })
      .catch((err) => console.error("Error en fetch updateUsuario:", err));
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const btnPassword = document.querySelector(".btn-contraseña");
  const modalPassword = document.getElementById("modalPassword");
  const cerrarPassword = document.getElementById("cerrarPassword");
  const cancelPassword = document.getElementById("cancelPassword");
  const formPassword = document.getElementById("formPassword");

  if (!btnPassword || !modalPassword || !cerrarPassword || !formPassword)
    return;

  // Abrir modal
  btnPassword.addEventListener("click", () => {
    modalPassword.style.display = "flex";
  });

  // Cerrar modal
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
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "La nueva contraseña y la confirmación no coinciden.",
      });
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
            Swal.fire({
              icon: "success",
              title: "Contraseña actualizada",
              text: "La contraseña fue actualizada correctamente",
              showConfirmButton: false,
              timer: 1500,
            });
            modalPassword.style.display = "none";
            formPassword.reset();
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: data.message || "No se pudo actualizar la contraseña.",
            });
          }
        } catch (err) {
          console.error("Respuesta no JSON:", text);
          Swal.fire({
            icon: "error",
            title: "Error de servidor",
            text: "Ver consola (posible error PHP o ruta incorrecta)",
          });
        }
      })
      .catch((err) => console.error("Error en fetch cambiarPassword:", err));
  });
});
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
      if (result.isConfirmed) {
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
                Swal.fire({
                  icon: "error",
                  title: "Error",
                  text: data.message || "No se pudo eliminar la cuenta.",
                });
              }
            } catch (err) {
              console.error("Respuesta no JSON:", text);
              Swal.fire({
                icon: "error",
                title: "Error de servidor",
                text: "Ver consola (posible error PHP o ruta incorrecta)",
              });
            }
          })
          .catch((err) => console.error("Error en fetch eliminarCuenta:", err));
      }
    });
  });
});
document.querySelectorAll(".toggle-password").forEach((icon) => {
  icon.addEventListener("click", () => {
    const input = document.querySelector(icon.getAttribute("toggle"));
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
