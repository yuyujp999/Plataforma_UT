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

// Obtener alumnos
$stmt = $pdo->query("SELECT * FROM alumnos");
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Alumnos Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/secretariasModales1.css" />
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
            <button class="hamburger" id="hamburger">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarAlumno" placeholder="Buscar..." />
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
                <div class="title">Gestión de Alumnos</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button class="btn btn-outline btn-sm" id="btnNuevoAlumno">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-user-graduate"></i> Alumnos</h3>
                </div>

                <!-- Tabla de alumnos -->
                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaAlumnos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido Paterno</th>
                                <th>Apellido Materno</th>
                                <th>CURP</th>
                                <th>Fecha Nacimiento</th>
                                <th>Sexo</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Correo</th>
                                <th>Matrícula</th>
                                <th>Contraseña</th>
                                <th>Carrera</th>
                                <th>Semestre</th>
                                <th>Grupo</th>
                                <th>Contacto Emergencia</th>
                                <th>Parentesco Emergencia</th>
                                <th>Teléfono Emergencia</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody">
                            <?php if (!empty($alumnos)): ?>
                            <?php foreach ($alumnos as $row): ?>
                            <tr data-id="<?= htmlspecialchars($row['id_alumno'] ?? '') ?>">
                                <td><?= htmlspecialchars($row['id_alumno'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['nombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['apellido_paterno'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['apellido_materno'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['curp'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['fecha_nacimiento'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['sexo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['telefono'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['direccion'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['correo_personal'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['matricula'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['password'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['carrera'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['semestre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['grupo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['contacto_emergencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['parentesco_emergencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['telefono_emergencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['fecha_registro'] ?? '') ?></td>
                                <td>
                                    <button class="btn btn-outline btn-sm btn-editar">
                                        <i class="fas fa-edit"></i>Editar
                                    </button>
                                    <button class="btn btn-outline btn-sm btn-eliminar">
                                        <i class="fas fa-trash"></i>Eliminar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="19">No hay alumnos registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container" id="paginationAlumno"></div>

                <!-- MODAL NUEVO ALUMNO -->
                <div class="modal-overlay" id="modalNuevoAlumno">
                    <div class="modal">
                        <button type="button" class="close-modal" id="closeModal">&times;</button>
                        <h2>Nuevo Alumno</h2>
                        <form id="formNuevo">
                            <fieldset>
                                <label for="nombre">Nombre *</label>
                                <input type="text" name="nombre" id="nombre" required>

                                <label for="apellido_paterno">Apellido Paterno *</label>
                                <input type="text" name="apellido_paterno" id="apellido_paterno" required>

                                <label for="apellido_materno">Apellido Materno</label>
                                <input type="text" name="apellido_materno" id="apellido_materno">

                                <label for="curp">CURP *</label>
                                <input type="text" name="curp" id="curp" required>

                                <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required>

                                <label for="sexo">Sexo *</label>
                                <select name="sexo" id="sexo" required>
                                    <option value="" disabled selected>Selecciona</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                <label for="telefono">Teléfono</label>
                                <input type="text" name="telefono" id="telefono">

                                <label for="direccion">Dirección</label>
                                <input type="text" name="direccion" id="direccion">

                                <label for="correo_personal">Correo</label>
                                <input type="email" name="correo" id="correo_personal">

                                <label for="matricula">Matrícula</label>
                                <input type="text" name="matricula" id="matricula" placeholder="Autogenerada" readonly>

                                <label for="password">Contraseña</label>
                                <input type="text" name="password" id="password" placeholder="Autogenerada" readonly>

                                <label for="carrera">Carrera *</label>
                                <input type="text" name="carrera" id="carrera" required>

                                <label for="semestre">Semestre *</label>
                                <input type="number" name="semestre" id="semestre" min="1" required>

                                <label for="grupo">Grupo *</label>
                                <input type="text" name="grupo" id="grupo" required>

                                <label for="contacto_emergencia">Contacto Emergencia</label>
                                <input type="text" name="contacto_emergencia" id="contacto_emergencia">

                                <label for="parentesco_emergencia">Parentesco Emergencia</label>
                                <input type="text" name="parentesco_emergencia" id="parentesco_emergencia">

                                <label for="telefono_emergencia">Teléfono Emergencia</label>
                                <input type="text" name="telefono_emergencia" id="telefono_emergencia">
                            </fieldset>

                            <div class="actions">
                                <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                                <button type="submit" class="btn-save">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL EDITAR ALUMNO -->
                <div class="modal-overlay" id="modalEditar">
                    <div class="modal">
                        <button type="button" class="close-modal" id="closeEditar">&times;</button>
                        <h2>Editar Alumno</h2>
                        <form id="formEditar">
                            <fieldset>
                                <label for="edit_nombre">Nombre *</label>
                                <input type="text" id="edit_nombre" name="nombre" required>

                                <label for="edit_apellido_paterno">Apellido Paterno *</label>
                                <input type="text" id="edit_apellido_paterno" name="apellido_paterno" required>

                                <label for="edit_apellido_materno">Apellido Materno</label>
                                <input type="text" id="edit_apellido_materno" name="apellido_materno">

                                <label for="edit_curp">CURP *</label>
                                <input type="text" id="edit_curp" name="curp" required>

                                <label for="edit_fecha_nacimiento">Fecha de Nacimiento *</label>
                                <input type="date" id="edit_fecha_nacimiento" name="fecha_nacimiento" required>

                                <label for="edit_sexo">Sexo *</label>
                                <select id="edit_sexo" name="sexo" required>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                <label for="edit_telefono">Teléfono</label>
                                <input type="text" id="edit_telefono" name="telefono">

                                <label for="edit_direccion">Dirección</label>
                                <input type="text" id="edit_direccion" name="direccion">

                                <label for="edit_correo_personal">Correo</label>
                                <input type="email" id="edit_correo_personal" name="correo_personal">

                                <label for="edit_matricula">Matrícula</label>
                                <input type="text" id="edit_matricula" name="matricula" readonly>

                                <label for="edit_password">Contraseña</label>
                                <input type="text" id="edit_password" name="password" readonly>

                                <label for="edit_carrera">Carrera *</label>
                                <input type="text" id="edit_carrera" name="carrera" required>

                                <label for="edit_semestre">Semestre *</label>
                                <input type="number" id="edit_semestre" name="semestre" min="1" required>

                                <label for="edit_grupo">Grupo *</label>
                                <input type="text" id="edit_grupo" name="grupo" required>

                                <label for="edit_contacto_emergencia">Contacto Emergencia</label>
                                <input type="text" id="edit_contacto_emergencia" name="contacto_emergencia">

                                <label for="edit_parentesco_emergencia">Parentesco Emergencia</label>
                                <input type="text" id="edit_parentesco_emergencia" name="parentesco_emergencia">

                                <label for="edit_telefono_emergencia">Teléfono Emergencia</label>
                                <input type="text" id="edit_telefono_emergencia" name="telefono_emergencia">
                            </fieldset>

                            <div class="actions">
                                <button type="button" class="btn-cancel" id="cancelEditar">Cancelar</button>
                                <button type="submit" class="btn-save">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>


                <script>
                window.rolUsuarioPHP = "<?= $rolUsuario; ?>";

                document.getElementById('buscarAlumno').addEventListener('keyup', function() {
                    const filtro = this.value.toLowerCase();
                    const filas = document.querySelectorAll('#tablaAlumnos tbody tr');
                    filas.forEach(fila => {
                        fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' :
                            'none';
                    });
                });
                </script>
                <script src="/Plataforma_UT/js/Dashboard.js"></script>
                <script src="../../js/admin/Alumno.js"></script>
</body>

</html>