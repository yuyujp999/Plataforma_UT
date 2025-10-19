<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../conexion/conexion.php'; // Debe definir $pdo (PDO)

// ====== Pre-flight CORS ======
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ====== Helpers ======
function respuestaJSON($status, $message, $extra = [])
{
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}

set_exception_handler(function ($e) {
    respuestaJSON("error", $e->getMessage());
});
set_error_handler(function ($severity, $message, $file, $line) {
    respuestaJSON("error", "Error: $message en $file:$line");
});

// Sanitiza números (teléfonos)
function soloDigitos($v)
{
    return preg_replace('/\D+/', '', (string) $v);
}

// Quita acentos y normaliza para correo
function slugify($s)
{
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-zA-Z0-9]+/', '.', $s);
    $s = strtolower(trim($s, '.'));
    return $s ?: 'usuario';
}

// Genera un correo único nombre.apellido@institucional.com
function generarCorreoUnico(PDO $pdo, $nombre, $apellido, $dominio = '@institucional.com')
{
    $base = slugify($nombre) . '.' . slugify($apellido);
    $email = $base . $dominio;

    // Verifica y agrega sufijos si existe
    $i = 1;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM secretarias WHERE correo_institucional = :c");
    while (true) {
        $stmt->execute([':c' => $email]);
        $exists = (int) $stmt->fetchColumn() > 0;
        if (!$exists)
            break;
        $email = $base . $i . $dominio;
        $i++;
        if ($i > 9999) {
            throw new Exception("No fue posible generar un correo único.");
        }
    }
    return $email;
}

// Password aleatoria segura
function generarPasswordPlano($len = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

// ====== Acción ======
$action = $_POST['action'] ?? '';
if (!$action)
    respuestaJSON("error", "No se recibió acción.");

// Campos manejados (sin fecha_ingreso porque ahora es automática)
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
    'contacto_emergencia',
    'parentesco_emergencia',
    'telefono_emergencia'
];

