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

// Obtener materias
$stmt = $pdo->query("SELECT m.id_materia, m.nombre_materia, m.clave, m.horas_semana, m.id_grado, g.nombre_grado 
                     FROM materias m 
                     LEFT JOIN grados g ON m.id_grado = g.id_grado");
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener grados para el select
$stmtGrados = $pdo->query("SELECT id_grado, nombre_grado FROM grados");
$grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Materias</title>
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
                <input type="text" id="buscarMateria" placeholder="Buscar..." />
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
                <div class="title">Gestión de Materias</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar"><i class="fas fa-download"></i> Exportar</button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo"><i class="fas fa-plus"></i> Nueva</button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-book"></i> Materias</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaMaterias">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Clave</th>
                                <th>Horas/Semana</th>
                                <th>Grado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($materias)): ?>
                                <?php foreach ($materias as $row): ?>
                                    <tr data-id="<?= htmlspecialchars($row['id_materia']) ?>"
                                        data-nombre="<?= htmlspecialchars($row['nombre_materia']) ?>"
                                        data-clave="<?= htmlspecialchars($row['clave']) ?>"
                                        data-horas="<?= htmlspecialchars($row['horas_semana']) ?>"
                                        data-grado="<?= htmlspecialchars($row['id_grado']) ?>">
                                        <td><?= htmlspecialchars($row['id_materia']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre_materia']) ?></td>
                                        <td><?= htmlspecialchars($row['clave']) ?></td>
                                        <td><?= htmlspecialchars($row['horas_semana']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre_grado']) ?></td>
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
                                    <td colspan="6">No hay materias registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVA MATERIA -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nueva Materia</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="nombre_materia">Nombre de la Materia</label>
                    <input type="text" name="nombre_materia" id="nombre_materia" required>

                    <label for="clave">Clave</label>
                    <input type="text" name="clave" id="clave">

                    <label for="horas_semana">Horas/Semana</label>
                    <input type="number" name="horas_semana" id="horas_semana" min="1" value="4">

                    <label for="id_grado">Grado</label>
                    <select name="id_grado" id="id_grado" required>
                        <option value="">Selecciona un grado</option>
                        <?php foreach ($grados as $g): ?>
                            <option value="<?= $g['id_grado'] ?>"><?= $g['nombre_grado'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR MATERIA -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
            <h2>Editar Materia</h2>
            <form id="formEditar">
                <fieldset>
                    <label for="editNombreMateria">Nombre de la Materia</label>
                    <input type="text" name="nombre_materia" id="editNombreMateria" required>

                    <label for="editClave">Clave</label>
                    <input type="text" name="clave" id="editClave">

                    <label for="editHorasSemana">Horas/Semana</label>
                    <input type="number" name="horas_semana" id="editHorasSemana" min="1" required>

                    <label for="editIdGrado">Grado</label>
                    <select name="id_grado" id="editIdGrado" required>
                        <option value="">Selecciona un grado</option>
                        <?php foreach ($grados as $g): ?>
                            <option value="<?= $g['id_grado'] ?>"><?= $g['nombre_grado'] ?></option>
                        <?php endforeach; ?>
                    </select>
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

        document.getElementById('buscarMateria').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaMaterias tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    </script>

    <script src="/Plataforma_UT/js/DashboardY.js"></script>
    <script src="../../js/admin/Materia.js"></script>
</body>

</html>