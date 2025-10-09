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
    ],
    docente: [
      {
        icon: "fas fa-chart-pie",
        text: "Dashboard",
        path: "/docente/dashboard.php",
      },
      {
        icon: "fas fa-box",
        text: "Virtual UTSC",
        path: "/docente/virtual.php",
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
        icon: "fas fa-file-alt",
        text: "Reportes",
        path: "/admin/reportes.php",
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
    ],
  };

  const rolUsuario = window.rolUsuarioPHP;
  const menu = document.getElementById("menu");
  menu.innerHTML = '<div class="menu-heading">Menú</div>';

  (menuConfig[rolUsuario] || []).forEach((item) => {
    const div = document.createElement("div");
    div.classList.add("nav-item");
    div.innerHTML = `<i class="${item.icon}"></i><span>${item.text}</span>`;
    if (item.path) {
      div.addEventListener("click", () => {
        window.location.href = item.path;
      });
      div.style.cursor = "pointer";
    }
    menu.appendChild(div);
  });

  const adminHeading = document.createElement("div");
  adminHeading.classList.add("menu-heading");
  adminHeading.textContent = "Admin";
  menu.appendChild(adminHeading);

  const ajustes = document.createElement("div");
  ajustes.classList.add("nav-item");
  ajustes.innerHTML = `<i class="fas fa-cog"></i><span>Ajustes</span>`;
  ajustes.addEventListener("click", () => {
    window.location.href = "/ajustes.php";
  });
  ajustes.style.cursor = "pointer";
  menu.appendChild(ajustes);

  const notif = document.createElement("div");
  notif.classList.add("nav-item");
  notif.innerHTML = `<i class="fas fa-bell"></i><span>Notificaciones</span>`;
  notif.addEventListener("click", () => {
    window.location.href = "/notificaciones.php";
  });
  notif.style.cursor = "pointer";
  menu.appendChild(notif);
  const logout = document.createElement("div");
  logout.classList.add("nav-item");
  logout.innerHTML = `<i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>`;
  logout.style.cursor = "pointer";
  logout.addEventListener("click", () => {
    window.location.href = "/Plataforma_UT/controladores/logout.php";
  });
  menu.appendChild(logout);
});

const hamburger = document.getElementById("hamburger");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

function toggleMenu() {
  sidebar.classList.toggle("active");
  overlay.classList.toggle("active");
}

hamburger.addEventListener("click", toggleMenu);
overlay.addEventListener("click", toggleMenu);

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
