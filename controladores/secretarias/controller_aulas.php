<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../conexion/conexion.php'; // Debe exponer $pdo (PDO)

// ====== SEGURIDAD ======
if (!isset($_SESSION['rol'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}
$rol = strtolower(trim($_SESSION['rol'] ?? ''));

// ====== PERMISOS ======
$perm = [
    'listar' => true,
    'agregar' => in_array($rol, ['admin', 'secretaria', 'secretarías', 'secretarias', 'secretaría'], true),
    'editar' => in_array($rol, ['admin', 'secretaria', 'secretarías', 'secretarias', 'secretaría'], true),
    'eliminar' => ($rol === 'admin'), // secretaría NO elimina
];

function require_perm($action, $perm)
{
    if (empty($perm[$action])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Acción no permitida para tu rol'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ====== HELPER: actor de sesión ======
function get_actor_id_from_session(): ?int
{
    $u = $_SESSION['usuario'] ?? [];
    foreach (['id_secretaria', 'id'] as $k) {
        if (isset($u[$k]) && (int) $u[$k] > 0)
            return (int) $u[$k];
    }
    return null;
}

// ====== HELPER: notificar al admin (PDO) ======
function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    // Tabla notificaciones: (id, tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido, created_at)
    $tipo = $cfg['tipo'] ?? 'movimiento';
    $titulo = $cfg['titulo'] ?? '';
    $detalle = $cfg['detalle'] ?? null;
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $recurso = $cfg['recurso'] ?? null; // p.ej. 'aula'
    $accion = $cfg['accion'] ?? null;  // 'alta','edicion','eliminacion'
    $meta = $cfg['meta'] ?? null;
    $leido = 0;

    $sql = "INSERT INTO notificaciones (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido)
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

// ====== ROUTER ======
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'listar':
        require_perm('listar', $perm);
        listarAulas($pdo);
        break;

    case 'agregar':
        require_perm('agregar', $perm);
        agregarAula($pdo);
        break;

    case 'editar':
        require_perm('editar', $perm);
        editarAula($pdo);
        break;

    case 'eliminar':
        require_perm('eliminar', $perm);
        eliminarAula($pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida'], JSON_UNESCAPED_UNICODE);
        break;
}

// ================= FUNCIONES =================

function listarAulas(PDO $pdo): void
{
    try {
        $sql = "SELECT id_aula, nombre, capacidad
                FROM aulas
                ORDER BY nombre ASC";
        $stmt = $pdo->query($sql);
        $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $aulas], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al listar: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function agregarAula(PDO $pdo): void
{
    $nombre = trim($_POST['nombre'] ?? '');
    $capacidad = isset($_POST['capacidad']) ? (int) $_POST['capacidad'] : 0;

    if ($nombre === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'El nombre del aula es obligatorio'], JSON_UNESCAPED_UNICODE);
        return;
    }
    if ($capacidad < 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'La capacidad no puede ser negativa'], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        // duplicado (case-insensitive)
        $chk = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE LOWER(nombre) = LOWER(?)");
        $chk->execute([$nombre]);
        if ((int) $chk->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Ya existe un aula con ese nombre'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $st = $pdo->prepare("INSERT INTO aulas (nombre, capacidad)
                             VALUES (?, ?)");
        $st->execute([$nombre, $capacidad]);

        $nuevoId = (int) $pdo->lastInsertId();

        // Notificación
        $actor_id = get_actor_id_from_session();
        notificar_admin_pdo($pdo, [
            'tipo' => 'movimiento',
            'titulo' => 'Alta de aula',
            'detalle' => 'Se agregó el aula ' . $nombre . '.',
            'actor_id' => $actor_id,
            'recurso' => 'aula',
            'accion' => 'alta',
            'meta' => json_encode(['id_aula' => $nuevoId, 'nombre' => $nombre, 'capacidad' => $capacidad], JSON_UNESCAPED_UNICODE),
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Aula agregada correctamente', 'id_aula' => $nuevoId], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al agregar: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function editarAula(PDO $pdo): void
{
    $id = (int) ($_POST['id_aula'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $capacidad = isset($_POST['capacidad']) ? (int) $_POST['capacidad'] : 0;

    if ($id <= 0 || $nombre === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
        return;
    }
    if ($capacidad < 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'La capacidad no puede ser negativa'], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        // existe?
        $ex = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE id_aula = ?");
        $ex->execute([$id]);
        if ((int) $ex->fetchColumn() === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'El aula no existe'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // duplicado por nombre con otro id
        $chk = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE LOWER(nombre) = LOWER(?) AND id_aula <> ?");
        $chk->execute([$nombre, $id]);
        if ((int) $chk->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Ya existe otra aula con ese nombre'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $st = $pdo->prepare("UPDATE aulas
                             SET nombre = ?, capacidad = ?
                             WHERE id_aula = ?");
        $st->execute([$nombre, $capacidad, $id]);

        // Notificación
        $actor_id = get_actor_id_from_session();
        notificar_admin_pdo($pdo, [
            'tipo' => 'movimiento',
            'titulo' => 'Edición de aula',
            'detalle' => 'Se editó el aula ' . $nombre . '.',
            'actor_id' => $actor_id,
            'recurso' => 'aula',
            'accion' => 'edicion',
            'meta' => json_encode(['id_aula' => $id, 'nombre' => $nombre, 'capacidad' => $capacidad], JSON_UNESCAPED_UNICODE),
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Aula actualizada correctamente'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function eliminarAula(PDO $pdo): void
{
    $id = (int) ($_POST['id_aula'] ?? 0);

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        // obtener nombre para la notificación
        $rs = $pdo->prepare("SELECT nombre, capacidad FROM aulas WHERE id_aula = ? LIMIT 1");
        $rs->execute([$id]);
        $row = $rs->fetch(PDO::FETCH_ASSOC);
        $nombre = $row['nombre'] ?? '';
        $capacidad = $row['capacidad'] ?? null;

        // borrar
        $st = $pdo->prepare("DELETE FROM aulas WHERE id_aula = ?");
        $st->execute([$id]);

        if ($st->rowCount() < 1) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'El aula no existe o ya fue eliminada'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Notificación
        $actor_id = get_actor_id_from_session();
        notificar_admin_pdo($pdo, [
            'tipo' => 'movimiento',
            'titulo' => 'Eliminación de aula',
            'detalle' => 'Se eliminó el aula ' . $nombre . '.',
            'actor_id' => $actor_id,
            'recurso' => 'aula',
            'accion' => 'eliminacion',
            'meta' => json_encode(['id_aula' => $id, 'nombre' => $nombre, 'capacidad' => $capacidad], JSON_UNESCAPED_UNICODE),
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Aula eliminada correctamente'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        $msg = $e->getCode() === '23000'
            ? 'No se puede eliminar: hay registros relacionados.'
            : 'Error al eliminar: ' . $e->getMessage();
        echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
    }
}