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

// Datos de usuario de sesión
$usuarioSesion = $_SESSION['usuario'] ?? [];
$nombreCompleto = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido_paterno'] ?? ''));
$iniciales = strtoupper(substr($usuarioSesion['nombre'] ?? 'U', 0, 1) . substr($usuarioSesion['apellido_paterno'] ?? '', 0, 1));

// --- Datos de calificaciones para la gráfica: Top 10 alumnos ---
try {
    $stmt = $pdo->query("
        SELECT AVG(calificacion_final) AS promedio
        FROM calificaciones
        GROUP BY id_alumno
        ORDER BY promedio DESC
        LIMIT 10
    ");
    $topPromedios = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $nombresAlumnos = [];
    foreach ($topPromedios as $index => $promedio) {
        $nombresAlumnos[] = 'Top ' . ($index + 1);
    }

    $promedios = array_map('floatval', $topPromedios);

    // Promedio general
    $promedioGeneral = $pdo->query("SELECT AVG(calificacion_final) FROM calificaciones")->fetchColumn();
} catch (PDOException $e) {
    $nombresAlumnos = [];
    $promedios = [];
    $promedioGeneral = 0;
}

// --- Datos de materias con más horas ---
try {
    $stmtMaterias = $pdo->query("
        SELECT nombre_materia, horas_semana
        FROM materias
        ORDER BY horas_semana DESC
        LIMIT 10
    ");
    $materiasData = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

    $nombresMaterias = array_column($materiasData, 'nombre_materia');
    $horasPorMateria = array_map('intval', array_column($materiasData, 'horas_semana'));
} catch (PDOException $e) {
    $nombresMaterias = [];
    $horasPorMateria = [];
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
        .grafica-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }

        .grafica-calificaciones,
        .grafica-materias {
            flex: 1;
            max-width: 48%;
        }

        canvas {
            width: 100% !important;
            height: 250px !important;
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
                <!-- Tarjetas de estadísticas (igual que antes) -->
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

            <div class="grafica-container">
                <!-- Gráfica de calificaciones -->
                <div class="grafica-calificaciones">
                    <h2>Top 10 Promedios de Calificaciones</h2>
                    <canvas id="calificacionesChart"></canvas>
                </div>

                <!-- Gráfica de materias con más horas -->
                <div class="grafica-materias">
                    <h2>Materias con Más Horas/Semana</h2>
                    <canvas id="materiasChart"></canvas>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // --- Gráfica de calificaciones ---
                const nombresAlumnos = <?php echo json_encode($nombresAlumnos); ?>;
                const promedios = <?php echo json_encode($promedios); ?>;

                const coloresBarras = promedios.map(promedio => {
                    if (promedio >= 10) return 'rgba(0, 200, 0, 0.6)';
                    if (promedio >= 7) return 'rgba(255, 165, 0, 0.6)';
                    return 'rgba(255, 0, 0, 0.6)';
                });

                const borderColores = promedios.map(promedio => {
                    if (promedio >= 10) return 'rgba(0, 200, 0, 1)';
                    if (promedio >= 7) return 'rgba(255, 165, 0, 1)';
                    return 'rgba(255, 0, 0, 1)';
                });

                const ctxCalificaciones = document.getElementById('calificacionesChart').getContext('2d');
                new Chart(ctxCalificaciones, {
                    type: 'bar',
                    data: {
                        labels: nombresAlumnos,
                        datasets: [{
                            label: 'Promedio de Calificaciones',
                            data: promedios,
                            backgroundColor: coloresBarras,
                            borderColor: borderColores,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 10
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // --- Gráfica de materias con más horas ---
                const nombresMaterias = <?php echo json_encode($nombresMaterias); ?>;
                const horasPorMateria = <?php echo json_encode($horasPorMateria); ?>;

                const coloresMaterias = horasPorMateria.map(h => {
                    if (h >= 8) return 'rgba(0, 200, 0, 0.6)';
                    if (h >= 4) return 'rgba(255, 165, 0, 0.6)';
                    return 'rgba(255, 0, 0, 0.6)';
                });

                const borderColoresMaterias = horasPorMateria.map(h => {
                    if (h >= 8) return 'rgba(0, 200, 0, 1)';
                    if (h >= 4) return 'rgba(255, 165, 0, 1)';
                    return 'rgba(255, 0, 0, 1)';
                });

                const ctxMaterias = document.getElementById('materiasChart').getContext('2d');
                new Chart(ctxMaterias, {
                    type: 'bar',
                    data: {
                        labels: nombresMaterias,
                        datasets: [{
                            label: 'Horas/Semana',
                            data: horasPorMateria,
                            backgroundColor: coloresMaterias,
                            borderColor: borderColoresMaterias,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            </script>

            <script>
                window.rolUsuarioPHP = "<?php echo $rolUsuario; ?>";
            </script>
            <script src="/Plataforma_UT/js/DashboardY.js"></script>
</body>

</html>