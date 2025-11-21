<?php
// /controladores/docentes/CalificacionesController.php

class CalificacionesController
{
    /** Materias/asignaciones del docente (igual que en ExamenesController) */
    public static function obtenerAsignacionesDocente(int $idDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
        SELECT 
          ad.id_asignacion_docente,
          m.nombre_materia AS materia
        FROM asignaciones_docentes ad
        INNER JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
        INNER JOIN materias m         ON am.id_materia = m.id_materia
        WHERE ad.id_docente = ?
        ORDER BY m.nombre_materia ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idDocente);
        $stmt->execute();
        $res = $stmt->get_result();

        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Construye la "matriz" de calificaciones para una asignación:
     * - alumnos del grupo
     * - tareas
     * - evaluaciones/proyectos
     * - exámenes
     * - calificaciones de cada uno
     */
    public static function obtenerMatriz(
        int $idDocente,
        int $idAsignacionDocente
    ): array {
        include __DIR__ . '/../../conexion/conexion.php';

        /* 1) TAREAS de esa asignación */
        $sqlT = "
            SELECT id_tarea, titulo
            FROM tareas_materias
            WHERE id_asignacion_docente = ?
            ORDER BY fecha_entrega, id_tarea
        ";
        $stmtT = $conn->prepare($sqlT);
        $stmtT->bind_param("i", $idAsignacionDocente);
        $stmtT->execute();
        $resT = $stmtT->get_result();
        $tareas = [];
        while ($t = $resT->fetch_assoc()) {
            $tareas[] = $t;
        }

        /* 2) EVALUACIONES (proyectos finales, etc.) */
        $sqlEvals = "
            SELECT id_evaluacion, titulo, tipo
            FROM evaluaciones_docente
            WHERE id_asignacion_docente = ?
            ORDER BY fecha_cierre, id_evaluacion
        ";
        $stmtEv = $conn->prepare($sqlEvals);
        $stmtEv->bind_param("i", $idAsignacionDocente);
        $stmtEv->execute();
        $resEv = $stmtEv->get_result();
        $evaluaciones = [];
        while ($e = $resEv->fetch_assoc()) {
            $evaluaciones[] = $e;
        }

        /* 3) EXÁMENES en línea */
        $sqlExams = "
            SELECT id_examen, titulo
            FROM examenes
            WHERE id_asignacion_docente = ?
            ORDER BY fecha_cierre, id_examen
        ";
        $stmtEx = $conn->prepare($sqlExams);
        $stmtEx->bind_param("i", $idAsignacionDocente);
        $stmtEx->execute();
        $resEx = $stmtEx->get_result();
        $examenes = [];
        while ($ex = $resEx->fetch_assoc()) {
            $examenes[] = $ex;
        }

        /* 4) ALUMNOS del grupo de ESTA asignación (aunque no hayan entregado nada)
              asignaciones_docentes -> asignar_materias -> grupos -> asignaciones_grupo_alumno -> alumnos
        */
        $sqlAl = "
            SELECT DISTINCT
              a.id_alumno,
              a.matricula,
              a.nombre,
              a.apellido_paterno,
              a.apellido_materno
            FROM asignaciones_docentes ad
            INNER JOIN asignar_materias am
              ON ad.id_nombre_materia = am.id_nombre_materia
            INNER JOIN grupos g
              ON g.id_nombre_grupo = am.id_nombre_grupo_int
            INNER JOIN asignaciones_grupo_alumno aga
              ON aga.id_grupo = g.id_grupo
            INNER JOIN alumnos a
              ON a.id_alumno = aga.id_alumno
            WHERE ad.id_asignacion_docente = ?
            ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre
        ";

        $stmtAl = $conn->prepare($sqlAl);
        $stmtAl->bind_param("i", $idAsignacionDocente);
        $stmtAl->execute();
        $resAl = $stmtAl->get_result();

        $alumnos = [];
        while ($a = $resAl->fetch_assoc()) {
            $alumnos[] = $a;
        }

        /* 5) CALIFICACIONES de tareas */
        $sqlCalT = "
            SELECT ea.id_alumno, ea.id_tarea, ea.calificacion
            FROM entregas_alumnos ea
            INNER JOIN tareas_materias t ON ea.id_tarea = t.id_tarea
            WHERE t.id_asignacion_docente = ?
        ";
        $stmtCT = $conn->prepare($sqlCalT);
        $stmtCT->bind_param("i", $idAsignacionDocente);
        $stmtCT->execute();
        $resCT = $stmtCT->get_result();
        $calTareas = [];
        while ($c = $resCT->fetch_assoc()) {
            $calTareas[$c['id_alumno']][$c['id_tarea']] = $c['calificacion'];
        }

        /* 6) CALIFICACIONES de evaluaciones */
        $sqlCalEv = "
            SELECT eea.id_alumno, eea.id_evaluacion, eea.calificacion
            FROM entregas_evaluaciones_alumnos eea
            INNER JOIN evaluaciones_docente ev ON eea.id_evaluacion = ev.id_evaluacion
            WHERE ev.id_asignacion_docente = ?
        ";
        $stmtCEv = $conn->prepare($sqlCalEv);
        $stmtCEv->bind_param("i", $idAsignacionDocente);
        $stmtCEv->execute();
        $resCEv = $stmtCEv->get_result();
        $calEvals = [];
        while ($c = $resCEv->fetch_assoc()) {
            $calEvals[$c['id_alumno']][$c['id_evaluacion']] = $c['calificacion'];
        }

        /* 7) CALIFICACIONES de exámenes */
        $sqlCalEx = "
            SELECT ec.id_alumno, ec.id_examen, ec.calificacion
            FROM examen_calificaciones ec
            INNER JOIN examenes e ON ec.id_examen = e.id_examen
            WHERE e.id_asignacion_docente = ?
        ";
        $stmtCEx = $conn->prepare($sqlCalEx);
        $stmtCEx->bind_param("i", $idAsignacionDocente);
        $stmtCEx->execute();
        $resCEx = $stmtCEx->get_result();
        $calExams = [];
        while ($c = $resCEx->fetch_assoc()) {
            $calExams[$c['id_alumno']][$c['id_examen']] = $c['calificacion'];
        }

        return [
            'alumnos'      => $alumnos,
            'tareas'       => $tareas,
            'evaluaciones' => $evaluaciones,
            'examenes'     => $examenes,
            'cal_tareas'   => $calTareas,
            'cal_evals'    => $calEvals,
            'cal_exams'    => $calExams,
        ];
    }

