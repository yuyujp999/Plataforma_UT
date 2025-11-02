<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/conexion.php'; // Debe exponer $conn (mysqli)

/* ======================= Helpers de Notificaciones ======================= */

/** Obtiene el id de secretaría desde la sesión para usarlo como actor_id */
function getSecretariaActorIdFromSession(): ?int
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
 * Inserta una notificación dirigida al admin (silencioso si falla).
 * Tabla: notificaciones(tipo,titulo,detalle,para_rol,actor_id,recurso,accion,meta,leido,created_at)
 */
function notificar_admin(mysqli $conn, array $cfg): void
{
    $tipo = (isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento', 'mensaje'], true)) ? $cfg['tipo'] : 'movimiento';
    $titulo = (string) ($cfg['titulo'] ?? '');
    $detalle = (string) ($cfg['detalle'] ?? '');
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $recurso = (string) ($cfg['recurso'] ?? 'asignacion_docente');
    $accion = (string) ($cfg['accion'] ?? '');
    $meta = $cfg['meta'] ?? null;
    if (is_array($meta))
        $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO notificaciones (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido)
            VALUES (?,?,?,?,?,?,?,?,0)";
    if ($stmt = $conn->prepare($sql)) {
        if (is_null($actor_id)) {
            $stmt->bind_param("sssssss", $tipo, $titulo, $detalle, $para_rol, $actor_id, $recurso, $accion, $meta);
        } else {
            // bind_param requiere tipos: s s s s i s s s
            $stmt->bind_param("ssssisss", $tipo, $titulo, $detalle, $para_rol, $actor_id, $recurso, $accion, $meta);
        }
        // Ejecutar de forma silenciosa
        @$stmt->execute();
        $stmt->close();
    }
}

/* ======================= Helpers de dominio ======================= */

// Texto: "Profesor {Docente} - {ClaveMateria}"
function buildNombrePMg(mysqli $conn, int $id_docente, int $id_nombre_materia): string
{
    // Docente
    $stmt = $conn->prepare("SELECT nombre, apellido_paterno, COALESCE(apellido_materno,'') am FROM docentes WHERE id_docente=?");
    $stmt->bind_param('i', $id_docente);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Clave materia (catálogo)
    $stmt = $conn->prepare("SELECT nombre FROM cat_nombres_materias WHERE id_nombre_materia=?");
    $stmt->bind_param('i', $id_nombre_materia);
    $stmt->execute();
    $mat = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$doc || !$mat)
        return 'Profesor - Desconocido';

    $nombreCompleto = trim($doc['nombre'] . ' ' . $doc['apellido_paterno'] . ' ' . $doc['am']);
    return "Profesor {$nombreCompleto} - {$mat['nombre']}";
}

/** Devuelve id catálogo si existe para ese nombre */
function getIdCatalogoPMg(mysqli $conn, string $nombre): ?int
{
    $stmt = $conn->prepare("SELECT id_nombre_profesor_materia_grupo FROM cat_nombre_profesor_materia_grupo WHERE nombre=? LIMIT 1");
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['id_nombre_profesor_materia_grupo'] : null;
}

/** Inserta en catálogo y devuelve ID */
function createCatalogoPMg(mysqli $conn, string $nombre): int
{
    $stmt = $conn->prepare("INSERT INTO cat_nombre_profesor_materia_grupo (nombre) VALUES (?)");
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

/** Cuenta cuántas asignaciones usan ese id de catálogo PMg */
function countUsosCatalogoPMg(mysqli $conn, int $id_cpmg): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM asignaciones_docentes WHERE id_nombre_profesor_materia_grupo = ?");
    $stmt->bind_param('i', $id_cpmg);
    $stmt->execute();
    $c = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $c;
}

/** Borra del catálogo si ya nadie lo usa */
function deleteCatalogoPMgSiHuerfano(mysqli $conn, int $id_cpmg): void
{
    if (countUsosCatalogoPMg($conn, $id_cpmg) === 0) {
        $stmt = $conn->prepare("DELETE FROM cat_nombre_profesor_materia_grupo WHERE id_nombre_profesor_materia_grupo = ? LIMIT 1");
        $stmt->bind_param('i', $id_cpmg);
        $stmt->execute();
        $stmt->close();
    }
}

