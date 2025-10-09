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

// Obtener docentes
$stmt = $pdo->query("SELECT * FROM docentes");
$docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Docentes Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />



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
                <input type="text" id="buscarDocente" placeholder="Buscar..." />
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
                <div class="title">Gestión de Docentes</div>
                <div class="action-buttons">


                    <button class="btn btn-outline" id="btnExportar">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>

                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Docentes</h3>

                </div>


                <!-- Contenedor scroll horizontal -->
                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaDocentes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido Paterno</th>
                                <th>Apellido Materno</th>
                                <th>CURP</th>
                                <th>RFC</th>
                                <th>Fecha Nacimiento</th>
                                <th>Sexo</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Correo</th>
                                <th>Matrícula</th>
                                <th>Contraseña</th>
                                <th>Nivel Estudios</th>
                                <th>Área Especialidad</th>
                                <th>Universidad Egreso</th>
                                <th>Cédula Profesional</th>
                                <th>Idiomas</th>
                                <th>Departamento</th>
                                <th>Puesto</th>
                                <th>Tipo Contrato</th>
                                <th>Fecha Ingreso</th>
                                <th>Num. Empleado</th>
                                <th>Contacto Emergencia</th>
                                <th>Parentesco Emergencia</th>
                                <th>Teléfono Emergencia</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody">
                            <?php if (!empty($docentes)): ?>
                            <?php foreach ($docentes as $row): ?>
                            <tr data-id="<?= htmlspecialchars($row['id_docente'] ?? '') ?>">
                                <td><?= htmlspecialchars($row['id_docente'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['nombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['apellido_paterno'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['apellido_materno'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['curp'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['rfc'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['fecha_nacimiento'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['sexo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['telefono'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['direccion'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['correo_personal'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['matricula'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['password'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['nivel_estudios'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['area_especialidad'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['universidad_egreso'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['cedula_profesional'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['idiomas'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['departamento'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['puesto'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['tipo_contrato'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['fecha_ingreso'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['num_empleado'] ?? '') ?></td>
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
                                <td colspan="28">No hay docentes registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>


                <!-- Contenedor de paginación -->
                <div class="pagination-container" id="pagination"></div>
                <!-- MODAL NUEVO DOCENTE (MATRÍCULA Y CONTRASEÑA BLOQUEADAS) -->
                <div class="modal-overlay" id="modalNuevo">
                    <div class="modal">
                        <button type="button" class="close-modal" id="closeModal">&times;</button>
                        <h2>Nuevo Docente</h2>
                        <form id="formNuevo">
                            <fieldset>
                                <!-- Datos personales -->
                                <label for="nombre">Nombre <span class="required">*</span></label>
                                <input type="text" name="nombre" id="nombre" placeholder="Ej. Juan" required>

                                <label for="apellido_paterno">Apellido Paterno <span class="required">*</span></label>
                                <input type="text" name="apellido_paterno" id="apellido_paterno" placeholder="Ej. Pérez"
                                    required>

                                <label for="apellido_materno">Apellido Materno</label>
                                <input type="text" name="apellido_materno" id="apellido_materno"
                                    placeholder="Ej. López">

                                <label for="curp">CURP <span class="required">*</span></label>
                                <input type="text" name="curp" id="curp" placeholder="Ej. PEJL800101HDFXXX00" required>

                                <label for="rfc">RFC <span class="required">*</span></label>
                                <input type="text" name="rfc" id="rfc" placeholder="Ej. PEJL800101XXX" required>

                                <label for="fecha_nacimiento">Fecha de Nacimiento <span
                                        class="required">*</span></label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required>

                                <label for="sexo">Sexo <span class="required">*</span></label>
                                <select name="sexo" id="sexo" required>
                                    <option value="" disabled selected>Selecciona el sexo</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                <label for="telefono">Teléfono</label>
                                <input type="text" name="telefono" id="telefono" placeholder="Ej. 55-1234-5678">

                                <label for="direccion">Dirección</label>
                                <input type="text" name="direccion" id="direccion"
                                    placeholder="Ej. Calle 123, Colonia, Ciudad">

                                <label for="correo_personal">Correo Personal</label>
                                <input type="email" name="correo_personal" id="correo_personal"
                                    placeholder="ejemplo@mail.com">

                                <!-- Bloqueados -->
                                <label for="matricula">Matrícula</label>
                                <input type="text" name="matricula" id="matricula" placeholder="Autogenerada" readonly>

                                <label for="password">Contraseña</label>
                                <input type="text" name="password" id="password" placeholder="Autogenerada" readonly>

                                <!-- Académicos -->
                                <label for="nivel_estudios">Nivel Estudios <span class="required">*</span></label>
                                <select name="nivel_estudios" id="nivel_estudios" required>
                                    <option value="" disabled selected>Selecciona el nivel</option>
                                    <option value="Licenciatura">Licenciatura</option>
                                    <option value="Maestría">Maestría</option>
                                    <option value="Doctorado">Doctorado</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                <label for="area_especialidad">Área Especialidad</label>
                                <input type="text" name="area_especialidad" id="area_especialidad"
                                    placeholder="Ej. Matemáticas">

                                <label for="universidad_egreso">Universidad Egreso</label>
                                <input type="text" name="universidad_egreso" id="universidad_egreso"
                                    placeholder="Ej. UNAM">

                                <label for="cedula_profesional">Cédula Profesional</label>
                                <input type="text" name="cedula_profesional" id="cedula_profesional"
                                    placeholder="Opcional">

                                <label for="idiomas">Idiomas</label>
                                <input type="text" name="idiomas" id="idiomas" placeholder="Ej. Inglés, Francés">

                                <!-- Laborales -->
                                <label for="departamento">Departamento <span class="required">*</span></label>
                                <input type="text" name="departamento" id="departamento" placeholder="Ej. Ciencias"
                                    required>

                                <label for="puesto">Puesto <span class="required">*</span></label>
                                <input type="text" name="puesto" id="puesto" placeholder="Ej. Profesor" required>

                                <label for="tipo_contrato">Tipo Contrato <span class="required">*</span></label>
                                <select name="tipo_contrato" id="tipo_contrato" required>
                                    <option value="" disabled selected>Selecciona tipo de contrato</option>
                                    <option value="Tiempo Completo">Tiempo Completo</option>
                                    <option value="Medio Tiempo">Medio Tiempo</option>
                                    <option value="Asignatura">Asignatura</option>
                                </select>

                                <label for="fecha_ingreso">Fecha Ingreso <span class="required">*</span></label>
                                <input type="date" name="fecha_ingreso" id="fecha_ingreso" required>

                                <label for="num_empleado">Número de Empleado</label>
                                <input type="text" name="num_empleado" id="num_empleado" placeholder="Opcional">

                                <!-- Emergencia -->
                                <label for="contacto_emergencia">Contacto Emergencia</label>
                                <input type="text" name="contacto_emergencia" id="contacto_emergencia"
                                    placeholder="Ej. Juan Pérez">

                                <label for="parentesco_emergencia">Parentesco Emergencia</label>
                                <input type="text" name="parentesco_emergencia" id="parentesco_emergencia"
                                    placeholder="Ej. Hermano">

                                <label for="telefono_emergencia">Teléfono Emergencia</label>
                                <input type="text" name="telefono_emergencia" id="telefono_emergencia"
                                    placeholder="Ej. 55-1234-5678">
                            </fieldset>

                            <div class="actions">
                                <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                                <button type="submit" class="btn-save">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL EDITAR DOCENTE PROFESIONAL (MATRÍCULA Y CONTRASEÑA BLOQUEADOS) -->
                <div class="modal-overlay" id="modalEditar">
                    <div class="modal">
                        <button type="button" class="close-modal" id="closeEditar">&times;</button>
                        <h2>Editar Docente</h2>
                        <form id="formEditar">
                            <fieldset>
                                <label for="edit_nombre">Nombre <span class="required">*</span></label>
                                <input type="text" name="nombre" id="edit_nombre" placeholder="Ej. Juan" required>

                                <label for="edit_apellido_paterno">Apellido Paterno <span
                                        class="required">*</span></label>
                                <input type="text" name="apellido_paterno" id="edit_apellido_paterno"
                                    placeholder="Ej. Pérez" required>

                                <label for="edit_apellido_materno">Apellido Materno</label>
                                <input type="text" name="apellido_materno" id="edit_apellido_materno"
                                    placeholder="Ej. López">

                                <label for="edit_curp">CURP <span class="required">*</span></label>
                                <input type="text" name="curp" id="edit_curp" placeholder="Ej. PEJL800101HDFXXX00"
                                    required>

                                <label for="edit_rfc">RFC <span class="required">*</span></label>
                                <input type="text" name="rfc" id="edit_rfc" placeholder="Ej. PEJL800101XXX" required>

                                <label for="edit_fecha_nacimiento">Fecha de Nacimiento <span
                                        class="required">*</span></label>
                                <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" required>

                                <label for="edit_sexo">Sexo <span class="required">*</span></label>
                                <select name="sexo" id="edit_sexo" required>
                                    <option value="" disabled selected>Selecciona el sexo</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                <label for="edit_telefono">Teléfono</label>
                                <input type="text" name="telefono" id="edit_telefono" placeholder="Ej. 55-1234-5678">

                                <label for="edit_direccion">Dirección</label>
                                <input type="text" name="direccion" id="edit_direccion"
                                    placeholder="Ej. Calle 123, Colonia, Ciudad">

                                <label for="edit_correo_personal">Correo Personal</label>
                                <input type="email" name="correo_personal" id="edit_correo_personal"
                                    placeholder="ejemplo@mail.com">

                                <label for="edit_matricula">Matrícula</label>
                                <input type="text" name="matricula" id="edit_matricula" placeholder="Autogenerada"
                                    readonly>

                                <label for="edit_password">Contraseña</label>
                                <input type="text" name="password" id="edit_password" placeholder="Autogenerada"
                                    readonly>

                                <label for="edit_nivel_estudios">Nivel Estudios <span class="required">*</span></label>
                                <select name="nivel_estudios" id="edit_nivel_estudios" required>
                                    <option value="" disabled selected>Selecciona el nivel</option>
                                    <option value="Licenciatura">Licenciatura</option>
                                    <option value="Maestría">Maestría</option>
                                    <option value="Doctorado">Doctorado</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                <label for="edit_area_especialidad">Área Especialidad</label>
                                <input type="text" name="area_especialidad" id="edit_area_especialidad"
                                    placeholder="Ej. Matemáticas">

                                <label for="edit_universidad_egreso">Universidad Egreso</label>
                                <input type="text" name="universidad_egreso" id="edit_universidad_egreso"
                                    placeholder="Ej. UNAM">

                                <label for="edit_cedula_profesional">Cédula Profesional</label>
                                <input type="text" name="cedula_profesional" id="edit_cedula_profesional"
                                    placeholder="Opcional">

                                <label for="edit_idiomas">Idiomas</label>
                                <input type="text" name="idiomas" id="edit_idiomas" placeholder="Ej. Inglés, Francés">

                                <label for="edit_departamento">Departamento <span class="required">*</span></label>
                                <input type="text" name="departamento" id="edit_departamento" placeholder="Ej. Ciencias"
                                    required>

                                <label for="edit_puesto">Puesto <span class="required">*</span></label>
                                <input type="text" name="puesto" id="edit_puesto" placeholder="Ej. Profesor" required>

                                <label for="edit_tipo_contrato">Tipo Contrato <span class="required">*</span></label>
                                <select name="tipo_contrato" id="edit_tipo_contrato" required>
                                    <option value="" disabled selected>Selecciona tipo de contrato</option>
                                    <option value="Tiempo Completo">Tiempo Completo</option>
                                    <option value="Medio Tiempo">Medio Tiempo</option>
                                    <option value="Asignatura">Asignatura</option>
                                </select>

                                <label for="edit_fecha_ingreso">Fecha Ingreso <span class="required">*</span></label>
                                <input type="date" name="fecha_ingreso" id="edit_fecha_ingreso" required>

                                <label for="edit_num_empleado">Número de Empleado</label>
                                <input type="text" name="num_empleado" id="edit_num_empleado" placeholder="Opcional">

                                <label for="edit_contacto_emergencia">Contacto Emergencia</label>
                                <input type="text" name="contacto_emergencia" id="edit_contacto_emergencia"
                                    placeholder="Ej. Juan Pérez">

                                <label for="edit_parentesco_emergencia">Parentesco Emergencia</label>
                                <input type="text" name="parentesco_emergencia" id="edit_parentesco_emergencia"
                                    placeholder="Ej. Hermano">

                                <label for="edit_telefono_emergencia">Teléfono Emergencia</label>
                                <input type="text" name="telefono_emergencia" id="edit_telefono_emergencia"
                                    placeholder="Ej. 55-1234-5678">
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

                document.getElementById('buscarDocente').addEventListener('keyup', function() {
                    const filtro = this.value.toLowerCase();
                    const filas = document.querySelectorAll('#tablaDocentes tbody tr');
                    filas.forEach(fila => {
                        fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' :
                            'none';
                    });
                });
                </script>
                <script src="/Plataforma_UT/js/Dashboard.js"></script>
                <script src="../../js/admin/Docentes2.js"></script>

</body>

</html>