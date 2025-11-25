<?php
// /controladores/docentes/AsistenciasController.php

class AsistenciasController
{
    /**
     * Obtener mapa de asistencias por alumno para un grupo + materia + fecha.
     * Devuelve: [id_alumno] => 'P'|'A'|'J'|'R'
     */
    public static function obtenerMapaDia(int $idGrupo, int $idAsignacionDocente, string $fecha): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $mapa = [];

        $stmt = $conn->prepare("
            SELECT id_alumno, estado
            FROM asistencias_alumnos
            WHERE id_grupo = ? AND id_asignacion_docente = ? AND fecha = ?
        ");
        $stmt->bind_param("iis", $idGrupo, $idAsignacionDocente, $fecha);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $mapa[(int)$row['id_alumno']] = $row['estado'];
        }

        return $mapa;
    }

    /**
     * Obtener todas las fechas donde ya hay asistencias guardadas
     * para un grupo + materia. Devuelve array de ['fecha' => 'YYYY-MM-DD'].
     */
    public static function obtenerFechasRegistradas(int $idGrupo, int $idAsignacionDocente): array
    {
        include __DIR__ . '/../../conexion/conexion.php';

        $fechas = [];

        $stmt = $conn->prepare("
            SELECT DISTINCT fecha
            FROM asistencias_alumnos
            WHERE id_grupo = ? AND id_asignacion_docente = ?
            ORDER BY fecha DESC
        ");
        $stmt->bind_param("ii", $idGrupo, $idAsignacionDocente);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $fechas[] = $row['fecha'];
        }

        return $fechas;
    }

    /**
     * Guardar asistencias de un día para un grupo + materia.
     * $estados: [id_alumno => 'P'|'A'|'J'|'R']
     */
    public static function guardarAsistencias(
        int $idDocente,
        int $idGrupo,
        int $idAsignacionDocente,
        string $fecha,
        array $estados
    ): array {
        include __DIR__ . '/../../conexion/conexion.php';

        // Validar que la asignación pertenece a este docente
        $stmtChk = $conn->prepare("
            SELECT 1
            FROM asignaciones_docentes
            WHERE id_asignacion_docente = ? AND id_docente = ?
            LIMIT 1
        ");
        $stmtChk->bind_param("ii", $idAsignacionDocente, $idDocente);
        $stmtChk->execute();
        $rowChk = $stmtChk->get_result()->fetch_assoc();
        if (!$rowChk) {
            return ['success' => false, 'mensaje' => 'No tienes permiso para registrar asistencia en esta materia.'];
        }

        // Normalizar fecha (YYYY-MM-DD)
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaObj) {
            return ['success' => false, 'mensaje' => 'Fecha inválida.'];
        }
        $fecha = $fechaObj->format('Y-m-d');

        // Guardar cada alumno
        $stmtIns = $conn->prepare("
            INSERT INTO asistencias_alumnos
                (id_alumno, id_asignacion_docente, id_grupo, fecha, estado)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE estado = VALUES(estado), updated_at = CURRENT_TIMESTAMP
        ");

        foreach ($estados as $idAlumno => $estado) {
            $idAlumno = (int)$idAlumno;
            $estado   = strtoupper(trim((string)$estado));

            if (!in_array($estado, ['P', 'A', 'J', 'R'], true)) {
                continue;
            }

            $stmtIns->bind_param("iiiss", $idAlumno, $idAsignacionDocente, $idGrupo, $fecha, $estado);
            $stmtIns->execute();
        }

        return ['success' => true, 'mensaje' => '✅ Asistencias guardadas correctamente.'];
    }

    /**
     * Manejador directo para llamadas vía POST desde el formulario.
     */
    public static function handlePost()
    {
        session_start();

        $rol = $_SESSION['rol'] ?? '';
        $idDocente = (int)($_SESSION['id_docente'] ?? ($_SESSION['usuario']['id_docente'] ?? 0));

        if ($rol !== 'docente' || !$idDocente) {
            http_response_code(403);
            echo "Acceso no autorizado.";
            return;
        }

        $idGrupo      = (int)($_POST['id_grupo'] ?? 0);
        $idAsignacion = (int)($_POST['id_asignacion_docente'] ?? 0);
        $fecha        = $_POST['fecha'] ?? date('Y-m-d');
        $estados      = $_POST['estado'] ?? [];

        $redirectController = "/Plataforma_UT/vistas/Docentes/dashboardgrupo.php";
        $redirectUrl = $redirectController . '?id=' . $idGrupo
                     . '&id_asignacion_docente=' . $idAsignacion
                     . '&fecha=' . urlencode($fecha);

        if ($idGrupo <= 0 || $idAsignacion <= 0) {
            $_SESSION['flash_asistencia'] = 'Faltan datos para guardar la asistencia.';
            header("Location: " . $redirectUrl);
            exit;
        }

        $res = self::guardarAsistencias($idDocente, $idGrupo, $idAsignacion, $fecha, $estados);

        $_SESSION['flash_asistencia'] = $res['mensaje'] ?? '';
        header("Location: " . $redirectUrl);
        exit;
    }
}

/* Si se llama este archivo directamente por POST */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AsistenciasController::handlePost();
}
