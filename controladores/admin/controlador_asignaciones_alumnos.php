<?php
// controlador_asignaciones_alumnos.php
session_start();

// Mostrar errores solo para debug (quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Forzar JSON como salida
header('Content-Type: application/json; charset=utf-8');

// Verificar sesión
if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso.']);
    exit;
}

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// Obtener acción enviada por AJAX
$accion = $_POST['accion'] ?? '';

try {

    switch ($accion) {

        // =======================================
        // CREAR NUEVA ASIGNACIÓN
        // =======================================
        case 'crear':
            $id_alumno = $_POST['id_alumno'] ?? null;
            $id_carrera = $_POST['id_carrera'] ?? null;
            $id_grado = $_POST['id_grado'] ?? null;
            $id_ciclo = $_POST['id_ciclo'] ?? null;
            $grupo = $_POST['grupo'] ?? null;

            if (!$id_alumno || !$id_carrera || !$id_grado || !$id_ciclo) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO asignaciones_alumnos 
                (id_alumno, id_carrera, id_grado, id_ciclo, grupo, fecha_asignacion) 
                VALUES (:alumno, :carrera, :grado, :ciclo, :grupo, NOW())
            ");
            $stmt->execute([
                ':alumno' => $id_alumno,
                ':carrera' => $id_carrera,
                ':grado' => $id_grado,
                ':ciclo' => $id_ciclo,
                ':grupo' => $grupo
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Asignación creada correctamente']);
            exit;

        // =======================================
        // EDITAR ASIGNACIÓN
        // =======================================
        case 'editar':
            $id_asignacion = $_POST['id_asignacion'] ?? null;
            $id_alumno = $_POST['id_alumno'] ?? null;
            $id_carrera = $_POST['id_carrera'] ?? null;
            $id_grado = $_POST['id_grado'] ?? null;
            $id_ciclo = $_POST['id_ciclo'] ?? null;
            $grupo = $_POST['grupo'] ?? null;

            if (!$id_asignacion || !$id_alumno || !$id_carrera || !$id_grado || !$id_ciclo) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE asignaciones_alumnos 
                SET id_alumno=:alumno, id_carrera=:carrera, id_grado=:grado, id_ciclo=:ciclo, grupo=:grupo
                WHERE id_asignacion=:id
            ");
            $stmt->execute([
                ':alumno' => $id_alumno,
                ':carrera' => $id_carrera,
                ':grado' => $id_grado,
                ':ciclo' => $id_ciclo,
                ':grupo' => $grupo,
                ':id' => $id_asignacion
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Asignación actualizada correctamente']);
            exit;

        // =======================================
        // ELIMINAR ASIGNACIÓN
        // =======================================
        case 'eliminar':
            $id_asignacion = $_POST['id_asignacion'] ?? null;

            if (!$id_asignacion) {
                echo json_encode(['status' => 'error', 'message' => 'ID de asignación no proporcionado']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM asignaciones_alumnos WHERE id_asignacion=:id");
            $stmt->execute([':id' => $id_asignacion]);

            echo json_encode(['status' => 'success', 'message' => 'Asignación eliminada correctamente']);
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            exit;
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
}