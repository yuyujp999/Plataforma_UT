<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// No imprimir HTML de warnings/notices
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// ===== Helpers JSON =====
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

// ===== Sesión =====
if (!isset($_SESSION['rol'])) {
    json_error('No tienes permiso', 403);
}

// ===== PDO =====
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    json_error("Error de conexión: " . $e->getMessage(), 500);
}

// ===== Catálogo de nombres legibles =====
function getOrCreateNombreSemestreId(PDO $pdo, string $nombre): int
{
    $sel = $pdo->prepare("SELECT id_nombre_semestre FROM cat_nombres_semestre WHERE nombre = ?");
    $sel->execute([$nombre]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return (int) $row['id_nombre_semestre'];
    }

    $ins = $pdo->prepare("INSERT INTO cat_nombres_semestre (nombre) VALUES (?)");
    $ins->execute([$nombre]);
    return (int) $pdo->lastInsertId();
}

// ===== Acción =====
$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {

        // ------------------------ CREAR ------------------------
        case 'crear': {
            $semestre = isset($_POST['semestre']) ? (int) $_POST['semestre'] : 0;
            $id_carrera = isset($_POST['id_carrera']) ? (int) $_POST['id_carrera'] : 0;

            if ($semestre <= 0 || $id_carrera <= 0) {
                json_error('Faltan campos obligatorios');
            }

            // Validar carrera
            $cStmt = $pdo->prepare("SELECT nombre_carrera FROM carreras WHERE id_carrera = ?");
            $cStmt->execute([$id_carrera]);
            $carrera = $cStmt->fetch(PDO::FETCH_ASSOC);
            if (!$carrera) {
                json_error('Carrera no encontrada');
            }

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

            send_json([
                'status' => 'success',
                'message' => 'Semestre creado',
                'id_nombre_semestre' => $id_nombre_semestre,
                'nombre_semestre' => $nombre_legible
            ]);
            break;
        }

        // ------------------------ EDITAR ------------------------
        case 'editar': {
            $id = isset($_POST['id_semestre']) ? (int) $_POST['id_semestre'] : 0;
            $semestre = isset($_POST['semestre']) ? (int) $_POST['semestre'] : 0;
            $id_carrera = isset($_POST['id_carrera']) ? (int) $_POST['id_carrera'] : 0;

            if ($id <= 0 || $semestre <= 0 || $id_carrera <= 0) {
                json_error('Faltan campos obligatorios');
            }

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

                // Si no cambia el texto, solo actualiza semestre/carrera y listo
                if (trim($nombreNuevo) === trim($nombreActual)) {
                    $upd = $pdo->prepare("
                UPDATE semestres
                   SET semestre = ?, id_carrera = ?
                 WHERE id_semestre = ?
            ");
                    $upd->execute([$semestre, $id_carrera, $id]);
                    $pdo->commit();

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
                    // Solo lo usas tú: intenta mantener el mismo ID
                    if ($idNombreExistente > 0 && $idNombreExistente !== $oldIdNombre) {
                        // El texto ya existe con otro ID -> apuntar a ese ID y borrar el viejo
                        $upd = $pdo->prepare("
                    UPDATE semestres
                       SET semestre = ?, id_carrera = ?, id_nombre_semestre = ?
                     WHERE id_semestre = ?
                ");
                        $upd->execute([$semestre, $id_carrera, $idNombreExistente, $id]);

                        // Viejo queda huérfano -> elimínalo
                        $delCat = $pdo->prepare("DELETE FROM cat_nombres_semestre WHERE id_nombre_semestre = ? LIMIT 1");
                        $delCat->execute([$oldIdNombre]);

                        $finalId = $idNombreExistente;
                    } else {
                        // El texto no existe todavía -> actualiza el catálogo (mismo ID, no sube AUTO_INCREMENT)
                        $updCat = $pdo->prepare("UPDATE cat_nombres_semestre SET nombre = ? WHERE id_nombre_semestre = ?");
                        $updCat->execute([$nombreNuevo, $oldIdNombre]);

                        // Actualiza semestre/carrera (el id del catálogo no cambia)
                        $upd = $pdo->prepare("
                    UPDATE semestres
                       SET semestre = ?, id_carrera = ?
                     WHERE id_semestre = ?
                ");
                        $upd->execute([$semestre, $id_carrera, $id]);

                        $finalId = $oldIdNombre;
                    }
                } else {
                    // Lo usan varios -> no podemos cambiar el texto del catálogo
                    if ($idNombreExistente > 0) {
                        $finalId = $idNombreExistente;
                    } else {
                        // crea/obtiene (subirá AUTO_INCREMENT solo si es nuevo)
                        $finalId = getOrCreateNombreSemestreId($pdo, $nombreNuevo);
                    }

                    $upd = $pdo->prepare("
                UPDATE semestres
                   SET semestre = ?, id_carrera = ?, id_nombre_semestre = ?
                 WHERE id_semestre = ?
            ");
                    $upd->execute([$semestre, $id_carrera, $finalId, $id]);
                }

                $pdo->commit();

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

        // ------------------------ ELIMINAR ------------------------
        case 'eliminar': {
            $id = isset($_POST['id_semestre']) ? (int) $_POST['id_semestre'] : 0;
            if ($id <= 0) {
                json_error('ID no válido');
            }

            // Transacción: borrar semestre y limpiar catálogo si queda huérfano
            $pdo->beginTransaction();
            try {
                // Obtener id del catálogo asociado
                $sel = $pdo->prepare("SELECT id_nombre_semestre FROM semestres WHERE id_semestre = ?");
                $sel->execute([$id]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    throw new Exception('Semestre no encontrado.');
                }
                $idCat = (int) $row['id_nombre_semestre'];

                // Borrar semestre
                $del = $pdo->prepare("DELETE FROM semestres WHERE id_semestre = ?");
                $del->execute([$id]);

                // Si el catálogo quedó sin referencias, eliminarlo
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM semestres WHERE id_nombre_semestre = ?");
                $cnt->execute([$idCat]);
                if ((int) $cnt->fetchColumn() === 0) {
                    $delCat = $pdo->prepare("DELETE FROM cat_nombres_semestre WHERE id_nombre_semestre = ? LIMIT 1");
                    $delCat->execute([$idCat]);
                }

                $pdo->commit();
                send_json(['status' => 'success', 'message' => 'Semestre eliminado']);
            } catch (Throwable $ex) {
                $pdo->rollBack();
                json_error('Error de base de datos: ' . $ex->getMessage(), 500);
            }
            break;
        }

        // ------------------------ LISTAR ------------------------
        case 'listar': {
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
    // $e->errorInfo: [0] SQLSTATE, [1] driver error code (MySQL), [2] message
    $errInfo = $e->errorInfo ?? [];
    $mysqlCode = $errInfo[1] ?? null;

    if ($mysqlCode === 1062) {
        json_error('1062 Duplicate entry: ' . ($errInfo[2] ?? $e->getMessage()));
    } elseif ($mysqlCode === 1452) {
        // Cannot add or update a child row: a foreign key constraint fails
        json_error('Error de referencia (FK): verifica que id_carrera e id_nombre_semestre existan.');
    } else {
        json_error('Error de base de datos: ' . $e->getMessage(), 500);
    }
} catch (Throwable $e) {
    json_error('Error del servidor: ' . $e->getMessage(), 500);
}