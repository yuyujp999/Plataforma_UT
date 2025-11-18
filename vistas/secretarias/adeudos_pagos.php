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

// ===== Permisos por rol (SOLO SECRETARÍA) =====
$esSecretaria = in_array(strtolower($rolUsuario), ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

$permisos = [
    'crear' => $esSecretaria,
    'editar' => $esSecretaria,
    'eliminar' => false,
    'exportar' => $esSecretaria,
];

// Si no es secretaría, lo sacamos
if (!$esSecretaria) {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener pagos (incluyendo nombre del alumno por matrícula)
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.matricula,
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno,
        p.periodo,
        p.concepto,
        p.monto,
        p.adeudo,
        p.pago,
        p.condonacion,
        p.fecha_registro
    FROM pagos p
    LEFT JOIN alumnos a ON a.matricula = p.matricula
    ORDER BY p.fecha_registro DESC, p.id DESC
");
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Pagos (Secretaría)</title>

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

    <style>
        .is-hidden {
            display: none !important;
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

        /* Campo matrícula + botón buscar */
        .matricula-group {
            display: flex;
            gap: 0.5rem;
            align-items: stretch;
        }

        .matricula-group input[type="text"],
        .matricula-group input[type="number"] {
            flex: 1;
            height: 40px;
        }

        .btn-icon {
            border: none;
            outline: none;
            height: 40px;
            min-width: 40px;
            padding: 0;
            border-radius: 10px;
            background: #0f766e;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon i {
            font-size: 0.95rem;
        }

        /* ===== Modal Buscar Alumno (pegado al lado) ===== */
        .modal-buscar-alumno {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }

        .modal-buscar-alumno .modal {
            max-width: 520px;
            width: 100%;
            max-height: 70vh;
            margin: 2.5rem 2.5rem 2rem auto;
            display: flex;
            flex-direction: column;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.26);
        }

        .modal-buscar-alumno h2 {
            margin-bottom: 0.25rem;
        }

        .modal-buscar-alumno .subtitle {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 1rem;
        }

        /* Buscador dentro del modal */
        .search-input-wrapper {
            position: relative;
            margin-bottom: 0.8rem;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-65%);
            /* ← SUBIDA REAL */
            font-size: 1rem;
            color: #64748b;
            pointer-events: none;
        }



        .search-input-wrapper input {
            width: 100%;
            height: 40px;
            padding: 0 0.85rem 0 2.75rem;
            border-radius: 999px;
            border: 1px solid #0f766e;
            font-size: 0.9rem;
            background-color: #f8fafc;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: #0f766e;
            background-color: #ffffff;
            box-shadow: 0 0 0 1px rgba(15, 118, 110, 0.25);
        }

        .modal-buscar-alumno .table-wrapper {
            flex: 1;
            min-height: 120px;
            max-height: 40vh;
            overflow: hidden;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .modal-buscar-alumno .table-wrapper .data-table {
            margin: 0;
            border-radius: 0;
        }

        .modal-buscar-alumno .table-wrapper thead th {
            position: sticky;
            top: 0;
            background: #f1f5f9;
            z-index: 1;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Más compacto SOLO para la tabla del modal */
        #tablaResultadosAlumnos thead th {
            padding: 0.35rem 0.75rem;
        }

        #tablaResultadosAlumnos tbody td {
            padding: 0.25rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }

        #tablaResultadosAlumnos tbody tr {
            cursor: pointer;
            transition: background-color 0.12s ease, transform 0.05s ease;
        }

        #tablaResultadosAlumnos tbody tr:hover {
            background-color: #e0f2fe;
        }

        #tablaResultadosAlumnos tbody tr:active {
            transform: scale(0.995);
        }

        /* Ancho mínimo para que se vea la matrícula completa */
        #tablaResultadosAlumnos th:first-child {
            min-width: 130px;
        }

        #tablaResultadosAlumnos td.matricula-cell {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            /* un poco más cerca matrícula y botón */
            white-space: nowrap;
            font-weight: 600;
            color: #0f172a;
            min-width: 130px;
        }

        #tablaResultadosAlumnos td.nombre-cell {
            color: #1e293b;
        }

        .btnSelectAlumno {
            border: none;
            background: #dcfce7;
            color: #15803d;
            border-radius: 999px;
            padding: 0.1rem 0.6rem;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btnSelectAlumno:hover {
            background: #bbf7d0;
        }

        .modal-buscar-alumno .actions {
            margin-top: 0.8rem;
            display: flex;
            justify-content: flex-end;
        }

        .modal-buscar-alumno .actions .btn-cancel {
            min-width: 120px;
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
                <!-- El menú se llena vía JS -->
            </div>
        </div>

        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarPago" placeholder="Buscar pagos..." />
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
                <div class="title">Gestión de Pagos</div>
                <div class="action-buttons">
                    <?php if ($permisos['exportar']): ?>
                        <!-- Botón exportar (opcional) -->
                    <?php endif; ?>
                    <?php if ($permisos['crear']): ?>
                        <button class="btn btn-outline btn-sm" id="btnNuevo">
                            <i class="fas fa-plus"></i> Nuevo Pago
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <div class="card-title">
                    <h3><i class="fas fa-money-check-dollar"></i> Pagos Registrados</h3>
                </div>

                <div class="table-container" style="overflow-x:auto;">
                    <table class="data-table" id="tablaPagos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Matrícula</th>
                                <th>Alumno</th>
                                <th>Período</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Adeudo</th>
                                <th>Pago</th>
                                <th>Condonación</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pagos)): ?>
                                <?php foreach ($pagos as $row):
                                    $nombreAlumno = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' ' . ($row['apellido_materno'] ?? ''));
                                    ?>
                                    <tr data-id="<?= htmlspecialchars($row['id']) ?>"
                                        data-matricula="<?= htmlspecialchars($row['matricula']) ?>"
                                        data-periodo="<?= htmlspecialchars($row['periodo']) ?>"
                                        data-concepto="<?= htmlspecialchars($row['concepto']) ?>"
                                        data-monto="<?= htmlspecialchars($row['monto']) ?>"
                                        data-adeudo="<?= htmlspecialchars($row['adeudo']) ?>"
                                        data-pago="<?= htmlspecialchars($row['pago']) ?>"
                                        data-condonacion="<?= htmlspecialchars($row['condonacion']) ?>">
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['matricula']) ?></td>
                                        <td><?= htmlspecialchars($nombreAlumno ?: 'Sin nombre') ?></td>
                                        <td><?= htmlspecialchars($row['periodo']) ?></td>
                                        <td><?= htmlspecialchars($row['concepto']) ?></td>
                                        <td>$<?= number_format($row['monto'], 2) ?></td>
                                        <td>$<?= number_format($row['adeudo'], 2) ?></td>
                                        <td>$<?= number_format($row['pago'], 2) ?></td>
                                        <td>$<?= number_format($row['condonacion'], 2) ?></td>
                                        <td><?= htmlspecialchars($row['fecha_registro']) ?></td>
                                        <td>
                                            <?php if ($permisos['editar']): ?>
                                                <button class="btn btn-outline btn-sm btn-editar">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($permisos['eliminar']): ?>
                                                <button class="btn btn-outline btn-sm btn-eliminar">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11">No hay pagos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="paginationPagos"></div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO PAGO -->
    <?php if ($permisos['crear']): ?>
        <div class="modal-overlay" id="modalNuevo">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModal">&times;</button>
                <h2>Nuevo Pago / Cargo</h2>
                <form id="formNuevo" autocomplete="off">
                    <fieldset>
                        <label for="matricula">Matrícula</label>
                        <div class="matricula-group">
                            <input type="text" name="matricula" id="matricula" required>
                            <button type="button" class="btn-icon" id="btnBuscarMatriculaNuevo" title="Buscar alumno">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>

                        <label for="periodo">Período</label>
                        <input type="text" name="periodo" id="periodo" placeholder="Ej. Septiembre-Diciembre 2024" required>

                        <label for="concepto">Concepto</label>
                        <input type="text" name="concepto" id="concepto" required>

                        <label for="monto">Monto</label>
                        <input type="number" step="0.01" name="monto" id="monto" min="0" required>

                        <label for="adeudo">Adeudo</label>
                        <input type="number" step="0.01" name="adeudo" id="adeudo" min="0" value="0">

                        <label for="pago">Pago</label>
                        <input type="number" step="0.01" name="pago" id="pago" min="0" value="0">

                        <label for="condonacion">Condonación</label>
                        <input type="number" step="0.01" name="condonacion" id="condonacion" min="0" value="0">
                    </fieldset>

                    <div class="actions">
                        <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL EDITAR PAGO -->
    <?php if ($permisos['editar']): ?>
        <div class="modal-overlay" id="modalEditar">
            <div class="modal">
                <button type="button" class="close-modal" id="closeModalEditar">&times;</button>
                <h2>Editar Pago / Cargo</h2>
                <form id="formEditar" autocomplete="off">
                    <fieldset>
                        <input type="hidden" name="id" id="editId">

                        <label for="editMatricula">Matrícula</label>
                        <div class="matricula-group">
                            <input type="text" name="matricula" id="editMatricula" required>
                            <button type="button" class="btn-icon" id="btnBuscarMatriculaEditar" title="Buscar alumno">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>

                        <label for="editPeriodo">Período</label>
                        <input type="text" name="periodo" id="editPeriodo" required>

                        <label for="editConcepto">Concepto</label>
                        <input type="text" name="concepto" id="editConcepto" required>

                        <label for="editMonto">Monto</label>
                        <input type="number" step="0.01" name="monto" id="editMonto" min="0" required>

                        <label for="editAdeudo">Adeudo</label>
                        <input type="number" step="0.01" name="adeudo" id="editAdeudo" min="0">

                        <label for="editPago">Pago</label>
                        <input type="number" step="0.01" name="pago" id="editPago" min="0">

                        <label for="editCondonacion">Condonación</label>
                        <input type="number" step="0.01" name="condonacion" id="editCondonacion" min="0">
                    </fieldset>

                    <div class="actions">
                        <button type="button" class="btn-cancel" id="cancelModalEditar">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL BUSCAR ALUMNO -->
    <div class="modal-overlay modal-buscar-alumno is-hidden" id="modalBuscarAlumno">
        <div class="modal">
            <button type="button" class="close-modal" id="closeModalBuscarAlumno">&times;</button>
            <h2>Buscar Alumno</h2>
            <p class="subtitle">Escribe parte del nombre, apellidos o la matrícula y selecciona de la lista.</p>

            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="inputBuscarAlumno" placeholder="Nombre, apellidos o matrícula...">
            </div>

            <div class="table-wrapper">
                <div class="table-container" style="max-height:40vh; overflow-y:auto; margin:0;">
                    <table class="data-table" id="tablaResultadosAlumnos">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nombre</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena por JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="actions">
                <button type="button" class="btn-cancel" id="cancelModalBuscarAlumno">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        window.rolUsuarioPHP = "<?= htmlspecialchars($rolUsuario, ENT_QUOTES) ?>";
        window.PERMISOS = <?= json_encode($permisos) ?>;

        // Buscador rápido de pagos en la tabla
        document.getElementById('buscarPago')?.addEventListener('keyup', function () {
            const filtro = this.value.toLowerCase();
            document.querySelectorAll('#tablaPagos tbody tr').forEach(fila => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    </script>

    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/secretarias/Pagos.js"></script>
</body>

</html>