/** Valida duplicado (docente + id_nombre_materia). Excluir id_asignacion si se pasa. */
function existeDuplicado(mysqli $conn, int $id_docente, int $id_nombre_materia, int $excluir_id = 0): bool
{
    if ($excluir_id > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM asignaciones_docentes WHERE id_docente=? AND id_nombre_materia=? AND id_asignacion_docente<>?");
        $stmt->bind_param('iii', $id_docente, $id_nombre_materia, $excluir_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM asignaciones_docentes WHERE id_docente=? AND id_nombre_materia=?");
        $stmt->bind_param('ii', $id_docente, $id_nombre_materia);
    }
    $stmt->execute();
    $c = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $c > 0;
}

/** Nombre completo del docente por id (para notificación) */
function getNombreDocente(mysqli $conn, int $id_docente): ?string
{
    $stmt = $conn->prepare("SELECT nombre, apellido_paterno, COALESCE(apellido_materno,'') am FROM docentes WHERE id_docente=?");
    $stmt->bind_param('i', $id_docente);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r)
        return null;
    return trim($r['nombre'] . ' ' . $r['apellido_paterno'] . ' ' . $r['am']);
}

/** Clave (nombre) de materia por id_nombre_materia (para notificación) */
function getClaveMateria(mysqli $conn, int $id_nombre_materia): ?string
{
    $stmt = $conn->prepare("SELECT nombre FROM cat_nombres_materias WHERE id_nombre_materia=?");
    $stmt->bind_param('i', $id_nombre_materia);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ? (string) $r['nombre'] : null;
}

