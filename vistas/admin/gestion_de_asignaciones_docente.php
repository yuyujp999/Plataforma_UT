<?php
session_start();

// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sesión
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

/* =========================
   LISTADO (JOIN con catálogos)
   ========================= */
$sql = "
SELECT
  a.id_asignacion_docente,
  a.id_docente,
  a.id_nombre_materia,
  a.id_nombre_profesor_materia_grupo,
  CONCAT(d.nombre,' ',d.apellido_paterno,' ',COALESCE(d.apellido_materno,'')) AS docente,
  cm.nombre AS clave_materia,
  cpmg.nombre AS profesor_materia_grupo
FROM asignaciones_docentes a
JOIN docentes d ON d.id_docente = a.id_docente
LEFT JOIN cat_nombres_materias cm ON cm.id_nombre_materia = a.id_nombre_materia
LEFT JOIN cat_nombre_profesor_materia_grupo cpmg ON cpmg.id_nombre_profesor_materia_grupo = a.id_nombre_profesor_materia_grupo
ORDER BY a.id_asignacion_docente ASC";
$asignaciones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   SELECTS
   ========================= */
$docentes = $pdo->query("
  SELECT id_docente, CONCAT(nombre,' ',apellido_paterno,' ',COALESCE(apellido_materno,'')) AS docente
  FROM docentes
  ORDER BY docente
")->fetchAll(PDO::FETCH_ASSOC);

$clavesMaterias = $pdo->query("
  SELECT id_nombre_materia, nombre
  FROM cat_nombres_materias
  ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);
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
            </div>
        </div>

        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarAsignacion" placeholder="Buscar Docentes Asignados..." />
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
                                <th>Clave Materia</th>
                                <th>Profesor-Materia-Grupo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($asignaciones):
                                foreach ($asignaciones as $row): ?>
                                    <tr data-id="<?= (int) $row['id_asignacion_docente'] ?>"
                                        data-id-docente="<?= (int) $row['id_docente'] ?>"
                                        data-id-nombre-materia="<?= (int) $row['id_nombre_materia'] ?>"
                                        data-id-cpmg="<?= (int) $row['id_nombre_profesor_materia_grupo'] ?>"
                                        data-cpmg-nombre="<?= htmlspecialchars($row['profesor_materia_grupo'] ?? '') ?>">
                                        <td><?= (int) $row['id_asignacion_docente'] ?></td>
                                        <td><?= htmlspecialchars($row['docente']) ?></td>
                                        <td><?= htmlspecialchars($row['clave_materia'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($row['profesor_materia_grupo'] ?? '—') ?></td>
                                        <td>
                                            <button class="btn btn-outline btn-sm btn-editar"><i class="fas fa-edit"></i>
                                                Editar</button>
                                            <button class="btn btn-outline btn-sm btn-eliminar"><i class="fas fa-trash"></i>
                                                Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5">No hay asignaciones registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO -->
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
                            <option value="<?= (int) $d['id_docente'] ?>"><?= htmlspecialchars($d['docente']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="materia">Clave de Materia</label>
                    <select name="id_nombre_materia" id="materia" required>
                        <option value="">Selecciona una clave</option>
                        <?php foreach ($clavesMaterias as $m): ?>
                            <option value="<?= (int) $m['id_nombre_materia'] ?>"><?= htmlspecialchars($m['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="nombreProfesorGrupo">Profesor-Materia-Grupo</label>
                    <input type="text" id="nombreProfesorGrupo" name="nombre_profesor_materia_grupo" readonly
                        placeholder="Se generará automáticamente">
                    <input type="hidden" name="id_nombre_profesor_materia_grupo" id="idNombreProfesorGrupoNuevo">
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR -->
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
                            <option value="<?= (int) $d['id_docente'] ?>"><?= htmlspecialchars($d['docente']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editMateria">Clave de Materia</label>
                    <select name="id_nombre_materia" id="editMateria" required>
                        <option value="">Selecciona una clave</option>
                        <?php foreach ($clavesMaterias as $m): ?>
                            <option value="<?= (int) $m['id_nombre_materia'] ?>"><?= htmlspecialchars($m['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editNombreProfesorGrupo">Profesor-Materia-Grupo</label>
                    <input type="text" id="editNombreProfesorGrupo" name="nombre_profesor_materia_grupo" readonly
                        placeholder="Se generará automáticamente">
                    <input type="hidden" name="id_nombre_profesor_materia_grupo" id="idNombreProfesorGrupoEditar">
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModalEditar">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Autogenerar "Profesor {Docente} - {Clave}"
        function actualizarNombrePMg(selectDoc, selectMat, output) {
            const docente = selectDoc.options[selectDoc.selectedIndex]?.text || '';
            const clave = selectMat.options[selectMat.selectedIndex]?.text || '';
            output.value = (docente && clave) ? `Profesor ${docente} - ${clave}` : '';
        }

        // NUEVO
        const selDocN = document.getElementById('docente');
        const selMatN = document.getElementById('materia');
        const outN = document.getElementById('nombreProfesorGrupo');
        selDocN.addEventListener('change', () => actualizarNombrePMg(selDocN, selMatN, outN));
        selMatN.addEventListener('change', () => actualizarNombrePMg(selDocN, selMatN, outN));

        // EDITAR
        const selDocE = document.getElementById('editDocente');
        const selMatE = document.getElementById('editMateria');
        const outE = document.getElementById('editNombreProfesorGrupo');
        selDocE.addEventListener('change', () => actualizarNombrePMg(selDocE, selMatE, outE));
        selMatE.addEventListener('change', () => actualizarNombrePMg(selDocE, selMatE, outE));
    </script>

    <script>
        window.rolUsuarioPHP =
            "<?= $rolUsuario; ?>"; // BÚSQUEDA EN TIEMPO REAL document.getElementById('buscarAsignacion').addEventListener('keyup', function () { const filtro = this.value.toLowerCase(); const filas = document.querySelectorAll('#tablaAsignaciones tbody tr'); filas.forEach(fila => { fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none'; }); }); 
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/admin/AsignacionesDocentes3.js"></script>
</body>

</html>