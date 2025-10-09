<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}
$rolUsuario = $_SESSION['rol'];

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

$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM administradores")->fetchColumn();
$totalSecretarias = (int) $pdo->query("SELECT COUNT(*) FROM secretarias")->fetchColumn();
$totalAlumnos = (int) $pdo->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
$totalDocentes = (int) $pdo->query("SELECT COUNT(*) FROM docentes")->fetchColumn();

function indicadorCambio($total)
{
    $umbral = 10;
    if ($total > $umbral) {
        return ['clase' => 'positive', 'icon' => 'fa-arrow-up', 'texto' => 'Muchos'];
    } else {
        return ['clase' => 'negative', 'icon' => 'fa-arrow-down', 'texto' => 'Pocos'];
    }
}

$indAdmins = indicadorCambio($totalAdmins);
$indSecretarias = indicadorCambio($totalSecretarias);
$indAlumnos = indicadorCambio($totalAlumnos);
$indDocentes = indicadorCambio($totalDocentes);

// Extraer datos de usuario de sesión
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
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
                <input type="text" placeholder="Search..." />
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
                <div class="title">Dashboard Administrador</div>
                <div class="action-buttons">
                    <button class="btn btn-outline">
                        <i class="fas fa-download"></i>
                        Exportar Dashboard
                    </button>
                </div>
            </div>

            <div class="stats-cards">

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalAdmins ?></div>
                            <div class="card-label">Administradores</div>
                        </div>
                        <div class="card-icon purple"><i class="fas fa-user-shield"></i></div>
                    </div>
                    <div class="card-change <?= $indAdmins['clase'] ?>">
                        <i class="fas <?= $indAdmins['icon'] ?>"></i>
                        <span><?= $indAdmins['texto'] ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalSecretarias ?></div>
                            <div class="card-label">Secretarias</div>
                        </div>
                        <div class="card-icon blue"><i class="fas fa-user-tie"></i></div>
                    </div>
                    <div class="card-change <?= $indSecretarias['clase'] ?>">
                        <i class="fas <?= $indSecretarias['icon'] ?>"></i>
                        <span><?= $indSecretarias['texto'] ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalAlumnos ?></div>
                            <div class="card-label">Alumnos</div>
                        </div>
                        <div class="card-icon green"><i class="fas fa-user-graduate"></i></div>
                    </div>
                    <div class="card-change <?= $indAlumnos['clase'] ?>">
                        <i class="fas <?= $indAlumnos['icon'] ?>"></i>
                        <span><?= $indAlumnos['texto'] ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"><?= $totalDocentes ?></div>
                            <div class="card-label">Docentes</div>
                        </div>
                        <div class="card-icon orange"><i class="fas fa-chalkboard-teacher"></i></div>
                    </div>
                    <div class="card-change <?= $indDocentes['clase'] ?>">
                        <i class="fas <?= $indDocentes['icon'] ?>"></i>
                        <span><?= $indDocentes['texto'] ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    window.rolUsuarioPHP = "<?php echo $rolUsuario; ?>";
    </script>
    <script src="/Plataforma_UT/js/Dashboard.js"></script>
</body>

</html>