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
            z-index: 1000
        }

        .mini-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 12px;
            margin-top: 14px
        }

        .mini-card {
            border: 1px solid #cde9d6;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .04)
        }

        .mini-card h4 {
            margin: 0 0 4px;
            color: #155724;
            font-weight: 700
        }

        .mini-card .subtle {
            margin-bottom: 8px;
            display: block
        }

        .mini-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px
        }

        .mini-card th,
        .mini-card td {
            padding: 6px 8px;
            border-bottom: 1px solid #eef4ef;
            text-align: left
        }

        .subtle {
            color: #6c757d;
            font-size: 12px
        }

        .is-hidden {
            display: none !important
        }

        .dismissible-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border: 1px solid #cde9d6;
            background: #f6fffa;
            border-radius: 10px;
            gap: 10px;
            margin-bottom: 10px
        }

        .dismissible-head .title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #155724
        }

        .dismissible-head .close {
            border: none;
            background: #e8f5ee;
            color: #0f5132;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            font-weight: 600
        }

        .dismissible-head .close:hover {
            opacity: .9
        }

        .card-title {
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .card-actions {
            display: flex;
            gap: 8px;
            align-items: center
        }

        .btn-toggle {
            border: 1px solid #cde9d6;
            background: #f1fff7;
            color: #0f5132;
            border-radius: 8px;
            padding: 6px 10px;
            font-weight: 600;
            cursor: pointer
        }

        .btn-toggle .chev {
            transition: transform .2s ease
        }

        .btn-toggle.collapsed .chev {
            transform: rotate(-90deg)
        }

        .card-body {
            margin-top: 10px
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

            <!-- CARD: alumnos disponibles -->
            <div class="table-card" id="cardAlumnosGrupo">
                <div class="card-title">
                    <div>
                        <h3 style="margin:0"><i class="fas fa-users"></i> Alumnos de la carrera del grupo</h3>
                        <p class="subtle">Capacidad por grupo: 30 alumnos.</p>
                    </div>
                    <div class="card-actions">
                        <button class="btn-toggle" data-target="#bodyAlumnos"><i class="fas fa-chevron-down chev"></i>
                            Ocultar/Mostrar</button>
                    </div>
                </div>

                <div id="bodyAlumnos" class="card-body">
                    <div class="table-container" style="overflow-x:auto;">
                        <table class="data-table" id="tablaAlumnos">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Alumno</th>
                                    <th>Matrícula</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3">Selecciona un grupo para listar alumnos.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Lista descartable tras asignar -->
                    <div id="wrapResumen" class="is-hidden">
                        <div class="dismissible-head">
                            <div class="title"><i class="fas fa-list-check"></i> Resultado de asignación</div>
                            <button id="btnCerrarResumen" class="close"><i class="fas fa-xmark"></i> Cerrar
                                lista</button>
                        </div>
                        <div id="resumenAsignaciones" class="mini-cards"></div>
                    </div>
                </div>
            </div>

            <!-- CARD: ciclo escolar -->
            <div class="table-card" id="cardCiclo">
                <div class="card-title">
                    <div>
                        <h3 style="margin:0"><i class="fas fa-calendar-alt"></i> Asignar ciclo escolar al grupo</h3>
                        <p class="subtle">Crea/actualiza los registros en <code>alumno_ciclo</code> para todos los
                            alumnos del grupo destino (sin duplicados).</p>
                    </div>
                    <div class="card-actions">
                        <button class="btn-toggle" data-target="#bodyCiclo"><i class="fas fa-chevron-down chev"></i>
                            Ocultar/Mostrar</button>
                    </div>
                </div>

                <div id="bodyCiclo" class="card-body">
                    <div class="action-buttons" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <label for="selectCiclo" style="font-weight:500;">Ciclo escolar:</label>
                        <select id="selectCiclo" class="btn btn-outline"
                            style="padding:8px;border-radius:8px;min-width:320px;">
                            <option value="">Selecciona un ciclo</option>
                        </select>

                        <label for="selectGrupoDestino" style="font-weight:500;">Aplicar a:</label>
                        <select id="selectGrupoDestino" class="btn btn-outline"
                            style="padding:8px;border-radius:8px;min-width:380px;">
                            <option value="">(elige un grupo destino)</option>
                        </select>

                        <button class="btn btn-outline" id="btnAsignarCiclo" disabled>
                            <i class="fas fa-calendar-check"></i> Asignar ciclo al grupo
                        </button>
                    </div>

                    <div class="table-container" style="overflow-x:auto;margin-top:12px;">
                        <table class="data-table" id="tablaPreviewCiclo">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Alumno</th>
                                    <th>Matrícula</th>
                                    <th>Tiene este ciclo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="subtle">Selecciona grupo y ciclo para ver el preview.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="resultadoCiclo" class="mini-cards is-hidden"></div>
                    <div id="resultadoAcciones" class="result-actions is-hidden">
                        <button class="btn btn-outline" id="btnReabrirListas"><i class="fas fa-list"></i> Reabrir
                            listas</button>
                    </div>
                </div>
            </div>

            <!-- CARD: tablero de grupos con ciclo -->
            <div class="table-card" id="cardResumenCiclos" style="margin-top:16px;">
                <div class="card-title">
                    <div>
                        <h3 style="margin:0"><i class="fas fa-layer-group"></i> Grupos con ciclo asignado</h3>
                        <p class="subtle">Se actualiza automáticamente cuando asignas un ciclo.</p>
                    </div>
                    <div class="card-actions">
                        <button class="btn-toggle" data-target="#bodyResumen"><i class="fas fa-chevron-down chev"></i>
                            Ocultar/Mostrar</button>
                    </div>
                </div>

                <div id="bodyResumen" class="card-body">
                    <div id="gridCiclos" class="mini-cards">
                        <div class="subtle">Cargando…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js" defer></script>
    <script>
        window.CTRL_ASIG_URL = "../../controladores/admin/controlador_asignaciones_alumnos.php";
    </script>
    <script>
        window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
    </script>

    <!-- Búsqueda rápida por grupo -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selGrupo = document.getElementById('selectGrupo');
            const inputBuscar = document.getElementById('buscarAsignacion');
            const tablaBody = document.querySelector('#tablaAlumnos tbody');
            const resumen = document.getElementById('resumenAsignaciones');
            const btnAsignar = document.getElementById('btnAsignar');
            if (!selGrupo || !inputBuscar || !tablaBody) return;

            const _norm = s => (s || '').toString().normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
                .trim();
            const _acronym = s => (s || '').replace(/[()\-]/g, ' ')
                .split(/\s+/).filter(w => /^[a-zA-ZÁÉÍÓÚÑáéíóúñ]+$/.test(w) && _norm(w).length >= 3)
                .map(w => _norm(w)[0]).join('');

            async function waitGruposCargados() {
                const t0 = Date.now();
                while (Date.now() - t0 < 1500) {
                    if (selGrupo.options.length > 1) return;
                    await new Promise(r => setTimeout(r, 100));
                }
            }

            function resetVista() {
                selGrupo.value = '';
                tablaBody.innerHTML = '<tr><td colspan="3">Selecciona un grupo para listar alumnos.</td></tr>';
                resumen.innerHTML = '';
                btnAsignar.disabled = true;
            }

            async function selectGroupByQuery(q) {
                const needle = _norm(q);
                if (!needle) {
                    resetVista();
                    return;
                }
                await waitGruposCargados();
                const opts = Array.from(selGrupo.options).slice(1).map(o => ({
                    opt: o,
                    textNorm: _norm(o.textContent || ''),
                    acro: _acronym(o.textContent || '')
                }));
                let match = opts.find(e => e.textNorm.includes(needle)) || opts.find(e => e.acro && e.acro
                    .includes(needle));
                if (match && selGrupo.value !== match.opt.value) {
                    selGrupo.value = match.opt.value;
                    selGrupo.dispatchEvent(new Event('change'));
                    try {
                        const r = await fetch(
                            `${window.CTRL_ASIG_URL}?action=resumen_grupo&id_grupo=${encodeURIComponent(selGrupo.value)}`
                        );
                        if (r.ok) {
                            const j = await r.json();
                            if (j.ok && j.resumen && typeof window.renderResumenAsignaciones === 'function') {
                                window.renderResumenAsignaciones(j.resumen);
                            }
                        }
                    } catch (_) { }
                }
            }

            let t;
            const debounce = (fn, ms = 250) => (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), ms);
            };
            inputBuscar.addEventListener('input', debounce(e => selectGroupByQuery(e.target.value), 250));
            inputBuscar.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    selectGroupByQuery(e.target.value);
                }
            });
        });
    </script>

    <!-- ====== ORDEN SEGURO POR APELLIDOS (Paterno, Materno, Nombres) ====== -->
    <script>
        (function () {
            const norm = s => (s || '').toString()
                .normalize('NFD').replace(/\p{Diacritic}/gu, '')
                .replace(/\s+/g, ' ').trim().toLowerCase();

            function splitSpanishFullName(fullname) {
                const parts = (fullname || '').trim().replace(/\s+/g, ' ').split(' ');
                if (parts.length === 0) return {
                    nombres: '',
                    ap1: '',
                    ap2: ''
                };
                if (parts.length === 1) return {
                    nombres: '',
                    ap1: parts[0],
                    ap2: ''
                };
                if (parts.length === 2) return {
                    nombres: parts[0],
                    ap1: parts[1],
                    ap2: ''
                };
                const ap2 = parts.pop(); // Materno
                const ap1 = parts.pop(); // Paterno
                const nombres = parts.join(' ');
                return {
                    nombres,
                    ap1,
                    ap2
                };
            }

            function nameSortKey(fullname) {
                const {
                    nombres,
                    ap1,
                    ap2
                } = splitSpanishFullName(fullname);
                return `${norm(ap1)}|${norm(ap2)}|${norm(nombres)}`;
            }

            const observers = new WeakMap();
            const debouncers = new WeakMap();

            function reindexNumberColumn(tbody) {
                let i = 1;
                tbody.querySelectorAll('tr').forEach(tr => {
                    if (tr.children.length >= 1) {
                        tr.cells[0].textContent = i++;
                    }
                });
            }

            function sortTBodyBySurname(tbody, hasNumberColumn = true, nameColIndex = 1) {
                const obs = observers.get(tbody);
                if (obs) obs.disconnect(); // Pausar observación

                const rows = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.children.length > nameColIndex);
                if (!rows.length) {
                    // Reactivar observador aunque no haya filas
                    setTimeout(() => {
                        const obs2 = new MutationObserver(() => scheduleSort(tbody, hasNumberColumn,
                            nameColIndex));
                        obs2.observe(tbody, {
                            childList: true
                        });
                        observers.set(tbody, obs2);
                    }, 0);
                    return;
                }

                rows.sort((a, b) => {
                    const keyA = nameSortKey(a.cells[nameColIndex].textContent);
                    const keyB = nameSortKey(b.cells[nameColIndex].textContent);
                    return keyA < keyB ? -1 : keyA > keyB ? 1 : 0;
                });

                const frag = document.createDocumentFragment();
                rows.forEach(tr => frag.appendChild(tr));
                tbody.innerHTML = '';
                tbody.appendChild(frag);

                if (hasNumberColumn) reindexNumberColumn(tbody);

                // Reactivar observador en el siguiente tick
                setTimeout(() => {
                    const obs2 = new MutationObserver(() => scheduleSort(tbody, hasNumberColumn, nameColIndex));
                    obs2.observe(tbody, {
                        childList: true
                    }); // solo childList: ligero y sin bucles
                    observers.set(tbody, obs2);
                }, 0);
            }

            function scheduleSort(tbody, hasNumberColumn = true, nameColIndex = 1) {
                clearTimeout(debouncers.get(tbody));
                const id = setTimeout(() => sortTBodyBySurname(tbody, hasNumberColumn, nameColIndex), 60);
                debouncers.set(tbody, id);
            }

            // 1) Tabla principal: #tablaAlumnos (cols: #, Alumno, Matrícula)
            document.addEventListener('DOMContentLoaded', () => {
                const tbAlumnos = document.querySelector('#tablaAlumnos tbody');
                if (tbAlumnos) {
                    const obs = new MutationObserver(() => scheduleSort(tbAlumnos, true, 1));
                    obs.observe(tbAlumnos, {
                        childList: true
                    });
                    observers.set(tbAlumnos, obs);
                    scheduleSort(tbAlumnos, true, 1);
                    window.forceOrdenAlfabeticoAlumnos = () => scheduleSort(tbAlumnos, true, 1);
                }
            });

            // 2) Preview del ciclo: #tablaPreviewCiclo (cols: #, Alumno, Matrícula, Tiene…)
            document.addEventListener('DOMContentLoaded', () => {
                const tbPreview = document.querySelector('#tablaPreviewCiclo tbody');
                if (tbPreview) {
                    const obs = new MutationObserver(() => scheduleSort(tbPreview, true, 1));
                    obs.observe(tbPreview, {
                        childList: true
                    });
                    observers.set(tbPreview, obs);
                    scheduleSort(tbPreview, true, 1);
                    window.forceOrdenPreviewCiclo = () => scheduleSort(tbPreview, true, 1);
                }
            });

            // 3) Tarjetas tablero: #gridCiclos (cada mini-card tiene table>tbody con cols: #, Alumno, Matrícula)
            document.addEventListener('DOMContentLoaded', () => {
                const grid = document.getElementById('gridCiclos');
                if (!grid) return;

                function attachToAllCardBodies() {
                    grid.querySelectorAll('.mini-card table tbody').forEach(tbody => {
                        if (observers.has(tbody)) return; // ya conectado
                        const obs = new MutationObserver(() => scheduleSort(tbody, true, 1));
                        obs.observe(tbody, {
                            childList: true
                        });
                        observers.set(tbody, obs);
                        scheduleSort(tbody, true, 1);
                    });
                }

                // Detectar nuevas tarjetas únicamente
                const obsGrid = new MutationObserver(() => attachToAllCardBodies());
                obsGrid.observe(grid, {
                    childList: true
                });
                attachToAllCardBodies();

                window.forceOrdenTableroCiclos = () => {
                    grid.querySelectorAll('.mini-card table tbody').forEach(tb => scheduleSort(tb, true,
                        1));
                };
            });
        })();
    </script>
    <!-- ====== /ORDEN SEGURO ====== -->

    <!-- JS principal de la página -->
    <script src="../../js/secretarias/AsignacionesAlumnos.js" defer></script>

    <!-- Toggle de tarjetas (abrir/cerrar y recordar estado) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const KEY = 'asig_cards_state_v1';
            const state = JSON.parse(localStorage.getItem(KEY) || '{}');

            function applyState(btn) {
                const targetSel = btn.getAttribute('data-target');
                const target = document.querySelector(targetSel);
                if (!target) return;
                const id = targetSel;
                const collapsed = !!state[id];
                target.classList.toggle('is-hidden', collapsed);
                btn.classList.toggle('collapsed', collapsed);
            }

            function toggle(e) {
                const btn = e.currentTarget;
                const targetSel = btn.getAttribute('data-target');
                const target = document.querySelector(targetSel);
                if (!target) return;

                const id = targetSel;
                const isHidden = target.classList.toggle('is-hidden');
                btn.classList.toggle('collapsed', isHidden);
                state[id] = isHidden ? 1 : 0;
                localStorage.setItem(KEY, JSON.stringify(state));
            }

            document.querySelectorAll('.btn-toggle').forEach(btn => {
                applyState(btn);
                btn.addEventListener('click', toggle);
            });
        });
    </script>
</body>

</html>