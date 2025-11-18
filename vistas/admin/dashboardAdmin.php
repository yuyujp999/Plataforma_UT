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

    <!-- ===== Estilos específicos + NUEVO DISEÑO DE SECCIONES ===== -->
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
        color: #b91c1c;
        background: linear-gradient(180deg, #ffffff 0%, #fff5f5 100%);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .08), inset 0 1px 0 rgba(255, 255, 255, .6);
        cursor: pointer;
        transition: all .15s ease;
        white-space: nowrap;
    }

    .notif-dropdown .notif-head .notif-actions #mailDelAll:hover {
        color: #ffffff;
        background: #ef4444;
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

    /* === Badge notificaciones === */

    .header .notification {
        position: relative;
    }

    .header .notification .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #f43f5e;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
        line-height: 1;
        padding: 0;
    }

    .header .notification .badge span {
        position: relative;
        top: 1px;
        left: 1px;
    }

    /* ========= NUEVO DISEÑO PARA LAS SECCIONES DEL DASHBOARD ========= */

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .section-title i {
        font-size: 1rem;
        opacity: .8;
    }

    /* Panel de tareas rápidas */
    .quick-actions {
        margin-top: 2rem;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }

    .quick-action {
        border: none;
        outline: none;
        border-radius: 18px;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: .9rem;
        font-weight: 500;
        background: linear-gradient(135deg, #f9fafb 0%, #eef2ff 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        cursor: pointer;
        transition: all .18s ease;
        text-align: left;
    }

    .quick-action i {
        font-size: 1.3rem;
        padding: .6rem;
        border-radius: 999px;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
    }

    .quick-action span {
        flex: 1;
    }

    .quick-action small {
        display: block;
        font-size: .75rem;
        color: #6b7280;
        margin-top: .15rem;
    }

    .quick-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 34px rgba(79, 70, 229, .18);
        background: linear-gradient(135deg, #eef2ff 0%, #e0f2fe 100%);
    }

    .quick-action:active {
        transform: translateY(0);
        box-shadow: 0 6px 14px rgba(15, 23, 42, .16);
    }

    /* Grid principal: Actividad reciente + Próximos eventos académicos */
    .dashboard-grid {
        margin-top: 2rem;
        display: grid;
        grid-template-columns: 2fr 1.4fr;
        gap: 1.5rem;
    }

    .dashboard-col {
        min-width: 0;
    }

    @media(max-width: 992px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Actividad reciente */
    .activity-list {
        background: #ffffff;
        border-radius: 18px;
        padding: 1rem 1.1rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        max-height: 360px;
        overflow: auto;
    }

    .activity-item {
        display: flex;
        gap: .8rem;
        padding: .55rem 0.2rem;
        border-bottom: 1px dashed #e5e7eb;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
    }

    .activity-icon.success {
        background: #ecfdf3;
        color: #166534;
    }

    .activity-icon.info {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .activity-icon.warn {
        background: #fffbeb;
        color: #92400e;
    }

    .activity-icon.danger {
        background: #fef2f2;
        color: #b91c1c;
    }

    .activity-body {
        flex: 1;
        min-width: 0;
    }

    .activity-title {
        font-size: .88rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: .1rem;
    }

    .activity-text {
        font-size: .78rem;
        color: #6b7280;
        margin-bottom: .15rem;
    }

    .activity-meta {
        font-size: .72rem;
        color: #9ca3af;
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        align-items: center;
    }

    .activity-pill {
        padding: 0.05rem .45rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 600;
    }

    .activity-pill.success {
        background: #dcfce7;
        color: #166534;
    }

    .activity-pill.info {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .activity-pill.warn {
        background: #fef3c7;
        color: #92400e;
    }

    .activity-pill.danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .activity-empty {
        font-size: .85rem;
        color: #6b7280;
        text-align: center;
        padding: 1.4rem .5rem;
    }

    .activity-empty.error {
        color: #b91c1c;
    }

    /* Tarjeta base (reutilizada) */
    .cycle-card {
        background: radial-gradient(circle at top left, #e0f2fe 0%, #eef2ff 35%, #ffffff 100%);
        border-radius: 18px;
        padding: 1.2rem 1.3rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        display: flex;
        flex-direction: column;
        gap: .8rem;
    }

    .cycle-name {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
    }

    /* ===== Próximos eventos académicos ===== */

    .events-list {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        margin-top: .2rem;
    }

    .event-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .45rem .5rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, .7);
    }

    .event-date {
        width: 46px;
        min-width: 46px;
        height: 46px;
        border-radius: 14px;
        background: #0f172a;
        color: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        line-height: 1.1;
    }

    .event-day {
        font-size: 1.05rem;
    }

    .event-month {
        font-size: .7rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        opacity: .85;
    }

    .event-info {
        flex: 1;
        min-width: 0;
    }

    .event-title {
        font-size: .9rem;
        font-weight: 600;
        color: #111827;
    }

    .event-meta {
        font-size: .75rem;
        color: #6b7280;
        margin-top: .05rem;
    }

    .event-tag {
        padding: .18rem .6rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .event-tag.start {
        background: #bbf7d0;
        color: #166534;
    }

    .event-tag.partial {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .event-tag.vacaciones {
        background: #fef3c7;
        color: #92400e;
    }

    .event-tag.fin {
        background: #fee2e2;
        color: #b91c1c;
    }

    .events-link {
        margin-top: .6rem;
        font-size: .78rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: #2563eb;
        text-decoration: none;
        font-weight: 500;
    }

    .events-link i {
        font-size: .8rem;
    }

    .events-link:hover {
        text-decoration: underline;
    }

    /* Utilidad para el buscador (ocultar elementos) */
    .is-hidden {
        display: none !important;
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
                <input type="text" id="globalSearch" placeholder="Buscar..." />
            </div>
            <div class="header-actions">
                <!-- CAMPANITA -->
                <div class="notification" id="notifBell">
                    <i class="fas fa-bell"></i>
                    <div class="badge"><span>0</span></div>
                </div>
                <!-- SOBRE -->
                <div class="notification" id="notifMail">
                    <i class="fas fa-envelope"></i>
                    <div class="badge"><span>0</span></div>
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
                            <div class=" card-label">Alumnos</div>
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

            <!-- ============ PANEL DE TAREAS RÁPIDAS ============ -->
            <div class="quick-actions">
                <h2 class="section-title"><i class="fas fa-bolt"></i> Panel de tareas rápidas</h2>
                <div class="quick-actions-grid">
                    <button class="quick-action" data-target="Administradores" data-url="gestion_de_admin.php">
                        <i class="fas fa-user-shield"></i>
                        <div>
                            <span>Gestionar administradores</span>
                            <small>Alta, baja y edición de administradores</small>
                        </div>
                    </button>
                    <button class="quick-action" data-target="Secretarias" data-url="gestion_de_secretarias.php">
                        <i class="fas fa-user-tie"></i>
                        <div>
                            <span>Gestionar secretarias</span>
                            <small>Control de cuentas de secretaría</small>
                        </div>
                    </button>
                    <button class="quick-action" data-target="Docentes" data-url="gestion_de_profesores.php">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <div>
                            <span>Gestionar docentes</span>
                            <small>Asignación y administración de docentes</small>
                        </div>
                    </button>
                    <button class="quick-action" data-target="Alumnos" data-url="gestion_de_alumnos.php">
                        <i class="fas fa-user-graduate"></i>
                        <div>
                            <span>Gestionar alumnos</span>
                            <small>Inscripciones, bajas y actualizaciones</small>
                        </div>
                    </button>
                    <button class="quick-action" data-target="Carreras" data-url="gestion_de_carreras.php">
                        <i class="fas fa-layer-group"></i>
                        <div>
                            <span>Configurar carreras</span>
                            <small>Alta de carreras y planes</small>
                        </div>
                    </button>
                    <button class="quick-action" data-target="Grupos" data-url="gestion_de_grupos.php">
                        <i class="fas fa-users"></i>
                        <div>
                            <span>Administrar grupos</span>
                            <small>Crear y asignar grupos</small>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ============ GRID: ACTIVIDAD RECIENTE + PRÓXIMOS EVENTOS ============ -->
            <div class="dashboard-grid">
                <!-- ACTIVIDAD RECIENTE -->
                <div class="dashboard-col">
                    <h2 class="section-title"><i class="fas fa-clock-rotate-left"></i> Actividad reciente</h2>
                    <div id="actividadReciente" class="activity-list">
                        <div class="activity-empty">Cargando actividad reciente...</div>
                    </div>
                </div>

                <!-- PRÓXIMOS EVENTOS ACADÉMICOS -->
                <div class="dashboard-col">
                    <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Próximos eventos académicos</h2>
                    <div class="cycle-card">
                        <div class="cycle-name">Calendario escolar 2025 · UT Montemorelos N.L.</div>

                        <div class="events-list">
                            <!-- Ejemplos de eventos, puedes ajustarlos a las fechas reales -->
                            <div class="event-item">
                                <div class="event-date">
                                    <span class="event-day">13</span>
                                    <span class="event-month">ENE</span>
                                </div>
                                <div class="event-info">
                                    <div class="event-title">Inicio de cuatrimestre</div>
                                    <div class="event-meta">Lunes · Inicio de clases para todos los grupos</div>
                                </div>
                                <span class="event-tag start">Inicio</span>
                            </div>

                            <div class="event-item">
                                <div class="event-date">
                                    <span class="event-day">17</span>
                                    <span class="event-month">FEB</span>
                                </div>
                                <div class="event-info">
                                    <div class="event-title">Parcial 1</div>
                                    <div class="event-meta">Evaluaciones parciales programadas</div>
                                </div>
                                <span class="event-tag partial">Parcial</span>
                            </div>

                            <div class="event-item">
                                <div class="event-date">
                                    <span class="event-day">07</span>
                                    <span class="event-month">ABR</span>
                                </div>
                                <div class="event-info">
                                    <div class="event-title">Inicio de vacaciones</div>
                                    <div class="event-meta">Periodo vacacional de primavera</div>
                                </div>
                                <span class="event-tag vacaciones">Vacaciones</span>
                            </div>

                            <div class="event-item">
                                <div class="event-date">
                                    <span class="event-day">31</span>
                                    <span class="event-month">JUL</span>
                                </div>
                                <div class="event-info">
                                    <div class="event-title">Fin de cuatrimestre</div>
                                    <div class="event-meta">Cierre de actas y fin de actividades</div>
                                </div>
                                <span class="event-tag fin">Fin</span>
                            </div>
                        </div>

                        <!-- Cambia la ruta al PDF real de tu calendario -->
                        <a href="/Plataforma_UT/docs/calendario_limpio.pdf" class="events-link" target="_blank"
                            rel="noopener">
                            <i class="fas fa-external-link-alt"></i>
                            Ver calendario completo
                        </a>
                    </div>
                </div>
            </div>

            <script>
            window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
            </script>
            <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>

            <!-- ===== JS Notificaciones / Mensajes + ACTIVIDAD RECIENTE ===== -->
            <script>
            (function() {
                const bellWrap = document.getElementById('notifBell');
                const mailWrap = document.getElementById('notifMail');
                const bellBadge = bellWrap?.querySelector('.badge');
                const mailBadge = mailWrap?.querySelector('.badge');

                const API_BASE = '/Plataforma_UT/api/notificaciones_admin.php';
                const AUTO_MARK_ON_OPEN = true;

                function setBadge(el, value) {
                    const n = Number(value) || 0;
                    if (!el) return;

                    if (n > 0) {
                        el.innerHTML = `<span>${n}</span>`;
                        el.style.display = 'flex';
                    } else {
                        el.innerHTML = `<span>0</span>`;
                        el.style.display = 'none';
                    }
                }

                async function fetchCounts() {
                    try {
                        const res = await fetch(`${API_BASE}?accion=counts`, {
                            cache: 'no-store'
                        });
                        if (!res.ok) throw new Error();
                        const d = await res.json();
                        if (d.status === 'ok') {
                            setBadge(bellBadge, d.bell);
                            setBadge(mailBadge, d.mail);
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
                    return String(s).replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                let dropdownBell = null,
                    dropdownMail = null;

                /* ======== ACTIVIDAD RECIENTE ======== */
                const actividadReciente = document.getElementById('actividadReciente');

                async function cargarActividadReciente() {
                    if (!actividadReciente) return;
                    try {
                        const r = await fetch(`${API_BASE}?accion=historial&limit=5`, {
                            cache: 'no-store'
                        });
                        const d = await r.json();
                        if (d.status === 'ok' && d.items.length) {
                            actividadReciente.innerHTML = '';
                            d.items.forEach(n => {
                                const isMsg = (n.tipo === 'mensaje');
                                const pillClass = isMsg ? 'info' : pickIconClass(n.accion);
                                const icon = isMsg ? 'fa-envelope-open-text' : pickIcon(n.accion);
                                const item = document.createElement('div');
                                item.className = 'activity-item';
                                item.innerHTML = `
                                        <div class="activity-icon ${pillClass}">
                                            <i class="fas ${icon}"></i>
                                        </div>
                                        <div class="activity-body">
                                            <div class="activity-title">${escapeHtml(n.titulo || (isMsg ? 'Mensaje' : 'Movimiento'))}</div>
                                            <div class="activity-text">${escapeHtml(n.detalle || '')}</div>
                                            <div class="activity-meta">
                                                <span class="activity-pill ${pillClass}">
                                                    ${isMsg ? 'MENSAJE' : escapeHtml(n.recurso || 'Evento')}
                                                </span>
                                                ${n.secretaria_nombre ? `<span>Por: ${escapeHtml(n.secretaria_nombre)}</span>` : ``}
                                                ${n.accion ? `<span>${escapeHtml(n.accion)}</span>` : ``}
                                                <span>· ${new Date(n.created_at).toLocaleString()}</span>
                                            </div>
                                        </div>`;
                                actividadReciente.appendChild(item);
                            });
                        } else {
                            actividadReciente.innerHTML =
                                '<div class="activity-empty">Sin actividad reciente.</div>';
                        }
                    } catch (e) {
                        actividadReciente.innerHTML =
                            '<div class="activity-empty error">No se pudo cargar la actividad.</div>';
                    }
                }

                cargarActividadReciente();
                setInterval(cargarActividadReciente, 15000);

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

                    dropdownBell.querySelector('#bellMarkAll')?.addEventListener('click', async () => {
                        await fetch(`${API_BASE}?accion=marcar_leidas`, {
                            method: 'POST'
                        });
                        setBadge(bellBadge, 0);
                        dropdownBell.querySelectorAll('.notif-item').forEach(el => el
                            .remove());
                        dropdownBell.querySelector('.notif-foot')?.remove();
                        fetchCounts();
                        cargarActividadReciente();
                    });

                    dropdownBell.querySelector('#openMailAll')?.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        dropdownBell.style.display = 'none';
                        openMailDropdown(100);
                    });

                    dropdownBell.style.display = (dropdownBell.style.display === 'none' || !dropdownBell
                        .style.display) ? 'block' : 'none';

                    if (AUTO_MARK_ON_OPEN) {
                        await fetch(`${API_BASE}?accion=marcar_leidas`, {
                            method: 'POST'
                        });
                        setBadge(bellBadge, 0);
                        fetchCounts();
                        cargarActividadReciente();
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
                        cargarActividadReciente();
                    });

                    dropdownMail.querySelector('#closeMail')?.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        dropdownMail.style.display = 'none';
                    });

                    dropdownMail.style.display = (dropdownMail.style.display === 'none' || !dropdownMail.style
                        .display) ? 'block' : 'none';
                }

                mailWrap?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openMailDropdown(50);
                });

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
                    cargarActividadReciente();
                });

                document.addEventListener('click', () => {
                    document.querySelectorAll('.notif-dropdown').forEach(dd => dd.style.display = 'none');
                });
            })();
            </script>

            <!-- ===== JS BUSCADOR GLOBAL & PANEL DE TAREAS RÁPIDAS ===== -->
            <script>
            // Buscador global
            (function() {
                const input = document.getElementById('globalSearch');
                const icon = document.querySelector('.search-bar i');
                const menu = document.getElementById('menu');
                if (!input || !menu) return;

                const normalizar = (t) =>
                    t.toLowerCase()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "");

                function ejecutarBusqueda() {
                    const term = normalizar(input.value.trim());
                    if (!term) return;

                    const items = menu.querySelectorAll('a, button, .menu-item');
                    for (const el of items) {
                        const texto = normalizar(el.textContent || '');
                        if (texto.includes(term)) {
                            const clickable = (el.tagName === 'A' || el.tagName === 'BUTTON') ?
                                el :
                                el.querySelector('a, button');
                            if (clickable) {
                                clickable.click();
                                return;
                            }
                        }
                    }

                    const quickButtons = document.querySelectorAll('.quick-action');
                    for (const btn of quickButtons) {
                        const texto = normalizar(btn.textContent || '');
                        if (texto.includes(term)) {
                            const url = btn.dataset.url;
                            if (url) {
                                window.location.href = url;
                            } else {
                                btn.click();
                            }
                            return;
                        }
                    }
                }

                input.addEventListener('input', () => {
                    const term = normalizar(input.value.trim());
                    const items = menu.querySelectorAll('a, button, .menu-item');
                    items.forEach(el => {
                        const texto = normalizar(el.textContent || '');
                        const match = !term || texto.includes(term);
                        const cont = el.closest('li, .menu-item, a');
                        if (cont) cont.classList.toggle('is-hidden', !match);
                    });
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        ejecutarBusqueda();
                    }
                });

                icon?.addEventListener('click', ejecutarBusqueda);
            })();

            // Panel de tareas rápidas
            (function() {
                const menu = document.getElementById('menu');
                const buttons = document.querySelectorAll('.quick-action');
                if (!buttons.length) return;

                function irAlMenu(label) {
                    if (!menu) return;
                    const objetivo = (label || '').toLowerCase();
                    const items = menu.querySelectorAll('a, button, .menu-item');
                    for (const el of items) {
                        const texto = (el.textContent || '').toLowerCase();
                        if (texto.includes(objetivo)) {
                            el.click();
                            break;
                        }
                    }
                }

                buttons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const url = btn.dataset.url;
                        if (url) {
                            window.location.href = url;
                            return;
                        }
                        const target = btn.dataset.target || '';
                        irAlMenu(target);
                    });
                });
            })();
            </script>

        </div>
    </div>
</body>

</html>