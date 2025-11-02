<?php
/**
 * controller_grupos.php (Secretarías)
 * CRUD de grupos con generación de nombre consecutivo (G1, G2, …) por semestre.
 * Permisos: Admin y Secretarías pueden crear/editar/eliminar. Otros roles: solo listar/sugerir.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== Config de errores (apaga en prod) =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== Helpers de respuesta =====
function respond(string $status, ?string $message = null, array $extra = [], int $http = 200): void
{
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($http);
    $payload = array_merge(['status' => $status], $message ? ['message' => $message] : [], $extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function guard_mutation(bool $allowed): void
{
    if (!$allowed) respond('error', 'No autorizado para esta acción', [], 403);
}

// ===== Sesión / permisos =====
if (!isset($_SESSION['rol'])) {
    respond('error', 'No autorizado', [], 403);
}
$rol = mb_strtolower((string) ($_SESSION['rol'] ?? ''), 'UTF-8');
$esAdmin = ($rol === 'admin');
$esSecretaria = in_array($rol, ['secretaria', 'secretarías', 'secretarias', 'secretaría'], true);

// Secretarías SÍ pueden crear/editar/eliminar en GRUPOS
$canMutate = ($esAdmin || $esSecretaria);

// ===== Conexión PDO =====
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    respond('error', 'Error de conexión: ' . $e->getMessage(), [], 500);
}

// ====== Helper: obtener actor_id (id_secretaria) de sesión ======
function get_secretaria_actor_id_from_session(): ?int {
    $roles_secretaria = ['secretaria','secretarías','secretarias','secretaría'];
    $rol = strtolower($_SESSION['rol'] ?? '');
    if (!in_array($rol, $roles_secretaria, true)) return null;

    $candidatas = ['id_secretaria','secretaria_id','iduser','id'];
    $fuentes = [];
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) $fuentes[] = $_SESSION['usuario'];
    $fuentes[] = $_SESSION;

    foreach ($fuentes as $arr) {
        foreach ($candidatas as $k) {
            if (isset($arr[$k]) && (int)$arr[$k] > 0) return (int)$arr[$k];
        }
    }
    return null;
}

// ====== Helper: notificar al admin (PDO) ======
function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    $tipo     = isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento','mensaje'], true) ? $cfg['tipo'] : 'movimiento';
    $titulo   = (string)($cfg['titulo']  ?? '');
    $detalle  = (string)($cfg['detalle'] ?? '');
    $para_rol = 'admin';
    $actor_id = $cfg['actor_id'] ?? null;
    $actor_id = is_numeric($actor_id) ? (int)$actor_id : null;
    $recurso  = (string)($cfg['recurso'] ?? 'grupo');
    $accion   = (string)($cfg['accion']  ?? '');
    $meta     = $cfg['meta'] ?? null;
    if (is_array($meta))      $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
    elseif ($meta !== null)   $meta = (string)$meta;
    $leido    = 0;

    $sql = "INSERT INTO notificaciones (tipo,titulo,detalle,para_rol,actor_id,recurso,accion,meta,leido)
            VALUES (:tipo,:titulo,:detalle,:para_rol,:actor_id,:recurso,:accion,:meta,:leido)";
    $st = $pdo->prepare($sql);
    $st->bindValue(':tipo', $tipo);
    $st->bindValue(':titulo', $titulo);
    $st->bindValue(':detalle', $detalle);
    $st->bindValue(':para_rol', $para_rol);
    $st->bindValue(':actor_id', $actor_id, is_null($actor_id) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $st->bindValue(':recurso', $recurso);
    $st->bindValue(':accion', $accion);
    $st->bindValue(':meta', $meta);
    $st->bindValue(':leido', $leido, PDO::PARAM_INT);
    $st->execute();
}

// ===== Utilidades de esquema =====
function tablaExiste(PDO $pdo, string $tabla): bool
{
    $q = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $q->execute([$tabla]);
    return (bool) $q->fetchColumn();
}
function columnaExiste(PDO $pdo, string $tabla, string $columna): bool
{
    $q = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $q->execute([$tabla, $columna]);
    return (bool) $q->fetchColumn();
}
/**
 * Resuelve la tabla de semestres que expone id_nombre_semestre y nombre(legible).
 * Compatibilidad: cat_nombres_semestre (preferida) o semestres (si trae id_nombre_semestre + nombre/nombre_grado).
 */