try {
    // ===== CREAR SECRETARIA =====
    if ($action === 'create') {
        $data = [];
        foreach ($fields as $f)
            $data[$f] = trim($_POST[$f] ?? '');

        // Validación obligatorios
        $obligatorios = ['nombre', 'apellido_paterno', 'curp', 'rfc', 'fecha_nacimiento', 'sexo', 'departamento'];
        foreach ($obligatorios as $ob) {
            if ($data[$ob] === '') {
                throw new Exception("Faltan campos obligatorios: $ob");
            }
        }

        // Sanitizar teléfonos
        $data['telefono'] = soloDigitos($data['telefono']);
        $data['telefono_emergencia'] = soloDigitos($data['telefono_emergencia']);

        // Sexo permitido
        $sexosPermitidos = ['Masculino', 'Femenino', 'Otro'];
        if (!in_array($data['sexo'], $sexosPermitidos, true)) {
            throw new Exception("Valor de sexo inválido.");
        }

        // Correo y password
        $correo = generarCorreoUnico($pdo, $data['nombre'], $data['apellido_paterno']);
        $passwordPlano = generarPasswordPlano(10);
        $passwordHash = password_hash($passwordPlano, PASSWORD_BCRYPT);

        // Insert con fecha_ingreso automática (CURRENT_DATE)
        $sql = "INSERT INTO secretarias (
            nombre, apellido_paterno, apellido_materno, curp, rfc, fecha_nacimiento, sexo,
            telefono, direccion, correo_institucional, password, departamento, fecha_ingreso,
            contacto_emergencia, parentesco_emergencia, telefono_emergencia
        ) VALUES (
            :nombre,:apellido_paterno,:apellido_materno,:curp,:rfc,:fecha_nacimiento,:sexo,
            :telefono,:direccion,:correo_institucional,:password,:departamento,CURRENT_DATE,
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
            ':password' => $passwordHash,
            ':departamento' => $data['departamento'],
            ':contacto_emergencia' => $data['contacto_emergencia'],
            ':parentesco_emergencia' => $data['parentesco_emergencia'],
            ':telefono_emergencia' => $data['telefono_emergencia'],
        ]);

        respuestaJSON("success", "Secretaria agregada correctamente.", [
            "correo" => $correo,
            "password_plano" => $passwordPlano
        ]);
    }

    // ===== EDITAR SECRETARIA =====
    if ($action === 'edit') {
        $id = intval($_POST['id_secretaria'] ?? 0);
        if ($id <= 0)
            throw new Exception("ID secretaria inválido.");

        // Campos editables (NO correo_institucional, NO password, NO fecha_ingreso)
        $editable = [
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
            'contacto_emergencia',
            'parentesco_emergencia',
            'telefono_emergencia'
        ];

        $updates = [];
        $params = [':id' => $id];

        foreach ($editable as $f) {
            if (array_key_exists($f, $_POST)) {
                $val = trim($_POST[$f]);
                if (in_array($f, ['telefono', 'telefono_emergencia'], true)) {
                    $val = soloDigitos($val);
                }
                $updates[] = "$f = :$f";
                $params[":$f"] = $val;
            }
        }

        if (empty($updates))
            throw new Exception("No hay campos para actualizar.");

        // Validación de sexo si lo envían
        if (isset($_POST['sexo'])) {
            $sexosPermitidos = ['Masculino', 'Femenino', 'Otro'];
            if (!in_array($_POST['sexo'], $sexosPermitidos, true)) {
                throw new Exception("Valor de sexo inválido.");
            }
        }

        $sql = "UPDATE secretarias SET " . implode(', ', $updates) . " WHERE id_secretaria = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Regresamos el correo (no se modifica) como conveniencia
        $stmt2 = $pdo->prepare("SELECT correo_institucional FROM secretarias WHERE id_secretaria = :id");
        $stmt2->execute([':id' => $id]);
        $result = $stmt2->fetch(PDO::FETCH_ASSOC);

        respuestaJSON("success", "Secretaria actualizada correctamente.", [
            "correo" => $result['correo_institucional'] ?? null
        ]);
    }

    // ===== ELIMINAR SECRETARIA =====
    if ($action === 'delete') {
        $id = intval($_POST['id_secretaria'] ?? 0);
        if ($id <= 0)
            throw new Exception("ID secretaria inválido.");

        $stmt = $pdo->prepare("DELETE FROM secretarias WHERE id_secretaria = :id");
        $stmt->execute([':id' => $id]);

        respuestaJSON("success", "Secretaria eliminada correctamente.");
    }

    // ===== OBTENER SECRETARIA =====
    if ($action === 'get') {
        $id = intval($_POST['id_secretaria'] ?? 0);
        if ($id <= 0)
            throw new Exception("ID secretaria inválido.");

        // Selecciona columnas explícitas y arma un payload completo
        $stmt = $pdo->prepare("
            SELECT
                id_secretaria,
                nombre,
                apellido_paterno,
                apellido_materno,
                curp,
                rfc,
                fecha_nacimiento,
                sexo,
                telefono,
                direccion,
                departamento,
                contacto_emergencia,
                parentesco_emergencia,
                telefono_emergencia,
                correo_institucional,
                fecha_ingreso
            FROM secretarias
            WHERE id_secretaria = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)
            throw new Exception("Secretaria no encontrada.");

        // No exponemos password
        unset($row['password']);

        // Payload garantizando que existan las claves
        $payload = [
            'id_secretaria' => $row['id_secretaria'],
            'nombre' => $row['nombre'] ?? '',
            'apellido_paterno' => $row['apellido_paterno'] ?? '',
            'apellido_materno' => $row['apellido_materno'] ?? '',
            'curp' => $row['curp'] ?? '',
            'rfc' => $row['rfc'] ?? '',
            'fecha_nacimiento' => $row['fecha_nacimiento'] ?? '',
            'sexo' => $row['sexo'] ?? '',
            'telefono' => $row['telefono'] ?? '',
            'direccion' => $row['direccion'] ?? '',
            'departamento' => $row['departamento'] ?? '',
            'contacto_emergencia' => $row['contacto_emergencia'] ?? '',
            'parentesco_emergencia' => $row['parentesco_emergencia'] ?? '',
            'telefono_emergencia' => $row['telefono_emergencia'] ?? '',
            'correo_institucional' => $row['correo_institucional'] ?? '',
            'fecha_ingreso' => $row['fecha_ingreso'] ?? '',
        ];

        respuestaJSON("success", "Secretaria encontrada", $payload);
    }

    throw new Exception("Acción no válida.");

} catch (Exception $e) {
    respuestaJSON("error", $e->getMessage());
}