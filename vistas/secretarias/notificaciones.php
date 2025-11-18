<?php
session_start();

// Mostrar errores para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirigir si no hay sesión
if (!isset($_SESSION['rol']) || !isset($_SESSION['usuario'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

$rolUsuario = $_SESSION['rol'] ?? '';
$usuarioSesion = $_SESSION['usuario'] ?? [];

$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? 'U', 0, 1));

/* ===== Helpers ===== */
function getSecretariaIdFromSession(array $u): ?int
{
    foreach (['id_secretaria', 'secretaria_id', 'id', 'iduser'] as $k) {
        if (isset($u[$k]) && is_numeric($u[$k])) {
            return (int) $u[$k];
        }
    }
    return null;
}
function h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$idSecretaria = getSecretariaIdFromSession($usuarioSesion);

/* ===== Conexión PDO ===== */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/* ===== Mensajes del administrador para esta secretaría ===== */
$mensajes = [];
$unreadMensajes = 0;

if ($idSecretaria) {
    $sqlMsg = "
        SELECT 
            ms.id_ms,
            m.id_mensaje,
            m.titulo,
            m.cuerpo,
            m.prioridad,
            m.fecha_envio,
            ms.leido_en
        FROM mensajes_secretarias ms
        INNER JOIN mensajes m ON m.id_mensaje = ms.id_mensaje
        WHERE ms.id_secretaria = :id
        ORDER BY m.fecha_envio DESC
        LIMIT 20
    ";
    $st = $pdo->prepare($sqlMsg);
    $st->execute([':id' => $idSecretaria]);
    $mensajes = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare("SELECT COUNT(*) FROM mensajes_secretarias WHERE id_secretaria = :id AND leido_en IS NULL");
    $st->execute([':id' => $idSecretaria]);
    $unreadMensajes = (int) $st->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notificaciones</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/ajustes1.css" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">

    <style>
        /* ===== Contenedor general (similar a ajustes) ===== */
        .noti-container {
            max-width: 1200px;
            margin: 1.5rem auto 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .noti-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .noti-header-left {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .noti-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0;
        }

        .noti-title-icon {
            font-size: 1.1rem;
            padding: 6px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
        }

        .noti-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0;
        }

        .badge-unread {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            padding: 0.3rem 0.7rem;
            border-radius: 999px;
            background: #fee2e2;
            color: #b91c1c;
            font-weight: 600;
        }

        .badge-unread i {
            color: #ef4444;
        }

        /* ===== GRID de cards ===== */
        .mensajes-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 1.2rem;
        }

        .mensaje-item {
            border-radius: 20px;
            padding: 1.1rem 1.4rem;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            cursor: pointer;
            transition: all .15s ease;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            min-height: 150px;
        }

        .mensaje-item.mensaje-no-leido {
            border-top: 4px solid #2563eb;
        }

        .mensaje-item.mensaje-leido {
            border-top: 4px solid #9ca3af;
        }

        .mensaje-item.mensaje-activo {
            box-shadow: 0 18px 32px rgba(37, 99, 235, 0.2);
            border-color: #2563eb;
            transform: translateY(-2px);
        }

        .mensaje-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .mensaje-titulo {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .mensaje-titulo i {
            color: #2563eb;
            font-size: 1.1rem;
        }

        .mensaje-estado-pill {
            font-size: 0.7rem;
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .mensaje-estado-pill.leido {
            background: #ecfdf5;
            color: #15803d;
        }

        .mensaje-estado-pill.no-leido {
            background: #fef2f2;
            color: #b91c1c;
        }

        .mensaje-estado-pill i {
            font-size: 0.8rem;
        }

        .mensaje-prioridad {
            font-size: 0.7rem;
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }

        .prioridad-alta {
            background: #fef3c7;
            color: #92400e;
        }

        .prioridad-media {
            background: #e0f2fe;
            color: #0369a1;
        }

        .prioridad-normal {
            background: #e5e7eb;
            color: #374151;
        }

        .mensaje-cuerpo {
            font-size: 0.88rem;
            color: #4b5563;
            margin: 0;
        }

        .mensaje-meta {
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .mensaje-meta i {
            margin-right: 0.25rem;
        }

        /* Footer / botón Responder */
        .mensaje-footer {
            margin-top: 0.6rem;
            display: none;
            justify-content: flex-end;
        }

        .mensaje-item.mensaje-activo .mensaje-footer {
            display: flex;
        }

        .btn-responder {
            appearance: none;
            border: none;
            border-radius: 999px;
            padding: 0.5rem 1.3rem;
            font-size: 0.85rem;
            font-weight: 600;
            background: #22c55e;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 12px 22px rgba(34, 197, 94, .25);
            transition: transform .1s ease, box-shadow .1s ease, background .1s ease;
        }

        .btn-responder i {
            font-size: 0.9rem;
        }

        .btn-responder:hover {
            background: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(34, 197, 94, .3);
        }

        /* Área de respuesta en la card */
        .mensaje-respuesta {
            margin-top: 0.6rem;
            display: none;
            flex-direction: column;
            gap: 0.5rem;
        }

        .mensaje-respuesta textarea {
            box-sizing: border-box;
            width: 100%;
            height: 110px;
            min-height: 110px;
            max-height: 110px;
            resize: none;
            overflow: auto;
            background: #f6f9fc;
            border: 1px solid #e5edf4;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 0.85rem;
            line-height: 1.4;
            color: #1f2937;
        }

        .mensaje-respuesta textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .18);
        }

        .mensaje-respuesta-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-small {
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-cancel {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #475569;
        }

        .btn-send {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        .btn-send:hover {
            background: #1d4ed8;
        }

        .btn-cancel:hover {
            background: #f8fafc;
        }

        .empty-state {
            margin-top: 0.75rem;
            font-size: 0.85rem;
            color: #9ca3af;
        }

        /* Paginación */
        .mensajes-paginacion {
            margin-top: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .paginacion-buttons {
            display: flex;
            gap: 0.4rem;
        }

        .paginacion-btn {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .1s ease;
        }

        .paginacion-btn i {
            font-size: 0.85rem;
            color: #4b5563;
        }

        .paginacion-btn:hover:not(:disabled) {
            background: #f3f4f6;
        }

        .paginacion-btn:disabled {
            opacity: .4;
            cursor: default;
        }

        .paginacion-info {
            font-weight: 500;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            overflow-y: auto;
            z-index: 1000
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
                <input type="text" id="buscarMensaje" placeholder="Buscar en mensajes..." />
            </div>
            <div class="header-actions">
                <div class="user-profile" id="userProfile" data-nombre="<?= h($nombreCompleto) ?>"
                    data-rol="<?= h($rolUsuario) ?>">
                    <div class="profile-img"><?= h($iniciales) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= h($nombreCompleto ?: 'Usuario') ?></div>
                        <div class="user-role"><?= h($rolUsuario ?: 'Rol') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-title">
                <div class="title">Notificaciones</div>
            </div>

            <!-- ==== NUEVO DISEÑO: header + cards sueltas ==== -->
            <div class="noti-container">
                <div class="noti-header-row">
                    <div class="noti-header-left">
                        <h2 class="noti-title">
                            <span class="noti-title-icon"><i class="fas fa-envelope-open-text"></i></span>
                            Mensajes del administrador
                        </h2>
                        <p class="noti-subtitle">
                            Se muestran los mensajes que el administrador ha enviado a tu cuenta de secretaría.
                        </p>
                    </div>
                    <div class="badge-unread">
                        <i class="fas fa-circle"></i>
                        <?= (int) $unreadMensajes ?> sin leer
                    </div>
                </div>

                <div class="mensajes-list" id="mensajesList">
                    <?php if (empty($mensajes)): ?>
                        <div class="empty-state">
                            No tienes mensajes registrados por el momento.
                        </div>
                    <?php else: ?>
                        <?php foreach ($mensajes as $idx => $m): ?>
                            <?php
                            $esLeido = !empty($m['leido_en']);
                            $prioridad = strtolower($m['prioridad'] ?? 'normal');

                            if ($prioridad === 'alta') {
                                $prioridadClass = 'prioridad-alta';
                                $prioridadLabel = 'ALTA';
                            } elseif ($prioridad === 'media') {
                                $prioridadClass = 'prioridad-media';
                                $prioridadLabel = 'MEDIA';
                            } else {
                                $prioridadClass = 'prioridad-normal';
                                $prioridadLabel = 'NORMAL';
                            }

                            $fechaEnvio = $m['fecha_envio'] ?? '';
                            if ($fechaEnvio) {
                                $ts = strtotime($fechaEnvio);
                                if ($ts) {
                                    $fechaEnvio = date('d/m/Y H:i', $ts);
                                }
                            }
                            ?>
                            <article class="mensaje-item <?= $esLeido ? 'mensaje-leido' : 'mensaje-no-leido' ?>"
                                data-index="<?= (int) $idx ?>" data-id-ms="<?= (int) $m['id_ms'] ?>"
                                data-titulo="<?= h($m['titulo'] ?? '') ?>" data-leido="<?= $esLeido ? '1' : '0' ?>">
                                <div class="mensaje-header">
                                    <div class="mensaje-titulo">
                                        <i class="fas fa-envelope"></i>
                                        <?= h($m['titulo'] ?: 'Mensaje sin título') ?>
                                        <span class="mensaje-prioridad <?= $prioridadClass ?>">
                                            <?= h($prioridadLabel) ?>
                                        </span>
                                    </div>
                                    <span class="mensaje-estado-pill <?= $esLeido ? 'leido' : 'no-leido' ?>">
                                        <i class="fas <?= $esLeido ? 'fa-check-circle' : 'fa-circle-exclamation' ?>"></i>
                                        <?= $esLeido ? 'Leído' : 'No leído' ?>
                                    </span>
                                </div>

                                <p class="mensaje-cuerpo">
                                    <?= nl2br(h($m['cuerpo'] ?? '(Sin contenido)')) ?>
                                </p>

                                <div class="mensaje-meta">
                                    <span><i class="fas fa-user-tie"></i>Administrador</span>
                                    <?php if ($fechaEnvio): ?>
                                        <span><i class="fas fa-clock"></i><?= h($fechaEnvio) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="mensaje-footer">
                                    <button type="button" class="btn-responder">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </div>

                                <div class="mensaje-respuesta">
                                    <textarea placeholder="Escribe tu respuesta para el administrador..."></textarea>
                                    <div class="mensaje-respuesta-actions">
                                        <button type="button" class="btn-small btn-cancel">Cancelar</button>
                                        <button type="button" class="btn-small btn-send">Enviar</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($mensajes)): ?>
                    <div class="mensajes-paginacion">
                        <div class="paginacion-info" id="pagInfo"></div>
                        <div class="paginacion-buttons">
                            <button type="button" class="paginacion-btn" id="btnPrev">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button type="button" class="paginacion-btn" id="btnNext">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>

    <script>
        (function () {
            const API_ALERTAS = "/Plataforma_UT/controladores/secretarias/alertas.php";

            const list = document.getElementById("mensajesList");
            const cards = list ? Array.from(list.querySelectorAll(".mensaje-item")) : [];
            const btnPrev = document.getElementById("btnPrev");
            const btnNext = document.getElementById("btnNext");
            const pagInfo = document.getElementById("pagInfo");
            const buscarInput = document.getElementById("buscarMensaje");

            const perPage = 6; // ===== 6 cards por página =====
            let currentPage = 0;
            let filteredCards = cards.slice(); // para búsqueda

            async function postAlertas(action, data) {
                const fd = new FormData();
                fd.append("accion", action);
                Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
                const r = await fetch(API_ALERTAS, {
                    method: "POST",
                    body: fd
                });
                const j = await r.json();
                if (j.status !== "success") {
                    throw new Error(j.message || "Error en la petición");
                }
                return j;
            }

            function updatePagination() {
                if (!filteredCards.length) {
                    if (pagInfo) pagInfo.textContent = "";
                    if (btnPrev) btnPrev.disabled = true;
                    if (btnNext) btnNext.disabled = true;
                    return;
                }

                const total = filteredCards.length;
                const totalPages = Math.max(1, Math.ceil(total / perPage));
                currentPage = Math.min(currentPage, totalPages - 1);

                const start = currentPage * perPage;
                const end = start + perPage;

                cards.forEach(c => c.style.display = "none");
                filteredCards.forEach((c, i) => {
                    if (i >= start && i < end) c.style.display = "flex";
                });

                if (pagInfo) {
                    const a = start + 1;
                    const b = Math.min(end, total);
                    pagInfo.textContent = `Mostrando ${a} - ${b} de ${total}`;
                }

                if (btnPrev) btnPrev.disabled = (currentPage === 0);
                if (btnNext) btnNext.disabled = (currentPage >= totalPages - 1);
            }

            btnPrev && btnPrev.addEventListener("click", () => {
                if (currentPage > 0) {
                    currentPage--;
                    updatePagination();
                }
            });

            btnNext && btnNext.addEventListener("click", () => {
                const totalPages = Math.max(1, Math.ceil(filteredCards.length / perPage));
                if (currentPage < totalPages - 1) {
                    currentPage++;
                    updatePagination();
                }
            });

            function setActiveCard(card) {
                cards.forEach(c => c.classList.remove("mensaje-activo"));
                if (!card) return;
                card.classList.add("mensaje-activo");

                // Marcar como leído si aún no
                if (card.dataset.leido === "0") {
                    const idMs = card.dataset.idMs;
                    if (idMs) {
                        postAlertas("marcar_mensaje_leido", {
                            id_ms: idMs
                        })
                            .then(() => {
                                card.dataset.leido = "1";
                                const pill = card.querySelector(".mensaje-estado-pill");
                                if (pill) {
                                    pill.classList.remove("no-leido");
                                    pill.classList.add("leido");
                                    pill.innerHTML = '<i class="fas fa-check-circle"></i> Leído';
                                }
                            })
                            .catch(() => {
                                /* silencio */
                            });
                    }
                }
            }

            cards.forEach(card => {
                card.addEventListener("click", (e) => {
                    if (
                        e.target.closest(".btn-responder") ||
                        e.target.closest(".mensaje-respuesta") ||
                        e.target.closest(".btn-send") ||
                        e.target.closest(".btn-cancel")
                    ) return;
                    setActiveCard(card);
                });

                const btnResp = card.querySelector(".btn-responder");
                const respBox = card.querySelector(".mensaje-respuesta");
                const btnSend = card.querySelector(".btn-send");
                const btnCancel = card.querySelector(".btn-cancel");
                const textarea = respBox ? respBox.querySelector("textarea") : null;

                btnResp && btnResp.addEventListener("click", (e) => {
                    e.stopPropagation();
                    setActiveCard(card);
                    // cerrar otros cuadros
                    cards.forEach(c => {
                        if (c !== card) {
                            const otherBox = c.querySelector(".mensaje-respuesta");
                            if (otherBox) otherBox.style.display = "none";
                        }
                    });
                    if (respBox) {
                        respBox.style.display = "flex";
                        textarea && textarea.focus();
                    }
                });

                btnCancel && btnCancel.addEventListener("click", (e) => {
                    e.stopPropagation();
                    if (respBox) {
                        respBox.style.display = "none";
                        textarea && (textarea.value = "");
                    }
                });

                btnSend && btnSend.addEventListener("click", async (e) => {
                    e.stopPropagation();
                    if (!textarea) return;
                    const texto = textarea.value.trim();
                    if (!texto) {
                        Swal.fire("Aviso", "Escribe una respuesta antes de enviar.", "info");
                        return;
                    }
                    const idMs = card.dataset.idMs || "";
                    const titulo = card.dataset.titulo || "";

                    btnSend.disabled = true;
                    try {
                        await postAlertas("responder_mensaje", {
                            id_ms: idMs,
                            titulo: titulo,
                            respuesta: texto
                        });
                        Swal.fire("Enviado", "La respuesta se envió al administrador.", "success");
                        textarea.value = "";
                        respBox.style.display = "none";
                        card.dataset.leido = "1";
                    } catch (err) {
                        Swal.fire("Error", err.message || "No se pudo enviar la respuesta.",
                            "error");
                    } finally {
                        btnSend.disabled = false;
                    }
                });
            });

            // Búsqueda
            buscarInput && buscarInput.addEventListener("input", () => {
                const q = buscarInput.value.toLowerCase().trim();
                filteredCards = cards.filter(c => c.innerText.toLowerCase().includes(q));
                currentPage = 0;
                updatePagination();
            });

            // Inicializar
            if (cards.length) {
                filteredCards = cards.slice();
                updatePagination();
                setTimeout(() => {
                    const firstVisible = filteredCards[0];
                    firstVisible && setActiveCard(firstVisible);
                }, 0);
            }
        })();
    </script>
</body>

</html>