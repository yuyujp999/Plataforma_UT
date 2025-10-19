<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['rol'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}
$rolUsuario = $_SESSION['rol'] ?? '';
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Asignaciones</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png" />

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

        .mini-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .mini-card {
            border: 1px solid #cde9d6;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .04);
        }

        .mini-card h4 {
            margin: 0 0 8px;
            color: #155724;
            font-weight: 600;
        }

        .mini-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .mini-card th,
        .mini-card td {
            padding: 6px 8px;
            border-bottom: 1px solid #eef4ef;
            text-align: left;
        }

        .subtle {
            color: #6c757d;
            font-size: 12px;
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
                <input type="text" id="buscarAsignacion" placeholder="Buscar Alumnos Asignados..." />
            </div>
            <div class="header-actions">
                <div class="notification"><i class="fas fa-bell"></i>
                    <div class="badge">3</div>
                </div>
                <div class="notification"><i class="fas fa-envelope"></i>
                    <div class="badge">5</div>
                </div>
                <div class="user-profile" id="userProfile" data-nombre="<?= htmlspecialchars($nombreCompleto) ?>"
                    data-rol="<?= htmlspecialchars($rolUsuario) ?>">
                    <div class="profile-img"><?= $iniciales ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($nombreCompleto ?: 'Usuario') ?></div>
                        <div class="user-role"><?= htmlspecialchars($rolUsuario ?: 'Rol') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-title">
                <div class="title">Gestión de Asignaciones de Alumnos</div>
                <div class="action-buttons" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <label for="selectGrupo" style="font-weight:500;">Grupo:</label>
                    <select id="selectGrupo" class="btn btn-outline"
                        style="padding:8px;border-radius:8px;min-width:320px;">
                        <option value="">Seleccionar grupo</option>
                    </select>
                    <button class="btn btn-outline" id="btnAsignar" disabled>
                        <i class="fas fa-user-check"></i> Asignar grupo
                    </button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-users"></i> Alumnos de la carrera del grupo</h3>
                    <p class="subtle">Capacidad por grupo: 30 alumnos.</p>
                </div>
                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaAlumnos">
                        <thead>
                            <tr>
                                <th>ID Alumno</th>
                                <th>Nombre</th>
                                <th>Matrícula</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4">Selecciona un grupo para listar alumnos.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <div id="resumenAsignaciones" class="mini-cards"></div>
        </div>
    </div>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js" defer></script>

    <script>
        // *** AJUSTA esta ruta si tu controlador está en otro directorio ***
        window.CTRL_ASIG_URL = "../../controladores/admin/controlador_asignaciones_alumnos.php";
    </script>
    <script>
        window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ===== ELEMENTOS =====
            const selGrupo = document.getElementById('selectGrupo');
            const inputBuscar = document.getElementById('buscarAsignacion');
            const tablaBody = document.querySelector('#tablaAlumnos tbody');
            const resumen = document.getElementById('resumenAsignaciones');
            const btnAsignar = document.getElementById('btnAsignar');

            if (!selGrupo || !inputBuscar || !tablaBody) return;

            // ===== UTILIDADES =====
            const _norm = (s) =>
                (s || '')
                    .toString()
                    .normalize('NFD')
                    .replace(/\p{Diacritic}/gu, '')
                    .toLowerCase()
                    .trim();

            const _acronym = (s) =>
                (s || '')
                    .replace(/[()\-]/g, ' ')
                    .split(/\s+/)
                    .filter((w) => /^[a-zA-ZÁÉÍÓÚÑáéíóúñ]+$/.test(w) && _norm(w).length >= 3)
                    .map((w) => _norm(w)[0])
                    .join('');

            async function waitGruposCargados() {
                const start = Date.now();
                while (Date.now() - start < 1500) {
                    if (selGrupo.options.length > 1) return;
                    await new Promise((r) => setTimeout(r, 100));
                }
            }

            // ===== RESETEAR VISTA =====
            function resetVista() {
                selGrupo.value = '';
                tablaBody.innerHTML =
                    '<tr><td colspan="4">Selecciona un grupo para listar alumnos.</td></tr>';
                resumen.innerHTML = '';
                btnAsignar.disabled = true;
            }

            // ===== SELECCIONAR GRUPO POR TEXTO O ACRÓNIMO =====
            async function selectGroupByQuery(q) {
                const needle = _norm(q);
                if (!needle) {
                    // 🔹 si se borra la búsqueda => limpiar todo
                    resetVista();
                    return;
                }

                await waitGruposCargados();

                const opts = Array.from(selGrupo.options).slice(1);
                const enriched = opts.map((o) => {
                    const text = o.textContent || '';
                    return {
                        opt: o,
                        textNorm: _norm(text),
                        acro: _acronym(text)
                    };
                });

                let match =
                    enriched.find((e) => e.textNorm.includes(needle)) ||
                    enriched.find((e) => e.acro && e.acro.includes(needle));

                if (match && selGrupo.value !== match.opt.value) {
                    selGrupo.value = match.opt.value;
                    selGrupo.dispatchEvent(new Event('change'));

                    // opcional: intenta traer resumen del backend si existe el endpoint
                    try {
                        const r = await fetch(
                            `${window.CTRL_ASIG_URL}?action=resumen_grupo&id_grupo=${encodeURIComponent(
                                selGrupo.value
                            )}`
                        );
                        if (r.ok) {
                            const j = await r.json();
                            if (
                                j.ok &&
                                j.resumen &&
                                typeof window.renderResumenAsignaciones === 'function'
                            ) {
                                window.renderResumenAsignaciones(j.resumen);
                            }
                        }
                    } catch (_) {
                        /* silencioso */
                    }
                }
            }

            // ===== DEBOUNCE =====
            let t;
            const debounce = (fn, ms = 250) => (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), ms);
            };

            // ===== EVENTOS DE BÚSQUEDA =====
            inputBuscar.addEventListener(
                'input',
                debounce((e) => selectGroupByQuery(e.target.value), 250)
            );

            inputBuscar.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    selectGroupByQuery(e.target.value);
                }
            });
        });
    </script>


    <script src="../../js/admin/AsignacionesAlumnos0.js" defer></script>
</body>

</html>