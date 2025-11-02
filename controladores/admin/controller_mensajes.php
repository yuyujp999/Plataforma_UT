<?php
/**
 * controller_mensajes.php
 * Acciones:
 *  - listar_mensajes
 *  - listar_secretarias
 *  - crear_mensaje
 *  - detalle_mensaje
 *  - actualizar_mensaje
 *  - eliminar_mensaje
 *  - actualizar_destinatarias
 */

session_start();

// --- Headers JSON + CORS ---
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Mostrar errores en dev (apágalo en prod) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Helpers JSON ---
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

// --- Seguridad: sesión y rol ---
if (!isset($_SESSION['rol']) || !isset($_SESSION['usuario'])) {
    fail('No autorizado (sesión).', 401);
}
$rol = $_SESSION['rol'] ?? '';
if (!in_array(strtolower($rol), ['admin', 'administrador'], true)) {
    // Si deseas restringir por rol, descomenta:
    // fail('No autorizado (rol).', 403);
}

// --- Obtener id_admin desde la sesión ---
function getAdminIdFromSession(): ?int
{
    $u = $_SESSION['usuario'] ?? [];
    foreach (['id_admin', 'admin_id', 'id', 'idadmin'] as $k) {
        if (isset($u[$k]) && is_numeric($u[$k])) {
            return (int) $u[$k];
        }
    }
    return null;
}

// --- Conexión PDO ---
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    fail('Error de conexión: ' . $e->getMessage(), 500);
}

