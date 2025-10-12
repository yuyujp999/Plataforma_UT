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

// Obtener administradores SIN nivel_acceso
$stmt = $pdo->query("SELECT id_admin, nombre, apellido_paterno, apellido_materno, correo, password, fecha_registro FROM administradores");
$administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
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
            <button class="hamburger" id="hamburger">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarAdmin" placeholder="Buscar..." />
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
                <div class="title">Gestión de Administradores</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-user-shield"></i> Administradores</h3>

                </div>

                <table class="data-table" id="tablaAdmins">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido Paterno</th>
                            <th>Apellido Materno</th>
                            <th>Correo</th>
                            <th>Password</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($administradores)): ?>
                            <?php foreach ($administradores as $row): ?>
                                <tr data-id="<?= htmlspecialchars($row['id_admin']) ?>"
                                    data-nombre="<?= htmlspecialchars($row['nombre']) ?>"
                                    data-apellido-p="<?= htmlspecialchars($row['apellido_paterno']) ?>"
                                    data-apellido-m="<?= htmlspecialchars($row['apellido_materno']) ?>"
                                    data-correo="<?= htmlspecialchars($row['correo']) ?>">
                                    <td><?= htmlspecialchars($row['id_admin']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= htmlspecialchars($row['apellido_paterno']) ?></td>
                                    <td><?= htmlspecialchars($row['apellido_materno']) ?></td>
                                    <td><?= htmlspecialchars($row['correo']) ?></td>
                                    <td><?= htmlspecialchars($row['password']) ?></td>
                                    <td><?= htmlspecialchars($row['fecha_registro']) ?></td>
                                    <td>
                                        <button class="btn btn-outline btn-sm btn-editar">
                                            <i class="fas fa-edit"></i>Editar
                                        </button>
                                        <button class="btn btn-outline btn-sm btn-eliminar">
                                            <i class="fas fa-trash"></i>Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No hay administradores registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination-container" id="pagination"></div>

            </div>
        </div>
    </div>


    <!-- MODAL NUEVO -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nuevo Administrador</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" required>

                    <label for="apellido_paterno">Apellido Paterno</label>
                    <input type="text" name="apellido_paterno" id="apellido_paterno" required>

                    <label for="apellido_materno">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="apellido_materno" required>

                    <label for="correo">Correo</label>
                    <input type="email" name="correo" id="correo" required>

                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" required>
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Administrador</h2>
            <form id="formEditar">
                <fieldset>
                    <label for="editNombre">Nombre</label>
                    <input type="text" name="nombre" id="editNombre" required>

                    <label for="editApellidoPaterno">Apellido Paterno</label>
                    <input type="text" name="apellido_paterno" id="editApellidoPaterno" required>

                    <label for="editApellidoMaterno">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="editApellidoMaterno" required>

                    <label for="editCorreo">Correo</label>
                    <input type="email" name="correo" id="editCorreo" required>

                    <label for="editPassword">Nueva Contraseña (opcional)</label>
                    <input type="password" name="password" id="editPassword">
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

        document.getElementById('buscarAdmin').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaAdmins tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    </script>
    <script src="/Plataforma_UT/js/DashboardY.js"></script>
    <script src="../../js/admin/AdminO.js"></script>

</body>

</html>