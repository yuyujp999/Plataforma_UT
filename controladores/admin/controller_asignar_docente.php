<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/conexion.php'; // Debe exponer $conn (mysqli)

/* ======================= Helpers ======================= */

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

            // Estado actual (id del catálogo PMg actual)
            $stmt = $conn->prepare("SELECT id_nombre_profesor_materia_grupo FROM asignaciones_docentes WHERE id_asignacion_docente=? FOR UPDATE");
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

            // ¿Existe un catálogo con el nombre objetivo?
            $idExistente = getIdCatalogoPMg($conn, $nombre_pmg);

            // ¿Cuántos usan el catálogo viejo?
            $usosViejo = countUsosCatalogoPMg($conn, $oldIdCpmg);

            try {
                if ($usosViejo === 1) {
                    // Solo lo usa esta asignación
                    if ($idExistente !== null && $idExistente !== $oldIdCpmg) {
                        // El nombre deseado ya existe con otro ID -> apuntar a ese y borrar el viejo
                        $upd = $conn->prepare("UPDATE asignaciones_docentes SET id_docente=?, id_nombre_materia=?, id_nombre_profesor_materia_grupo=? WHERE id_asignacion_docente=?");
                        $upd->bind_param('iiii', $id_docente, $id_nombre_materia, $idExistente, $id_asignacion);
                        $upd->execute();
                        $upd->close();

                        // Borra viejo si queda huérfano
                        deleteCatalogoPMgSiHuerfano($conn, $oldIdCpmg);
                    } else {
                        // Renombrar en sitio (conserva el mismo ID; no sube el auto_increment)
                        $updCat = $conn->prepare("UPDATE cat_nombre_profesor_materia_grupo SET nombre=? WHERE id_nombre_profesor_materia_grupo=?");
                        $updCat->bind_param('si', $nombre_pmg, $oldIdCpmg);
                        $updCat->execute();
                        $updCat->close();

                        // Actualiza otras columnas
                        $upd = $conn->prepare("UPDATE asignaciones_docentes SET id_docente=?, id_nombre_materia=? WHERE id_asignacion_docente=?");
                        $upd->bind_param('iii', $id_docente, $id_nombre_materia, $id_asignacion);
                        $upd->execute();
                        $upd->close();
                    }
                } else {
                    // Lo usan varios -> no renombramos esa PK
                    $finalId = $idExistente !== null ? $idExistente : createCatalogoPMg($conn, $nombre_pmg);

                    $upd = $conn->prepare("UPDATE asignaciones_docentes SET id_docente=?, id_nombre_materia=?, id_nombre_profesor_materia_grupo=? WHERE id_asignacion_docente=?");
                    $upd->bind_param('iiii', $id_docente, $id_nombre_materia, $finalId, $id_asignacion);
                    $upd->execute();
                    $upd->close();
                }

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Asignación actualizada']);
            } catch (Throwable $tx) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $tx->getMessage()]);
            }

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
                // Obtener id del catálogo asociado
                $stmt = $conn->prepare("SELECT id_nombre_profesor_materia_grupo FROM asignaciones_docentes WHERE id_asignacion_docente=? FOR UPDATE");
                $stmt->bind_param('i', $id_asignacion);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$row) {
                    throw new RuntimeException('Asignación no encontrada');
                }
                $idCpmg = (int) $row['id_nombre_profesor_materia_grupo'];

                // Borrar asignación
                $del = $conn->prepare("DELETE FROM asignaciones_docentes WHERE id_asignacion_docente=?");
                $del->bind_param('i', $id_asignacion);
                $del->execute();
                $del->close();

                // Limpiar catálogo si quedó sin uso
                deleteCatalogoPMgSiHuerfano($conn, $idCpmg);

                $conn->commit();
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