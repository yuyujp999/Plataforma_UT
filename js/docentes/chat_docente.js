document.addEventListener("DOMContentLoaded", () => {
  const chatList = document.querySelector(".chat-list");
  const chatBody = document.querySelector(".chat-body");
  const input = document.querySelector(".chat-input input");
  const btn = document.querySelector(".chat-input button");
  let id_chat = null;
  let intervalo = null;

  // 🔹 Cargar grupos del docente
  fetch("/Plataforma_UT/api/chat_docente.php?action=grupos")
    .then(res => res.json())
    .then(grupos => {
      if (!grupos.length) {
        chatList.innerHTML = "<p class='placeholder'>No tienes grupos asignados.</p>";
        return;
      }

      chatList.innerHTML = grupos.map(g =>
        `<div class='grupo' data-id='${g.id_grupo}'>
          <i class='fa-solid fa-users'></i> ${g.nombre_grupo}
        </div>`).join("");

      document.querySelectorAll(".grupo").forEach(el => {
        el.addEventListener("click", () => cargarAlumnos(el.dataset.id, el));
      });
    });

  // 🔹 Mostrar alumnos de un grupo (evita duplicados)
function cargarAlumnos(id_grupo, elemento) {
  // 🔸 Limpiar los alumnos anteriores antes de volver a cargar
  document.querySelectorAll(".alumno").forEach(a => a.remove());
  document.querySelectorAll(".placeholder").forEach(p => p.remove());

  // Si ya hay alumnos cargados debajo, no los vuelvas a insertar
  if (elemento.nextElementSibling && elemento.nextElementSibling.classList.contains("alumno")) return;

  fetch(`/Plataforma_UT/api/chat_docente.php?action=alumnos&id_grupo=${id_grupo}`)
    .then(res => res.json())
    .then(alumnos => {
      if (!alumnos.length) {
        elemento.insertAdjacentHTML("afterend", "<p class='placeholder'>Sin alumnos.</p>");
        return;
      }

      // 🔸 Evitar duplicados de alumnos
      const alumnosUnicos = [];
      const ids = new Set();
      for (const a of alumnos) {
        if (!ids.has(a.id_alumno)) {
          ids.add(a.id_alumno);
          alumnosUnicos.push(a);
        }
      }

      const html = alumnosUnicos.map(a =>
        `<div class='alumno' data-id='${a.id_alumno}' data-grupo='${id_grupo}'>
          <i class='fa-solid fa-user-graduate'></i> ${a.nombre}
        </div>`).join("");

      elemento.insertAdjacentHTML("afterend", html);

      document.querySelectorAll(".alumno").forEach(a =>
        a.addEventListener("click", () => abrirChat(a.dataset.id, a.dataset.grupo, a.textContent))
      );
    });
}

  // 🔹 Abrir chat con alumno
  function abrirChat(id_alumno, id_grupo, nombre) {
    fetch("/Plataforma_UT/api/chat_docente.php?action=crear_chat", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id_docente=${window.usuarioId || 1}&id_alumno=${id_alumno}&id_grupo=${id_grupo}`
    })
      .then(res => res.json())
      .then(data => {
        id_chat = data.id_chat;
        document.querySelector(".chat-header h3").textContent = nombre.trim();
        input.disabled = false;
        btn.disabled = false;
        cargarMensajes();
        if (intervalo) clearInterval(intervalo);
        intervalo = setInterval(cargarMensajes, 3000);
      });
  }

  // 🔹 Cargar mensajes desde la base de datos
function cargarMensajes() {
  if (!id_chat) return;
  fetch(`/Plataforma_UT/api/chat_docente.php?action=mensajes&id_chat=${id_chat}`)
    .then(res => res.json())
    .then(mensajes => {
      chatBody.innerHTML = mensajes.map(m => `
        <div class="mensaje ${m.remitente === 'docente' ? 'msg-right' : 'msg-left'}" data-id="${m.id_mensaje}">
          <div class="msg-content">
            ${m.contenido}
            <div class="msg-actions">
              ${m.remitente === 'docente' 
                ? `<button class="delete-msg" title="Eliminar mensaje"><i class="fa-solid fa-trash"></i></button>` 
                : ''}
            </div>
            <span class="hora">
              ${new Date(m.fecha_envio).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}
              ${m.leido == 1 ? '✅✅' : '✅'}
            </span>
          </div>
        </div>
      `).join("");

      // 🔸 Botón eliminar mensaje
      document.querySelectorAll(".delete-msg").forEach(btn => {
        btn.addEventListener("click", e => {
          const id_mensaje = e.target.closest(".mensaje").dataset.id;
          eliminarMensaje(id_mensaje);
        });
      });

      // 🔸 Marcar mensajes como leídos
      marcarComoLeidos();
      chatBody.scrollTop = chatBody.scrollHeight;
    });
}

// 🔹 Marcar mensajes como leídos (para quien recibe)
function marcarComoLeidos() {
  fetch("/Plataforma_UT/api/chat_docente.php?action=marcar_leido", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id_chat=${id_chat}&remitente=docente`
  });
}

// 🔹 Eliminar mensaje
function eliminarMensaje(id_mensaje) {
  if (!confirm("¿Eliminar este mensaje para todos?")) return;
  fetch("/Plataforma_UT/api/chat_docente.php?action=eliminar_mensaje", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id_mensaje=${id_mensaje}`
  })
    .then(res => res.json())
    .then(data => {
      if (data.status === "ok") {
        cargarMensajes();
      }
    });
}




  // 🔹 Enviar mensaje
  btn.addEventListener("click", () => {
    const texto = input.value.trim();
    if (!texto || !id_chat) return;

    // Mostrar mensaje al instante en el chat antes de recargar
    const msgTemp = `
      <div class="mensaje msg-right temp">
        <div class="msg-content">${texto}<span class="hora">Enviando...</span></div>
      </div>`;
    chatBody.insertAdjacentHTML("beforeend", msgTemp);
    chatBody.scrollTop = chatBody.scrollHeight;

    fetch("/Plataforma_UT/api/chat_docente.php?action=enviar", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id_chat=${encodeURIComponent(id_chat)}&remitente=docente&contenido=${encodeURIComponent(texto)}`
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === "ok") {
          input.value = "";
          cargarMensajes();
        }
      });
  });
});
