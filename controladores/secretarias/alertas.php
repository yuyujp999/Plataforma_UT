<?php
// /Plataforma_UT/controladores/secretaria/alertas.php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function ok($data = [], $extra = [])
{
    echo json_encode(array_merge(['status' => 'success', 'data' => $data], $extra));
    exit;
}
function fail($msg, $code = 400, $extra = [])
{
    http_response_code(200);
    echo json_encode(array_merge(['status' => 'error', 'message' => $msg], $extra));
    exit;
}

if (!isset($_SESSION['rol']) || !isset($_SESSION['usuario']))
    fail('No autorizado.', 401);

function getSecretariaIdFromSession(array $u): ?int
{
    foreach (['id_secretaria', 'secretaria_id', 'id', 'iduser'] as $k)
        if (isset($u[$k]) && is_numeric($u[$k]))
            return (int) $u[$k];
    return null;
}
$idSecretaria = getSecretariaIdFromSession($_SESSION['usuario'] ?? []);
if (!$idSecretaria)
    fail('No se pudo determinar la secretaría.', 400);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    fail('DB: ' . $e->getMessage(), 500);
}

$accion = $_POST['accion'] ?? '';

/* helpers de conteo para badges */
function unread_msgs(PDO $pdo, int $idSecretaria): int
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM mensajes_secretarias WHERE id_secretaria=:id AND leido_en IS NULL");
    $st->execute([':id' => $idSecretaria]);
    return (int) $st->fetchColumn();
}
function firstExistingCol(PDO $pdo, string $table, array $cands): ?string
{
    $st = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t");
    $st->execute([':t' => $table]);
    $cols = $st->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cands as $c) {
        if (in_array($c, $cols, true))
            return $c;
    }
    return null;
}
function unread_notis(PDO $pdo, int $idSecretaria): int
{
    $tbl = 'notificaciones';
    $colLeida = firstExistingCol($pdo, $tbl, ['leida', 'leido', 'is_read']);
    $colRol = firstExistingCol($pdo, $tbl, ['rol_destino', 'rol', 'destino']);
    $colUser = firstExistingCol($pdo, $tbl, ['id_usuario', 'usuario_id', 'id_user']);
    if (!$colLeida || !$colRol || !$colUser)
        return 0;
    $sql = "SELECT COUNT(*) FROM {$tbl} WHERE {$colRol}='secretaria' AND {$colUser}=:id AND {$colLeida}=0";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $idSecretaria]);
    return (int) $st->fetchColumn();
}

try {
    switch ($accion) {

        case 'marcar_mensaje_leido': {
            $id_ms = (int) ($_POST['id_ms'] ?? 0);
            if ($id_ms <= 0)
                fail('id_ms inválido');

            // Restringe a la secretaría dueña del registro
            $st = $pdo->prepare("UPDATE mensajes_secretarias SET leido_en = IFNULL(leido_en, NOW()) WHERE id_ms=:id AND id_secretaria=:sec");
            $st->execute([':id' => $id_ms, ':sec' => $idSecretaria]);

            ok(['unread_msgs' => unread_msgs($pdo, $idSecretaria)]);
        }
            break;

        case 'marcar_notificacion_leida': {
            $tbl = 'notificaciones';
            $colId = firstExistingCol($pdo, $tbl, ['id_notificacion', 'id', 'notif_id']);
            $colLeida = firstExistingCol($pdo, $tbl, ['leida', 'leido', 'is_read']);
            $colRol = firstExistingCol($pdo, $tbl, ['rol_destino', 'rol', 'destino']);
            $colUser = firstExistingCol($pdo, $tbl, ['id_usuario', 'usuario_id', 'id_user']);
            $colFecha = firstExistingCol($pdo, $tbl, ['creada_en', 'creado_en', 'created_at', 'fecha', 'fecha_creacion']);
            $colTitulo = firstExistingCol($pdo, $tbl, ['titulo', 'title']);

            if (!$colLeida || !$colRol || !$colUser)
                ok(['unread_notis' => 0]); // tabla sin soporte

            $id_n = $_POST['id_noti'] ?? '';
            if ($colId && $id_n !== '') {
                $st = $pdo->prepare("UPDATE {$tbl} SET {$colLeida}=1 WHERE {$colId}=:id AND {$colRol}='secretaria' AND {$colUser}=:u");
                $st->execute([':id' => $id_n, ':u' => $idSecretaria]);
            } else {
                // Fallback por fecha/título si no hay PK
                $creada = $_POST['creada_en'] ?? '';
                $titulo = $_POST['titulo'] ?? '';
                $where = "{$colRol}='secretaria' AND {$colUser}=:u";
                $params = [':u' => $idSecretaria];

                if ($colFecha && $creada !== '') {
                    $where .= " AND {$colFecha} = :f";
                    $params[':f'] = $creada;
                }
                if ($colTitulo && $titulo !== '') {
                    $where .= " AND {$colTitulo} = :t";
                    $params[':t'] = $titulo;
                }

                $pdo->prepare("UPDATE {$tbl} SET {$colLeida}=1 WHERE {$where}")->execute($params);
            }

            ok(['unread_notis' => unread_notis($pdo, $idSecretaria)]);
        }
            break;

        default:
            fail('Acción no reconocida');
    }
} catch (Throwable $e) {
    fail('Excepción: ' . $e->getMessage(), 500);
}