<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$rol = $_GET['rol'] ?? '';
if (!$rol) {
    header('Location: inicio.php');
    exit;
}
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="css/styleL.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="icon" href="img/ut_logo.png" sizes="32x32" type="image/png">
</head>

<body>
    <div class="login-box">
        <div class="login-header">
            <header>Iniciar sesión - <?= htmlspecialchars(ucfirst($rol)) ?></header>
        </div>

        <form action="controladores/login_controller.php" method="POST">
            <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">

            <?php if ($rol === 'docente' || $rol === 'alumno'): ?>
                <div class="input-box">
                    <input type="text" name="matricula" class="input-field" placeholder="Matrícula" required />
                </div>
            <?php else: ?>
                <div class="input-box">
                    <input type="text" name="correo" class="input-field" placeholder="Correo Institucional" required />
                </div>
            <?php endif; ?>

            <div class="input-box">
                <input type="password" name="contrasena" class="input-field" placeholder="Contraseña" required />
            </div>

            <div class="input-submit">
                <button class="submit-btn" id="submit"></button>
                <label for="submit">Iniciar Sesión</label>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($login_error): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error de inicio de sesión',
                text: '<?= addslashes($login_error) ?>',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar'
            });
        </script>
    <?php endif; ?>


</body>

</html>