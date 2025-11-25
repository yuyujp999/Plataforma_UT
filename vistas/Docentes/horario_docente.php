<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";

$base      = "/Plataforma_UT";
$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;

/* ============================
   OBTENER HORARIOS DEL DOCENTE
   ============================ */
/*
   Estructura real de la BD (según ut_db):
   - horarios: id_horario, id_nombre_profesor_materia_grupo, id_aula, dia, bloque, hora_inicio, hora_fin
   - asignaciones_docentes: id_asignacion_docente, id_docente, id_nombre_materia, id_nombre_profesor_materia_grupo
   - asignar_materias: id_asignacion, id_materia, id_nombre_grupo_int, id_nombre_materia
   - materias: id_materia, nombre_materia, ...
   - aulas: id_aula, nombre, ...
   - cat_nombre_profesor_materia_grupo: id_nombre_profesor_materia_grupo, nombre
*/

$sql = "
    SELECT
        h.dia,
        h.hora_inicio,
        h.hora_fin,
        h.bloque,
        a.nombre AS aula,
        m.nombre_materia,
        cpmg.nombre AS etiqueta_grupo
    FROM horarios h
    INNER JOIN aulas a
        ON a.id_aula = h.id_aula
    INNER JOIN asignaciones_docentes ad
        ON ad.id_nombre_profesor_materia_grupo = h.id_nombre_profesor_materia_grupo
    INNER JOIN asignar_materias am
        ON am.id_nombre_materia = ad.id_nombre_materia
    INNER JOIN materias m
        ON m.id_materia = am.id_materia
    INNER JOIN cat_nombre_profesor_materia_grupo cpmg
        ON cpmg.id_nombre_profesor_materia_grupo = h.id_nombre_profesor_materia_grupo
    WHERE ad.id_docente = ?
    ORDER BY FIELD(h.dia, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'),
             h.hora_inicio ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idDocente);
$stmt->execute();
$resHorarios = $stmt->get_result();
$horarios = $resHorarios->fetch_all(MYSQLI_ASSOC);

/* Agrupar por día */
$dias = [
    "Lunes"      => [],
    "Martes"     => [],
    "Miércoles"  => [],
    "Jueves"     => [],
    "Viernes"    => [],
    "Sábado"     => [],
];

foreach ($horarios as $h) {
    if (isset($dias[$h['dia']])) {
        $dias[$h['dia']][] = $h;
    }
}

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📅 Mi Horario | UT Panel</title>

  <link rel="stylesheet" href="<?= $base ?>/css/styleD.css">
  <link rel="stylesheet" href="/Plataforma_UT/css/darkmode.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <style>
    .horario-page-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:20px;
    }
    .horario-page-header h2{
      font-size:1.4rem;
      color:#064e3b;
      margin-bottom:4px;
    }
    .horario-page-header p{
      margin:0;
      font-size:.9rem;
      color:#6b7280;
    }

    .card-dia {
        background: #f8fafc;
        padding: 16px 18px;
        border-radius: 14px;
        box-shadow: 0 4px 10px rgba(15,23,42,0.08);
        margin-bottom: 18px;
    }

    .card-dia h3 {
        margin: 0 0 10px 0;
        color: #0f766e;
        font-size: 1.15rem;
        display:flex;
        align-items:center;
        gap:8px;
    }

    .card-dia h3 i{
        color:#0f766e;
    }

    .horario-item {
        background: #ffffff;
        padding: 10px 14px;
        border-radius: 10px;
        border-left: 5px solid #0f766e;
        margin-bottom: 10px;
        box-shadow: 0 2px 4px rgba(15,23,42,0.05);
        font-size:.9rem;
    }

    .horario-item strong {
        color: #0f172a;
    }

    .horario-item small {
        color: #475569;
        display:block;
    }

    .horario-item .linea-sec{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top:4px;
        font-size:.8rem;
        color:#6b7280;
    }

    .chip-bloque{
        background:#ecfdf5;
        border-radius:999px;
        padding:2px 10px;
        border:1px solid #6ee7b7;
        font-size:.75rem;
        color:#047857;
    }
  </style>
</head>

<body>
<div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo">
        <h1>UT<span>Panel</span></h1>
      </div>
      <nav class="nav-menu" id="menu"></nav>
    </aside>

    <main class="main-content">
        <div class="horario-page-header">
          <div>
            <h2><i class="fa-solid fa-calendar-week"></i> Mi horario</h2>
            <p>Aquí se muestran las clases que te han asignado en el sistema.</p>
          </div>
        </div>

        <?php
        $tieneAlgo = false;
        foreach ($dias as $dia => $lista): ?>
            <div class="card-dia">
                <h3><i class="fa-solid fa-calendar-day"></i> <?= esc($dia) ?></h3>

                <?php if (empty($lista)): ?>
                    <p style="color:#64748b;font-size:.85rem;">No tienes clases este día.</p>
                <?php else: 
                    $tieneAlgo = true;
                    foreach ($lista as $h): ?>
                        <div class="horario-item">
                            <strong><?= esc($h['nombre_materia']) ?></strong>
                            <small><?= esc($h['etiqueta_grupo']) ?></small>
                            <div class="linea-sec">
                                <span>Aula: <strong><?= esc($h['aula']) ?></strong></span>
                                <span>
                                  <span class="chip-bloque">
                                    Bloque <?= (int)$h['bloque'] ?> — <?= esc($h['hora_inicio']) ?>–<?= esc($h['hora_fin']) ?>
                                  </span>
                                </span>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (!$tieneAlgo): ?>
          <p style="color:#6b7280;font-size:.9rem;">
            Por ahora no tienes horarios registrados. Si crees que esto es un error, consulta con control escolar.
          </p>
        <?php endif; ?>
    </main>
</div>

<script>
  window.rolUsuarioPHP = "docente";
</script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
<script src="/Plataforma_UT/js/modeToggle.js"></script>

</body>
</html>
