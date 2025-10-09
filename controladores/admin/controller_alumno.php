<?php
// --- Configuración de errores ---
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

// --- Función para responder en JSON ---
function respuestaJSON($status, $message, $extra = [])
{
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}

// --- Manejadores de errores ---
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

    // === Campos del alumno (coinciden con la tabla) ===
    $fields = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'curp',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'direccion',
        'correo_personal',
        'carrera',
        'semestre',
        'grupo',
        'contacto_emergencia',
        'parentesco_emergencia',
        'telefono_emergencia'
    ];

    // ===== CREAR ALUMNO =====
    if ($action === 'create') {
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }

        // Validar campos obligatorios
        foreach (['nombre', 'apellido_paterno', 'curp', 'correo_personal', 'carrera', 'semestre'] as $ob) {
            if ($data[$ob] === '')
                throw new Exception("Faltan campos obligatorios: $ob");
        }

        // Generar matrícula y contraseña
        $matricula = 'AL' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);

        // Insertar en base de datos
        $stmt = $conn->prepare("
            INSERT INTO alumnos (
                matricula, nombre, apellido_paterno, apellido_materno, curp,
                fecha_nacimiento, sexo, telefono, direccion, correo_personal,
                carrera, semestre, grupo,
                contacto_emergencia, parentesco_emergencia, telefono_emergencia,
                password
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssssssssssssssss",
            $matricula,
            $data['nombre'],
            $data['apellido_paterno'],
            $data['apellido_materno'],
            $data['curp'],
            $data['fecha_nacimiento'],
            $data['sexo'],
            $data['telefono'],
            $data['direccion'],
            $data['correo_personal'],
            $data['carrera'],
            $data['semestre'],
            $data['grupo'],
            $data['contacto_emergencia'],
            $data['parentesco_emergencia'],
            $data['telefono_emergencia'],
            $password
        );

        if (!$stmt->execute())
            throw new Exception("Error al insertar alumno: " . $stmt->error);
        $stmt->close();

        respuestaJSON("success", "Alumno agregado correctamente.", [
            "matricula" => $matricula,
            "password" => $password
        ]);
    }

    // ===== EDITAR ALUMNO =====
    if ($action === 'edit') {
        $id_alumno = intval($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

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
        $params[] = $id_alumno;

        $stmt = $conn->prepare("UPDATE alumnos SET " . implode(',', $updates) . " WHERE id_alumno=?");
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute())
            throw new Exception("Error al actualizar alumno: " . $stmt->error);
        $stmt->close();

        // Obtener matrícula y contraseña actualizadas
        $stmt2 = $conn->prepare("SELECT matricula, password FROM alumnos WHERE id_alumno=?");
        $stmt2->bind_param("i", $id_alumno);
        $stmt2->execute();
        $result = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        respuestaJSON("success", "Alumno actualizado correctamente.", [
            "matricula" => $result['matricula'] ?? '',
            "password" => $result['password'] ?? ''
        ]);
    }

    // ===== ELIMINAR ALUMNO =====
    if ($action === 'delete') {
        $id_alumno = intval($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $stmt = $conn->prepare("DELETE FROM alumnos WHERE id_alumno=?");
        $stmt->bind_param('i', $id_alumno);

        if (!$stmt->execute())
            throw new Exception("Error al eliminar alumno: " . $stmt->error);
        $stmt->close();

        respuestaJSON("success", "Alumno eliminado correctamente.");
    }

    // ===== OBTENER DATOS DE UN ALUMNO =====
    if ($action === 'get') {
        $id_alumno = intval($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $stmt = $conn->prepare("SELECT * FROM alumnos WHERE id_alumno=?");
        $stmt->bind_param("i", $id_alumno);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result)
            throw new Exception("Alumno no encontrado.");

        respuestaJSON("success", "Alumno encontrado", $result);
    }

    // Si la acción no coincide
    throw new Exception("Acción no válida.");
} catch (Exception $e) {
    respuestaJSON("error", $e->getMessage());
}

$conn->close();
?>