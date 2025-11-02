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
    $colRol    = firstExistingCol($pdo,$tbl,['rol_destino','rol','destino']);
    $colUser   = firstExistingCol($pdo,$tbl,['id_usuario','usuario_id','id_user']);

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
        z-index: 1000;
    }

    .notif-wrap {
        position: relative;
    }

    .notification {
        position: relative;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        text-align: center;
    }

    .dropdown {
        position: absolute;
        right: 0;
        margin-top: 8px;
        width: min(360px, 90vw);
        max-height: 420px;
        overflow: auto;
        background: #fff;
        border: 1px solid #e5e9ec;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        display: none;
        z-index: 3000;
    }

    .dropdown.active {
        display: block;
    }

    .dd-header {
        padding: 10px 14px;
        font-weight: 700;
        border-bottom: 1px solid #eef2f5;
        display: flex;
        gap: 8px;
        align-items: center;
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
        display: flex;
        gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #f2f5f7;
        cursor: pointer;
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
        flex: 1;
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
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
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
        margin-left: 6px;
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
                        <div class="dd-header"><i class="fas fa-bell"></i> Notificaciones</div>
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
                                data-id-ms="<?= (int)$m['id_ms'] ?>">
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
        </div>
    </div>

    <script>
    // Toggler dropdowns
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

    // ====== Marcar como leído (AJAX) ======
    const API_MARK = "/Plataforma_UT/controladores/secretarias/alertas.php";

    async function post(action, data) {
        const fd = new FormData();
        fd.append('accion', action);
        Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
        const r = await fetch(API_MARK, {
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

    // click en notificación
    listBell?.addEventListener('click', async (e) => {
        const li = e.target.closest('li.dd-item');
        if (!li) return;
        if (!li.classList.contains('dd-unread')) return; // ya leída

        const payload = {
            id_noti: li.dataset.idNoti || '',
            creada_en: li.dataset.created || '',
            titulo: li.dataset.title || ''
        };
        const r = await post('marcar_notificacion_leida', payload);
        if (r.status === 'success') {
            li.classList.remove('dd-unread');
            if ((r.data?.unread_notis ?? null) !== null) {
                badgeBell.textContent = r.data.unread_notis;
            } else {
                decBadge(badgeBell);
            }
        }
    });

    // click en mensaje
    listMail?.addEventListener('click', async (e) => {
        const li = e.target.closest('li.dd-item');
        if (!li) return;
        if (!li.classList.contains('dd-unread')) return;
        const id_ms = li.dataset.idMs;
        if (!id_ms) return;

        const r = await post('marcar_mensaje_leido', {
            id_ms
        });
        if (r.status === 'success') {
            li.classList.remove('dd-unread');
            if ((r.data?.unread_msgs ?? null) !== null) {
                badgeMail.textContent = r.data.unread_msgs;
            } else {
                decBadge(badgeMail);
            }
        }
    });
    </script>

    <script>
    window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
    </script>
    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>

</html>