function resolverTablaSemestres(PDO $pdo): array
{
    $candidatas = ['cat_nombres_semestre', 'semestres'];
    foreach ($candidatas as $t) {
        if (!tablaExiste($pdo, $t)) continue;
        $colId = 'id_nombre_semestre';
        if (!columnaExiste($pdo, $t, $colId)) continue;
        $nombre1 = columnaExiste($pdo, $t, 'nombre') ? 'nombre' : null;
        $nombre2 = columnaExiste($pdo, $t, 'nombre_grado') ? 'nombre_grado' : null;
        if (!$nombre1 && !$nombre2) continue;
        return ['tabla' => $t, 'col_id' => $colId, 'col_nombre_1' => $nombre1, 'col_nombre_2' => $nombre2];
    }
    throw new RuntimeException("No se encontró tabla de semestres válida.");
}
function exprNombreSemestre(array $meta): string
{
    $parts = [];
    if (!empty($meta['col_nombre_1'])) $parts[] = "NULLIF(TRIM(s.`{$meta['col_nombre_1']}`),'')";
    if (!empty($meta['col_nombre_2'])) $parts[] = "NULLIF(TRIM(s.`{$meta['col_nombre_2']}`),'')";
    return $parts ? "COALESCE(" . implode(", ", $parts) . ")" : "NULL";
}

// ===== Helpers de negocio =====
function obtenerNombreSemestre(PDO $pdo, int $idSem): ?string
{
    $meta = resolverTablaSemestres($pdo);
    $sqlExpr = exprNombreSemestre($meta);
    $chk = $pdo->prepare("SELECT 1 FROM `{$meta['tabla']}` WHERE `{$meta['col_id']}` = ?");
    $chk->execute([$idSem]);
    if (!$chk->fetchColumn()) return null;

    $s = $pdo->prepare("SELECT $sqlExpr AS nombre FROM `{$meta['tabla']}` s WHERE s.`{$meta['col_id']}` = ?");
    $s->execute([$idSem]);
    $nombre = $s->fetchColumn();
    return $nombre !== false ? trim((string) $nombre) : null;
}
function quitarAcentos(string $t): string
{
    return strtr($t, [
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'
    ]);
}
/**
 * Genera el PRIMER consecutivo libre con prefijo por semestre:
 * Ej: "INDS1G1", "INDS1G2"... Si G1 está libre, devuelve G1 aunque exista G2/G3.
 */
