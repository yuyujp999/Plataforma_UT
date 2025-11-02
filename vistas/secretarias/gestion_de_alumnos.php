<?php
session_start();

// Mostrar errores para debug (apaga en prod)
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

/**
 * Enlaces clave:
 * alumnos.id_nombre_semestre (FK nullable) --> cat_nombres_semestre.id_nombre_semestre
 */

// === CONSULTA ALUMNOS ===
$sqlAlumnos = "
    SELECT
        a.id_alumno,
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno,
        a.curp,
        a.fecha_nacimiento,
        a.sexo,
        a.telefono,
        a.direccion,
        a.correo_personal,
        a.matricula,
        a.password,
        a.id_nombre_semestre,
        ns.nombre AS nombre_semestre,
        a.contacto_emergencia,
        a.parentesco_emergencia,
        a.telefono_emergencia,
        a.fecha_registro,
        COALESCE(LOWER(TRIM(a.estatus)),'') AS estatus
    FROM alumnos a
    LEFT JOIN cat_nombres_semestre ns
        ON a.id_nombre_semestre = ns.id_nombre_semestre
    WHERE a.deleted_at IS NULL
    ORDER BY a.id_alumno ASC
";
$alumnos = $pdo->query($sqlAlumnos)->fetchAll(PDO::FETCH_ASSOC);

// === CONSULTA SEMESTRES PARA SELECT ===
$sqlSemestres = "
    SELECT DISTINCT ns.id_nombre_semestre, ns.nombre
    FROM cat_nombres_semestre ns
    JOIN semestres s ON s.id_nombre_semestre = ns.id_nombre_semestre
    ORDER BY ns.nombre ASC
