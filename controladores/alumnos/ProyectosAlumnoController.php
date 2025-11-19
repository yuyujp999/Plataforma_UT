<?php
// /controladores/alumnos/ProyectosAlumnoController.php
class ProyectosAlumnoController
{
    private static function nowMx(): DateTime {
        return new DateTime('now', new DateTimeZone('America/Mexico_City'));
    }

    private static function allowExt($name): bool {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['pdf','doc','docx','ppt','pptx','jpg','jpeg','png','zip','rar'], true);
    }

    private static function uploadDir(): string {
        return __DIR__ . '/../../uploads/proyectos_alumnos/';
    }

    /** Lista las evaluaciones (proyectos) publicadas por docentes,
     *  con join a materias/docente y la entrega del alumno si existe.
     *  Mantengo tu estilo: NO filtro por inscripción (igual que en tareas). */
    public static function obtenerProyectosAlumno(int $idAlumno)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
        SELECT
          e.id_evaluacion,
          e.titulo,
          e.tipo,
          e.descripcion,
          e.archivo AS archivo_docente,
          e.fecha_cierre,
          m.nombre_materia AS materia,
          d.nombre AS nombre_docente,
          d.apellido_paterno AS apellido_docente,
          ea.id_entrega,
          ea.archivo AS archivo_alumno,
          ea.fecha_entrega AS fecha_envio,
          ea.estado,
          ea.calificacion,
          ea.retroalimentacion
        FROM evaluaciones_docente e
        LEFT JOIN asignaciones_docentes ad ON e.id_asignacion_docente = ad.id_asignacion_docente
        LEFT JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
        LEFT JOIN materias m ON am.id_materia = m.id_materia
        LEFT JOIN docentes d ON ad.id_docente = d.id_docente
        LEFT JOIN entregas_evaluaciones_alumnos ea ON ea.id_evaluacion = e.id_evaluacion AND ea.id_alumno = ?
        ORDER BY e.fecha_cierre DESC, e.fecha_publicacion DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idAlumno);
        $stmt->execute();
        return $stmt->get_result();
    }

    /** Subir o re-subir entrega (se cierra duro en fecha_cierre). */
    public static function subirEntregaProyecto(int $idEvaluacion, int $idAlumno, array $archivo): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // 1) Validar fecha_cierre (cierre real, sin tolerancia)
        $q = $conn->prepare("SELECT fecha_cierre FROM evaluaciones_docente WHERE id_evaluacion = ? LIMIT 1");
        $q->bind_param("i", $idEvaluacion);
        $q->execute();
        $row = $q->get_result()->fetch_assoc();
        if (!$row) return ['success'=>false, 'mensaje'=>'❌ Evaluación no encontrada.'];

        $ahora = self::nowMx();
        $cierre = new DateTime($row['fecha_cierre'], new DateTimeZone('America/Mexico_City'));
        if ($ahora > $cierre) {
            return ['success'=>false, 'mensaje'=>'🚫 La entrega está cerrada.'];
        }

        // 2) Validar archivo
        if (empty($archivo['name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return ['success'=>false, 'mensaje'=>'⚠️ Selecciona un archivo válido.'];
        }
        if (!self::allowExt($archivo['name'])) {
            return ['success'=>false, 'mensaje'=>'⚠️ Formato no permitido.'];
        }

        // 3) Subir archivo
        $dir = self::uploadDir();
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $nombre = uniqid('proy_') . '_' . basename($archivo['name']);
        $dest   = $dir . $nombre;
        if (!move_uploaded_file($archivo['tmp_name'], $dest)) {
            return ['success'=>false, 'mensaje'=>'❌ Error al subir el archivo.'];
        }
        $rutaRel = 'uploads/proyectos_alumnos/' . $nombre;

        // 4) Insertar o actualizar entrega (manteniendo estado)
        $check = $conn->prepare("SELECT id_entrega, estado FROM entregas_evaluaciones_alumnos WHERE id_evaluacion = ? AND id_alumno = ?");
        $check->bind_param("ii", $idEvaluacion, $idAlumno);
        $check->execute();
        $prev = $check->get_result()->fetch_assoc();

        if ($prev) {
            // Si estaba Devuelta => Reentregada. Si no, Entregada.
            $nuevoEstado = ($prev['estado'] === 'Devuelta') ? 'Reentregada' : 'Entregada';
            $up = $conn->prepare("
              UPDATE entregas_evaluaciones_alumnos
              SET archivo = ?, fecha_entrega = NOW(), estado = ?, calificacion = NULL, retroalimentacion = NULL
              WHERE id_entrega = ?
            ");
            $up->bind_param("ssi", $rutaRel, $nuevoEstado, $prev['id_entrega']);
            $ok = $up->execute();
            return $ok
              ? ['success'=>true, 'mensaje'=>'✅ Entrega actualizada.']
              : ['success'=>false, 'mensaje'=>'⚠️ No se pudo actualizar la entrega.'];
        } else {
            $ins = $conn->prepare("
              INSERT INTO entregas_evaluaciones_alumnos (id_evaluacion, id_alumno, archivo, fecha_entrega, estado)
              VALUES (?, ?, ?, NOW(), 'Entregada')
            ");
            $ins->bind_param("iis", $idEvaluacion, $idAlumno, $rutaRel);
            $ok = $ins->execute();
            return $ok
              ? ['success'=>true, 'mensaje'=>'✅ Entrega enviada.']
              : ['success'=>false, 'mensaje'=>'⚠️ No se pudo guardar la entrega.'];
        }
    }
}
