<?php
// --- Configuración de errores ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // no mostrar errores al cliente

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Requiere que tu conexion/conexion.php defina $conn (mysqli)
require_once __DIR__ . '/../../conexion/conexion.php';

// --- CORS preflight ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Helpers de respuesta JSON ---
function respuestaJSON($status, $message, $extra = [])
{
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}

// Manejadores globales de excepción/error
set_exception_handler(function ($e) {
    respuestaJSON("error", $e->getMessage());
});
set_error_handler(function ($severity, $message, $file, $line) {
    respuestaJSON("error", "Error: $message en $file:$line");
});

// --- Helpers de negocio ---
function soloDigitos($v)
{
    return preg_replace('/\D+/', '', (string) $v);
}

function generarPasswordPlano($len = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

/**
 * Genera una matrícula única con prefijo DOC y consecutivo de 4 dígitos.
 * Formato: DOC0001, DOC0002, ...
 */
function generarMatriculaUnica(mysqli $conn)
{
    $sql = "SELECT matricula FROM docentes WHERE matricula REGEXP '^DOC[0-9]{4}$' ORDER BY matricula DESC LIMIT 1";
    $res = $conn->query($sql);
    $next = 1;
    if ($res && $row = $res->fetch_assoc()) {
        $ult = (int) substr($row['matricula'], 3); // después de DOC
        $next = $ult + 1;
    }
    // en caso de colisión extrema, intenta unos cuantos
    for ($i = 0; $i < 50; $i++) {
        $mat = 'DOC' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM docentes WHERE matricula=?");
        $stmt->bind_param("s", $mat);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ((int) $count === 0) {
            return $mat;
        }
        $next++;
    }
    throw new Exception("No fue posible generar matrícula única.");
}

// --- Validación de método/acción ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON("error", "Método no permitido.");
}
$action = $_POST['action'] ?? '';
if (!$action) {
    respuestaJSON("error", "No se recibió acción.");
}

// ===== Campos del docente conforme a tu tabla ACTUAL =====
// (sin 'departamento' ni 'num_empleado')
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
    'puesto',
    'tipo_contrato',
    'fecha_ingreso',
    'contacto_emergencia',
    'parentesco_emergencia',
    'telefono_emergencia',
    // OJO: NO incluimos 'matricula' ni 'password' aquí para que no se editen directamente
];

try {
    // ====== CREATE ======
    if ($action === 'create') {
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }

        // Requeridos según tu tabla
        $obligatorios = [
            'nombre',
            'apellido_paterno',
            'curp',
            'rfc',
            'fecha_nacimiento',
            'sexo',
            'nivel_estudios',
            'puesto',
            'tipo_contrato',
            'fecha_ingreso'
        ];
        foreach ($obligatorios as $ob) {
            if (($data[$ob] ?? '') === '') {
                throw new Exception("Faltan campos obligatorios: $ob");
            }
        }

        // Solo dígitos en teléfonos
        $data['telefono'] = soloDigitos($data['telefono']);
        $data['telefono_emergencia'] = soloDigitos($data['telefono_emergencia']);

        // Generar matrícula y password
        $matricula = generarMatriculaUnica($conn);
        $passwordPlano = generarPasswordPlano(10);
        $passwordHash = password_hash($passwordPlano, PASSWORD_BCRYPT);

        // INSERT (sin id_docente/fecha_registro; MySQL los maneja)
        $stmt = $conn->prepare("
            INSERT INTO docentes (
                nombre, apellido_paterno, apellido_materno, curp, rfc, fecha_nacimiento, sexo,
                telefono, direccion, correo_personal, matricula, password, nivel_estudios,
                area_especialidad, universidad_egreso, cedula_profesional, idiomas, puesto,
                tipo_contrato, fecha_ingreso, contacto_emergencia, parentesco_emergencia, telefono_emergencia
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?,?,?,?,?,?,?,?,?, ?,?)
        ");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);

        // 23 parámetros tipo 's'
        $stmt->bind_param(
            "sssssssssssssssssssssss",
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
            $matricula,
            $passwordHash,
            $data['nivel_estudios'],
            $data['area_especialidad'],
            $data['universidad_egreso'],
            $data['cedula_profesional'],
            $data['idiomas'],
            $data['puesto'],
            $data['tipo_contrato'],
            $data['fecha_ingreso'],
            $data['contacto_emergencia'],
            $data['parentesco_emergencia'],
            $data['telefono_emergencia']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al insertar docente: " . $stmt->error);
        }
        $stmt->close();

        respuestaJSON("success", "Docente agregado correctamente.", [
            "matricula" => $matricula,
            "password_plano" => $passwordPlano
        ]);
    }

    // ====== EDIT ======
    if ($action === 'edit') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $updates = [];
        $params = [];
        $types = '';

        foreach ($fields as $f) {
            if (array_key_exists($f, $_POST)) {
                $val = trim($_POST[$f]);
                if (in_array($f, ['telefono', 'telefono_emergencia'], true)) {
                    $val = soloDigitos($val);
                }
                $updates[] = "$f=?";
                $params[] = $val;
                $types .= 's';
            }
        }

        if (empty($updates))
            throw new Exception("No hay campos para actualizar.");

        // WHERE id_docente
        $types .= 'i';
        $params[] = $id_docente;

        $sql = "UPDATE docentes SET " . implode(',', $updates) . " WHERE id_docente=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar docente: " . $stmt->error);
        }
        $stmt->close();

        // Devolver matrícula como referencia (password no se devuelve por seguridad)
        $stmt2 = $conn->prepare("SELECT matricula FROM docentes WHERE id_docente=?");
        $stmt2->bind_param("i", $id_docente);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        respuestaJSON("success", "Docente actualizado correctamente.", [
            "matricula" => $res['matricula'] ?? ''
        ]);
    }

    // ====== DELETE ======
    if ($action === 'delete') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("DELETE FROM docentes WHERE id_docente=?");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_docente);

        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar docente: " . $stmt->error);
        }
        $stmt->close();

        respuestaJSON("success", "Docente eliminado correctamente.");
    }

    // ====== GET ======
    if ($action === 'get') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("SELECT * FROM docentes WHERE id_docente=?");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param("i", $id_docente);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row)
            throw new Exception("Docente no encontrado.");

        // Por seguridad: no devolver el hash de password
        unset($row['password']);

        respuestaJSON("success", "Docente encontrado", $row);
    }

    // Acción no válida
    throw new Exception("Acción no válida.");

} catch (Exception $e) {
    respuestaJSON("error", $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}