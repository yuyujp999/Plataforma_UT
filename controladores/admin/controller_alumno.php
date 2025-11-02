<?php
// --- Configuración ---
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../conexion/conexion.php'; // Debe exponer $conn (mysqli)

// --- Pre-flight para CORS ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Función para enviar respuesta JSON ---
function respuestaJSON($status, $message, $extra = [])
{
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}

// --- Manejadores de errores ---
set_exception_handler(function ($e) {
    respuestaJSON("error", $e->getMessage());
});
set_error_handler(function ($sev, $msg, $file, $line) {
    respuestaJSON("error", "Error: $msg en $file:$line");
});

// --- Helpers ---
function nullIfEmpty($v)
{
    $v = isset($v) ? trim((string) $v) : '';
    return ($v === '') ? null : $v;
}
function required($arr, $keys)
{
    foreach ($keys as $k) {
        if (!isset($arr[$k]) || trim($arr[$k]) === '') {
            throw new Exception("Faltan campos obligatorios: {$k}");
        }
    }
}
// Validaciones pedidas:
function validarTelefono($v, $campo)
{
    if ($v === null)
        return; // opcional
    if (!preg_match('/^\d{7,15}$/', $v)) {
        throw new Exception("{$campo}: ingresa solo dígitos (7 a 15).");
    }
}
function validarContactoNombre($v)
{
    if ($v === null)
        return; // opcional
    // Solo letras (incluye acentos), espacios y . ' -
    if (!preg_match("/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]{2,60}$/u", $v)) {
        throw new Exception("Contacto de emergencia: solo letras y espacios (2 a 60).");
    }
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON("error", "Método no permitido.");
}
if (!$action) {
    respuestaJSON("error", "No se recibió acción.");
}

