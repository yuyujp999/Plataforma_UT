<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ======= Permisos =======
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso']);
    exit;
}

// ======= Conexión =======
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => "Error de conexión: " . $e->getMessage()]);
    exit;
}

$accion = $_POST['accion'] ?? '';

// -------- utilidades de esquema ----------
function tablaExiste(PDO $pdo, string $tabla): bool {
    $q = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $q->execute([$tabla]);
    return (bool)$q->fetchColumn();
}
function columnaExiste(PDO $pdo, string $tabla, string $columna): bool {
    $q = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $q->execute([$tabla, $columna]);
    return (bool)$q->fetchColumn();
}
function resolverTablaSemestres(PDO $pdo): array {
    $candidatas = ['cat_nombres_semestre', 'semestres'];
    foreach ($candidatas as $t) {
        if (!tablaExiste($pdo,$t)) continue;
        $colId = 'id_nombre_semestre';
        if (!columnaExiste($pdo,$t,$colId)) continue;
        $nombre1 = columnaExiste($pdo,$t,'nombre') ? 'nombre' : null;
        $nombre2 = columnaExiste($pdo,$t,'nombre_grado') ? 'nombre_grado' : null;
        if (!$nombre1 && !$nombre2) continue;
        return ['tabla'=>$t,'col_id'=>$colId,'col_nombre_1'=>$nombre1,'col_nombre_2'=>$nombre2];
    }
    throw new RuntimeException("No se encontró tabla de semestres válida.");
}
function exprNombreSemestre(array $meta): string {
    $parts=[];
    if (!empty($meta['col_nombre_1'])) $parts[]="NULLIF(TRIM(s.`{$meta['col_nombre_1']}`),'')";
    if (!empty($meta['col_nombre_2'])) $parts[]="NULLIF(TRIM(s.`{$meta['col_nombre_2']}`),'')";
    return $parts ? "COALESCE(".implode(", ",$parts).")" : "NULL";
}

// -------- helpers de negocio ----------
function obtenerNombreSemestre(PDO $pdo, int $idSem): ?string {
    $meta = resolverTablaSemestres($pdo);
    $sqlExpr = exprNombreSemestre($meta);
    $chk = $pdo->prepare("SELECT 1 FROM `{$meta['tabla']}` WHERE `{$meta['col_id']}` = ?");
    $chk->execute([$idSem]);
    if (!$chk->fetchColumn()) return null;
    $s = $pdo->prepare("SELECT $sqlExpr AS nombre FROM `{$meta['tabla']}` s WHERE s.`{$meta['col_id']}` = ?");
    $s->execute([$idSem]);
    $nombre = $s->fetchColumn();
    return $nombre!==false ? trim((string)$nombre) : null;
}
function quitarAcentos(string $t): string {
    return strtr($t, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N','á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}

/**
 * Genera el PRIMER consecutivo libre con prefijo (IS1G1, IS1G2,...).
 * Si G1 está libre, devuelve G1 aunque exista G2 o G3.
 */
function generarNombreGrupoConsecutivo(PDO $pdo, int $idSem): string {
    $nombreSem = obtenerNombreSemestre($pdo,$idSem);
    if (!$nombreSem) throw new RuntimeException("Semestre inválido (no existe).");

    $clean = trim(preg_replace('/\s+/u',' ', str_replace(['-','_','/'],' ',$nombreSem)));
    $stop = ['de','del','la','las','lo','los','y','e','a','al','en','para','por','con'];
    $parts = array_filter(explode(' ',$clean), fn($w)=>$w!=='');
    $ini=''; $num='';

    if (!empty($parts)) {
        $last = end($parts);
        if (preg_match('/^\d+$/u',$last)) { $num=$last; array_pop($parts); }
    }
    foreach ($parts as $p) {
        $pl = mb_strtolower($p,'UTF-8');
        if (preg_match('/^\d+$/u',$pl)) continue;
        if (in_array($pl,$stop,true)) continue;
        $pNo = quitarAcentos($p);
        $ini .= mb_strtoupper(mb_substr($pNo,0,1,'UTF-8'),'UTF-8');
    }
    if ($ini==='') $ini='S';
    $prefijo = $ini.$num.'G';

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
        if (preg_match('/^'.preg_quote($prefijo,'/').'(\d+)$/u', (string)$ng, $m)) {
            $ocupados[(int)$m[1]] = true;
        }
    }
    $n=1;
    while (isset($ocupados[$n])) $n++;
    return $prefijo.$n;
}

function asegurarCatNombreGrupo(PDO $pdo, string $nombre): int {
    $sel = $pdo->prepare("SELECT id_nombre_grupo FROM cat_nombres_grupo WHERE nombre = ?");
    $sel->execute([$nombre]);
    $id = $sel->fetchColumn();
    if ($id!==false && $id!==null) return (int)$id;
    $ins = $pdo->prepare("INSERT INTO cat_nombres_grupo (nombre) VALUES (?)");
    $ins->execute([$nombre]);
    return (int)$pdo->lastInsertId();
}