function generarNombreGrupoConsecutivo(PDO $pdo, int $idSem): string
{
    $nombreSem = obtenerNombreSemestre($pdo, $idSem);
    if (!$nombreSem) throw new RuntimeException("Semestre inválido (no existe).");

    // Normaliza y toma iniciales + posible dígito final
    $clean = trim(preg_replace('/\s+/u', ' ', str_replace(['-', '_', '/'], ' ', $nombreSem)));
    $stop = ['de','del','la','las','lo','los','y','e','a','al','en','para','por','con'];
    $parts = array_filter(explode(' ', $clean), fn($w) => $w !== '');
    $ini = ''; $num = '';

    if (!empty($parts)) {
        $last = end($parts);
        if (preg_match('/^\d+$/u', $last)) { $num = $last; array_pop($parts); }
    }
    foreach ($parts as $p) {
        $pl = mb_strtolower($p, 'UTF-8');
        if (preg_match('/^\d+$/u', $pl)) continue;
        if (in_array($pl, $stop, true)) continue;
        $pNo = quitarAcentos($p);
        $ini .= mb_strtoupper(mb_substr($pNo, 0, 1, 'UTF-8'), 'UTF-8');
    }
    if ($ini === '') $ini = 'S';
    $prefijo = $ini . $num . 'G';

    // Ocupados para el semestre
    $q = $pdo->prepare("
        SELECT cg.nombre
          FROM grupos g
          INNER JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
         WHERE g.id_nombre_semestre = ?
    ");
    $q->execute([$idSem]);
    $exist = $q->fetchAll(PDO::FETCH_COLUMN);

    $ocupados = [];
    foreach ($exist as $ng) {
        if (preg_match('/^' . preg_quote($prefijo, '/') . '(\d+)$/u', (string)$ng, $m)) {
            $ocupados[(int)$m[1]] = true;
        }
    }
    $n = 1;
    while (isset($ocupados[$n])) $n++;
    return $prefijo . $n;
}
function asegurarCatNombreGrupo(PDO $pdo, string $nombre): int
{
    $sel = $pdo->prepare("SELECT id_nombre_grupo FROM cat_nombres_grupo WHERE nombre = ?");
    $sel->execute([$nombre]);
    $id = $sel->fetchColumn();
    if ($id !== false && $id !== null) return (int)$id;
    $ins = $pdo->prepare("INSERT INTO cat_nombres_grupo (nombre) VALUES (?)");
    $ins->execute([$nombre]);
    return (int)$pdo->lastInsertId();
}
function resolverInfoGrupo(PDO $pdo, int $idGrupo): array
{
    // Devuelve datos del grupo con nombres legibles para detalle de notificación
    $meta = resolverTablaSemestres($pdo);
    $sqlExpr = exprNombreSemestre($meta);
    $resp = $pdo->prepare("
        SELECT g.id_grupo, g.id_nombre_semestre, $sqlExpr AS nombre_semestre,
               g.id_nombre_grupo, cg.nombre AS nombre_grupo
          FROM grupos g
          LEFT JOIN `{$meta['tabla']}` s ON s.`{$meta['col_id']}` = g.id_nombre_semestre
          LEFT JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
         WHERE g.id_grupo = ?
    ");
    $resp->execute([$idGrupo]);
    return (array)$resp->fetch(PDO::FETCH_ASSOC);
}
function obtenerGrupoPorId(PDO $pdo, int $idGrupo): array
{
    return resolverInfoGrupo($pdo, $idGrupo);
}

// ===== Router =====
$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {

        // ------ Sugerir nombre (siempre permitido con sesión) ------
        case 'sugerir_nombre': {
            $idSem = isset($_POST['id_nombre_semestre']) ? (int) $_POST['id_nombre_semestre'] : 0;
            if ($idSem <= 0) respond('error', 'id_nombre_semestre requerido.');
            $sugerido = generarNombreGrupoConsecutivo($pdo, $idSem);
            respond('success', null, ['sugerido' => $sugerido]);
        }

        // ------ Listar (siempre permitido con sesión) ------
        case 'listar': {
            $meta = resolverTablaSemestres($pdo);
            $sqlExpr = exprNombreSemestre($meta);
            $stmt = $pdo->query("
                SELECT g.id_grupo,
                       g.id_nombre_semestre,
                       $sqlExpr AS nombre_semestre,
                       g.id_nombre_grupo,
                       cg.nombre AS nombre_grupo
                  FROM grupos g
                  LEFT JOIN `{$meta['tabla']}` s ON s.`{$meta['col_id']}` = g.id_nombre_semestre
                  LEFT JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
              ORDER BY nombre_semestre, cg.nombre
            ");
            respond('success', null, ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        // ------ Crear (Admin/Secretaría) ------
        case 'crear': {
            guard_mutation($canMutate);

            $idSem = isset($_POST['id_nombre_semestre']) ? (int) $_POST['id_nombre_semestre'] : 0;
            $nombreTxt = trim((string) ($_POST['nombre_grupo'] ?? ''));

            if ($idSem <= 0) respond('error', 'Falta id_nombre_semestre');
            $nomSem = obtenerNombreSemestre($pdo, $idSem);
            if (!$nomSem) respond('error', "El id_nombre_semestre ($idSem) no existe.");

            // Si enviaron un nombre manual, valida duplicados dentro del semestre
            if ($nombreTxt !== '') {
                $dup = $pdo->prepare("
                    SELECT COUNT(*)
                      FROM grupos g
                      INNER JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
                     WHERE g.id_nombre_semestre = ? AND cg.nombre = ?
                ");
                $dup->execute([$idSem, $nombreTxt]);
                if ((int)$dup->fetchColumn() > 0) {
                    respond('error', 'Ese nombre de grupo ya existe en el semestre.');
                }
            } else {
                // Si no enviaron nombre, genera el primer hueco libre
                $nombreTxt = generarNombreGrupoConsecutivo($pdo, $idSem);
            }

            $pdo->beginTransaction();
            $idNombreGrupo = asegurarCatNombreGrupo($pdo, $nombreTxt);

            $ins = $pdo->prepare("INSERT INTO grupos (id_nombre_semestre, id_nombre_grupo) VALUES (?, ?)");
            $ins->execute([$idSem, $idNombreGrupo]);

            $idGrupo = (int) $pdo->lastInsertId();
            $row = obtenerGrupoPorId($pdo, $idGrupo);
            $pdo->commit();

            // ---- Notificación
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo'    => 'movimiento',
                'titulo'  => 'Alta de grupo',
                'detalle' => 'Se creó el grupo ' . ($row['nombre_grupo'] ?? $nombreTxt) . ' en ' . ($row['nombre_semestre'] ?? $nomSem) . '.',
                'actor_id'=> $actorId,
                'recurso' => 'grupo',
                'accion'  => 'alta',
                'meta'    => [
                    'id_grupo' => $idGrupo,
                    'id_nombre_semestre' => $idSem,
                    'id_nombre_grupo' => $idNombreGrupo,
                    'nombre_grupo' => $row['nombre_grupo'] ?? $nombreTxt,
                    'nombre_semestre' => $row['nombre_semestre'] ?? $nomSem
                ],
            ]);

            respond('success', 'Grupo creado', ['grupo' => $row]);
        }

        // ------ Editar (Admin/Secretaría) ------
        case 'editar': {
            guard_mutation($canMutate);

            $idGrupo = isset($_POST['id_grupo']) ? (int) $_POST['id_grupo'] : 0;
            if ($idGrupo <= 0) respond('error', 'Falta id_grupo');

            $nuevoIdSem = isset($_POST['id_nombre_semestre']) ? (int) $_POST['id_nombre_semestre'] : 0;
            $nombreTxt  = trim((string) ($_POST['nombre_grupo'] ?? ''));

            if ($nuevoIdSem <= 0 && $nombreTxt === '') {
                // Nada que cambiar, pero no es error
                $row = obtenerGrupoPorId($pdo, $idGrupo);
                respond('success', 'Sin cambios', ['grupo' => $row]);
            }

            $pdo->beginTransaction();

            // Estado actual
            $cur = $pdo->prepare("SELECT id_nombre_semestre, id_nombre_grupo FROM grupos WHERE id_grupo = ? FOR UPDATE");
            $cur->execute([$idGrupo]);
            $curRow = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$curRow) {
                $pdo->rollBack();
                respond('error', 'Grupo no encontrado.');
            }
            $oldIdSem  = (int)$curRow['id_nombre_semestre'];
            $oldIdNomG = (int)$curRow['id_nombre_grupo'];

            // Si cambia semestre y no dan nombre -> sugerir primer hueco en nuevo semestre
            if ($nuevoIdSem > 0 && $nuevoIdSem !== $oldIdSem && $nombreTxt === '') {
                if (!obtenerNombreSemestre($pdo, $nuevoIdSem)) {
                    $pdo->rollBack();
                    respond('error', 'Semestre inválido.');
                }
                $nombreTxt = generarNombreGrupoConsecutivo($pdo, $nuevoIdSem);
            }

            $finalIdSem = $oldIdSem;
            if ($nuevoIdSem > 0) {
                if (!obtenerNombreSemestre($pdo, $nuevoIdSem)) {
                    $pdo->rollBack();
                    respond('error', 'Semestre inválido.');
                }
                $finalIdSem = $nuevoIdSem;
            }

            // Solo cambia semestre
            if ($nombreTxt === '') {
                $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ? WHERE id_grupo = ?");
                $upd->execute([$finalIdSem, $idGrupo]);
                $pdo->commit();

                $row = obtenerGrupoPorId($pdo, $idGrupo);

                // Notificación (solo cambio de semestre)
                $actorId = get_secretaria_actor_id_from_session();
                notificar_admin_pdo($pdo, [
                    'tipo'    => 'movimiento',
                    'titulo'  => 'Edición de grupo',
                    'detalle' => 'Se movió el grupo ' . ($row['nombre_grupo'] ?? '') . ' a ' . ($row['nombre_semestre'] ?? '') . '.',
                    'actor_id'=> $actorId,
                    'recurso' => 'grupo',
                    'accion'  => 'edicion',
                    'meta'    => [
                        'id_grupo' => $row['id_grupo'] ?? $idGrupo,
                        'id_nombre_semestre' => $row['id_nombre_semestre'] ?? $finalIdSem,
                        'id_nombre_grupo' => $row['id_nombre_grupo'] ?? $oldIdNomG,
                        'nombre_grupo' => $row['nombre_grupo'] ?? '',
                        'nombre_semestre' => $row['nombre_semestre'] ?? ''
                    ],
                ]);

                respond('success', 'Grupo actualizado', ['grupo' => $row]);
            }

            // Validar duplicado de nombre en el semestre destino
            $dup = $pdo->prepare("
                SELECT COUNT(*)
                  FROM grupos g
                  INNER JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
                 WHERE g.id_nombre_semestre = ? AND cg.nombre = ? AND g.id_grupo <> ?
            ");
            $dup->execute([$finalIdSem, $nombreTxt, $idGrupo]);
            if ((int)$dup->fetchColumn() > 0) {
                $pdo->rollBack();
                respond('error', 'Ese nombre ya está en uso en el semestre.');
            }

            // ¿Cuántos usan el catálogo actual?
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE id_nombre_grupo = ?");
            $cnt->execute([$oldIdNomG]);
            $usosOld = (int)$cnt->fetchColumn();

            // ¿Existe catálogo con el nombre deseado?
            $selNuevo = $pdo->prepare("SELECT id_nombre_grupo FROM cat_nombres_grupo WHERE nombre = ?");
            $selNuevo->execute([$nombreTxt]);
            $rowNuevo = $selNuevo->fetch(PDO::FETCH_ASSOC);
            $idNombreExistente = $rowNuevo ? (int)$rowNuevo['id_nombre_grupo'] : 0;

            if ($usosOld === 1) {
                if ($idNombreExistente > 0 && $idNombreExistente !== $oldIdNomG) {
                    // Apuntar al existente y borrar el viejo
                    $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ?, id_nombre_grupo = ? WHERE id_grupo = ?");
                    $upd->execute([$finalIdSem, $idNombreExistente, $idGrupo]);

                    $delCat = $pdo->prepare("DELETE FROM cat_nombres_grupo WHERE id_nombre_grupo = ? LIMIT 1");
                    $delCat->execute([$oldIdNomG]);

                    $finalNombreId = $idNombreExistente;
                } else {
                    // Renombrar en sitio (conserva ID)
                    $updCat = $pdo->prepare("UPDATE cat_nombres_grupo SET nombre = ? WHERE id_nombre_grupo = ?");
                    $updCat->execute([$nombreTxt, $oldIdNomG]);

                    $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ? WHERE id_grupo = ?");
                    $upd->execute([$finalIdSem, $idGrupo]);

                    $finalNombreId = $oldIdNomG;
                }
            } else {
                // Usado por varios -> no renombrar ese ID
                $finalNombreId = $idNombreExistente > 0 ? $idNombreExistente : asegurarCatNombreGrupo($pdo, $nombreTxt);
                $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ?, id_nombre_grupo = ? WHERE id_grupo = ?");
                $upd->execute([$finalIdSem, $finalNombreId, $idGrupo]);
            }

            $pdo->commit();
            $row = obtenerGrupoPorId($pdo, $idGrupo);

            // ---- Notificación (edición con cambio de nombre y/o semestre)
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo'    => 'movimiento',
                'titulo'  => 'Edición de grupo',
                'detalle' => 'Se actualizó el grupo a ' . ($row['nombre_grupo'] ?? $nombreTxt) . ' en ' . ($row['nombre_semestre'] ?? '') . '.',
                'actor_id'=> $actorId,
                'recurso' => 'grupo',
                'accion'  => 'edicion',
                'meta'    => [
                    'id_grupo' => $row['id_grupo'] ?? $idGrupo,
                    'id_nombre_semestre' => $row['id_nombre_semestre'] ?? $finalIdSem,
                    'id_nombre_grupo' => $row['id_nombre_grupo'] ?? $finalNombreId,
                    'nombre_grupo' => $row['nombre_grupo'] ?? $nombreTxt,
                    'nombre_semestre' => $row['nombre_semestre'] ?? ''
                ],
            ]);

            respond('success', 'Grupo actualizado', ['grupo' => $row]);
        }

        // ------ Eliminar (Admin/Secretaría) ------
        case 'eliminar': {
            guard_mutation($canMutate);

            $idGrupo = isset($_POST['id_grupo']) ? (int) $_POST['id_grupo'] : 0;
            if ($idGrupo <= 0) respond('error', 'ID no válido');

            // Obtener info antes de eliminar para detalle/meta
            $infoPrev = resolverInfoGrupo($pdo, $idGrupo);
            if (!$infoPrev) respond('error', 'Grupo no encontrado.');

            $pdo->beginTransaction();

            $sel = $pdo->prepare("SELECT id_nombre_grupo FROM grupos WHERE id_grupo = ? FOR UPDATE");
            $sel->execute([$idGrupo]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                respond('error', 'Grupo no encontrado.');
            }
            $idNomG = (int)$row['id_nombre_grupo'];

            $del = $pdo->prepare("DELETE FROM grupos WHERE id_grupo = ?");
            $del->execute([$idGrupo]);

            // Limpia catálogo si queda huérfano
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE id_nombre_grupo = ?");
            $cnt->execute([$idNomG]);
            if ((int)$cnt->fetchColumn() === 0) {
                $delCat = $pdo->prepare("DELETE FROM cat_nombres_grupo WHERE id_nombre_grupo = ? LIMIT 1");
                $delCat->execute([$idNomG]);
            }

            $pdo->commit();

            // ---- Notificación
            $actorId = get_secretaria_actor_id_from_session();
            notificar_admin_pdo($pdo, [
                'tipo'    => 'movimiento',
                'titulo'  => 'Eliminación de grupo',
                'detalle' => 'Se eliminó el grupo ' . ($infoPrev['nombre_grupo'] ?? '') . ' de ' . ($infoPrev['nombre_semestre'] ?? '') . '.',
                'actor_id'=> $actorId,
                'recurso' => 'grupo',
                'accion'  => 'eliminacion',
                'meta'    => [
                    'id_grupo' => $idGrupo,
                    'id_nombre_semestre' => $infoPrev['id_nombre_semestre'] ?? null,
                    'id_nombre_grupo' => $idNomG,
                    'nombre_grupo' => $infoPrev['nombre_grupo'] ?? null,
                    'nombre_semestre' => $infoPrev['nombre_semestre'] ?? null
                ],
            ]);

            respond('success', 'Grupo eliminado');
        }

        default:
            respond('error', 'Acción no válida', [], 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond('error', $e->getMessage(), [], 400);
}