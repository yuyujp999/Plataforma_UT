<?php
// ===== CONTROLLER AJUSTES =====
if (session_status() === PHP_SESSION_NONE)
    session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../conexion/conexion.php';

// Verificar sesión
if (!isset($_SESSION['rol'], $_SESSION['usuario']['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID de sesión no válido'
    ]);
    exit;
}

$id_usuario = intval($_SESSION['usuario']['id']);
$accion = $_POST['accion'] ?? 'getDatos';

try {
    // Conexión PDO
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($accion) {

        // === Obtener datos del usuario ===
        case 'getDatos':
            $stmt = $pdo->prepare("SELECT nombre, apellido_paterno, apellido_materno FROM administradores WHERE id_admin = ?");
            $stmt->execute([$id_usuario]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            }
            break;

        // === Actualizar datos del usuario ===
        case 'updateUsuario':
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
            $apellido_materno = trim($_POST['apellido_materno'] ?? '');

            if (!$nombre || !$apellido_paterno || !$apellido_materno) {
                echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE administradores SET nombre=?, apellido_paterno=?, apellido_materno=? WHERE id_admin=?");
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

            $stmt = $pdo->prepare("SELECT password FROM administradores WHERE id_admin = ?");
            $stmt->execute([$id_usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
                exit;
            }

            $stored = $row['password'];

            function isHash($s)
            {
                return (bool) preg_match('/^\$2[ayb]\$|^\$argon2i\$|^\$argon2id\$/', $s);
            }

            $passOk = false;
            if ($stored !== '' && isHash($stored)) {
                $passOk = password_verify($actual, $stored);
            } else {
                $passOk = ($actual === $stored);
            }

            if (!$passOk) {
                echo json_encode(['status' => 'error', 'message' => 'Contraseña actual incorrecta']);
                exit;
            }

            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE administradores SET password = ? WHERE id_admin = ?");
            $stmt->execute([$hash, $id_usuario]);

            echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada correctamente']);
            break;

        // === Eliminar cuenta ===
        case 'eliminarCuenta':
            $stmt = $pdo->prepare("DELETE FROM administradores WHERE id_admin = ?");
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
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión o consulta: ' . $e->getMessage()]);
}