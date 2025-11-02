<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- Sesión ---
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// --- Conexión PDO ---
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

/* ===========================
   Helpers de Notificación
   =========================== */

/**
 * Devuelve el id de secretaría desde la sesión para usarlo como actor_id.
 * Busca en $_SESSION['usuario'] y en la raíz de la sesión varias llaves comunes.
 */
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

/**
 * Inserta una notificación dirigida al admin (silenciosa si falla).
 * Tabla esperada: notificaciones(tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido, created_at)
 */
function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    $tipo = isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento', 'mensaje'], true) ? $cfg['tipo'] : 'movimiento';
    $titulo = (string) ($cfg['titulo'] ?? '');
    $detalle = (string) ($cfg['detalle'] ?? '');
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $actor_id = is_numeric($actor_id) ? (int) $actor_id : null;
    $recurso = (string) ($cfg['recurso'] ?? 'asignacion_materia');
    $accion = (string) ($cfg['accion'] ?? '');
    $meta = $cfg['meta'] ?? null;
    if (is_array($meta))
        $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
    elseif ($meta !== null)
        $meta = (string) $meta;

    try {
        $st = $pdo->prepare("
            INSERT INTO notificaciones (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido)
            VALUES (:tipo,:titulo,:detalle,:para_rol,:actor_id,:recurso,:accion,:meta,0)
        ");
        $st->bindValue(':tipo', $tipo);
        $st->bindValue(':titulo', $titulo);
        $st->bindValue(':detalle', $detalle);
        $st->bindValue(':para_rol', $para_rol);
        $st->bindValue(':actor_id', $actor_id, is_null($actor_id) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $st->bindValue(':recurso', $recurso);
        $st->bindValue(':accion', $accion);
        $st->bindValue(':meta', $meta);
        $st->execute();
    } catch (Throwable $e) {
        // No romper el flujo si falla la notificación
    }
}

/* ===========================
   Helpers propios
   =========================== */

function normalize_text(string $t): string
{
    // Quitar acentos, todo mayúsculas, solo A-Z0-9 y guión
    $t = iconv('UTF-8', 'ASCII//TRANSLIT', $t);
    $t = strtoupper($t);
    $t = preg_replace('/[^A-Z0-9\-]/', '', $t);
    return $t;
}

/**
 * Regla sugerida:
 * primeras 4 letras de la materia (sin espacios/acentos) + '-' + grupo (normalizado).
 * Ej: "Comunicación Oral y Escrita" + "IS1G2" => "COMU-IS1G2"
 */
function build_clave(string $nombreMateria, string $nombreGrupo): string
{
    $mat = normalize_text(str_replace(' ', '', $nombreMateria));
    $grp = normalize_text($nombreGrupo);
    $base = substr($mat, 0, 4);
    return ($base && $grp) ? "{$base}-{$grp}" : '';
}

/** Devuelve nombre de materia (tabla materias) o null */
function get_nombre_materia(PDO $pdo, int $id_materia): ?string
{
    $q = $pdo->prepare("SELECT nombre_materia FROM materias WHERE id_materia = ?");
    $q->execute([$id_materia]);
    $n = $q->fetchColumn();
    return $n !== false ? (string) $n : null;
}

/** Devuelve nombre de grupo (tabla cat_nombres_grupo) o null */
function get_nombre_grupo(PDO $pdo, int $id_nombre_grupo): ?string
{
    $q = $pdo->prepare("SELECT nombre FROM cat_nombres_grupo WHERE id_nombre_grupo = ?");
    $q->execute([$id_nombre_grupo]);
    $n = $q->fetchColumn();
    return $n !== false ? (string) $n : null;
}

/** Devuelve id del catálogo para nombre exacto (si existe) */
function get_id_catalogo_clave(PDO $pdo, string $clave): ?int
{
    $sel = $pdo->prepare("SELECT id_nombre_materia FROM cat_nombres_materias WHERE nombre = ?");
    $sel->execute([$clave]);
    $id = $sel->fetchColumn();
    return ($id !== false && $id !== null) ? (int) $id : null;
}

/** Crea en catálogo y devuelve ID */
function create_catalogo_clave(PDO $pdo, string $clave): int
{
    $ins = $pdo->prepare("INSERT INTO cat_nombres_materias (nombre) VALUES (?)");
    $ins->execute([$clave]);
    return (int) $pdo->lastInsertId();
}

/** ¿Cuántas asignaciones usan ese id_nombre_materia? */
function count_usos_catalogo(PDO $pdo, int $id_nombre_materia): int
{
    $c = $pdo->prepare("SELECT COUNT(*) FROM asignar_materias WHERE id_nombre_materia = ?");
    $c->execute([$id_nombre_materia]);
    return (int) $c->fetchColumn();
}

/** Borra del catálogo si nadie más lo usa */
function delete_catalogo_si_huerfano(PDO $pdo, int $id_nombre_materia): void
{
    $cnt = count_usos_catalogo($pdo, $id_nombre_materia);
    if ($cnt === 0) {
        $del = $pdo->prepare("DELETE FROM cat_nombres_materias WHERE id_nombre_materia = ? LIMIT 1");
        $del->execute([$id_nombre_materia]);
    }
}

/* ===========================
   Router
   =========================== */

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {

        /* ========== AGREGAR ========== */
        case 'agregar': {
            $id_materia = (int) ($_POST['id_materia'] ?? 0);
            $id_nombre_grupo_int = (int) ($_POST['id_nombre_grupo_int'] ?? 0);
            $clave_generada = trim((string) ($_POST['clave_generada'] ?? ''));

            if ($id_materia <= 0 || $id_nombre_grupo_int <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
                exit;
            }

            $nombreMateria = get_nombre_materia($pdo, $id_materia);
            if (!$nombreMateria) {
                echo json_encode(['status' => 'error', 'message' => 'Materia no encontrada']);
                exit;
            }
            $nombreGrupo = get_nombre_grupo($pdo, $id_nombre_grupo_int);
            if (!$nombreGrupo) {
                echo json_encode(['status' => 'error', 'message' => 'Grupo no encontrado']);
                exit;
            }

            // Si no traen clave, la generamos; si la traen, normalizamos
            $clave = $clave_generada === '' ? build_clave($nombreMateria, $nombreGrupo)
                : normalize_text($clave_generada);
            if ($clave === '') {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo generar la clave']);
                exit;
            }

            // Evitar duplicados (misma materia + mismo grupo)
            $dup = $pdo->prepare("
                SELECT COUNT(*) 
                FROM asignar_materias 
                WHERE id_materia = ? AND id_nombre_grupo_int = ?
            ");
            $dup->execute([$id_materia, $id_nombre_grupo_int]);
            if ((int) $dup->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'La materia ya está asignada a ese grupo.']);
                exit;
            }

            // Reutiliza id si la clave ya existe; si no, crea
            $id_nombre_materia = get_id_catalogo_clave($pdo, $clave);
            if ($id_nombre_materia === null) {
                $id_nombre_materia = create_catalogo_clave($pdo, $clave);
            }

            $ins = $pdo->prepare("
                INSERT INTO asignar_materias (id_materia, id_nombre_grupo_int, id_nombre_materia)
                VALUES (?, ?, ?)
            ");
            $ins->execute([$id_materia, $id_nombre_grupo_int, $id_nombre_materia]);
            $nuevoId = (int) $pdo->lastInsertId();

            // === Notificación ===
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Asignación de materia a grupo',
                'detalle' => 'Se asignó "' . $nombreMateria . '" al grupo ' . $nombreGrupo . ' (clave ' . $clave . ').',
                'actor_id' => $actorId,
                'recurso' => 'asignacion_materia',
                'accion' => 'alta',
                'meta' => [
                    'id_asignacion' => $nuevoId,
                    'id_materia' => $id_materia,
                    'nombre_materia' => $nombreMateria,
                    'id_nombre_grupo_int' => $id_nombre_grupo_int,
                    'nombre_grupo' => $nombreGrupo,
                    'id_nombre_materia' => $id_nombre_materia,
                    'clave' => $clave
                ],
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Asignación agregada correctamente',
                'payload' => [
                    'id_asignacion' => $nuevoId,
                    'id_materia' => $id_materia,
                    'id_nombre_grupo_int' => $id_nombre_grupo_int,
                    'id_nombre_materia' => $id_nombre_materia,
                    'clave' => $clave
                ]
            ]);
            break;
        }

        /* ========== EDITAR (sin huérfanos) ========== */
        case 'editar': {
            $id_asignacion = (int) ($_POST['id_asignacion'] ?? 0);
            $id_materia = (int) ($_POST['id_materia'] ?? 0);
            $id_nombre_grupo_int = (int) ($_POST['id_nombre_grupo_int'] ?? 0);
            $clave_generada = trim((string) ($_POST['clave_generada'] ?? ''));

            if ($id_asignacion <= 0 || $id_materia <= 0 || $id_nombre_grupo_int <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
                exit;
            }

            // Estado actual (trae también materia/grupo previos para la notificación)
            $cur = $pdo->prepare("
                SELECT id_materia AS old_id_materia, id_nombre_grupo_int AS old_id_grupo, id_nombre_materia AS old_id_nombre_materia
                FROM asignar_materias
                WHERE id_asignacion = ?
            ");
            $cur->execute([$id_asignacion]);
            $rowCur = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$rowCur) {
                echo json_encode(['status' => 'error', 'message' => 'Asignación no encontrada']);
                exit;
            }
            $oldIdNombreMateria = (int) $rowCur['old_id_nombre_materia'];
            $oldIdMateria = (int) $rowCur['old_id_materia'];
            $oldIdGrupo = (int) $rowCur['old_id_grupo'];

            $oldNombreMateria = $oldIdMateria ? get_nombre_materia($pdo, $oldIdMateria) : null;
            $oldNombreGrupo = $oldIdGrupo ? get_nombre_grupo($pdo, $oldIdGrupo) : null;

            // Nombres nuevos
            $nombreMateria = get_nombre_materia($pdo, $id_materia);
            if (!$nombreMateria) {
                echo json_encode(['status' => 'error', 'message' => 'Materia no encontrada']);
                exit;
            }
            $nombreGrupo = get_nombre_grupo($pdo, $id_nombre_grupo_int);
            if (!$nombreGrupo) {
                echo json_encode(['status' => 'error', 'message' => 'Grupo no encontrado']);
                exit;
            }

            // Clave nueva objetivo
            $claveNueva = $clave_generada === '' ? build_clave($nombreMateria, $nombreGrupo)
                : normalize_text($clave_generada);
            if ($claveNueva === '') {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo generar la clave']);
                exit;
            }

            // Evitar duplicados (misma materia + grupo), excluyendo esta asignación
            $dup = $pdo->prepare("
                SELECT COUNT(*) 
                FROM asignar_materias 
                WHERE id_materia = ? AND id_nombre_grupo_int = ? AND id_asignacion <> ?
            ");
            $dup->execute([$id_materia, $id_nombre_grupo_int, $id_asignacion]);
            if ((int) $dup->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'La materia ya está asignada a ese grupo.']);
                exit;
            }

            // Clave anterior (para notificación), si la hay
            $oldClave = null;
            if ($oldIdNombreMateria > 0) {
                $getClave = $pdo->prepare("SELECT nombre FROM cat_nombres_materias WHERE id_nombre_materia = ?");
                $getClave->execute([$oldIdNombreMateria]);
                $oldClave = $getClave->fetchColumn() ?: null;
            }

            // --- Lógica sin huérfanos ---
            $pdo->beginTransaction();

            try {
                // ¿Existe ya un catálogo con la clave nueva?
                $idExistente = get_id_catalogo_clave($pdo, $claveNueva);

                // ¿Cuántos usan el catálogo viejo?
                $usosViejo = count_usos_catalogo($pdo, $oldIdNombreMateria);

                if ($usosViejo === 1) {
                    // Solo lo usa esta asignación -> intentamos conservar el mismo ID
                    if ($idExistente !== null && $idExistente !== $oldIdNombreMateria) {
                        // La clave deseada ya existe con otro ID -> apuntar a ese y borrar el viejo
                        $upd = $pdo->prepare("
                            UPDATE asignar_materias
                               SET id_materia = ?, id_nombre_grupo_int = ?, id_nombre_materia = ?
                             WHERE id_asignacion = ?
                        ");
                        $upd->execute([$id_materia, $id_nombre_grupo_int, $idExistente, $id_asignacion]);

                        // viejo huérfano -> eliminar
                        delete_catalogo_si_huerfano($pdo, $oldIdNombreMateria);
                        $finalIdNombre = $idExistente;

                    } else {
                        // Renombrar en sitio el catálogo (misma PK)
                        $updCat = $pdo->prepare("UPDATE cat_nombres_materias SET nombre = ? WHERE id_nombre_materia = ?");
                        $updCat->execute([$claveNueva, $oldIdNombreMateria]);

                        // Actualizar FK normales
                        $upd = $pdo->prepare("
                            UPDATE asignar_materias
                               SET id_materia = ?, id_nombre_grupo_int = ?
                             WHERE id_asignacion = ?
                        ");
                        $upd->execute([$id_materia, $id_nombre_grupo_int, $id_asignacion]);

                        $finalIdNombre = $oldIdNombreMateria;
                    }
                } else {
                    // El catálogo actual lo usan varios -> no lo podemos renombrar
                    if ($idExistente !== null) {
                        $finalIdNombre = $idExistente;
                    } else {
                        $finalIdNombre = create_catalogo_clave($pdo, $claveNueva);
                    }

                    $upd = $pdo->prepare("
                        UPDATE asignar_materias
                           SET id_materia = ?, id_nombre_grupo_int = ?, id_nombre_materia = ?
                         WHERE id_asignacion = ?
                    ");
                    $upd->execute([$id_materia, $id_nombre_grupo_int, $finalIdNombre, $id_asignacion]);
                }

                $pdo->commit();

                // === Notificación ===
                $actorId = get_secretaria_actor_id_from_session();
                $detalle = 'Se actualizó la asignación: ';
                $detalle .= ($oldNombreMateria && $oldNombreGrupo) ? ('"' . $oldNombreMateria . '" → "' . $nombreMateria . '", ' . $oldNombreGrupo . ' → ' . $nombreGrupo) : ('"' . $nombreMateria . '" / ' . $nombreGrupo);
                $detalle .= ' (clave ' . $claveNueva . ').';

                notificar_admin_pdo($pdo, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Edición de asignación de materia',
                    'detalle' => $detalle,
                    'actor_id' => $actorId,
                    'recurso' => 'asignacion_materia',
                    'accion' => 'edicion',
                    'meta' => [
                        'id_asignacion' => $id_asignacion,
                        'old' => [
                            'id_materia' => $oldIdMateria,
                            'nombre_materia' => $oldNombreMateria,
                            'id_nombre_grupo_int' => $oldIdGrupo,
                            'nombre_grupo' => $oldNombreGrupo,
                            'id_nombre_materia' => $oldIdNombreMateria,
                            'clave' => $oldClave
                        ],
                        'new' => [
                            'id_materia' => $id_materia,
                            'nombre_materia' => $nombreMateria,
                            'id_nombre_grupo_int' => $id_nombre_grupo_int,
                            'nombre_grupo' => $nombreGrupo,
                            'id_nombre_materia' => $finalIdNombre,
                            'clave' => $claveNueva
                        ]
                    ],
                ]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Asignación actualizada correctamente',
                    'payload' => [
                        'id_asignacion' => $id_asignacion,
                        'id_materia' => $id_materia,
                        'id_nombre_grupo_int' => $id_nombre_grupo_int,
                        'id_nombre_materia' => $finalIdNombre,
                        'clave' => $claveNueva
                    ]
                ]);
            } catch (Throwable $ex) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $ex->getMessage()]);
            }

            break;
        }

        /* ========== ELIMINAR (limpia catálogo si queda huérfano) ========== */
        case 'eliminar': {
            $id_asignacion = (int) ($_POST['id_asignacion'] ?? 0);
            if ($id_asignacion <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID no válido']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                // Obtener info completa para notificación y catálogo
                $sel = $pdo->prepare("
                    SELECT am.id_nombre_materia,
                           am.id_materia,
                           m.nombre_materia,
                           am.id_nombre_grupo_int,
                           g.nombre AS nombre_grupo,
                           cnm.nombre AS clave
                      FROM asignar_materias am
                      LEFT JOIN materias m ON m.id_materia = am.id_materia
                      LEFT JOIN cat_nombres_grupo g ON g.id_nombre_grupo = am.id_nombre_grupo_int
                      LEFT JOIN cat_nombres_materias cnm ON cnm.id_nombre_materia = am.id_nombre_materia
                     WHERE am.id_asignacion = ?
                     FOR UPDATE
                ");
                $sel->execute([$id_asignacion]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    throw new RuntimeException('Asignación no encontrada');
                }

                $idCat = (int) $row['id_nombre_materia'];
                $idMateria = (int) $row['id_materia'];
                $nomMateria = (string) ($row['nombre_materia'] ?? '');
                $idGrupo = (int) $row['id_nombre_grupo_int'];
                $nomGrupo = (string) ($row['nombre_grupo'] ?? '');
                $clave = (string) ($row['clave'] ?? '');

                // Borrar asignación
                $del = $pdo->prepare("DELETE FROM asignar_materias WHERE id_asignacion = ?");
                $del->execute([$id_asignacion]);

                // Limpiar catálogo si quedó huérfano
                delete_catalogo_si_huerfano($pdo, $idCat);

                $pdo->commit();

                // === Notificación ===
                $actorId = get_secretaria_actor_id_from_session();
                notificar_admin_pdo($pdo, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Eliminación de asignación de materia',
                    'detalle' => 'Se eliminó la asignación de "' . $nomMateria . '" en el grupo ' . $nomGrupo . ' (clave ' . $clave . ').',
                    'actor_id' => $actorId,
                    'recurso' => 'asignacion_materia',
                    'accion' => 'eliminacion',
                    'meta' => [
                        'id_asignacion' => $id_asignacion,
                        'id_materia' => $idMateria,
                        'nombre_materia' => $nomMateria,
                        'id_nombre_grupo_int' => $idGrupo,
                        'nombre_grupo' => $nomGrupo,
                        'id_nombre_materia' => $idCat,
                        'clave' => $clave
                    ],
                ]);

                echo json_encode(['status' => 'success', 'message' => 'Asignación eliminada correctamente']);
            } catch (Throwable $ex) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $ex->getMessage()]);
            }
            break;
        }

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida']);
    }
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Excepción: ' . $e->getMessage()]);
}