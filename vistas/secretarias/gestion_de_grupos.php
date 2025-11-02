<?php
session_start();

// === Debug (puedes desactivar en prod) ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Redirigir si no hay sesión ---
if (!isset($_SESSION['rol'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

$rolUsuario = $_SESSION['rol'] ?? '';
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));

// ===== Permisos por rol =====
$rol = mb_strtolower((string) $rolUsuario, 'UTF-8');
$esAdmin = ($rol === 'admin');
$esSecretaria = in_array($rol, ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

/* Secretaría: crear/editar ✔; eliminar ✖ — Admin: todo ✔ */
$permisos = [
    'crear' => ($esAdmin || $esSecretaria),
    'editar' => ($esAdmin || $esSecretaria),
    'eliminar' => $esAdmin,
];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Helper escapar
function h($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

/* ===========================
   LISTADOS (SOLO LECTURA)
   =========================== */
$stmt = $pdo->query("
    SELECT 
        g.id_grupo,
        g.id_nombre_semestre,
        g.id_nombre_grupo,
        s.nombre  AS nombre_semestre,
        cg.nombre AS nombre_grupo
    FROM grupos g
    LEFT JOIN cat_nombres_semestre s 
        ON s.id_nombre_semestre = g.id_nombre_semestre
    LEFT JOIN cat_nombres_grupo cg
        ON cg.id_nombre_grupo = g.id_nombre_grupo
    ORDER BY g.id_grupo ASC
");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Catálogo de semestres para selects */
$gradosStmt = $pdo->query("
    SELECT id_nombre_semestre, nombre 
      FROM cat_nombres_semestre
  ORDER BY id_nombre_semestre ASC
");
$gradosList = $gradosStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Grupos (Secretarías)</title>
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
                <input type="text" id="buscarGrado" placeholder="Buscar Grupos..." />
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
                <div class="title">Gestión de Grupos</div>
                <div class="action-buttons">
                    <?php if ($permisos['crear']): ?>
                    <button class="btn btn-outline btn-sm" id="btnNuevo">
                        <i class="fas fa-plus"></i> Nuevo Grupo
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-book"></i> Grupos</h3>
                </div>
                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaGrados">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Semestre</th>
                                <th>Nombre del Grupo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($grupos)): ?>
                            <?php foreach ($grupos as $row):
                                    $nombreSem = $row['nombre_semestre'] ?? '(sin semestre)';
                                    $nombreGrupo = $row['nombre_grupo'] ?? '(sin nombre)';
                                    ?>
                            <tr data-id="<?= (int) $row['id_grupo'] ?>"
                                data-id-nombre-semestre="<?= (int) $row['id_nombre_semestre'] ?>"
                                data-id-nombre-grupo="<?= (int) $row['id_nombre_grupo'] ?>"
                                data-nombre-semestre="<?= h($nombreSem) ?>" data-nombre-grupo="<?= h($nombreGrupo) ?>"
                                data-nombre-grado="<?= h($nombreSem) ?>">
                                <td><?= (int) $row['id_grupo'] ?></td>
                                <td><?= h($nombreSem) ?></td>
                                <td><?= h($nombreGrupo) ?></td>
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
                                <td colspan="4">No hay grupos registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container" id="paginationGrupo"></div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO GRUPO (según permisos) -->
    <?php if ($permisos['crear']): ?>
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nuevo Grupo</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="nombre_grado">Grado</label>
                    <select name="id_nombre_semestre" id="nombre_grado" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($gradosList as $g): ?>
                        <option value="<?= (int) $g['id_nombre_semestre'] ?>" data-nombre="<?= h($g['nombre']) ?>">
                            <?= h($g['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="nombre_grupo">Nombre del Grupo</label>
                    <input type="text" name="nombre_grupo" id="nombre_grupo" readonly
                        placeholder="Se generará automáticamente">
                </fieldset>
                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL EDITAR GRUPO (según permisos) -->
    <?php if ($permisos['editar']): ?>
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Grupo</h2>
            <form id="formEditar">
                <fieldset>
                    <label for="editNombreGrado">Grado</label>
                    <select name="id_nombre_semestre" id="editNombreGrado" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($gradosList as $g): ?>
                        <option value="<?= (int) $g['id_nombre_semestre'] ?>" data-nombre="<?= h($g['nombre']) ?>">
                            <?= h($g['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editNombreGrupo">Nombre del Grupo</label>
                    <input type="text" name="nombre_grupo" id="editNombreGrupo" readonly>
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
    // Exponer rol y permisos al JS (para mostrar/ocultar y bloquear acciones)
    window.rolUsuarioPHP = "<?= h($rolUsuario) ?>";
    window.PERMISOS = <?= json_encode($permisos) ?>;
    </script>

    <script>
    // Búsqueda en tabla
    document.getElementById('buscarGrado')?.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaGrados tbody tr').forEach(fila => {
            fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
        });
    });
    </script>

    <script>
    // Paginación simple en cliente
    document.addEventListener("DOMContentLoaded", () => {
        const table = document.getElementById("tablaGrados");
        if (!table) return;

        const tbody = table.querySelector("tbody");
        const pagination = document.getElementById("paginationGrupo");
        const searchInput = document.getElementById("buscarGrado");

        const ROWS_PER_PAGE = 8;
        let currentPage = 1;
        const allRows = Array.from(tbody.querySelectorAll("tr"));

        const getFilteredRows = () => {
            const q = (searchInput?.value || "").trim().toLowerCase();
            return q ? allRows.filter(tr => tr.innerText.toLowerCase().includes(q)) : allRows;
        };

        const hideAll = () => {
            allRows.forEach(tr => tr.style.display = "none");
        };

        const paginate = (rows, page, perPage) => {
            const total = rows.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));
            page = Math.min(Math.max(1, page), totalPages);

            hideAll();
            const start = (page - 1) * perPage;
            rows.slice(start, start + perPage).forEach(tr => tr.style.display = "");
            renderPagination(totalPages, page);
            currentPage = page;
        };

        const goToPage = (p) => paginate(getFilteredRows(), p, ROWS_PER_PAGE);

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

        searchInput?.addEventListener("keyup", () => goToPage(1));
        goToPage(1);
    });
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <!-- JS específico para Secretarías -->
    <script src="../../js/secretarias/Grupos.js"></script>
</body>

</html>