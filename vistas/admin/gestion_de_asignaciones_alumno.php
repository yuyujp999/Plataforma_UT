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

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener asignaciones
$stmt = $pdo->query("
    SELECT a.id_asignacion, a.id_alumno, a.id_carrera, a.id_grado, a.id_ciclo, a.grupo, a.fecha_asignacion,
           CONCAT(al.nombre,' ',al.apellido_paterno,' ',al.apellido_materno) AS alumno,
           c.nombre_carrera AS carrera,
           g.nombre_grado AS grado,
           ci.nombre_ciclo AS ciclo
    FROM asignaciones_alumnos a
    INNER JOIN alumnos al ON a.id_alumno = al.id_alumno
    INNER JOIN carreras c ON a.id_carrera = c.id_carrera
    INNER JOIN grados g ON a.id_grado = g.id_grado
    INNER JOIN ciclos_escolares ci ON a.id_ciclo = ci.id_ciclo
    ORDER BY a.id_asignacion ASC
");
$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listas para selects
$alumnos = $pdo->query("SELECT id_alumno, CONCAT(nombre,' ',apellido_paterno,' ',apellido_materno) AS alumno FROM alumnos ORDER BY id_alumno ASC")->fetchAll(PDO::FETCH_ASSOC);
$carreras = $pdo->query("SELECT id_carrera, nombre_carrera FROM carreras ORDER BY id_carrera ASC")->fetchAll(PDO::FETCH_ASSOC);
$grados = $pdo->query("SELECT id_grado, nombre_grado FROM grados ORDER BY id_grado ASC")->fetchAll(PDO::FETCH_ASSOC);
$ciclos = $pdo->query("SELECT id_ciclo, nombre_ciclo FROM ciclos_escolares ORDER BY id_ciclo ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Asignaciones de Alumnos</title>
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
                <input type="text" id="buscarAsignacion" placeholder="Buscar..." />
            </div>
            <div class="header-actions">
                <div class="notification"><i class="fas fa-bell"></i>
                    <div class="badge">3</div>
                </div>
                <div class="notification"><i class="fas fa-envelope"></i>
                    <div class="badge">5</div>
                </div>
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
                <div class="title">Gestión de Asignaciones de Alumnos</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar"><i class="fas fa-download"></i> Exportar</button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo"><i class="fas fa-plus"></i> Nuevo</button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-user-graduate"></i> Asignaciones</h3>
                </div>
                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaAsignaciones">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Alumno</th>
                                <th>Carrera</th>
                                <th>Grado</th>
                                <th>Ciclo Escolar</th>
                                <th>Grupo</th>
                                <th>Fecha Asignación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($asignaciones)): ?>
                                <?php foreach ($asignaciones as $row): ?>
                                    <tr data-id="<?= $row['id_asignacion'] ?>" data-alumno="<?= $row['id_alumno'] ?>"
                                        data-carrera="<?= $row['id_carrera'] ?>" data-grado="<?= $row['id_grado'] ?>"
                                        data-ciclo="<?= $row['id_ciclo'] ?>"
                                        data-grupo="<?= htmlspecialchars($row['grupo']) ?>">
                                        <td><?= $row['id_asignacion'] ?></td>
                                        <td><?= htmlspecialchars($row['alumno']) ?></td>
                                        <td><?= htmlspecialchars($row['carrera']) ?></td>
                                        <td><?= htmlspecialchars($row['grado']) ?></td>
                                        <td><?= htmlspecialchars($row['ciclo']) ?></td>
                                        <td><?= htmlspecialchars($row['grupo']) ?></td>
                                        <td><?= $row['fecha_asignacion'] ?></td>
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
                                    <td colspan="8">No hay asignaciones registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVA ASIGNACIÓN -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nueva Asignación</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="alumno">Alumno</label>
                    <select name="id_alumno" id="alumno" required>
                        <option value="">Selecciona un alumno</option>
                        <?php foreach ($alumnos as $al): ?>
                            <option value="<?= $al['id_alumno'] ?>"><?= htmlspecialchars($al['alumno']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="carrera">Carrera</label>
                    <select name="id_carrera" id="carrera" required>
                        <option value="">Selecciona una carrera</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= $c['id_carrera'] ?>"><?= htmlspecialchars($c['nombre_carrera']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="grado">Grado</label>
                    <select name="id_grado" id="grado" required>
                        <option value="">Selecciona un grado</option>
                        <?php foreach ($grados as $g): ?>
                            <option value="<?= $g['id_grado'] ?>"><?= htmlspecialchars($g['nombre_grado']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="ciclo">Ciclo Escolar</label>
                    <select name="id_ciclo" id="ciclo" required>
                        <option value="">Selecciona un ciclo</option>
                        <?php foreach ($ciclos as $ci): ?>
                            <option value="<?= $ci['id_ciclo'] ?>"><?= htmlspecialchars($ci['nombre_ciclo']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="grupo">Grupo</label>
                    <input type="text" name="grupo" id="grupo" placeholder="Ej. A">
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR ASIGNACIÓN -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Asignación</h2>
            <form id="formEditar">
                <fieldset>
                    <label for="editAlumno">Alumno</label>
                    <select name="id_alumno" id="editAlumno" required>
                        <option value="">Selecciona un alumno</option>
                        <?php foreach ($alumnos as $al): ?>
                            <option value="<?= $al['id_alumno'] ?>"><?= htmlspecialchars($al['alumno']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editCarrera">Carrera</label>
                    <select name="id_carrera" id="editCarrera" required>
                        <option value="">Selecciona una carrera</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= $c['id_carrera'] ?>"><?= htmlspecialchars($c['nombre_carrera']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editGrado">Grado</label>
                    <select name="id_grado" id="editGrado" required>
                        <option value="">Selecciona un grado</option>
                        <?php foreach ($grados as $g): ?>
                            <option value="<?= $g['id_grado'] ?>"><?= htmlspecialchars($g['nombre_grado']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editCiclo">Ciclo Escolar</label>
                    <select name="id_ciclo" id="editCiclo" required>
                        <option value="">Selecciona un ciclo</option>
                        <?php foreach ($ciclos as $ci): ?>
                            <option value="<?= $ci['id_ciclo'] ?>"><?= htmlspecialchars($ci['nombre_ciclo']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editGrupo">Grupo</label>
                    <input type="text" name="grupo" id="editGrupo" placeholder="Ej. A">
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

        // BUSCAR
        document.getElementById('buscarAsignacion').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaAsignaciones tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    </script>

    <script src="/Plataforma_UT/js/DashboardY.js"></script>
    <script src="../../js/admin/AsignacionesAlumnos.js"></script>
</body>

</html>