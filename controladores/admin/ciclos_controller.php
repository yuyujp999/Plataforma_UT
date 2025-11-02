<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== Verificar sesión =====
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// ===== Conexión PDO =====
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// ===== Helpers =====
function respond($status, $message = null, $extra = [])
{
    $payload = array_merge(['status' => $status], $message ? ['message' => $message] : [], $extra);
    echo json_encode($payload);
    exit;
}

function isValidDate($date)
{
    if (!$date)
        return false;
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function bool01($v)
{
    // Acepta "1", 1, true, "on" => 1; cualquier otra cosa => 0
    if (is_string($v))
        $v = strtolower($v);
    return in_array($v, [1, '1', true, 'true', 'on', 'sí', 'si'], true) ? 1 : 0;
}

// ===== Router =====
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    // -------------------------------------------------
    // CREAR CICLO
    // -------------------------------------------------
    case 'agregar':
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
            $stmt = $pdo->prepare(
                "INSERT INTO ciclos_escolares (clave, fecha_inicio, fecha_fin, activo)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$clave, $fecha_inicio, $fecha_fin, $activo]);
            $id = (int) $pdo->lastInsertId();

            respond('success', null, [
                'data' => [
                    'id_ciclo' => $id,
                    'clave' => $clave,
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin,
                    'activo' => $activo
                ]
            ]);
        } catch (PDOException $e) {
            // 1062 => duplicate entry (por índice único en clave)
            if ((int) $e->errorInfo[1] === 1062) {
                respond('error', 'La clave ya existe. Debe ser única.');
            }
            respond('error', 'No se pudo agregar el ciclo: ' . $e->getMessage());
        }
        break;

    // -------------------------------------------------
    // EDITAR CICLO
    // -------------------------------------------------
    case 'editar':
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
            $stmt = $pdo->prepare(
                "UPDATE ciclos_escolares
                   SET clave = ?, fecha_inicio = ?, fecha_fin = ?, activo = ?
                 WHERE id_ciclo = ?"
            );
            $ok = $stmt->execute([$clave, $fecha_inicio, $fecha_fin, $activo, $id_ciclo]);

            if ($ok && $stmt->rowCount() > 0) {
                respond('success', null, [
                    'data' => [
                        'id_ciclo' => (int) $id_ciclo,
                        'clave' => $clave,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                        'activo' => $activo
                    ]
                ]);
            } else {
                // Puede ser que no cambió nada pero existe
                respond('success', 'Sin cambios', [
                    'data' => [
                        'id_ciclo' => (int) $id_ciclo,
                        'clave' => $clave,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                        'activo' => $activo
                    ]
                ]);
            }
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                respond('error', 'La clave ya existe. Debe ser única.');
            }
            respond('error', 'No se pudo actualizar el ciclo: ' . $e->getMessage());
        }
        break;

    // -------------------------------------------------
    // ELIMINAR CICLO
    // -------------------------------------------------
    case 'eliminar':
        $id_ciclo = $_POST['id_ciclo'] ?? null;
        if (!$id_ciclo) {
            respond('error', 'ID no válido');
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM ciclos_escolares WHERE id_ciclo = ?");
            $ok = $stmt->execute([$id_ciclo]);

            if ($ok && $stmt->rowCount() > 0) {
                respond('success');
            } else {
                respond('error', 'No se encontró el ciclo o no se pudo eliminar');
            }
        } catch (PDOException $e) {
            // 1451 => cannot delete or update a parent row: a foreign key constraint fails
            if ((int) $e->errorInfo[1] === 1451) {
                respond('error', 'No se puede eliminar: el ciclo está referenciado por otros registros.');
            }
            respond('error', 'Error al eliminar: ' . $e->getMessage());
        }
        break;

    // -------------------------------------------------
    // (Opcional) ACTIVAR/DESACTIVAR RÁPIDO
    // -------------------------------------------------
    case 'toggle_activo':
        $id_ciclo = $_POST['id_ciclo'] ?? null;
        $activo = bool01($_POST['activo'] ?? 0);
        if (!$id_ciclo) {
            respond('error', 'ID no válido');
        }
        try {
            $stmt = $pdo->prepare("UPDATE ciclos_escolares SET activo = ? WHERE id_ciclo = ?");
            $stmt->execute([$activo, $id_ciclo]);
            respond('success', null, ['data' => ['id_ciclo' => (int) $id_ciclo, 'activo' => $activo]]);
        } catch (PDOException $e) {
            respond('error', 'No se pudo actualizar el estado: ' . $e->getMessage());
        }
        break;

    // -------------------------------------------------
    default:
        respond('error', 'Acción no reconocida');
}