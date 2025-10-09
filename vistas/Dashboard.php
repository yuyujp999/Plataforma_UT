<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}
$rolUsuario = $_SESSION['rol'];
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
    <link rel="stylesheet" href="../css/styleD.css" />
    <link rel="icon" href="../img/ut_logo.png" sizes="32x32" type="image/png">
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
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="badge">3</div>
                </div>
                <div class="notification">
                    <i class="fas fa-envelope"></i>
                    <div class="badge">5</div>
                </div>
                <div class="user-profile" id="userProfile"
                    data-nombre="<?= htmlspecialchars($_SESSION['usuario'] ?? '') ?>"
                    data-rol="<?= htmlspecialchars($_SESSION['rol'] ?? '') ?>">
                    <div class="profile-img">INICIALES</div>
                    <div class="user-info">
                        <div class="user-name">EJEMPLO EJEMPLO</div>
                        <div class="user-role">ADMINISTRADOR O LO QUE SEA</div>
                    </div>
                </div>
            </div>
        </div>


        <div class="main-content">
            <div class="page-title">
                <div class="title">Dashboard</div>
                <div class="action-buttons">
                    <button class="btn btn-outline">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add New
                    </button>
                </div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"></div>
                            <div class="card-label"></div>
                        </div>
                        <div class="card-icon purple">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"></div>
                            <div class="card-label"></div>
                        </div>
                        <div class="card-icon blue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"></div>
                            <div class="card-label"></div>
                        </div>
                        <div class="card-icon green">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="card-change negative">
                        <i class="fas fa-arrow-down"></i>
                        <span></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-header">
                        <div>
                            <div class="card-value"></div>
                            <div class="card-label"></div>
                        </div>
                        <div class="card-icon orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span></span>
                    </div>
                </div>
            </div>


</body>
<script>
    window.rolUsuarioPHP = "<?php echo $rolUsuario; ?>";
</script>
<script src="../js/Dashboard.js"></script>



</html>