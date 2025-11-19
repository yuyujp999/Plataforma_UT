<?php
class EntregasAlumnoController
{
    /**
     * 📤 Subir o actualizar entrega del alumno
     * Reglas:
     * - Si hay entrega previa con estado 'Devuelta' => guardar como 'Reentregada'
     * - Si no hay entrega previa => guardar como 'Entregada'
     * - Si ya está 'Calificada' (y no fue devuelta) => bloquear re-entrega
     * - En re-entrega se reinician calificacion y retroalimentacion
     */
    public static function subirEntrega($idTarea, $idAlumno, $archivo)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        if (!$idTarea || !$idAlumno || empty($archivo['tmp_name'])) {
            return ["success" => false, "mensaje" => "❌ Datos incompletos para subir la entrega."];
        }

        // 🗓️ Validar fecha límite + 7 días
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

        // 🔎 Verificar si ya existe una entrega previa
        $stmtCheck = $conn->prepare("
            SELECT id_entrega, estado, calificacion
            FROM entregas_alumnos
            WHERE id_tarea = ? AND id_alumno = ?
            ORDER BY fecha_entrega DESC
            LIMIT 1
        ");
        $stmtCheck->bind_param("ii", $idTarea, $idAlumno);
        $stmtCheck->execute();
        $prev = $stmtCheck->get_result()->fetch_assoc();

        // ⛔ Si ya está calificada y no fue devuelta, no permitimos reemplazar
        if ($prev && $prev['estado'] !== 'Devuelta' && $prev['calificacion'] !== null) {
            return ["success" => false, "mensaje" => "🔒 Esta tarea ya fue calificada. No es posible volver a entregar."];
        }

        // 📁 Carpeta de entregas
        $carpeta = __DIR__ . '/../../uploads/entregas/';
        if (!is_dir($carpeta)) @mkdir($carpeta, 0777, true);

        // 📦 Nombre único del archivo (conserva tu esquema)
        $nombreArchivo = uniqid('entrega_') . "_" . basename($archivo['name']);
        $rutaRelativa = 'uploads/entregas/' . $nombreArchivo;
        $rutaDestino  = __DIR__ . '/../../' . $rutaRelativa;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return ["success" => false, "mensaje" => "❌ Error al subir el archivo. Intenta de nuevo."];
        }

        // 🏷️ Estado a guardar
        $nuevoEstado = ($prev && $prev['estado'] === 'Devuelta') ? 'Reentregada' : 'Entregada';

        // 📝 Guardar (update si ya había, insert si es nueva)
        if ($prev) {
            $stmt = $conn->prepare("
                UPDATE entregas_alumnos 
                SET archivo = ?, fecha_entrega = NOW(), estado = ?, calificacion = NULL, retroalimentacion = NULL
                WHERE id_tarea = ? AND id_alumno = ?
            ");
            $stmt->bind_param("ssii", $rutaRelativa, $nuevoEstado, $idTarea, $idAlumno);
            $ok = $stmt->execute();
        } else {
            $stmt = $conn->prepare("
                INSERT INTO entregas_alumnos (id_tarea, id_alumno, archivo, fecha_entrega, estado)
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->bind_param("iiss", $idTarea, $idAlumno, $rutaRelativa, $nuevoEstado);
            $ok = $stmt->execute();
        }

        if (!$ok) {
            return ["success" => false, "mensaje" => "⚠️ No se pudo registrar la entrega."];
        }

        // ✅ Mensaje según el estado
        if ($nuevoEstado === 'Reentregada') {
            return ["success" => true, "mensaje" => "♻️ Re-entregada correctamente. Queda en revisión."];
        }
        return ["success" => true, "mensaje" => "✅ Entrega enviada correctamente."];
    }

    /**
     * 🔎 Obtener la entrega de un alumno en una tarea específica
     */
    public static function obtenerEntregaAlumno($idTarea, $idAlumno)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmt = $conn->prepare("
            SELECT id_entrega, archivo, fecha_entrega, calificacion, estado, retroalimentacion
            FROM entregas_alumnos
            WHERE id_tarea = ? AND id_alumno = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $idTarea, $idAlumno);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * 🧾 Listar todas las entregas del alumno (opcional)
     */
    public static function listarEntregasPorAlumno($idAlumno)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmt = $conn->prepare("
            SELECT e.id_entrega, e.archivo, e.fecha_entrega, e.calificacion, e.estado, e.retroalimentacion,
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
