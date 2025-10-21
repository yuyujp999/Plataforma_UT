document.addEventListener("DOMContentLoaded", function () {
  const menuConfig = {
    alumno: [
      {
        icon: "fas fa-chart-pie",
        text: "Dashboard",
        path: "/alumno/dashboard.php",
      },
      { icon: "fas fa-users", text: "Saiiut UTSC", path: "/alumno/saiiut.php" },
      { icon: "fas fa-box", text: "Virtual UTSC", path: "/alumno/virtual.php" },
      {
        icon: "fas fa-graduation-cap",
        text: "Calificaciones",
        path: "/alumno/calificaciones.php",
      },
      {
        icon: "fas fa-book",
        text: "Material de Estudio",
        path: "/alumno/material.php",
      },
      {
        icon: "fas fa-calendar-alt",
        text: "Calendario Académico",
        path: "/alumno/calendario.php",
      },
      {
        icon: "fas fa-bell",
        text: "Notificaciones",
        path: "/alumno/notificaciones.php",
      },
      { icon: "fas fa-cog", text: "Ajustes", path: "/alumno/ajustes.php" },
    ],

    docente: [
      {
        icon: "fas fa-chart-pie",
        text: "Dashboard",
        path: "/Plataforma_UT/vistas/docentes/dashboardDocente.php",
      },
      {
        icon: "fas fa-box",
        text: "Virtual UTSC",
        path: "/vistas/docentes/tareas.php",
      },
      {
        icon: "fas fa-upload",
        text: "Subir Recursos",
        path: "/docente/subir_recursos.php",
      },
      {
        icon: "fas fa-tasks",
        text: "Calificar Tareas",
        path: "/docente/calificar_tareas.php",
      },
      {
        icon: "fas fa-undo",
        text: "Regresar Tareas",
        path: "/docente/regresar_tareas.php",
      },
      {
        icon: "fas fa-bell",
        text: "Notificaciones",
        path: "/docente/notificaciones.php",
      },
      { icon: "fas fa-cog", text: "Ajustes", path: "/docente/ajustes.php" },
    ],

    admin: [
      {
        icon: "fas fa-chart-pie",
        text: "Dashboard",
        path: "/Plataforma_UT/vistas/admin/dashboardAdmin.php",
      },
      {
        icon: "fas fa-user-shield",
        text: "Administradores",
        path: "/Plataforma_UT/vistas/admin/gestion_de_admin.php",
      },
      {
        icon: "fas fa-user-tie",
        text: "Secretarías",
        path: "/Plataforma_UT/vistas/admin/gestion_de_secretarias.php",
      },
      {
        icon: "fas fa-chalkboard-teacher",
        text: "Docentes",
        path: "/Plataforma_UT/vistas/admin/gestion_de_profesores.php",
      },
      {
        icon: "fas fa-user-graduate",
        text: "Alumnos",
        path: "/Plataforma_UT/vistas/admin/gestion_de_alumnos.php",
      },

      {
        icon: "fas fa-university",
        text: "Carreras",
        path: "/Plataforma_UT/vistas/admin/gestion_de_carreras.php",
      },
      {
        icon: "fas fa-layer-group",
        text: "Semestres",
        path: "/Plataforma_UT/vistas/admin/gestion_de_semestres.php",
      },
      {
        icon: "fas fa-users",
        text: "Grupos",
        path: "/Plataforma_UT/vistas/admin/gestion_de_grupos.php",
      },
      {
        icon: "fas fa-book",
        text: "Materias",
        path: "/Plataforma_UT/vistas/admin/gestion_de_materias.php",
      },
      {
        icon: "fas fa-book-open",
        text: "Asignar Materias",
        path: "/Plataforma_UT/vistas/admin/gestion_de_asignaciones_materias.php",
      },
      {
        icon: "fas fa-chalkboard-teacher",
        text: "Asignar Docente",
        path: "/Plataforma_UT/vistas/admin/gestion_de_asignaciones_docente.php",
      },
      {
        icon: "fas fa-user-graduate",
        text: "Asignar Alumno",
        path: "/Plataforma_UT/vistas/admin/gestion_de_asignaciones_alumno.php",
      },
      {
        icon: "fas fa-bell",
        text: "Notificaciones",
        path: "/Plataforma_UT/vistas/admin/notificaciones.php",
      },
      {
        icon: "fas fa-cog",
        text: "Ajustes",
        path: "/Plataforma_UT/vistas/admin/ajustes.php",
      },
    ],

    secretaria: [
      {
        icon: "fas fa-chart-pie",
        text: "Dashboard",
        path: "/secretaria/dashboard.php",
      },
      {
        icon: "fas fa-users-cog",
        text: "Gestionar Grupos/Materias",
        path: "/secretaria/gestionar_grupos.php",
      },
      {
        icon: "fas fa-file-excel",
        text: "Importar Listas",
        path: "/secretaria/importar_listas.php",
      },
      {
        icon: "fas fa-dollar-sign",
        text: "Adeudos y Pagos",
        path: "/secretaria/adeudos_pagos.php",
      },
      {
        icon: "fas fa-file-alt",
        text: "Reportes Acad./Financieros",
        path: "/secretaria/reportes.php",
      },
      { icon: "fas fa-cog", text: "Ajustes", path: "/secretaria/ajustes.php" },
    ],
  };

  const menu = document.getElementById("menu");
  menu.innerHTML = "";

  // --- Función para crear secciones ---
  function renderSeccion(headingText, items) {
    const sectionDiv = document.createElement("div");
    sectionDiv.classList.add("nav-menu");

    const heading = document.createElement("div");
    heading.classList.add("menu-heading");
    heading.textContent = headingText;
    sectionDiv.appendChild(heading);

    items.forEach((item) => {
      const div = document.createElement("div");
      div.classList.add("nav-item");
      div.innerHTML = `<i class="${item.icon}"></i><span>${item.text}</span>`;
      div.style.cursor = "pointer";
      div.addEventListener("click", () => (window.location.href = item.path));
      sectionDiv.appendChild(div);
    });

    menu.appendChild(sectionDiv);
  }

  const rolUsuario = window.rolUsuarioPHP;

  // --- Renderizado dinámico ---
  if (rolUsuario === "admin") {
    renderSeccion("Menú", menuConfig.admin.slice(0, 1));
    renderSeccion("Gestión de Usuarios", menuConfig.admin.slice(1, 5));
    renderSeccion("Gestión Académica", menuConfig.admin.slice(5, 12));
    renderSeccion("Configuraciones", menuConfig.admin.slice(12));
  } else {
    renderSeccion("Menú", menuConfig[rolUsuario] || []);
  }

  // --- Cerrar sesión (para todos) ---
  renderSeccion("Sesión", [
    {
      icon: "fas fa-sign-out-alt",
      text: "Cerrar sesión",
      path: "/Plataforma_UT/controladores/logout.php",
    },
  ]);
});

// === Toggle sidebar ===
const hamburger = document.getElementById("hamburger");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

function toggleMenu() {
  sidebar.classList.toggle("active");
  overlay.classList.toggle("active");
}

if (hamburger) hamburger.addEventListener("click", toggleMenu);
if (overlay) overlay.addEventListener("click", toggleMenu);

// === Perfil de usuario ===
document.addEventListener("DOMContentLoaded", () => {
  const profile = document.getElementById("userProfile");
  if (!profile) return;

  const nombre = profile.dataset.nombre || "";
  const rol = profile.dataset.rol || "";

  const iniciales = nombre
    .split(" ")
    .filter(Boolean)
    .map((w) => w[0].toUpperCase())
    .join("");

  profile.querySelector(".profile-img").textContent = iniciales;
  profile.querySelector(".user-name").textContent = nombre;
  profile.querySelector(".user-role").textContent =
    rol.charAt(0).toUpperCase() + rol.slice(1);
});
