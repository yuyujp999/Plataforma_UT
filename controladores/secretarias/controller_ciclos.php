<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

/* ===== No imprimir HTML de errores en respuestas JSON ===== */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/* ===== Helpers de respuesta ===== */
function respond(string $status, ?string $message = null, array $extra = [], int $http = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($http);
    $payload = array_merge(['status' => $status], $message ? ['message' => $message] : [], $extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function isValidDate(?string $date): bool
{
    if (!$date)
        return false;
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}
function bool01($v): int
{
    if (is_string($v))
        $v = mb_strtolower($v, 'UTF-8');
    return in_array($v, [1, '1', true, 'true', 'on', 'sí', 'si'], true) ? 1 : 0;
}

/* ===== Sesión y permisos ===== */
if (!isset($_SESSION['rol'])) {
    respond('error', 'No autorizado', [], 403);
}
$rol = mb_strtolower((string) ($_SESSION['rol'] ?? ''), 'UTF-8');
$esAdmin = ($rol === 'admin');
$esSecretaria = in_array($rol, ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

/* Matriz de permisos (Secretaría: crear/editar/toggle; eliminar solo Admin) */
$PERM = [
    'crear' => ($esAdmin || $esSecretaria),
    'editar' => ($esAdmin || $esSecretaria),
    'toggle' => ($esAdmin || $esSecretaria),
    'eliminar' => $esAdmin,
];

/* Guardas por permiso */
function require_perm(bool $can, string $msg = 'No autorizado'): void
{
    if (!$can)
        respond('error', $msg, [], 403);
}

/* ====== Helper: obtener actor_id (id_secretaria) de sesión ====== */
function get_secretaria_actor_id_from_session(): ?int
{
    $roles_secretaria = ['secretaria', 'secretarias', 'secretaría', 'secretarías'];
    $rol = strtolower($_SESSION['rol'] ?? '');
    if (!in_array($rol, $roles_secretaria, true))
        return null;

    $candidatas = ['id_secretaria', 'secretaria_id', 'iduser', 'id'];
    $fuentes = [];
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']))
        $fuentes[] = $_SESSION['usuario'];
    $fuentes[] = $_SESSION;

    foreach ($fuentes as $arr) {
        foreach ($candidatas as $k) {
            if (isset($arr[$k]) && (int) $arr[$k] > 0)
                return (int) $arr[$k];
        }
    }
    return null;
}

/* ====== Helper: notificar al admin (PDO) ====== */
function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    $tipo = isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento', 'mensaje'], true) ? $cfg['tipo'] : 'movimiento';
    $titulo = (string) ($cfg['titulo'] ?? '');
    $detalle = (string) ($cfg['detalle'] ?? '');
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $actor_id = is_numeric($actor_id) ? (int) $actor_id : null;
    $recurso = (string) ($cfg['recurso'] ?? 'ciclo');   // por defecto 'ciclo'
    $accion = (string) ($cfg['accion'] ?? '');        // alta|edicion|eliminacion|toggle|activar|desactivar
    $meta = $cfg['meta'] ?? null;
    if (is_array($meta))
        $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
    elseif ($meta !== null)
        $meta = (string) $meta;
    $leido = 0;

    $sql = "INSERT INTO notificaciones (tipo,titulo,detalle,para_rol,actor_id,recurso,accion,meta,leido)
            VALUES (:tipo,:titulo,:detalle,:para_rol,:actor_id,:recurso,:accion,:meta,:leido)";
    $st = $pdo->prepare($sql);
    $st->bindValue(':tipo', $tipo);
    $st->bindValue(':titulo', $titulo);
    $st->bindValue(':detalle', $detalle);
    $st->bindValue(':para_rol', $para_rol);
    $st->bindValue(':actor_id', $actor_id, is_null($actor_id) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $st->bindValue(':recurso', $recurso);
    $st->bindValue(':accion', $accion);
    $st->bindValue(':meta', $meta);
    $st->bindValue(':leido', $leido, PDO::PARAM_INT);
    $st->execute();
}

/* ===== Conexión PDO ===== */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    respond('error', 'Error de conexión: ' . $e->getMessage(), [], 500);
}

/* ===== Router ===== */
$accion = $_POST['accion'] ?? '';

switch ($accion) {

    /* ============================ LISTAR ============================ */
    case 'listar': {
        try {
            $q = $pdo->query("
                SELECT id_ciclo, clave, fecha_inicio, fecha_fin, activo
                  FROM ciclos_escolares
              ORDER BY id_ciclo DESC
            ");
            $rows = $q->fetchAll(PDO::FETCH_ASSOC);
            respond('success', null, ['data' => $rows]);
        } catch (PDOException $e) {
            respond('error', 'No se pudo listar: ' . $e->getMessage(), [], 500);
        }
        break;
    }

    /* ============================ AGREGAR ============================ */
    case 'agregar': {
        require_perm($PERM['crear'], 'No autorizado para agregar');

        $clave = trim($_POST['clave'] ?? '');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin = trim($_POST['fecha_fin'] ?? '');
        $activo = bool01($_POST['activo'] ?? 0);

        if ($clave === '' || !$fecha_inicio || !$fecha_fin) {
            respond('error', 'Faltan datos obligatorios');
        }
        if (!isValidDate($fecha_inicio) || !isValidDate($fecha_fin)) {
            respond('error', 'Formato de fecha inválido (use YYYY-MM-DD)');
        }
        if ($fecha_fin < $fecha_inicio) {
            respond('error', 'La fecha fin no puede ser menor a la fecha inicio');
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO ciclos_escolares (clave, fecha_inicio, fecha_fin, activo)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$clave, $fecha_inicio, $fecha_fin, $activo]);
            $id = (int) $pdo->lastInsertId();

            // Notificación
            $actor_id = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Alta de ciclo escolar',
                'detalle' => 'Se creó el ciclo ' . $clave . ' (' . $fecha_inicio . ' a ' . $fecha_fin . ').',
                'actor_id' => $actor_id,
                'recurso' => 'ciclo',
                'accion' => 'alta',
                'meta' => ['id_ciclo' => $id, 'clave' => $clave, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'activo' => $activo],
            ]);

            respond('success', null, [
                'data' => [
                    'id_ciclo' => $id,
                    'clave' => $clave,
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin,
                    'activo' => $activo,
                ]
            ]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                respond('error', 'La clave ya existe. Debe ser única.');
            }
            respond('error', 'No se pudo agregar el ciclo: ' . $e->getMessage(), [], 500);
        }
        break;
    }

    /* ============================ EDITAR ============================ */
    case 'editar': {
        require_perm($PERM['editar'], 'No autorizado para editar');

        $id_ciclo = $_POST['id_ciclo'] ?? null;
        $clave = trim($_POST['clave'] ?? '');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin = trim($_POST['fecha_fin'] ?? '');
        $activo = bool01($_POST['activo'] ?? 0);

        if (!$id_ciclo || $clave === '' || !$fecha_inicio || !$fecha_fin) {
            respond('error', 'Faltan datos obligatorios');
        }
        if (!isValidDate($fecha_inicio) || !isValidDate($fecha_fin)) {
            respond('error', 'Formato de fecha inválido (use YYYY-MM-DD)');
        }
        if ($fecha_fin < $fecha_inicio) {
            respond('error', 'La fecha fin no puede ser menor a la fecha inicio');
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE ciclos_escolares
                   SET clave = ?, fecha_inicio = ?, fecha_fin = ?, activo = ?
                 WHERE id_ciclo = ?
            ");
            $ok = $stmt->execute([$clave, $fecha_inicio, $fecha_fin, $activo, $id_ciclo]);

            // Notificación (siempre que se llame a editar)
            $actor_id = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Edición de ciclo escolar',
                'detalle' => 'Se editó el ciclo ' . $clave . ' (' . $fecha_inicio . ' a ' . $fecha_fin . ').',
                'actor_id' => $actor_id,
                'recurso' => 'ciclo',
                'accion' => 'edicion',
                'meta' => ['id_ciclo' => (int) $id_ciclo, 'clave' => $clave, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'activo' => $activo],
            ]);

            if ($ok && $stmt->rowCount() > 0) {
                respond('success', null, [
                    'data' => [
                        'id_ciclo' => (int) $id_ciclo,
                        'clave' => $clave,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                        'activo' => $activo,
                    ]
                ]);
            } else {
                respond('success', 'Sin cambios', [
                    'data' => [
                        'id_ciclo' => (int) $id_ciclo,
                        'clave' => $clave,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                        'activo' => $activo,
                    ]
                ]);
            }
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                respond('error', 'La clave ya existe. Debe ser única.');
            }
            respond('error', 'No se pudo actualizar el ciclo: ' . $e->getMessage(), [], 500);
        }
        break;
    }

    /* ============================ ELIMINAR (solo Admin) ============================ */
    case 'eliminar': {
        require_perm($PERM['eliminar'], 'No autorizado para eliminar');

        $id_ciclo = $_POST['id_ciclo'] ?? null;
        if (!$id_ciclo)
            respond('error', 'ID no válido');

        try {
            // Obtener clave para detalle
            $prev = null;
            $rs = $pdo->prepare("SELECT clave FROM ciclos_escolares WHERE id_ciclo=?");
            $rs->execute([$id_ciclo]);
            $prev = $rs->fetch(PDO::FETCH_ASSOC);
            $clavePrev = $prev['clave'] ?? '';

            $stmt = $pdo->prepare("DELETE FROM ciclos_escolares WHERE id_ciclo = ?");
            $ok = $stmt->execute([$id_ciclo]);

            if ($ok && $stmt->rowCount() > 0) {
                // Notificación
                $actor_id = get_secretaria_actor_id_from_session();
                notificar_admin_pdo($pdo, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Eliminación de ciclo escolar',
                    'detalle' => 'Se eliminó el ciclo ' . $clavePrev . '.',
                    'actor_id' => $actor_id,
                    'recurso' => 'ciclo',
                    'accion' => 'eliminacion',
                    'meta' => ['id_ciclo' => (int) $id_ciclo, 'clave' => $clavePrev],
                ]);

                respond('success');
            } else {
                respond('error', 'No se encontró el ciclo o no se pudo eliminar');
            }
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                respond('error', 'No se puede eliminar: el ciclo está referenciado por otros registros.');
            }
            respond('error', 'Error al eliminar: ' . $e->getMessage(), [], 500);
        }
        break;
    }

    /* ============================ TOGGLE ACTIVO ============================ */
    case 'toggle_activo': {
        require_perm($PERM['toggle'], 'No autorizado para cambiar estado');

        $id_ciclo = $_POST['id_ciclo'] ?? null;
        $activo = bool01($_POST['activo'] ?? 0);
        if (!$id_ciclo)
            respond('error', 'ID no válido');

        try {
            $stmt = $pdo->prepare("UPDATE ciclos_escolares SET activo = ? WHERE id_ciclo = ?");
            $stmt->execute([$activo, $id_ciclo]);

            // Notificación
            $actor_id = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => $activo ? 'Activación de ciclo escolar' : 'Desactivación de ciclo escolar',
                'detalle' => ($activo ? 'Se activó' : 'Se desactivó') . " el ciclo (ID {$id_ciclo}).",
                'actor_id' => $actor_id,
                'recurso' => 'ciclo',
                'accion' => $activo ? 'activar' : 'desactivar',
                'meta' => ['id_ciclo' => (int) $id_ciclo, 'activo' => $activo],
            ]);

            respond('success', null, ['data' => ['id_ciclo' => (int) $id_ciclo, 'activo' => $activo]]);
        } catch (PDOException $e) {
            respond('error', 'No se pudo actualizar el estado: ' . $e->getMessage(), [], 500);
        }
        break;
    }

    /* ============================ DEFAULT ============================ */
    default:
        respond('error', 'Acción no reconocida', [], 400);
}