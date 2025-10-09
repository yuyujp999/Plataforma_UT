<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Elige tu perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="css/styleI.css" />
    <link rel="icon" href="img/ut_logo.png" sizes="32x32" type="image/png">
</head>

<body>
    <h1>Hola, elige tu perfil</h1>
    <div class="stats-cards">
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="card-label">Profesor</div>
            <button class="btn-elegir" data-rol="docente">Elegir</button>
        </div>
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
            <div class="card-label">Alumno</div>
            <button class="btn-elegir" data-rol="alumno">Elegir</button>
        </div>
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-user-shield"></i></div>
            <div class="card-label">Administrador</div>
            <button class="btn-elegir" data-rol="admin">Elegir</button>
        </div>
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-user-tie"></i></div>
            <div class="card-label">Secretaria</div>
            <button class="btn-elegir" data-rol="secretaria">Elegir</button>
        </div>
    </div>

    <form id="rolForm" action="login.php" method="get" style="display:none;">
        <input type="hidden" name="rol" id="rolInput">
    </form>

</body>
<script src="js/Inicio.js"></script>

</html>