// 🌙 UT PANEL - MODO OSCURO GLOBAL
document.addEventListener("DOMContentLoaded", () => {
  const toggleDark = document.getElementById("toggleDark");

  // Funciones de activación
  const activarOscuro = () => {
    document.body.classList.add("dark-mode");
    localStorage.setItem("modo", "oscuro");
    if (toggleDark) toggleDark.checked = true;
  };

  const activarClaro = () => {
    document.body.classList.remove("dark-mode");
    localStorage.setItem("modo", "claro");
    if (toggleDark) toggleDark.checked = false;
  };

  // Al cargar: lee el modo guardado
  if (localStorage.getItem("modo") === "oscuro") activarOscuro();

  // Si existe el switch (solo en ajustes), sincronízalo
  if (toggleDark) {
    toggleDark.addEventListener("change", () => {
      toggleDark.checked ? activarOscuro() : activarClaro();
    });
  }
});

// 🎨 Botón estético de modo oscuro
document.addEventListener("DOMContentLoaded", () => {
  const themeBtn = document.getElementById("themeToggleBtn");
  if (!themeBtn) return;

  // Configurar estado inicial
  if (localStorage.getItem("modo") === "oscuro") {
    themeBtn.classList.add("active");
    themeBtn.querySelector("i").classList.replace("fa-moon", "fa-sun");
    themeBtn.querySelector(".toggle-text").textContent = "Desactivar";
  }

  // Evento al click
  themeBtn.addEventListener("click", () => {
    const isActive = document.body.classList.toggle("dark-mode");
    if (isActive) {
      localStorage.setItem("modo", "oscuro");
      themeBtn.classList.add("active");
      themeBtn.querySelector("i").classList.replace("fa-moon", "fa-sun");
      themeBtn.querySelector(".toggle-text").textContent = "Desactivar";
    } else {
      localStorage.setItem("modo", "claro");
      themeBtn.classList.remove("active");
      themeBtn.querySelector("i").classList.replace("fa-sun", "fa-moon");
      themeBtn.querySelector(".toggle-text").textContent = "Activar";
    }
  });
});
// 🌙 Switch deslizante de modo oscuro
document.addEventListener("DOMContentLoaded", () => {
  const themeSlider = document.getElementById("themeSlider");
  if (!themeSlider) return;

  // Estado inicial
  if (localStorage.getItem("modo") === "oscuro") {
    themeSlider.classList.add("active");
    document.body.classList.add("dark-mode");
  }

  // Click para alternar
  themeSlider.addEventListener("click", () => {
    themeSlider.classList.toggle("active");
    const isDark = themeSlider.classList.contains("active");

    if (isDark) {
      document.body.classList.add("dark-mode");
      localStorage.setItem("modo", "oscuro");
    } else {
      document.body.classList.remove("dark-mode");
      localStorage.setItem("modo", "claro");
    }
  });
});
