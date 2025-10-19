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

// Contadores generales
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM administradores")->fetchColumn();
$totalSecretarias = (int) $pdo->query("SELECT COUNT(*) FROM secretarias")->fetchColumn();
$totalAlumnos = (int) $pdo->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
$totalDocentes = (int) $pdo->query("SELECT COUNT(*) FROM docentes")->fetchColumn();

// Indicador de cambio
function indicadorCambio($total)
{
    $umbral = 10;
    return $total > $umbral
        ? ['clase' => 'positive', 'icon' => 'fa-arrow-up', 'texto' => 'Muchos']
        : ['clase' => 'negative', 'icon' => 'fa-arrow-down', 'texto' => 'Pocos'];
}
$indAdmins = indicadorCambio($totalAdmins);
$indSecretarias = indicadorCambio($totalSecretarias);
$indAlumnos = indicadorCambio($totalAlumnos);
$indDocentes = indicadorCambio($totalDocentes);

// Datos de usuario
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));

/* ============================
   NUEVO: Alumnos por carrera
   ============================ */
try {
    // ¿La tabla alumnos tiene id_carrera directo?
    $hasIdCarrera = (bool) $pdo->query("
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME='alumnos'
          AND COLUMN_NAME='id_carrera'
        LIMIT 1
    ")->fetchColumn();

    if ($hasIdCarrera) {
        $sql = "
            SELECT c.nombre_carrera AS carrera, COUNT(a.id_alumno) AS total
            FROM carreras c
            LEFT JOIN alumnos a ON a.id_carrera = c.id_carrera
            GROUP BY c.id_carrera, c.nombre_carrera
            ORDER BY total DESC, c.nombre_carrera ASC
            LIMIT 10
        ";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback por semestres
        $sql = "
            SELECT c.nombre_carrera AS carrera, COUNT(a.id_alumno) AS total
            FROM carreras c
            LEFT JOIN semestres s  ON s.id_carrera = c.id_carrera
            LEFT JOIN alumnos   a  ON a.id_nombre_semestre = s.id_nombre_semestre
            GROUP BY c.id_carrera, c.nombre_carrera
            ORDER BY total DESC, c.nombre_carrera ASC
            LIMIT 10
        ";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    $nombresCarreras = array_column($rows, 'carrera');
    $totalesPorCarrera = array_map('intval', array_column($rows, 'total'));
} catch (PDOException $e) {
    $nombresCarreras = [];
    $totalesPorCarrera = [];
}
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
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
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
                <input type="text" placeholder="Buscar..." />
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
                <div class="title">Dashboard Para Administradores</div>
                <div class="action-buttons">

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


            <script>
                window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
            </script>
            <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
        </div>
    </div>
</body>

</html>