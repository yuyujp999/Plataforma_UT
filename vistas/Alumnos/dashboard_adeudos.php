<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
    header("Location: /Plataforma_UT/inicio.php");
    exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";

$idAlumno    = $_SESSION['usuario']['id_alumno']   ?? 0;
$matricula   = $_SESSION['usuario']['matricula']   ?? '';
$rolUsuario  = $_SESSION['rol'];
$usuario     = $_SESSION['usuario'] ?? [];

$nombre      = $usuario['nombre']            ?? '';
$apellido    = $usuario['apellido_paterno']  ?? '';
$nombreCompleto = trim($nombre . " " . $apellido);

/*
  ===============================
        CONSULTAR ADEUDOS
  ===============================
  Se consultan los pagos desde la tabla `pagos`, que se liga por
  la matrícula del alumno.
*/

$sql = "
    SELECT 
        p.id,
        p.concepto,
        p.periodo,
        p.monto,
        p.adeudo,
        p.pago,
        p.condonacion,
        p.fecha_registro,
 CASE 
    WHEN p.adeudo <= 0 THEN 'Pagado'
    ELSE 'Pago parcial'
END AS estado
    FROM pagos p
    INNER JOIN alumnos a 
        ON a.matricula = p.matricula
    WHERE a.id_alumno = ?
    ORDER BY p.fecha_registro DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idAlumno);
$stmt->execute();
$adeudos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>💸 Adeudos | UT Panel</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css">
    <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">

    <style>
        .adeudos-container {
            margin-top: 20px;
        }

        .adeudo-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            border-left: 5px solid #006400;
        }

        .adeudo-icon {
            font-size: 32px;
            color: #006400;
            margin-right: 15px;
        }

        .adeudo-body h3 {
            margin: 0;
            font-size: 20px;
            color: #004d00;
        }

        .adeudo-body p {
            margin: 4px 0;
            color: #333;
            font-size: 14px;
        }

        .estado-tag {
            padding: 6px 14px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
            text-transform: capitalize;
        }

        .estado-tag.pagado {
            background: #c7ffd1;
            color: #0c7a23;
        }

        .estado-tag.pago-parcial {
            background: #fff7c2;
            color: #a58c00;
        }

        .estado-tag.pendiente {
            background: #ffd2d2;
            color: #8b0000;
        }
    </style>

</head>

<body>

<div class="container">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="logo"><h1>UT<span>Panel</span></h1></div>
        <nav class="nav-menu" id="menu"></nav>
    </aside>

    <!-- Contenido -->
    <main class="main-content">

        <header class="materia-header">
            <div class="materia-info">
                <h2><i class="fa-solid fa-money-bill-wave"></i> Mis Adeudos</h2>
                <p>Aquí puedes consultar tus pagos, adeudos y condonaciones.</p>
            </div>
        </header>

        <div class="adeudos-container">

            <?php if (empty($adeudos)): ?>
                <p style="padding:20px; background:#fff; border-radius:12px; text-align:center;">
                    No tienes adeudos registrados. 🎉
                </p>
            <?php endif; ?>

            <?php foreach ($adeudos as $a): ?>
                <?php
                    // Formatear fecha
                    $fecha = !empty($a['fecha_registro'])
                        ? date("d/m/Y", strtotime($a['fecha_registro']))
                        : "—";

                    // estado para la clase css
                    $estadoCss = strtolower(str_replace(' ', '-', $a['estado'])); // pagado / pago-parcial / pendiente
                ?>

                <div class="adeudo-card">
                    <div style="display:flex; align-items:center;">
                        <div class="adeudo-icon">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>

                        <div class="adeudo-body">
                            <h3><?= htmlspecialchars($a['concepto']) ?></h3>

                            <p><strong>Periodo:</strong> <?= htmlspecialchars($a['periodo']) ?></p>
                            <p><strong>Fecha de registro:</strong> <?= htmlspecialchars($fecha) ?></p>
                            <p><strong>Monto total:</strong> $<?= number_format($a['monto'], 2) ?></p>
                            <p><strong>Monto pagado:</strong> $<?= number_format($a['pago'], 2) ?></p>
                            <p><strong>Adeudo restante:</strong> $<?= number_format($a['adeudo'], 2) ?></p>

                            <?php if (!empty($a['condonacion']) && $a['condonacion'] > 0): ?>
                                <p><strong>Condonación:</strong> $<?= number_format($a['condonacion'], 2) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <span class="estado-tag <?= $estadoCss ?>">
                            <?= htmlspecialchars($a['estado']) ?>
                        </span>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>

    </main>
</div>

<script>
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?>";
</script>

<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>

</body>
</html>
