<?php
session_start();

// --- Mostrar errores para depuración ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Redirigir si no hay sesión ---
if (!isset($_SESSION['rol']) || !isset($_SESSION['usuario'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

// --- Obtener datos de sesión ---
$rolUsuario = $_SESSION['rol'] ?? 'Usuario';
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(
    ($usuarioSesion['nombre'] ?? 'Usuario') . ' ' . ($usuarioSesion['apellido_paterno'] ?? '')
);
$iniciales = strtoupper(
    substr($usuarioSesion['nombre'] ?? 'U', 0, 1) .
    substr($usuarioSesion['apellido_paterno'] ?? 'U', 0, 1)
);
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
    <link rel="stylesheet" href="../../css/styleD.css" />
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="overlay" id="overlay"></div>
            <div class="logo">
                <h1>UT<span>Panel</span></h1>
            </div>
            <div class="nav-menu" id="menu">
                <div class="menu-heading">Menú</div>
            </div>
        </div>

        <!-- Header -->
        <div class="header">
            <button class="hamburger" id="hamburger">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search..." />
            </div>
            <div class="header-actions">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="badge">3</div>
                </div>
                <div class="notification">
                    <i class="fas fa-envelope"></i>
                    <div class="badge">5</div>
                </div>
                <div class="user-profile" id="userProfile" data-nombre="<?= htmlspecialchars($nombreCompleto) ?>"
                    data-rol="<?= htmlspecialchars($rolUsuario) ?>">
                    <div class="profile-img"><?= $iniciales ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($nombreCompleto) ?></div>
                        <div class="user-role"><?= htmlspecialchars($rolUsuario) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-title">
                <div class="title">Dashboard</div>
                <div class="action-buttons">
                    <button class="btn btn-outline">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New
                    </button>
                </div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value">0</div>
                            <div class="card-label">Usuarios</div>
                        </div>
                        <div class="card-icon purple">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+0%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value">0</div>
                            <div class="card-label">Ingresos</div>
                        </div>
                        <div class="card-icon blue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+0%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value">0</div>
                            <div class="card-label">Ventas</div>
                        </div>
                        <div class="card-icon green">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="card-change negative">
                        <i class="fas fa-arrow-down"></i>
                        <span>-0%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value">0</div>
                            <div class="card-label">Crecimiento</div>
                        </div>
                        <div class="card-icon orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+0%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Disponibilizar rol para JS
        window.rolUsuarioPHP = "<?= $rolUsuario ?>";
    </script>
    <script src="../../js/Dashboard.js"></script>
</body>

</html>