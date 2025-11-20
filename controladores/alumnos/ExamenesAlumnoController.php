<?php
// /controladores/alumnos/ExamenesAlumnoController.php

class ExamenesAlumnoController
{
    /**
     * Exámenes disponibles para el alumno.
     * (Por ahora no filtramos por grupo, solo exámenes activos y no vencidos)
     */
    public static function obtenerExamenesDisponibles(int $idAlumno): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
            SELECT 
              e.id_examen,
              e.titulo,
              e.descripcion,
              e.fecha_cierre,
              e.estado,
              m.nombre_materia AS materia,
              d.nombre AS nombre_docente,
              d.apellido_paterno AS apellido_docente
            FROM examenes e
            INNER JOIN asignaciones_docentes ad 
                ON e.id_asignacion_docente = ad.id_asignacion_docente
            INNER JOIN asignar_materias am 
                ON ad.id_nombre_materia = am.id_nombre_materia
            INNER JOIN materias m 
                ON am.id_materia = m.id_materia
            INNER JOIN docentes d 
                ON ad.id_docente = d.id_docente
            WHERE e.estado = 'Activo'
              AND e.fecha_cierre >= CURDATE()
            ORDER BY e.fecha_cierre ASC
        ";

        $res = $conn->query($sql);
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Trae un examen con todas sus preguntas y opciones.
     */
    public static function obtenerExamenConPreguntas(int $idExamen): ?array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // Datos generales del examen
        $sqlEx = "
            SELECT 
              e.*,
              m.nombre_materia AS materia,
              d.nombre AS nombre_docente,
              d.apellido_paterno AS apellido_docente
            FROM examenes e
            INNER JOIN asignaciones_docentes ad 
                ON e.id_asignacion_docente = ad.id_asignacion_docente
            INNER JOIN asignar_materias am 
                ON ad.id_nombre_materia = am.id_nombre_materia
            INNER JOIN materias m 
                ON am.id_materia = m.id_materia
            INNER JOIN docentes d 
                ON ad.id_docente = d.id_docente
            WHERE e.id_examen = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sqlEx);
        $stmt->bind_param("i", $idExamen);
        $stmt->execute();
        $examen = $stmt->get_result()->fetch_assoc();

        if (!$examen) {
            return null;
        }

        // Preguntas + opciones
        $sqlPreg = "
            SELECT 
              p.id_pregunta,
              p.tipo,
              p.pregunta,
              o.id_opcion,
              o.texto_opcion AS opcion_texto
            FROM examen_preguntas p
            LEFT JOIN examen_pregunta_opciones o 
              ON o.id_pregunta = p.id_pregunta
            WHERE p.id_examen = ?
            ORDER BY p.orden, p.id_pregunta, o.id_opcion
        ";

        $stmt2 = $conn->prepare($sqlPreg);
        $stmt2->bind_param("i", $idExamen);
        $stmt2->execute();
        $res = $stmt2->get_result();

        $preguntas = [];
        while ($row = $res->fetch_assoc()) {
            $pid = (int)$row['id_pregunta'];

            if (!isset($preguntas[$pid])) {
                $preguntas[$pid] = [
                    'id_pregunta' => $pid,
                    'tipo'        => $row['tipo'],
                    'pregunta'    => $row['pregunta'],
                    'opciones'    => []
                ];
            }

            if (!empty($row['id_opcion'])) {
                $preguntas[$pid]['opciones'][] = [
                    'id_opcion' => (int)$row['id_opcion'],
                    'texto'     => $row['opcion_texto']
                ];
            }
        }

        $examen['preguntas'] = array_values($preguntas);
        return $examen;
    }

    /**
     * Guardar respuestas de un alumno.
     * Recibe:
     *   - resp_abierta[id_pregunta] = "texto..."
     *   - resp_opcion[id_pregunta]  = id_opcion
     */
    public static function guardarRespuestas(int $idExamen, int $idAlumno, array $data): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        if ($idExamen <= 0 || $idAlumno <= 0) {
            return ['success' => false, 'mensaje' => '❌ Datos inválidos.'];
        }

        // Eliminar respuestas previas (permite reenviar)
        $stmt = $conn->prepare("
            DELETE FROM examen_respuestas 
            WHERE id_examen = ? AND id_alumno = ?
        ");
        $stmt->bind_param("ii", $idExamen, $idAlumno);
        $stmt->execute();

        // Preguntas abiertas
        $abiertas = $data['resp_abierta'] ?? [];
        foreach ($abiertas as $idPregunta => $texto) {
            $texto = trim($texto);
            if ($texto === '') continue;

            $idPregunta = (int)$idPregunta;

            $stmt2 = $conn->prepare("
                INSERT INTO examen_respuestas (id_examen, id_alumno, id_pregunta, respuesta_texto)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->bind_param("iiis", $idExamen, $idAlumno, $idPregunta, $texto);
            $stmt2->execute();
        }

        // Preguntas de opción múltiple
        $opciones = $data['resp_opcion'] ?? [];
        foreach ($opciones as $idPregunta => $idOpcion) {
            $idPregunta = (int)$idPregunta;
            $idOpcion   = (int)$idOpcion;
            if ($idOpcion <= 0) continue;

            $stmt3 = $conn->prepare("
                INSERT INTO examen_respuestas (id_examen, id_alumno, id_pregunta, id_opcion)
                VALUES (?, ?, ?, ?)
            ");
            $stmt3->bind_param("iiii", $idExamen, $idAlumno, $idPregunta, $idOpcion);
            $stmt3->execute();
        }

        return ['success' => true, 'mensaje' => '✅ Examen enviado.'];
    }
}