// --- Leer acción ---
$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {

        case 'listar_mensajes': {
            $sql = "
                SELECT 
                    m.id_mensaje,
                    m.id_admin,
                    m.titulo,
                    m.cuerpo,
                    m.prioridad,
                    m.fecha_envio,
                    COALESCE(COUNT(ms.id_ms), 0) AS total_destinatarias,
                    COALESCE(SUM(CASE WHEN ms.leido_en IS NOT NULL THEN 1 ELSE 0 END), 0) AS total_leidos
                FROM mensajes m
                LEFT JOIN mensajes_secretarias ms ON ms.id_mensaje = m.id_mensaje
                GROUP BY m.id_mensaje
                ORDER BY m.id_mensaje DESC
            ";
            $st = $pdo->query($sql);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            ok($rows);
        }
            break;

        case 'listar_secretarias': {
            $sql = "
                SELECT 
                    id_secretaria,
                    nombre,
                    apellido_paterno,
                    apellido_materno
                FROM secretarias
                ORDER BY apellido_paterno ASC, nombre ASC
            ";
            $st = $pdo->query($sql);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            ok($rows);
        }
            break;

        case 'crear_mensaje': {
            $idAdmin = getAdminIdFromSession();
            if (!$idAdmin)
                fail('No se pudo obtener el id del administrador desde la sesión.');

            $titulo = trim($_POST['titulo'] ?? '');
            $cuerpo = trim($_POST['cuerpo'] ?? '');
            $prioridad = strtolower(trim($_POST['prioridad'] ?? 'normal'));
            $destino = $_POST['destino'] ?? 'todas';
            $secretarias = $_POST['secretarias'] ?? [];

            if ($titulo === '' || $cuerpo === '')
                fail('Título y contenido son obligatorios.');
            if (!in_array($prioridad, ['normal', 'alta'], true))
                $prioridad = 'normal';
            if (!in_array($destino, ['todas', 'especificas'], true))
                $destino = 'todas';

            // Determinar destinatarias
            if ($destino === 'todas') {
                $st = $pdo->query("SELECT id_secretaria FROM secretarias");
                $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            } else {
                $ids = array_values(array_unique(array_filter(array_map('intval', (array) $secretarias), fn($v) => $v > 0)));
                if (!$ids)
                    fail('Selecciona al menos una secretaría.');
                $in = implode(',', array_fill(0, count($ids), '?'));
                $stV = $pdo->prepare("SELECT id_secretaria FROM secretarias WHERE id_secretaria IN ($in)");
                $stV->execute($ids);
                $ids = array_map('intval', $stV->fetchAll(PDO::FETCH_COLUMN));
                if (!$ids)
                    fail('Las secretarías seleccionadas no existen.');
            }

            $pdo->beginTransaction();

            // Inserta mensaje
            $stm = $pdo->prepare("
                INSERT INTO mensajes (id_admin, titulo, cuerpo, prioridad, fecha_envio)
                VALUES (:id_admin, :titulo, :cuerpo, :prioridad, NOW())
            ");
            $stm->execute([
                ':id_admin' => $idAdmin,
                ':titulo' => $titulo,
                ':cuerpo' => $cuerpo,
                ':prioridad' => $prioridad,
            ]);
            $idMensaje = (int) $pdo->lastInsertId();

            // Inserta destinatarias
            $ins = $pdo->prepare("
                INSERT INTO mensajes_secretarias (id_mensaje, id_secretaria)
                VALUES (:id_mensaje, :id_secretaria)
            ");
            foreach ($ids as $idSec) {
                $ins->execute([
                    ':id_mensaje' => $idMensaje,
                    ':id_secretaria' => (int) $idSec,
                ]);
            }

            // (Opcional) notificaciones
            try {
                if (!empty($ids)) {
                    $notif = $pdo->prepare("
                        INSERT INTO notificaciones
                            (id_usuario, rol_destino, tipo, id_referencia, titulo, detalle, leida, creada_en)
                        VALUES
                            (:id_usuario, 'secretaria', 'mensaje', :id_referencia, :titulo, :detalle, 0, NOW())
                    ");
                    foreach ($ids as $idSec) {
                        $notif->execute([
                            ':id_usuario' => (int) $idSec,
                            ':id_referencia' => $idMensaje,
                            ':titulo' => $titulo,
                            ':detalle' => mb_substr($cuerpo, 0, 120) . (mb_strlen($cuerpo) > 120 ? '…' : '')
                        ]);
                    }
                }
            } catch (Throwable $e) {
                // Silencioso
            }

            $pdo->commit();
            ok([], ['message' => 'Mensaje enviado correctamente.']);
        }
            break;

        case 'detalle_mensaje': {
            $id = (int) ($_POST['id_mensaje'] ?? 0);
            if ($id <= 0)
                fail('Parámetro id_mensaje inválido.');

            $stM = $pdo->prepare("
                SELECT id_mensaje, id_admin, titulo, cuerpo, prioridad, fecha_envio
                FROM mensajes
                WHERE id_mensaje = :id LIMIT 1
            ");
            $stM->execute([':id' => $id]);
            $mensaje = $stM->fetch(PDO::FETCH_ASSOC);
            if (!$mensaje)
                fail('Mensaje no encontrado.');

            $stD = $pdo->prepare("
                SELECT 
                    ms.id_secretaria,
                    CONCAT_WS(' ', s.nombre, s.apellido_paterno, s.apellido_materno) AS nombre_secretaria,
                    ms.leido_en
                FROM mensajes_secretarias ms
                INNER JOIN secretarias s ON s.id_secretaria = ms.id_secretaria
                WHERE ms.id_mensaje = :id
                ORDER BY s.apellido_paterno ASC, s.nombre ASC
            ");
            $stD->execute([':id' => $id]);
            $destinatarias = $stD->fetchAll(PDO::FETCH_ASSOC);

            ok(['mensaje' => $mensaje, 'destinatarias' => $destinatarias]);
        }
            break;

        case 'actualizar_mensaje': {
            $id = (int) ($_POST['id_mensaje'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $cuerpo = trim($_POST['cuerpo'] ?? '');
            $prioridad = strtolower(trim($_POST['prioridad'] ?? 'normal'));

            if ($id <= 0)
                fail('Parámetro id_mensaje inválido.');
            if ($titulo === '' || $cuerpo === '')
                fail('Título y contenido son obligatorios.');
            if (!in_array($prioridad, ['normal', 'alta'], true))
                $prioridad = 'normal';

            $st = $pdo->prepare("
                UPDATE mensajes
                SET titulo = :titulo, cuerpo = :cuerpo, prioridad = :prioridad
                WHERE id_mensaje = :id
                LIMIT 1
            ");
            $st->execute([
                ':titulo' => $titulo,
                ':cuerpo' => $cuerpo,
                ':prioridad' => $prioridad,
                ':id' => $id,
            ]);

            ok([], ['message' => 'Mensaje actualizado.']);
        }
            break;

        case 'eliminar_mensaje': {
            $id = (int) ($_POST['id_mensaje'] ?? 0);
            if ($id <= 0)
                fail('Parámetro id_mensaje inválido.');

            $pdo->beginTransaction();

            try {
                $delN = $pdo->prepare("DELETE FROM notificaciones WHERE tipo = 'mensaje' AND id_referencia = :id");
                $delN->execute([':id' => $id]);
            } catch (Throwable $e) {
            }

            $delD = $pdo->prepare("DELETE FROM mensajes_secretarias WHERE id_mensaje = :id");
            $delD->execute([':id' => $id]);

            $delM = $pdo->prepare("DELETE FROM mensajes WHERE id_mensaje = :id LIMIT 1");
            $delM->execute([':id' => $id]);

            $pdo->commit();

            ok([], ['message' => 'Mensaje eliminado.']);
        }
            break;

        case 'actualizar_destinatarias': {
            $id = (int) ($_POST['id_mensaje'] ?? 0);
            $destino = $_POST['destino'] ?? 'especificas'; // <-- soporta 'todas'
            $secretarias = $_POST['secretarias'] ?? [];

            if ($id <= 0)
                fail('Parámetro id_mensaje inválido.');

            // Construir lista de IDs a asignar
            if ($destino === 'todas') {
                $stAll = $pdo->query("SELECT id_secretaria FROM secretarias");
                $ids = array_map('intval', $stAll->fetchAll(PDO::FETCH_COLUMN));
                if (!$ids)
                    fail('No hay secretarías registradas.');
            } else {
                $ids = array_values(array_unique(array_filter(array_map('intval', (array) $secretarias), fn($v) => $v > 0)));
                if (!$ids)
                    fail('Selecciona al menos una secretaría.');
                $in = implode(',', array_fill(0, count($ids), '?'));
                $stV = $pdo->prepare("SELECT id_secretaria FROM secretarias WHERE id_secretaria IN ($in)");
                $stV->execute($ids);
                $ids = array_map('intval', $stV->fetchAll(PDO::FETCH_COLUMN));
                if (!$ids)
                    fail('Las secretarías seleccionadas no existen.');
            }

            $pdo->beginTransaction();

            // Reemplazar destinatarias
            $del = $pdo->prepare("DELETE FROM mensajes_secretarias WHERE id_mensaje = :id");
            $del->execute([':id' => $id]);

            $ins = $pdo->prepare("
                INSERT INTO mensajes_secretarias (id_mensaje, id_secretaria)
                VALUES (:id_mensaje, :id_secretaria)
            ");
            foreach ($ids as $idSec) {
                $ins->execute([
                    ':id_mensaje' => $id,
                    ':id_secretaria' => (int) $idSec,
                ]);
            }

            // (Opcional) refrescar notificaciones
            try {
                $delN = $pdo->prepare("DELETE FROM notificaciones WHERE tipo = 'mensaje' AND id_referencia = :id");
                $delN->execute([':id' => $id]);

                $stMsg = $pdo->prepare("SELECT titulo, cuerpo FROM mensajes WHERE id_mensaje = :id LIMIT 1");
                $stMsg->execute([':id' => $id]);
                $msgRow = $stMsg->fetch(PDO::FETCH_ASSOC);
                $titulo = $msgRow['titulo'] ?? 'Mensaje';
                $cuerpo = $msgRow['cuerpo'] ?? '';

                $notif = $pdo->prepare("
                    INSERT INTO notificaciones
                        (id_usuario, rol_destino, tipo, id_referencia, titulo, detalle, leida, creada_en)
                    VALUES
                        (:id_usuario, 'secretaria', 'mensaje', :id_referencia, :titulo, :detalle, 0, NOW())
                ");
                foreach ($ids as $idSec) {
                    $notif->execute([
                        ':id_usuario' => (int) $idSec,
                        ':id_referencia' => $id,
                        ':titulo' => $titulo,
                        ':detalle' => mb_substr($cuerpo, 0, 120) . (mb_strlen($cuerpo) > 120 ? '…' : '')
                    ]);
                }
            } catch (Throwable $e) {
            }

            $pdo->commit();

            ok([], ['message' => 'Destinatarias actualizadas.']);
        }
            break;

        default:
            fail('Acción no reconocida.');
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    fail('Excepción: ' . $e->getMessage(), 500);
}