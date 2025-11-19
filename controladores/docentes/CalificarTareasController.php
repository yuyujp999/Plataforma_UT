<?php
class CalificarTareasController
{
    public static function obtenerEntregasPorMateria($idAsignacion)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
        SELECT 
            e.id_entrega, e.archivo, e.fecha_entrega,
            e.calificacion, e.retroalimentacion, e.estado,
            a.nombre AS nombre_alumno, a.apellido_paterno,
            t.titulo AS titulo_tarea
        FROM entregas_alumnos e
        INNER JOIN tareas_materias t ON e.id_tarea = t.id_tarea
        INNER JOIN alumnos a ON e.id_alumno = a.id_alumno
        WHERE t.id_asignacion_docente = ?
        ORDER BY e.fecha_entrega DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idAsignacion);
        $stmt->execute();
        return $stmt->get_result();
    }

    public static function calificarEntrega($idEntrega, $calificacion, $retroalimentacion)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmt = $conn->prepare("
            UPDATE entregas_alumnos
            SET calificacion = ?, retroalimentacion = ?, estado = 'Calificada'
            WHERE id_entrega = ?
        ");
        $stmt->bind_param("dsi", $calificacion, $retroalimentacion, $idEntrega);
        $stmt->execute();

        return $stmt->affected_rows > 0
            ? "✅ Entrega calificada correctamente."
            : "⚠️ No se pudo actualizar la calificación.";
    }

    public static function devolverEntrega($idEntrega, $retroalimentacion)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmt = $conn->prepare("
            UPDATE entregas_alumnos
            SET retroalimentacion = ?, estado = 'Devuelta', calificacion = NULL
            WHERE id_entrega = ?
        ");
        $stmt->bind_param("si", $retroalimentacion, $idEntrega);
        $stmt->execute();

        return $stmt->affected_rows > 0
            ? "📤 La tarea fue devuelta al alumno para corrección."
            : "⚠️ No se pudo devolver la tarea.";
    }

    /**
     * 🔹 Listado general del docente
     * - Muestra solo entregas existentes (INNER JOIN)
     * - Oculta entregas con estado 'Calificada'
     * - Ordena por prioridad: Devuelta (0) > Reentregada (1) > otras (2),
     *   luego por fecha de envío más reciente.
     */
    public static function obtenerTodasLasEntregasDocente($idDocente)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
        SELECT 
            t.id_tarea,
            t.titulo AS titulo_tarea,
            t.fecha_entrega AS fecha_limite,
            m.nombre_materia,
            a.nombre AS nombre_alumno,
            a.apellido_paterno,
            e.id_entrega,
            e.fecha_entrega AS fecha_envio,
            e.archivo,
            e.estado,
            e.calificacion
        FROM tareas_materias t
        INNER JOIN asignaciones_docentes ad
            ON t.id_asignacion_docente = ad.id_asignacion_docente
        INNER JOIN asignar_materias am
            ON ad.id_nombre_materia = am.id_nombre_materia
        INNER JOIN materias m
            ON am.id_materia = m.id_materia
        INNER JOIN entregas_alumnos e              -- solo entregas existentes
            ON e.id_tarea = t.id_tarea
        LEFT JOIN alumnos a
            ON e.id_alumno = a.id_alumno
        WHERE ad.id_docente = ?
          AND (e.estado IS NULL OR e.estado <> 'Calificada')  -- ocultar calificadas
        ORDER BY 
          CASE 
            WHEN e.estado = 'Devuelta' THEN 0
            WHEN e.estado = 'Reentregada' THEN 1
            ELSE 2
          END,
          e.fecha_entrega DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idDocente);
        $stmt->execute();
        return $stmt->get_result();
    }
}
