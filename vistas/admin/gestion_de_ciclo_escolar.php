<?php
session_start();

// Mostrar errores para debug (apaga en prod)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirigir si no hay sesión
if (!isset($_SESSION['rol'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

$rolUsuario = $_SESSION['rol'] ?? '';
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener ciclos
$stmt = $pdo->query("SELECT id_ciclo, clave, fecha_inicio, fecha_fin, activo FROM ciclos_escolares ORDER BY id_ciclo DESC");
$ciclos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Ciclos Escolares</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
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

        .table-container {
            overflow-x: auto
        }

        .badge {
            display: inline-block;
            padding: .25rem .5rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600
        }

        .badge-ok {
            background: #e6ffed;
            color: #087f23;
            border: 1px solid #87d39b
        }

        .badge-no {
            background: #ffe8e8;
            color: #b00020;
            border: 1px solid #ffb3b3
        }

        /* toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #ccc;
            border-radius: 30px;
            transition: .3s
        }

        .slider::before {
            content: "";
            position: absolute;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .3s
        }

        .switch input:checked+.slider {
            background: #28a745
        }

        .switch input:checked+.slider::before {
            transform: translateX(22px)
        }

        .slider:hover {
            box-shadow: 0 0 4px rgba(0, 0, 0, .2)
        }

        /* opcional: elimina la línea dura del header */
        .header {
            border-bottom: 0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .04)
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- SIDEBAR -->
        <div class="sidebar" id="sidebar">
            <div class="overlay" id="overlay"></div>
            <div class="logo">
                <h1>UT<span>Panel</span></h1>
            </div>
            <div class="nav-menu" id="menu">
                <div class="menu-heading">Menú</div>
            </div>
        </div>

        <!-- HEADER -->
        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarCiclo" placeholder="Buscar ciclos... (clave, fechas, activo)" />
            </div>
            <div class="header-actions">
                <div class="user-profile" id="userProfile" data-nombre="<?= htmlspecialchars($nombreCompleto) ?>"
                    data-rol="<?= htmlspecialchars($rolUsuario) ?>">
                    <div class="profile-img"><?= htmlspecialchars($iniciales) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($nombreCompleto ?: 'Usuario') ?></div>
                        <div class="user-role"><?= htmlspecialchars($rolUsuario ?: 'Rol') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN -->
        <div class="main-content">
            <div class="page-title">
                <div class="title">Gestión de Ciclos Escolares</div>
                <div class="action-buttons">
                    <button class="btn btn-outline btn-sm" id="btnNuevo">
                        <i class="fas fa-plus"></i> Nuevo Ciclo
                    </button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-calendar-alt"></i> Ciclos</h3>
                </div>

                <div class="table-container">
                    <table class="data-table" id="tablaCiclos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Clave</th>
                                <th>Fecha inicio</th>
                                <th>Fecha fin</th>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ciclos)): ?>
                                <?php foreach ($ciclos as $row): ?>
                                    <?php
                                    $id = (int) $row['id_ciclo'];
                                    $clave = (string) $row['clave'];
                                    $fi = (string) $row['fecha_inicio'];
                                    $ff = (string) $row['fecha_fin'];
                                    $activo = (int) $row['activo'] === 1;
                                    ?>
                                    <tr data-id="<?= htmlspecialchars($id) ?>" data-clave="<?= htmlspecialchars($clave) ?>"
                                        data-fecha-inicio="<?= htmlspecialchars($fi) ?>"
                                        data-fecha-fin="<?= htmlspecialchars($ff) ?>" data-activo="<?= $activo ? '1' : '0' ?>">
                                        <td><?= htmlspecialchars($id) ?></td>
                                        <td><?= htmlspecialchars($clave) ?></td>
                                        <td><?= htmlspecialchars($fi) ?></td>
                                        <td><?= htmlspecialchars($ff) ?></td>
                                        <td>
                                            <?php if ($activo): ?>
                                                <span class="badge badge-ok">Sí</span>
                                            <?php else: ?>
                                                <span class="badge badge-no">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline btn-sm btn-editar"><i class="fas fa-edit"></i>
                                                Editar</button>
                                            <button class="btn btn-outline btn-sm btn-eliminar"><i class="fas fa-trash"></i>
                                                Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No hay ciclos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container" id="paginationCiclos"></div>
            </div>
        </div>
    </div>

    <!-- MODAL: NUEVO -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nuevo Ciclo Escolar</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="clave">Clave</label>
                    <input type="text" name="clave" id="clave" maxlength="12" required>
                </fieldset>
                <fieldset>
                    <label for="fecha_inicio">Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" required>
                </fieldset>
                <fieldset>
                    <label for="fecha_fin">Fecha de fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" required>
                </fieldset>
                <fieldset style="display:flex;align-items:center;gap:1rem;">
                    <label for="activo" style="font-weight:600;">Activo</label>
                    <label class="switch">
                        <input type="checkbox" id="activo" name="activo" value="1" checked>
                        <span class="slider"></span>
                    </label>
                </fieldset>
                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDITAR -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Ciclo Escolar</h2>
            <form id="formEditar">
                <input type="hidden" id="editIdCiclo" name="id_ciclo">
                <fieldset>
                    <label for="editClave">Clave</label>
                    <input type="text" name="clave" id="editClave" maxlength="12" required>
                </fieldset>
                <fieldset>
                    <label for="editFechaInicio">Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" id="editFechaInicio" required>
                </fieldset>
                <fieldset>
                    <label for="editFechaFin">Fecha de fin</label>
                    <input type="date" name="fecha_fin" id="editFechaFin" required>
                </fieldset>
                <fieldset style="display:flex;align-items:center;gap:1rem;">
                    <label for="editActivo" style="font-weight:600;">Activo</label>
                    <label class="switch">
                        <input type="checkbox" id="editActivo" name="activo" value="1">
                        <span class="slider"></span>
                    </label>
                </fieldset>
                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModalEditar">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Exponer rol si lo usas en otros JS
        window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario) ?>";
    </script>

    <script>
        /* ====== Buscador + Paginación (cliente) ====== */
        document.addEventListener("DOMContentLoaded", () => {
            const table = document.getElementById("tablaCiclos");
            const tbody = table?.querySelector("tbody");
            const pagination = document.getElementById("paginationCiclos");
            const searchInput = document.getElementById("buscarCiclo");

            if (!table || !tbody || !pagination) return;

            const ROWS_PER_PAGE = 5;
            let currentPage = 1;
            const allRows = Array.from(tbody.querySelectorAll("tr"));

            // Muestra mensaje de "sin coincidencias"
            function showNoResults(show) {
                let row = tbody.querySelector("tr.__nores");
                if (show) {
                    if (!row) {
                        row = document.createElement("tr");
                        row.className = "__nores";
                        row.innerHTML = '<td colspan="6">Sin coincidencias.</td>';
                        tbody.appendChild(row);
                    }
                } else if (row) {
                    row.remove();
                }
            }

            const getFilteredRows = () => {
                const q = (searchInput?.value || "").trim().toLowerCase();
                if (!q) return allRows;
                return allRows.filter(tr => tr.innerText.toLowerCase().includes(q));
            };

            const renderPagination = (totalPagesParam, page) => {
                pagination.innerHTML = "";

                const mkBtn = (num, label = null, disabled = false, active = false) => {
                    const b = document.createElement("button");
                    b.className = "pagination-btn";
                    b.textContent = label ?? num;
                    if (active) b.classList.add("active");
                    b.disabled = disabled;
                    b.addEventListener("click", () => goToPage(num));
                    return b;
                };

                // anterior
                pagination.appendChild(mkBtn(page - 1, "«", page === 1));

                const windowSize = 1;
                const addDots = () => {
                    const s = document.createElement("span");
                    s.textContent = "…";
                    s.style.padding = "6px";
                    s.style.color = "#999";
                    pagination.appendChild(s);
                };

                for (let i = 1; i <= totalPagesParam; i++) {
                    if (i === 1 || i === totalPagesParam || Math.abs(i - page) <= windowSize) {
                        pagination.appendChild(mkBtn(i, null, false, i === page));
                    } else if ((i === 2 && page > windowSize + 2) ||
                        (i === totalPagesParam - 1 && page < totalPagesParam - windowSize - 1)) {
                        addDots();
                    }
                }

                // siguiente
                pagination.appendChild(mkBtn(page + 1, "»", page === totalPagesParam));
            };

            const paginate = (rows, page, perPage) => {
                // ocultar todas
                allRows.forEach(tr => tr.style.display = "none");

                const total = rows.length;
                const totalPages = Math.max(1, Math.ceil(total / perPage));
                if (page > totalPages) page = totalPages;
                if (page < 1) page = 1;

                // sin resultados
                showNoResults(total === 0);

                const start = (page - 1) * perPage;
                rows.slice(start, start + perPage).forEach(tr => tr.style.display = "");
                renderPagination(totalPages, page);
                currentPage = page;
            };

            const goToPage = (p) => paginate(getFilteredRows(), p, ROWS_PER_PAGE);

            // eventos
            searchInput?.addEventListener("input", () => {
                // re-calcular y llevar a página 1
                goToPage(1);
            });

            // init
            goToPage(1);
        });
    </script>

    <!-- Tu JS global -->
    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <!-- JS específico (CRUD y toggle si lo usas) -->
    <script src="../../js/admin/Ciclos.js"></script>
</body>

</html>