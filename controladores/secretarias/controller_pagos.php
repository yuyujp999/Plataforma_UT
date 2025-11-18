<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Silenciar notices/warnings en salida (para no romper JSON)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/* ======================= Helpers JSON ======================= */
function send_json(array $payload, int $http_status = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($http_status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function json_error(string $msg, int $http_status = 200): void
{
    send_json(['status' => 'error', 'message' => $msg], $http_status);
}

/* ======================= Sesión / Permisos ======================= */
if (!isset($_SESSION['rol'])) {
    json_error('No tienes permiso', 403);
}
$rol = strtolower(trim($_SESSION['rol'] ?? ''));

// Matriz de permisos
$perm = [
    'listar' => true, // cualquiera con sesión
    'crear' => in_array($rol, ['admin', 'secretaria', 'secretarías', 'secretarias', 'secretaría'], true),
    'editar' => in_array($rol, ['admin', 'secretaria', 'secretarías', 'secretarias', 'secretaría'], true),
    'eliminar' => ($rol === 'admin'),  // solo admin puede eliminar definitivamente
];
function require_perm(string $action, array $perm): void
{
    if (empty($perm[$action])) {
        json_error('Acción no permitida para tu rol', 403);
    }
}

/* ======================= Conexión PDO ======================= */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    json_error("Error de conexión: " . $e->getMessage(), 500);
}

/* =================== Helpers Notificaciones =================== */
function get_secretaria_actor_id_from_session(): ?int
{
    $roles_secretaria = ['secretaria', 'secretarías', 'secretarias', 'secretaría'];
    $rol = strtolower($_SESSION['rol'] ?? '');
    if (!in_array($rol, $roles_secretaria, true)) {
        return null;
    }

    $fuentes = [];
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
        $fuentes[] = $_SESSION['usuario'];
    }
    $fuentes[] = $_SESSION;

    foreach ($fuentes as $arr) {
        foreach (['id_secretaria', 'secretaria_id', 'iduser', 'id'] as $k) {
            if (isset($arr[$k]) && (int) $arr[$k] > 0) {
                return (int) $arr[$k];
            }
        }
    }
    return null;
}

