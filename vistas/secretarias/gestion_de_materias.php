<?php
session_start();

// Debug (apaga en prod)
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

// ===== Permisos =====
// Admin: crear/editar/eliminar
// Secretarías: crear/editar, NO eliminar
$rol = mb_strtolower((string) $rolUsuario, 'UTF-8');
$esAdmin = ($rol === 'admin');
$esSecretaria = in_array($rol, ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

$permisos = [
    'crear' => $esAdmin || $esSecretaria,
    'editar' => $esAdmin || $esSecretaria,
    'eliminar' => $esAdmin, // <-- Secretarías NO pueden eliminar
];

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Materias ordenadas alfabéticamente
$stmt = $pdo->query("SELECT id_materia, nombre_materia FROM materias ORDER BY nombre_materia ASC");
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Materias</title>
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
                <input type="text" id="buscarMateria" placeholder="Buscar Materias..." />
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

        <!-- MAIN -->
        <div class="main-content">
            <div class="page-title">
                <div class="title">Gestión de Materias</div>
                <div class="action-buttons">
                    <?php if ($permisos['crear']): ?>
                        <button class="btn btn-outline btn-sm" id="btnNuevo">
                            <i class="fas fa-plus"></i> Nueva Materia
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-book"></i> Materias</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaMaterias">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($materias)): ?>
                                <?php foreach ($materias as $row): ?>
                                    <tr data-id="<?= h($row['id_materia']) ?>" data-nombre="<?= h($row['nombre_materia']) ?>">
                                        <td><?= h($row['id_materia']) ?></td>
                                        <td><?= h($row['nombre_materia']) ?></td>
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
                                            <?php else: ?>
                                                <!-- Secretarías no pueden eliminar -->
                                            <?php endif; ?>
                                            <?php if (!$permisos['editar'] && !$permisos['eliminar']): ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No hay materias registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container" id="paginationMaterias"></div>
            </div>
        </div>
    </div>

    <!-- MODALES (según permisos) -->
    <?php if ($permisos['crear']): ?>
        <!-- NUEVA MATERIA -->
        <div class="modal-overlay" id="modalNuevo">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModal">&times;</button>
                <h2>Nueva Materia</h2>
                <form id="formNuevo">
                    <fieldset>
                        <label for="nombre_materia">Nombre de la Materia</label>
                        <input type="text" name="nombre_materia" id="nombre_materia" required>
                    </fieldset>
                    <div class="actions">
                        <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($permisos['editar']): ?>
        <!-- EDITAR MATERIA -->
        <div class="modal-overlay" id="modalEditar">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
                <h2>Editar Materia</h2>
                <form id="formEditar">
                    <fieldset>
                        <label for="editNombreMateria">Nombre de la Materia</label>
                        <input type="text" name="nombre_materia" id="editNombreMateria" required>
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
        // Exponer permisos al JS
        window.rolUsuarioPHP = "<?= h($rolUsuario) ?>";
        window.PERMISOS = <?= json_encode($permisos) ?>;
    </script>

    <script>
        // Buscador
        document.getElementById('buscarMateria').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaMaterias tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });

        // Paginación cliente
        document.addEventListener("DOMContentLoaded", () => {
            const table = document.getElementById("tablaMaterias");
            if (!table) return;

            const tbody = table.querySelector("tbody");
            const pagination = document.getElementById("paginationMaterias");
            const searchInput = document.getElementById("buscarMateria");

            const ROWS_PER_PAGE = 5;
            let currentPage = 1;
            const allRows = Array.from(tbody.querySelectorAll("tr"));

            const getFilteredRows = () => {
                const q = (searchInput?.value || "").trim().toLowerCase();
                if (!q) return allRows;
                return allRows.filter(tr => tr.innerText.toLowerCase().includes(q));
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

            const paginate = (rows, page, perPage) => {
                const total = rows.length;
                const totalPages = Math.max(1, Math.ceil(total / perPage));
                if (page > totalPages) page = totalPages;
                if (page < 1) page = 1;

                allRows.forEach(tr => tr.style.display = "none");

                const start = (page - 1) * perPage;
                const end = start + perPage;
                rows.slice(start, end).forEach(tr => tr.style.display = "");

                renderPagination(totalPages, page);
                currentPage = page;
            };

            const goToPage = (p) => paginate(getFilteredRows(), p, ROWS_PER_PAGE);

            searchInput?.addEventListener("keyup", () => {
                paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
            });

            paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
        });
    </script>

    <!-- JS global -->
    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <!-- JS de secretarías (usa PERMISOS para bloquear eliminar en el front si lo manejas ahí) -->
    <script src="../../js/secretarias/Materias.js"></script>
</body>

</html>