function obtenerGrupoPorId(PDO $pdo, int $idGrupo): array {
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

// ===== Acciones =====
try {
    switch ($accion) {

        // ---- SUGERIR NOMBRE (para el modal) ----
        case 'sugerir_nombre': {
            $idSem = isset($_POST['id_nombre_semestre']) ? (int)$_POST['id_nombre_semestre'] : 0;
            if ($idSem<=0) throw new RuntimeException('id_nombre_semestre requerido.');
            $sugerido = generarNombreGrupoConsecutivo($pdo,$idSem);
            echo json_encode(['status'=>'success','sugerido'=>$sugerido]);
            break;
        }

        // ---- CREAR ----
        case 'crear': {
            $idSem = isset($_POST['id_nombre_semestre']) ? (int)$_POST['id_nombre_semestre'] : 0;
            $nombreTxt = trim((string)($_POST['nombre_grupo'] ?? ''));
            if ($idSem<=0) throw new RuntimeException('Falta id_nombre_semestre.');

            if (!obtenerNombreSemestre($pdo,$idSem)) {
                throw new RuntimeException("El id_nombre_semestre ($idSem) no existe.");
            }

            // Si enviaron un nombre manual, validar que NO exista ya en ese semestre
            if ($nombreTxt !== '') {
                $dup = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM grupos g
                    INNER JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
                    WHERE g.id_nombre_semestre = ? AND cg.nombre = ?
                ");
                $dup->execute([$idSem, $nombreTxt]);
                if ((int)$dup->fetchColumn() > 0) {
                    throw new RuntimeException("Ese nombre de grupo ya existe en el semestre.");
                }
            } else {
                // Si no enviaron, usar el primer hueco libre
                $nombreTxt = generarNombreGrupoConsecutivo($pdo,$idSem);
            }

            $pdo->beginTransaction();

            $idNombreGrupo = asegurarCatNombreGrupo($pdo, $nombreTxt);
            $ins = $pdo->prepare("INSERT INTO grupos (id_nombre_semestre, id_nombre_grupo) VALUES (?, ?)");
            $ins->execute([$idSem, $idNombreGrupo]);

            $idGrupo = (int)$pdo->lastInsertId();
            $row = obtenerGrupoPorId($pdo, $idGrupo);

            $pdo->commit();
            echo json_encode(['status'=>'success','message'=>'Grupo creado','grupo'=>$row]);
            break;
        }

        // ---- EDITAR (sin huérfanos, conserva ID si procede) ----
        case 'editar': {
            $idGrupo = isset($_POST['id_grupo']) ? (int)$_POST['id_grupo'] : 0;
            if ($idGrupo<=0) throw new RuntimeException('Falta id_grupo.');

            $nuevoIdSem = isset($_POST['id_nombre_semestre']) ? (int)$_POST['id_nombre_semestre'] : 0;
            $nombreTxt  = trim((string)($_POST['nombre_grupo'] ?? ''));

            if ($nuevoIdSem<=0 && $nombreTxt==='') {
                echo json_encode(['status'=>'success','message'=>'Sin cambios']); break;
            }

            $pdo->beginTransaction();

            // Estado actual
            $cur = $pdo->prepare("SELECT id_nombre_semestre, id_nombre_grupo FROM grupos WHERE id_grupo = ? FOR UPDATE");
            $cur->execute([$idGrupo]);
            $curRow = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$curRow) throw new RuntimeException('Grupo no encontrado.');
            $oldIdSem  = (int)$curRow['id_nombre_semestre'];
            $oldIdNomG = (int)$curRow['id_nombre_grupo'];

            // Si cambia semestre y no dan nombre -> sugerir primer hueco del nuevo semestre
            if ($nuevoIdSem>0 && $nuevoIdSem !== $oldIdSem && $nombreTxt==='') {
                if (!obtenerNombreSemestre($pdo,$nuevoIdSem)) throw new RuntimeException("Semestre inválido.");
                $nombreTxt = generarNombreGrupoConsecutivo($pdo,$nuevoIdSem);
            }

            $finalIdSem = $oldIdSem;
            if ($nuevoIdSem>0) {
                if (!obtenerNombreSemestre($pdo,$nuevoIdSem)) throw new RuntimeException("Semestre inválido.");
                $finalIdSem = $nuevoIdSem;
            }

            if ($nombreTxt==='') {
                $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ? WHERE id_grupo = ?");
                $upd->execute([$finalIdSem, $idGrupo]);
                $pdo->commit();
                $row = obtenerGrupoPorId($pdo,$idGrupo);
                echo json_encode(['status'=>'success','message'=>'Grupo actualizado','grupo'=>$row]);
                break;
            }

            // Si dan nombre explícito, validar que no exista ya en ese semestre (otro grupo)
            $dup = $pdo->prepare("
                SELECT COUNT(*)
                FROM grupos g
                INNER JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
                WHERE g.id_nombre_semestre = ? AND cg.nombre = ? AND g.id_grupo <> ?
            ");
            $dup->execute([$finalIdSem, $nombreTxt, $idGrupo]);
            if ((int)$dup->fetchColumn() > 0) {
                throw new RuntimeException("Ese nombre ya está en uso en el semestre.");
            }

            // ¿Cuántos usan el catálogo actual?
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE id_nombre_grupo = ?");
            $cnt->execute([$oldIdNomG]);
            $usosOld = (int)$cnt->fetchColumn();

            // ¿Existe un catálogo con el nombre deseado?
            $selNuevo = $pdo->prepare("SELECT id_nombre_grupo FROM cat_nombres_grupo WHERE nombre = ?");
            $selNuevo->execute([$nombreTxt]);
            $rowNuevo = $selNuevo->fetch(PDO::FETCH_ASSOC);
            $idNombreExistente = $rowNuevo ? (int)$rowNuevo['id_nombre_grupo'] : 0;

            if ($usosOld === 1) {
                if ($idNombreExistente>0 && $idNombreExistente !== $oldIdNomG) {
                    // Apuntar al existente y borrar el viejo
                    $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ?, id_nombre_grupo = ? WHERE id_grupo = ?");
                    $upd->execute([$finalIdSem, $idNombreExistente, $idGrupo]);
                    $delCat = $pdo->prepare("DELETE FROM cat_nombres_grupo WHERE id_nombre_grupo = ? LIMIT 1");
                    $delCat->execute([$oldIdNomG]);
                    $finalNombreId = $idNombreExistente;
                } else {
                    // Renombrar en sitio el catálogo (conserva ID)
                    $updCat = $pdo->prepare("UPDATE cat_nombres_grupo SET nombre = ? WHERE id_nombre_grupo = ?");
                    $updCat->execute([$nombreTxt, $oldIdNomG]);
                    $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ? WHERE id_grupo = ?");
                    $upd->execute([$finalIdSem, $idGrupo]);
                    $finalNombreId = $oldIdNomG;
                }
            } else {
                // Usado por varios -> no renombramos ese id
                $finalNombreId = $idNombreExistente > 0 ? $idNombreExistente : asegurarCatNombreGrupo($pdo,$nombreTxt);
                $upd = $pdo->prepare("UPDATE grupos SET id_nombre_semestre = ?, id_nombre_grupo = ? WHERE id_grupo = ?");
                $upd->execute([$finalIdSem, $finalNombreId, $idGrupo]);
            }

            $pdo->commit();
            $row = obtenerGrupoPorId($pdo,$idGrupo);
            echo json_encode(['status'=>'success','message'=>'Grupo actualizado','grupo'=>$row]);
            break;
        }

        // ---- ELIMINAR (limpia catálogo si queda huérfano) ----
        case 'eliminar': {
            $idGrupo = isset($_POST['id_grupo']) ? (int)$_POST['id_grupo'] : 0;
            if ($idGrupo<=0) throw new RuntimeException('ID no válido.');

            $pdo->beginTransaction();
            $sel = $pdo->prepare("SELECT id_nombre_grupo FROM grupos WHERE id_grupo = ? FOR UPDATE");
            $sel->execute([$idGrupo]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Grupo no encontrado.');
            $idNomG = (int)$row['id_nombre_grupo'];

            $del = $pdo->prepare("DELETE FROM grupos WHERE id_grupo = ?");
            $del->execute([$idGrupo]);

            $cnt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE id_nombre_grupo = ?");
            $cnt->execute([$idNomG]);
            if ((int)$cnt->fetchColumn()===0) {
                $delCat = $pdo->prepare("DELETE FROM cat_nombres_grupo WHERE id_nombre_grupo = ? LIMIT 1");
                $delCat->execute([$idNomG]);
            }

            $pdo->commit();
            echo json_encode(['status'=>'success','message'=>'Grupo eliminado']);
            break;
        }

        // ---- LISTAR ----
        case 'listar': {
            $meta = resolverTablaSemestres($pdo);
            $sqlExpr = exprNombreSemestre($meta);
            $stmt = $pdo->query("
                SELECT g.id_grupo, g.id_nombre_semestre, $sqlExpr AS nombre_semestre,
                       g.id_nombre_grupo, cg.nombre AS nombre_grupo
                FROM grupos g
                LEFT JOIN `{$meta['tabla']}` s ON s.`{$meta['col_id']}` = g.id_nombre_semestre
                LEFT JOIN cat_nombres_grupo cg ON cg.id_nombre_grupo = g.id_nombre_grupo
                ORDER BY nombre_semestre, cg.nombre
            ");
            echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        default:
            echo json_encode(['status'=>'error','message'=>'Acción no válida']);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}