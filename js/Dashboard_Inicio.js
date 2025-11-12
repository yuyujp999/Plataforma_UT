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
      {
        icon: "fas fa-bell",
         text: "Chats",
          path: "/Plataforma_UT/vistas/Alumnos/chatAlumno.php",
           badgeId: "badgeChat"
        },
        // se agrego para los alert de chat no leidos 
        { icon: "fas fa-cog", text: "Configuración", path: "#" },
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
        icon: "fa-solid fa-comments",
       text: "Chats",
        path: "/Plataforma_UT/vistas/Docentes/dashboardChat.php",
},

      { icon: "fas fa-cog",
        text: "Ajustes",
        path: "/Plataforma_UT/vistas/Docentes/DashboardAjustes.php" },
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
      // NUEVO
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
        icon: "fas fa-file-excel",
        text: "Importar Listas",
        path: "/Plataforma_UT/vistas/secretarias/importar_listas.php",
      },
      {
        icon: "fas fa-dollar-sign",
        text: "Adeudos y Pagos",
        path: "/Plataforma_UT/vistas/secretarias/adeudos_pagos.php",
      },
      {
        icon: "fas fa-file-alt",
        text: "Reportes Acad./Financieros",
        path: "/Plataforma_UT/vistas/secretarias/reportes.php",
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
      //div.innerHTML = `<i class="${item.icon}"></i><span>${item.text}</span>`; esto lo cambie para el badge

      div.innerHTML = `
  <i class="${item.icon}"></i>
  <span>${item.text}</span>
  ${item.badgeId ? '<span id="' + item.badgeId + '" class="badgeMenu" style="display:none;">0</span>' : ''}
`;

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
    // Gestión Académica: Carreras -> Asignar Alumno (incluye Ciclo Escolar)
    renderSeccion("Gestión Académica", menuConfig.admin.slice(5, 13));
    // Configuraciones: Notificaciones, Ajustes
    renderSeccion("Configuraciones", menuConfig.admin.slice(13));
  } else if (rolUsuario === "secretaria") {
    // Menú principal
    renderSeccion("Menú", menuConfig.secretaria.slice(0, 1)); // Dashboard
    // Gestión de Usuarios: Docentes, Alumnos
    renderSeccion("Gestión de Usuarios", menuConfig.secretaria.slice(1, 3));
    // Gestión Académica: Carreras -> Asignar Alumno (incluye Ciclo Escolar)
    renderSeccion("Gestión Académica", menuConfig.secretaria.slice(3, 11));
    // Operativa: Importar Listas, Adeudos y Pagos, Reportes
    renderSeccion("Gestión Operativa", menuConfig.secretaria.slice(11, 14));
    // Configuraciones: Notificaciones, Ajustes
    renderSeccion("Configuraciones", menuConfig.secretaria.slice(14));
  } else {
    // Otros roles
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

 //const navMenu = document.getElementById("menu");
// === funcionamiento de los msj sin ver  ===
 //menuItems.forEach(item => {
 // const li = document.createElement("li");
 // li.classList.add("menu-item");
 // li.innerHTML = `
  //  <a href="${item.path}" id="${item.badgeId ? 'menuChat' : ''}">
  //    <i class="${item.icon}"></i>
  //    <span>${item.text}</span>
 //     ${item.badgeId ? '<span id="badgeChat" class="badgeMenu" style="display:none;">0</span>' : ''}
//    </a>
 // `;
 // navMenu.appendChild(li);
//});


//document.addEventListener("DOMContentLoaded", () => {
  //const badgeChat = document.getElementById("badgeChat");
  //if (!badgeChat) return;

  //function actualizarNotificacionesChat() {
  //  fetch("/Plataforma_UT/api/chat_alumno.php?action=mensajes_no_leidos")
    //  .then(res => res.json())
      //.then(data => {
       // if (data.total_no_leidos > 0) {
         // badgeChat.textContent = data.total_no_leidos;
          //badgeChat.style.display = "inline-block";
        //} else {
          //badgeChat.style.display = "none";
        //}
      //})
      //.catch(err => console.error("Error al obtener notificaciones:", err));
 // }

  //actualizarNotificacionesChat();
  //setInterval(actualizarNotificacionesChat, 5000);
//});

document.addEventListener("DOMContentLoaded", () => {
  const badge = document.getElementById("badgeChat");
  if (!badge) return;

  async function actualizarNotificacionesChat() {
    try {
      const response = await fetch("/Plataforma_UT/api/chat_docente.php?action=mensajes_no_leidos");
      const data = await response.json();

      if (data.no_leidos > 0) {
        badge.textContent = data.no_leidos;
        badge.style.display = "inline-block";
      } else {
        badge.style.display = "none";
      }
    } catch (err) {
      console.error("Error al obtener notificaciones:", err);
    }
  }

  actualizarNotificacionesChat();
  setInterval(actualizarNotificacionesChat, 10000);
});

  // --- Inicializar actualizador de badge SOLO después de renderizar el menú ---
  (function initBadgeAfterMenu() {
    // función que actualiza el badge usando la API del docente
    async function actualizarNotificacionesChatDocente() {
      const badge = document.getElementById("badgeChat");
      if (!badge) return; // si por alguna razón no existe, salir silenciosamente

      try {
        const res = await fetch("/Plataforma_UT/api/chat_docente.php?action=mensajes_no_leidos", { cache: "no-store" });
        const data = await res.json();
        const total = parseInt(data.no_leidos || data.no_leidos === 0 ? data.no_leidos : (data.no_leidos ?? data.noLeidos ?? 0), 10);

        if (total > 0) {
          badge.textContent = total;
          badge.style.display = "inline-block";
        } else {
          badge.style.display = "none";
        }
      } catch (err) {
        console.error("Error al obtener notificaciones (badge):", err);
      }
    }

    // Solo arrancar si el usuario actual es docente (evita llamadas innecesarias)
    if (window.rolUsuarioPHP === "docente") {
      // llamada inicial (espera 300 ms para dar tiempo a renderizado por si acaso)
      setTimeout(actualizarNotificacionesChatDocente, 300);
      // intervalo
      setInterval(actualizarNotificacionesChatDocente, 10000);
    }
  })();

