<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Evitar que warnings rompan el JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/* ================= Helpers JSON ================= */
function send_json(array $payload, int $http = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function ok($msg = null, array $extra = []): void
{
    send_json(array_merge(['status' => 'success'], $msg ? ['message' => $msg] : [], $extra));
}
function fail($msg, int $http = 200, array $extra = []): void
{
    send_json(array_merge(['status' => 'error', 'message' => $msg], $extra), $http);
}

/* ================= Sesión / Permisos ================= */
if (!isset($_SESSION['rol'])) {
    fail('No autorizado', 403);
}
$rol = strtolower(trim($_SESSION['rol'] ?? ''));
$perm = [
    'agregar' => in_array($rol, ['admin', 'secretaria', 'secretarías', 'secretarias', 'secretaría'], true),
    'editar' => in_array($rol, ['admin', 'secretaria', 'secretarías', 'secretarias', 'secretaría'], true),
    'eliminar' => ($rol === 'admin'),
];
function require_perm(string $action, array $perm): void
{
    if (empty($perm[$action]))
        fail('Acción no permitida para tu rol', 403);
}

/* ================= Conexión PDO ================= */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    fail('Error de conexión: ' . $e->getMessage(), 500);
}

/* ================= Helpers Notificaciones ================= */
/**
 * Obtiene el id de secretaría desde la sesión para actor_id.
 * Busca en $_SESSION['usuario'] y en la raíz de la sesión varias llaves comunes.
 */
function get_secretaria_actor_id_from_session(): ?int
{
    $roles_secretaria = ['secretaria', 'secretarías', 'secretarias', 'secretaría'];
    $rol = strtolower($_SESSION['rol'] ?? '');
    if (!in_array($rol, $roles_secretaria, true))
        return null;

    $fuentes = [];
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']))
        $fuentes[] = $_SESSION['usuario'];
    $fuentes[] = $_SESSION;

    foreach ($fuentes as $arr) {
        foreach (['id_secretaria', 'secretaria_id', 'iduser', 'id'] as $k) {
            if (isset($arr[$k]) && (int) $arr[$k] > 0)
                return (int) $arr[$k];
        }
    }
    return null;
}

/**
 * Inserta una notificación dirigida al admin.
 * Tabla esperada: notificaciones(tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido, created_at)
 */
function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    $tipo = isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento', 'mensaje'], true) ? $cfg['tipo'] : 'movimiento';
    $titulo = (string) ($cfg['titulo'] ?? '');
    $detalle = (string) ($cfg['detalle'] ?? '');
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $actor_id = is_numeric($actor_id) ? (int) $actor_id : null;
    $recurso = (string) ($cfg['recurso'] ?? 'materia');
    $accion = (string) ($cfg['accion'] ?? '');
    $meta = $cfg['meta'] ?? null;
    if (is_array($meta))
        $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
    elseif ($meta !== null)
        $meta = (string) $meta;

    $sql = "INSERT INTO notificaciones (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido)
            VALUES (:tipo, :titulo, :detalle, :para_rol, :actor_id, :recurso, :accion, :meta, 0)";
    try {
        $st = $pdo->prepare($sql);
        $st->bindValue(':tipo', $tipo);
        $st->bindValue(':titulo', $titulo);
        $st->bindValue(':detalle', $detalle);
        $st->bindValue(':para_rol', $para_rol);
        $st->bindValue(':actor_id', $actor_id, is_null($actor_id) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $st->bindValue(':recurso', $recurso);
        $st->bindValue(':accion', $accion);
        $st->bindValue(':meta', $meta);
        $st->execute();
    } catch (Throwable $e) {
        // No romper el flujo principal por un fallo en notificaciones
    }
}

/* ================= Utilidades ================= */
function norm_nombre(?string $s): string
{
    return trim((string) $s);
}
function existe_materia(PDO $pdo, string $nombre, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $q = $pdo->prepare("SELECT COUNT(*) FROM materias WHERE LOWER(nombre_materia)=LOWER(?) AND id_materia<>?");
        $q->execute([$nombre, $excludeId]);
    } else {
        $q = $pdo->prepare("SELECT COUNT(*) FROM materias WHERE LOWER(nombre_materia)=LOWER(?)");
        $q->execute([$nombre]);
    }
    return ((int) $q->fetchColumn()) > 0;
}