/* ======================= Router ======================= */

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        // ------- VALIDAR DUPLICADO -------
        case 'validarDuplicado': {
            $id_docente = (int) ($_POST['id_docente'] ?? 0);
            $id_nombre_materia = (int) ($_POST['id_nombre_materia'] ?? 0);
            $id_asignacion = (int) ($_POST['id_asignacion'] ?? 0);

            if ($id_docente <= 0 || $id_nombre_materia <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan datos']);
                break;
            }

            if (existeDuplicado($conn, $id_docente, $id_nombre_materia, $id_asignacion)) {
                echo json_encode(['status' => 'error', 'message' => 'Este docente ya está asignado a esa materia']);
            } else {
                echo json_encode(['status' => 'success']);
            }
            break;
        }

        // ------- AGREGAR -------
        case 'agregar': {
            $id_docente = (int) ($_POST['id_docente'] ?? 0);
            $id_nombre_materia = (int) ($_POST['id_nombre_materia'] ?? 0);
            $nombre_pmg = trim((string) ($_POST['nombre_profesor_materia_grupo'] ?? ''));

            if ($id_docente <= 0 || $id_nombre_materia <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
                break;
            }

            if (existeDuplicado($conn, $id_docente, $id_nombre_materia)) {
                echo json_encode(['status' => 'error', 'message' => 'Este docente ya está asignado a esa materia']);
                break;
            }

            if ($nombre_pmg === '') {
                $nombre_pmg = buildNombrePMg($conn, $id_docente, $id_nombre_materia);
            }

            // Reutiliza catálogo si existe; si no, crea
            $id_cpmg = getIdCatalogoPMg($conn, $nombre_pmg);
            if ($id_cpmg === null) {
                $id_cpmg = createCatalogoPMg($conn, $nombre_pmg);
            }

            $stmt = $conn->prepare("INSERT INTO asignaciones_docentes (id_docente, id_nombre_materia, id_nombre_profesor_materia_grupo) VALUES (?,?,?)");
            $stmt->bind_param('iii', $id_docente, $id_nombre_materia, $id_cpmg);
            $ok = $stmt->execute();
            $newId = (int) $stmt->insert_id;
            $stmt->close();

            // ---- Notificación ----
            if ($ok) {
                $actorId = getSecretariaActorIdFromSession();
                $docenteNom = getNombreDocente($conn, $id_docente) ?? 'Docente';
                $claveMat = getClaveMateria($conn, $id_nombre_materia) ?? 'Clave';
                notificar_admin($conn, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Asignación de docente a materia',
                    'detalle' => "Se asignó a {$docenteNom} en {$claveMat}.",
                    'actor_id' => $actorId,
                    'recurso' => 'asignacion_docente',
                    'accion' => 'alta',
                    'meta' => [
                        'id_asignacion_docente' => $newId,
                        'id_docente' => $id_docente,
                        'docente' => $docenteNom,
                        'id_nombre_materia' => $id_nombre_materia,
                        'clave_materia' => $claveMat,
                        'id_cpmg' => $id_cpmg,
                        'nombre_pmg' => $nombre_pmg,
                    ],
                ]);
            }

            echo json_encode($ok
                ? ['status' => 'success', 'id_asignacion' => $newId]
                : ['status' => 'error', 'message' => 'No se pudo agregar la asignación']);
            break;
        }

        // ------- EDITAR (sin huérfanos / conserva ID cuando procede) -------
        case 'editar': {
            $id_asignacion = (int) ($_POST['id_asignacion'] ?? 0);
            $id_docente = (int) ($_POST['id_docente'] ?? 0);
            $id_nombre_materia = (int) ($_POST['id_nombre_materia'] ?? 0);
            $nombre_pmg = trim((string) ($_POST['nombre_profesor_materia_grupo'] ?? ''));

            if ($id_asignacion <= 0 || $id_docente <= 0 || $id_nombre_materia <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
                break;
            }

            if (existeDuplicado($conn, $id_docente, $id_nombre_materia, $id_asignacion)) {
                echo json_encode(['status' => 'error', 'message' => 'Este docente ya está asignado a esa materia']);
                break;
            }

            if ($nombre_pmg === '') {
                $nombre_pmg = buildNombrePMg($conn, $id_docente, $id_nombre_materia);
            }

            // Estado actual (id del catálogo PMg actual) + datos previos para notificación
            $stmt = $conn->prepare("
                SELECT ad.id_nombre_profesor_materia_grupo,
                       ad.id_docente AS old_id_docente,
                       ad.id_nombre_materia AS old_id_nombre_materia
                  FROM asignaciones_docentes ad
                 WHERE ad.id_asignacion_docente=? FOR UPDATE
            ");
            $conn->begin_transaction();
            $stmt->bind_param('i', $id_asignacion);
            $stmt->execute();
            $rowCur = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$rowCur) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Asignación no encontrada']);
                break;
            }
            $oldIdCpmg = (int) $rowCur['id_nombre_profesor_materia_grupo'];
            $oldIdDocente = (int) $rowCur['old_id_docente'];
            $oldIdNomMat = (int) $rowCur['old_id_nombre_materia'];

            $oldDocenteNom = getNombreDocente($conn, $oldIdDocente) ?? '';
            $oldClaveMat = getClaveMateria($conn, $oldIdNomMat) ?? '';

            // ¿Existe un catálogo con el nombre objetivo?
            $idExistente = getIdCatalogoPMg($conn, $nombre_pmg);

            // ¿Cuántos usan el catálogo viejo?
            $usosViejo = countUsosCatalogoPMg($conn, $oldIdCpmg);

            $ok = false;
            try {
                if ($usosViejo === 1) {
                    // Solo lo usa esta asignación
                    if ($idExistente !== null && $idExistente !== $oldIdCpmg) {
                        // La descripción deseada ya existe con otro ID -> apuntar a ese y borrar el viejo si queda huérfano
                        $upd = $conn->prepare("UPDATE asignaciones_docentes SET id_docente=?, id_nombre_materia=?, id_nombre_profesor_materia_grupo=? WHERE id_asignacion_docente=?");
                        $upd->bind_param('iiii', $id_docente, $id_nombre_materia, $idExistente, $id_asignacion);
                        $ok = $upd->execute();
                        $upd->close();

                        deleteCatalogoPMgSiHuerfano($conn, $oldIdCpmg);
                        $finalId = $idExistente;
                    } else {
                        // Renombrar en sitio (misma PK)
                        $updCat = $conn->prepare("UPDATE cat_nombre_profesor_materia_grupo SET nombre=? WHERE id_nombre_profesor_materia_grupo=?");
                        $updCat->bind_param('si', $nombre_pmg, $oldIdCpmg);
                        $updCat->execute();
                        $updCat->close();

                        $upd = $conn->prepare("UPDATE asignaciones_docentes SET id_docente=?, id_nombre_materia=? WHERE id_asignacion_docente=?");
                        $upd->bind_param('iii', $id_docente, $id_nombre_materia, $id_asignacion);
                        $ok = $upd->execute();
                        $upd->close();
                        $finalId = $oldIdCpmg;
                    }
                } else {
                    // Lo usan varios -> no renombramos esa PK
                    $finalId = $idExistente !== null ? $idExistente : createCatalogoPMg($conn, $nombre_pmg);

                    $upd = $conn->prepare("UPDATE asignaciones_docentes SET id_docente=?, id_nombre_materia=?, id_nombre_profesor_materia_grupo=? WHERE id_asignacion_docente=?");
                    $upd->bind_param('iiii', $id_docente, $id_nombre_materia, $finalId, $id_asignacion);
                    $ok = $upd->execute();
                    $upd->close();
                }

                $conn->commit();
            } catch (Throwable $tx) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $tx->getMessage()]);
                break;
            }

            // ---- Notificación ----
            if ($ok) {
                $actorId = getSecretariaActorIdFromSession();
                $newDocNom = getNombreDocente($conn, $id_docente) ?? '';
                $newClaveMat = getClaveMateria($conn, $id_nombre_materia) ?? '';

                notificar_admin($conn, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Edición de asignación de docente',
                    'detalle' => "Se actualizó: {$oldDocenteNom} / {$oldClaveMat} → {$newDocNom} / {$newClaveMat}.",
                    'actor_id' => $actorId,
                    'recurso' => 'asignacion_docente',
                    'accion' => 'edicion',
                    'meta' => [
                        'id_asignacion_docente' => $id_asignacion,
                        'old' => [
                            'id_docente' => $oldIdDocente,
                            'docente' => $oldDocenteNom,
                            'id_nombre_materia' => $oldIdNomMat,
                            'clave_materia' => $oldClaveMat,
                            'id_cpmg' => $oldIdCpmg,
                        ],
                        'new' => [
                            'id_docente' => $id_docente,
                            'docente' => $newDocNom,
                            'id_nombre_materia' => $id_nombre_materia,
                            'clave_materia' => $newClaveMat,
                            'id_cpmg' => $finalId ?? null,
                            'nombre_pmg' => $nombre_pmg,
                        ]
                    ],
                ]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Asignación actualizada']);
            break;
        }

        // ------- ELIMINAR (limpia catálogo si queda huérfano) -------
        case 'eliminar': {
            $id_asignacion = (int) ($_POST['id_asignacion'] ?? 0);
            if ($id_asignacion <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
                break;
            }

            $conn->begin_transaction();
            try {
                // Obtener info completa para notificación y catálogo
                $stmt = $conn->prepare("
                    SELECT ad.id_nombre_profesor_materia_grupo AS id_cpmg,
                           ad.id_docente, d.nombre, d.apellido_paterno, COALESCE(d.apellido_materno,'') am,
                           ad.id_nombre_materia AS id_nom_mat, cnm.nombre AS clave_materia
                      FROM asignaciones_docentes ad
                 LEFT JOIN docentes d ON d.id_docente = ad.id_docente
                 LEFT JOIN cat_nombres_materias cnm ON cnm.id_nombre_materia = ad.id_nombre_materia
                     WHERE ad.id_asignacion_docente=? FOR UPDATE
                ");
                $stmt->bind_param('i', $id_asignacion);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$row) {
                    throw new RuntimeException('Asignación no encontrada');
                }

                $idCpmg = (int) $row['id_cpmg'];
                $idDocente = (int) $row['id_docente'];
                $docenteNom = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' ' . ($row['am'] ?? ''));
                $idNomMat = (int) $row['id_nom_mat'];
                $claveMat = (string) ($row['clave_materia'] ?? '');

                // Borrar asignación
                $del = $conn->prepare("DELETE FROM asignaciones_docentes WHERE id_asignacion_docente=?");
                $del->bind_param('i', $id_asignacion);
                $ok = $del->execute();
                $del->close();

                // Limpiar catálogo si quedó sin uso
                deleteCatalogoPMgSiHuerfano($conn, $idCpmg);

                $conn->commit();

                // ---- Notificación ----
                if ($ok) {
                    $actorId = getSecretariaActorIdFromSession();
                    notificar_admin($conn, [
                        'tipo' => 'movimiento',
                        'titulo' => 'Eliminación de asignación de docente',
                        'detalle' => "Se eliminó la asignación de {$docenteNom} en {$claveMat}.",
                        'actor_id' => $actorId,
                        'recurso' => 'asignacion_docente',
                        'accion' => 'eliminacion',
                        'meta' => [
                            'id_asignacion_docente' => $id_asignacion,
                            'id_docente' => $idDocente,
                            'docente' => $docenteNom,
                            'id_nombre_materia' => $idNomMat,
                            'clave_materia' => $claveMat,
                            'id_cpmg' => $idCpmg,
                        ],
                    ]);
                }

                echo json_encode(['status' => 'success', 'message' => 'Asignación eliminada']);
            } catch (Throwable $tx) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $tx->getMessage()]);
            }
            break;
        }

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
    }
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Excepción: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}