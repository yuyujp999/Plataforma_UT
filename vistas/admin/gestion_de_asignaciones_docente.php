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
    SELECT a.id_asignacion_docente, a.id_docente, a.id_materia, a.id_grado, a.id_ciclo, a.grupo,
           CONCAT(d.nombre,' ',d.apellido_paterno,' ',d.apellido_materno) AS docente,
           m.nombre_materia AS materia,
           g.nombre_grado AS grado,
           c.nombre_ciclo AS ciclo
    FROM asignaciones_docentes a
    INNER JOIN docentes d ON a.id_docente = d.id_docente
    INNER JOIN materias m ON a.id_materia = m.id_materia
    INNER JOIN grados g ON a.id_grado = g.id_grado
    INNER JOIN ciclos_escolares c ON a.id_ciclo = c.id_ciclo
    ORDER BY a.id_asignacion_docente ASC
");
$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listas para selects
$docentes = $pdo->query("SELECT id_docente, CONCAT(nombre,' ',apellido_paterno,' ',apellido_materno) AS docente FROM docentes ORDER BY id_docente ASC")->fetchAll(PDO::FETCH_ASSOC);
$materias = $pdo->query("SELECT id_materia, nombre_materia FROM materias ORDER BY id_materia ASC")->fetchAll(PDO::FETCH_ASSOC);
$grados = $pdo->query("SELECT id_grado, nombre_grado FROM grados ORDER BY id_grado ASC")->fetchAll(PDO::FETCH_ASSOC);
$ciclos = $pdo->query("SELECT id_ciclo, nombre_ciclo FROM ciclos_escolares ORDER BY id_ciclo ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Asignaciones de Docentes</title>
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
                <div class="title">Gestión de Asignaciones de Docentes</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar"><i class="fas fa-download"></i> Exportar</button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo"><i class="fas fa-plus"></i> Nuevo</button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Asignaciones</h3>
                </div>
                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaAsignaciones">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Docente</th>
                                <th>Materia</th>
                                <th>Grado</th>
                                <th>Ciclo Escolar</th>
                                <th>Grupo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($asignaciones)): ?>
                                <?php foreach ($asignaciones as $row): ?>
                                    <tr data-id="<?= $row['id_asignacion_docente'] ?>" data-docente="<?= $row['id_docente'] ?>"
                                        data-materia="<?= $row['id_materia'] ?>" data-grado="<?= $row['id_grado'] ?>"
                                        data-ciclo="<?= $row['id_ciclo'] ?>"
                                        data-grupo="<?= htmlspecialchars($row['grupo']) ?>">
                                        <td><?= $row['id_asignacion_docente'] ?></td>
                                        <td><?= htmlspecialchars($row['docente']) ?></td>
                                        <td><?= htmlspecialchars($row['materia']) ?></td>
                                        <td><?= htmlspecialchars($row['grado']) ?></td>
                                        <td><?= htmlspecialchars($row['ciclo']) ?></td>
                                        <td><?= htmlspecialchars($row['grupo']) ?></td>
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
                                    <td colspan="7">No hay asignaciones registradas.</td>
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
                    <label for="docente">Docente</label>
                    <select name="id_docente" id="docente" required>
                        <option value="">Selecciona un docente</option>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= $d['id_docente'] ?>"><?= htmlspecialchars($d['docente']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="materia">Materia</label>
                    <select name="id_materia" id="materia" required>
                        <option value="">Selecciona una materia</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?= $m['id_materia'] ?>"><?= htmlspecialchars($m['nombre_materia']) ?></option>
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
                        <?php foreach ($ciclos as $c): ?>
                            <option value="<?= $c['id_ciclo'] ?>"><?= htmlspecialchars($c['nombre_ciclo']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="grupo">Grupo</label>
                    <input type="text" name="grupo" id="grupo" placeholder="Ej. A" required>
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
                    <label for="editDocente">Docente</label>
                    <select name="id_docente" id="editDocente" required>
                        <option value="">Selecciona un docente</option>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= $d['id_docente'] ?>"><?= htmlspecialchars($d['docente']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editMateria">Materia</label>
                    <select name="id_materia" id="editMateria" required>
                        <option value="">Selecciona una materia</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?= $m['id_materia'] ?>"><?= htmlspecialchars($m['nombre_materia']) ?></option>
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
                        <?php foreach ($ciclos as $c): ?>
                            <option value="<?= $c['id_ciclo'] ?>"><?= htmlspecialchars($c['nombre_ciclo']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editGrupo">Grupo</label>
                    <input type="text" name="grupo" id="editGrupo" placeholder="Ej. A" required>
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
    <script src="../../js/admin/AsignacionesDocente.js"></script>
</body>

</html>