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

function h($v)
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/**
 * Filtro de estatus (admin puede ver todos; por defecto 'todos')
 * valores esperados: 'activo' | 'baja' | 'suspendido' | 'todos'
 */
$estatusFiltro = isset($_GET['estatus']) ? strtolower(trim($_GET['estatus'])) : 'todos';
$estatusValidos = ['activo', 'baja', 'suspendido', 'todos'];
if (!in_array($estatusFiltro, $estatusValidos, true)) {
    $estatusFiltro = 'todos';
}

// Obtener docentes (ocultar soft-deletes)
if ($estatusFiltro === 'todos') {
    $stmt = $pdo->prepare("SELECT * FROM docentes WHERE deleted_at IS NULL ORDER BY id_docente ASC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM docentes WHERE deleted_at IS NULL AND estatus = :estatus ORDER BY id_docente DESC");
    $stmt->execute([':estatus' => $estatusFiltro]);
}
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

    <link rel="stylesheet" href="../../css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">

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

        /* Badge muy simple para estatus */
        .badge-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .st-activo {
            background: #e6f7ef;
            color: #0a8a4b;
            border: 1px solid #bdebd2;
        }

        .st-baja {
            background: #fff1f0;
            color: #b11b1b;
            border: 1px solid #ffc2bf;
        }

        .st-suspendido {
            background: #fff9e6;
            color: #8a6d0a;
            border: 1px solid #ffe6a3;
        }

        /* Muestra puntos en contraseña en vez de hash */
        .pwd-mask {
            font-family: 'password';
        }

        /* === FILTRO DE ESTATUS (ADMIN) === */
        .filtro-container {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border: 1px solid #e0e6ed;
            border-radius: 10px;
            padding: 12px 16px;
            margin: 10px 0 15px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .filtro-container label {
            font-weight: 600;
            font-size: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;


        }

        .filtro-container i {
            color: #00897b;
        }

        #filtroEstatus {
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        #filtroEstatus:hover {
            border-color: #00a884;
        }

        #filtroEstatus:focus {
            border-color: #00897b;
            box-shadow: 0 0 0 2px rgba(0, 137, 123, 0.15);
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
                <input type="text" id="buscarDocente" placeholder="Buscar Docentes..." />
            </div>
            <div class="header-actions">

                <div class="user-profile" id="userProfile" data-nombre="<?= h($nombreCompleto) ?>"
                    data-rol="<?= h($rolUsuario) ?>">
                    <div class="profile-img"><?= h($iniciales) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= h($nombreCompleto ?: 'Usuario') ?></div>
                        <div class="user-role"><?= h($rolUsuario ?: 'Rol') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-title">
                <div class="title">Gestión de Docentes</div>
                <div class="action-buttons">
                    <button class="btn btn-outline" id="btnExportar"><i class="fas fa-download"></i> Exportar</button>
                    <button class="btn btn-outline btn-sm" id="btnNuevo"><i class="fas fa-plus"></i> Nuevo
                        Docente</button>
                </div>
            </div>

            <!-- Filtro de Estatus (Admin) -->
            <!-- Filtro de Estatus (Admin) -->
            <div class="table-card" style="margin-top: 10px;">
                <div class="filtro-container">
                    <label for="filtroEstatus">
                        <i class="fas fa-filter"></i> Estatus:
                    </label>
                    <select id="filtroEstatus" class="input">
                        <option value="todos" <?= $estatusFiltro === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="activo" <?= $estatusFiltro === 'activo' ? 'selected' : ''; ?>>Activos</option>
                        <option value="baja" <?= $estatusFiltro === 'baja' ? 'selected' : ''; ?>>Baja</option>
                        <option value="suspendido" <?= $estatusFiltro === 'suspendido' ? 'selected' : ''; ?>>Suspendidos
                        </option>
                    </select>
                </div>


                <div class="card-title">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Docentes</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaDocentes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Estatus</th>
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
                                <th>Puesto</th>
                                <th>Tipo Contrato</th>
                                <th>Fecha Ingreso</th>
                                <th>Contacto Emergencia</th>
                                <th>Parentesco Emergencia</th>
                                <th>Teléfono Emergencia</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody">
                            <?php if (!empty($docentes)): ?>
                                <?php foreach ($docentes as $row):
                                    $st = strtolower($row['estatus'] ?? 'activo');
                                    $cls = $st === 'baja' ? 'st-baja' : ($st === 'suspendido' ? 'st-suspendido' : 'st-activo');
                                    ?>
                                    <tr data-id="<?= h($row['id_docente'] ?? '') ?>">
                                        <td><?= h($row['id_docente'] ?? '') ?></td>
                                        <td>
                                            <span class="badge-status <?= $cls ?>"><?= h($st) ?></span>
                                        </td>
                                        <td><?= h($row['nombre'] ?? '') ?></td>
                                        <td><?= h($row['apellido_paterno'] ?? '') ?></td>
                                        <td><?= h($row['apellido_materno'] ?? '') ?></td>
                                        <td><?= h($row['curp'] ?? '') ?></td>
                                        <td><?= h($row['rfc'] ?? '') ?></td>
                                        <td><?= h($row['fecha_nacimiento'] ?? '') ?></td>
                                        <td><?= h($row['sexo'] ?? '') ?></td>
                                        <td><?= h($row['telefono'] ?? '') ?></td>
                                        <td><?= h($row['direccion'] ?? '') ?></td>
                                        <td><?= h($row['correo_personal'] ?? '') ?></td>
                                        <td><?= h($row['matricula'] ?? '') ?></td>
                                        <td><?= h($row['password'] ?? '') ?></td>
                                        <td><?= h($row['nivel_estudios'] ?? '') ?></td>
                                        <td><?= h($row['area_especialidad'] ?? '') ?></td>
                                        <td><?= h($row['universidad_egreso'] ?? '') ?></td>
                                        <td><?= h($row['cedula_profesional'] ?? '') ?></td>
                                        <td><?= h($row['idiomas'] ?? '') ?></td>
                                        <td><?= h($row['puesto'] ?? '') ?></td>
                                        <td><?= h($row['tipo_contrato'] ?? '') ?></td>
                                        <td><?= h($row['fecha_ingreso'] ?? '') ?></td>
                                        <td><?= h($row['contacto_emergencia'] ?? '') ?></td>
                                        <td><?= h($row['parentesco_emergencia'] ?? '') ?></td>
                                        <td><?= h($row['telefono_emergencia'] ?? '') ?></td>
                                        <td><?= h($row['fecha_registro'] ?? '') ?></td>
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
                                    <td colspan="27">No hay docentes registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container" id="paginationDocentes"></div>
            </div>
        </div>
    </div>

    <!-- ========== MODAL NUEVO DOCENTE (SIN departamento / num_empleado) ========== -->
    <div class="modal-overlay" id="modalNuevo">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModal">&times;</button>
            <h2>Nuevo Docente</h2>
            <form id="formNuevo">
                <fieldset>
                    <label for="nombre">Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" id="nombre" required>

                    <label for="apellido_paterno">Apellido Paterno <span class="required">*</span></label>
                    <input type="text" name="apellido_paterno" id="apellido_paterno" required>

                    <label for="apellido_materno">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="apellido_materno">

                    <label for="curp">CURP <span class="required">*</span></label>
                    <input type="text" name="curp" id="curp" required>

                    <label for="rfc">RFC <span class="required">*</span></label>
                    <input type="text" name="rfc" id="rfc" required>

                    <label for="fecha_nacimiento">Fecha de Nacimiento <span class="required">*</span></label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required>

                    <label for="sexo">Sexo <span class="required">*</span></label>
                    <select name="sexo" id="sexo" required>
                        <option value="" disabled selected>Selecciona el sexo</option>
                        <option>Masculino</option>
                        <option>Femenino</option>
                        <option>Otro</option>
                    </select>

                    <label for="telefono">Teléfono</label>
                    <input type="text" name="telefono" id="telefono" inputmode="numeric" pattern="\d*" maxlength="15"
                        oninput="this.value=this.value.replace(/\D/g,'')">

                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion">

                    <label for="correo_personal">Correo Personal</label>
                    <input type="email" name="correo_personal" id="correo_personal">

                    <label for="matricula">Matrícula</label>
                    <input type="text" name="matricula" id="matricula" placeholder="Autogenerada" readonly>

                    <label for="password">Contraseña</label>
                    <input type="text" name="password" id="password" placeholder="Autogenerada" readonly>

                    <label for="nivel_estudios">Nivel Estudios <span class="required">*</span></label>
                    <select name="nivel_estudios" id="nivel_estudios" required>
                        <option value="" disabled selected>Selecciona el nivel</option>
                        <option>Licenciatura</option>
                        <option>Maestría</option>
                        <option>Doctorado</option>
                        <option>Otro</option>
                    </select>

                    <label for="area_especialidad">Área Especialidad</label>
                    <input type="text" name="area_especialidad" id="area_especialidad" placeholder="Opcional">

                    <label for="universidad_egreso">Universidad Egreso</label>
                    <input type="text" name="universidad_egreso" id="universidad_egreso">

                    <label for="cedula_profesional">Cédula Profesional</label>
                    <input type="text" name="cedula_profesional" id="cedula_profesional" placeholder="Opcional">

                    <label for="idiomas">Idiomas</label>
                    <input type="text" name="idiomas" id="idiomas" placeholder="Ej: Español, Inglés">

                    <label for="puesto">Puesto <span class="required">*</span></label>
                    <input type="text" name="puesto" id="puesto" required placeholder="Ej: Profesor de Asignatura">

                    <label for="tipo_contrato">Tipo Contrato <span class="required">*</span></label>
                    <select name="tipo_contrato" id="tipo_contrato" required>
                        <option value="" disabled selected>Selecciona tipo de contrato</option>
                        <option>Tiempo Completo</option>
                        <option>Medio Tiempo</option>
                        <option>Asignatura</option>
                        <option>Honorarios</option>
                    </select>

                    <label for="fecha_ingreso">Fecha Ingreso <span class="required">*</span></label>
                    <input type="date" name="fecha_ingreso" id="fecha_ingreso" required>

                    <label for="contacto_emergencia">Contacto Emergencia</label>
                    <input type="text" name="contacto_emergencia" id="contacto_emergencia">

                    <label for="parentesco_emergencia">Parentesco Emergencia</label>
                    <input type="text" name="parentesco_emergencia" id="parentesco_emergencia">

                    <label for="telefono_emergencia">Teléfono Emergencia</label>
                    <input type="text" name="telefono_emergencia" id="telefono_emergencia" inputmode="numeric"
                        pattern="\d*" maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== MODAL EDITAR DOCENTE (SIN departamento / num_empleado) ========== -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="close-modal" id="closeEditar">&times;</button>
            <h2>Editar Docente</h2>
            <form id="formEditar">
                <fieldset>
                    <label for="edit_nombre">Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" id="edit_nombre" required>

                    <label for="edit_apellido_paterno">Apellido Paterno <span class="required">*</span></label>
                    <input type="text" name="apellido_paterno" id="edit_apellido_paterno" required>

                    <label for="edit_apellido_materno">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="edit_apellido_materno">

                    <label for="edit_curp">CURP <span class="required">*</span></label>
                    <input type="text" name="curp" id="edit_curp" required>

                    <label for="edit_rfc">RFC <span class="required">*</span></label>
                    <input type="text" name="rfc" id="edit_rfc" required>

                    <label for="edit_fecha_nacimiento">Fecha de Nacimiento <span class="required">*</span></label>
                    <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" required>

                    <label for="edit_sexo">Sexo <span class="required">*</span></label>
                    <select name="sexo" id="edit_sexo" required>
                        <option value="" disabled selected>Selecciona el sexo</option>
                        <option>Masculino</option>
                        <option>Femenino</option>
                        <option>Otro</option>
                    </select>

                    <label for="edit_telefono">Teléfono</label>
                    <input type="text" name="telefono" id="edit_telefono" inputmode="numeric" pattern="\d*"
                        maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">

                    <label for="edit_direccion">Dirección</label>
                    <input type="text" name="direccion" id="edit_direccion">

                    <label for="edit_correo_personal">Correo Personal</label>
                    <input type="email" name="correo_personal" id="edit_correo_personal">

                    <label for="edit_matricula">Matrícula</label>
                    <input type="text" id="edit_matricula" placeholder="Autogenerada" readonly>

                    <label for="edit_password">Contraseña</label>
                    <input type="text" id="edit_password" placeholder="No disponible (no editable)" readonly>

                    <label for="edit_nivel_estudios">Nivel Estudios <span class="required">*</span></label>
                    <select name="nivel_estudios" id="edit_nivel_estudios" required>
                        <option value="" disabled selected>Selecciona el nivel</option>
                        <option>Licenciatura</option>
                        <option>Maestría</option>
                        <option>Doctorado</option>
                        <option>Otro</option>
                    </select>

                    <label for="edit_area_especialidad">Área Especialidad</label>
                    <input type="text" name="area_especialidad" id="edit_area_especialidad" placeholder="Opcional">

                    <label for="edit_universidad_egreso">Universidad Egreso</label>
                    <input type="text" name="universidad_egreso" id="edit_universidad_egreso">

                    <label for="edit_cedula_profesional">Cédula Profesional</label>
                    <input type="text" name="cedula_profesional" id="edit_cedula_profesional" placeholder="Opcional">

                    <label for="edit_idiomas">Idiomas</label>
                    <input type="text" name="idiomas" id="edit_idiomas" placeholder="Ej: Español, Inglés">

                    <label for="edit_puesto">Puesto <span class="required">*</span></label>
                    <input type="text" name="puesto" id="edit_puesto" required placeholder="Ej: Profesor de Asignatura">

                    <label for="edit_tipo_contrato">Tipo Contrato <span class="required">*</span></label>
                    <select name="tipo_contrato" id="edit_tipo_contrato" required>
                        <option value="" disabled selected>Selecciona tipo de contrato</option>
                        <option>Tiempo Completo</option>
                        <option>Medio Tiempo</option>
                        <option>Asignatura</option>
                        <option>Honorarios</option>
                    </select>

                    <label for="edit_fecha_ingreso">Fecha Ingreso <span class="required">*</span></label>
                    <input type="date" name="fecha_ingreso" id="edit_fecha_ingreso" required>

                    <label for="edit_contacto_emergencia">Contacto Emergencia</label>
                    <input type="text" name="contacto_emergencia" id="edit_contacto_emergencia">

                    <label for="edit_parentesco_emergencia">Parentesco Emergencia</label>
                    <input type="text" name="parentesco_emergencia" id="edit_parentesco_emergencia">

                    <label for="edit_telefono_emergencia">Teléfono Emergencia</label>
                    <input type="text" name="telefono_emergencia" id="edit_telefono_emergencia" inputmode="numeric"
                        pattern="\d*" maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
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

        // Buscador por texto
        document.getElementById('buscarDocente').addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaDocentes tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });

        // Filtro de estatus: recarga con querystring ?estatus=...
        document.getElementById('filtroEstatus')?.addEventListener('change', function () {
            const v = this.value || 'todos';
            const url = new URL(window.location.href);
            url.searchParams.set('estatus', v);
            window.location.href = url.toString();
        });
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/admin/Docentes3.js"></script>

</body>

</html>