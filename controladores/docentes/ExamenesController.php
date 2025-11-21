<?php
// /controladores/docentes/ExamenesController.php

class ExamenesController
{
    /* =============== UTILIDADES BÁSICAS ================= */

    private static function nowMx(): DateTime {
        return new DateTime('now', new DateTimeZone('America/Mexico_City'));
    }

    private static function isAjax(): bool {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || isset($_POST['ajax']);
    }

    /* =============== LECTURAS ============================ */

    // Materias/asignaciones del docente para vincular el examen
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
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
        return $out;
    }

    // Listar todos los exámenes de un docente
    public static function listarExamenesDocente(int $idDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
            SELECT 
                e.id_examen,
                e.id_asignacion_docente,
                e.titulo,
                e.descripcion,
                e.fecha_cierre,
                e.estado,
                m.nombre_materia AS materia
            FROM examenes e
            LEFT JOIN asignaciones_docentes ad 
                ON e.id_asignacion_docente = ad.id_asignacion_docente
            LEFT JOIN asignar_materias am 
                ON ad.id_nombre_materia = am.id_nombre_materia
            LEFT JOIN materias m 
                ON am.id_materia = m.id_materia
            WHERE e.id_docente = ?
            ORDER BY e.fecha_cierre DESC, e.id_examen DESC
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

    // Examen con preguntas + opciones (para vistas y JSON)
    public static function obtenerExamenCompleto(int $idDocente, int $idExamen): ?array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // Examen
        $stmt = $conn->prepare("
            SELECT 
                e.*,
                m.nombre_materia AS materia
            FROM examenes e
            LEFT JOIN asignaciones_docentes ad 
                ON e.id_asignacion_docente = ad.id_asignacion_docente
            LEFT JOIN asignar_materias am 
                ON ad.id_nombre_materia = am.id_nombre_materia
            LEFT JOIN materias m 
                ON am.id_materia = m.id_materia
            WHERE e.id_docente = ? AND e.id_examen = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $idDocente, $idExamen);
        $stmt->execute();
        $ex = $stmt->get_result()->fetch_assoc();
        if (!$ex) return null;

        // Formato amigable de fecha para la vista/preview
        if (!empty($ex['fecha_cierre'])) {
            $dt = DateTime::createFromFormat('Y-m-d', $ex['fecha_cierre']);
            if ($dt) {
                $ex['fecha_cierre_formateada'] = $dt->format('d/m/Y');
            } else {
                $ex['fecha_cierre_formateada'] = $ex['fecha_cierre'];
            }
        } else {
            $ex['fecha_cierre_formateada'] = '';
        }

        // Preguntas
        $stmtQ = $conn->prepare("
            SELECT 
                p.id_pregunta,
                p.tipo,
                p.pregunta,
                p.puntos,
                p.orden
            FROM examen_preguntas p
            WHERE p.id_examen = ?
            ORDER BY p.orden ASC, p.id_pregunta ASC
        ");
        $stmtQ->bind_param("i", $idExamen);
        $stmtQ->execute();
        $resQ = $stmtQ->get_result();

        $preguntas = [];
        while ($q = $resQ->fetch_assoc()) {
            $preguntas[$q['id_pregunta']] = $q;
            $preguntas[$q['id_pregunta']]['opciones'] = [];
        }

        if (!empty($preguntas)) {
            $idsPreg = implode(',', array_keys($preguntas));
            $sqlOpt = "
                SELECT id_opcion, id_pregunta, texto_opcion, es_correcta
                FROM examen_pregunta_opciones
                WHERE id_pregunta IN ($idsPreg)
                ORDER BY id_opcion ASC
            ";
            $resO = $conn->query($sqlOpt);
            while ($o = $resO->fetch_assoc()) {
                $pid = (int)$o['id_pregunta'];
                if (isset($preguntas[$pid])) {
                    $preguntas[$pid]['opciones'][] = $o;
                }
            }
        }

        $ex['preguntas'] = array_values($preguntas);
        return $ex;
    }

    /* =============== EXÁMENES (CRUD) ===================== */

    public static function crearExamen(int $idDocente, array $data): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        try {
            $titulo      = trim($data['titulo'] ?? '');
            $desc        = trim($data['descripcion'] ?? '');
            $idAsig      = isset($data['id_asignacion_docente']) ? (int)$data['id_asignacion_docente'] : null;
            $fechaCierre = trim($data['fecha_cierre'] ?? '');
            $questionsJs = $data['questions_json'] ?? '[]';

            if ($titulo === '' || $fechaCierre === '') {
                return ['success'=>false, 'mensaje'=>'❌ El título y la fecha de cierre son obligatorios.'];
            }

            // 1) Crear examen
            $stmt = $conn->prepare("
                INSERT INTO examenes 
                    (id_docente, id_asignacion_docente, titulo, descripcion, fecha_cierre, estado)
                VALUES (?, ?, ?, ?, ?, 'Activo')
            ");
            $stmt->bind_param(
                "iisss",
                $idDocente,
                $idAsig,
                $titulo,
                $desc,
                $fechaCierre
            );
            $ok = $stmt->execute();
            if (!$ok) {
                return ['success'=>false, 'mensaje'=>'⚠️ No se pudo crear el examen.'];
            }

            $idExamen = $stmt->insert_id;

            // 2) Preguntas recibidas en JSON
            $questions = json_decode($questionsJs, true);
            if (is_array($questions)) {
                $orden = 1;
                foreach ($questions as $q) {
                    $tipo  = isset($q['tipo']) && $q['tipo'] === 'opcion' ? 'opcion' : 'abierta';
                    $texto = trim($q['pregunta'] ?? '');
                    if ($texto === '') continue;

                    $payload = [
                        'id_examen'       => $idExamen,
                        'tipo'            => $tipo,
                        'pregunta'        => $texto,
                        'puntos'          => 1,
                        'orden'           => $orden++,
                        'opciones'        => $q['opciones'] ?? [],
                        'opcion_correcta' => $q['correcta'] ?? null,
                    ];
                    self::crearPregunta($idDocente, $payload);
                }
            }

            return [
                'success'   => true,
                'mensaje'   => '✅ Examen creado correctamente.',
                'id_examen' => $idExamen
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'mensaje' => '⚠️ Error en el servidor: ' . $e->getMessage()
            ];
        }
    }

    public static function actualizarExamen(int $idDocente, int $idExamen, array $data): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $titulo      = trim($data['titulo'] ?? '');
        $desc        = trim($data['descripcion'] ?? '');
        $idAsig      = isset($data['id_asignacion_docente']) ? (int)$data['id_asignacion_docente'] : null;
        $fechaCierre = trim($data['fecha_cierre'] ?? '');
        $estado      = trim($data['estado'] ?? 'Activo');

        if ($titulo === '' || $fechaCierre === '') {
            return ['success'=>false, 'mensaje'=>'❌ El título y la fecha de cierre son obligatorios.'];
        }

        $stmt = $conn->prepare("
            UPDATE examenes
            SET id_asignacion_docente = ?, titulo = ?, descripcion = ?, 
                fecha_cierre = ?, estado = ?
            WHERE id_examen = ? AND id_docente = ?
        ");
        $stmt->bind_param(
            "issssii",
            $idAsig,
            $titulo,
            $desc,
            $fechaCierre,
            $estado,
            $idExamen,
            $idDocente
        );

        $ok = $stmt->execute();
        return $ok
            ? ['success'=>true, 'mensaje'=>'✅ Examen actualizado.']
            : ['success'=>false, 'mensaje'=>'⚠️ No se pudo actualizar el examen.'];
    }

    public static function eliminarExamen(int $idDocente, int $idExamen): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmtP = $conn->prepare("SELECT id_pregunta FROM examen_preguntas WHERE id_examen = ?");
        $stmtP->bind_param("i", $idExamen);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        $idsPreg = [];
        while ($r = $resP->fetch_assoc()) $idsPreg[] = (int)$r['id_pregunta'];

        if ($idsPreg) {
            $idsIn = implode(',', $idsPreg);
            $conn->query("DELETE FROM examen_pregunta_opciones WHERE id_pregunta IN ($idsIn)");
            $conn->query("DELETE FROM examen_preguntas WHERE id_pregunta IN ($idsIn)");
        }

        $stmt = $conn->prepare("DELETE FROM examenes WHERE id_examen = ? AND id_docente = ?");
        $stmt->bind_param("ii", $idExamen, $idDocente);
        $ok = $stmt->execute();

        return $ok
            ? ['success'=>true, 'mensaje'=>'🗑️ Examen eliminado.']
            : ['success'=>false, 'mensaje'=>'⚠️ No se pudo eliminar el examen.'];
    }

    /* =============== PREGUNTAS (CRUD) ==================== */

    public static function crearPregunta(int $idDocente, array $data): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $idExamen = isset($data['id_examen']) ? (int)$data['id_examen'] : 0;
        $tipo     = ($data['tipo'] ?? 'abierta') === 'opcion' ? 'opcion' : 'abierta';
        $pregunta = trim($data['pregunta'] ?? '');
        $puntos   = isset($data['puntos']) ? (float)$data['puntos'] : 1.0;
        $orden    = isset($data['orden']) ? (int)$data['orden'] : 0;

        if (!$idExamen || $pregunta === '') {
            return ['success'=>false, 'mensaje'=>'❌ Falta el examen o el texto de la pregunta.'];
        }

        $stmt = $conn->prepare("
            INSERT INTO examen_preguntas (id_examen, tipo, pregunta, puntos, orden)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issdi", $idExamen, $tipo, $pregunta, $puntos, $orden);
        $ok = $stmt->execute();
        if (!$ok) {
            return ['success'=>false, 'mensaje'=>'⚠️ No se pudo guardar la pregunta.'];
        }

        $idPregunta = $stmt->insert_id;

        if ($tipo === 'opcion') {
            $opciones     = $data['opciones'] ?? [];
            $idxCorrecta  = isset($data['opcion_correcta']) ? (int)$data['opcion_correcta'] : -1;

            $stmtOpt = $conn->prepare("
                INSERT INTO examen_pregunta_opciones (id_pregunta, texto_opcion, es_correcta)
                VALUES (?, ?, ?)
            ");

            foreach ($opciones as $idx => $texto) {
                $texto = trim($texto);
                if ($texto === '') continue;

                $esCorrecta = ($idx === $idxCorrecta) ? 1 : 0;
                $stmtOpt->bind_param("isi", $idPregunta, $texto, $esCorrecta);
                $stmtOpt->execute();
            }
        }

        return ['success'=>true, 'mensaje'=>'✅ Pregunta guardada.', 'id_pregunta'=>$idPregunta];
    }

    public static function actualizarPregunta(int $idDocente, array $data): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $idPregunta = isset($data['id_pregunta']) ? (int)$data['id_pregunta'] : 0;
        $tipo       = ($data['tipo'] ?? 'abierta') === 'opcion' ? 'opcion' : 'abierta';
        $pregunta   = trim($data['pregunta'] ?? '');
        $puntos     = isset($data['puntos']) ? (float)$data['puntos'] : 1.0;

        if (!$idPregunta || $pregunta === '') {
            return ['success'=>false, 'mensaje'=>'❌ Falta la pregunta o el ID.'];
        }

        $stmt = $conn->prepare("
            UPDATE examen_preguntas p
            INNER JOIN examenes e ON p.id_examen = e.id_examen
            SET p.tipo = ?, p.pregunta = ?, p.puntos = ?
            WHERE p.id_pregunta = ? AND e.id_docente = ?
        ");
        $stmt->bind_param("ssdii", $tipo, $pregunta, $puntos, $idPregunta, $idDocente);
        $ok = $stmt->execute();
        if (!$ok) {
            return ['success'=>false, 'mensaje'=>'⚠️ No se pudo actualizar la pregunta.'];
        }

        $conn->query("DELETE o FROM examen_pregunta_opciones o WHERE o.id_pregunta = ".(int)$idPregunta);

        if ($tipo === 'opcion') {
            $opciones    = $data['opciones'] ?? [];
            $idxCorrecta = isset($data['opcion_correcta']) ? (int)$data['opcion_correcta'] : -1;

            $stmtOpt = $conn->prepare("
                INSERT INTO examen_pregunta_opciones (id_pregunta, texto_opcion, es_correcta)
                VALUES (?, ?, ?)
            ");

            foreach ($opciones as $idx => $texto) {
                $texto = trim($texto);
                if ($texto === '') continue;

                $esCorrecta = ($idx === $idxCorrecta) ? 1 : 0;
                $stmtOpt->bind_param("isi", $idPregunta, $texto, $esCorrecta);
                $stmtOpt->execute();
            }
        }

        return ['success'=>true, 'mensaje'=>'✅ Pregunta actualizada.'];
    }

    public static function eliminarPregunta(int $idDocente, int $idPregunta): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $stmt = $conn->prepare("
            DELETE o FROM examen_pregunta_opciones o
            WHERE o.id_pregunta = ?
        ");
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();

        $stmt2 = $conn->prepare("
            DELETE p FROM examen_preguntas p
            INNER JOIN examenes e ON p.id_examen = e.id_examen
            WHERE p.id_pregunta = ? AND e.id_docente = ?
        ");
        $stmt2->bind_param("ii", $idPregunta, $idDocente);
        $ok = $stmt2->execute();

        return $ok
            ? ['success'=>true, 'mensaje'=>'🗑️ Pregunta eliminada.']
            : ['success'=>false, 'mensaje'=>'⚠️ No se pudo eliminar la pregunta.'];
    }

        /**
     * Lista de exámenes enviados por alumnos para este docente.
     * Un registro por (examen, alumno).
     */
    public static function listarEnviosDocente(int $idDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $sql = "
            SELECT
              e.id_examen,
              e.titulo,
              e.fecha_cierre,
              m.nombre_materia AS materia,
              a.id_alumno,
              a.matricula,
              a.nombre,
              a.apellido_paterno,
              MIN(r.fecha_envio) AS primera_respuesta,
              MAX(r.fecha_envio) AS ultima_respuesta,
              COUNT(DISTINCT r.id_pregunta) AS preguntas_respondidas
            FROM examen_respuestas r
            INNER JOIN examenes e
              ON r.id_examen = e.id_examen
            INNER JOIN asignaciones_docentes ad
              ON e.id_asignacion_docente = ad.id_asignacion_docente
            INNER JOIN asignar_materias am
              ON ad.id_nombre_materia = am.id_nombre_materia
            INNER JOIN materias m
              ON am.id_materia = m.id_materia
            INNER JOIN alumnos a
              ON r.id_alumno = a.id_alumno
            WHERE e.id_docente = ?
            GROUP BY e.id_examen, a.id_alumno
            ORDER BY e.fecha_cierre DESC, e.id_examen, a.apellido_paterno, a.nombre
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
     * Detalle de un examen respondido por un alumno:
     * - Preguntas
     * - Opciones
     * - Qué marcó el alumno
     * - Cuáles son correctas / incorrectas
     */
    public static function obtenerResultadoExamenAlumno(int $idDocente, int $idExamen, int $idAlumno): ?array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        // 1) Verificar que el examen exista y pertenezca al docente
        $stmtEx = $conn->prepare("
            SELECT 
              e.*,
              m.nombre_materia AS materia
            FROM examenes e
            INNER JOIN asignaciones_docentes ad
              ON e.id_asignacion_docente = ad.id_asignacion_docente
            INNER JOIN asignar_materias am
              ON ad.id_nombre_materia = am.id_nombre_materia
            INNER JOIN materias m
              ON am.id_materia = m.id_materia
            WHERE e.id_examen = ? AND e.id_docente = ?
            LIMIT 1
        ");
        $stmtEx->bind_param("ii", $idExamen, $idDocente);
        $stmtEx->execute();
        $ex = $stmtEx->get_result()->fetch_assoc();
        if (!$ex) return null;

        // Alumno
        $stmtAl = $conn->prepare("
            SELECT nombre, apellido_paterno, apellido_materno, matricula
            FROM alumnos
            WHERE id_alumno = ?
            LIMIT 1
        ");
        $stmtAl->bind_param("i", $idAlumno);
        $stmtAl->execute();
        $al = $stmtAl->get_result()->fetch_assoc();
        if (!$al) return null;

        // 2) Preguntas + respuestas + opciones
        $sqlPreg = "
            SELECT 
              p.id_pregunta,
              p.tipo,
              p.pregunta,
              p.puntos,
              p.orden,
              r.id_respuesta,
              r.respuesta_texto,
              r.id_opcion AS opcion_marcada,
              o.id_opcion,
              o.texto_opcion,
              o.es_correcta
            FROM examen_preguntas p
            LEFT JOIN examen_respuestas r
              ON r.id_pregunta = p.id_pregunta
             AND r.id_examen  = p.id_examen
             AND r.id_alumno  = ?
            LEFT JOIN examen_pregunta_opciones o
              ON o.id_pregunta = p.id_pregunta
            WHERE p.id_examen = ?
            ORDER BY p.orden ASC, p.id_pregunta ASC, o.id_opcion ASC
        ";

        $stmtP = $conn->prepare($sqlPreg);
        $stmtP->bind_param("ii", $idAlumno, $idExamen);
        $stmtP->execute();
        $resP = $stmtP->get_result();

        $preguntas = [];
        while ($row = $resP->fetch_assoc()) {
            $pid = (int)$row['id_pregunta'];

            if (!isset($preguntas[$pid])) {
                $preguntas[$pid] = [
                    'id_pregunta'     => $pid,
                    'tipo'            => $row['tipo'],
                    'pregunta'        => $row['pregunta'],
                    'puntos'          => (float)$row['puntos'],
                    'orden'           => (int)$row['orden'],
                    'respuesta_texto' => $row['respuesta_texto'],
                    'opcion_marcada'  => $row['opcion_marcada'] ? (int)$row['opcion_marcada'] : null,
                    'opciones'        => [],
                    'es_correcta'     => null, // lo calculamos abajo
                ];
            }

            if (!empty($row['id_opcion'])) {
                $preguntas[$pid]['opciones'][] = [
                    'id_opcion'    => (int)$row['id_opcion'],
                    'texto_opcion' => $row['texto_opcion'],
                    'es_correcta'  => (int)$row['es_correcta'],
                ];
            }
        }

        // 3) Calcular correctas / incorrectas (solo opción múltiple)
        $totalOpcion   = 0;
        $correctasO    = 0;

        foreach ($preguntas as $pid => &$p) {
            if ($p['tipo'] !== 'opcion') {
                $p['es_correcta'] = null; // abierta, no se califica automática
                continue;
            }

            $totalOpcion++;
            $marcada = $p['opcion_marcada'];
            $esCorrecta = false;

            foreach ($p['opciones'] as &$opt) {
                if ($opt['id_opcion'] === $marcada && $opt['es_correcta'] == 1) {
                    $esCorrecta = true;
                }
            }

            $p['es_correcta'] = $esCorrecta;
            if ($esCorrecta) {
                $correctasO++;
            }
        }
        unset($p);

        $stats = [
            'total_preguntas_opcion' => $totalOpcion,
            'correctas'              => $correctasO,
            'incorrectas'            => max(0, $totalOpcion - $correctasO),
            'porcentaje'             => $totalOpcion > 0 ? round($correctasO * 100.0 / $totalOpcion, 1) : null,
        ];

        return [
            'examen' => [
                'id_examen'    => (int)$ex['id_examen'],
                'titulo'       => $ex['titulo'],
                'materia'      => $ex['materia'],
                'fecha_cierre' => $ex['fecha_cierre'],
            ],
            'alumno' => [
                'id_alumno'  => $idAlumno,
                'matricula'  => $al['matricula'] ?? '',
                'nombre'     => $al['nombre'] ?? '',
                'apellidos'  => trim(($al['apellido_paterno'] ?? '') . ' ' . ($al['apellido_materno'] ?? '')),
            ],
            'stats'     => $stats,
            'preguntas' => array_values($preguntas),
        ];
    }

    /* =============== HANDLER DIRECTO (POST) =============== */

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

        switch ($accion) {
            case 'crear_examen':
            case 'nuevo_examen':
                $msg = self::crearExamen($idDocente, $_POST);
                break;

            case 'actualizar_examen':
            case 'editar_examen':
                $idEx = (int)($_POST['id_examen'] ?? 0);
                $msg = self::actualizarExamen($idDocente, $idEx, $_POST);
                break;

            case 'eliminar_examen':
                $idEx = (int)($_POST['id_examen'] ?? 0);
                $msg = self::eliminarExamen($idDocente, $idEx);
                break;

            case 'crear_pregunta':
            case 'guardar_pregunta':
            case 'add_pregunta':
                $msg = self::crearPregunta($idDocente, $_POST);
                break;

            case 'actualizar_pregunta':
            case 'editar_pregunta':
                $msg = self::actualizarPregunta($idDocente, $_POST);
                break;

            case 'eliminar_pregunta':
            case 'borrar_pregunta':
                $idP = (int)($_POST['id_pregunta'] ?? 0);
                $msg = self::eliminarPregunta($idDocente, $idP);
                break;

            case 'obtener_examen_json':
                header('Content-Type: application/json; charset=utf-8');

                $idEx = (int)($_POST['id_examen'] ?? 0);
                if (!$idEx) {
                    echo json_encode([
                        'success' => false,
                        'mensaje' => 'ID de examen no válido.'
                    ]);
                    return;
                }

                $ex = self::obtenerExamenCompleto($idDocente, $idEx);
                if (!$ex) {
                    echo json_encode([
                        'success' => false,
                        'mensaje' => 'Examen no encontrado o no pertenece al docente.'
                    ]);
                    return;
                }

                echo json_encode([
                    'success'   => true,
                    'examen'    => [
                        'id_examen'               => (int)$ex['id_examen'],
                        'titulo'                  => $ex['titulo'] ?? '',
                        'materia'                 => $ex['materia'] ?? '',
                        'descripcion'             => $ex['descripcion'] ?? '',
                        'fecha_cierre'            => $ex['fecha_cierre'] ?? '',
                        'fecha_cierre_formateada' => $ex['fecha_cierre_formateada'] ?? '',
                        'estado'                  => $ex['estado'] ?? '',
                    ],
                    'preguntas' => $ex['preguntas'] ?? []
                ]);
                return;
        }

        if (self::isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($msg);
            return;
        }

        $_SESSION['flash_msg'] = $msg['mensaje'];
        header('Location: /Plataforma_UT/vistas/Docentes/examenes.php');
        exit;
    }
}

/* Si se llama directamente este archivo vía POST */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ExamenesController::handlePostDirect();
}
