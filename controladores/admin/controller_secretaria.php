<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../conexion/conexion.php';

// Pre-flight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para devolver JSON y salir
function respuestaJSON($status, $message, $extra = [])
{
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}

// Capturar errores y excepciones globalmente
set_exception_handler(function ($e) {
    respuestaJSON("error", $e->getMessage());
});
set_error_handler(function ($severity, $message, $file, $line) {
    respuestaJSON("error", "Error: $message en $file:$line");
});

// Obtener acción
$action = $_POST['action'] ?? '';
if (!$action)
    respuestaJSON("error", "No se recibió acción.");

// Campos de la secretaria
$fields = [
    'nombre',
    'apellido_paterno',
    'apellido_materno',
    'curp',
    'rfc',
    'fecha_nacimiento',
    'sexo',
    'telefono',
    'direccion',
    'departamento',
    'fecha_ingreso',
    'contacto_emergencia',
    'parentesco_emergencia',
    'telefono_emergencia'
];

try {
    // ===== CREAR SECRETARIA =====
    if ($action === 'create') {
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }

        // Validar campos obligatorios
        foreach (['nombre', 'apellido_paterno', 'curp', 'rfc', 'fecha_nacimiento', 'sexo', 'departamento', 'fecha_ingreso'] as $ob) {
            if ($data[$ob] === '')
                throw new Exception("Faltan campos obligatorios: $ob");
        }

        // Generar correo y contraseña
        $correo = strtolower($data['nombre'] . '.' . $data['apellido_paterno']) . '@institucional.com';
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);

        $sql = "INSERT INTO secretarias (
            nombre, apellido_paterno, apellido_materno, curp, rfc, fecha_nacimiento, sexo,
            telefono, direccion, correo_institucional, password, departamento, fecha_ingreso,
            contacto_emergencia, parentesco_emergencia, telefono_emergencia
        ) VALUES (
            :nombre,:apellido_paterno,:apellido_materno,:curp,:rfc,:fecha_nacimiento,:sexo,
            :telefono,:direccion,:correo_institucional,:password,:departamento,:fecha_ingreso,
            :contacto_emergencia,:parentesco_emergencia,:telefono_emergencia
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':apellido_paterno' => $data['apellido_paterno'],
            ':apellido_materno' => $data['apellido_materno'],
            ':curp' => $data['curp'],
            ':rfc' => $data['rfc'],
            ':fecha_nacimiento' => $data['fecha_nacimiento'],
            ':sexo' => $data['sexo'],
            ':telefono' => $data['telefono'],
            ':direccion' => $data['direccion'],
            ':correo_institucional' => $correo,
            ':password' => $password,
            ':departamento' => $data['departamento'],
            ':fecha_ingreso' => $data['fecha_ingreso'],
            ':contacto_emergencia' => $data['contacto_emergencia'],
            ':parentesco_emergencia' => $data['parentesco_emergencia'],
            ':telefono_emergencia' => $data['telefono_emergencia']
        ]);

        respuestaJSON("success", "Secretaria agregada correctamente.", ["correo" => $correo, "password" => $password]);
    }

    // ===== EDITAR SECRETARIA =====
    if ($action === 'edit') {
        $id = intval($_POST['id_secretaria'] ?? 0);
        if ($id <= 0)
            throw new Exception("ID secretaria inválido.");

        $updates = [];
        $params = [];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $updates[] = "$f=:$f";
                $params[":$f"] = $_POST[$f];
            }
        }

        if (empty($updates))
            throw new Exception("No hay campos para actualizar.");
        $params[":id"] = $id;

        $sql = "UPDATE secretarias SET " . implode(',', $updates) . " WHERE id_secretaria=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Obtener correo y password
        $stmt2 = $pdo->prepare("SELECT correo_institucional,password FROM secretarias WHERE id_secretaria=:id");
        $stmt2->execute([':id' => $id]);
        $result = $stmt2->fetch(PDO::FETCH_ASSOC);

        respuestaJSON("success", "Secretaria actualizada correctamente.", ["correo" => $result['correo_institucional'], "password" => $result['password']]);
    }

    // ===== ELIMINAR SECRETARIA =====
    if ($action === 'delete') {
        $id = intval($_POST['id_secretaria'] ?? 0);
        if ($id <= 0)
            throw new Exception("ID secretaria inválido.");

        $stmt = $pdo->prepare("DELETE FROM secretarias WHERE id_secretaria=:id");
        $stmt->execute([':id' => $id]);

        respuestaJSON("success", "Secretaria eliminada correctamente.");
    }

    // ===== OBTENER SECRETARIA =====
    if ($action === 'get') {
        $id = intval($_POST['id_secretaria'] ?? 0);
        if ($id <= 0)
            throw new Exception("ID secretaria inválido.");

        $stmt = $pdo->prepare("SELECT * FROM secretarias WHERE id_secretaria=:id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result)
            throw new Exception("Secretaria no encontrada.");

        respuestaJSON("success", "Secretaria encontrada", $result);
    }

    throw new Exception("Acción no válida.");

} catch (Exception $e) {
    respuestaJSON("error", $e->getMessage());
}
?>