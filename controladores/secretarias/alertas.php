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

/* Solo secretarías */
if (!isset($_SESSION['rol']) || !in_array(strtolower($_SESSION['rol']), ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}
$usuarioSesion = $_SESSION['usuario'] ?? [];

function getSecretariaIdFromSession(array $u): ?int
{
    foreach (['id_secretaria', 'secretaria_id', 'id', 'iduser'] as $k)
        if (isset($u[$k]) && is_numeric($u[$k]))
            return (int) $u[$k];
    return null;
}
$idSecretaria = getSecretariaIdFromSession($usuarioSesion);
if (!$idSecretaria) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Sesión incompleta']);
    exit;
}

/* Conexión */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión']);
    exit;
}

/* Utils */
function input($k, $d = null)
{
    return $_POST[$k] ?? $_GET[$k] ?? $d;
}
function firstExistingCol(PDO $pdo, string $table, array $candidates): ?string
{
    $st = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t AND COLUMN_NAME=:c");
    foreach ($candidates as $c) {
        $st->execute([':t' => $table, ':c' => $c]);
        if ($st->fetchColumn())
            return $c;
    }
    return null;
}

$accion = input('accion', '');

/* 1) marcar_notificacion_leida */
if ($accion === 'marcar_notificacion_leida') {
    $id = trim((string) input('id_noti', ''));
    $tit = trim((string) input('titulo', ''));
    $crea = trim((string) input('creada_en', ''));

    $tbl = 'notificaciones';
    $colId = firstExistingCol($pdo, $tbl, ['id_notificacion', 'id', 'notif_id']);
    $colLeida = firstExistingCol($pdo, $tbl, ['leida', 'leido', 'is_read']);
    $colRol = firstExistingCol($pdo, $tbl, ['rol_destino', 'rol', 'destino', 'para_rol']);
    $colUser = firstExistingCol($pdo, $tbl, ['id_usuario', 'usuario_id', 'id_user']);
    $colTitulo = firstExistingCol($pdo, $tbl, ['titulo', 'title']);
    $colFecha = firstExistingCol($pdo, $tbl, ['creada_en', 'creado_en', 'created_at', 'fecha', 'fecha_creacion']);

    if (!$colLeida || !$colRol || !$colUser) {
        echo json_encode(['status' => 'error', 'message' => 'Tabla notificaciones incompatible']);
        exit;
    }

    if ($colId && $id !== '') {
        $st = $pdo->prepare("UPDATE {$tbl} SET {$colLeida}=1 WHERE {$colId}=:id AND {$colRol}='secretaria' AND {$colUser}=:u");
        $st->execute([':id' => $id, ':u' => $idSecretaria]);
    } else {
        if (!$colTitulo || !$colFecha) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede localizar el registro']);
            exit;
        }
        $st = $pdo->prepare("UPDATE {$tbl} SET {$colLeida}=1 WHERE {$colRol}='secretaria' AND {$colUser}=:u AND {$colTitulo}=:t " . ($crea !== '' ? "AND {$colFecha}=:f" : '') . " LIMIT 1");
        $p = [':u' => $idSecretaria, ':t' => $tit];
        if ($crea !== '')
            $p[':f'] = $crea;
        $st->execute($p);
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$colRol}='secretaria' AND {$colUser}=:u AND {$colLeida}=0");
    $st->execute([':u' => $idSecretaria]);
    $unread = (int) $st->fetchColumn();
    echo json_encode(['status' => 'success', 'data' => ['unread_notis' => $unread]]);
    exit;
}

