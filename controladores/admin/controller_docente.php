<?php
// --- Configuración de errores ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al cliente

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../conexion/conexion.php';

// --- Pre-flight para CORS ---
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

$action = $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido.");
    }

    if (!$action) {
        throw new Exception("No se recibió acción.");
    }

    // Campos del docente
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
        'correo_personal',
        'nivel_estudios',
        'area_especialidad',
        'universidad_egreso',
        'cedula_profesional',
        'idiomas',
        'departamento',
        'puesto',
        'tipo_contrato',
        'fecha_ingreso',
        'num_empleado',
        'contacto_emergencia',
        'parentesco_emergencia',
        'telefono_emergencia'
    ];

    // ===== CREAR DOCENTE =====
    if ($action === 'create') {
        $data = [];
        foreach ($fields as $f)
            $data[$f] = trim($_POST[$f] ?? '');

        // Validar campos obligatorios
        foreach (['nombre', 'apellido_paterno', 'curp', 'rfc', 'fecha_nacimiento', 'sexo', 'nivel_estudios', 'departamento', 'puesto', 'tipo_contrato', 'fecha_ingreso'] as $ob) {
            if ($data[$ob] === '')
                throw new Exception("Faltan campos obligatorios: $ob");
        }

        $matricula = 'DOC' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);

        $stmt = $conn->prepare("
            INSERT INTO docentes (
                matricula, nombre, apellido_paterno, apellido_materno, curp, rfc, fecha_nacimiento, sexo,
                telefono, direccion, correo_personal, nivel_estudios, area_especialidad, universidad_egreso,
                cedula_profesional, idiomas, departamento, puesto, tipo_contrato, fecha_ingreso, num_empleado,
                contacto_emergencia, parentesco_emergencia, telefono_emergencia, password
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, ?,?)
        ");

        $stmt->bind_param(
            "sssssssssssssssssssssssss", // 25 "s"
            $matricula,
            $data['nombre'],
            $data['apellido_paterno'],
            $data['apellido_materno'],
            $data['curp'],
            $data['rfc'],
            $data['fecha_nacimiento'],
            $data['sexo'],
            $data['telefono'],
            $data['direccion'],
            $data['correo_personal'],
            $data['nivel_estudios'],
            $data['area_especialidad'],
            $data['universidad_egreso'],
            $data['cedula_profesional'],
            $data['idiomas'],
            $data['departamento'],
            $data['puesto'],
            $data['tipo_contrato'],
            $data['fecha_ingreso'],
            $data['num_empleado'],
            $data['contacto_emergencia'],
            $data['parentesco_emergencia'],
            $data['telefono_emergencia'],
            $password
        );

        if (!$stmt->execute())
            throw new Exception("Error al insertar docente: " . $stmt->error);
        $stmt->close();

        respuestaJSON("success", "Docente agregado correctamente.", ["matricula" => $matricula, "password" => $password]);
    }

    // ===== EDITAR DOCENTE =====
    if ($action === 'edit') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $updates = [];
        $params = [];
        $types = '';

        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $updates[] = "$f=?";
                $params[] = $_POST[$f];
                $types .= 's';
            }
        }

        if (empty($updates))
            throw new Exception("No hay campos para actualizar.");

        $types .= 'i';
        $params[] = $id_docente;

        $stmt = $conn->prepare("UPDATE docentes SET " . implode(',', $updates) . " WHERE id_docente=?");
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute())
            throw new Exception("Error al actualizar docente: " . $stmt->error);
        $stmt->close();

        // Traer matrícula y contraseña
        $stmt2 = $conn->prepare("SELECT matricula,password FROM docentes WHERE id_docente=?");
        $stmt2->bind_param("i", $id_docente);
        $stmt2->execute();
        $result = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        respuestaJSON("success", "Docente actualizado correctamente.", ["matricula" => $result['matricula'] ?? '', "password" => $result['password'] ?? '']);
    }

    // ===== ELIMINAR DOCENTE =====
    if ($action === 'delete') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("DELETE FROM docentes WHERE id_docente=?");
        $stmt->bind_param('i', $id_docente);
        if (!$stmt->execute())
            throw new Exception("Error al eliminar docente: " . $stmt->error);
        $stmt->close();

        respuestaJSON("success", "Docente eliminado correctamente.");
    }

    // ===== OBTENER DATOS DE UN DOCENTE =====
    if ($action === 'get') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("SELECT * FROM docentes WHERE id_docente=?");
        $stmt->bind_param("i", $id_docente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result)
            throw new Exception("Docente no encontrado.");

        respuestaJSON("success", "Docente encontrado", $result);
    }

    throw new Exception("Acción no válida.");

} catch (Exception $e) {
    respuestaJSON("error", $e->getMessage());
}

$conn->close();
?>