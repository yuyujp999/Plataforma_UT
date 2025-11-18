document.addEventListener("DOMContentLoaded", function () {
  const menuConfig = {
    alumno: [
      {
        icon: "fas fa-chart-pie",
        text: "Dashboard",
        path: "/Plataforma_UT/vistas/Alumnos/dashboardAlumno.php",
      },
      {
        icon: "fas fa-users",
        text: "Saiiut UTSC",
        path: "/Plataforma_UT/vistas/Alumnos/dashboard_adeudos.php",
      },
      {
        icon: "fas fa-graduation-cap",
        text: "Calificaciones",
        path: "/Plataforma_UT/vistas/Alumnos/dashboard_calificaciones.php",
      },
      {
        icon: "fas fa-book",
        text: "Mis Tareas",
        path: "/Plataforma_UT/vistas/Alumnos/dashboardTareas.php",
      },
      {
        icon: "fas fa-calendar-alt",
        text: "Calendario Académico",
        path: "/Plataforma_UT/vistas/Alumnos/dashboard_calendario.php",
      },
      {
<<<<<<< HEAD
        icon: "fas fa-cog",
        text: "Ajustes",
        path: "/Plataforma_UT/vistas/Alumnos/dashboard_ajustes.php",
      },
=======
        icon: "fa-solid fa-comments",
       text: "Chats",
        path: "/Plataforma_UT/vistas/Alumnos/ChatAlumno.php",
},
      { icon: "fas fa-cog", 
        text: "Ajustes", 
        path: "/Plataforma_UT/vistas/Alumnos/dashboard_ajustes.php" },
>>>>>>> d8c4327fa39f9fa78d2456765900a9d6b1244661
    ],

    docente: [
      {
        icon: "fas fa-chart-pie",
        text: "Dashboard",
        path: "/Plataforma_UT/vistas/docentes/dashboardDocente.php",
      },
      {
        icon: "fas fa-box",
        text: "Proyectos y Examenes",
        path: "/Plataforma_UT/vistas/docentes/evaluaciones.php",
      },
      {
        icon: "fas fa-tasks",
        text: "Calificar Tareas",
        path: "/Plataforma_UT/vistas/docentes/calificar_tareas.php",
      },
      {
        icon: "fa-solid fa-comments",
        text: "Chats",
        path: "/Plataforma_UT/vistas/Docentes/dashboardChat.php",
      },
      {
        icon: "fas fa-cog",
        text: "Ajustes",
        path: "/Plataforma_UT/vistas/Docentes/DashboardAjustes.php",
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
        icon: "fas fa-calendar-check",
        text: "Ciclo Escolar",
        path: "/Plataforma_UT/vistas/admin/gestion_de_ciclo_escolar.php",
      },
      {
        icon: "fas fa-users",
        text: "Grupos",
        path: "/Plataforma_UT/vistas/admin/gestion_de_grupos.php",
      },
      // NUEVO: Aulas (ADMIN)
      {
        icon: "fas fa-door-open",
        text: "Aulas",
        path: "/Plataforma_UT/vistas/admin/gestion_de_aulas.php",
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
        text: "Mensajes",
        path: "/Plataforma_UT/vistas/admin/mensajes.php",
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
        path: "/Plataforma_UT/vistas/secretarias/dashboardSecretaria.php",
      },
      {
        icon: "fas fa-chalkboard-teacher",
        text: "Docentes",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_profesores.php",
      },
      {
        icon: "fas fa-user-graduate",
        text: "Alumnos",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_alumnos.php",
      },
      {
        icon: "fas fa-university",
        text: "Carreras",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_carreras.php",
      },
      {
        icon: "fas fa-layer-group",
        text: "Semestres",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_semestres.php",
      },
      {
        icon: "fas fa-calendar-check",
        text: "Ciclo Escolar",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_ciclo_escolar.php",
      },
      {
        icon: "fas fa-users",
        text: "Grupos",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_grupos.php",
      },
      // NUEVO: Aulas (SECRETARÍA)
      {
        icon: "fas fa-door-open",
        text: "Aulas",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_aulas.php",
      },
      {
        icon: "fas fa-book",
        text: "Materias",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_materias.php",
      },
      {
        icon: "fas fa-book-open",
        text: "Asignar Materias",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_asignaciones_materias.php",
      },
      {
        icon: "fas fa-chalkboard-teacher",
        text: "Asignar Docente",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_asignaciones_docente.php",
      },
      {
        icon: "fas fa-user-graduate",
        text: "Asignar Alumno",
        path: "/Plataforma_UT/vistas/secretarias/gestion_de_asignaciones_alumno.php",
      },
      {
        icon: "fas fa-clock",
        text: "Horarios",
        path: "/Plataforma_UT/vistas/secretarias/horarios.php",
      },
      {
        icon: "fas fa-dollar-sign",
        text: "Adeudos y Pagos",
        path: "/Plataforma_UT/vistas/secretarias/adeudos_pagos.php",
      },
      {
        icon: "fas fa-bell",
        text: "Notificaciones",
        path: "/Plataforma_UT/vistas/secretarias/notificaciones.php",
      },
      {
        icon: "fas fa-cog",
        text: "Ajustes",
        path: "/Plataforma_UT/vistas/secretarias/ajustes.php",
      },
    ],
  };

  const menu = document.getElementById("menu");
  if (menu) menu.innerHTML = "";

  // --- Creador de secciones ---
  function renderSeccion(headingText, items) {
    if (!menu || !items?.length) return;

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

  // --- Render por rol ---
  if (rolUsuario === "admin") {
    // Menú principal
    renderSeccion("Menú", menuConfig.admin.slice(0, 1));
    // Gestión de Usuarios: Administradores, Secretarías, Docentes, Alumnos
    renderSeccion("Gestión de Usuarios", menuConfig.admin.slice(1, 5));
    // Gestión Académica: Carreras -> Asignar Alumno (incluye Ciclo Escolar, Grupos, Aulas, etc.)
    renderSeccion("Gestión Académica", menuConfig.admin.slice(5, 14));
    // Configuraciones: Mensajes, Ajustes
    renderSeccion("Configuraciones", menuConfig.admin.slice(14));
  } else if (rolUsuario === "secretaria") {
    // Menú principal
    renderSeccion("Menú", menuConfig.secretaria.slice(0, 1)); // Dashboard
    // Gestión de Usuarios: Docentes, Alumnos
    renderSeccion("Gestión de Usuarios", menuConfig.secretaria.slice(1, 3));
    // Gestión Académica: Carreras -> Asignar Alumno (incluye Ciclo Escolar, Grupos, Aulas, etc.)
    renderSeccion("Gestión Académica", menuConfig.secretaria.slice(3, 12));
    // Gestión Operativa: Horarios, Adeudos y Pagos
    renderSeccion("Gestión Operativa", menuConfig.secretaria.slice(12, 14));
    // Configuraciones: Notificaciones, Ajustes
    renderSeccion("Configuraciones", menuConfig.secretaria.slice(14));
  } else {
    // Otros roles (alumno, docente, etc.)
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

  // === Perfil de usuario ===
  const profile = document.getElementById("userProfile");
  if (profile) {
    const nombre = profile.dataset.nombre || "";
    const rol = profile.dataset.rol || "";

    const iniciales = nombre
      .split(" ")
      .filter(Boolean)
      .map((w) => w[0].toUpperCase())
      .join("");

    const img = profile.querySelector(".profile-img");
    const nameEl = profile.querySelector(".user-name");
    const roleEl = profile.querySelector(".user-role");

    if (img) img.textContent = iniciales || "?";
    if (nameEl) nameEl.textContent = nombre || "Usuario";
    if (roleEl)
      roleEl.textContent = rol
        ? rol.charAt(0).toUpperCase() + rol.slice(1)
        : "Rol";
  }
});

// === Toggle sidebar ===
const hamburger = document.getElementById("hamburger");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

function toggleMenu() {
  if (sidebar) sidebar.classList.toggle("active");
  if (overlay) overlay.classList.toggle("active");
}

if (hamburger) hamburger.addEventListener("click", toggleMenu);
if (overlay) overlay.addEventListener("click", toggleMenu);
