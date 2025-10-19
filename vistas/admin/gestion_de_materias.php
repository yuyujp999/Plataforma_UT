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

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener materias (solo id y nombre)
$stmt = $pdo->query("SELECT id_materia, nombre_materia FROM materias");
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <input type="text" id="buscarMateria" placeholder="Buscar Materias..." />
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
                <div class="title">Gestión de Materias</div>
                <div class="action-buttons">
                    <button class="btn btn-outline btn-sm" id="btnNuevo"><i class="fas fa-plus"></i> Nueva
                        Materia</button>
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
                                    <tr data-id="<?= htmlspecialchars($row['id_materia']) ?>"
                                        data-nombre="<?= htmlspecialchars($row['nombre_materia']) ?>">
                                        <td><?= htmlspecialchars($row['id_materia']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre_materia']) ?></td>
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

    <!-- MODAL NUEVA MATERIA -->
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

    <!-- MODAL EDITAR MATERIA -->
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

    <script>
        window.rolUsuarioPHP = "<?= $rolUsuario; ?>";

        document.getElementById('buscarMateria').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaMaterias tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
        document.addEventListener("DOMContentLoaded", () => {
            const table = document.getElementById("tablaMaterias");
            if (!table) return;

            const tbody = table.querySelector("tbody");
            const pagination = document.getElementById("paginationMaterias");
            const searchInput = document.getElementById("buscarMateria");

            const ROWS_PER_PAGE = 5; // Número de filas por página
            let currentPage = 1;
            const allRows = Array.from(tbody.querySelectorAll("tr"));

            // ===== Helpers =====
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

                // Ocultar todas las filas
                allRows.forEach(tr => {
                    tr.style.display = "none";
                });

                // Mostrar solo las filas visibles de la página actual
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

                // Botón « (anterior)
                pagination.appendChild(mkBtn(page - 1, "«", page === 1));

                // Números con puntos suspensivos
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

                // Botón » (siguiente)
                pagination.appendChild(mkBtn(page + 1, "»", page === totalPages));
            };

            const goToPage = (p) => paginate(getFilteredRows(), p, ROWS_PER_PAGE);

            // ===== Buscador =====
            searchInput?.addEventListener("keyup", () => {
                paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
            });

            // ===== Inicializar =====
            paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
        });
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/admin/Materias8.js"></script>
</body>

</html>