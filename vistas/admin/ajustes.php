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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ajustes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/ajustes.css" />
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
                <input type="text" id="buscarAsignacion" placeholder="Buscar..." />
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
                <div class="title">Ajustes</div>
            </div>

            <!-- === NUEVA SECCIÓN AJUSTES === -->
            <div class="ajustes-container">

                <section class="ajuste-item">
                    <h2><i class="fas fa-user-edit"></i> Cambiar nombre de usuario</h2>
                    <p>Cambiar tu nombre de usuario puede tener efectos secundarios no deseados.</p>
                    <button class="btn-ajuste">Cambiar nombre de usuario</button>
                </section>

                <section class="ajuste-item">
                    <h2><i class="fas fa-shield-alt"></i> Configuración de seguridad</h2>
                    <p>¿Quieres cambiar tu Contraseña? </p>
                    <button class="btn-contraseña">Cambiar contraseña</button>
                </section>


                <section class="ajuste-item eliminar">
                    <h2><i class="fas fa-trash-alt"></i> Eliminar cuenta</h2>
                    <p>Una vez que elimines tu cuenta, no hay vuelta atrás. Por favor, asegúrate antes de continuar.</p>
                    <button class="btn-eliminar">Eliminar cuenta</button>
                </section>

            </div>
        </div>
    </div>
    <!-- Modal Cambiar Nombre de Usuario -->
    <div id="modalUsuario" class="modal-overlay">
        <div class="modal">
            <button class="close-modal" id="cerrarModal">&times;</button>
            <h2>Editar Información</h2>
            <form id="formAjustes">
                <fieldset>
                    <div>
                        <label for="nombre">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    <div>
                        <label for="apellido_paterno">Apellido Paterno:</label>
                        <input type="text" name="apellido_paterno" id="apellido_paterno" required>
                    </div>
                    <div>
                        <label for="apellido_materno">Apellido Materno:</label>
                        <input type="text" name="apellido_materno" id="apellido_materno" required>
                    </div>
                </fieldset>
                <div class="actions">
                    <button type="button" class="btn-cancel" id="cerrarModalBtn">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div id="modalPassword" class="modal-overlay" style="display:none;">
        <div class="modal">
            <h2>Cambiar Contraseña</h2>
            <button class="close-modal" id="cerrarPassword">&times;</button>
            <form id="formPassword">
                <fieldset>
                    <div class="password-field">
                        <label for="actual">Contraseña Actual</label>
                        <input type="password" id="actual" name="actual" required>
                        <i class="fas fa-eye toggle-password" toggle="#actual"></i>
                    </div>
                    <div class="password-field">
                        <label for="nueva">Nueva Contraseña</label>
                        <input type="password" id="nueva" name="nueva" required>
                        <i class="fas fa-eye toggle-password" toggle="#nueva"></i>
                    </div>
                    <div class="password-field">
                        <label for="confirmar">Confirmar Contraseña</label>
                        <input type="password" id="confirmar" name="confirmar" required>
                        <i class="fas fa-eye toggle-password" toggle="#confirmar"></i>
                    </div>
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelPassword">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/admin/AsignacionesAlumnos.js"></script>
    <script src="../../js/admin/Ajustes1.js"></script>


</body>

</html>