/* 2) marcar_mensaje_leido */
if ($accion === 'marcar_mensaje_leido') {
    $id_ms = (int) input('id_ms', 0);
    if ($id_ms <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }
    $pdo->prepare("UPDATE mensajes_secretarias SET leido_en=NOW() WHERE id_ms=:id AND id_secretaria=:u AND leido_en IS NULL")
        ->execute([':id' => $id_ms, ':u' => $idSecretaria]);
    $st = $pdo->prepare("SELECT COUNT(*) FROM mensajes_secretarias WHERE id_secretaria=:u AND leido_en IS NULL");
    $st->execute([':u' => $idSecretaria]);
    $unread = (int) $st->fetchColumn();
    echo json_encode(['status' => 'success', 'data' => ['unread_msgs' => $unread]]);
    exit;
}

/* 3) responder_notificacion */
if ($accion === 'responder_notificacion') {
    $tituloOrig = trim((string) input('titulo', ''));
    $creada_en = trim((string) input('creada_en', ''));
    $respuesta = trim((string) input('respuesta', ''));
    if ($respuesta === '') {
        echo json_encode(['status' => 'error', 'message' => 'La respuesta no puede estar vacía']);
        exit;
    }

    $pdo->prepare("INSERT INTO notificaciones (para_rol,tipo,titulo,detalle,accion,recurso,actor_id,created_at,leido)
                   VALUES ('admin','mensaje',:t,:d,'respuesta','notificacion',:a,NOW(),0)")
        ->execute([
            ':t' => ($tituloOrig !== '' ? 'Respuesta a: ' . $tituloOrig : 'Respuesta de Secretaría'),
            ':d' => $respuesta,
            ':a' => $idSecretaria
        ]);

    // marcar original como leída si es localizable
    $tbl = 'notificaciones';
    $colLeida = firstExistingCol($pdo, $tbl, ['leida', 'leido', 'is_read']);
    $colRol = firstExistingCol($pdo, $tbl, ['rol_destino', 'rol', 'destino', 'para_rol']);
    $colUser = firstExistingCol($pdo, $tbl, ['id_usuario', 'usuario_id', 'id_user']);
    $colTitulo = firstExistingCol($pdo, $tbl, ['titulo', 'title']);
    $colFecha = firstExistingCol($pdo, $tbl, ['creada_en', 'creado_en', 'created_at', 'fecha', 'fecha_creacion']);
    if ($colLeida && $colRol && $colUser && $colTitulo && $colFecha && $tituloOrig !== '') {
        $st = $pdo->prepare("UPDATE {$tbl} SET {$colLeida}=1 WHERE {$colRol}='secretaria' AND {$colUser}=:u AND {$colTitulo}=:t " . ($creada_en !== '' ? "AND {$colFecha}=:f" : '') . " LIMIT 1");
        $p = [':u' => $idSecretaria, ':t' => $tituloOrig];
        if ($creada_en !== '')
            $p[':f'] = $creada_en;
        $st->execute($p);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

/* 4) responder_mensaje (desde el sobre) */
if ($accion === 'responder_mensaje') {
    $id_ms = (int) input('id_ms', 0);
    $tituloOrig = trim((string) input('titulo', ''));
    $respuesta = trim((string) input('respuesta', ''));
    if ($respuesta === '') {
        echo json_encode(['status' => 'error', 'message' => 'La respuesta no puede estar vacía']);
        exit;
    }

    // Enviar como notificación al admin (se verá en su historial)
    $pdo->prepare("INSERT INTO notificaciones (para_rol,tipo,titulo,detalle,accion,recurso,actor_id,created_at,leido)
                   VALUES ('admin','mensaje',:t,:d,'respuesta','mensaje',:a,NOW(),0)")
        ->execute([
            ':t' => ($tituloOrig !== '' ? 'Respuesta a: ' . $tituloOrig : 'Respuesta de Secretaría'),
            ':d' => $respuesta,
            ':a' => $idSecretaria
        ]);

    // marcar el mensaje como leído
    if ($id_ms > 0) {
        $pdo->prepare("UPDATE mensajes_secretarias SET leido_en=COALESCE(leido_en,NOW()) WHERE id_ms=:id AND id_secretaria=:u")
            ->execute([':id' => $id_ms, ':u' => $idSecretaria]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);