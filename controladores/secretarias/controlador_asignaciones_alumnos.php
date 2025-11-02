<?php
session_start();

if (!isset($_SESSION['rol'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ======================= Helpers: Notificaciones ======================= */

/**
 * Obtiene actor_id priorizando id_secretaria desde la sesión (para mostrar “POR:”).
 * Revisa múltiples claves comúnmente usadas.
 */
function getSecretariaActorIdFromSession(): ?int
{
    $roles_secretaria = ['secretaria', 'secretarías', 'secretarias', 'secretaría'];
    $rol = strtolower((string) ($_SESSION['rol'] ?? ''));
    if (!in_array($rol, $roles_secretaria, true)) {
        return null; // Solo etiquetamos actor_id para secretarías
    }

    // Candidatos donde puede venir el id
    $candidatos = [];
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']))
        $candidatos[] = $_SESSION['usuario'];
    $candidatos[] = $_SESSION;

    $claves = [
        'id_secretaria',
        'secretaria_id',
        'idSecretaria',
        'idSec',
        'id',
        'iduser',
        'user_id'
    ];
    foreach ($candidatos as $arr) {
        foreach ($claves as $k) {
            if (isset($arr[$k]) && (int) $arr[$k] > 0) {
                return (int) $arr[$k];
            }
        }
    }
    return null;
}

/**
 * Inserta notificación dirigida a admin. Silencioso si falla para no romper flujo.
 * Tabla sugerida:
 *  CREATE TABLE IF NOT EXISTS notificaciones (
 *    id INT AUTO_INCREMENT PRIMARY KEY,
 *    tipo ENUM('movimiento','mensaje') NOT NULL DEFAULT 'movimiento',
 *    titulo VARCHAR(120) NOT NULL,
 *    detalle TEXT NULL,
 *    para_rol ENUM('admin','secretaria') NOT NULL DEFAULT 'admin',
 *    actor_id INT NULL,
 *    recurso VARCHAR(50) NULL,
 *    accion  VARCHAR(30) NULL,
 *    meta JSON NULL,
 *    leido TINYINT(1) NOT NULL DEFAULT 0,
 *    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 *  );
 */
function notificar_admin_pdo(PDO $pdo, array $cfg): void
{
    try {
        $tipo = (isset($cfg['tipo']) && in_array($cfg['tipo'], ['movimiento', 'mensaje'], true)) ? $cfg['tipo'] : 'movimiento';
        $titulo = (string) ($cfg['titulo'] ?? '');
        $detalle = (string) ($cfg['detalle'] ?? '');
        $para_rol = 'admin';
        $actor_id = $cfg['actor_id'] ?? null;
        $recurso = $cfg['recurso'] ?? null;
        $accion = $cfg['accion'] ?? null;
        $meta = $cfg['meta'] ?? null;
        if (is_array($meta))
            $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);

        $st = $pdo->prepare("
            INSERT INTO notificaciones (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido)
            VALUES (:tipo, :titulo, :detalle, :para_rol, :actor_id, :recurso, :accion, :meta, 0)
        ");
        $st->execute([
            ':tipo' => $tipo,
            ':titulo' => $titulo,
            ':detalle' => $detalle,
            ':para_rol' => $para_rol,
            ':actor_id' => $actor_id,
            ':recurso' => $recurso,
            ':accion' => $accion,
            ':meta' => $meta,
        ]);
    } catch (Throwable $e) {
        // No romper
    }
}

/* ======================= Helpers negocio ======================= */

function fetchAllByIds(PDO $pdo, array $ids)
{
    if (empty($ids))
        return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id_alumno,
                                CONCAT(nombre,' ',apellido_paterno) AS nombre,
                                matricula
                         FROM alumnos
                         WHERE id_alumno IN ($in)");
    $st->execute($ids);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

try {
    switch ($action) {

        /* ===== LISTA GRUPOS ===== */
        case 'lista_grupos': {
            $sql = "SELECT g.id_grupo,
                           CONCAT(cns.nombre,' - ',cng.nombre,' (',c.nombre_carrera,')') AS nombre_grupo
                    FROM grupos g
                    INNER JOIN semestres s              ON s.id_nombre_semestre = g.id_nombre_semestre
                    INNER JOIN carreras c               ON c.id_carrera         = s.id_carrera
                    INNER JOIN cat_nombres_grupo cng    ON cng.id_nombre_grupo  = g.id_nombre_grupo
                    INNER JOIN cat_nombres_semestre cns ON cns.id_nombre_semestre= g.id_nombre_semestre
                    ORDER BY c.nombre_carrera, cns.nombre, cng.nombre";
            $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $data]);
            break;
        }

        /* ===== ALUMNOS DISPONIBLES + RESUMEN ===== */
        case 'alumnos_por_grupo': {
            $idGrupo = (int) ($_GET['id_grupo'] ?? 0);
            if ($idGrupo <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Grupo inválido', 'data' => []]);
                break;
            }

            $st = $pdo->prepare("SELECT s.id_carrera, g.id_nombre_semestre,
                                        cng.nombre AS nombre_grupo, cns.nombre AS nombre_semestre
                                 FROM grupos g
                                 INNER JOIN semestres s              ON s.id_nombre_semestre = g.id_nombre_semestre
                                 INNER JOIN cat_nombres_grupo cng    ON cng.id_nombre_grupo  = g.id_nombre_grupo
                                 INNER JOIN cat_nombres_semestre cns ON cns.id_nombre_semestre= g.id_nombre_semestre
                                 WHERE g.id_grupo = :g LIMIT 1");
            $st->execute([':g' => $idGrupo]);
            $meta = $st->fetch(PDO::FETCH_ASSOC);
            if (!$meta) {
                echo json_encode(['ok' => false, 'msg' => 'No se encontró la carrera/semestre', 'data' => []]);
                break;
            }

            $cand = $pdo->prepare("SELECT g2.id_grupo, cng2.nombre AS nombre_grupo, cns2.nombre AS nombre_semestre
                                   FROM grupos g2
                                   INNER JOIN semestres s2 ON s2.id_nombre_semestre = g2.id_nombre_semestre
                                   INNER JOIN cat_nombres_grupo cng2 ON cng2.id_nombre_grupo = g2.id_nombre_grupo
                                   INNER JOIN cat_nombres_semestre cns2 ON cns2.id_nombre_semestre = g2.id_nombre_semestre
                                   WHERE s2.id_carrera = :c AND g2.id_nombre_semestre = :ns AND g2.id_grupo <> :g
                                   ORDER BY (cng2.nombre LIKE '%2%') DESC, g2.id_grupo ASC
                                   LIMIT 1");
            $cand->execute([':c' => $meta['id_carrera'], ':ns' => $meta['id_nombre_semestre'], ':g' => $idGrupo]);
            $row2 = $cand->fetch(PDO::FETCH_ASSOC);
            $grupo2_id = $row2['id_grupo'] ?? null;

            $qAsig = $pdo->prepare("SELECT a.id_alumno, CONCAT(a.nombre,' ',a.apellido_paterno) AS nombre, a.matricula
                                    FROM asignaciones_grupo_alumno aga
                                    INNER JOIN alumnos a ON a.id_alumno = aga.id_alumno
                                    WHERE aga.id_grupo = :g
                                    ORDER BY a.apellido_paterno, a.nombre");
            $qAsig->execute([':g' => $idGrupo]);
            $asig1 = $qAsig->fetchAll(PDO::FETCH_ASSOC);

            $asig2 = [];
            if ($grupo2_id) {
                $qAsig->execute([':g' => $grupo2_id]);
                $asig2 = $qAsig->fetchAll(PDO::FETCH_ASSOC);
            }

            $hasCarrera = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                                       WHERE TABLE_SCHEMA = DATABASE()
                                         AND TABLE_NAME='alumnos' AND COLUMN_NAME='id_carrera'")->fetchColumn();

            if ($hasCarrera) {
                $q = $pdo->prepare("SELECT a.id_alumno, a.nombre, a.apellido_paterno, a.matricula
                                    FROM alumnos a
                                    WHERE a.id_carrera = :c
                                      AND NOT EXISTS (SELECT 1 FROM asignaciones_grupo_alumno x WHERE x.id_alumno = a.id_alumno)
                                    ORDER BY a.apellido_paterno, a.nombre");
                $q->execute([':c' => $meta['id_carrera']]);
            } else {
                $q = $pdo->prepare("SELECT a.id_alumno, a.nombre, a.apellido_paterno, a.matricula
                                    FROM alumnos a
                                    INNER JOIN semestres s ON s.id_nombre_semestre = a.id_nombre_semestre
                                    WHERE s.id_carrera = :c
                                      AND NOT EXISTS (SELECT 1 FROM asignaciones_grupo_alumno x WHERE x.id_alumno = a.id_alumno)
                                    ORDER BY a.apellido_paterno, a.nombre");
                $q->execute([':c' => $meta['id_carrera']]);
            }

            echo json_encode([
                'ok' => true,
                'data' => $q->fetchAll(PDO::FETCH_ASSOC),
                'resumen' => [
                    'grupo1' => ['id' => $idGrupo, 'titulo' => $meta['nombre_semestre'] . ' - ' . $meta['nombre_grupo'], 'alumnos' => $asig1],
                    'grupo2' => $grupo2_id ? ['id' => $grupo2_id, 'titulo' => ($row2['nombre_semestre'] ?? '') . ' - ' . ($row2['nombre_grupo'] ?? 'Grupo 2'), 'alumnos' => $asig2] : null
                ]
            ]);
            break;
        }

        /* ===== RESUMEN LIGERO ===== */
        case 'resumen_grupo': {
            $idGrupo = (int) ($_GET['id_grupo'] ?? 0);
            if ($idGrupo <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Grupo inválido']);
                break;
            }

            $st = $pdo->prepare("SELECT s.id_carrera, g.id_nombre_semestre,
                                        cng.nombre AS nombre_grupo, cns.nombre AS nombre_semestre
                                 FROM grupos g
                                 INNER JOIN semestres s ON s.id_nombre_semestre = g.id_nombre_semestre
                                 INNER JOIN cat_nombres_grupo cng ON cng.id_nombre_grupo = g.id_nombre_grupo
                                 INNER JOIN cat_nombres_semestre cns ON cns.id_nombre_semestre = g.id_nombre_semestre
                                 WHERE g.id_grupo = :g LIMIT 1");
            $st->execute([':g' => $idGrupo]);
            $meta = $st->fetch(PDO::FETCH_ASSOC);
            if (!$meta) {
                echo json_encode(['ok' => false, 'msg' => 'No se encontró la carrera/semestre']);
                break;
            }

            $cand = $pdo->prepare("SELECT g2.id_grupo, cng2.nombre AS nombre_grupo, cns2.nombre AS nombre_semestre
                                   FROM grupos g2
                                   INNER JOIN semestres s2 ON s2.id_nombre_semestre = g2.id_nombre_semestre
                                   INNER JOIN cat_nombres_grupo cng2 ON cng2.id_nombre_grupo = g2.id_nombre_grupo
                                   INNER JOIN cat_nombres_semestre cns2 ON cns2.id_nombre_semestre = g2.id_nombre_semestre
                                   WHERE s2.id_carrera = :c AND g2.id_nombre_semestre = :ns AND g2.id_grupo <> :g
                                   ORDER BY (cng2.nombre LIKE '%2%') DESC, g2.id_grupo ASC
                                   LIMIT 1");
            $cand->execute([':c' => $meta['id_carrera'], ':ns' => $meta['id_nombre_semestre'], ':g' => $idGrupo]);
            $row2 = $cand->fetch(PDO::FETCH_ASSOC);
            $grupo2_id = $row2['id_grupo'] ?? null;

            $qAsig = $pdo->prepare("SELECT a.id_alumno, CONCAT(a.nombre,' ',a.apellido_paterno) AS nombre, a.matricula
                                  FROM asignaciones_grupo_alumno aga
                                  INNER JOIN alumnos a ON a.id_alumno=aga.id_alumno
                                  WHERE aga.id_grupo=:g ORDER BY a.apellido_paterno,a.nombre");
            $qAsig->execute([':g' => $idGrupo]);
            $asig1 = $qAsig->fetchAll(PDO::FETCH_ASSOC);
            $asig2 = [];
            if ($grupo2_id) {
                $qAsig->execute([':g' => $grupo2_id]);
                $asig2 = $qAsig->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode([
                'ok' => true,
                'resumen' => [
                    'grupo1' => ['id' => $idGrupo, 'titulo' => $meta['nombre_semestre'] . ' - ' . $meta['nombre_grupo'], 'alumnos' => $asig1],
                    'grupo2' => $grupo2_id ? ['id' => $grupo2_id, 'titulo' => ($row2['nombre_semestre'] ?? '') . ' - ' . ($row2['nombre_grupo'] ?? 'Grupo 2'), 'alumnos' => $asig2] : null
                ]
            ]);
            break;
        }

        /* ===== ASIGNAR GRUPO (cupo 30 + overflow a G2) ===== */
        case 'asignar_grupo': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
                break;
            }

            $hasAga = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='asignaciones_grupo_alumno'")->fetchColumn();
            if (!$hasAga) {
                http_response_code(409);
                echo json_encode(['ok' => false, 'msg' => 'Falta la tabla asignaciones_grupo_alumno']);
                break;
            }

            $idGrupo = (int) ($_POST['id_grupo'] ?? 0);
            $alumnos = $_POST['alumnos'] ?? [];
            if ($idGrupo <= 0 || !is_array($alumnos) || !count($alumnos)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'msg' => 'Faltan datos (id_grupo y alumnos[])']);
                break;
            }
            $alumnos = array_values(array_unique(array_map('intval', $alumnos)));

            $st = $pdo->prepare("SELECT s.id_carrera, g.id_nombre_semestre, cng.nombre AS nombre_grupo, cns.nombre AS nombre_semestre
                               FROM grupos g
                               INNER JOIN semestres s ON s.id_nombre_semestre=g.id_nombre_semestre
                               INNER JOIN cat_nombres_grupo cng ON cng.id_nombre_grupo=g.id_nombre_grupo
                               INNER JOIN cat_nombres_semestre cns ON cns.id_nombre_semestre=g.id_nombre_semestre
                               WHERE g.id_grupo=:g LIMIT 1");
            $st->execute([':g' => $idGrupo]);
            $meta = $st->fetch(PDO::FETCH_ASSOC);
            if (!$meta) {
                echo json_encode(['ok' => false, 'msg' => 'Grupo no encontrado']);
                break;
            }

            $cand = $pdo->prepare("SELECT g2.id_grupo, cng2.nombre AS nombre_grupo, cns2.nombre AS nombre_semestre
                                 FROM grupos g2
                                 INNER JOIN semestres s2 ON s2.id_nombre_semestre=g2.id_nombre_semestre
                                 INNER JOIN cat_nombres_grupo cng2 ON cng2.id_nombre_grupo=g2.id_nombre_grupo
                                 INNER JOIN cat_nombres_semestre cns2 ON cns2.id_nombre_semestre=g2.id_nombre_semestre
                                 WHERE s2.id_carrera=:c AND g2.id_nombre_semestre=:ns AND g2.id_grupo<>:g
                                 ORDER BY (cng2.nombre LIKE '%2%') DESC, g2.id_grupo ASC LIMIT 1");
            $cand->execute([':c' => $meta['id_carrera'], ':ns' => $meta['id_nombre_semestre'], ':g' => $idGrupo]);
            $row2 = $cand->fetch(PDO::FETCH_ASSOC);
            $grupo2_id = $row2['id_grupo'] ?? null;

            $capSt = $pdo->prepare("SELECT COUNT(*) FROM asignaciones_grupo_alumno WHERE id_grupo=:g");
            $capSt->execute([':g' => $idGrupo]);
            $ocupados1 = (int) $capSt->fetchColumn();
            $cupo1 = max(30 - $ocupados1, 0);

            $ins = $pdo->prepare("INSERT IGNORE INTO asignaciones_grupo_alumno (id_grupo,id_alumno) VALUES (:g,:a)");
            $insertados1 = [];
            $insertados2 = [];
            $pendientes = [];
            foreach ($alumnos as $aId) {
                if ($cupo1 <= 0)
                    break;
                $ins->execute([':g' => $idGrupo, ':a' => $aId]);
                if ($ins->rowCount() > 0) {
                    $insertados1[] = $aId;
                    $cupo1--;
                }
            }
            $resto = array_values(array_diff($alumnos, $insertados1));

            if (!empty($resto) && $grupo2_id) {
                $capSt->execute([':g' => $grupo2_id]);
                $ocupados2 = (int) $capSt->fetchColumn();
                $cupo2 = max(30 - $ocupados2, 0);
                foreach ($resto as $aId) {
                    if ($cupo2 <= 0)
                        break;
                    $ins->execute([':g' => $grupo2_id, ':a' => $aId]);
                    if ($ins->rowCount() > 0) {
                        $insertados2[] = $aId;
                        $cupo2--;
                    }
                }
                $pendientes = array_values(array_diff($resto, $insertados2));
            } else {
                $pendientes = $resto;
            }

            $titulo1 = $meta['nombre_semestre'] . ' - ' . $meta['nombre_grupo'];
            $al1 = fetchAllByIds($pdo, $insertados1);
            $res1 = ['id' => $idGrupo, 'titulo' => $titulo1, 'alumnos' => $al1];

            $res2 = null;
            $al2 = [];
            if ($grupo2_id) {
                $titulo2 = ($row2['nombre_semestre'] ?? '') . ' - ' . ($row2['nombre_grupo'] ?? 'Grupo 2');
                $al2 = fetchAllByIds($pdo, $insertados2);
                $res2 = ['id' => $grupo2_id, 'titulo' => $titulo2, 'alumnos' => $al2];
            }

            // ---- Notificación: asignación a grupo(s)
            $actorId = getSecretariaActorIdFromSession();
            $total1 = count($insertados1);
            $total2 = count($insertados2);
            $pend = count($pendientes);

            $detalle = "G1: {$total1}" . ($grupo2_id ? " • G2: {$total2}" : '') . " • Pendientes: {$pend}";
            notificar_admin_pdo($pdo, [
                'tipo' => 'movimiento',
                'titulo' => 'Asignación de alumnos a grupo',
                'detalle' => $detalle,
                'actor_id' => $actorId,
                'recurso' => 'asignacion_grupo',
                'accion' => 'alta',
                'meta' => [
                    'grupo1' => ['id' => $idGrupo, 'titulo' => $titulo1, 'insertados' => $al1],
                    'grupo2' => $grupo2_id ? ['id' => $grupo2_id, 'titulo' => ($row2['nombre_semestre'] ?? '') . ' - ' . ($row2['nombre_grupo'] ?? 'Grupo 2'), 'insertados' => $al2] : null,
                    'pendientes' => $pendientes,
                ]
            ]);

            echo json_encode([
                'ok' => true,
                'msg' => "Asignados G1: " . $total1 . ", G2: " . $total2 . ". Pendientes: " . $pend,
                'resumen' => ['grupo1' => $res1, 'grupo2' => $res2, 'pendientes' => $pend]
            ]);
            break;
        }

        /* ===== LISTA CICLOS ===== */
        case 'lista_ciclos': {
            $sql = "SELECT id_ciclo, fecha_inicio, fecha_fin, IFNULL(activo,0) AS activo
                    FROM ciclos_escolares
                    ORDER BY fecha_inicio DESC";
            $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as &$r) {
                $r['nombre_ciclo'] = date('Y', strtotime($r['fecha_inicio'])) . '-' . date('Y', strtotime($r['fecha_fin']));
            }
            echo json_encode(['ok' => true, 'data' => $data]);
            break;
        }

        /* ===== PREVIEW CICLO ===== */
        case 'preview_ciclo_grupo': {
            $idGrupo = (int) ($_GET['id_grupo'] ?? 0);
            $idCiclo = (int) ($_GET['id_ciclo'] ?? 0);
            if ($idGrupo <= 0 || $idCiclo <= 0) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'msg' => 'Faltan id_grupo o id_ciclo']);
                break;
            }

            $sql = "SELECT a.id_alumno,
                           CONCAT(a.nombre,' ',a.apellido_paterno) AS nombre,
                           a.matricula,
                           CASE WHEN ac.id_alumno IS NULL THEN 0 ELSE 1 END AS tiene_ciclo
                    FROM asignaciones_grupo_alumno aga
                    INNER JOIN alumnos a ON a.id_alumno = aga.id_alumno
                    LEFT JOIN alumno_ciclo ac
                      ON ac.id_alumno = a.id_alumno AND ac.id_ciclo = :ciclo
                    WHERE aga.id_grupo = :grupo
                    ORDER BY a.apellido_paterno, a.nombre";
            $st = $pdo->prepare($sql);
            $st->execute([':grupo' => $idGrupo, ':ciclo' => $idCiclo]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['ok' => true, 'data' => $rows]);
            break;
        }

        /* ===== ASIGNAR CICLO (bloqueo + reinscripción + NO duplicados) ===== */
        case 'asignar_ciclo_grupo': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
                break;
            }

            $idGrupo = (int) ($_POST['id_grupo'] ?? 0);
            $idCiclo = (int) ($_POST['id_ciclo'] ?? 0);
            $reinscribir = (int) ($_POST['reinscribir'] ?? 0);

            if ($idGrupo <= 0 || $idCiclo <= 0) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'msg' => 'Faltan id_grupo o id_ciclo']);
                break;
            }

            $hasAga = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='asignaciones_grupo_alumno'")->fetchColumn();
            $hasAc = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='alumno_ciclo'")->fetchColumn();
            if (!$hasAga || !$hasAc) {
                http_response_code(409);
                echo json_encode(['ok' => false, 'msg' => 'Faltan tablas requeridas (asignaciones_grupo_alumno o alumno_ciclo)']);
                break;
            }

            // Alumnos del grupo
            $stA = $pdo->prepare("SELECT id_alumno FROM asignaciones_grupo_alumno WHERE id_grupo = :g");
            $stA->execute([':g' => $idGrupo]);
            $alumnosGrupo = $stA->fetchAll(PDO::FETCH_COLUMN);
            if (!$alumnosGrupo) {
                echo json_encode(['ok' => false, 'msg' => 'El grupo no tiene alumnos asignados']);
                break;
            }

            // BLOQUEO: si tienen ciclo vigente distinto al nuevo, pedir REINSCRIPCIÓN
            $in = implode(',', array_fill(0, count($alumnosGrupo), '?'));
            $sqlBloq = "SELECT ac.id_alumno
                FROM alumno_ciclo ac
                INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                WHERE ac.id_alumno IN ($in)
                  AND ac.estatus = 'inscrito'
                  AND (ce.activo = 1 OR CURDATE() BETWEEN ce.fecha_inicio AND ce.fecha_fin)
                  AND ac.id_ciclo <> ?";
            $stB = $pdo->prepare($sqlBloq);
            $params = array_merge($alumnosGrupo, [$idCiclo]);
            $stB->execute($params);
            $bloqueados = $stB->fetchAll(PDO::FETCH_COLUMN);

            if ($bloqueados && !$reinscribir) {
                echo json_encode([
                    'ok' => false,
                    'code' => 'BLOQUEO_CICLO',
                    'msg' => "Hay " . count($bloqueados) . " alumno(s) con ciclo vigente distinto. Marca REINSCRIPCIÓN para moverlos.",
                    'bloqueados' => count($bloqueados)
                ]);
                break;
            }

            try {
                $pdo->beginTransaction();

                $movidos = 0;
                if ($bloqueados && $reinscribir) {
                    // Cerrar ciclo vigente distinto (concluir)
                    $inB = implode(',', array_fill(0, count($bloqueados), '?'));
                    $sqlClose = "UPDATE alumno_ciclo ac
                         INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                         SET ac.estatus = 'concluido', ac.fecha_baja = NOW()
                         WHERE ac.id_alumno IN ($inB)
                           AND ac.estatus='inscrito'
                           AND (ce.activo = 1 OR CURDATE() BETWEEN ce.fecha_inicio AND ce.fecha_fin)";
                    $stC = $pdo->prepare($sqlClose);
                    $stC->execute($bloqueados);
                    $movidos = $stC->rowCount();
                }

                // Conteo previo de "ya tenían este ciclo"
                $stPrev = $pdo->prepare("SELECT COUNT(*)
                                 FROM asignaciones_grupo_alumno aga
                                 INNER JOIN alumno_ciclo ac ON ac.id_alumno = aga.id_alumno AND ac.id_ciclo = :c
                                 WHERE aga.id_grupo = :g");
                $stPrev->execute([':g' => $idGrupo, ':c' => $idCiclo]);
                $yaTenianAntes = (int) $stPrev->fetchColumn();

                // INSERT IGNORE solo faltantes
                $sqlInsert = "INSERT IGNORE INTO alumno_ciclo (id_alumno, id_ciclo, id_grupo, estatus, fecha_inscripcion)
                      SELECT aga.id_alumno, :c1, :g1, 'inscrito', NOW()
                      FROM asignaciones_grupo_alumno aga
                      WHERE aga.id_grupo = :g2";
                $ins = $pdo->prepare($sqlInsert);
                $ins->execute([':c1' => $idCiclo, ':g1' => $idGrupo, ':g2' => $idGrupo]);
                $insertados = $ins->rowCount();

                // Para resumen final
                $stRes = $pdo->prepare("SELECT a.id_alumno, CONCAT(a.nombre,' ',a.apellido_paterno) AS nombre, a.matricula
                                FROM alumno_ciclo ac
                                INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
                                WHERE ac.id_grupo = :g AND ac.id_ciclo = :c
                                ORDER BY a.apellido_paterno, a.nombre");
                $stRes->execute([':g' => $idGrupo, ':c' => $idCiclo]);
                $alumnosCiclo = $stRes->fetchAll(PDO::FETCH_ASSOC);

                $pdo->commit();

                $omitidos = $yaTenianAntes;
                $msg = "Insertados: $insertados. Omitidos (ya tenían este ciclo): $omitidos.";
                if ($movidos)
                    $msg .= " Reinscritos: $movidos.";

                // ---- Notificación: asignación de ciclo ----
                $actorId = getSecretariaActorIdFromSession();

                // Nombre del ciclo legible
                $stCiclo = $pdo->prepare("SELECT fecha_inicio, fecha_fin, IFNULL(activo,0) AS activo FROM ciclos_escolares WHERE id_ciclo=?");
                $stCiclo->execute([$idCiclo]);
                $rc = $stCiclo->fetch(PDO::FETCH_ASSOC);
                $cicloLabel = $rc ? (date('Y', strtotime($rc['fecha_inicio'])) . '-' . date('Y', strtotime($rc['fecha_fin'])) . ((int) $rc['activo'] ? ' • ACTIVO' : '')) : ("ID $idCiclo");

                // Nombre del grupo legible
                $stG = $pdo->prepare("SELECT cng.nombre AS nombre_grupo, cns.nombre AS nombre_semestre
                                      FROM grupos g
                                      INNER JOIN cat_nombres_grupo cng ON cng.id_nombre_grupo = g.id_nombre_grupo
                                      INNER JOIN cat_nombres_semestre cns ON cns.id_nombre_semestre = g.id_nombre_semestre
                                      WHERE g.id_grupo=?");
                $stG->execute([$idGrupo]);
                $rg = $stG->fetch(PDO::FETCH_ASSOC);
                $grupoLabel = $rg ? ($rg['nombre_semestre'] . ' - ' . $rg['nombre_grupo']) : "Grupo $idGrupo";

                notificar_admin_pdo($pdo, [
                    'tipo' => 'movimiento',
                    'titulo' => 'Asignación de ciclo a grupo',
                    'detalle' => "Grupo: {$grupoLabel} • Ciclo: {$cicloLabel} • {$msg}",
                    'actor_id' => $actorId,
                    'recurso' => 'alumno_ciclo',
                    'accion' => 'alta',
                    'meta' => [
                        'id_grupo' => $idGrupo,
                        'grupo' => $grupoLabel,
                        'id_ciclo' => $idCiclo,
                        'ciclo' => $cicloLabel,
                        'insertados' => $insertados,
                        'omitidos' => $omitidos,
                        'reinscritos' => $movidos,
                        'alumnos_result' => $alumnosCiclo
                    ]
                ]);

                echo json_encode([
                    'ok' => true,
                    'msg' => $msg,
                    'resumen' => ['id_grupo' => $idGrupo, 'id_ciclo' => $idCiclo, 'alumnos' => $alumnosCiclo]
                ]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['ok' => false, 'msg' => 'Excepción: ' . $e->getMessage()]);
            }
            break;
        }

        /* ===== TABLERO: UN CICLO POR GRUPO (vigente o el más reciente) ===== */
        case 'grupos_ciclo_cards': {
            $sqlPairs = "
                SELECT x.id_grupo, x.id_ciclo
                FROM (
                    SELECT ac.id_grupo, ac.id_ciclo, ce.fecha_inicio, 1 AS prio
                    FROM alumno_ciclo ac
                    INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                    WHERE ac.estatus='inscrito' AND (ce.activo=1 OR CURDATE() BETWEEN ce.fecha_inicio AND ce.fecha_fin)
                    UNION ALL
                    SELECT ac.id_grupo, ac.id_ciclo, ce.fecha_inicio, 2 AS prio
                    FROM alumno_ciclo ac
                    INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                    WHERE ac.estatus='inscrito'
                ) x
                INNER JOIN (
                    SELECT id_grupo, MIN(prio) AS prio
                    FROM (
                        SELECT ac.id_grupo, 1 AS prio
                        FROM alumno_ciclo ac
                        INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                        WHERE ac.estatus='inscrito' AND (ce.activo=1 OR CURDATE() BETWEEN ce.fecha_inicio AND ce.fecha_fin)
                        UNION ALL
                        SELECT ac.id_grupo, 2 AS prio
                        FROM alumno_ciclo ac
                        INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                        WHERE ac.estatus='inscrito'
                    ) t GROUP BY id_grupo
                ) p ON p.id_grupo=x.id_grupo AND p.prio=x.prio
                INNER JOIN (
                    SELECT id_grupo, MAX(fecha_inicio) AS max_ini
                    FROM (
                        SELECT ac.id_grupo, ce.fecha_inicio
                        FROM alumno_ciclo ac
                        INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                        WHERE ac.estatus='inscrito' AND (ce.activo=1 OR CURDATE() BETWEEN ce.fecha_inicio AND ce.fecha_fin)
                        UNION ALL
                        SELECT ac.id_grupo, ce.fecha_inicio
                        FROM alumno_ciclo ac
                        INNER JOIN ciclos_escolares ce ON ce.id_ciclo = ac.id_ciclo
                        WHERE ac.estatus='inscrito'
                    ) z GROUP BY id_grupo
                ) m ON m.id_grupo=x.id_grupo AND m.max_ini=x.fecha_inicio
                GROUP BY x.id_grupo
            ";

            $pairs = $pdo->query($sqlPairs)->fetchAll(PDO::FETCH_ASSOC);
            if (!$pairs) {
                echo json_encode(['ok' => true, 'data' => []]);
                break;
            }

            $map = [];
            foreach ($pairs as $p)
                $map[(int) $p['id_grupo']] = (int) $p['id_ciclo'];
            $idsGrupos = implode(',', array_keys($map));

            $sqlDet = "SELECT ac.id_grupo, ac.id_ciclo,
                              COUNT(ac.id_alumno) AS total,
                              cng.nombre AS nombre_grupo,
                              cns.nombre AS nombre_semestre,
                              c.nombre_carrera,
                              ce.fecha_inicio, ce.fecha_fin, IFNULL(ce.activo,0) AS activo
                       FROM alumno_ciclo ac
                       INNER JOIN grupos g                 ON g.id_grupo = ac.id_grupo
                       INNER JOIN semestres s              ON s.id_nombre_semestre = g.id_nombre_semestre
                       INNER JOIN cat_nombres_grupo cng    ON cng.id_nombre_grupo = g.id_nombre_grupo
                       INNER JOIN cat_nombres_semestre cns ON cns.id_nombre_semestre = g.id_nombre_semestre
                       INNER JOIN carreras c               ON c.id_carrera = s.id_carrera
                       INNER JOIN ciclos_escolares ce      ON ce.id_ciclo = ac.id_ciclo
                       WHERE ac.estatus='inscrito' AND ac.id_grupo IN ($idsGrupos)
                       GROUP BY ac.id_grupo, ac.id_ciclo";
            $rows = $pdo->query($sqlDet)->fetchAll(PDO::FETCH_ASSOC);

            $filtered = array_values(array_filter(
                $rows,
                fn($r) => (int) $map[(int) $r['id_grupo']] === (int) $r['id_ciclo']
            ));

            $stAl = $pdo->prepare("SELECT a.id_alumno, CONCAT(a.nombre,' ',a.apellido_paterno) AS nombre, a.matricula
                                   FROM alumno_ciclo ac
                                   INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
                                   WHERE ac.id_grupo = :g AND ac.id_ciclo = :c AND ac.estatus='inscrito'
                                   ORDER BY a.apellido_paterno, a.nombre");

            $out = [];
            foreach ($filtered as $p) {
                $stAl->execute([':g' => $p['id_grupo'], ':c' => $p['id_ciclo']]);
                $al = $stAl->fetchAll(PDO::FETCH_ASSOC);

                $label = date('Y', strtotime($p['fecha_inicio'])) . '-' . date('Y', strtotime($p['fecha_fin']));
                if ((int) $p['activo'])
                    $label .= ' • ACTIVO';

                $out[] = [
                    'id_grupo' => (int) $p['id_grupo'],
                    'id_ciclo' => (int) $p['id_ciclo'],
                    'titulo' => "{$p['nombre_semestre']} - {$p['nombre_grupo']} ({$p['nombre_carrera']})",
                    'ciclo' => $label,            // para front nuevo
                    'ciclo_label' => $label,      // compat front viejo
                    'total' => (int) $p['total'],
                    'alumnos' => $al
                ];
            }

            echo json_encode(['ok' => true, 'data' => $out]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Excepción: ' . $e->getMessage()]);
}