    /**
     * Guarda calificaciones que vengan del formulario:
     *  nota_tarea[alumno][tarea]
     *  nota_eval[alumno][evaluacion]
     *  nota_examen[alumno][examen]
     */
    public static function guardar(
        int $idDocente,
        int $idAsignacionDocente,
        array $post
    ): array {
        include __DIR__ . '/../../conexion/conexion.php';

        $conn->begin_transaction();
        try {
            /* TAREAS */
            if (!empty($post['nota_tarea']) && is_array($post['nota_tarea'])) {
                $sql = "
                    UPDATE entregas_alumnos ea
                    INNER JOIN tareas_materias t ON ea.id_tarea = t.id_tarea
                    SET ea.calificacion = ?, 
                        ea.estado = 'Calificada',
                        ea.fecha_calificacion = NOW()
                    WHERE ea.id_alumno = ? 
                      AND ea.id_tarea = ?
                      AND t.id_asignacion_docente = ?
                ";
                $stmt = $conn->prepare($sql);

                foreach ($post['nota_tarea'] as $idAlumno => $filas) {
                    foreach ($filas as $idTarea => $nota) {
                        $nota = trim((string)$nota);
                        if ($nota === '') continue;
                        $notaF    = floatval($nota);
                        $idAlumno = (int)$idAlumno;
                        $idTarea  = (int)$idTarea;
                        $stmt->bind_param("diii", $notaF, $idAlumno, $idTarea, $idAsignacionDocente);
                        $stmt->execute();
                    }
                }
            }

            /* EVALUACIONES / PROYECTOS */
            if (!empty($post['nota_eval']) && is_array($post['nota_eval'])) {
                $sql = "
                    UPDATE entregas_evaluaciones_alumnos eea
                    INNER JOIN evaluaciones_docente ev ON eea.id_evaluacion = ev.id_evaluacion
                    SET eea.calificacion = ?,
                        eea.estado = 'Calificada'
                    WHERE eea.id_alumno = ?
                      AND eea.id_evaluacion = ?
                      AND ev.id_asignacion_docente = ?
                ";
                $stmt = $conn->prepare($sql);

                foreach ($post['nota_eval'] as $idAlumno => $filas) {
                    foreach ($filas as $idEval => $nota) {
                        $nota = trim((string)$nota);
                        if ($nota === '') continue;
                        $notaF    = floatval($nota);
                        $idAlumno = (int)$idAlumno;
                        $idEval   = (int)$idEval;
                        $stmt->bind_param("diii", $notaF, $idAlumno, $idEval, $idAsignacionDocente);
                        $stmt->execute();
                    }
                }
            }

            /* EXÁMENES (tabla examen_calificaciones) */
            if (!empty($post['nota_examen']) && is_array($post['nota_examen'])) {
                $sql = "
                    INSERT INTO examen_calificaciones (id_examen, id_alumno, calificacion, fecha_calificacion)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                      calificacion = VALUES(calificacion),
                      fecha_calificacion = VALUES(fecha_calificacion)
                ";
                $stmt = $conn->prepare($sql);

                foreach ($post['nota_examen'] as $idAlumno => $filas) {
                    foreach ($filas as $idExamen => $nota) {
                        $nota = trim((string)$nota);
                        if ($nota === '') continue;
                        $notaF    = floatval($nota);
                        $idAlumno = (int)$idAlumno;
                        $idExamen = (int)$idExamen;
                        $stmt->bind_param("iid", $idExamen, $idAlumno, $notaF);
                        $stmt->execute();
                    }
                }
            }

            $conn->commit();
            return ['success' => true, 'mensaje' => '✅ Calificaciones guardadas.'];

        } catch (\Throwable $e) {
            $conn->rollback();
            return ['success' => false, 'mensaje' => '⚠️ Error al guardar calificaciones: '.$e->getMessage()];
        }
    }
}
