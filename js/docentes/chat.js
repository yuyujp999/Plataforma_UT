// /Plataforma_UT/js/docentes/chat.js
// Chat unificado: carga chats, mensajes, envío de texto y archivos, badges y online.
// Reemplaza completamente el archivo actual por este para tener una versión limpia y coherente.

document.addEventListener('DOMContentLoaded', () => {
    // ---------- CONFIG / GLOBALES ----------
    let idChatActual = null;
    let archivoSeleccionado = null; // archivo pendiente de enviar
    let nombreChat = '';
    let historialConteos = {}; // conteo por id_chat
    let sonidoListo = false;

    const API = '/Plataforma_UT/api/chat.php'; // URL API central
    const idUsuario = window.ID_USUARIO ?? null;
    const rolUsuario = window.rolUsuarioPHP ?? 'docente';

    // ---------- ELEMENTOS DOM ----------
    const elChatsActivos = document.getElementById('chatsActivos');
    const elUsuariosEnLinea = document.getElementById('usuariosEnLinea');
    const elResultados = document.getElementById('resultadosBusqueda');
    const elChatHeader = document.getElementById('chatHeader');
    const elChatBody = document.getElementById('chatBody');
    const elMsgInput = document.getElementById('msgInput');
    const elSendBtn = document.getElementById('sendBtn');
    const elSearchBtn = document.getElementById('searchBtn');
    const elSearchInput = document.getElementById('searchInput');
    const elFileInput = document.getElementById('inputArchivo');
    const elPreview = document.getElementById('previewFile'); // preview area

    // sonido de notificación
    const sonidoMensaje = new Audio('/Plataforma_UT/css/sounds/notify.mp3');
    sonidoMensaje.oncanplaythrough = () => { sonidoListo = true; };
    sonidoMensaje.addEventListener('error', () => { /* ignore */ });

    // ---------- UTILIDADES ----------
    function safeText(s = '') {
        return String(s).replace(/\s+/g, '').trim().toLowerCase();
    }

    function horaFormateada(fechaStr) {
        if (!fechaStr) return '';
        try {
            return new Date(fechaStr).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return fechaStr;
        }
    }

    // ---------- PREVIEW TEMPORAL ----------
    function mostrarArchivoTemporal(file) {
        if (!elPreview) return;

        if (!file) {
            elPreview.style.display = 'none';
            elPreview.innerHTML = '';
            return;
        }

        elPreview.style.display = 'block';

        if (file.type.startsWith('image/')) {
            elPreview.innerHTML = `
                <div style="display:flex;gap:8px;align-items:center">
                    <img src="${URL.createObjectURL(file)}"
                         style="max-width:120px;border-radius:8px;"/>
                    <div style="font-size:12px;color:#333">
                        <div>${file.name}</div>
                        <div style="opacity:0.7">${(file.size / 1024 | 0)} KB</div>
                    </div>
                </div>
            `;
        } else {
            elPreview.innerHTML = `
                <div style="display:flex;gap:8px;align-items:center">
                    <div style="font-size:22px">📎</div>
                    <div style="font-size:12px;color:#333">
                        <div>${file.name}</div>
                        <div style="opacity:0.7">${(file.size / 1024 | 0)} KB</div>
                    </div>
                </div>
            `;
        }
    }

    // ---------- MOSTRAR ARCHIVO EN CHAT ----------
    function mostrarArchivoEnChat(url, tipo, remitente, fecha) {
        if (!elChatBody) return;

        const miMensaje =
            (remitente === rolUsuario ||
                remitente === idUsuario ||
                remitente === 'yo');

        const clase = miMensaje ? 'msg msg-out' : 'msg msg-in';
        let html = '';

        if (tipo === 'imagen' || (typeof tipo === 'string' && tipo.includes('image'))) {
            html = `
                <div class="${clase}">
                    <img src="${url}" alt="imagen"
                         style="max-width:220px;border-radius:8px;display:block;margin-bottom:6px;">
                    <div style="font-size:12px;color:gray;text-align:right">
                        ${horaFormateada(fecha)}
                    </div>
                </div>
            `;
        } else {
            const nombre = url.split('/').pop();
            html = `
                <div class="${clase}">
                    <a href="${url}" target="_blank" class="chat-file"
                       style="display:inline-block;padding:6px 8px;border-radius:6px;background:#f3f3f3;text-decoration:none;">
                        📄 ${nombre}
                    </a>
                    <div style="font-size:12px;color:gray;text-align:right;margin-top:4px">
                        ${horaFormateada(fecha)}
                    </div>
                </div>
            `;
        }

        elChatBody.insertAdjacentHTML('beforeend', html);
        elChatBody.scrollTop = elChatBody.scrollHeight;
    }

    // ---------- RENDER MENSAJES ----------
    function renderizarMensajesEnChat(mensajes) {
        if (!elChatBody) return;

        elChatBody.innerHTML = '';

        if (!Array.isArray(mensajes) || mensajes.length === 0) {
            elChatBody.innerHTML = '<p class="placeholder">No hay mensajes aún.</p>';
            return;
        }

        mensajes.forEach(m => {
            // Si hay archivo, mostrarlo
            if (m.archivo) {
                // Si el backend incluye 'url' usarla; sino construir ruta relativa
                const url = m.url ?? `/Plataforma_UT/docs/documentos/${m.archivo}`;

                const tipo = m.tipo_archivo ||
                    (m.archivo && m.archivo.match(/\.(jpg|png|gif|jpeg)$/i)
                        ? 'imagen'
                        : 'documento');

                mostrarArchivoEnChat(
                    url,
                    tipo,
                    m.remitente,
                    m.fecha_envio
                );
            }

            // Si hay contenido textual, mostrarlo
            if (m.contenido && m.contenido !== null) {
                const hora = m.fecha_envio ? horaFormateada(m.fecha_envio) : '';
                const div = document.createElement('div');
                div.className = `msg ${m.remitente}`;
                div.innerHTML = `
                    <div>${m.contenido}</div>
                    <div style="font-size:12px;color:gray;text-align:right">${hora}</div>
                `;
                elChatBody.appendChild(div);
            }
        });

        elChatBody.scrollTop = elChatBody.scrollHeight;
    }

    // ---------- CARGAR MENSAJES ----------
    async function cargarMensajes(id) {
        if (!id) return;

        try {
            const res = await fetch(`${API}?action=mensajes&id_chat=${encodeURIComponent(id)}`);
            const mensajes = await res.json();
            renderizarMensajesEnChat(mensajes);
            historialConteos[id] = Array.isArray(mensajes) ? mensajes.length : 0;
        } catch (err) {
            console.error('cargarMensajes error', err);
        }
    }

    // ---------- CREAR ITEM DE CHAT ----------
    function createChatItemDOM(c) {
        const div = document.createElement('div');
        div.className = 'chat-item';
        div.dataset.id = c.id_chat;
        div.dataset.nombre = c.nombre;
        div.innerHTML = `
            <span class="chat-name">${c.nombre}</span>
            <span class="small" style="margin-left:8px">${c.rol || ''}</span>
        `;
        div.addEventListener('click', () => abrirChat(c.id_chat, c.nombre));
        return div;
    }

    // ---------- CARGAR CHATS ----------
    async function cargarChats() {
        try {
            const res = await fetch(`${API}?action=chats`);
            const data = await res.json();

            elChatsActivos.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                elChatsActivos.innerHTML =
                    '<p class="placeholder">No hay conversaciones.</p>';
                return;
            }

            data.forEach(c => {
                const dom = createChatItemDOM(c);
                elChatsActivos.appendChild(dom);
            });

            actualizarBadges();
        } catch (err) {
            console.error('cargarChats error', err);
            elChatsActivos.innerHTML =
                '<p class="placeholder">Error al cargar chats.</p>';
        }
    }

    // ---------- BUSCAR USUARIOS ----------
    async function buscarUsuarios(query) {
        try {
            const res = await fetch(
                `${API}?action=buscar_usuarios&query=${encodeURIComponent(query)}`
            );
            const data = await res.json();

            elResultados.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                elResultados.innerHTML =
                    '<p class="placeholder">No se encontraron resultados.</p>';
                return;
            }

            data.forEach(u => {
                const div = document.createElement('div');
                div.className = 'user-item';
                div.textContent = `${u.nombre} (${u.rol})`;
                div.addEventListener('click', () =>
                    crearChat(u.id_usuario, u.rol, u.nombre)
                );
                elResultados.appendChild(div);
            });
        } catch (err) {
            console.error('buscarUsuarios error', err);
            elResultados.innerHTML =
                '<p class="placeholder">Error en búsqueda.</p>';
        }
    }

    // ---------- CREAR CHAT ----------
    async function crearChat(id_usuario, rol, nombre) {
        try {
            const fd = new FormData();
            fd.append('id_usuario', id_usuario);
            fd.append('rol', rol);

            const res = await fetch(`${API}?action=crear_chat`, {
                method: 'POST',
                body: fd
            });

            const data = await res.json();

            if (data.status === 'ok') {
                await cargarChats();
                abrirChat(data.id_chat, nombre);
            } else {
                console.warn('crearChat fallo', data);
            }
        } catch (err) {
            console.error('crearChat error', err);
        }
    }

    // ---------- CARGAR USUARIOS EN LÍNEA ----------
    async function cargarUsuariosEnLinea() {
        try {
            const res = await fetch(`${API}?action=usuarios_en_linea`);
            const data = await res.json();

            elUsuariosEnLinea.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                elUsuariosEnLinea.innerHTML =
                    '<p class="placeholder">No hay usuarios en línea.</p>';
                return;
            }

            data.forEach(u => {
                const div = document.createElement('div');
                div.className = 'online-user';
                div.innerHTML = `
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="status-dot ${u.en_linea ? 'online' : 'status-offline'}"></div>
                        <span>
                            ${u.nombre}
                            <span class="small">(${u.rol})</span>
                        </span>
                    </div>
                `;
                div.addEventListener('click', () =>
                    crearChat(u.id_usuario, u.rol, u.nombre)
                );
                elUsuariosEnLinea.appendChild(div);
            });
        } catch (err) {
            console.error('cargarUsuariosEnLinea error', err);
            elUsuariosEnLinea.innerHTML =
                '<p class="placeholder">Error al cargar usuarios.</p>';
        }
    }

    // ---------- ABRIR CHAT ----------
    async function abrirChat(id, nombre) {
        if (!id) return;

        try {
            idChatActual = id;
            nombreChat = nombre;

            if (elMsgInput) elMsgInput.disabled = false;
            if (elSendBtn) elSendBtn.disabled = false;

            if (elChatHeader) {
                elChatHeader.innerHTML = `<h3><i class="fa-solid fa-user"></i> ${nombre}</h3>`;
            }

            await cargarMensajes(idChatActual);

            // marcar leído
            const fd = new FormData();
            fd.append('id_chat', idChatActual);

            await fetch(`${API}?action=marcar_leido`, {
                method: 'POST',
                body: fd
            });

            historialConteos[idChatActual] = historialConteos[idChatActual] || 0;
            actualizarBadges();
        } catch (err) {
            console.error('abrirChat error', err);
        }
    }

    // ---------- ENVIAR TEXTO Y/O ARCHIVOS ----------
    async function enviarTextoYArchivos() {
        const texto = (elMsgInput?.value || '').trim();

        if (!texto && !archivoSeleccionado) return;

        if (!idChatActual) {
            alert('Selecciona un chat primero.');
            return;
        }

        // subir archivo primero si existe
        if (archivoSeleccionado) {
            try {
                const data = await enviarArchivoAdjunto(archivoSeleccionado);

                if (data && data.status === 'ok') {
                    // usar url devuelta o construirla
                    const url = data.url ??
                        `/Plataforma_UT/docs/documentos/${data.archivo}`;

                    mostrarArchivoEnChat(
                        url,
                        data.tipo_archivo,
                        data.remitente,
                        data.fecha_envio ?? data.fecha
                    );
                }
            } catch (err) {
                console.error('Error al subir archivo:', err);
                alert('No se pudo subir el archivo.');
                return;
            }
        }

        // enviar texto si existe
        if (texto) {
            const fd = new FormData();
            fd.append('id_chat', idChatActual);
            fd.append('contenido', texto);

            try {
                await fetch(`${API}?action=enviar`, {
                    method: 'POST',
                    body: fd
                });

                elMsgInput.value = '';

                // recargar mensajes para garantizar consistencia
                await cargarMensajes(idChatActual);
            } catch (err) {
                console.error('Error al enviar texto:', err);
            }
        }

        // limpiar preview/archivo
        archivoSeleccionado = null;

        if (elFileInput) elFileInput.value = '';

        if (elPreview) {
            elPreview.style.display = 'none';
            elPreview.innerHTML = '';
        }
    }

    // ---------- SUBIR ARCHIVO AL SERVIDOR ----------
    async function enviarArchivoAdjunto(file) {
        if (!idChatActual) {
            throw new Error('idChatActual no definido');
        }

        const form = new FormData();
        form.append('archivo', file);
        form.append('id_chat', idChatActual);
        form.append('remitente', rolUsuario);

        const resp = await fetch(`${API}?action=subirArchivo`, {
            method: 'POST',
            body: form
        });

        // Manejo robusto para evitar "Unexpected token '<'"
        const raw = await resp.text();
        let data;

        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Respuesta NO es JSON válido. Respuesta cruda:', raw);
            throw new Error('Respuesta del servidor no es JSON válido.');
        }

        if (!data || (data.status !== 'ok' && data.status !== 'success')) {
            throw new Error(data?.msg || 'Error servidor');
        }

        // actualizar mensajes y badges
        await cargarMensajes(idChatActual);
        actualizarBadges();

        return data;
    }

    // ---------- BADGES / NOTIFICACIONES ----------
    async function actualizarBadges() {
        try {
            const res = await fetch(`${API}?action=notificaciones`);
            const data = await res.json();

            if (!Array.isArray(data)) return;

            data.forEach(noti => {
                let chatItem = [...document.querySelectorAll('.chat-item')]
                    .find(i => i.dataset.id == noti.id_chat);

                if (!chatItem) {
                    chatItem = [...document.querySelectorAll('.chat-item')]
                        .find(i =>
                            safeText(i.textContent).includes(safeText(noti.nombre))
                        );
                }

                if (!chatItem) return;

                let badge = chatItem.querySelector('.badge');

                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge';
                    chatItem.appendChild(badge);
                }

                const num = parseInt(noti.no_leidos, 10) || 0;
                badge.textContent = num;
                badge.style.display = num > 0 ? 'inline-block' : 'none';
            });
        } catch (err) {
            console.error('actualizarBadges error', err);
        }
    }

    function actualizarBadgesNoti(data) {
        if (!Array.isArray(data)) return;

        data.forEach(noti => {
            let chatItem = [...document.querySelectorAll('.chat-item')]
                .find(i => i.dataset.id == noti.id_chat);

            if (!chatItem) {
                chatItem = [...document.querySelectorAll('.chat-item')]
                    .find(i =>
                        safeText(i.textContent).includes(safeText(noti.nombre))
                    );
            }

            if (!chatItem) return;

            let badge = chatItem.querySelector('.badge');

            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge';
                chatItem.appendChild(badge);
            }

            const num = parseInt(noti.no_leidos, 10) || 0;
            badge.textContent = num;
            badge.style.display = num > 0 ? 'inline-block' : 'none';
        });
    }

    // ---------- EVENTOS (DOM) ----------

    // Input archivo: preview + guardar referencia
    elFileInput?.addEventListener('change', (e) => {
        const file = e.target.files && e.target.files[0];

        if (!file) {
            archivoSeleccionado = null;
            if (elPreview) {
                elPreview.style.display = 'none';
                elPreview.innerHTML = '';
            }
            return;
        }

        archivoSeleccionado = file;
        mostrarArchivoTemporal(file);
    });

    // Botón enviar: texto + archivo
    elSendBtn?.addEventListener('click', enviarTextoYArchivos);

    // Enter en input para enviar
    elMsgInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            enviarTextoYArchivos();
        }
    });

    // Buscador
    elSearchBtn?.addEventListener('click', () => {
        const q = (elSearchInput.value || '').trim();
        if (!q) return;
        buscarUsuarios(q);
    });

    elSearchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            elSearchBtn.click();
        }
    });

    // ---------- REVISIONES PERIÓDICAS ----------

    // ping para mantener sesión/en línea
    setInterval(() => {
        fetch(`${API}?action=ping`).catch(() => {});
    }, 3000);

    setInterval(() => {
        fetch('/Plataforma_UT/api/limpiar_online.php').catch(() => {});
    }, 3000);

    // refinar: recarga mensajes del chat abierto y badges
    setInterval(async () => {
        if (idChatActual) {
            await cargarMensajes(idChatActual);
        }
    }, 4000);

    setInterval(actualizarBadges, 4000);

    // ---------- INICIALIZACIÓN ----------
    (function init() {
        cargarChats();
        cargarUsuariosEnLinea();
        actualizarBadges();
    })();

    // Liberar audio al primer click (requisito navegadores)
    document.addEventListener('click', () => {
        if (!sonidoListo) {
            sonidoMensaje.play().catch(() => {});
            sonidoMensaje.pause();
            sonidoMensaje.currentTime = 0;
            sonidoListo = true;
        }
    }, { once: true });

    // ---------- EMOJI (si existen elementos) ----------
    const emojiBtn = document.getElementById('emojiBtn');
    const emojiPanel = document.getElementById('emojiPanel');

    if (emojiBtn && emojiPanel && elMsgInput) {
        emojiBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            emojiPanel.classList.toggle('open');
        });

        emojiPanel.addEventListener('click', (e) => {
            if (e.target.tagName === 'SPAN') {
                elMsgInput.value += e.target.textContent;
                elMsgInput.focus();
            }
        });

        document.addEventListener('click', (e) => {
            if (!emojiPanel.contains(e.target) && e.target !== emojiBtn) {
                emojiPanel.classList.remove('open');
            }
        });
    }

    // ---------- CREAR CHAT ALUMNO-ALUMNO ----------
    async function crearChatAlumnoAlumno(idDestino, nombre) {
        try {
            const fd = new FormData();
            fd.append('id_usuario_origen', idUsuario);
            fd.append('rol_origen', rolUsuario);
            fd.append('id_usuario_destino', idDestino);
            fd.append('rol_destino', 'alumno');

            const res = await fetch(`${API}?action=crear_chat`, {
                method: 'POST',
                body: fd
            });

            const data = await res.json();

            if (data.status === 'ok') {
                await cargarChats();
                abrirChat(data.id_chat, nombre);
            } else {
                console.warn('Error crearChat alumno-alumno:', data);
            }
        } catch (err) {
            console.error('crearChatAlumnoAlumno error', err);
        }
    }
}); // end DOMContentLoaded
