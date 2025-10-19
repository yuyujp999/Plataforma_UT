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

// Obtener carreras
$stmt = $pdo->query("SELECT id_carrera, nombre_carrera, descripcion, duracion_anios, fecha_creacion FROM carreras");
$carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Carreras</title>
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
    .is-hidden {
        display: none !important;
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
                <input type="text" id="buscarCarrera" placeholder="Buscar Carreras..." />
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
                <div class="title">Gestión de Carreras</div>
                <div class="action-buttons">

                    <button class="btn btn-outline btn-sm" id="btnNuevo">
                        <i class="fas fa-plus"></i> Nueva Carrera
                    </button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-graduation-cap"></i> Carreras</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaCarreras">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre de la Carrera</th>
                                <th>Descripción</th>
                                <th>Duración (Años)</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($carreras)): ?>
                                <?php foreach ($carreras as $row): ?>
                                    <tr data-id="<?= htmlspecialchars($row['id_carrera']) ?>"
                                        data-nombre="<?= htmlspecialchars($row['nombre_carrera']) ?>"
                                        data-descripcion="<?= htmlspecialchars($row['descripcion']) ?>"
                                        data-duracion="<?= htmlspecialchars($row['duracion_anios']) ?>">
                                        <td><?= htmlspecialchars($row['id_carrera']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre_carrera']) ?></td>
                                        <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                        <td><?= htmlspecialchars($row['duracion_anios']) ?></td>
                                        <td><?= htmlspecialchars($row['fecha_creacion']) ?></td>
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
                                    <td colspan="6">No hay carreras registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="paginationCarreras"></div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVA CARRERA -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nueva Carrera</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="nombre_carrera">Nombre de la Carrera</label>
                    <input type="text" name="nombre_carrera" id="nombre_carrera" required>

                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3"></textarea>

                    <label for="duracion_anios">Duración (Años)</label>
                    <input type="number" name="duracion_anios" id="duracion_anios" min="1" value="">
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR CARRERA -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Carrera</h2>
            <form id="formEditar">
                <fieldset>
                    <label for="editNombreCarrera">Nombre de la Carrera</label>
                    <input type="text" name="nombre_carrera" id="editNombreCarrera" required>

                    <label for="editDescripcion">Descripción</label>
                    <textarea name="descripcion" id="editDescripcion" rows="3"></textarea>

                    <label for="editDuracionAnios">Duración (Años)</label>
                    <input type="number" name="duracion_anios" id="editDuracionAnios" min="1" required>
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

        document.getElementById('buscarCarrera').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaCarreras tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    </script>

    </script>


    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/admin/Carreras3.js"></script>
</body>

</html>