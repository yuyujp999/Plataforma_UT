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

// ===== Permisos por rol =====
$rol = strtolower($rolUsuario);
$esAdmin = ($rol === 'admin');
$esSecretaria = in_array($rol, ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

$permisos = [
    'crear'   => $esAdmin || $esSecretaria,
    'editar'  => $esAdmin || $esSecretaria,
    'eliminar'=> $esAdmin, // solo admin elimina
];

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/**
 * LISTAS PARA LOS SELECTS
 */

// Profesor–Materia–Grupo
$combosStmt = $pdo->query("
    SELECT 
        id_nombre_profesor_materia_grupo,
        nombre
    FROM cat_nombre_profesor_materia_grupo
    ORDER BY nombre ASC
");
$combosList = $combosStmt->fetchAll(PDO::FETCH_ASSOC);

// Aulas
$aulasStmt = $pdo->query("
    SELECT id_aula, nombre 
    FROM aulas 
    ORDER BY nombre ASC
");
$aulasList = $aulasStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * LISTADO DE HORARIOS
 * grupo_codigo = parte después del último " - " en el nombre del catálogo
 * materia_color = código de materia (antes del primer "-") dentro de ese segmento
 */
$horariosStmt = $pdo->query("
    SELECT 
        h.id_horario,
        h.id_nombre_profesor_materia_grupo,
        h.id_aula,
        h.dia,
        h.bloque,
        h.hora_inicio,
        h.hora_fin,
        c.nombre AS profesor_materia_grupo,
        a.nombre AS nombre_aula,
        SUBSTRING_INDEX(c.nombre, ' - ', -1) AS grupo_codigo,
        SUBSTRING_INDEX(SUBSTRING_INDEX(c.nombre, ' - ', -1), '-', 1) AS materia_color
    FROM horarios h
    JOIN cat_nombre_profesor_materia_grupo c 
        ON h.id_nombre_profesor_materia_grupo = c.id_nombre_profesor_materia_grupo
    JOIN aulas a 
        ON h.id_aula = a.id_aula
    ORDER BY 
        grupo_codigo ASC,
        FIELD(h.dia, 'Lunes','Martes','Miércoles','Jueves','Viernes'),
        h.bloque ASC
");
$horarios = $horariosStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * VISTA SEMANAL POR GRUPO
 */

// Días y bloques
$diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
$bloques = range(1, 8);

// Horas por bloque
$bloqueHoras = [
    1 => '07:00 - 07:50',
    2 => '07:50 - 08:40',
    3 => '08:40 - 09:30',
    4 => '09:30 - 10:20',
    5 => '10:30 - 11:20',
    6 => '11:20 - 12:10',
    7 => '12:10 - 13:00',
    8 => '13:00 - 13:50',
];

// Sacar lista de grupos que tienen horarios
$gruposVista = [];
foreach ($horarios as $h) {
    $g = trim($h['grupo_codigo'] ?? '');
    if ($g === '') {
        $g = 'SIN_GRUPO';
    }
    if (!in_array($g, $gruposVista, true)) {
        $gruposVista[] = $g;
    }
}
sort($gruposVista);

// Grupo seleccionado para la vista semanal
$grupoSeleccionado = $_GET['grupo'] ?? ($gruposVista[0] ?? '');
if (!in_array($grupoSeleccionado, $gruposVista, true) && !empty($gruposVista)) {
    $grupoSeleccionado = $gruposVista[0];
}

// Construir matriz SOLO del grupo seleccionado
$grid = [];
foreach ($horarios as $h) {
    $g = trim($h['grupo_codigo'] ?? '');
    if ($g === '') {
        $g = 'SIN_GRUPO';
    }
    if ($g !== $grupoSeleccionado) {
        continue;
    }

    $d = $h['dia'];
    $b = (int) $h['bloque'];

    if (!isset($grid[$b])) {
        $grid[$b] = [];
    }
    if (!isset($grid[$b][$d])) {
        $grid[$b][$d] = [];
    }

    // ===== Color por materia =====
    $materiaKey = trim($h['materia_color'] ?? '');
    if ($materiaKey === '') {
        $materiaKey = 'X';
    }

    $hash = 0;
    for ($i = 0; $i < strlen($materiaKey); $i++) {
        $hash += ord($materiaKey[$i]);
    }
    $colorIndex = ($hash % 8) + 1; // 1..8

    $texto = sprintf(
        '<div class="subject-tag color-%d">%s<br><small>%s</small></div>',
        $colorIndex,
        htmlspecialchars($h['profesor_materia_grupo'], ENT_QUOTES),
        'Aula ' . htmlspecialchars($h['nombre_aula'], ENT_QUOTES)
    );

    $grid[$b][$d][] = $texto;
}

/**
 * LEYENDA PARA EL PDF (materia en negrita + profesor debajo)
 */
$legendMap = [];
foreach ($horarios as $h) {
    $g = trim($h['grupo_codigo'] ?? '');
    if ($g === '') {
        $g = 'SIN_GRUPO';
    }
    if ($g !== $grupoSeleccionado) {
        continue;
    }

    // mismo cálculo de color que arriba
    $materiaKey = trim($h['materia_color'] ?? '');
    if ($materiaKey === '') {
        $materiaKey = 'X';
    }
    $hash = 0;
    for ($i = 0; $i < strlen($materiaKey); $i++) {
        $hash += ord($materiaKey[$i]);
    }
    $colorIndex = ($hash % 8) + 1;

    // Partimos "Profesor - Materia - Grupo"
    $parts = explode(' - ', $h['profesor_materia_grupo']);
    $profesor = $parts[0] ?? $h['profesor_materia_grupo'];
    $materia  = $parts[1] ?? $h['profesor_materia_grupo'];

    $key = strtolower($materia . '|' . $profesor);
    if (!isset($legendMap[$key])) {
        $legendMap[$key] = [
            'materia'  => $materia,
            'profesor' => $profesor,
            'color'    => $colorIndex,
        ];
    }
}
$legendItems = array_values($legendMap);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Horarios (Secretarías)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Librerías para generar PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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

.pagination-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 15px;
    gap: 8px;
    flex-wrap: wrap;
}

.pagination-btn {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.pagination-btn:hover {
    background-color: #218838;
}

.pagination-btn.active {
    background-color: #1e7e34;
    font-weight: bold;
}

/* ===== Vista semanal por grupo (compacta y colorida) ===== */
.horario-grid-wrapper {
    overflow-x: auto;
    margin-top: 10px;
    background: #ffffff;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.horario-grid {
    min-width: 780px;
    table-layout: fixed;
    font-size: 0.78rem;
    border-collapse: collapse;
    background: #ffffff;
}

.horario-grid th,
.horario-grid td {
    text-align: center;
    vertical-align: middle;
    padding: 4px;
    border: 1px solid #cbd5e1;
}

.horario-grid thead th {
    background: #e2f3ff;
    color: #0f172a;
    font-weight: 600;
    text-align: center !important;
    vertical-align: middle;
}

.bloque-label {
    font-weight: 600;
    background: #f1f5f9;
    text-align: center;
}

.bloque-label small {
    display: block;
    font-weight: 400;
    color: #64748b;
}

.horario-cell {
    padding: 4px;
    position: relative;
    text-align: center;
}

.subject-tag {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    border-radius: 6px;
    padding: 4px 6px;
    margin: 2px 0;
    font-size: 0.75rem;
    line-height: 1.2;
    text-align: center;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    white-space: normal;
    overflow: visible;
    word-wrap: break-word;
    word-break: break-word;
}

.subject-tag small {
    display: block;
    margin-top: 2px;
    font-size: 0.68rem;
    color: #0f172a;
    opacity: .85;
}

/* Paleta de colores (por materia) */
.subject-tag.color-1 {
    background: #e0e7ff;
    border: 1px solid #c7d2fe;
}

.subject-tag.color-2 {
    background: #fee2e2;
    border: 1px solid #fecaca;
}

.subject-tag.color-3 {
    background: #dcfce7;
    border: 1px solid #bbf7d0;
}

.subject-tag.color-4 {
    background: #fef9c3;
    border: 1px solid #fef08a;
}

.subject-tag.color-5 {
    background: #ede9fe;
    border: 1px solid #ddd6fe;
}

.subject-tag.color-6 {
    background: #cffafe;
    border: 1px solid #a5f3fc;
}

.subject-tag.color-7 {
    background: #fce7f3;
    border: 1px solid #f9a8d4;
}

.subject-tag.color-8 {
    background: #e2e8f0;
    border: 1px solid #cbd5e1;
}

.grupo-select-wrapper {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
}

.grupo-select-wrapper select {
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    font-size: 0.85rem;
}

@media print {

    .sidebar,
    .header,
    .table-card:first-of-type,
    .grupo-select-wrapper button {
        display: none !important;
    }

    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .main-content {
        margin: 0;
        padding: 0;
    }

    .horario-grid-wrapper {
        overflow: visible;
        border: none;
        box-shadow: none;
    }
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
                <!-- Tu menú se llena con Dashboard_Inicio.js -->
            </div>
        </div>

        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarHorario" placeholder="Buscar horarios..." />
            </div>
            <div class="header-actions">
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
                <div class="title">Gestión de Horarios</div>
                <div class="action-buttons">
                    <?php if ($permisos['crear']): ?>
                    <button class="btn btn-outline btn-sm" id="btnNuevoHorario">
                        <i class="fas fa-plus"></i> Nuevo Horario
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== TABLA CRUD NORMAL ===== -->
            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-calendar-alt"></i> Horarios</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaHorarios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Profesor - Materia - Grupo</th>
                                <th>Aula</th>
                                <th>Día</th>
                                <th>Bloque</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($horarios)): ?>
                            <?php foreach ($horarios as $row): ?>
                            <tr data-id="<?= htmlspecialchars($row['id_horario']) ?>"
                                data-combo="<?= htmlspecialchars($row['id_nombre_profesor_materia_grupo']) ?>"
                                data-aula="<?= htmlspecialchars($row['id_aula']) ?>"
                                data-dia="<?= htmlspecialchars($row['dia']) ?>"
                                data-bloque="<?= htmlspecialchars($row['bloque']) ?>"
                                data-inicio="<?= htmlspecialchars(substr($row['hora_inicio'], 0, 5)) ?>"
                                data-fin="<?= htmlspecialchars(substr($row['hora_fin'], 0, 5)) ?>">
                                <td><?= $row['id_horario'] ?></td>
                                <td><?= htmlspecialchars($row['profesor_materia_grupo']) ?></td>
                                <td><?= htmlspecialchars($row['nombre_aula']) ?></td>
                                <td><?= htmlspecialchars($row['dia']) ?></td>
                                <td><?= (int) $row['bloque'] ?></td>
                                <td><?= htmlspecialchars(substr($row['hora_inicio'], 0, 5)) ?></td>
                                <td><?= htmlspecialchars(substr($row['hora_fin'], 0, 5)) ?></td>
                                <td>
                                    <?php if ($permisos['editar']): ?>
                                    <button class="btn btn-outline btn-sm btn-editar-horario">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($permisos['eliminar']): ?>
                                    <button class="btn btn-outline btn-sm btn-eliminar-horario">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8">No hay horarios registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination-container" id="paginationHorarios"></div>
                </div>
            </div>

            <!-- ===== VISTA SEMANAL POR GRUPO ===== -->
            <div class="table-card" id="cardHorarioGrupo">
                <div class="card-title" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                    <div class="grupo-select-wrapper">
                        <h3 style="margin:0;">
                            <i class="fas fa-table"></i>
                            Horario semanal por grupo
                        </h3>
                        <?php if (!empty($gruposVista)): ?>
                        <label for="selectGrupoVista">Grupo:</label>
                        <select id="selectGrupoVista" onchange="cambiarGrupoVista(this.value)">
                            <?php foreach ($gruposVista as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>"
                                <?= $g === $grupoSeleccionado ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <span style="color:#64748b;font-size:0.85rem;">Sin horarios registrados aún.</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($gruposVista)): ?>
                    <button class="btn btn-outline btn-sm" id="btnPdfHorarioGrupo">
                        <i class="fas fa-file-pdf"></i> PDF Horario
                    </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($gruposVista)): ?>
                <div class="horario-grid-wrapper" id="horarioGridCapture">
                    <table class="data-table horario-grid">
                        <thead>
                            <tr>
                                <th>Bloque</th>
                                <?php foreach ($diasSemana as $d): ?>
                                <th><?= htmlspecialchars($d) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloques as $b): ?>
                            <tr>
                                <td class="bloque-label">
                                    <?= $b ?>
                                    <small><?= $bloqueHoras[$b] ?? '' ?></small>
                                </td>
                                <?php foreach ($diasSemana as $d): ?>
                                <?php $celda = $grid[$b][$d] ?? []; ?>
                                <td class="horario-cell">
                                    <?php if (!empty($celda)): ?>
                                    <?= implode('', $celda) ?>
                                    <?php else: ?>
                                    &nbsp;
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /main-content -->
    </div><!-- /container -->

    <!-- MODAL NUEVO HORARIO -->
    <?php if ($permisos['crear']): ?>
    <div class="modal-overlay" id="modalNuevoHorario">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalNuevoHorario">&times;</button>
            <h2>Nuevo Horario</h2>
            <form id="formNuevoHorario">
                <fieldset>
                    <label for="nuevoCombo">Profesor - Materia - Grupo</label>
                    <select name="id_nombre_profesor_materia_grupo" id="nuevoCombo" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($combosList as $c): ?>
                        <option value="<?= $c['id_nombre_profesor_materia_grupo'] ?>">
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="nuevoIdAula">Aula</label>
                    <select name="id_aula" id="nuevoIdAula" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($aulasList as $a): ?>
                        <option value="<?= $a['id_aula'] ?>">
                            <?= htmlspecialchars($a['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="nuevoDia">Día</label>
                    <select name="dia" id="nuevoDia" required>
                        <option value="">Seleccione...</option>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                    </select>

                    <label for="nuevoBloque">Bloque</label>
                    <select name="bloque" id="nuevoBloque" required>
                        <option value="">Seleccione...</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>

                    <label for="nuevoHoraInicio">Hora inicio</label>
                    <input type="time" name="hora_inicio" id="nuevoHoraInicio" required>

                    <label for="nuevoHoraFin">Hora fin</label>
                    <input type="time" name="hora_fin" id="nuevoHoraFin" required>
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModalNuevoHorario">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL EDITAR HORARIO -->
    <?php if ($permisos['editar']): ?>
    <div class="modal-overlay" id="modalEditarHorario">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditarHorario">&times;</button>
            <h2>Editar Horario</h2>
            <form id="formEditarHorario">
                <input type="hidden" name="id_horario" id="editIdHorario">

                <fieldset>
                    <label for="editCombo">Profesor - Materia - Grupo</label>
                    <select name="id_nombre_profesor_materia_grupo" id="editCombo" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($combosList as $c): ?>
                        <option value="<?= $c['id_nombre_profesor_materia_grupo'] ?>">
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editIdAula">Aula</label>
                    <select name="id_aula" id="editIdAula" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($aulasList as $a): ?>
                        <option value="<?= $a['id_aula'] ?>">
                            <?= htmlspecialchars($a['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editDia">Día</label>
                    <select name="dia" id="editDia" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                    </select>

                    <label for="editBloque">Bloque</label>
                    <select name="bloque" id="editBloque" required>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>

                    <label for="editHoraInicio">Hora inicio</label>
                    <input type="time" name="hora_inicio" id="editHoraInicio" required>

                    <label for="editHoraFin">Hora fin</label>
                    <input type="time" name="hora_fin" id="editHoraFin" required>
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModalEditarHorario">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    // Datos globales para JS
    window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES) ?>";
    window.PERMISOS = <?= json_encode($permisos) ?>;
    const HORARIO_LEYENDA = <?= json_encode($legendItems, JSON_UNESCAPED_UNICODE) ?>;

    // Cambiar grupo en la vista semanal (recarga con ?grupo=)
    function cambiarGrupoVista(grupo) {
        const url = new URL(window.location.href);
        url.searchParams.set('grupo', grupo);
        window.location.href = url.toString();
    }

    // Generar PDF del horario por grupo (con encabezado y leyenda)
    document.addEventListener("DOMContentLoaded", () => {
        const btnPdf = document.getElementById("btnPdfHorarioGrupo");
        const captura = document.getElementById("horarioGridCapture");
        const grupoActual = "<?= htmlspecialchars($grupoSeleccionado, ENT_QUOTES) ?>";

        if (!btnPdf || !captura) return;

        btnPdf.addEventListener("click", async () => {
            try {
                if (typeof html2canvas === "undefined") {
                    Swal.fire("Error", "No se pudo cargar html2canvas para generar el PDF.",
                        "error");
                    return;
                }

                const jsPdfNamespace = window.jspdf || {};
                const JsPDFClass = jsPdfNamespace.jsPDF || window.jsPDF;

                if (typeof JsPDFClass === "undefined") {
                    Swal.fire("Error", "No se pudo cargar jsPDF para generar el PDF.", "error");
                    return;
                }

                Swal.fire({
                    title: "Generando PDF...",
                    text: "Por favor espera un momento",
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });

                await new Promise(r => setTimeout(r, 150));

                const canvas = await html2canvas(captura, {
                    scale: 2,
                    backgroundColor: "#ffffff",
                    useCORS: true
                });

                const imgData = canvas.toDataURL("image/jpeg", 1.0);
                const pdf = new JsPDFClass("landscape", "mm", "a4");

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const marginX = 10;
                let currentY = 18;

                // Encabezado
                pdf.setFont("helvetica", "bold");
                pdf.setFontSize(20);
                pdf.text("Horario del grupo " + (grupoActual || ""), pageWidth / 2, currentY, {
                    align: "center"
                });

                currentY += 8;
                pdf.setFont("helvetica", "normal");
                pdf.setFontSize(11);
                const fechaStr = new Date().toLocaleDateString("es-MX");
                pdf.text("Generado: " + fechaStr, pageWidth - marginX, currentY, {
                    align: "right"
                });

                currentY += 10;

                // Imagen de la tabla
                const imgWidth = pageWidth - marginX * 2;
                const imgHeight = canvas.height * imgWidth / canvas.width;

                pdf.addImage(imgData, "JPEG", marginX, currentY, imgWidth, imgHeight);
                currentY += imgHeight + 10;

                // Si no cabe la leyenda, nueva página
                if (currentY > pageHeight - 30) {
                    pdf.addPage("landscape");
                    currentY = 20;
                }

                // Leyenda: materias y docentes
                if (HORARIO_LEYENDA && HORARIO_LEYENDA.length) {
                    pdf.setFont("helvetica", "bold");
                    pdf.setFontSize(14);
                    pdf.text("Materias y docentes", marginX, currentY);
                    currentY += 8;

                    pdf.setFontSize(11);

                    HORARIO_LEYENDA.forEach(item => {
                        if (currentY > pageHeight - 12) {
                            pdf.addPage("landscape");
                            currentY = 20;
                        }

                        const c = item.color || 1;
                        switch (c) {
                            case 1:
                                pdf.setFillColor(224, 231, 255);
                                break;
                            case 2:
                                pdf.setFillColor(254, 226, 226);
                                break;
                            case 3:
                                pdf.setFillColor(220, 252, 231);
                                break;
                            case 4:
                                pdf.setFillColor(254, 249, 195);
                                break;
                            case 5:
                                pdf.setFillColor(237, 233, 254);
                                break;
                            case 6:
                                pdf.setFillColor(207, 250, 254);
                                break;
                            case 7:
                                pdf.setFillColor(252, 231, 243);
                                break;
                            default:
                                pdf.setFillColor(226, 232, 240);
                                break;
                        }

                        // Cuadrito de color
                        pdf.rect(marginX, currentY - 4, 6, 6, "F");

                        const xText = marginX + 10;

                        // Materia en negrita
                        pdf.setFont("helvetica", "bold");
                        pdf.text(String(item.materia || ""), xText, currentY);

                        // Profesor debajo
                        currentY += 5;
                        pdf.setFont("helvetica", "normal");
                        pdf.text("Profesor: " + String(item.profesor || ""), xText,
                            currentY);

                        currentY += 7;
                    });
                }

                pdf.save("Horario_" + (grupoActual || "grupo") + ".pdf");
                Swal.close();
                Swal.fire("Listo", "El PDF se descargó correctamente", "success");
            } catch (e) {
                console.error(e);
                Swal.close();
                Swal.fire("Error", "No se pudo generar el PDF.", "error");
            }
        });
    });
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/secretarias/Horarios.js"></script>

</body>

</html>