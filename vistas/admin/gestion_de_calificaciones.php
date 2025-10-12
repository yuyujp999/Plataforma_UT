<?php
session_start();

// Mostrar errores
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

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ==================== OBTENER CALIFICACIONES ====================
$stmt = $pdo->query("
    SELECT c.id_calificacion, c.calificacion_final, c.observaciones, c.fecha_registro,
           a.id_alumno, CONCAT(a.nombre, ' ', a.apellido_paterno, ' ', a.apellido_materno) AS alumno,
           ad.id_asignacion_docente, CONCAT(d.nombre, ' ', d.apellido_paterno) AS docente, m.nombre_materia AS materia
    FROM calificaciones c
    INNER JOIN alumnos a ON c.id_alumno = a.id_alumno
    INNER JOIN asignaciones_docentes ad ON c.id_asignacion_docente = ad.id_asignacion_docente
    INNER JOIN docentes d ON ad.id_docente = d.id_docente
    INNER JOIN materias m ON ad.id_materia = m.id_materia
    ORDER BY c.id_calificacion ASC
");
$calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================== LISTA DE ALUMNOS ====================
$stmtAlumnos = $pdo->query("
    SELECT id_alumno, CONCAT(nombre,' ',apellido_paterno,' ',apellido_materno) AS alumno 
    FROM alumnos 
    ORDER BY id_alumno ASC
");
$alumnos = $stmtAlumnos->fetchAll(PDO::FETCH_ASSOC);

// ==================== LISTA DE ASIGNACIONES DOCENTES ====================
$stmtAsign = $pdo->query("
    SELECT ad.id_asignacion_docente, CONCAT(d.nombre, ' ', d.apellido_paterno) AS docente, m.nombre_materia AS materia
    FROM asignaciones_docentes ad
    INNER JOIN docentes d ON ad.id_docente = d.id_docente
    INNER JOIN materias m ON ad.id_materia = m.id_materia
    ORDER BY ad.id_asignacion_docente ASC
");
$asignaciones = $stmtAsign->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Calificaciones</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
</head>

<body>
    <div class="container">
        <!-- SIDEBAR -->
        <div class="sidebar" id="sidebar">
            <div class="overlay" id="overlay"></div>
            <div class="logo">
                <h1>UT<span>Panel</span></h1>
            </div>
            <div class="nav-menu" id="menu">
                <div class="menu-heading">Menú</div>
                <!-- Aquí tus opciones de menú -->
            </div>
        </div>

        <!-- HEADER -->
        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar"><i class="fas fa-search"></i><input type="text" id="buscarCalificacion"
                    placeholder="Buscar..." /></div>
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

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="page-title">
                <div class="title">Gestión de Calificaciones</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar"><i class="fas fa-download"></i> Exportar</button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo"><i class="fas fa-plus"></i> Nuevo</button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-clipboard-list"></i> Calificaciones</h3>
                </div>
                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaCalificaciones">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Alumno</th>
                                <th>Asignación Docente</th>
                                <th>Calificación Final</th>
                                <th>Observaciones</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($calificaciones)): ?>
                                <?php foreach ($calificaciones as $row): ?>
                                    <tr data-id="<?= htmlspecialchars($row['id_calificacion'] ?? '') ?>"
                                        data-alumno="<?= htmlspecialchars($row['id_alumno'] ?? '') ?>"
                                        data-asignacion="<?= htmlspecialchars($row['id_asignacion'] ?? '') ?>"
                                        data-calificacion="<?= htmlspecialchars($row['calificacion_final'] ?? '') ?>"
                                        data-observaciones="<?= htmlspecialchars($row['observaciones'] ?? '') ?>">
                                        <td><?= htmlspecialchars($row['id_calificacion'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['alumno'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars(($row['docente'] ?? '-') . ' - ' . ($row['materia'] ?? '-')) ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['calificacion_final'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['observaciones'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['fecha_registro'] ?? '-') ?></td>
                                        <td>
                                            <button class="btn btn-outline btn-sm btn-editar"><i class="fas fa-edit"></i>
                                                Editar</button>
                                            <button class="btn btn-outline btn-sm btn-eliminar"><i class="fas fa-trash"></i>
                                                Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No hay calificaciones registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO CALIFICACIÓN -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nuevo Registro</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="alumno">Alumno</label>
                    <select name="id_alumno" id="alumno" required>
                        <option value="">Selecciona un alumno</option>
                        <?php foreach ($alumnos as $alumno): ?>
                            <option value="<?= $alumno['id_alumno'] ?>"><?= htmlspecialchars($alumno['alumno']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="asignacion">Asignación Docente</label>
                    <select name="id_asignacion_docente" id="asignacion" required>
                        <option value="">Selecciona asignación</option>
                        <?php foreach ($asignaciones as $asig): ?>
                            <option value="<?= $asig['id_asignacion_docente'] ?>">
                                <?= htmlspecialchars($asig['docente'] . ' - ' . $asig['materia']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>


                    <label for="calificacion_final">Calificación Final</label>
                    <input type="number" step="0.01" name="calificacion_final" id="calificacion_final" required>

                    <label for="observaciones">Observaciones</label>
                    <textarea name="observaciones" id="observaciones"></textarea>
                </fieldset>
                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR CALIFICACIÓN -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Registro</h2>
            <form id="formEditar">
                <fieldset>
                    <label for="editAlumno">Alumno</label>
                    <select name="id_alumno" id="editAlumno" required>
                        <option value="">Selecciona un alumno</option>
                        <?php foreach ($alumnos as $alumno): ?>
                            <option value="<?= $alumno['id_alumno'] ?>"><?= htmlspecialchars($alumno['alumno']) ?></option>
                        <?php endforeach; ?>
                    </select>



                    <label for="editAsignacion">Asignación Docente</label>
                    <select name="id_asignacion_docente" id="editAsignacion" required>
                        <option value="">Selecciona asignación</option>
                        <?php foreach ($asignaciones as $asig): ?>
                            <option value="<?= htmlspecialchars($asig['id_asignacion_docente'] ?? '') ?>">
                                <?= htmlspecialchars(($asig['docente'] ?? '-') . ' - ' . ($asig['materia'] ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>


                    <label for="editCalificacion">Calificación Final</label>
                    <input type="number" step="0.01" name="calificacion_final" id="editCalificacion" required>

                    <label for="editObservaciones">Observaciones</label>
                    <textarea name="observaciones" id="editObservaciones"></textarea>
                </fieldset>
                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModalEditar">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.rolUsuarioPHP = "<?= $rolUsuario; ?>";

        // Búsqueda
        document.getElementById('buscarCalificacion').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaCalificaciones tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    </script>

    <script src="/Plataforma_UT/js/DashboardY.js"></script>
    <script src="../../js/admin/Calificaciones.js"></script>
</body>

</html>