<?php
class CalificarTareasController
{
    public static function obtenerEntregasPorMateria($idAsignacion)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // 🔍 Buscar todas las tareas de esa asignación docente
        $sql = "
            SELECT 
                e.id_entrega,
                e.fecha_entrega,
                e.calificacion,
                e.estado,
                e.archivo,
                a.id_alumno,
                CONCAT(a.nombre, ' ', a.apellido_paterno, ' ', a.apellido_materno) AS nombre_alumno,
                t.titulo AS titulo_tarea
            FROM entregas_alumnos e
            INNER JOIN tareas_materias t ON e.id_tarea = t.id_tarea
            INNER JOIN alumnos a ON e.id_alumno = a.id_alumno
            INNER JOIN asignaciones_docentes ad ON t.id_asignacion_docente = ad.id_asignacion_docente
            WHERE ad.id_asignacion_docente = ?
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
            SET calificacion = ?, retroalimentacion = ?, estado = 'Calificado'
            WHERE id_entrega = ?
        ");
        $stmt->bind_param("dsi", $calificacion, $retroalimentacion, $idEntrega);
        $stmt->execute();

        return $stmt->affected_rows > 0
            ? "✅ Entrega calificada correctamente."
            : "⚠️ No se pudo actualizar la calificación.";
    }
}
