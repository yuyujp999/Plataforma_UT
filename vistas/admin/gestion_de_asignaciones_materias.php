<?php
session_start();

// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sesión
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

/* =========================
   OBTENER DATA PARA LA VISTA
   ========================= */

// Listado de asignaciones con catálogos
$sql = "
SELECT 
    a.id_asignacion,
    a.id_materia,
    m.nombre_materia,
    a.id_nombre_grupo_int,
    cg.nombre AS nombre_grupo,
    a.id_nombre_materia,
    cm.nombre AS clave_generada
FROM asignar_materias a
LEFT JOIN materias m ON a.id_materia = m.id_materia
LEFT JOIN cat_nombres_grupo cg ON a.id_nombre_grupo_int = cg.id_nombre_grupo
LEFT JOIN cat_nombres_materias cm ON a.id_nombre_materia = cm.id_nombre_materia
ORDER BY a.id_asignacion ASC";
$asignaciones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Materias para el select
$materias = $pdo->query("SELECT id_materia, nombre_materia FROM materias ORDER BY nombre_materia")->fetchAll(PDO::FETCH_ASSOC);

// Grupos (catálogo) para el select
$grupos = $pdo->query("SELECT id_nombre_grupo AS id, nombre FROM cat_nombres_grupo ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// (Opcional) catálogo de claves por si quieres desplegar/validar existentes
$clavesCat = $pdo->query("SELECT id_nombre_materia, nombre FROM cat_nombres_materias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Asignar Materias</title>
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
    <style>
        .modal form fieldset {
            margin-bottom: 15px;
        }

        .clave-chip {
            font-weight: 600;
            letter-spacing: .5px;
        }

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
                <input type="text" id="buscarAsignacion" placeholder="Buscar Materias..." />
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
                <div class="title">Gestión de Asignaciones Materias</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar"><i class="fas fa-download"></i> Exportar</button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo"><i class="fas fa-plus"></i> Nueva
                        Materia</button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-link"></i> Asignaciones de Materias</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaAsignaciones">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Materia</th>
                                <th>Grupo</th>
                                <th>Clave</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($asignaciones)): ?>
                                <?php foreach ($asignaciones as $row): ?>
                                    <tr data-id="<?= htmlspecialchars($row['id_asignacion']) ?>"
                                        data-id-materia="<?= htmlspecialchars($row['id_materia']) ?>"
                                        data-id-nombre-grupo="<?= htmlspecialchars($row['id_nombre_grupo_int']) ?>"
                                        data-id-nombre-materia="<?= htmlspecialchars($row['id_nombre_materia']) ?>"
                                        data-clave="<?= htmlspecialchars($row['clave_generada']) ?>">
                                        <td><?= htmlspecialchars($row['id_asignacion']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre_materia'] ?: 'Sin materia') ?></td>
                                        <td><?= htmlspecialchars($row['nombre_grupo'] ?: '—') ?></td>
                                        <td><span
                                                class="clave-chip"><?= htmlspecialchars($row['clave_generada'] ?: '—') ?></span>
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
                                    <td colspan="5">No hay asignaciones registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="paginationAsignacionesMateria"></div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVA ASIGNACIÓN -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nueva Asignación</h2>

            <form id="formNuevo">
                <fieldset>
                    <label for="id_materia">Materia</label>
                    <select name="id_materia" id="id_materia" required>
                        <option value="">Seleccione una materia</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?= $m['id_materia'] ?>"><?= htmlspecialchars($m['nombre_materia']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset>
                    <label for="id_nombre_grupo_int">Grupo</label>
                    <select name="id_nombre_grupo_int" id="id_nombre_grupo_int" required>
                        <option value="">Seleccione un grupo</option>
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?= htmlspecialchars($g['id']) ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset>
                    <label for="clave_generada">Clave</label>
                    <input type="text" id="clave_generada" name="clave_generada" readonly required>
                    <!-- Si tu backend ya devuelve/espera el id del catálogo, úsalo; si no, ignóralo -->
                    <input type="hidden" id="id_nombre_materia_nuevo" name="id_nombre_materia">
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR ASIGNACIÓN -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Asignación</h2>

            <form id="formEditar">
                <fieldset>
                    <label for="editMateria">Materia</label>
                    <select name="id_materia" id="editMateria" required>
                        <option value="">Seleccione una materia</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?= htmlspecialchars($m['id_materia']) ?>">
                                <?= htmlspecialchars($m['nombre_materia']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset>
                    <label for="editGrupo">Grupo</label>
                    <select name="id_nombre_grupo_int" id="editGrupo" required>
                        <option value="">Seleccione un grupo</option>
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?= htmlspecialchars($g['id']) ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset>
                    <label for="editClave">Clave</label>
                    <input type="text" id="editClave" name="clave_generada" readonly required>
                    <input type="hidden" id="id_nombre_materia_editar" name="id_nombre_materia">
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

        // Buscador
        document.getElementById('buscarAsignacion').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            document.querySelectorAll('#tablaAsignaciones tbody tr').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });

        // Util: quitar acentos/espacios y mayúsculas
        const normalizeTxt = (t) =>
            t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '').toUpperCase();

        // Construye la clave: 4 letras de materia + '-' + nombreGrupo
        function buildClave(nombreMateria, nombreGrupo) {
            const base = normalizeTxt(nombreMateria).slice(0, 4);
            const grp = normalizeTxt(nombreGrupo);
            return base && grp ? `${base}-${grp}` : '';
        }

        document.addEventListener("DOMContentLoaded", () => {
            // ===== NUEVA =====
            const selMateriaN = document.getElementById("id_materia");
            const selGrupoN = document.getElementById("id_nombre_grupo_int");
            const claveN = document.getElementById("clave_generada");

            function updateClaveNueva() {
                const materiaTxt = selMateriaN.options[selMateriaN.selectedIndex]?.text || "";
                const grupoTxt = selGrupoN.options[selGrupoN.selectedIndex]?.text || "";
                claveN.value = buildClave(materiaTxt, grupoTxt);
                // Si tu backend hace upsert en cat_nombres_materias y devuelve id, aquí podrías setearlo
                document.getElementById('id_nombre_materia_nuevo').value = '';
            }
            selMateriaN.addEventListener("change", updateClaveNueva);
            selGrupoN.addEventListener("change", updateClaveNueva);

            // ===== EDITAR =====
            const selMateriaE = document.getElementById("editMateria");
            const selGrupoE = document.getElementById("editGrupo");
            const claveE = document.getElementById("editClave");

            function updateClaveEditar() {
                const materiaTxt = selMateriaE.options[selMateriaE.selectedIndex]?.text || "";
                const grupoTxt = selGrupoE.options[selGrupoE.selectedIndex]?.text || "";
                claveE.value = buildClave(materiaTxt, grupoTxt);
                document.getElementById('id_nombre_materia_editar').value = '';
            }
            selMateriaE.addEventListener("change", updateClaveEditar);
            selGrupoE.addEventListener("change", updateClaveEditar);

            // Cargar datos en modal editar desde la fila
            document.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', e => {
                    const tr = e.target.closest('tr');
                    const idMateria = tr.dataset.idMateria || '';
                    const idNomGrupo = tr.dataset.idNombreGrupo || '';
                    const clave = tr.dataset.clave || '';

                    document.getElementById('modalEditar').classList.add('active');

                    // Set selects
                    selMateriaE.value = idMateria;
                    selGrupoE.value = idNomGrupo;
                    claveE.value = clave;
                });
            });

            // Abrir/Cerrar modales
            document.getElementById('btnNuevo').addEventListener('click', () => {
                document.getElementById('formNuevo').reset();
                document.getElementById('modalNuevo').classList.add('active');
            });
            document.getElementById('closeModal').addEventListener('click', () => {
                document.getElementById('modalNuevo').classList.remove('active');
            });
            document.getElementById('cancelModal').addEventListener('click', () => {
                document.getElementById('modalNuevo').classList.remove('active');
            });

            document.getElementById('closeModalEditar').addEventListener('click', () => {
                document.getElementById('modalEditar').classList.remove('active');
            });
            document.getElementById('cancelModalEditar').addEventListener('click', () => {
                document.getElementById('modalEditar').classList.remove('active');
            });
        });
        document.addEventListener("DOMContentLoaded", () => {
            const table = document.getElementById("tablaAsignaciones");
            if (!table) return;

            const tbody = table.querySelector("tbody");
            const pagination = document.getElementById("paginationAsignacionesMateria");
            const searchInput = document.getElementById("buscarAsignacion");

            const ROWS_PER_PAGE = 5; // <- cambia si quieres más/menos por página
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

                // Ocultar todo
                allRows.forEach(tr => {
                    tr.style.display = "none";
                });

                // Mostrar sólo las filas de la página
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

                // « anterior
                pagination.appendChild(mkBtn(page - 1, "«", page === 1));

                // números con ventana
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

                // siguiente »
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
    <script src="../../js/admin/AsignarMaterias3.js"></script>
</body>

</html>