/* ================= Router ================= */
$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        /* ---------- AGREGAR ---------- */
        case 'agregar': {
            require_perm('agregar', $perm);

            $nombre = norm_nombre($_POST['nombre_materia'] ?? '');
            if ($nombre === '')
                fail('Faltan datos obligatorios');

            // Duplicado (case-insensitive)
            if (existe_materia($pdo, $nombre)) {
                fail('Ya existe una materia con ese nombre.');
            }

            $st = $pdo->prepare("INSERT INTO materias (nombre_materia) VALUES (?)");
            $st->execute([$nombre]);
            $idNueva = (int) $pdo->lastInsertId();

            // Notificación
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Alta de materia',
                'detalle' => 'Se creó la materia "' . $nombre . '".',
                'actor_id' => $actorId,
                'recurso' => 'materia',
                'accion' => 'alta',
                'meta' => ['id_materia' => $idNueva, 'nombre_materia' => $nombre],
            ]);

            ok('Materia agregada correctamente.', ['id_materia' => $idNueva]);
            break;
        }

        /* ---------- EDITAR ---------- */
        case 'editar': {
            require_perm('editar', $perm);

            $id = isset($_POST['id_materia']) ? (int) $_POST['id_materia'] : 0;
            $nombre = norm_nombre($_POST['nombre_materia'] ?? '');
            if ($id <= 0 || $nombre === '')
                fail('Faltan datos obligatorios');

            // Verificar existencia
            $cur = $pdo->prepare("SELECT nombre_materia FROM materias WHERE id_materia=?");
            $cur->execute([$id]);
            $prev = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$prev)
                fail('La materia no existe.');

            // Duplicado (case-insensitive) excluyendo el mismo id
            if (existe_materia($pdo, $nombre, $id)) {
                fail('Ya existe otra materia con ese nombre.');
            }

            $st = $pdo->prepare("UPDATE materias SET nombre_materia = ? WHERE id_materia = ?");
            $st->execute([$nombre, $id]);

            // Notificación
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Edición de materia',
                'detalle' => 'Se actualizó la materia "' . ($prev['nombre_materia'] ?? '') . '" a "' . $nombre . '".',
                'actor_id' => $actorId,
                'recurso' => 'materia',
                'accion' => 'edicion',
                'meta' => [
                    'id_materia' => $id,
                    'nombre_anterior' => $prev['nombre_materia'] ?? null,
                    'nombre_nuevo' => $nombre
                ],
            ]);

            ok('Materia actualizada correctamente.');
            break;
        }

        /* ---------- ELIMINAR ---------- */
        case 'eliminar': {
            require_perm('eliminar', $perm);

            $id = isset($_POST['id_materia']) ? (int) $_POST['id_materia'] : 0;
            if ($id <= 0)
                fail('ID no válido');

            // Traer nombre para detalle/meta
            $cur = $pdo->prepare("SELECT nombre_materia FROM materias WHERE id_materia=?");
            $cur->execute([$id]);
            $prev = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$prev)
                fail('La materia no existe.');

            try {
                $st = $pdo->prepare("DELETE FROM materias WHERE id_materia = ?");
                $st->execute([$id]);
            } catch (PDOException $e) {
                // Código 23000 suele ser FK constraint
                if (($e->errorInfo[0] ?? '') === '23000') {
                    fail('No se puede eliminar: hay registros relacionados.', 200);
                }
                fail('No se pudo eliminar la materia: ' . $e->getMessage(), 500);
            }

            // Notificación
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Eliminación de materia',
                'detalle' => 'Se eliminó la materia "' . ($prev['nombre_materia'] ?? ('ID ' . $id)) . '".',
                'actor_id' => $actorId,
                'recurso' => 'materia',
                'accion' => 'eliminacion',
                'meta' => ['id_materia' => $id, 'nombre_materia' => $prev['nombre_materia'] ?? null],
            ]);

            ok('Materia eliminada correctamente.');
            break;
        }

        default:
            fail('Acción no reconocida', 400);
    }
} catch (Throwable $e) {
    fail('Error del servidor: ' . $e->getMessage(), 500);
}