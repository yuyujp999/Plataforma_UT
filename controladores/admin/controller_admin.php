<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../conexion/conexion.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

$accion = $_POST['accion'] ?? '';

switch ($accion) {

    // ====== AGREGAR ======
    case 'agregar':
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
        $apellido_materno = trim($_POST['apellido_materno'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$nombre || !$apellido_paterno || !$correo || !$password) {
            echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
            exit;
        }

        // 🔎 Verificar si el correo ya está registrado
        $check = $pdo->prepare("SELECT COUNT(*) FROM administradores WHERE correo = ?");
        $check->execute([$correo]);
        if ($check->fetchColumn() > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "No pueden haber usuarios repetidos. El correo ya existe."
            ]);
            exit;
        }

        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO administradores 
                (nombre, apellido_paterno, apellido_materno, correo, password, fecha_registro)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nombre, $apellido_paterno, $apellido_materno, $correo, $hashed]);

            echo json_encode(["status" => "success", "message" => "Administrador agregado correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al agregar: " . $e->getMessage()]);
        }
        break;

    // ====== EDITAR ======
    case 'editar':
        $id_admin = intval($_POST['id_admin'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
        $apellido_materno = trim($_POST['apellido_materno'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($id_admin <= 0 || !$nombre || !$apellido_paterno || !$correo) {
            echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
            exit;
        }

        // 🔎 Verificar si el nuevo correo ya está en uso por otro usuario
        $check = $pdo->prepare("SELECT COUNT(*) FROM administradores WHERE correo = ? AND id_admin != ?");
        $check->execute([$correo, $id_admin]);
        if ($check->fetchColumn() > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "No pueden haber usuarios repetidos. El correo ya está en uso por otro administrador."
            ]);
            exit;
        }

        try {
            if ($password) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    UPDATE administradores SET 
                    nombre=?, apellido_paterno=?, apellido_materno=?, correo=?, password=? 
                    WHERE id_admin=?
                ");
                $stmt->execute([$nombre, $apellido_paterno, $apellido_materno, $correo, $hashed, $id_admin]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE administradores SET 
                    nombre=?, apellido_paterno=?, apellido_materno=?, correo=? 
                    WHERE id_admin=?
                ");
                $stmt->execute([$nombre, $apellido_paterno, $apellido_materno, $correo, $id_admin]);
            }

            echo json_encode(["status" => "success", "message" => "Administrador actualizado correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $e->getMessage()]);
        }
        break;

    // ====== ELIMINAR ======
    case 'eliminar':
        $id = intval($_POST['id_admin'] ?? 0);
        if ($id <= 0) {
            echo json_encode(["status" => "error", "message" => "ID inválido."]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM administradores WHERE id_admin=?");
            $stmt->execute([$id]);
            echo json_encode(["status" => "success", "message" => "Administrador eliminado correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al eliminar: " . $e->getMessage()]);
        }
        break;

    // ====== LISTAR ======
    default:
        try {
            $stmt = $pdo->query("SELECT * FROM administradores ORDER BY id_admin DESC");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["status" => "success", "data" => $admins]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al listar: " . $e->getMessage()]);
        }
        break;
}
?>