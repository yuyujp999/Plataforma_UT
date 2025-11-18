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
// Ajusta aquí la política: qué puede hacer cada rol.
$esAdmin = strcasecmp($rolUsuario, 'admin') === 0;
$esSecretaria = in_array(strtolower($rolUsuario), ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

$permisos = [
    'crear' => $esAdmin || $esSecretaria, // Secretarías SÍ crea
    'editar' => $esAdmin || $esSecretaria, // Secretarías SÍ edita
    'eliminar' => $esAdmin,                  // Secretarías NO elimina
    'exportar' => $esAdmin || $esSecretaria, // si usas exportar aquí
];

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener aulas (orden alfabético por nombre)
$stmt = $pdo->query("
    SELECT id_aula, nombre, capacidad
    FROM aulas
    ORDER BY nombre ASC
");
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Aulas (Secretarías)</title>

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
                <input type="text" id="buscarAula" placeholder="Buscar Aulas..." />
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
                <div class="title">Gestión de Aulas</div>
                <div class="action-buttons">
                    <?php if ($permisos['exportar']): ?>

                    <?php endif; ?>
                    <?php if ($permisos['crear']): ?>
                        <button class="btn btn-outline btn-sm" id="btnNuevo">
                            <i class="fas fa-plus"></i> Nueva Aula
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-door-open"></i> Aulas</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaAulas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Aula</th>
                                <th>Capacidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($aulas)): ?>
                                <?php foreach ($aulas as $row): ?>
                                    <tr data-id="<?= htmlspecialchars($row['id_aula']) ?>"
                                        data-nombre="<?= htmlspecialchars($row['nombre']) ?>"
                                        data-capacidad="<?= htmlspecialchars($row['capacidad']) ?>">
                                        <td><?= htmlspecialchars($row['id_aula']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><?= htmlspecialchars($row['capacidad']) ?></td>
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
                                    <td colspan="4">No hay aulas registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="paginationAulas"></div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVA AULA -->
    <?php if ($permisos['crear']): ?>
        <div class="modal-overlay" id="modalNuevo">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModal">&times;</button>
                <h2>Nueva Aula</h2>
                <form id="formNuevo" autocomplete="off">
                    <fieldset>
                        <label for="nombre_aula">Nombre del Aula</label>
                        <input type="text" name="nombre" id="nombre_aula" required>

                        <label for="capacidad">Capacidad</label>
                        <input type="number" name="capacidad" id="capacidad" min="0">
                    </fieldset>

                    <div class="actions">
                        <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL EDITAR AULA -->
    <?php if ($permisos['editar']): ?>
        <div class="modal-overlay" id="modalEditar">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
                <h2>Editar Aula</h2>
                <form id="formEditar" autocomplete="off">
                    <fieldset>
                        <label for="editNombreAula">Nombre del Aula</label>
                        <input type="text" name="nombre" id="editNombreAula" required>

                        <label for="editCapacidad">Capacidad</label>
                        <input type="number" name="capacidad" id="editCapacidad" min="0">
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
        // Exponer cosas útiles al JS
        window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES) ?>";
        window.PERMISOS = <?= json_encode($permisos) ?>;

        // Buscador rápido
        document.getElementById('buscarAula')?.addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            document.querySelectorAll('#tablaAulas tbody tr').forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <!-- JS específico de Aulas (deberás crearlo siguiendo la lógica de Carreras.js) -->
    <script src="../../js/secretarias/Aulas.js"></script>
</body>

</html>