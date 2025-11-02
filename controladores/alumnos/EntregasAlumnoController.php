<?php
class EntregasAlumnoController
{
    /**
     * 📤 Subir o actualizar entrega del alumno
     */
    public static function subirEntrega($idTarea, $idAlumno, $archivo)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // 🗓️ Validar fecha límite
        $stmtFecha = $conn->prepare("SELECT fecha_entrega FROM tareas_materias WHERE id_tarea = ?");
        $stmtFecha->bind_param("i", $idTarea);
        $stmtFecha->execute();
        $resFecha = $stmtFecha->get_result()->fetch_assoc();

        if ($resFecha && $resFecha['fecha_entrega']) {
            $fechaLimite = new DateTime($resFecha['fecha_entrega']);
            $ahora = new DateTime('now', new DateTimeZone('America/Mexico_City'));
            if ($ahora > $fechaLimite->modify('+7 days')) {
                return ["success" => false, "mensaje" => "🚫 La fecha límite de entrega ya expiró."];
            }
        }

        // 📁 Carpeta de entregas
        $carpeta = __DIR__ . '/../../uploads/entregas/';
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

        // 📦 Nombre único del archivo
        $nombreArchivo = uniqid('entrega_') . "_" . basename($archivo['name']);
        $rutaRelativa = 'uploads/entregas/' . $nombreArchivo;
        $rutaDestino = __DIR__ . '/../../' . $rutaRelativa;

        // 🔄 Mover archivo al servidor
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return ["success" => false, "mensaje" => "❌ Error al subir el archivo. Intenta de nuevo."];
        }

        // 🧩 Verificar si ya existe una entrega previa del alumno
        $stmtCheck = $conn->prepare("SELECT id_entrega FROM entregas_alumnos WHERE id_tarea = ? AND id_alumno = ?");
        $stmtCheck->bind_param("ii", $idTarea, $idAlumno);
        $stmtCheck->execute();
        $resultado = $stmtCheck->get_result();

        if ($resultado->num_rows > 0) {
            // 🔁 Actualizar entrega existente
            $stmt = $conn->prepare("
                UPDATE entregas_alumnos 
                SET archivo = ?, fecha_entrega = NOW(), estado = 'Actualizada'
                WHERE id_tarea = ? AND id_alumno = ?
            ");
            $stmt->bind_param("sii", $rutaRelativa, $idTarea, $idAlumno);
            $stmt->execute();
            return ["success" => true, "mensaje" => "✅ Entrega actualizada correctamente."];
        } else {
            // 🆕 Nueva entrega
            $stmt = $conn->prepare("
                INSERT INTO entregas_alumnos (id_tarea, id_alumno, archivo, fecha_entrega, estado)
                VALUES (?, ?, ?, NOW(), 'Entregada')
            ");
            $stmt->bind_param("iis", $idTarea, $idAlumno, $rutaRelativa);
            $stmt->execute();
            return ["success" => true, "mensaje" => "✅ Entrega enviada correctamente."];
        }
    }

    /**
     * 🔎 Obtener la entrega de un alumno en una tarea específica
     */
    public static function obtenerEntregaAlumno($idTarea, $idAlumno)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmt = $conn->prepare("
            SELECT id_entrega, archivo, fecha_entrega, calificacion
            FROM entregas_alumnos
            WHERE id_tarea = ? AND id_alumno = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $idTarea, $idAlumno);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * 🧾 Obtener todas las entregas del alumno (si lo necesitas en otro módulo)
     */
    public static function listarEntregasPorAlumno($idAlumno)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmt = $conn->prepare("
            SELECT e.id_entrega, e.archivo, e.fecha_entrega, e.calificacion, 
                   t.titulo AS titulo_tarea, t.id_tarea
            FROM entregas_alumnos e
            INNER JOIN tareas_materias t ON e.id_tarea = t.id_tarea
            WHERE e.id_alumno = ?
            ORDER BY e.fecha_entrega DESC
        ");
        $stmt->bind_param("i", $idAlumno);
        $stmt->execute();
        return $stmt->get_result();
    }
}
