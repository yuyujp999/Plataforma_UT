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

        /* ===== ALUMNOS DISPONIBLES + RESUMEN (persistente) ===== */
        case 'alumnos_por_grupo': {
            $idGrupo = (int) ($_GET['id_grupo'] ?? 0);
            if ($idGrupo <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Grupo inválido', 'data' => []]);
                break;
            }

            // meta del grupo y carrera
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

            // Grupo 2 candidato (misma carrera+semestre)
            $cand = $pdo->prepare("SELECT g2.id_grupo, cng2.nombre AS nombre_grupo, cns2.nombre AS nombre_semestre
                                   FROM grupos g2
                                   INNER JOIN semestres s2              ON s2.id_nombre_semestre = g2.id_nombre_semestre
                                   INNER JOIN cat_nombres_grupo cng2    ON cng2.id_nombre_grupo  = g2.id_nombre_grupo
                                   INNER JOIN cat_nombres_semestre cns2 ON cns2.id_nombre_semestre= g2.id_nombre_semestre
                                   WHERE s2.id_carrera = :c AND g2.id_nombre_semestre = :ns AND g2.id_grupo <> :g
                                   ORDER BY (cng2.nombre LIKE '%2%') DESC, g2.id_grupo ASC
                                   LIMIT 1");
            $cand->execute([':c' => $meta['id_carrera'], ':ns' => $meta['id_nombre_semestre'], ':g' => $idGrupo]);
            $row2 = $cand->fetch(PDO::FETCH_ASSOC);
            $grupo2_id = $row2['id_grupo'] ?? null;

            // RESUMEN: ya asignados en grupo1 y (si existe) grupo2
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

            // ALUMNOS DISPONIBLES: de la misma carrera, que NO estén en asignaciones_grupo_alumno (en ningún grupo)
            $hasCarrera = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                                       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='alumnos' AND COLUMN_NAME='id_carrera'")->fetchColumn();

            if ($hasCarrera) {
                $q = $pdo->prepare("SELECT a.id_alumno, a.nombre, a.apellido_paterno, a.matricula
                                    FROM alumnos a
                                    WHERE a.id_carrera = :c
                                      AND NOT EXISTS (
                                            SELECT 1 FROM asignaciones_grupo_alumno x
                                            WHERE x.id_alumno = a.id_alumno
                                      )
                                    ORDER BY a.apellido_paterno, a.nombre");
                $q->execute([':c' => $meta['id_carrera']]);
            } else {
                $q = $pdo->prepare("SELECT a.id_alumno, a.nombre, a.apellido_paterno, a.matricula
                                    FROM alumnos a
                                    INNER JOIN semestres s ON s.id_nombre_semestre = a.id_nombre_semestre
                                    WHERE s.id_carrera = :c
                                      AND NOT EXISTS (
                                            SELECT 1 FROM asignaciones_grupo_alumno x
                                            WHERE x.id_alumno = a.id_alumno
                                      )
                                    ORDER BY a.apellido_paterno, a.nombre");
                $q->execute([':c' => $meta['id_carrera']]);
            }

            echo json_encode([
                'ok' => true,
                'data' => $q->fetchAll(PDO::FETCH_ASSOC), // disponibles
                'resumen' => [
                    'grupo1' => [
                        'id' => $idGrupo,
                        'titulo' => $meta['nombre_semestre'] . ' - ' . $meta['nombre_grupo'],
                        'alumnos' => $asig1
                    ],
                    'grupo2' => $grupo2_id ? [
                        'id' => $grupo2_id,
                        'titulo' => ($row2['nombre_semestre'] ?? '') . ' - ' . ($row2['nombre_grupo'] ?? 'Grupo 2'),
                        'alumnos' => $asig2
                    ] : null
                ]
            ]);
            break;
        }

        /* ===== ASIGNAR (cupo 30, resto a Grupo 2) ===== */
        case 'asignar_grupo': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
                break;
            }

            $hasAga = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='asignaciones_grupo_alumno'")->fetchColumn();
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

            // meta del grupo para detectar grupo 2
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
                echo json_encode(['ok' => false, 'msg' => 'Grupo no encontrado']);
                break;
            }

            $cand = $pdo->prepare("SELECT g2.id_grupo, cng2.nombre AS nombre_grupo, cns2.nombre AS nombre_semestre
                                   FROM grupos g2
                                   INNER JOIN semestres s2              ON s2.id_nombre_semestre = g2.id_nombre_semestre
                                   INNER JOIN cat_nombres_grupo cng2    ON cng2.id_nombre_grupo  = g2.id_nombre_grupo
                                   INNER JOIN cat_nombres_semestre cns2 ON cns2.id_nombre_semestre= g2.id_nombre_semestre
                                   WHERE s2.id_carrera = :c AND g2.id_nombre_semestre = :ns AND g2.id_grupo <> :g
                                   ORDER BY (cng2.nombre LIKE '%2%') DESC, g2.id_grupo ASC
                                   LIMIT 1");
            $cand->execute([':c' => $meta['id_carrera'], ':ns' => $meta['id_nombre_semestre'], ':g' => $idGrupo]);
            $row2 = $cand->fetch(PDO::FETCH_ASSOC);
            $grupo2_id = $row2['id_grupo'] ?? null;

            // cupos
            $capSt = $pdo->prepare("SELECT COUNT(*) FROM asignaciones_grupo_alumno WHERE id_grupo = :g");
            $capSt->execute([':g' => $idGrupo]);
            $ocupados1 = (int) $capSt->fetchColumn();
            $cupo1 = max(30 - $ocupados1, 0);

            $ins = $pdo->prepare("INSERT IGNORE INTO asignaciones_grupo_alumno (id_grupo, id_alumno) VALUES (:g,:a)");

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

            // Resumen de lo recién insertado (para pintar en UI sin recargar)
            $titulo1 = $meta['nombre_semestre'] . ' - ' . $meta['nombre_grupo'];
            $al1 = fetchAllByIds($pdo, $insertados1);
            $res1 = ['id' => $idGrupo, 'titulo' => $titulo1, 'alumnos' => $al1];

            $res2 = null;
            if ($grupo2_id) {
                $titulo2 = ($row2['nombre_semestre'] ?? '') . ' - ' . ($row2['nombre_grupo'] ?? 'Grupo 2');
                $al2 = fetchAllByIds($pdo, $insertados2);
                $res2 = ['id' => $grupo2_id, 'titulo' => $titulo2, 'alumnos' => $al2];
            }

            echo json_encode([
                'ok' => true,
                'msg' => "Asignados G1: " . count($insertados1) . ", G2: " . count($insertados2) . ". Pendientes: " . count($pendientes),
                'resumen' => [
                    'grupo1' => $res1,
                    'grupo2' => $res2,
                    'pendientes' => count($pendientes)
                ]
            ]);
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