try {
    // ====== CREAR ======
    if ($action === 'create') {
        // Campos esperados (id_nombre_semestre es CLAVE)
        $payload = [
            'nombre' => $_POST['nombre'] ?? '',
            'apellido_paterno' => $_POST['apellido_paterno'] ?? '',
            'apellido_materno' => $_POST['apellido_materno'] ?? '',
            'curp' => $_POST['curp'] ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
            'sexo' => $_POST['sexo'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'direccion' => $_POST['direccion'] ?? '',
            'correo_personal' => $_POST['correo_personal'] ?? '',
            'id_nombre_semestre' => $_POST['id_nombre_semestre'] ?? '',
            'contacto_emergencia' => $_POST['contacto_emergencia'] ?? '',
            'parentesco_emergencia' => $_POST['parentesco_emergencia'] ?? '',
            'telefono_emergencia' => $_POST['telefono_emergencia'] ?? '',
        ];

        // Requeridos
        required($payload, ['nombre', 'apellido_paterno', 'curp', 'fecha_nacimiento', 'sexo', 'id_nombre_semestre']);

        // Normalizar opcionales a null si vienen vacíos
        $apellido_materno = nullIfEmpty($payload['apellido_materno']);
        $telefono = nullIfEmpty($payload['telefono']);
        $direccion = nullIfEmpty($payload['direccion']);
        $correo_personal = nullIfEmpty($payload['correo_personal']);
        $id_nombre_semestre = (int) $payload['id_nombre_semestre']; // value del <select>
        $contacto_emergencia = nullIfEmpty($payload['contacto_emergencia']);
        $parentesco_emergencia = nullIfEmpty($payload['parentesco_emergencia']);
        $telefono_emergencia = nullIfEmpty($payload['telefono_emergencia']);

        // --- Validaciones solicitadas ---
        validarTelefono($telefono, 'Teléfono');
        validarTelefono($telefono_emergencia, 'Teléfono de emergencia');
        validarContactoNombre($contacto_emergencia);

        // --- Generar matrícula y contraseña ---
        // Lógica:
        //  - Buscar el máximo número existente en matriculas que comienzan con 'A' seguido de dígitos.
        //  - Incrementar en 1.
        //  - Si el número resultante <= 999: padding a 3 dígitos -> A001, A002...
        //    Si > 999: padding a 6 dígitos (para cubrir hasta 999999).
        //  - Contraseña: últimos 2 dígitos del bloque numérico + iniciales (nombre + apellido_paterno) en mayúsculas + "UT"

        $rsMax = $conn->query("SELECT MAX(CAST(SUBSTRING(matricula,2) AS UNSIGNED)) AS maxnum FROM alumnos WHERE matricula REGEXP '^A[0-9]+$'");
        if (!$rsMax) {
            throw new Exception("Error al calcular matrícula: " . $conn->error);
        }
        $rowMax = $rsMax->fetch_assoc();
        $maxNum = (int) ($rowMax['maxnum'] ?? 0);
        $nextNum = $maxNum + 1;
        if ($nextNum <= 0)
            $nextNum = 1;

        // Decidir padding: 3 dígitos para [1..999], 6 dígitos para >=1000 (permite hasta 999999)
        $padLength = ($nextNum <= 999) ? 3 : 6;
        $numStr = str_pad((string) $nextNum, $padLength, '0', STR_PAD_LEFT);
        $matricula = 'A' . $numStr;

        // Últimos 2 dígitos (numéricos) del bloque; si no hay suficientes, usamos ceros por la derecha/izquierda según substr
        $lastTwo = substr($numStr, -2);
        if ($lastTwo === false || $lastTwo === '') {
            $lastTwo = str_pad((string) $nextNum, 2, '0', STR_PAD_LEFT);
            $lastTwo = substr($lastTwo, -2);
        }

        // Iniciales: primera letra de nombre y primera letra de apellido_paterno, en mayúsculas (manejo multibyte)
        $nombreTrim = trim((string) $payload['nombre']);
        $apPTrim = trim((string) $payload['apellido_paterno']);
        $initialNombre = mb_strtoupper(mb_substr($nombreTrim, 0, 1, 'UTF-8'), 'UTF-8');
        $initialApP = mb_strtoupper(mb_substr($apPTrim, 0, 1, 'UTF-8'), 'UTF-8');
        $initials = $initialNombre . $initialApP;

        $password_plain = $lastTwo . $initials . 'UT';
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

        // Insert
        $sql = "INSERT INTO alumnos (
                    nombre, apellido_paterno, apellido_materno, curp,
                    fecha_nacimiento, sexo, telefono, direccion, correo_personal,
                    matricula, password, id_nombre_semestre,
                    contacto_emergencia, parentesco_emergencia, telefono_emergencia
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);

        $stmt->bind_param(
            "sssssssssssssss",
            $payload['nombre'],
            $payload['apellido_paterno'],
            $apellido_materno,
            $payload['curp'],
            $payload['fecha_nacimiento'],
            $payload['sexo'],
            $telefono,
            $direccion,
            $correo_personal,
            $matricula,
            $password_hash,
            $id_nombre_semestre,
            $contacto_emergencia,
            $parentesco_emergencia,
            $telefono_emergencia
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al agregar alumno: " . $stmt->error);
        }
        $nuevoId = $stmt->insert_id;
        $stmt->close();

        // Devolver también el nombre legible del semestre
        $rs = $conn->query("SELECT nombre FROM cat_nombres_semestre WHERE id_nombre_semestre = {$id_nombre_semestre} LIMIT 1");
        $nombre_semestre = $rs && $rs->num_rows ? ($rs->fetch_assoc()['nombre'] ?? '') : '';

        respuestaJSON("success", "Alumno agregado correctamente.", [
            "id_alumno" => $nuevoId,
            "matricula" => $matricula,
            "password" => $password_plain,
            "nombre_semestre" => $nombre_semestre
        ]);
    }

    // ====== EDITAR ======
    if ($action === 'edit') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        // Campos actualizables
        $permitidos = [
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'curp',
            'fecha_nacimiento',
            'sexo',
            'telefono',
            'direccion',
            'correo_personal',
            'id_nombre_semestre',
            'contacto_emergencia',
            'parentesco_emergencia',
            'telefono_emergencia'
        ];

        $updates = [];
        $types = '';
        $params = [];

        // Antes de bind, validamos si vienen presentes:
        if (array_key_exists('telefono', $_POST)) {
            $tel = nullIfEmpty($_POST['telefono']);
            validarTelefono($tel, 'Teléfono');
        }
        if (array_key_exists('telefono_emergencia', $_POST)) {
            $telE = nullIfEmpty($_POST['telefono_emergencia']);
            validarTelefono($telE, 'Teléfono de emergencia');
        }
        if (array_key_exists('contacto_emergencia', $_POST)) {
            $cont = nullIfEmpty($_POST['contacto_emergencia']);
            validarContactoNombre($cont);
        }

        foreach ($permitidos as $f) {
            if (array_key_exists($f, $_POST)) {
                if (in_array($f, ['telefono', 'contacto_emergencia', 'telefono_emergencia', 'apellido_materno', 'direccion', 'correo_personal'])) {
                    $val = nullIfEmpty($_POST[$f]);
                    $types .= 's';
                    $params[] = $val;
                } elseif ($f === 'id_nombre_semestre') {
                    $types .= 'i';
                    $params[] = (int) $_POST[$f];
                } else {
                    $types .= 's';
                    $params[] = $_POST[$f];
                }
                $updates[] = "{$f} = ?";
            }
        }

        if (empty($updates)) {
            throw new Exception("No hay campos para actualizar.");
        }

        $sql = "UPDATE alumnos SET " . implode(', ', $updates) . " WHERE id_alumno = ?";
        $types .= 'i';
        $params[] = $id_alumno;

        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar alumno: " . $stmt->error);
        }
        $stmt->close();

        respuestaJSON("success", "Alumno actualizado correctamente.");
    }

    // ====== ELIMINAR ======
    if ($action === 'delete') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $stmt = $conn->prepare("DELETE FROM alumnos WHERE id_alumno = ?");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);

        $stmt->bind_param('i', $id_alumno);
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar alumno: " . $stmt->error);
        }
        $stmt->close();

        respuestaJSON("success", "Alumno eliminado correctamente.");
    }

    // ====== OBTENER (1) ======
    if ($action === 'get') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $sql = "SELECT a.*,
                       ns.nombre AS nombre_semestre
                FROM alumnos a
                LEFT JOIN cat_nombres_semestre ns
                  ON ns.id_nombre_semestre = a.id_nombre_semestre
                WHERE a.id_alumno = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);

        $stmt->bind_param("i", $id_alumno);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res)
            throw new Exception("Alumno no encontrado.");

        respuestaJSON("success", "Alumno encontrado.", ["alumno" => $res]);
    }

    // ====== LISTAR (opcional para tabla) ======
    if ($action === 'list') {
        $sql = "SELECT a.*,
                       ns.nombre AS nombre_semestre
                FROM alumnos a
                LEFT JOIN cat_nombres_semestre ns
                  ON ns.id_nombre_semestre = a.id_nombre_semestre
                ORDER BY a.id_alumno DESC";

        $rs = $conn->query($sql);
        if (!$rs)
            throw new Exception("Error al listar: " . $conn->error);

        $data = [];
        while ($row = $rs->fetch_assoc()) {
            $data[] = $row;
        }

        respuestaJSON("success", "OK", ["rows" => $data]);
    }

    // Si ninguna coincide
    throw new Exception("Acción no válida.");

} catch (Exception $e) {
    respuestaJSON("error", $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}