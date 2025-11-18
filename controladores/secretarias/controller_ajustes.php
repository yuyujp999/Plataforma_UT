<?php
// ===== CONTROLLER AJUSTES SECRETARÍAS =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../conexion/conexion.php';

// Verificar sesión básica
if (!isset($_SESSION['rol'], $_SESSION['usuario'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID de sesión no válido (sin datos de usuario)'
    ]);
    exit;
}

/*
    Ajusta esto al nombre REAL del id de secretaria en tu sesión.

    En tu proyecto has usado cosas como:
      $_SESSION['usuario']['id_secretaria']
*/
$id_usuario = intval($_SESSION['usuario']['id_secretaria'] ?? 0);

if ($id_usuario <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID de sesión no válido'
    ]);
    exit;
}

$accion = $_POST['accion'] ?? 'getDatos';

// Helper para detectar si el password ya está hasheado
function isHash($s)
{
    return (bool) preg_match('/^\$2[ayb]\$|^\$argon2i\$|^\$argon2id\$/', $s);
}

try {
    // Conexión PDO
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($accion) {

        // === Obtener datos de la secretaría ===
        case 'getDatos':
            $stmt = $pdo->prepare("
                SELECT nombre, apellido_paterno, apellido_materno 
                FROM secretarias 
                WHERE id_secretaria = ?
            ");
            $stmt->execute([$id_usuario]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Secretaría no encontrada']);
            }
            break;

        // === Actualizar datos de la secretaría ===
        case 'updateUsuario':
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
            $apellido_materno = trim($_POST['apellido_materno'] ?? '');

            if (!$nombre || !$apellido_paterno || !$apellido_materno) {
                echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE secretarias 
                SET nombre = ?, apellido_paterno = ?, apellido_materno = ? 
                WHERE id_secretaria = ?
            ");
            $stmt->execute([$nombre, $apellido_paterno, $apellido_materno, $id_usuario]);

            // Actualizar sesión
            $_SESSION['usuario']['nombre'] = $nombre;
            $_SESSION['usuario']['apellido_paterno'] = $apellido_paterno;
            $_SESSION['usuario']['apellido_materno'] = $apellido_materno;

            echo json_encode(['status' => 'success', 'message' => 'Datos actualizados correctamente']);
            break;

        // === Cambiar contraseña ===
        case 'cambiarPassword':
            $actual = $_POST['actual'] ?? '';
            $nueva = $_POST['nueva'] ?? '';

            if (!$actual || !$nueva) {
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT password FROM secretarias WHERE id_secretaria = ?");
            $stmt->execute([$id_usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['status' => 'error', 'message' => 'Secretaría no encontrada']);
                exit;
            }

            $stored = $row['password'];

            $passOk = false;
            if ($stored !== '' && isHash($stored)) {
                // Ya está hasheado
                $passOk = password_verify($actual, $stored);
            } else {
                // Password en texto plano (compatibilidad con sistemas viejos)
                $passOk = ($actual === $stored);
            }

            if (!$passOk) {
                echo json_encode(['status' => 'error', 'message' => 'Contraseña actual incorrecta']);
                exit;
            }

            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE secretarias SET password = ? WHERE id_secretaria = ?");
            $stmt->execute([$hash, $id_usuario]);

            echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada correctamente']);
            break;

        // === Eliminar cuenta de secretaría ===
        case 'eliminarCuenta':
            $stmt = $pdo->prepare("DELETE FROM secretarias WHERE id_secretaria = ?");
            $stmt->execute([$id_usuario]);

            // Cerrar sesión
            session_unset();
            session_destroy();

            echo json_encode(['status' => 'success', 'message' => 'Cuenta eliminada correctamente']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión o consulta: ' . $e->getMessage()
    ]);
}