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
    'eliminar' => ($rol === 'admin'),
];
function require_perm(string $action, array $perm): void
{
    if (empty($perm[$action]))
        json_error('Acción no permitida para tu rol', 403);
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
function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    $tipo = isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento', 'mensaje'], true) ? $cfg['tipo'] : 'movimiento';
    $titulo = (string) ($cfg['titulo'] ?? '');
    $detalle = (string) ($cfg['detalle'] ?? '');
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $actor_id = is_numeric($actor_id) ? (int) $actor_id : null;
    $recurso = (string) ($cfg['recurso'] ?? 'semestre');
    $accion = (string) ($cfg['accion'] ?? '');
    $meta = $cfg['meta'] ?? null;
    if (is_array($meta))
        $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
    elseif ($meta !== null)
        $meta = (string) $meta;

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
    } catch (Throwable $e) { /* no romper flujo */
    }
}

/* =================== Catálogo de nombres legibles =================== */
function getOrCreateNombreSemestreId(PDO $pdo, string $nombre): int
{
    $sel = $pdo->prepare("SELECT id_nombre_semestre FROM cat_nombres_semestre WHERE nombre = ?");
    $sel->execute([$nombre]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row)
        return (int) $row['id_nombre_semestre'];

    $ins = $pdo->prepare("INSERT INTO cat_nombres_semestre (nombre) VALUES (?)");
    $ins->execute([$nombre]);
    return (int) $pdo->lastInsertId();
}

