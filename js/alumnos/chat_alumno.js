document.addEventListener("DOMContentLoaded", () => {
  const chatList = document.querySelector(".chat-list");
  const chatBody = document.querySelector(".chat-body");
  const input = document.querySelector(".chat-input input");
  const btn = document.querySelector(".chat-input button");
  let id_chat = null;
  let intervalo = null;

  // 🔹 Cargar chats del alumno (docentes disponibles)
  fetch("/Plataforma_UT/api/chat_alumno.php?action=chats")
    .then(res => res.json())
    .then(chats => {
      if (!chats.length) {
        chatList.innerHTML = "<p class='placeholder'>No tienes conversaciones.</p>";
        return;
      }

      // Evita duplicados de docentes
      const docentesUnicos = [];
      const ids = new Set();
      for (const c of chats) {
        if (!ids.has(c.id_chat)) {
          ids.add(c.id_chat);
          docentesUnicos.push(c);
        }
      }

      chatList.innerHTML = docentesUnicos
        .map(
          c => `
        <div class='chat' data-id='${c.id_chat}'>
          <i class='fa-solid fa-user-tie'></i> ${c.nombre_docente}<br>
          <small>${c.grupo}</small>
        </div>`
        )
        .join("");

      document.querySelectorAll(".chat").forEach(c =>
        c.addEventListener("click", () => abrirChat(c.dataset.id, c.textContent))
      );
    });

  // 🔹 Abrir chat con un docente
  function abrirChat(id, nombre) {
    id_chat = id;
    document.querySelector(".chat-header h3").textContent = nombre.trim();
    input.disabled = false;
    btn.disabled = false;
    cargarMensajes();
    if (intervalo) clearInterval(intervalo);
    intervalo = setInterval(cargarMensajes, 3000);
  }

  // 🔹 Cargar mensajes del chat actual
  function cargarMensajes() {
    if (!id_chat) return;
    fetch(`/Plataforma_UT/api/chat_alumno.php?action=mensajes&id_chat=${id_chat}`)
      .then(res => res.json())
      .then(mensajes => {
        if (!Array.isArray(mensajes)) return;

        chatBody.innerHTML = mensajes
          .map(m => {
            const esAlumno = m.remitente === "alumno";
            const nombreRemitente = esAlumno
              ? `<span class='nombre-remitente'><i class="fa-solid fa-user-graduate"></i> Tú:</span>`
              : `<span class='nombre-remitente'><i class="fa-solid fa-user-tie"></i> Docente:</span>`;

            // 🔸 Botón eliminar solo para los mensajes del alumno
            const botonEliminar = esAlumno
              ? `<button class="delete-msg" data-id="${m.id_mensaje}" title="Eliminar mensaje">
                  <i class="fa-solid fa-trash"></i>
                </button>`
              : "";

            return `
              <div class="mensaje ${esAlumno ? "msg-right" : "msg-left"}" data-id="${m.id_mensaje}">
                <div class="msg-content">
                  ${nombreRemitente}<br>
                  ${m.contenido}
                  <span class="hora">
                    ${new Date(m.fecha_envio).toLocaleTimeString([], {
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                    ${m.leido == 1 ? "✓ ✓ " : "✓ "}
                  </span>
                  <div class="msg-actions">${botonEliminar}</div>
                </div>
              </div>`;
          })
          .join("");

        // 🔸 Activar eventos de eliminación
        document.querySelectorAll(".delete-msg").forEach(btn =>
          btn.addEventListener("click", e => {
            e.stopPropagation();
            eliminarMensaje(btn.dataset.id);
          })
        );

        // 🔸 Marcar como leídos
        fetch("/Plataforma_UT/api/chat_alumno.php?action=marcar_leido", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `id_chat=${id_chat}`,
        });

        chatBody.scrollTop = chatBody.scrollHeight;
      })
      .catch(err => console.error("Error al cargar mensajes:", err));
  }

  // 🔹 Eliminar mensaje propio
  function eliminarMensaje(idMensaje) {
    if (!confirm("¿Deseas eliminar este mensaje?")) return;

    fetch("/Plataforma_UT/api/chat_alumno.php?action=eliminar_mensaje", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id_mensaje=${encodeURIComponent(idMensaje)}`,
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === "ok") {
          const msgElement = document.querySelector(
            `.mensaje[data-id='${idMensaje}']`
          );
          if (msgElement) msgElement.remove();
        } else {
          console.error("Error al eliminar mensaje:", data);
        }
      })
      .catch(err => console.error("Error al eliminar:", err));
  }

  // 🔹 Enviar mensaje
  btn.addEventListener("click", () => {
    const texto = input.value.trim();
    if (!texto || !id_chat) return;

    // Mostrar temporalmente mientras se envía
    const msgTemp = `
      <div class="mensaje msg-right temp">
        <div class="msg-content">
          <span class='nombre-remitente'><i class="fa-solid fa-user-graduate"></i> Tú:</span><br>
          ${texto}
          <span class="hora">Enviando...</span>
        </div>
      </div>`;
    chatBody.insertAdjacentHTML("beforeend", msgTemp);
    chatBody.scrollTop = chatBody.scrollHeight;

    fetch("/Plataforma_UT/api/chat_alumno.php?action=enviar", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id_chat=${encodeURIComponent(id_chat)}&remitente=alumno&contenido=${encodeURIComponent(texto)}`,
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === "ok") {
          input.value = "";
          cargarMensajes(); // refresca el chat al enviar
        } else {
          console.error("Error al enviar mensaje:", data);
        }
      })
      .catch(err => console.error("Error al enviar:", err));
  });
});

  // 🔍 Buscar alumnos o docentes en toda la base
  const buscador = document.getElementById("buscadorChat");
  const resultados = document.querySelector(".search-results");

  if (buscador) {
    buscador.addEventListener("input", () => {
      const texto = buscador.value.trim();

      if (texto.length < 2) {
        resultados.style.display = "none";
        return;
      }

      fetch(`/Plataforma_UT/api/chat_alumno.php?action=buscar_usuarios&query=${encodeURIComponent(texto)}`)
        .then(res => res.json())
        .then(usuarios => {
          if (!usuarios.length) {
            resultados.innerHTML = "<p class='placeholder'>No se encontraron resultados.</p>";
            resultados.style.display = "block";
            return;
          }

          resultados.innerHTML = usuarios.map(u => `
            <div class="result" data-id="${u.id_usuario}" data-rol="${u.rol}">
              <i class="${u.rol === 'docente' ? 'fa-solid fa-user-tie' : 'fa-solid fa-user-graduate'}"></i>
              ${u.nombre}
            </div>
          `).join("");
          resultados.style.display = "block";

          document.querySelectorAll(".result").forEach(r => {
            r.addEventListener("click", () => {
              crearOabrirChat(r.dataset.id, r.dataset.rol, r.textContent.trim());
            });
          });
        })
        .catch(err => console.error("Error en búsqueda:", err));
    });
  }

  // 📨 Crear o abrir chat nuevo con un usuario
  function crearOabrirChat(idUsuario, rol, nombre) {
    fetch("/Plataforma_UT/api/chat_alumno.php?action=crear_chat", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id_usuario=${encodeURIComponent(idUsuario)}&rol=${encodeURIComponent(rol)}`
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === "ok") {
          resultados.style.display = "none";
          buscador.value = "";
          abrirChat(data.id_chat, nombre);
        } else {
          console.error("Error al crear chat:", data);
        }
      })
      .catch(err => console.error("Error al crear chat:", err));
  }