function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    $tipo = isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento', 'mensaje'], true) ? $cfg['tipo'] : 'movimiento';
    $titulo = (string) ($cfg['titulo'] ?? '');
    $detalle = (string) ($cfg['detalle'] ?? '');
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $actor_id = is_numeric($actor_id) ? (int) $actor_id : null;
    $recurso = (string) ($cfg['recurso'] ?? 'pago');
    $accion = (string) ($cfg['accion'] ?? '');
    $meta = $cfg['meta'] ?? null;

    if (is_array($meta)) {
        $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
    } elseif ($meta !== null) {
        $meta = (string) $meta;
    }

    $st = $pdo->prepare("
        INSERT INTO notificaciones (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido)
        VALUES (:tipo, :titulo, :detalle, :para_rol, :actor_id, :recurso, :accion, :meta, 0)
    ");
    $st->bindValue(':tipo', $tipo);
    $st->bindValue(':titulo', $titulo);
    $st->bindValue(':detalle', $detalle);
    $st->bindValue(':para_rol', $para_rol);
    $st->bindValue(':actor_id', $actor_id, is_null($actor_id) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $st->bindValue(':recurso', $recurso);
    $st->bindValue(':accion', $accion);
    $st->bindValue(':meta', $meta);

    try {
        $st->execute();
    } catch (Throwable $e) {
        // No romper flujo si falla la notificación
    }
}

/* ======================= Router de acciones ======================= */
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        /* ------------------------ CREAR ------------------------ */
        case 'crear': {
            require_perm('crear', $perm);

            $matricula = trim($_POST['matricula'] ?? '');
            $periodo = trim($_POST['periodo'] ?? '');
            $concepto = trim($_POST['concepto'] ?? '');
            $monto = isset($_POST['monto']) ? (float) $_POST['monto'] : 0.0;
            $adeudo = isset($_POST['adeudo']) ? (float) $_POST['adeudo'] : 0.0;
            $pago = isset($_POST['pago']) ? (float) $_POST['pago'] : 0.0;
            $condonacion = isset($_POST['condonacion']) ? (float) $_POST['condonacion'] : 0.0;

            if ($matricula === '' || $periodo === '' || $concepto === '' || $monto <= 0) {
                json_error('Faltan campos obligatorios (matrícula, periodo, concepto, monto)');
            }

            // Validar que el alumno exista
            $aStmt = $pdo->prepare("SELECT nombre, apellido_paterno, apellido_materno FROM alumnos WHERE matricula = ?");
            $aStmt->execute([$matricula]);
            $alumno = $aStmt->fetch(PDO::FETCH_ASSOC);
            if (!$alumno) {
                json_error('La matrícula no corresponde a ningún alumno registrado.');
            }
            $nombreAlumno = trim(($alumno['nombre'] ?? '') . ' ' . ($alumno['apellido_paterno'] ?? '') . ' ' . ($alumno['apellido_materno'] ?? ''));

            $ins = $pdo->prepare("
                INSERT INTO pagos (matricula, periodo, concepto, monto, adeudo, pago, condonacion)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$matricula, $periodo, $concepto, $monto, $adeudo, $pago, $condonacion]);
            $id_pago = (int) $pdo->lastInsertId();

            // Notificación a admin
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Alta de pago/cargo',
                'detalle' => "Se registró un movimiento para el alumno {$nombreAlumno} ({$matricula}).",
                'actor_id' => $actorId,
                'recurso' => 'pago',
                'accion' => 'alta',
                'meta' => [
                    'id_pago' => $id_pago,
                    'matricula' => $matricula,
                    'alumno' => $nombreAlumno,
                    'periodo' => $periodo,
                    'concepto' => $concepto,
                    'monto' => $monto,
                    'adeudo' => $adeudo,
                    'pago' => $pago,
                    'condonacion' => $condonacion,
                ],
            ]);

            send_json([
                'status' => 'success',
                'message' => 'Movimiento registrado correctamente',
                'id_pago' => $id_pago
            ]);
            break;
        }

        /* ------------------------ EDITAR ------------------------ */
        case 'editar': {
            require_perm('editar', $perm);

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $matricula = trim($_POST['matricula'] ?? '');
            $periodo = trim($_POST['periodo'] ?? '');
            $concepto = trim($_POST['concepto'] ?? '');
            $monto = isset($_POST['monto']) ? (float) $_POST['monto'] : 0.0;
            $adeudo = isset($_POST['adeudo']) ? (float) $_POST['adeudo'] : 0.0;
            $pago = isset($_POST['pago']) ? (float) $_POST['pago'] : 0.0;
            $condonacion = isset($_POST['condonacion']) ? (float) $_POST['condonacion'] : 0.0;

            if ($id <= 0 || $matricula === '' || $periodo === '' || $concepto === '' || $monto <= 0) {
                json_error('Faltan campos obligatorios');
            }

            // Verificar que el registro exista
            $cur = $pdo->prepare("SELECT * FROM pagos WHERE id = ?");
            $cur->execute([$id]);
            $prev = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$prev) {
                json_error('Registro de pago no encontrado');
            }

            // Validar alumno
            $aStmt = $pdo->prepare("SELECT nombre, apellido_paterno, apellido_materno FROM alumnos WHERE matricula = ?");
            $aStmt->execute([$matricula]);
            $alumno = $aStmt->fetch(PDO::FETCH_ASSOC);
            if (!$alumno) {
                json_error('La matrícula no corresponde a ningún alumno registrado.');
            }
            $nombreAlumno = trim(($alumno['nombre'] ?? '') . ' ' . ($alumno['apellido_paterno'] ?? '') . ' ' . ($alumno['apellido_materno'] ?? ''));

            $upd = $pdo->prepare("
                UPDATE pagos
                   SET matricula = ?,
                       periodo   = ?,
                       concepto  = ?,
                       monto     = ?,
                       adeudo    = ?,
                       pago      = ?,
                       condonacion = ?
                 WHERE id = ?
            ");
            $upd->execute([$matricula, $periodo, $concepto, $monto, $adeudo, $pago, $condonacion, $id]);

            // Notificación
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Edición de pago/cargo',
                'detalle' => "Se actualizó un movimiento para el alumno {$nombreAlumno} ({$matricula}).",
                'actor_id' => $actorId,
                'recurso' => 'pago',
                'accion' => 'edicion',
                'meta' => [
                    'id_pago' => $id,
                    'matricula' => $matricula,
                    'alumno' => $nombreAlumno,
                    'periodo' => $periodo,
                    'concepto' => $concepto,
                    'monto' => $monto,
                    'adeudo' => $adeudo,
                    'pago' => $pago,
                    'condonacion' => $condonacion,
                ],
            ]);

            send_json([
                'status' => 'success',
                'message' => 'Movimiento actualizado correctamente'
            ]);
            break;
        }

        /* ------------------------ ELIMINAR ------------------------ */
        case 'eliminar': {
            require_perm('eliminar', $perm);

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                json_error('ID no válido');
            }

            // Obtener info previa para notificación
            $info = $pdo->prepare("
                SELECT p.*, 
                       a.nombre, a.apellido_paterno, a.apellido_materno
                  FROM pagos p
             LEFT JOIN alumnos a ON a.matricula = p.matricula
                 WHERE p.id = ?
            ");
            $info->execute([$id]);
            $prev = $info->fetch(PDO::FETCH_ASSOC);
            if (!$prev) {
                json_error('Registro de pago no encontrado');
            }

            $del = $pdo->prepare("DELETE FROM pagos WHERE id = ?");
            $del->execute([$id]);

            $nombreAlumno = trim(($prev['nombre'] ?? '') . ' ' . ($prev['apellido_paterno'] ?? '') . ' ' . ($prev['apellido_materno'] ?? ''));
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Eliminación de pago/cargo',
                'detalle' => "Se eliminó un movimiento del alumno {$nombreAlumno} ({$prev['matricula']}).",
                'actor_id' => $actorId,
                'recurso' => 'pago',
                'accion' => 'eliminacion',
                'meta' => [
                    'id_pago' => $id,
                    'matricula' => $prev['matricula'],
                    'alumno' => $nombreAlumno,
                    'periodo' => $prev['periodo'],
                    'concepto' => $prev['concepto'],
                    'monto' => $prev['monto'],
                    'adeudo' => $prev['adeudo'],
                    'pago' => $prev['pago'],
                    'condonacion' => $prev['condonacion'],
                ],
            ]);

            send_json(['status' => 'success', 'message' => 'Registro eliminado']);
            break;
        }

        /* ------------------------ LISTAR ------------------------ */
        case 'listar': {
            require_perm('listar', $perm);

            $q = $pdo->query("
                SELECT 
                    p.*,
                    a.nombre,
                    a.apellido_paterno,
                    a.apellido_materno
                FROM pagos p
                LEFT JOIN alumnos a ON a.matricula = p.matricula
                ORDER BY p.fecha_registro DESC, p.id DESC
            ");
            $pagos = $q->fetchAll(PDO::FETCH_ASSOC);
            send_json(['status' => 'success', 'data' => $pagos]);
            break;
        }

        default:
            json_error('Acción no válida', 400);
    }
} catch (PDOException $e) {
    $errInfo = $e->errorInfo ?? [];
    $mysqlCode = $errInfo[1] ?? null;

    if ($mysqlCode === 1062) {
        json_error('1062 Duplicate entry: ' . ($errInfo[2] ?? $e->getMessage()));
    } elseif ($mysqlCode === 1452) {
        json_error('Error de referencia (FK): verifica que la matrícula exista en la tabla alumnos.');
    } else {
        json_error('Error de base de datos: ' . $e->getMessage(), 500);
    }
} catch (Throwable $e) {
    json_error('Error del servidor: ' . $e->getMessage(), 500);
}