/* ======================= Router de acciones ======================= */
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        /* ------------------------ CREAR ------------------------ */
        case 'crear': {
            require_perm('crear', $perm);

            $semestre = isset($_POST['semestre']) ? (int) $_POST['semestre'] : 0;
            $id_carrera = isset($_POST['id_carrera']) ? (int) $_POST['id_carrera'] : 0;

            if ($semestre <= 0 || $id_carrera <= 0)
                json_error('Faltan campos obligatorios');

            // Validar carrera
            $cStmt = $pdo->prepare("SELECT nombre_carrera FROM carreras WHERE id_carrera = ?");
            $cStmt->execute([$id_carrera]);
            $carrera = $cStmt->fetch(PDO::FETCH_ASSOC);
            if (!$carrera)
                json_error('Carrera no encontrada');

            // Duplicado exacto
            $dupStmt = $pdo->prepare("SELECT COUNT(*) AS n FROM semestres WHERE semestre = :sem AND id_carrera = :car");
            $dupStmt->execute([':sem' => $semestre, ':car' => $id_carrera]);
            if ((int) $dupStmt->fetch(PDO::FETCH_ASSOC)['n'] > 0) {
                json_error('Ya existe un registro con ese Semestre y Carrera.');
            }

            // Nombre legible y catálogo
            $nombre_legible = $carrera['nombre_carrera'] . ' ' . $semestre;
            $id_nombre_semestre = getOrCreateNombreSemestreId($pdo, $nombre_legible);

            // Insert
            $ins = $pdo->prepare("
                INSERT INTO semestres (semestre, id_carrera, id_nombre_semestre)
                VALUES (?, ?, ?)
            ");
            $ins->execute([$semestre, $id_carrera, $id_nombre_semestre]);
            $id_semestre = (int) $pdo->lastInsertId();

            // Notificación
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Alta de semestre',
                'detalle' => 'Se creó el semestre ' . $nombre_legible . '.',
                'actor_id' => $actorId,
                'recurso' => 'semestre',
                'accion' => 'alta',
                'meta' => [
                    'id_semestre' => $id_semestre,
                    'id_carrera' => $id_carrera,
                    'nombre_carrera' => $carrera['nombre_carrera'],
                    'semestre' => $semestre,
                    'id_nombre_semestre' => $id_nombre_semestre,
                    'nombre_semestre' => $nombre_legible
                ],
            ]);

            send_json([
                'status' => 'success',
                'message' => 'Semestre creado',
                'id_nombre_semestre' => $id_nombre_semestre,
                'nombre_semestre' => $nombre_legible
            ]);
            break;
        }

        /* ------------------------ EDITAR ------------------------ */
        case 'editar': {
            require_perm('editar', $perm);

            $id = isset($_POST['id_semestre']) ? (int) $_POST['id_semestre'] : 0;
            $semestre = isset($_POST['semestre']) ? (int) $_POST['semestre'] : 0;
            $id_carrera = isset($_POST['id_carrera']) ? (int) $_POST['id_carrera'] : 0;

            if ($id <= 0 || $semestre <= 0 || $id_carrera <= 0)
                json_error('Faltan campos obligatorios');

            // Validar carrera
            $cStmt = $pdo->prepare("SELECT nombre_carrera FROM carreras WHERE id_carrera = ?");
            $cStmt->execute([$id_carrera]);
            $carrera = $cStmt->fetch(PDO::FETCH_ASSOC);
            if (!$carrera)
                json_error('Carrera no encontrada');

            // Duplicado en otro registro
            $dupStmt = $pdo->prepare("
                SELECT COUNT(*) AS n
                  FROM semestres
                 WHERE semestre = :sem
                   AND id_carrera = :car
                   AND id_semestre <> :id
            ");
            $dupStmt->execute([':sem' => $semestre, ':car' => $id_carrera, ':id' => $id]);
            if ((int) $dupStmt->fetch(PDO::FETCH_ASSOC)['n'] > 0) {
                json_error('Ya existe otro registro con ese Semestre y Carrera.');
            }

            $pdo->beginTransaction();
            try {
                // Datos actuales
                $cur = $pdo->prepare("
                    SELECT s.id_nombre_semestre, ns.nombre AS nombre_actual
                    FROM semestres s
                    LEFT JOIN cat_nombres_semestre ns ON ns.id_nombre_semestre = s.id_nombre_semestre
                    WHERE s.id_semestre = ?
                    FOR UPDATE
                ");
                $cur->execute([$id]);
                $row = $cur->fetch(PDO::FETCH_ASSOC);
                if (!$row)
                    throw new Exception('Semestre no encontrado.');
                $oldIdNombre = (int) $row['id_nombre_semestre'];
                $nombreActual = (string) ($row['nombre_actual'] ?? '');

                $nombreNuevo = $carrera['nombre_carrera'] . ' ' . $semestre;

                // Si no cambia el texto, solo actualiza semestre/carrera
                if (trim($nombreNuevo) === trim($nombreActual)) {
                    $upd = $pdo->prepare("
                        UPDATE semestres
                           SET semestre = ?, id_carrera = ?
                         WHERE id_semestre = ?
                    ");
                    $upd->execute([$semestre, $id_carrera, $id]);
                    $pdo->commit();

                    // Notificación (edición sin cambio de texto)
                    $actorId = get_secretaria_actor_id_from_session();
                    notificar_admin_pdo($pdo, [
                        'tipo' => 'movimiento',
                        'titulo' => 'Edición de semestre',
                        'detalle' => 'Se actualizó el semestre ' . $nombreNuevo . '.',
                        'actor_id' => $actorId,
                        'recurso' => 'semestre',
                        'accion' => 'edicion',
                        'meta' => [
                            'id_semestre' => $id,
                            'id_carrera' => $id_carrera,
                            'nombre_carrera' => $carrera['nombre_carrera'],
                            'semestre' => $semestre,
                            'id_nombre_semestre' => $oldIdNombre,
                            'nombre_semestre' => $nombreNuevo
                        ],
                    ]);

                    send_json([
                        'status' => 'success',
                        'message' => 'Semestre actualizado',
                        'id_nombre_semestre' => $oldIdNombre,
                        'nombre_semestre' => $nombreNuevo
                    ]);
                    break;
                }

                // ¿Cuántos semestres usan el id actual?
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM semestres WHERE id_nombre_semestre = ?");
                $cnt->execute([$oldIdNombre]);
                $numUsos = (int) $cnt->fetchColumn();

                // ¿Ya existe un catálogo con el nombre nuevo?
                $selNuevo = $pdo->prepare("SELECT id_nombre_semestre FROM cat_nombres_semestre WHERE nombre = ?");
                $selNuevo->execute([$nombreNuevo]);
                $rowNuevo = $selNuevo->fetch(PDO::FETCH_ASSOC);
                $idNombreExistente = $rowNuevo ? (int) $rowNuevo['id_nombre_semestre'] : 0;

                if ($numUsos === 1) {
                    if ($idNombreExistente > 0 && $idNombreExistente !== $oldIdNombre) {
                        // Apuntar a existente y borrar el viejo
                        $upd = $pdo->prepare("
                            UPDATE semestres
                               SET semestre = ?, id_carrera = ?, id_nombre_semestre = ?
                             WHERE id_semestre = ?
                        ");
                        $upd->execute([$semestre, $id_carrera, $idNombreExistente, $id]);

                        $delCat = $pdo->prepare("DELETE FROM cat_nombres_semestre WHERE id_nombre_semestre = ? LIMIT 1");
                        $delCat->execute([$oldIdNombre]);

                        $finalId = $idNombreExistente;
                    } else {
                        // Renombrar el catálogo manteniendo ID
                        $updCat = $pdo->prepare("UPDATE cat_nombres_semestre SET nombre = ? WHERE id_nombre_semestre = ?");
                        $updCat->execute([$nombreNuevo, $oldIdNombre]);

                        $upd = $pdo->prepare("
                            UPDATE semestres
                               SET semestre = ?, id_carrera = ?
                             WHERE id_semestre = ?
                        ");
                        $upd->execute([$semestre, $id_carrera, $id]);

                        $finalId = $oldIdNombre;
                    }
                } else {
                    // Compartido -> usar existente o crear nuevo
                    $finalId = $idNombreExistente > 0 ? $idNombreExistente : getOrCreateNombreSemestreId($pdo, $nombreNuevo);
                    $upd = $pdo->prepare("
                        UPDATE semestres
                           SET semestre = ?, id_carrera = ?, id_nombre_semestre = ?
                         WHERE id_semestre = ?
                    ");
                    $upd->execute([$semestre, $id_carrera, $finalId, $id]);
                }

                $pdo->commit();

                // Notificación
                $actorId = get_secretaria_actor_id_from_session();
                notificar_admin_pdo($pdo, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Edición de semestre',
                    'detalle' => 'Se actualizó el semestre a ' . $nombreNuevo . '.',
                    'actor_id' => $actorId,
                    'recurso' => 'semestre',
                    'accion' => 'edicion',
                    'meta' => [
                        'id_semestre' => $id,
                        'id_carrera' => $id_carrera,
                        'nombre_carrera' => $carrera['nombre_carrera'],
                        'semestre' => $semestre,
                        'id_nombre_semestre' => $finalId,
                        'nombre_semestre' => $nombreNuevo
                    ],
                ]);

                send_json([
                    'status' => 'success',
                    'message' => 'Semestre actualizado',
                    'id_nombre_semestre' => $finalId,
                    'nombre_semestre' => $nombreNuevo
                ]);
            } catch (Throwable $ex) {
                $pdo->rollBack();
                json_error('Error de base de datos: ' . $ex->getMessage(), 500);
            }
            break;
        }

        /* ------------------------ ELIMINAR ------------------------ */
        case 'eliminar': {
            require_perm('eliminar', $perm);

            $id = isset($_POST['id_semestre']) ? (int) $_POST['id_semestre'] : 0;
            if ($id <= 0)
                json_error('ID no válido');

            $pdo->beginTransaction();
            try {
                // Traer info previa para meta/detalle
                $info = $pdo->prepare("
                    SELECT s.id_semestre, s.semestre, s.id_carrera,
                           c.nombre_carrera,
                           ns.id_nombre_semestre, ns.nombre AS nombre_semestre
                      FROM semestres s
                 LEFT JOIN carreras c ON c.id_carrera = s.id_carrera
                 LEFT JOIN cat_nombres_semestre ns ON ns.id_nombre_semestre = s.id_nombre_semestre
                     WHERE s.id_semestre = ?
                ");
                $info->execute([$id]);
                $prev = $info->fetch(PDO::FETCH_ASSOC);
                if (!$prev)
                    throw new Exception('Semestre no encontrado.');

                $idCat = (int) ($prev['id_nombre_semestre'] ?? 0);

                // Borrar semestre
                $del = $pdo->prepare("DELETE FROM semestres WHERE id_semestre = ?");
                $del->execute([$id]);

                // Si el catálogo quedó sin referencias, eliminarlo
                if ($idCat > 0) {
                    $cnt = $pdo->prepare("SELECT COUNT(*) FROM semestres WHERE id_nombre_semestre = ?");
                    $cnt->execute([$idCat]);
                    if ((int) $cnt->fetchColumn() === 0) {
                        $delCat = $pdo->prepare("DELETE FROM cat_nombres_semestre WHERE id_nombre_semestre = ? LIMIT 1");
                        $delCat->execute([$idCat]);
                    }
                }

                $pdo->commit();

                // Notificación
                $actorId = get_secretaria_actor_id_from_session();
                notificar_admin_pdo($pdo, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Eliminación de semestre',
                    'detalle' => 'Se eliminó el semestre ' . ($prev['nombre_semestre'] ?? ('ID ' . $id)) . '.',
                    'actor_id' => $actorId,
                    'recurso' => 'semestre',
                    'accion' => 'eliminacion',
                    'meta' => [
                        'id_semestre' => $id,
                        'id_carrera' => $prev['id_carrera'] ?? null,
                        'nombre_carrera' => $prev['nombre_carrera'] ?? null,
                        'semestre' => $prev['semestre'] ?? null,
                        'id_nombre_semestre' => $prev['id_nombre_semestre'] ?? null,
                        'nombre_semestre' => $prev['nombre_semestre'] ?? null
                    ],
                ]);

                send_json(['status' => 'success', 'message' => 'Semestre eliminado']);
            } catch (Throwable $ex) {
                $pdo->rollBack();
                json_error('Error de base de datos: ' . $ex->getMessage(), 500);
            }
            break;
        }

        /* ------------------------ LISTAR ------------------------ */
        case 'listar': {
            require_perm('listar', $perm);

            $q = $pdo->query("
                SELECT 
                    s.id_semestre,
                    s.semestre,
                    s.id_carrera,
                    c.nombre_carrera,
                    ns.nombre AS nombre_semestre
                FROM semestres s
                LEFT JOIN carreras c ON c.id_carrera = s.id_carrera
                LEFT JOIN cat_nombres_semestre ns ON ns.id_nombre_semestre = s.id_nombre_semestre
                ORDER BY c.nombre_carrera, s.semestre
            ");
            $semestres = $q->fetchAll(PDO::FETCH_ASSOC);
            send_json(['status' => 'success', 'data' => $semestres]);
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
        json_error('Error de referencia (FK): verifica que id_carrera e id_nombre_semestre existan.');
    } else {
        json_error('Error de base de datos: ' . $e->getMessage(), 500);
    }
} catch (Throwable $e) {
    json_error('Error del servidor: ' . $e->getMessage(), 500);
}