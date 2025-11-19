<?php
// /controladores/docentes/EvaluacionesController.php
class EvaluacionesController
{
    /* ================= Utilidades ================= */

    private static function nowMx(): DateTime {
        return new DateTime('now', new DateTimeZone('America/Mexico_City'));
    }

    private static function allowExt(string $filename): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['pdf','zip'], true);
    }

    private static function uploadDir(): string {
        return __DIR__ . '/../../uploads/evaluaciones/';
    }

    private static function publicPath(string $relative): string {
        return '/Plataforma_UT/' . ltrim($relative, '/\\');
    }

    /* ================ Lecturas ==================== */

    // Materias/asignaciones del docente para vincular la evaluación
    public static function obtenerAsignacionesDocente(int $idDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
        SELECT 
          ad.id_asignacion_docente,
          m.nombre_materia AS materia
        FROM asignaciones_docentes ad
        INNER JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
        INNER JOIN materias m ON am.id_materia = m.id_materia
        WHERE ad.id_docente = ?
        ORDER BY m.nombre_materia ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idDocente);
        $stmt->execute();
        $res = $stmt->get_result();

        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }

    // Listado de evaluaciones del docente (con flag 'cerrada')
    public static function obtenerEvaluaciones(int $idDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
        SELECT 
          e.id_evaluacion,
          e.id_asignacion_docente,
          e.titulo,
          e.tipo,
          e.descripcion,
          e.archivo,
          e.fecha_publicacion,
          e.fecha_cierre,
          m.nombre_materia AS materia,
          CASE WHEN NOW() > e.fecha_cierre THEN 1 ELSE 0 END AS cerrada
        FROM evaluaciones_docente e
        LEFT JOIN asignaciones_docentes ad ON e.id_asignacion_docente = ad.id_asignacion_docente
        LEFT JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
        LEFT JOIN materias m ON am.id_materia = m.id_materia
        WHERE e.id_docente = ?
        ORDER BY e.fecha_cierre DESC, e.fecha_publicacion DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idDocente);
        $stmt->execute();
        $res = $stmt->get_result();

        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }

    private static function obtenerEvaluacionPorId(int $idDocente, int $idEvaluacion): ?array
    {
        include __DIR__ . '/../../conexion/conexion.php';
        $stmt = $conn->prepare("SELECT * FROM evaluaciones_docente WHERE id_docente = ? AND id_evaluacion = ? LIMIT 1");
        $stmt->bind_param("ii", $idDocente, $idEvaluacion);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r ?: null;
    }

    /* ================ Escrituras ================== */

    public static function crearEvaluacion(int $idDocente, array $data, array $file): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $titulo  = trim($data['titulo'] ?? '');
        $tipo    = trim($data['tipo'] ?? 'Proyecto Final');
        $desc    = trim($data['descripcion'] ?? '');
        $cierre  = trim($data['fecha_cierre'] ?? ''); // viene como YYYY-MM-DD
        $idAsig  = isset($data['id_asignacion_docente']) ? (int)$data['id_asignacion_docente'] : null;

        if ($titulo === '' || $cierre === '') {
            return ['success'=>false, 'mensaje'=>'❌ Título y fecha de cierre son obligatorios.'];
        }

        // ✅ Validar formato de fecha (solo fecha)
        $fechaObj = DateTime::createFromFormat('Y-m-d', $cierre);
        if (!$fechaObj) {
            return ['success'=>false, 'mensaje'=>'⚠️ La fecha de cierre no es válida.'];
        }
        // Guardamos como fin de día 23:59:59, pero el docente solo ve la fecha
        $cierreDateTime = $fechaObj->format('Y-m-d 23:59:59');

        // Validar archivo
        $rutaRel = null;
        if (!empty($file['name'])) {
            if (!self::allowExt($file['name'])) {
                return ['success'=>false, 'mensaje'=>'⚠️ Solo se permiten PDF o ZIP.'];
            }
            $dir = self::uploadDir();
            if (!is_dir($dir)) @mkdir($dir, 0777, true);
            $nombre = uniqid('eval_') . '_' . basename($file['name']);
            $dest   = $dir . $nombre;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                return ['success'=>false, 'mensaje'=>'❌ Error al subir el archivo.'];
            }
            $rutaRel = 'uploads/evaluaciones/' . $nombre;
        }

        $stmt = $conn->prepare("
          INSERT INTO evaluaciones_docente
            (id_docente, id_asignacion_docente, titulo, tipo, descripcion, archivo, fecha_publicacion, fecha_cierre)
          VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->bind_param(
            "iisssss",
            $idDocente,
            $idAsig,
            $titulo,
            $tipo,
            $desc,
            $rutaRel,
            $cierreDateTime
        );
        $ok = $stmt->execute();

        return $ok
          ? ['success'=>true, 'mensaje'=>'✅ Evaluación creada.']
          : ['success'=>false, 'mensaje'=>'⚠️ No se pudo crear la evaluación.'];
    }

    public static function actualizarEvaluacion(int $idDocente, int $idEvaluacion, array $data, array $file): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $actual = self::obtenerEvaluacionPorId($idDocente, $idEvaluacion);
        if (!$actual) return ['success'=>false, 'mensaje'=>'❌ Evaluación no encontrada.'];

        $titulo = trim($data['titulo'] ?? $actual['titulo']);
        $tipo   = trim($data['tipo'] ?? $actual['tipo']);
        $desc   = trim($data['descripcion'] ?? $actual['descripcion']);
        $idAsig = isset($data['id_asignacion_docente'])
            ? (int)$data['id_asignacion_docente']
            : (int)$actual['id_asignacion_docente'];

        // 📅 Fecha de cierre: si viene del formulario, es YYYY-MM-DD, si no, dejamos la que ya tenía
        if (!empty($data['fecha_cierre'])) {
            $cierreInput = trim($data['fecha_cierre']);
            $fechaObj = DateTime::createFromFormat('Y-m-d', $cierreInput);
            if (!$fechaObj) {
                return ['success'=>false, 'mensaje'=>'⚠️ La fecha de cierre no es válida.'];
            }
            $cierre = $fechaObj->format('Y-m-d 23:59:59');
        } else {
            $cierre = $actual['fecha_cierre'];
        }

        // Si sube nuevo archivo, reemplazar
        $rutaRel = $actual['archivo'];
        if (!empty($file['name'])) {
            if (!self::allowExt($file['name'])) {
                return ['success'=>false, 'mensaje'=>'⚠️ Solo se permiten PDF o ZIP.'];
            }
            $dir = self::uploadDir();
            if (!is_dir($dir)) @mkdir($dir, 0777, true);
            $nombre = uniqid('eval_') . '_' . basename($file['name']);
            $dest   = $dir . $nombre;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                return ['success'=>false, 'mensaje'=>'❌ Error al subir el archivo.'];
            }
            // Borrar el anterior si existía
            if (!empty($rutaRel)) {
                @unlink(__DIR__ . '/../../' . ltrim($rutaRel, '/\\'));
            }
            $rutaRel = 'uploads/evaluaciones/' . $nombre;
        }

        $stmt = $conn->prepare("
          UPDATE evaluaciones_docente
          SET id_asignacion_docente = ?, titulo = ?, tipo = ?, descripcion = ?, archivo = ?, fecha_cierre = ?
          WHERE id_evaluacion = ? AND id_docente = ?
        ");
        $stmt->bind_param(
            "isssssii",
            $idAsig,
            $titulo,
            $tipo,
            $desc,
            $rutaRel,
            $cierre,
            $idEvaluacion,
            $idDocente
        );
        $ok = $stmt->execute();

        return $ok
          ? ['success'=>true, 'mensaje'=>'✅ Evaluación actualizada.']
          : ['success'=>false, 'mensaje'=>'⚠️ No se pudo actualizar.'];
    }

    public static function eliminarEvaluacion(int $idDocente, int $idEvaluacion): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $actual = self::obtenerEvaluacionPorId($idDocente, $idEvaluacion);
        if (!$actual) return ['success'=>false, 'mensaje'=>'❌ Evaluación no encontrada.'];

        // Borrar archivo físico si existe
        if (!empty($actual['archivo'])) {
            @unlink(__DIR__ . '/../../' . ltrim($actual['archivo'], '/\\'));
        }

        $stmt = $conn->prepare("DELETE FROM evaluaciones_docente WHERE id_evaluacion = ? AND id_docente = ?");
        $stmt->bind_param("ii", $idEvaluacion, $idDocente);
        $ok = $stmt->execute();

        return $ok
          ? ['success'=>true, 'mensaje'=>'🗑️ Evaluación eliminada.']
          : ['success'=>false, 'mensaje'=>'⚠️ No se pudo eliminar.'];
    }

    /* ============== Modo POST directo (acción) ============== */
    public static function handlePostDirect()
    {
        session_start();
        $rol = $_SESSION['rol'] ?? '';
        $idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
        if ($rol !== 'docente' || !$idDocente) {
            http_response_code(403);
            echo "Acceso no autorizado.";
            return;
        }

        $accion = $_POST['accion'] ?? '';
        $msg = ['success'=>false, 'mensaje'=>'Acción no válida.'];

        if ($accion === 'subir') {
            $msg = self::crearEvaluacion($idDocente, $_POST, $_FILES['archivo'] ?? []);
        } elseif ($accion === 'editar') {
            $idEval = (int)($_POST['id_evaluacion'] ?? 0);
            $msg = self::actualizarEvaluacion($idDocente, $idEval, $_POST, $_FILES['archivo'] ?? []);
        } elseif ($accion === 'eliminar') {
            $idEval = (int)($_POST['id_evaluacion'] ?? 0);
            $msg = self::eliminarEvaluacion($idDocente, $idEval);
        }

        // Redirigir de vuelta con mensaje flash
        $_SESSION['flash_msg'] = $msg['mensaje'];
        header('Location: /Plataforma_UT/vistas/Docentes/evaluaciones.php');
        exit;
    }
}

/* Si se llama directamente este archivo vía POST (desde la vista) */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    EvaluacionesController::handlePostDirect();
}