";
$semestres = $pdo->query($sqlSemestres)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Alumnos (Secretarías)</title>

    <!-- Estilos existentes del proyecto -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="../../css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/secretarias/secretarias.css" />

    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .table-container {
            overflow-x: auto;
        }

        .data-table td,
        .data-table th {
            white-space: nowrap;
        }

        .modal fieldset {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .modal fieldset label {
            font-weight: 600;
        }

        .modal fieldset input,
        .modal fieldset select {
            width: 100%;
        }

        @media (max-width: 1024px) {
            .modal fieldset {
                grid-template-columns: 1fr;
            }
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            overflow-y: auto;
            z-index: 1000;
        }

        /* Estados visuales */
        .row-baja {
            background: #fff5f5;
            opacity: .92;
        }

        .row-suspendido {
            background: #fffaf0;
            opacity: .95;
        }

        .estado-badge {
            margin-left: 8px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .3px;
            vertical-align: middle;
            display: inline-block;
            border: 1px solid transparent;
            text-transform: capitalize;
        }

        .estado-badge.baja {
            background: #ffe0e0;
            color: #b11b1b;
            border-color: #ffc2bf;
        }

        .estado-badge.suspendido {
            background: #fff3cd;
            color: #8a6d0a;
            border-color: #ffe08a;
        }


        /* amarillo muy tenue */

        .estado-badge.baja {
            background: #fde1e4;
            color: #9c1c28;
            border-color: #f8c6cc;
        }

        .estado-badge.suspendido {
            background: #fff3c8;
            color: #7a6510;
            border-color: #ffe08a;
        }
    </style>
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
                <input type="text" id="buscarAlumno" placeholder="Buscar Alumnos..." />
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
                <div class="title">Gestión de Alumnos</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar"><i class="fas fa-download"></i> Exportar</button>
                    <button class="btn btn-outline btn-sm" id="btnNuevoAlumno"><i class="fas fa-plus"></i> Nuevo
                        Alumno</button>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-user-graduate"></i> Alumnos</h3>
                </div>

                <div class="table-container">
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
                                <th>Semestre</th>
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
                                    <tr data-id="<?= htmlspecialchars($row['id_alumno']) ?>"
                                        data-estatus="<?= htmlspecialchars($row['estatus'] ?? '') ?>">
                                        <td><?= htmlspecialchars($row['id_alumno']) ?></td>
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
                                        <td><?= htmlspecialchars($row['nombre_semestre'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($row['contacto_emergencia'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['parentesco_emergencia'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['telefono_emergencia'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['fecha_registro'] ?? '') ?></td>
                                        <td>
                                            <button class="btn btn-outline btn-sm btn-editar" title="Editar"><i
                                                    class="fas fa-edit"></i> Editar</button>
                                            <button class="btn btn-outline btn-sm btn-baja" title="Dar de baja / Reactivar"><i
                                                    class="fas fa-user-slash"></i> Baja</button>
                                            <button class="btn btn-outline btn-sm btn-susp" title="Suspender / Activar"><i
                                                    class="fas fa-user-clock"></i> Susp</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="18">No hay alumnos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container" id="paginationAlumno"></div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO ALUMNO -->
    <div class="modal-overlay" id="modalNuevoAlumno">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nuevo Alumno</h2>
            <form id="formNuevo" autocomplete="off">
                <fieldset>
                    <label for="nombre">Nombre *</label>
                    <input type="text" name="nombre" id="nombre" required>

                    <label for="apellido_paterno">Apellido Paterno *</label>
                    <input type="text" name="apellido_paterno" id="apellido_paterno" required>

                    <label for="apellido_materno">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="apellido_materno">

                    <label for="curp">CURP *</label>
                    <input type="text" name="curp" id="curp" maxlength="18" required
                        oninput="this.value=this.value.toUpperCase()">

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
                    <input type="tel" name="telefono" id="telefono" inputmode="numeric" pattern="[0-9]{7,15}"
                        title="Ingresa solo números (7 a 15 dígitos)"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">

                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion">

                    <label for="correo_personal">Correo</label>
                    <input type="email" name="correo_personal" id="correo_personal">

                    <label for="id_nombre_semestre">Semestre *</label>
                    <select name="id_nombre_semestre" id="id_nombre_semestre" required>
                        <option value="" disabled selected>Selecciona un semestre</option>
                        <?php foreach ($semestres as $s): ?>
                            <option value="<?= htmlspecialchars($s['id_nombre_semestre']) ?>">
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="contacto_emergencia">Contacto Emergencia</label>
                    <input type="text" name="contacto_emergencia" id="contacto_emergencia"
                        pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]{2,60}" title="Solo letras y espacios (2 a 60 caracteres)"
                        oninput="this.value=this.value.replace(/[0-9]/g,'')">

                    <label for="parentesco_emergencia">Parentesco Emergencia</label>
                    <input type="text" name="parentesco_emergencia" id="parentesco_emergencia">

                    <label for="telefono_emergencia">Teléfono Emergencia</label>
                    <input type="tel" name="telefono_emergencia" id="telefono_emergencia" inputmode="numeric"
                        pattern="[0-9]{7,15}" title="Ingresa solo números (7 a 15 dígitos)"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">

                    <!-- Autogenerados por backend -->
                    <input type="hidden" name="matricula" id="matricula">
                    <input type="hidden" name="password" id="password">
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
            <form id="formEditar" autocomplete="off">
                <input type="hidden" id="edit_id_alumno" name="id_alumno">
                <fieldset>
                    <label for="edit_nombre">Nombre *</label>
                    <input type="text" name="nombre" id="edit_nombre" required>

                    <label for="edit_apellido_paterno">Apellido Paterno *</label>
                    <input type="text" name="apellido_paterno" id="edit_apellido_paterno" required>

                    <label for="edit_apellido_materno">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="edit_apellido_materno">

                    <label for="edit_curp">CURP *</label>
                    <input type="text" name="curp" id="edit_curp" maxlength="18" required
                        oninput="this.value=this.value.toUpperCase()">

                    <label for="edit_fecha_nacimiento">Fecha de Nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" required>

                    <label for="edit_sexo">Sexo *</label>
                    <select name="sexo" id="edit_sexo" required>
                        <option value="" disabled selected>Selecciona</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>

                    <label for="edit_telefono">Teléfono</label>
                    <input type="tel" name="telefono" id="edit_telefono" inputmode="numeric" pattern="[0-9]{7,15}"
                        title="Ingresa solo números (7 a 15 dígitos)"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">

                    <label for="edit_direccion">Dirección</label>
                    <input type="text" name="direccion" id="edit_direccion">

                    <label for="edit_correo_personal">Correo</label>
                    <input type="email" name="correo_personal" id="edit_correo_personal">

                    <label for="edit_matricula">Matrícula</label>
                    <input type="text" name="matricula" id="edit_matricula" readonly>

                    <label for="edit_password">Contraseña</label>
                    <input type="text" name="password" id="edit_password" readonly>

                    <label for="edit_id_nombre_semestre">Semestre *</label>
                    <select name="id_nombre_semestre" id="edit_id_nombre_semestre" required>
                        <option value="" disabled selected>Selecciona un semestre</option>
                        <?php foreach ($semestres as $s): ?>
                            <option value="<?= htmlspecialchars($s['id_nombre_semestre']) ?>">
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="edit_contacto_emergencia">Contacto Emergencia</label>
                    <input type="text" name="contacto_emergencia" id="edit_contacto_emergencia"
                        pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]{2,60}" title="Solo letras y espacios (2 a 60 caracteres)"
                        oninput="this.value=this.value.replace(/[0-9]/g,'')">

                    <label for="edit_parentesco_emergencia">Parentesco Emergencia</label>
                    <input type="text" name="parentesco_emergencia" id="edit_parentesco_emergencia">

                    <label for="edit_telefono_emergencia">Teléfono Emergencia</label>
                    <input type="tel" name="telefono_emergencia" id="edit_telefono_emergencia" inputmode="numeric"
                        pattern="[0-9]{7,15}" title="Ingresa solo números (7 a 15 dígitos)"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">
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
    </script>
    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/secretarias/Alumnos1.js"></script>
</body>

</html>