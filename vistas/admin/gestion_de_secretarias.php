<?php
session_start();

// Validar sesión
if (!isset($_SESSION['rol'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}
$rolUsuario = $_SESSION['rol'];

// Activar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexión PDO
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=ut_db;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Contar registros
$totalSecretarias = (int) $pdo->query("SELECT COUNT(*) FROM secretarias")->fetchColumn();

// Obtener datos completos de secretarias
$stmt = $pdo->query("SELECT * FROM secretarias ORDER BY id_secretaria ASC");
$secretarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extraer datos de usuario de sesión
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Secretarias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/secretarias.css" />

    <link rel="stylesheet" href="../../css/admin/secretariasModales1.css" />
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
            <button class="hamburger" id="hamburger">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarSecretaria" placeholder="Buscar Secretarias..." />
            </div>
            <div class="header-actions">
                <div class="notification"><i class="fas fa-bell"></i>
                    <div class="badge">3</div>
                </div>
                <div class="notification"><i class="fas fa-envelope"></i>
                    <div class="badge">5</div>
                </div>
                <div class="user-profile" id="userProfile" data-nombre="<?= htmlspecialchars($nombreCompleto) ?>"
                    data-rol="<?= htmlspecialchars($_SESSION['rol'] ?? '') ?>">
                    <div class="profile-img"><?= $iniciales ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($nombreCompleto ?: 'Usuario') ?></div>
                        <div class="user-role"><?= htmlspecialchars($_SESSION['rol'] ?? 'Rol') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-title">
                <div class="title">Gestión de Secretarias</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo">
                        <i class="fas fa-plus"></i> Nuevo Secretaria/o
                    </button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-user-tie"></i> Secretarias</h3>

                </div>

                <!-- Contenedor con scroll horizontal -->
                <div class="table-scroll-wrapper">
                    <table class="data-table" id="tablaSecretarias">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido Paterno</th>
                                <th>Apellido Materno</th>
                                <th>CURP</th>
                                <th>RFC</th>
                                <th>Fecha Nacimiento</th>
                                <th>Sexo</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Correo Institucional</th>
                                <th>Password</th>
                                <th>Departamento</th>
                                <th>Fecha Ingreso</th>
                                <th>Contacto Emergencia</th>
                                <th>Parentesco Emergencia</th>
                                <th>Teléfono Emergencia</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($secretarias) > 0): ?>
                                <?php foreach ($secretarias as $sec): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sec['id_secretaria']) ?></td>
                                        <td><?= htmlspecialchars($sec['nombre']) ?></td>
                                        <td><?= htmlspecialchars($sec['apellido_paterno']) ?></td>
                                        <td><?= htmlspecialchars($sec['apellido_materno']) ?></td>
                                        <td><?= htmlspecialchars($sec['curp']) ?></td>
                                        <td><?= htmlspecialchars($sec['rfc']) ?></td>
                                        <td><?= htmlspecialchars($sec['fecha_nacimiento']) ?></td>
                                        <td><?= htmlspecialchars($sec['sexo']) ?></td>
                                        <td><?= htmlspecialchars($sec['telefono']) ?></td>
                                        <td><?= htmlspecialchars($sec['direccion']) ?></td>
                                        <td><?= htmlspecialchars($sec['correo_institucional']) ?></td>
                                        <td><?= htmlspecialchars($sec['password']) ?></td>
                                        <td><?= htmlspecialchars($sec['departamento']) ?></td>
                                        <td><?= htmlspecialchars($sec['fecha_ingreso']) ?></td>
                                        <td><?= htmlspecialchars($sec['contacto_emergencia']) ?></td>
                                        <td><?= htmlspecialchars($sec['parentesco_emergencia']) ?></td>
                                        <td><?= htmlspecialchars($sec['telefono_emergencia']) ?></td>
                                        <td><?= htmlspecialchars($sec['fecha_ingreso']) ?></td>
                                        <td>
                                            <button class="btn btn-outline btn-sm btnEditar"
                                                data-id="<?= $sec['id_secretaria'] ?>">
                                                <i class="fas fa-edit"></i>Editar
                                            </button>
                                            <button class="btn btn-outline btn-sm btnEliminar"
                                                data-id="<?= $sec['id_secretaria'] ?>">
                                                <i class="fas fa-trash"></i>Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="19">No hay secretarias registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container" id="paginationSecretarias">
                </div>
            </div>

            <!-- Modal Nueva Secretaria -->
            <div class="modal-overlay" id="modalNuevo">
                <div class="modal">
                    <button type="button" class="close-modal" id="closeModal">&times;</button>
                    <h2>Nueva Secretaria</h2>
                    <form id="formNuevo">
                        <fieldset>
                            <!-- Datos Personales -->
                            <label for="nombre">Nombre <span class="required">*</span></label>
                            <input type="text" name="nombre" id="nombre" required>

                            <label for="apellido_paterno">Apellido Paterno <span class="required">*</span></label>
                            <input type="text" name="apellido_paterno" id="apellido_paterno" required>

                            <label for="apellido_materno">Apellido Materno</label>
                            <input type="text" name="apellido_materno" id="apellido_materno">

                            <label for="curp">CURP <span class="required">*</span></label>
                            <input type="text" name="curp" id="curp" required>

                            <label for="rfc">RFC <span class="required">*</span></label>
                            <input type="text" name="rfc" id="rfc" required>

                            <label for="fecha_nacimiento">Fecha Nacimiento <span class="required">*</span></label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required>

                            <label for="sexo">Sexo <span class="required">*</span></label>
                            <select name="sexo" id="sexo" required>
                                <option value="" disabled selected>Selecciona el sexo</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>

                            <label for="telefono">Teléfono</label>
                            <input type="tel" name="telefono" id="telefono" inputmode="numeric" pattern="\d*"
                                maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">

                            <label for="direccion">Dirección</label>
                            <input type="text" name="direccion" id="direccion">

                            <!-- Bloqueados -->
                            <label for="correo_institucional">Correo Institucional</label>
                            <input type="email" name="correo_institucional" id="correo_institucional"
                                placeholder="Autogenerado" readonly>

                            <label for="password">Contraseña</label>
                            <input type="text" name="password" id="password" placeholder="Autogenerada" readonly>

                            <!-- Laborales -->
                            <label for="departamento">Departamento <span class="required">*</span></label>
                            <select name="departamento" id="departamento" required>
                                <option value="" disabled selected>Selecciona el departamento</option>
                                <option>Recursos Humanos</option>
                                <option>Dirección Académica</option>
                                <option>Servicios Escolares</option>
                                <option>Contabilidad</option>
                                <option>Vinculación</option>
                                <option>Coordinación General</option>
                                <option>Otro</option>
                            </select>

                            <!-- Emergencia -->
                            <label for="contacto_emergencia">Contacto Emergencia</label>
                            <input type="text" name="contacto_emergencia" id="contacto_emergencia">

                            <label for="parentesco_emergencia">Parentesco Emergencia</label>
                            <input type="text" name="parentesco_emergencia" id="parentesco_emergencia">

                            <label for="telefono_emergencia">Teléfono Emergencia</label>
                            <input type="tel" name="telefono_emergencia" id="telefono_emergencia" inputmode="numeric"
                                pattern="\d*" maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
                        </fieldset>

                        <div class="actions">
                            <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                            <button type="submit" class="btn-save">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal Editar Secretaria -->
            <div class="modal-overlay" id="modalEditar">
                <div class="modal">
                    <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
                    <h2>Editar Secretaria</h2>
                    <form id="formEditar">
                        <input type="hidden" name="id_secretaria" id="editId">
                        <fieldset>
                            <!-- Datos Personales -->
                            <label for="editNombre">Nombre <span class="required">*</span></label>
                            <input type="text" name="nombre" id="editNombre" required>

                            <label for="editApellidoP">Apellido Paterno <span class="required">*</span></label>
                            <input type="text" name="apellido_paterno" id="editApellidoP" required>

                            <label for="editApellidoM">Apellido Materno</label>
                            <input type="text" name="apellido_materno" id="editApellidoM">

                            <label for="editCurp">CURP <span class="required">*</span></label>
                            <input type="text" name="curp" id="editCurp" required>

                            <label for="editRfc">RFC <span class="required">*</span></label>
                            <input type="text" name="rfc" id="editRfc" required>

                            <label for="editFechaNac">Fecha Nacimiento <span class="required">*</span></label>
                            <input type="date" name="fecha_nacimiento" id="editFechaNac" required>

                            <label for="editSexo">Sexo <span class="required">*</span></label>
                            <select name="sexo" id="editSexo" required>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>

                            <label for="editTelefono">Teléfono</label>
                            <input type="tel" name="telefono" id="editTelefono" inputmode="numeric" pattern="\d*"
                                maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">

                            <label for="editDireccion">Dirección</label>
                            <input type="text" name="direccion" id="editDireccion">

                            <!-- Bloqueados -->
                            <label for="editCorreo">Correo Institucional</label>
                            <input type="email" name="correo_institucional" id="editCorreo" placeholder="Autogenerado"
                                readonly>

                            <label for="editPassword">Contraseña</label>
                            <input type="text" name="password" id="editPassword" placeholder="Autogenerada" readonly>

                            <!-- Laborales -->
                            <label for="editDepartamento">Departamento <span class="required">*</span></label>
                            <select name="departamento" id="editDepartamento" required>
                                <option value="" disabled>Selecciona el departamento</option>
                                <option>Recursos Humanos</option>
                                <option>Dirección Académica</option>
                                <option>Servicios Escolares</option>
                                <option>Contabilidad</option>
                                <option>Vinculación</option>
                                <option>Coordinación General</option>
                                <option>Otro</option>
                            </select>

                            <!-- Emergencia -->
                            <label for="editContactoEmergencia">Contacto Emergencia</label>
                            <input type="text" name="contacto_emergencia" id="editContactoEmergencia">

                            <label for="editParentescoEmergencia">Parentesco Emergencia</label>
                            <input type="text" name="parentesco_emergencia" id="editParentescoEmergencia">

                            <label for="editTelefonoEmergencia">Teléfono Emergencia</label>
                            <input type="tel" name="telefono_emergencia" id="editTelefonoEmergencia" inputmode="numeric"
                                pattern="\d*" maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
                        </fieldset>

                        <div class="actions">
                            <button type="button" class="btn-cancel" id="cancelModalEditar">Cancelar</button>
                            <button type="submit" class="btn-save">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>


            <script>
                // Rol del usuario en JS
                window.rolUsuarioPHP = "<?= $rolUsuario ?>";

                // Filtro de búsqueda
                document.getElementById('buscarSecretaria').addEventListener('keyup', function () {
                    const filtro = this.value.toLowerCase();
                    const filas = document.querySelectorAll('#tablaSecretarias tbody tr');
                    filas.forEach(fila => {
                        const textoFila = fila.innerText.toLowerCase();
                        fila.style.display = textoFila.includes(filtro) ? '' : 'none';
                    });
                });
                document.addEventListener("DOMContentLoaded", () => {
                    const table = document.getElementById("tablaSecretarias");
                    if (!table) return;

                    const tbody = table.querySelector("tbody");
                    const pagination = document.getElementById("paginationSecretarias");
                    const searchInput = document.getElementById("buscarSecretaria");

                    const ROWS_PER_PAGE = 5;
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

                        // Mostrar solo las filas correspondientes
                        const start = (page - 1) * perPage;
                        const end = start + perPage;
                        rows.slice(start, end).forEach(tr => {
                            tr.style.display = "";
                        });

                        renderPagination(totalPages, page);
                        currentPage = page;
                    };

                    const renderPagination = (totalPages, page) => {
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

                        // Botón «
                        pagination.appendChild(mkBtn(page - 1, "«", page === 1));

                        // Números
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

                        // Botón »
                        pagination.appendChild(mkBtn(page + 1, "»", page === totalPages));
                    };

                    const goToPage = (p) => {
                        paginate(getFilteredRows(), p, ROWS_PER_PAGE);
                    };

                    // ===== Buscador =====
                    if (searchInput) {
                        searchInput.addEventListener("keyup", () => {
                            paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
                        });
                    }

                    // ===== Inicializar =====
                    paginate(getFilteredRows(), 1, ROWS_PER_PAGE);
                });
            </script>
            <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
            <script src="../../js/admin/SecretariasJ.js"></script>
</body>

</html>