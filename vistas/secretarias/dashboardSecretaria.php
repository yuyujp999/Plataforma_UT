<?php
session_start();

/* ===== Sesión ===== */
if (!isset($_SESSION['rol']) || !isset($_SESSION['usuario'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

$rolUsuario = $_SESSION['rol'] ?? 'Usuario';
$usuarioSesion = $_SESSION['usuario'] ?? [];

$nombreCompleto = trim(($usuarioSesion['nombre'] ?? 'Usuario') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? 'U', 0, 1));

/* Helper: id_secretaria desde sesión */
function getSecretariaIdFromSession(array $u): ?int {
    foreach (['id_secretaria','secretaria_id','id','iduser'] as $k) {
        if (isset($u[$k]) && is_numeric($u[$k])) return (int)$u[$k];
    }
    return null;
}
$idSecretaria = getSecretariaIdFromSession($usuarioSesion);

/* ===== Debug (apagar en prod) ===== */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ===== Conexión ===== */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4","root","");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Error de conexión: ".$e->getMessage()); }

/* ===== Helpers ===== */
function contar(PDO $pdo, string $tabla): int {
    try { return (int)$pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn(); }
    catch (PDOException $e) { return 0; }
}
function indicadorCambio(int $total, int $umbral=10): array {
    return $total>$umbral ? ['clase'=>'positive','icon'=>'fa-arrow-up','texto'=>'Muchos']
                          : ['clase'=>'negative','icon'=>'fa-arrow-down','texto'=>'Pocos'];
}
function colExists(PDO $pdo,string $table,string $column): bool {
    $st=$pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t AND COLUMN_NAME=:c");
    $st->execute([':t'=>$table,':c'=>$column]); return (bool)$st->fetchColumn();
}
function firstExistingCol(PDO $pdo,string $table,array $candidates): ?string {
    foreach ($candidates as $c) if (colExists($pdo,$table,$c)) return $c;
    return null;
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ===== Contadores ===== */
$totalAlumnos = contar($pdo, "alumnos");
$totalDocentes = contar($pdo, "docentes");
$totalSecretarias = contar($pdo, "secretarias");
$totalAdministradores = contar($pdo, "administradores");

$indAlumnos = indicadorCambio($totalAlumnos);
$indDocentes = indicadorCambio($totalDocentes);
$indSecre   = indicadorCambio($totalSecretarias);
$indAdmins  = indicadorCambio($totalAdministradores);

/* ===== Mensajes y Notificaciones ===== */
$mensajes = []; $unreadMensajes = 0;
$notificaciones = []; $unreadNotis = 0;

if ($idSecretaria) {
    // MENSAJES (incluyo ms.id_ms)
    $sqlMsg = "
        SELECT ms.id_ms, m.id_mensaje, m.titulo, m.cuerpo, m.prioridad, m.fecha_envio, ms.leido_en
        FROM mensajes_secretarias ms
        INNER JOIN mensajes m ON m.id_mensaje = ms.id_mensaje
        WHERE ms.id_secretaria = :id
        ORDER BY m.fecha_envio DESC
        LIMIT 5";
    $st=$pdo->prepare($sqlMsg); $st->execute([':id'=>$idSecretaria]);
    $mensajes = $st->fetchAll(PDO::FETCH_ASSOC);

    $st=$pdo->prepare("SELECT COUNT(*) FROM mensajes_secretarias WHERE id_secretaria=:id AND leido_en IS NULL");
    $st->execute([':id'=>$idSecretaria]);
    $unreadMensajes = (int)$st->fetchColumn();

    // NOTIFICACIONES (campos flexibles)
    $tbl='notificaciones';
    $colId     = firstExistingCol($pdo,$tbl,['id_notificacion','id','notif_id']);
    $colFecha  = firstExistingCol($pdo,$tbl,['creada_en','creado_en','created_at','fecha','fecha_creacion']);
    $colLeida  = firstExistingCol($pdo,$tbl,['leida','leido','is_read']);
    $colTitulo = firstExistingCol($pdo,$tbl,['titulo','title']);
    $colDetalle= firstExistingCol($pdo,$tbl,['detalle','descripcion','description','mensaje','message']);
    $colRol    = firstExistingCol($pdo,$tbl,['rol_destino','rol','destino','para_rol']);
    $colUser   = firstExistingCol($pdo,$tbl,['id_usuario','usuario_id','id_user','actor_id']);

    if ($colUser && $colRol) {
        $select=[];
        $select[] = $colTitulo ? "$colTitulo AS titulo" : "'' AS titulo";
        $select[] = $colDetalle? "$colDetalle AS detalle" : "'' AS detalle";
        $select[] = $colFecha  ? "$colFecha AS creada_en" : "NOW() AS creada_en";
        $select[] = $colLeida  ? "$colLeida AS leida" : "0 AS leida";
        if ($colId) $select[] = "$colId AS id_notificacion";

        $sqlNot = "SELECT ".implode(", ",$select)." FROM {$tbl}
                   WHERE {$colRol}='secretaria' AND {$colUser}=:id
                   ORDER BY ".($colFecha ?: "NOW()")." DESC
                   LIMIT 5";
        $st=$pdo->prepare($sqlNot); $st->execute([':id'=>$idSecretaria]);
        $notificaciones = $st->fetchAll(PDO::FETCH_ASSOC);

        if ($colLeida) {
            $st=$pdo->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$colRol}='secretaria' AND {$colUser}=:id AND {$colLeida}=0");
            $st->execute([':id'=>$idSecretaria]);
            $unreadNotis = (int)$st->fetchColumn();
        } else { $unreadNotis=0; }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Secretarías | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
    <style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 280px;
        overflow-y: auto;
        z-index: 1000
    }

    .notif-wrap {
        position: relative
    }

    .notification {
        position: relative;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center
    }

    .notification .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #e74c3c;
        color: #fff;
        border-radius: 999px;
        font-size: 11px;
        line-height: 1;
        padding: 4px 6px;
        min-width: 18px;
        text-align: center
    }

    .dropdown {
        position: absolute;
        right: 0;
        margin-top: 8px;
        width: min(420px, 92vw);
        max-height: 520px;
        overflow: auto;
        background: #fff;
        border: 1px solid #e5e9ec;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        display: none;
        z-index: 3000
    }

    .dropdown.active {
        display: block
    }

    .dd-header {
        padding: 10px 14px;
        font-weight: 700;
        border-bottom: 1px solid #eef2f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px
    }

    .dd-empty {
        padding: 14px;
        color: #7b8b9a;
        font-size: 14px
    }

    .dd-list {
        list-style: none;
        margin: 0;
        padding: 0
    }

    .dd-item {
        display: grid;
        grid-template-columns: 40px 1fr;
        gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #f2f5f7;
        align-items: start
    }

    .dd-item:last-child {
        border-bottom: none
    }

    .dd-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #f3f6f7
    }

    .dd-body {
        min-width: 0
    }

    .dd-title {
        font-weight: 600;
        font-size: 14px;
        margin: 0 0 4px
    }

    .dd-desc {
        font-size: 12px;
        color: #6b7b8c;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical
    }

    .dd-meta {
        font-size: 11px;
        color: #9aa8b6;
        margin-top: 6px
    }

    .dd-unread .dd-title {
        color: #1d4ed8
    }

    .nd-badge-alta {
        font-size: 11px;
        background: #fdecea;
        color: #b91c1c;
        border: 1px solid #f5c2c7;
        padding: 2px 6px;
        border-radius: 999px;
        margin-left: 6px
    }

    /* Responder inline */
    .dd-actions {
        margin-top: 8px;
        display: flex;
        gap: 8px;
        align-items: center
    }

    .nd-reply-toggle {
        appearance: none;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 10px;
        cursor: pointer;
        transition: .15s
    }

    .nd-reply-toggle:hover {
        background: #f8fafc
    }

    .dd-reply {
        display: none;
        margin-top: 8px;
        padding: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fafafa
    }

    .dd-reply textarea {
        width: 100%;
        min-height: 70px;
        resize: vertical;
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px
    }

    .dd-reply .row {
        margin-top: 8px;
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: flex-end
    }

    .btn {
        appearance: none;
        border: 1px solid transparent;
        padding: 8px 12px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer
    }

    .btn-primary {
        background: #28a745;
        color: #fff
    }

    .btn-primary:hover {
        background: #20af1eff
    }

    .btn-light {
        background: #fff;
        border-color: #cbd5e1;
        color: #335542ff
    }

    .btn-light:hover {
        background: #f8fafc
    }

    .muted {
        color: #6b7280;
        font-size: 12px
    }

    /* === Reply textarea fijo === */
    .dd-reply textarea,
    .textarea-fixed {
        box-sizing: border-box;
        width: 100%;
        height: 120px;
        min-height: 120px;
        max-height: 120px;
        resize: none;
        overflow: auto;
        background: #f6f9fc;
        border: 1px solid #e5edf4;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        line-height: 1.4;
        color: #1f2937;
        outline: none;
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .dd-reply textarea::placeholder,
    .textarea-fixed::placeholder {
        color: #9aa8b6;
    }

    .dd-reply textarea:focus,
    .textarea-fixed:focus {
        border-color: #88ed3aff;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, .12);
    }

    .dd-reply {
        background: #fafcff;
        border: 1px solid #e8eef5;
        border-radius: 12px;
        padding: 10px;
    }

    .dd-reply .row {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
    }

    .nd-reply-toggle {
        --btn-bg: #ffffff;
        --btn-bd: #cbd5e1;
        --btn-tx: #334155;
        --btn-bg-hover: #f8fafc;
        --btn-shadow: 0 1px 2px rgba(0, 0, 0, .06), inset 0 1px 0 rgba(255, 255, 255, .5);

        appearance: none;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .85rem;
        border: 1px solid var(--btn-bd);
        background: var(--btn-bg);
        color: var(--btn-tx);
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        border-radius: 999px;
        cursor: pointer;
        transition: all .15s ease;
        box-shadow: var(--btn-shadow);
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }

    .nd-reply-toggle i {
        font-size: 12px;
    }

    .nd-reply-toggle:hover {
        background: var(--btn-bg-hover);
        transform: translateY(-1px);
    }

    .nd-reply-toggle:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, .12), var(--btn-shadow);
    }

    .nd-reply-toggle:active {
        transform: translateY(0);
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, .12);
    }

    .nd-reply-toggle.primary {
        --btn-bg: #4f46e5;
        --btn-bd: #4f46e5;
        --btn-tx: #fff;
        --btn-bg-hover: #4338ca;
        box-shadow: 0 6px 14px rgba(79, 70, 229, .25);
    }

    .nd-reply-toggle[disabled],
    .nd-reply-toggle.loading {
        opacity: .7;
        cursor: not-allowed;
        transform: none !important;
    }

    .nd-reply-toggle.loading {
        position: relative;
        pointer-events: none;
    }

    .nd-reply-toggle.loading::after {
        content: "";
        width: 14px;
        height: 14px;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        display: inline-block;
        margin-left: .35rem;
        animation: ndspin .7s linear infinite;
    }

    @keyframes ndspin {
        to {
            transform: rotate(360deg);
        }
    }

    /* ===== TITULOS / PANEL TAREAS RÁPIDAS ===== */

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

    /* ========= GRID: SEGUIMIENTO SECRETARÍA + CALENDARIO ========= */

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

    /* ===== Lista de tareas / recordatorios de secretaría ===== */

    .tasks-list {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        margin-top: .2rem;
    }

    .task-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .45rem .5rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, .8);
    }

    .task-icon {
        width: 42px;
        min-width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #0f172a;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .task-info {
        flex: 1;
        min-width: 0;
    }

    .task-title {
        font-size: .9rem;
        font-weight: 600;
        color: #111827;
    }

    .task-meta {
        font-size: .78rem;
        color: #6b7280;
        margin-top: .05rem;
    }

    .task-meta a {
        color: #2563eb;
        text-decoration: none;
        font-weight: 500;
    }

    .task-meta a:hover {
        text-decoration: underline;
    }

    /* ===== Próximos eventos académicos (CALENDARIO) ===== */

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
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="overlay" id="overlay"></div>
            <div class="logo">
                <h1>UT<span>Panel</span></h1>
            </div>
            <div class="nav-menu" id="menu">
                <div class="menu-heading">Menú</div>
            </div>
        </div>

        <!-- Header -->
        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Buscar..." /></div>

            <div class="header-actions">
                <!-- CAMPANA -->
                <div class="notif-wrap" id="wrapBell">
                    <div class="notification" id="btnBell" aria-haspopup="true" aria-expanded="false"
                        title="Notificaciones">
                        <i class="fas fa-bell"></i>
                        <div class="badge" id="badgeBell"><?= (int)$unreadNotis ?></div>
                    </div>
                    <div class="dropdown" id="ddBell" role="menu" aria-label="Notificaciones">
                        <div class="dd-header"><span><i class="fas fa-bell"></i> Notificaciones</span></div>
                        <?php if (empty($notificaciones)): ?>
                        <div class="dd-empty">Sin notificaciones.</div>
                        <?php else: ?>
                        <ul class="dd-list" id="listBell">
                            <?php foreach ($notificaciones as $n): ?>
                            <li class="dd-item <?= (int)($n['leida'] ?? 0) === 0 ? 'dd-unread' : '' ?>" data-type="noti"
                                <?= isset($n['id_notificacion']) ? 'data-id-noti="'.(int)$n['id_notificacion'].'"' : '' ?>
                                data-created="<?= h($n['creada_en'] ?? '') ?>"
                                data-title="<?= h($n['titulo'] ?? '') ?>">
                                <div class="dd-icon"><i class="fas fa-circle-info"></i></div>
                                <div class="dd-body">
                                    <div class="dd-title"><?= h($n['titulo'] ?? 'Notificación') ?></div>
                                    <p class="dd-desc"><?= h($n['detalle'] ?? '') ?></p>
                                    <div class="dd-meta">
                                        <?= h(date('d/m/Y H:i', strtotime($n['creada_en'] ?? date('Y-m-d H:i:s')))) ?>
                                    </div>

                                    <div class="dd-actions">
                                        <button type="button" class="nd-reply-toggle">Responder</button>
                                        <span class="muted">Se enviará al admin</span>
                                    </div>
                                    <div class="dd-reply">
                                        <textarea
                                            placeholder="Escribe tu respuesta para el administrador..."></textarea>
                                        <div class="row">
                                            <button type="button"
                                                class="btn btn-light nd-reply-cancel">Cancelar</button>
                                            <button type="button"
                                                class="btn btn-primary nd-reply-send-noti">Enviar</button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SOBRE -->
                <div class="notif-wrap" id="wrapMail">
                    <div class="notification" id="btnMail" aria-haspopup="true" aria-expanded="false" title="Mensajes">
                        <i class="fas fa-envelope"></i>
                        <div class="badge" id="badgeMail"><?= (int)$unreadMensajes ?></div>
                    </div>
                    <div class="dropdown" id="ddMail" role="menu" aria-label="Mensajes">
                        <div class="dd-header"><i class="fas fa-envelope"></i> Mensajes</div>
                        <?php if (empty($mensajes)): ?>
                        <div class="dd-empty">Sin mensajes.</div>
                        <?php else: ?>
                        <ul class="dd-list" id="listMail">
                            <?php foreach ($mensajes as $m): ?>
                            <li class="dd-item <?= empty($m['leido_en']) ? 'dd-unread' : '' ?>" data-type="msg"
                                data-id-ms="<?= (int)$m['id_ms'] ?>" data-title="<?= h($m['titulo'] ?? '') ?>">
                                <div class="dd-icon">
                                    <i
                                        class="fas <?= (strtolower($m['prioridad'] ?? 'normal') === 'alta') ? 'fa-triangle-exclamation' : 'fa-envelope-open' ?>"></i>
                                </div>
                                <div class="dd-body">
                                    <div class="dd-title">
                                        <?= h($m['titulo'] ?? 'Mensaje') ?>
                                        <?php if (strtolower($m['prioridad'] ?? 'normal') === 'alta'): ?>
                                        <span class="nd-badge-alta">ALTA</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="dd-desc">
                                        <?= h(mb_substr($m['cuerpo'] ?? '', 0, 90)) ?><?= (mb_strlen($m['cuerpo'] ?? '') > 90 ? '…' : '') ?>
                                    </p>
                                    <div class="dd-meta">
                                        <?= h(date('d/m/Y H:i', strtotime($m['fecha_envio'] ?? date('Y-m-d H:i:s')))) ?>
                                    </div>

                                    <div class="dd-actions">
                                        <button type="button" class="nd-reply-toggle">Responder</button>
                                        <span class="muted">Se enviará al admin</span>
                                    </div>
                                    <div class="dd-reply">
                                        <textarea placeholder="Escribe tu respuesta..."></textarea>
                                        <div class="row">
                                            <button type="button"
                                                class="btn btn-light nd-reply-cancel">Cancelar</button>
                                            <button type="button"
                                                class="btn btn-primary nd-reply-send-msg">Enviar</button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Usuario -->
                <div class="user-profile" id="userProfile" data-nombre="<?= h($nombreCompleto) ?>"
                    data-rol="<?= h($rolUsuario) ?>">
                    <div class="profile-img"><?= h($iniciales) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= h($nombreCompleto ?: 'Usuario') ?></div>
                        <div class="user-role"><?= h($rolUsuario) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main -->
        <div class="main-content">
            <div class="page-title">
                <div class="title">Dashboard para Secretarías</div>
                <div class="action-buttons"></div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalAlumnos ?></div>
                            <div class="card-label">Alumnos</div>
                        </div>
                        <div class="card-icon green"><i class="fas fa-user-graduate"></i></div>
                    </div>
                    <div class="card-change <?= $indAlumnos['clase'] ?>"><i
                            class="fas <?= $indAlumnos['icon'] ?>"></i><span><?= $indAlumnos['texto'] ?></span></div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalDocentes ?></div>
                            <div class="card-label">Docentes</div>
                        </div>
                        <div class="card-icon orange"><i class="fas fa-chalkboard-teacher"></i></div>
                    </div>
                    <div class="card-change <?= $indDocentes['clase'] ?>"><i
                            class="fas <?= $indDocentes['icon'] ?>"></i><span><?= $indDocentes['texto'] ?></span></div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalSecretarias ?></div>
                            <div class="card-label">Secretarías</div>
                        </div>
                        <div class="card-icon blue"><i class="fas fa-user-tie"></i></div>
                    </div>
                    <div class="card-change <?= $indSecre['clase'] ?>"><i
                            class="fas <?= $indSecre['icon'] ?>"></i><span><?= $indSecre['texto'] ?></span></div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalAdministradores ?></div>
                            <div class="card-label">Administradores</div>
                        </div>
                        <div class="card-icon purple"><i class="fas fa-user-shield"></i></div>
                    </div>
                    <div class="card-change <?= $indAdmins['clase'] ?>"><i
                            class="fas <?= $indAdmins['icon'] ?>"></i><span><?= $indAdmins['texto'] ?></span></div>
                </div>
            </div>

            <!-- ============ PANEL DE TAREAS RÁPIDAS PARA SECRETARÍAS ============ -->
            <div class="quick-actions">
                <h2 class="section-title"><i class="fas fa-bolt"></i> Panel de tareas rápidas</h2>
                <div class="quick-actions-grid">
                    <button class="quick-action" data-target="Alumnos" data-url="gestion_de_alumnos.php">
                        <i class="fas fa-user-graduate"></i>
                        <div>
                            <span>Gestionar alumnos</span>
                            <small>Inscripciones, bajas y actualizaciones</small>
                        </div>
                    </button>

                    <button class="quick-action" data-target="Docentes" data-url="gestion_de_profesores.php">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <div>
                            <span>Gestionar docentes</span>
                            <small>Altas, bajas y asignación de docentes</small>
                        </div>
                    </button>

                    <button class="quick-action" data-target="Grupos" data-url="gestion_de_grupos.php">
                        <i class="fas fa-users"></i>
                        <div>
                            <span>Gestionar grupos</span>
                            <small>Crear, asignar y administrar grupos</small>
                        </div>
                    </button>

                    <button class="quick-action" data-target="Materias" data-url="gestion_de_materias.php">
                        <i class="fas fa-book-open"></i>
                        <div>
                            <span>Gestionar materias</span>
                            <small>Alta y control de asignaturas</small>
                        </div>
                    </button>

                    <button class="quick-action" data-target="Horarios" data-url="horarios.php">
                        <i class="fas fa-calendar-alt"></i>
                        <div>
                            <span>Gestionar horarios</span>
                            <small>Organización de horarios por grupo</small>
                        </div>
                    </button>

                    <button class="quick-action" data-target="Aulas" data-url="gestion_de_aulas.php">
                        <i class="fas fa-door-open"></i>
                        <div>
                            <span>Gestionar aulas</span>
                            <small>Control de aulas y capacidad</small>
                        </div>
                    </button>

                    <button class="quick-action" data-target="Adeudos" data-url="adeudos_pagos.php">
                        <i class="fas fa-money-bill-wave"></i>
                        <div>
                            <span>Adeudos y pagos</span>
                            <small>Seguimiento de adeudos de alumnos</small>
                        </div>
                    </button>

                    <button class="quick-action" data-target="Notificaciones" data-url="notificaciones.php">
                        <i class="fas fa-bell"></i>
                        <div>
                            <span>Notificaciones</span>
                            <small>Envío y consulta de avisos</small>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ============ GRID: SEGUIMIENTO SECRETARÍA + CALENDARIO ============ -->
            <div class="dashboard-grid">
                <!-- SEGUIMIENTO SECRETARÍA -->
                <div class="dashboard-col">
                    <h2 class="section-title"><i class="fas fa-clipboard-list"></i> Seguimiento de secretaría</h2>
                    <div class="cycle-card">
                        <div class="cycle-name">Resumen de tareas clave</div>

                        <div class="tasks-list">
                            <div class="task-item">
                                <div class="task-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="task-info">
                                    <div class="task-title">Inscripciones y reinscripciones</div>
                                    <div class="task-meta">
                                        Revisa altas recientes y actualiza datos de alumnos
                                        · <a href="gestion_de_alumnos.php">Ir a gestión de alumnos</a>
                                    </div>
                                </div>
                            </div>

                            <div class="task-item">
                                <div class="task-icon">
                                    <i class="fas fa-chalkboard"></i>
                                </div>
                                <div class="task-info">
                                    <div class="task-title">Grupos y horarios</div>
                                    <div class="task-meta">
                                        Asegura que todos los grupos tengan materias y horarios asignados
                                        · <a href="gestion_de_grupos.php">Ver grupos</a> ·
                                        <a href="horarios.php">Ver horarios</a>
                                    </div>
                                </div>
                            </div>

                            <div class="task-item">
                                <div class="task-icon">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div class="task-info">
                                    <div class="task-title">Adeudos y regularización</div>
                                    <div class="task-meta">
                                        Da seguimiento a alumnos con adeudos pendientes
                                        · <a href="adeudos_pagos.php">Ir a adeudos y pagos</a>
                                    </div>
                                </div>
                            </div>

                            <div class="task-item">
                                <div class="task-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="task-info">
                                    <div class="task-title">Avisos a la comunidad</div>
                                    <div class="task-meta">
                                        Envía o revisa notificaciones importantes para alumnos y docentes
                                        · <a href="notificaciones.php">Gestionar notificaciones</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRÓXIMOS EVENTOS ACADÉMICOS (CALENDARIO) -->
                <div class="dashboard-col">
                    <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Próximos eventos académicos</h2>
                    <div class="cycle-card">
                        <div class="cycle-name">Calendario escolar 2025 · UT Montemorelos N.L.</div>

                        <div class="events-list">
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

                        <!-- Cambia la ruta al PDF real de tu calendario si es diferente -->
                        <a href="/Plataforma_UT/docs/calendario_limpio.pdf" class="events-link" target="_blank"
                            rel="noopener">
                            <i class="fas fa-external-link-alt"></i>
                            Ver calendario completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // --- togglers --->
    const btnBell = document.getElementById('btnBell'),
        ddBell = document.getElementById('ddBell'),
        badgeBell = document.getElementById('badgeBell');
    const btnMail = document.getElementById('btnMail'),
        ddMail = document.getElementById('ddMail'),
        badgeMail = document.getElementById('badgeMail');
    const listBell = document.getElementById('listBell'),
        listMail = document.getElementById('listMail');

    function closeAll() {
        ddBell?.classList.remove('active');
        ddMail?.classList.remove('active');
        btnBell?.setAttribute('aria-expanded', 'false');
        btnMail?.setAttribute('aria-expanded', 'false');
    }
    btnBell?.addEventListener('click', e => {
        e.stopPropagation();
        const open = ddBell.classList.contains('active');
        closeAll();
        if (!open) {
            ddBell.classList.add('active');
            btnBell.setAttribute('aria-expanded', 'true');
        }
    });
    btnMail?.addEventListener('click', e => {
        e.stopPropagation();
        const open = ddMail.classList.contains('active');
        closeAll();
        if (!open) {
            ddMail.classList.add('active');
            btnMail.setAttribute('aria-expanded', 'true');
        }
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.notif-wrap')) closeAll();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeAll();
    });

    // --- API --->
    const API_ALERTAS = "/Plataforma_UT/controladores/secretarias/alertas.php";
    async function post(action, data) {
        const fd = new FormData();
        fd.append('accion', action);
        Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
        const r = await fetch(API_ALERTAS, {
            method: 'POST',
            body: fd
        });
        return r.json();
    }

    function decBadge(el) {
        if (!el) return;
        const n = parseInt(el.textContent || "0", 10);
        el.textContent = Math.max(0, n - 1);
    }

    // --- marcar notificación leída --->
    listBell?.addEventListener('click', async (e) => {
        const li = e.target.closest('li.dd-item');
        if (!li) return;
        if (e.target.closest('.dd-reply') || e.target.closest('.nd-reply-toggle') || e.target.closest(
                '.nd-reply-send-noti') || e.target.closest('.nd-reply-cancel')) return;
        if (!li.classList.contains('dd-unread')) return;
        const payload = {
            id_noti: li.dataset.idNoti || '',
            creada_en: li.dataset.created || '',
            titulo: li.dataset.title || ''
        };
        const r = await post('marcar_notificacion_leida', payload);
        if (r.status === 'success') {
            li.classList.remove('dd-unread');
            if ((r.data?.unread_notis ?? null) !== null) badgeBell.textContent = r.data.unread_notis;
            else decBadge(badgeBell);
        }
    });

    // --- marcar mensaje leído --->
    listMail?.addEventListener('click', async (e) => {
        const li = e.target.closest('li.dd-item');
        if (!li) return;
        if (e.target.closest('.dd-reply') || e.target.closest('.nd-reply-toggle') || e.target.closest(
                '.nd-reply-send-msg') || e.target.closest('.nd-reply-cancel')) return;
        if (!li.classList.contains('dd-unread')) return;
        const id_ms = li.dataset.idMs;
        if (!id_ms) return;
        const r = await post('marcar_mensaje_leido', {
            id_ms
        });
        if (r.status === 'success') {
            li.classList.remove('dd-unread');
            if ((r.data?.unread_msgs ?? null) !== null) badgeMail.textContent = r.data.unread_msgs;
            else decBadge(badgeMail);
        }
    });

    // --- toggles responder --->
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nd-reply-toggle');
        if (!btn) return;
        const body = btn.closest('.dd-body');
        const box = body?.querySelector('.dd-reply');
        if (!box) return;
        box.style.display = box.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.nd-reply-cancel');
        if (!btn) return;
        const box = btn.closest('.dd-reply');
        if (!box) return;
        const ta = box.querySelector('textarea');
        if (ta) ta.value = '';
        box.style.display = 'none';
    });

    // --- enviar respuesta de NOTIFICACIÓN --->
    listBell?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.nd-reply-send-noti');
        if (!btn) return;
        const li = btn.closest('li.dd-item');
        const box = btn.closest('.dd-reply');
        const ta = box?.querySelector('textarea');
        if (!li || !ta) return;
        const texto = (ta.value || '').trim();
        if (!texto) {
            alert('Escribe un mensaje.');
            return;
        }
        btn.disabled = true;
        const payload = {
            id_noti: li.dataset.idNoti || '',
            creada_en: li.dataset.created || '',
            titulo: li.dataset.title || '',
            respuesta: texto
        };
        const r = await post('responder_notificacion', payload);
        if (r.status === 'success') {
            if (li.classList.contains('dd-unread')) {
                li.classList.remove('dd-unread');
                decBadge(badgeBell);
            }
            box.innerHTML = `<div class="muted">Respuesta enviada.</div>`;
            setTimeout(() => {
                box.style.display = 'none';
            }, 1200);
        } else {
            alert(r.message || 'No se pudo enviar.');
            btn.disabled = false;
        }
    });

    // --- enviar respuesta de MENSAJE --->
    listMail?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.nd-reply-send-msg');
        if (!btn) return;
        const li = btn.closest('li.dd-item');
        const box = btn.closest('.dd-reply');
        const ta = box?.querySelector('textarea');
        if (!li || !ta) return;
        const texto = (ta.value || '').trim();
        if (!texto) {
            alert('Escribe un mensaje.');
            return;
        }
        btn.disabled = true;
        const payload = {
            id_ms: li.dataset.idMs || '',
            titulo: li.dataset.title || '',
            respuesta: texto
        };
        const r = await post('responder_mensaje', payload);
        if (r.status === 'success') {
            if (li.classList.contains('dd-unread')) {
                li.classList.remove('dd-unread');
                decBadge(badgeMail);
            }
            box.innerHTML = `<div class="muted">Respuesta enviada.</div>`;
            setTimeout(() => {
                box.style.display = 'none';
            }, 1200);
        } else {
            alert(r.message || 'No se pudo enviar.');
            btn.disabled = false;
        }
    });
    </script>

    <script>
    window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
    </script>
    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>

    <!-- ===== JS PANEL DE TAREAS RÁPIDAS ===== -->
    <script>
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
</body>

</html>