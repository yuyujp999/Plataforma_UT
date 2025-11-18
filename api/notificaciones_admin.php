<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

function input($k, $d = null)
{
    return $_POST[$k] ?? $_GET[$k] ?? $d;
}
function tipoValido($t, $fb = 'movimiento')
{
    $t = strtolower((string) $t);
    return in_array($t, ['movimiento', 'mensaje', 'todos'], true) ? $t : $fb;
}

$accion = input('accion', 'counts');
$tipo = input('tipo', null);

/* ===== COUNTS ===== */
if ($accion === 'counts') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE para_rol='admin' AND tipo='movimiento' AND leido=0");
    $stmt->execute();
    $bell = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE para_rol='admin'");
    $stmt->execute();
    $mail = (int) $stmt->fetchColumn();

    echo json_encode(['status' => 'ok', 'bell' => $bell, 'mail' => $mail]);
    exit;
}

/* ===== ULTIMAS_NO_LEIDAS ===== */
if ($accion === 'ultimas_no_leidas') {
    $limit = max(1, min(100, (int) input('limit', 10)));
    $sql = "
        SELECT n.id, n.tipo, n.titulo, n.detalle, n.accion, n.recurso,
               n.created_at, n.leido, n.actor_id
        FROM notificaciones n
        WHERE n.para_rol='admin' AND n.tipo='movimiento' AND n.leido=0
        ORDER BY n.id DESC
        LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    echo json_encode(['status' => 'ok', 'items' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

/* ===== HISTORIAL ===== */
if ($accion === 'historial') {
    $limit = max(1, min(200, (int) input('limit', 50)));
    $sql = "
        SELECT 
            n.id, n.tipo, n.titulo, n.detalle, n.accion, n.recurso,
            n.created_at, n.leido, n.actor_id,
            TRIM(CONCAT(COALESCE(s.nombre,''),' ',COALESCE(s.apellido_paterno,''))) AS secretaria_nombre
        FROM notificaciones n
        LEFT JOIN secretarias s ON s.id_secretaria = n.actor_id
        WHERE n.para_rol='admin'
        ORDER BY n.id DESC
        LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    echo json_encode(['status' => 'ok', 'items' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

/* ===== MARCAR_LEIDAS ===== */
if ($accion === 'marcar_leidas') {
    $pdo->prepare("
        UPDATE notificaciones
        SET leido=1
        WHERE para_rol='admin' AND tipo='movimiento' AND leido=0
    ")->execute();
    echo json_encode(['status' => 'ok']);
    exit;
}

/* ===== ELIMINAR (uno) ===== */
if ($accion === 'eliminar') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
        exit;
    }
    $id = (int) input('id', 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }

    $st = $pdo->prepare("DELETE FROM notificaciones WHERE id=:id AND para_rol='admin'");
    $ok = $st->execute([':id' => $id]);
    echo json_encode(['status' => $ok ? 'ok' : 'error']);
    exit;
}

/* ===== ELIMINAR_TODAS ===== */
if ($accion === 'eliminar_todas') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
        exit;
    }
    $tipo = tipoValido(input('tipo', 'todos'), 'todos');

    if ($tipo === 'todos') {
        $st = $pdo->prepare("DELETE FROM notificaciones WHERE para_rol='admin'");
        $st->execute();
    } else {
        $st = $pdo->prepare("DELETE FROM notificaciones WHERE para_rol='admin' AND tipo=:t");
        $st->execute([':t' => $tipo]);
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

/* ===== NUEVO: RESPUESTAS_MENSAJE =====
   Devuelve respuestas que mandaron secretarías respecto a un mensaje
   Buscamos en notificaciones: para_rol='admin', accion='respuesta', recurso='mensaje'
   y acotamos por título "Respuesta a: {titulo}".
*/
if ($accion === 'respuestas_mensaje') {
    $titulo = trim((string) input('titulo', ''));
    $limit = max(1, min(200, (int) input('limit', 100)));

    $condTitulo = "";
    $params = [];
    if ($titulo !== '') {
        $condTitulo = " AND n.titulo LIKE :t ";
        // así: "Respuesta a: {titulo}%"
        $params[':t'] = 'Respuesta a: ' . $titulo . '%';
    }

    $sql = "
        SELECT n.id, n.detalle, n.created_at,
               TRIM(CONCAT(COALESCE(s.nombre,''),' ',COALESCE(s.apellido_paterno,''))) AS secretaria_nombre
        FROM notificaciones n
        LEFT JOIN secretarias s ON s.id_secretaria = n.actor_id
        WHERE n.para_rol='admin' AND n.accion='respuesta' AND n.recurso='mensaje'
        $condTitulo
        ORDER BY n.id DESC
        LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $st->bindValue($k, $v, PDO::PARAM_STR);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    echo json_encode(['status' => 'ok', 'items' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);