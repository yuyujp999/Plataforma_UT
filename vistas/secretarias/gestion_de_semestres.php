<?php
session_start();

// Mostrar errores para debug
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

// ===== Permisos por rol =====
$rol = strtolower($rolUsuario);
$esAdmin = ($rol === 'admin');
$esSecretaria = in_array($rol, ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

$permisos = [
    'crear' => $esAdmin || $esSecretaria, // Secretarías SÍ crean
    'editar' => $esAdmin || $esSecretaria, // Secretarías SÍ editan
    'eliminar' => $esAdmin,                  // Secretarías NO eliminan
];

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener semestres (3 primeras columnas) + nombre de cat_nombres_semestre + nombre de carrera
$stmt = $pdo->query("
    SELECT 
        s.id_semestre,          -- 1ra columna
        s.semestre,             -- 2da columna
        s.id_carrera,           -- 3ra columna
        c.nombre_carrera,
        ns.nombre AS nombre_semestre
    FROM semestres s
    LEFT JOIN carreras c ON s.id_carrera = c.id_carrera
    LEFT JOIN cat_nombres_semestre ns ON ns.id_nombre_semestre = s.id_nombre_semestre
    ORDER BY s.id_semestre ASC
");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de carreras para los selects de los modales
$carrerasStmt = $pdo->query("SELECT id_carrera, nombre_carrera FROM carreras ORDER BY nombre_carrera ASC");
$carrerasList = $carrerasStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Semestres (Secretarías)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
</head>

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
</style>
<style>
    .pagination-container {
        display: flex;
        justify-content: flex-end;
        margin-top: 15px;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pagination-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.2s ease;
    }

    .pagination-btn:hover {
        background-color: #218838;
    }

    .pagination-btn.active {
        background-color: #1e7e34;
        font-weight: bold;
    }
</style>

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
                <input type="text" id="buscarGrado" placeholder="Buscar Semestres..." />
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
                <div class="title">Gestión de Semestres</div>
                <div class="action-buttons">
                    <?php if ($permisos['crear']): ?>
                        <button class="btn btn-outline btn-sm" id="btnNuevo">
                            <i class="fas fa-plus"></i> Nuevo Semestre
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-book"></i> Semestres</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaGrados">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Semestre</th>
                                <th>Carrera</th>
                                <th>Nombre del Semestre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($semestres)): ?>
                                <?php foreach ($semestres as $row): ?>
                                    <tr data-id="<?= htmlspecialchars($row['id_semestre']) ?>"
                                        data-semestre="<?= htmlspecialchars($row['semestre']) ?>"
                                        data-carrera="<?= htmlspecialchars($row['id_carrera']) ?>"
                                        data-nombre="<?= htmlspecialchars($row['nombre_semestre'] ?? '') ?>">
                                        <td><?= $row['id_semestre'] ?></td>
                                        <td><?= htmlspecialchars($row['semestre']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre_carrera'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['nombre_semestre'] ?? '') ?></td>
                                        <td>
                                            <?php if ($permisos['editar']): ?>
                                                <button class="btn btn-outline btn-sm btn-editar">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($permisos['eliminar']): ?>
                                                <button class="btn btn-outline btn-sm btn-eliminar">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No hay semestres registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination-container" id="paginationSemestres"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO SEMESTRE -->
    <?php if ($permisos['crear']): ?>
        <div class="modal-overlay" id="modalNuevo">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModal">&times;</button>
                <h2>Nuevo Semestre</h2>
                <form id="formNuevo">
                    <fieldset>
                        <label for="semestre">Semestre</label>
                        <input type="number" name="semestre" id="semestre" min="1" required title="Número de semestre">

                        <label for="id_carrera">Carrera</label>
                        <select name="id_carrera" id="id_carrera" required title="Selecciona la carrera">
                            <option value="">Seleccione...</option>
                            <?php foreach ($carrerasList as $c): ?>
                                <option value="<?= $c['id_carrera'] ?>"><?= htmlspecialchars($c['nombre_carrera']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="id_nombre_semestre">Nombre del Semestre</label>
                        <input type="text" name="id_nombre_semestre" id="id_nombre_semestre" readonly
                            placeholder="Autogenerada" title="Se autogenera con Carrera + Semestre">
                    </fieldset>

                    <div class="actions">
                        <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL EDITAR SEMESTRE -->
    <?php if ($permisos['editar']): ?>
        <div class="modal-overlay" id="modalEditar">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
                <h2>Editar Semestre</h2>
                <form id="formEditar">
                    <fieldset>
                        <label for="editSemestre">Semestre</label>
                        <input type="number" name="semestre" id="editSemestre" min="1" required title="Número de semestre">

                        <label for="editIdCarrera">Carrera</label>
                        <select name="id_carrera" id="editIdCarrera" required title="Selecciona la carrera">
                            <option value="">Seleccione...</option>
                            <?php foreach ($carrerasList as $c): ?>
                                <option value="<?= $c['id_carrera'] ?>"><?= htmlspecialchars($c['nombre_carrera']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="editNombreSemestre">Nombre del Semestre</label>
                        <input type="text" name="id_nombre_semestre" id="editNombreSemestre" readonly
                            title="Se autogenera con Carrera + Semestre">
                    </fieldset>

                    <div class="actions">
                        <button type="button" class="btn-cancel" id="cancelModalEditar">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Exponer rol y permisos al JS
        window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES) ?>";
        window.PERMISOS = <?= json_encode($permisos) ?>;

    // Paginación y buscador (lo tuyo tal cual)
    document.addEventListener("DOMContentLoaded", () => {
            const table = document.getElementById("tablaGrados");
            if (!table) return;

            const tbody = table.querySelector("tbody");
            const pagination = document.getElementById("paginationSemestres");
            const searchInput = document.getElementById("buscarGrado");

            const ROWS_PER_PAGE = 5;
            let currentPage = 1;
            const allRows = Array.from(tbody.querySelectorAll("tr"));

            const getFilteredRows = () => {
                const q = (searchInput?.value || "").trim().toLowerCase();
                if (!q) return allRows;
                return allRows.filter(tr => tr.innerText.toLowerCase().includes(q));
            };

            const paginate = (rows, page, perPage) => {
                const total = rows.length;
                const totalPages = Math.max(1, Math.ceil(total / perPage));
                if (page > totalPages) page = totalPages;
                if (page < 1) page = 1;

                allRows.forEach(tr => {
                    tr.style.display = "none";
                });

                const start = (page - 1) * perPage;
                const end = start + perPage;
                rows.slice(start, end).forEach(tr => {
                    tr.style.display = "";
                });

                renderPagination(totalPages, page);
                currentPage = page;
            };

            const renderPagination = (totalPages, page) => {
                if (!pagination) return;
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

                pagination.appendChild(mkBtn(page - 1, "«", page === 1));

                const windowSize = 1;
                const addDots = () => {
                    const s = document.createElement("span");
                    s.textContent = "…";
                    s.style.padding = "6px";
                    s.style.color = "#999";
                    pagination.appendChild(s);
                };

                for (let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || Math.abs(i - page) <= windowSize) {
                        pagination.appendChild(mkBtn(i, null, false, i === page));
                    } else if (
                        (i === 2 && page > windowSize + 2) ||
                        (i === totalPages - 1 && page < totalPages - windowSize - 1)
                    ) {
                        addDots();
                    }
                }

                pagination.appendChild(mkBtn(page + 1, "»", page === totalPages));
            };

            const goToPage = (p) => paginate(getFilteredRows(), p, ROWS_PER_PAGE);

            searchInput?.addEventListener("keyup", () => {
                paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
            });

            paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
        });
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <!-- Recuerda que tu JS de Semestres debe estar en /js/secretarias/Semestres.js y usar controller de secretarías -->
    <script src="../../js/secretarias/Semestres.js"></script>
</body>

</html>