<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}
$rolUsuario = $_SESSION['rol'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Contadores generales
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM administradores")->fetchColumn();
$totalSecretarias = (int) $pdo->query("SELECT COUNT(*) FROM secretarias")->fetchColumn();
$totalAlumnos = (int) $pdo->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
$totalDocentes = (int) $pdo->query("SELECT COUNT(*) FROM docentes")->fetchColumn();

function indicadorCambio($total)
{
    $umbral = 10;
    return $total > $umbral
        ? ['clase' => 'positive', 'icon' => 'fa-arrow-up', 'texto' => 'Muchos']
        : ['clase' => 'negative', 'icon' => 'fa-arrow-down', 'texto' => 'Pocos'];
}
$indAdmins = indicadorCambio($totalAdmins);
$indSecretarias = indicadorCambio($totalSecretarias);
$indAlumnos = indicadorCambio($totalAlumnos);
$indDocentes = indicadorCambio($totalDocentes);

// Datos de sesión
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/adminnotificaciones.css" />
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">

    <!-- ===== Estilos específicos para el botón "Eliminar todo" (MENSAJES) ===== -->
    <style>
    /* Contenedor del botón en la cabecera del dropdown de mensajes */
    .notif-dropdown .notif-head .notif-actions {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    /* Botón "Eliminar todo" */
    .notif-dropdown .notif-head .notif-actions #mailDelAll {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .9rem;
        font-size: .85rem;
        font-weight: 600;
        border-radius: 12px;
        border: 1px solid #ef4444;
        /* rojo tenue */
        color: #b91c1c;
        /* texto rojo oscuro */
        background: linear-gradient(180deg, #ffffff 0%, #fff5f5 100%);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .08), inset 0 1px 0 rgba(255, 255, 255, .6);
        cursor: pointer;
        transition: all .15s ease;
        white-space: nowrap;
    }

    .notif-dropdown .notif-head .notif-actions #mailDelAll:hover {
        color: #ffffff;
        background: #ef4444;
        /* rojo */
        border-color: #ef4444;
        box-shadow: 0 6px 14px rgba(239, 68, 68, .25);
        transform: translateY(-1px);
    }

    .notif-dropdown .notif-head .notif-actions #mailDelAll:active {
        transform: translateY(0);
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, .15);
    }

    .notif-dropdown .notif-head .notif-actions #mailDelAll i {
        margin-right: .15rem;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="overlay" id="overlay"></div>
            <div class="logo">
                <h1>UT<span>Panel</span></h1>
            </div>
            <div class="nav-menu" id="menu">
                <div class="menu-heading">Menú</div>
            </div>
        </div>

        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Buscar..." />
            </div>
            <div class="header-actions">
                <!-- CAMPANITA -->
                <div class="notification" id="notifBell">
                    <i class="fas fa-bell"></i>
                    <div class="badge">0</div>
                </div>
                <!-- SOBRE -->
                <div class="notification" id="notifMail">
                    <i class="fas fa-envelope"></i>
                    <div class="badge">0</div>
                </div>
                <div class="user-profile" id="userProfile" data-nombre="<?= htmlspecialchars($nombreCompleto) ?>"
                    data-rol="<?= htmlspecialchars($_SESSION['rol'] ?? '') ?>">
                    <div class="profile-img"><?= $iniciales ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($nombreCompleto ?: 'Usuario') ?></div>
                        <div class="user-role"><?= htmlspecialchars($_SESSION['rol'] ?? 'Rol') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-title">
                <div class="title">Dashboard Para Administradores</div>
                <div class="action-buttons"></div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalAdmins ?></div>
                            <div class="card-label">Administradores</div>
                        </div>
                        <div class="card-icon purple"><i class="fas fa-user-shield"></i></div>
                    </div>
                    <div class="card-change <?= $indAdmins['clase'] ?>">
                        <i class="fas <?= $indAdmins['icon'] ?>"></i>
                        <span><?= $indAdmins['texto'] ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalSecretarias ?></div>
                            <div class="card-label">Secretarias</div>
                        </div>
                        <div class="card-icon blue"><i class="fas fa-user-tie"></i></div>
                    </div>
                    <div class="card-change <?= $indSecretarias['clase'] ?>">
                        <i class="fas <?= $indSecretarias['icon'] ?>"></i>
                        <span><?= $indSecretarias['texto'] ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalAlumnos ?></div>
                            <div class="card-label">Alumnos</div>
                        </div>
                        <div class="card-icon green"><i class="fas fa-user-graduate"></i></div>
                    </div>
                    <div class="card-change <?= $indAlumnos['clase'] ?>">
                        <i class="fas <?= $indAlumnos['icon'] ?>"></i>
                        <span><?= $indAlumnos['texto'] ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalDocentes ?></div>
                            <div class="card-label">Docentes</div>
                        </div>
                        <div class="card-icon orange"><i class="fas fa-chalkboard-teacher"></i></div>
                    </div>
                    <div class="card-change <?= $indDocentes['clase'] ?>">
                        <i class="fas <?= $indDocentes['icon'] ?>"></i>
                        <span><?= $indDocentes['texto'] ?></span>
                    </div>
                </div>
            </div>

            <script>
            window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
            </script>
            <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>

            <!-- ===== JS Notificaciones / Mensajes ===== -->
            <script>
            (function() {
                const bellWrap = document.getElementById('notifBell');
                const mailWrap = document.getElementById('notifMail');
                const bellBadge = bellWrap?.querySelector('.badge');
                const mailBadge = mailWrap?.querySelector('.badge');

                const API_BASE = '/Plataforma_UT/api/notificaciones_admin.php';
                const AUTO_MARK_ON_OPEN = true; // marca como leídas al abrir la campana

                function setBadge(el, value) {
                    const n = Number(value) || 0;
                    if (!el) return;
                    el.textContent = n;
                    el.style.display = n > 0 ? 'inline-block' : 'none';
                }

                async function fetchCounts() {
                    try {
                        const res = await fetch(`${API_BASE}?accion=counts`, {
                            cache: 'no-store'
                        });
                        if (!res.ok) throw new Error();
                        const d = await res.json();
                        if (d.status === 'ok') {
                            setBadge(bellBadge, d.bell); // no leídos (campana)
                            setBadge(mailBadge, d.mail); // total historial (sobre)
                        }
                    } catch {}
                }

                fetchCounts();
                let timer = setInterval(fetchCounts, 5000);
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        clearInterval(timer);
                    } else {
                        fetchCounts();
                        timer = setInterval(fetchCounts, 5000);
                    }
                });

                function pickIconClass(a = '') {
                    a = String(a).toLowerCase();
                    if (a.includes('baja') || a.includes('eliminar')) return 'danger';
                    if (a.includes('suspens')) return 'warn';
                    if (a.includes('editar') || a.includes('actualiz') || a.includes('reactivar')) return 'info';
                    return 'success';
                }

                function pickIcon(a = '') {
                    a = String(a).toLowerCase();
                    if (a.includes('baja') || a.includes('eliminar')) return 'fa-user-minus';
                    if (a.includes('suspens')) return 'fa-circle-pause';
                    if (a.includes('editar') || a.includes('actualiz') || a.includes('reactivar')) return 'fa-pen';
                    return 'fa-check-circle';
                }

                function escapeHtml(s) {
                    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                }

                let dropdownBell = null,
                    dropdownMail = null;

                /* ===== CAMPANA: solo NO leídas ===== */
                bellWrap?.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    if (dropdownMail) dropdownMail.style.display = 'none';

                    if (!dropdownBell) {
                        dropdownBell = document.createElement('div');
                        dropdownBell.className = 'notif-dropdown';
                        document.body.appendChild(dropdownBell);
                    }
                    const rect = bellWrap.getBoundingClientRect();
                    dropdownBell.style.top = (rect.bottom + 12) + 'px';
                    dropdownBell.style.right = (window.innerWidth - rect.right + 12) + 'px';

                    dropdownBell.innerHTML = `
      <div class="notif-head">
        <span>Notificaciones</span>
        <div class="notif-actions">
          <button class="notif-btn" id="bellMarkAll">Marcar leídas</button>
        </div>
      </div>`;

                    try {
                        const r = await fetch(`${API_BASE}?accion=ultimas_no_leidas&limit=10`, {
                            cache: 'no-store'
                        });
                        const d = await r.json();
                        if (d.status === 'ok' && d.items.length) {
                            d.items.forEach(n => {
                                const icoClass = pickIconClass(n.accion);
                                const ico = pickIcon(n.accion);
                                const item = document.createElement('div');
                                item.className = 'notif-item unread';
                                item.innerHTML = `
            <div class="notif-ico ${icoClass}"><i class="fas ${ico}"></i></div>
            <div>
              <div class="notif-title">${escapeHtml(n.titulo || '')}</div>
              <div class="notif-body">${escapeHtml(n.detalle || '')}</div>
              <div class="notif-meta">
                <span class="pill ${icoClass}">${escapeHtml(n.recurso || 'Evento')}</span>
                <span>${escapeHtml(n.accion || '')}</span>
                <span>· ${new Date(n.created_at).toLocaleString()}</span>
              </div>
            </div>
            <button class="notif-del" data-id="${n.id}" title="Eliminar">
              <i class="fas fa-trash"></i>
            </button>`;
                                dropdownBell.appendChild(item);
                            });
                            dropdownBell.innerHTML += `
          <div class="notif-foot">
            <a href="#" class="notif-link" id="openMailAll" role="button">Ver historial</a>
          </div>`;
                        } else {
                            dropdownBell.innerHTML += `
          <div class="notif-empty"><strong>Sin notificaciones</strong>
          <div>No hay movimientos nuevos.</div></div>`;
                        }
                    } catch {
                        dropdownBell.innerHTML +=
                            `<div class="notif-empty" style="color:#c00">Error cargando</div>`;
                    }

                    // botón marcar leídas
                    dropdownBell.querySelector('#bellMarkAll')?.addEventListener('click', async () => {
                        await fetch(`${API_BASE}?accion=marcar_leidas`, {
                            method: 'POST'
                        });
                        setBadge(bellBadge, 0);
                        dropdownBell.querySelectorAll('.notif-item').forEach(el => el
                            .remove());
                        dropdownBell.querySelector('.notif-foot')?.remove();
                        fetchCounts();
                    });

                    // abrir sobre (historial)
                    dropdownBell.querySelector('#openMailAll')?.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        dropdownBell.style.display = 'none';
                        openMailDropdown(100);
                    });

                    dropdownBell.style.display = (dropdownBell.style.display === 'none' || !dropdownBell
                        .style.display) ? 'block' : 'none';

                    // marcar leídas automáticamente al abrir
                    if (AUTO_MARK_ON_OPEN) {
                        await fetch(`${API_BASE}?accion=marcar_leidas`, {
                            method: 'POST'
                        });
                        setBadge(bellBadge, 0);
                        fetchCounts();
                    }
                });

                /* ===== SOBRE: historial completo ===== */
                async function openMailDropdown(limit = 50) {
                    if (dropdownBell) dropdownBell.style.display = 'none';
                    if (!dropdownMail) {
                        dropdownMail = document.createElement('div');
                        dropdownMail.className = 'notif-dropdown';
                        document.body.appendChild(dropdownMail);
                    }
                    const rect = mailWrap.getBoundingClientRect();
                    dropdownMail.style.top = (rect.bottom + 12) + 'px';
                    dropdownMail.style.right = (window.innerWidth - rect.right + 12) + 'px';

                    dropdownMail.innerHTML = `
      <div class="notif-head">
        <span>Mensajes</span>
        <div class="notif-actions">
          <button class="notif-btn" id="mailDelAll"><i class="fas fa-trash"></i> Eliminar todo</button>
        </div>
      </div>`;

                    try {
                        const r = await fetch(`${API_BASE}?accion=historial&limit=${limit}`, {
                            cache: 'no-store'
                        });
                        const d = await r.json();
                        if (d.status === 'ok' && d.items.length) {
                            d.items.forEach(n => {
                                const isMsg = (n.tipo === 'mensaje');
                                const pillClass = isMsg ? 'info' : pickIconClass(n.accion);
                                const icon = isMsg ? 'fa-envelope-open-text' : pickIcon(n.accion);
                                const secretaria = n.secretaria_nombre || '';
                                const item = document.createElement('div');
                                item.className = 'notif-item';
                                item.innerHTML = `
            <div class="notif-ico ${pillClass}"><i class="fas ${icon}"></i></div>
            <div>
              <div class="notif-title">${escapeHtml(n.titulo || '')}</div>
              <div class="notif-body">${escapeHtml(n.detalle || '')}</div>
              <div class="notif-meta">
                <span class="pill ${pillClass}">${isMsg ? 'MENSAJE' : escapeHtml(n.recurso || 'Evento')}</span>
                ${secretaria ? `<span>Por: ${escapeHtml(secretaria)}</span>` : ``}
                ${n.accion ? `<span>${escapeHtml(n.accion)}</span>` : ``}
                <span>· ${new Date(n.created_at).toLocaleString()}</span>
              </div>
            </div>
            <button class="notif-del" data-id="${n.id}" title="Eliminar">
              <i class="fas fa-trash"></i>
            </button>`;
                                dropdownMail.appendChild(item);
                            });
                            dropdownMail.innerHTML +=
                                `<div class="notif-foot"><a href="#" class="notif-link" id="closeMail">Cerrar</a></div>`;
                        } else {
                            dropdownMail.innerHTML +=
                                `<div class="notif-empty"><strong>Sin mensajes</strong><div>No hay actividades registradas.</div></div>`;
                        }
                    } catch {
                        dropdownMail.innerHTML +=
                            `<div class="notif-empty" style="color:#c00">Error cargando</div>`;
                    }

                    dropdownMail.querySelector('#mailDelAll')?.addEventListener('click', async () => {
                        const fd = new FormData();
                        fd.append('accion', 'eliminar_todas');
                        fd.append('tipo', 'todos');
                        await fetch(API_BASE, {
                            method: 'POST',
                            body: fd
                        });
                        dropdownMail.innerHTML +=
                            `<div class="notif-empty"><strong>Sin mensajes</strong></div>`;
                        setBadge(mailBadge, 0);
                        fetchCounts();
                    });

                    dropdownMail.querySelector('#closeMail')?.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        dropdownMail.style.display = 'none';
                    });

                    dropdownMail.style.display = (dropdownMail.style.display === 'none' || !dropdownMail.style
                        .display) ? 'block' : 'none';
                }

                // Click en el sobre
                mailWrap?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openMailDropdown(50);
                });

                // Eliminar individual (ambos menús)
                document.addEventListener('click', async (ev) => {
                    const btn = ev.target.closest('.notif-del');
                    if (!btn) return;
                    const id = btn.dataset.id;
                    if (!id) return;
                    const fd = new FormData();
                    fd.append('accion', 'eliminar');
                    fd.append('id', id);
                    await fetch(API_BASE, {
                        method: 'POST',
                        body: fd
                    });
                    btn.closest('.notif-item')?.remove();
                    fetchCounts();
                });

                // Cerrar menús al click fuera
                document.addEventListener('click', () => {
                    document.querySelectorAll('.notif-dropdown').forEach(dd => dd.style.display = 'none');
                });
            })();
            </script>

        </div>
    </div>
</body>

</html>