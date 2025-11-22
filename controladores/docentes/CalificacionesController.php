<?php
// /controladores/docentes/CalificacionesController.php

class CalificacionesController
{
    /**
     * Materias/asignaciones del docente para el <select>.
     */
    public static function obtenerAsignacionesDocente(int $idDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
            SELECT 
              ad.id_asignacion_docente,
              m.nombre_materia AS materia
            FROM asignaciones_docentes ad
            INNER JOIN asignar_materias am 
                ON ad.id_nombre_materia = am.id_nombre_materia
            INNER JOIN materias m 
                ON am.id_materia = m.id_materia
            WHERE ad.id_docente = ?
            ORDER BY m.nombre_materia ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idDocente);
        $stmt->execute();
        $res = $stmt->get_result();

        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Matriz completa para el dashboard de calificaciones.
     */
    public static function obtenerMatriz(int $idDocente, int $idAsignacionDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // ========== 1) CONFIG PORCENTAJES ==========
        $config = [
            'pct_tareas'    => 34.0,
            'pct_proyectos' => 33.0,
            'pct_examenes'  => 33.0,
        ];

        $tableCheckCfg = $conn->query("SHOW TABLES LIKE 'calificaciones_config'");
        if ($tableCheckCfg && $tableCheckCfg->num_rows > 0) {
            $stmtCfg = $conn->prepare("
                SELECT pct_tareas, pct_proyectos, pct_examenes
                FROM calificaciones_config
                WHERE id_docente = ? AND id_asignacion_docente = ?
                LIMIT 1
            ");
            $stmtCfg->bind_param("ii", $idDocente, $idAsignacionDocente);
            $stmtCfg->execute();
            $cfgRow = $stmtCfg->get_result()->fetch_assoc();
            if ($cfgRow) {
                $config['pct_tareas']    = (float)$cfgRow['pct_tareas'];
                $config['pct_proyectos'] = (float)$cfgRow['pct_proyectos'];
                $config['pct_examenes']  = (float)$cfgRow['pct_examenes'];
            }
        }

        // ========== 2) ALUMNOS DEL GRUPO ==========
        $sqlAlumnos = "
            SELECT DISTINCT
              a.id_alumno,
              a.nombre,
              a.apellido_paterno,
              a.apellido_materno,
              a.matricula
            FROM asignaciones_docentes ad
            INNER JOIN asignar_materias am 
                ON ad.id_nombre_materia = am.id_nombre_materia
            INNER JOIN grupos g 
                ON g.id_nombre_grupo = am.id_nombre_grupo_int
            INNER JOIN asignaciones_grupo_alumno aga 
                ON aga.id_grupo = g.id_grupo
            INNER JOIN alumnos a 
                ON aga.id_alumno = a.id_alumno
            WHERE ad.id_asignacion_docente = ?
            ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre
        ";
        $stmtA = $conn->prepare($sqlAlumnos);
        $stmtA->bind_param("i", $idAsignacionDocente);
        $stmtA->execute();
        $resA = $stmtA->get_result();

        $alumnos = [];
        while ($row = $resA->fetch_assoc()) {
            $alumnos[] = $row;
        }

        // ========== 3) TAREAS / EVALUACIONES / EXÁMENES ==========
        // Tareas
        $tareas = [];
        $stmtT = $conn->prepare("
            SELECT id_tarea, titulo
            FROM tareas_materias
            WHERE id_asignacion_docente = ?
            ORDER BY fecha_entrega ASC, id_tarea ASC
        ");
        $stmtT->bind_param("i", $idAsignacionDocente);
        $stmtT->execute();
        $resT = $stmtT->get_result();
        $idsT = [];
        while ($row = $resT->fetch_assoc()) {
            $tareas[] = $row;
            $idsT[]   = (int)$row['id_tarea'];
        }

        // Evaluaciones / proyectos
        $evaluaciones = [];
        $stmtEv = $conn->prepare("
            SELECT id_evaluacion, titulo
            FROM evaluaciones_docente
            WHERE id_asignacion_docente = ?
            ORDER BY fecha_cierre ASC, id_evaluacion ASC
        ");
        $stmtEv->bind_param("i", $idAsignacionDocente);
        $stmtEv->execute();
        $resEv = $stmtEv->get_result();
        $idsEv = [];
        while ($row = $resEv->fetch_assoc()) {
            $evaluaciones[] = $row;
            $idsEv[]        = (int)$row['id_evaluacion'];
        }

        // Exámenes
        $examenes = [];
        $stmtEx = $conn->prepare("
            SELECT id_examen, titulo
            FROM examenes
            WHERE id_asignacion_docente = ?
            ORDER BY fecha_cierre ASC, id_examen ASC
        ");
        $stmtEx->bind_param("i", $idAsignacionDocente);
        $stmtEx->execute();
        $resEx = $stmtEx->get_result();
        $idsEx = [];
        while ($row = $resEx->fetch_assoc()) {
            $examenes[] = $row;
            $idsEx[]    = (int)$row['id_examen'];
        }

        // ========== 4) CALIFICACIONES YA GUARDADAS ==========
        $calT  = [];
        $calEv = [];
        $calEx = [];

        // Tareas (entregas_alumnos)
        if (!empty($idsT)) {
            $inT   = implode(',', array_fill(0, count($idsT), '?'));
            $types = str_repeat('i', count($idsT));

            $sqlCalT = "
                SELECT ea.id_alumno, ea.id_tarea, ea.calificacion
                FROM entregas_alumnos ea
                WHERE ea.id_tarea IN ($inT)
            ";
            $stmtCalT = $conn->prepare($sqlCalT);
            $stmtCalT->bind_param($types, ...$idsT);
            $stmtCalT->execute();
            $resCalT = $stmtCalT->get_result();
            while ($row = $resCalT->fetch_assoc()) {
                $idAl = (int)$row['id_alumno'];
                $idTa = (int)$row['id_tarea'];
                $calT[$idAl][$idTa] = $row['calificacion'] !== null ? (float)$row['calificacion'] : null;
            }
        }

        // Evaluaciones (entregas_evaluaciones_alumnos)
        if (!empty($idsEv)) {
            $inEv  = implode(',', array_fill(0, count($idsEv), '?'));
            $types = str_repeat('i', count($idsEv));

            $sqlCalEv = "
                SELECT eea.id_alumno, eea.id_evaluacion, eea.calificacion
                FROM entregas_evaluaciones_alumnos eea
                WHERE eea.id_evaluacion IN ($inEv)
            ";
            $stmtCalEv = $conn->prepare($sqlCalEv);
            $stmtCalEv->bind_param($types, ...$idsEv);
            $stmtCalEv->execute();
            $resCalEv = $stmtCalEv->get_result();
            while ($row = $resCalEv->fetch_assoc()) {
                $idAl   = (int)$row['id_alumno'];
                $idEval = (int)$row['id_evaluacion'];
                $calEv[$idAl][$idEval] = $row['calificacion'] !== null ? (float)$row['calificacion'] : null;
            }
        }

        // Exámenes (examen_calificaciones si existe)
        $tableCheckExCal = $conn->query("SHOW TABLES LIKE 'examen_calificaciones'");
        if (!empty($idsEx) && $tableCheckExCal && $tableCheckExCal->num_rows > 0) {
            $inEx  = implode(',', array_fill(0, count($idsEx), '?'));
            $types = str_repeat('i', count($idsEx));

            $sqlCalEx = "
                SELECT ec.id_alumno, ec.id_examen, ec.calificacion
                FROM examen_calificaciones ec
                WHERE ec.id_examen IN ($inEx)
            ";
            $stmtCalEx = $conn->prepare($sqlCalEx);
            $stmtCalEx->bind_param($types, ...$idsEx);
            $stmtCalEx->execute();
            $resCalEx = $stmtCalEx->get_result();
            while ($row = $resCalEx->fetch_assoc()) {
                $idAl  = (int)$row['id_alumno'];
                $idExa = (int)$row['id_examen'];
                $calEx[$idAl][$idExa] = $row['calificacion'] !== null ? (float)$row['calificacion'] : null;
            }
        }

        return [
            'alumnos'      => $alumnos,
            'tareas'       => $tareas,
            'evaluaciones' => $evaluaciones,
            'examenes'     => $examenes,
            'cal_tareas'   => $calT,
            'cal_evals'    => $calEv,
            'cal_exams'    => $calEx,
            'config'       => $config,
        ];
    }

    /**
     * Guardar porcentajes y calificaciones.
     * - NO borra calificaciones existentes si dejas el input vacío.
     * - Si no existía registro de tarea/proyecto, lo INSERTA.
     */
    public static function guardar(int $idDocente, int $idAsignacionDocente, array $post): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // -------- PORCENTAJES ----------
        $pctT = isset($post['pct_tareas'])    ? (float)$post['pct_tareas']    : 34.0;
        $pctP = isset($post['pct_proyectos']) ? (float)$post['pct_proyectos'] : 33.0;
        $pctE = isset($post['pct_examenes'])  ? (float)$post['pct_examenes']  : 33.0;

        $pctT = max(0, min(100, $pctT));
        $pctP = max(0, min(100, $pctP));
        $pctE = max(0, min(100, $pctE));

        $tableCheckCfg = $conn->query("SHOW TABLES LIKE 'calificaciones_config'");
        if ($tableCheckCfg && $tableCheckCfg->num_rows > 0) {
            $stmtSel = $conn->prepare("
                SELECT id_config
                FROM calificaciones_config
                WHERE id_docente = ? AND id_asignacion_docente = ?
                LIMIT 1
            ");
            $stmtSel->bind_param("ii", $idDocente, $idAsignacionDocente);
            $stmtSel->execute();
            $rowCfg = $stmtSel->get_result()->fetch_assoc();

            if ($rowCfg) {
                $stmtUpd = $conn->prepare("
                    UPDATE calificaciones_config
                    SET pct_tareas = ?, pct_proyectos = ?, pct_examenes = ?
                    WHERE id_config = ?
                ");
                $stmtUpd->bind_param("dddi", $pctT, $pctP, $pctE, $rowCfg['id_config']);
                $stmtUpd->execute();
            } else {
                $stmtIns = $conn->prepare("
                    INSERT INTO calificaciones_config
                        (id_docente, id_asignacion_docente, pct_tareas, pct_proyectos, pct_examenes)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtIns->bind_param("iiddd", $idDocente, $idAsignacionDocente, $pctT, $pctP, $pctE);
                $stmtIns->execute();
            }
        }

        // -------- TAREAS ----------
        if (!empty($post['nota_tarea']) && is_array($post['nota_tarea'])) {
            foreach ($post['nota_tarea'] as $idAlumno => $tareasAlumno) {
                $idAlumno = (int)$idAlumno;

                foreach ($tareasAlumno as $idTarea => $nota) {
                    $idTarea = (int)$idTarea;
                    $nota    = trim((string)$nota);

                    // si está vacío: no tocamos lo que ya había
                    if ($nota === '' || !is_numeric($nota)) {
                        continue;
                    }

                    $notaNum = max(0, min(10, (float)$nota));

                    // ¿Existe ya entrega para esa tarea/alumno?
                    $stmtSel = $conn->prepare("
                        SELECT id_entrega
                        FROM entregas_alumnos
                        WHERE id_tarea = ? AND id_alumno = ?
                        LIMIT 1
                    ");
                    $stmtSel->bind_param("ii", $idTarea, $idAlumno);
                    $stmtSel->execute();
                    $rowEnt = $stmtSel->get_result()->fetch_assoc();

                    if ($rowEnt) {
                        // UPDATE de la entrega existente
                        $stmtUpd = $conn->prepare("
                            UPDATE entregas_alumnos
                            SET calificacion = ?, estado = 'Calificada', fecha_calificacion = NOW()
                            WHERE id_entrega = ?
                        ");
                        $stmtUpd->bind_param("di", $notaNum, $rowEnt['id_entrega']);
                        $stmtUpd->execute();
                    } else {
                        // INSERT de una nueva "entrega" solo para calificación
                        $stmtIns = $conn->prepare("
                            INSERT INTO entregas_alumnos
                                (id_tarea, id_alumno, archivo, fecha_entrega, fecha_calificacion, calificacion, estado, retroalimentacion)
                            VALUES (?, ?, NULL, NOW(), NOW(), ?, 'Calificada', NULL)
                        ");
                        $stmtIns->bind_param("iid", $idTarea, $idAlumno, $notaNum);
                        $stmtIns->execute();
                    }
                }
            }
        }

        // -------- EVALUACIONES / PROYECTOS ----------
        if (!empty($post['nota_eval']) && is_array($post['nota_eval'])) {
            foreach ($post['nota_eval'] as $idAlumno => $evalsAlumno) {
                $idAlumno = (int)$idAlumno;

                foreach ($evalsAlumno as $idEval => $nota) {
                    $idEval = (int)$idEval;
                    $nota   = trim((string)$nota);

                    if ($nota === '' || !is_numeric($nota)) {
                        continue;
                    }

                    $notaNum = max(0, min(10, (float)$nota));

                    // ¿ya hay entrega de ese proyecto/evaluación?
                    $stmtSel = $conn->prepare("
                        SELECT id_entrega
                        FROM entregas_evaluaciones_alumnos
                        WHERE id_evaluacion = ? AND id_alumno = ?
                        LIMIT 1
                    ");
                    $stmtSel->bind_param("ii", $idEval, $idAlumno);
                    $stmtSel->execute();
                    $rowEnt = $stmtSel->get_result()->fetch_assoc();

                    if ($rowEnt) {
                        $stmtUpd = $conn->prepare("
                            UPDATE entregas_evaluaciones_alumnos
                            SET calificacion = ?, estado = 'Calificada'
                            WHERE id_entrega = ?
                        ");
                        $stmtUpd->bind_param("di", $notaNum, $rowEnt['id_entrega']);
                        $stmtUpd->execute();
                    } else {
                        // nueva fila, sin archivo, solo calificación
                        $stmtIns = $conn->prepare("
                            INSERT INTO entregas_evaluaciones_alumnos
                                (id_evaluacion, id_alumno, archivo, fecha_entrega, estado, calificacion, retroalimentacion)
                            VALUES (?, ?, '', NOW(), 'Calificada', ?, NULL)
                        ");
                        $stmtIns->bind_param("iid", $idEval, $idAlumno, $notaNum);
                        $stmtIns->execute();
                    }
                }
            }
        }

        // -------- EXÁMENES ----------
        $tableCheckExCal = $conn->query("SHOW TABLES LIKE 'examen_calificaciones'");
        if ($tableCheckExCal && $tableCheckExCal->num_rows > 0) {
            if (!empty($post['nota_examen']) && is_array($post['nota_examen'])) {
                foreach ($post['nota_examen'] as $idAlumno => $examsAlumno) {
                    $idAlumno = (int)$idAlumno;

                    foreach ($examsAlumno as $idExamen => $nota) {
                        $idExamen = (int)$idExamen;
                        $nota     = trim((string)$nota);

                        if ($nota === '' || !is_numeric($nota)) {
                            continue;
                        }

                        $notaNum = max(0, min(10, (float)$nota));

                        $stmt = $conn->prepare("
                            INSERT INTO examen_calificaciones (id_examen, id_alumno, calificacion)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion)
                        ");
                        $stmt->bind_param("iid", $idExamen, $idAlumno, $notaNum);
                        $stmt->execute();
                    }
                }
            }
        }

        return [
            'success' => true,
            'mensaje' => '✅ Porcentajes y calificaciones guardados.'
        ];
    }
}
