<?php
session_start();

// Debug (apágalo en prod)
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Mensajes</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <!-- Tus hojas -->
    <link rel="stylesheet" href="../../css/admin/admin.css" />
    <link rel="stylesheet" href="/Plataforma_UT/css/styleD.css" />
    <link rel="stylesheet" href="../../css/admin/adminModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesoresModal.css" />
    <link rel="stylesheet" href="../../css/admin/profesores.css" />
    <link rel="stylesheet" href="../../css/admin/mensajes1.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../../img/ut_logo.png" sizes="32x32" type="image/png">

    <!-- Estilos mínimos para chips/enlaces -->
    <style>
        .modal-overlay {
            z-index: 3000;
        }

        .chips-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            background: #e6f4ea;
            border: 1px solid #cfe7db;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            color: #2c3e35;
        }

        .chip-link {
            color: #2c7a7b;
            font-weight: 600;
            cursor: pointer;
        }

        .chip-link:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 122, 123, .2);
            border-radius: 6px;
        }

        .detail-actions {
            display: flex;
            gap: 10px;
            margin: -6px 0 10px;
        }

        .icon-btn {
            background: #f3f6f7;
            border: 1px solid #e1e7ea;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
        }

        .icon-btn:hover {
            filter: brightness(.98);
        }

        .icon-btn.danger {
            background: #fdecec;
            border-color: #f7c9c9;
            color: #b21515;
        }

        /* Solo nombre (sin chip) para la destinataria seleccionada en detalle */
        .destinataria-badge {
            font-weight: 700;
            color: #2c3e35;
            background: #f5f8f9;
            border: 1px solid #e1e7ea;
            padding: 6px 10px;
            border-radius: 10px;
        }

        .nota-todas {
            display: none;
            margin-top: 6px;
            font-size: 12px;
            color: #2c7a7b;
            font-weight: 600;
        }

        /* Modo edición para mostrar el bloque de destinataria */
        #modalDetalle.is-editing #editarDestinatariasBlock {
            display: block !important;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- ===== Sidebar ===== -->
        <div class="sidebar" id="sidebar">
            <div class="overlay" id="overlay"></div>
            <div class="logo">
                <h1>UT<span>Panel</span></h1>
            </div>
            <div class="nav-menu" id="menu">
                <div class="menu-heading">Menú</div>
            </div>
        </div>

        <!-- ===== Header ===== -->
        <div class="header">
            <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarMateria" placeholder="Buscar Mensajes..." />
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

        <!-- ===== Main ===== -->
        <div class="main-content">
            <div class="page-title">
                <div class="title">Gestión de Mensajes</div>
                <div class="action-buttons">
                    <button class="btn btn-outline btn-sm" id="btnNuevo" title="Nuevo Mensaje">
                        <i class="fas fa-plus"></i> Nuevo Mensaje
                    </button>
                </div>
            </div>

            <!-- ===== Card + Tabla ===== -->
            <div class="table-card">
                <div class="table-scroll-wrapper">
                    <div class="table-header">
                        <h3><i class="fas fa-envelope"></i> Mensajes enviados</h3>
                    </div>
                    <table class="data-table" id="tablaMensajes">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Prioridad</th>
                                <th>Fecha de envío</th>
                                <th>Leídos</th>
                                <th style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBodyMensajes">
                            <tr>
                                <td colspan="5">Sin mensajes enviados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- /card -->
        </div>
    </div>

    <!-- ===== Modal: Nuevo Mensaje ===== -->
    <div class="modal-overlay" id="overlayNuevo">
        <div class="modal">
            <button class="close-modal" id="cerrarNuevo" aria-label="Cerrar">&times;</button>
            <h2><i class="fas fa-envelope"></i> Nuevo Mensaje</h2>

            <form id="formNuevoMensaje">
                <fieldset>
                    <div>
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" required maxlength="150"
                            placeholder="Escribe un título..." />
                    </div>

                    <div>
                        <label for="prioridad">Prioridad</label>
                        <select id="prioridad" name="prioridad">
                            <option value="normal">Normal</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>

                    <div style="grid-column: 1 / span 2;">
                        <label for="cuerpo">Contenido</label>
                        <textarea id="cuerpo" name="cuerpo" rows="6" required
                            placeholder="Escribe el contenido del mensaje..."></textarea>
                    </div>

                    <div style="grid-column: 1 / span 2;">
                        <label>Destinatarias</label>
                        <div class="radio-row">
                            <label><input type="radio" name="destino" value="todas" checked> Todas las
                                secretarías</label>
                            <label><input type="radio" name="destino" value="especificas"> Secretarías
                                específicas</label>
                        </div>
                    </div>

                    <!-- Selector secretarías (oculto por defecto) -->
                    <div style="grid-column: 1 / span 2; display:none;" id="selectSecretariasContainer">
                        <label for="secretarias">Selecciona secretarías</label>
                        <select id="secretarias" name="secretarias[]" multiple></select>
                        <small id="contadorSecretarias" style="display:block; margin-top:6px; opacity:.75;">0
                            seleccionadas</small>

                        <!-- Resumen de selección (chip + texto) -->
                        <div id="resumenSeleccion" class="chips-row" style="display:none; margin-top:8px;">
                            <div class="chips" id="chipsContainer"></div>
                            <span id="editarSeleccion" class="chip-link" tabindex="0">Cambiar selección</span>
                        </div>
                    </div>
                </fieldset>

                <div class="actions">
                    <button type="button" class="btn-cancel" id="cancelNuevo">Cancelar</button>
                    <button type="button" class="btn-save" id="guardarMensaje">Enviar Mensaje</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Modal: Detalle Mensaje ===== -->
    <div class="modal-overlay" id="overlayDetalle">
        <div class="modal" id="modalDetalle">
            <button class="close-modal" id="cerrarDetalle" aria-label="Cerrar">&times;</button>
            <h2><i class="fas fa-envelope-open-text"></i> Detalle del Mensaje</h2>

            <!-- Barra de acciones (Editar / Eliminar) -->
            <div class="detail-actions">
                <button type="button" class="icon-btn" id="btnEditarMensaje" title="Editar mensaje">
                    <i class="fas fa-pen-to-square"></i>
                </button>
                <button type="button" class="icon-btn danger" id="btnEliminarMensaje" title="Eliminar mensaje">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <fieldset>
                <!-- id oculto para usar en edición/borrado -->
                <input type="hidden" id="detalleIdMensaje" />

                <div>
                    <label>Título</label>
                    <input type="text" id="detalleTitulo" readonly />
                </div>
                <div>
                    <label>Prioridad</label>
                    <input type="text" id="detallePrioridad" readonly />
                </div>
                <div style="grid-column:1 / span 2;">
                    <label>Contenido</label>
                    <textarea id="detalleCuerpo" rows="6" readonly></textarea>
                </div>
                <div style="grid-column:1 / span 2;">
                    <label>Fecha de envío</label>
                    <input type="text" id="detalleFecha" readonly />
                </div>

                <!-- Tabla de destinatarias (siempre visible) -->
                <div style="grid-column:1 / span 2;">
                    <div class="table-card" style="margin:0;">
                        <div class="table-scroll-wrapper">
                            <div class="table-header">
                                <h3><i class="fas fa-user-check"></i> Secretarías destinatarias</h3>
                            </div>
                            <table class="data-table" id="tablaDestinatarias">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Leído</th>
                                        <th>Fecha de lectura</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Editor de DESTINATARIA (SIMPLE: una sola o TODAS) -->
                <div style="grid-column:1 / span 2; display:none;" id="editarDestinatariasBlock">
                    <label for="selEditarSecretarias">Editar destinataria</label>
                    <!-- IMPORTANTE: sin "multiple" para que sea UNA sola (o TODAS) -->
                    <select id="selEditarSecretarias"></select>

                    <small id="contadorEditarSecretarias" style="display:block; margin-top:6px; opacity:.75;">
                        0 seleccionadas
                    </small>

                    <!-- Nota visible cuando se eligió "todas" -->
                    <div id="notaTodas" class="nota-todas">
                        🟢 Mensaje para <strong>todas las secretarías</strong>.
                    </div>

                    <!-- Vista colapsada: SOLO nombre (o "todas") + enlace Cambiar -->
                    <div id="resumenEditarSeleccion" class="chips-row" style="display:none; margin-top:8px;">
                        <span class="destinataria-badge" id="destinatariaSeleccionadaTexto">—</span>
                        <span id="editarSeleccionDetalle" class="chip-link" tabindex="0">Cambiar destinataria</span>
                    </div>
                </div>
            </fieldset>

            <div class="actions" style="justify-content:flex-start; gap:10px;">
                <button type="button" class="btn-cancel" id="cerrarDetalleBtn">Cerrar</button>

                <!-- Guardar cambios generales (incluye destinataria) -->
                <button type="button" class="btn-save btn-save-edit" id="btnGuardarCambios">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>

    <script>
        // Exponer datos a JS
        window.rolUsuarioPHP = "<?= $rolUsuario; ?>";
        window.usuarioNombrePHP = "<?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?>";

        // Auto-resize para textareas dentro de modales
        function autoResize(ta) {
            ta.style.height = "auto";
            ta.style.height = ta.scrollHeight + "px";
        }
        document.addEventListener("input", (e) => {
            if (e.target.matches('.modal textarea')) autoResize(e.target);
        });
        window.addEventListener("load", () => {
            document.querySelectorAll('.modal textarea').forEach(autoResize);
        });
    </script>

    <!-- Scripts -->
    <script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
    <script src="../../js/admin/Mensajes.js"></script>
</body>

</html>