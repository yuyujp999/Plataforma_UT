<?php
// --- Configuración ---
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/../../conexion/conexion.php'; // Debe exponer $conn (mysqli)

// --- Pre-flight para CORS ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ================= Utilidades comunes ================= */
function respuestaJSON($status, $message, $extra = [])
{
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
set_exception_handler(function ($e) {
    respuestaJSON("error", $e->getMessage());
});
set_error_handler(function ($sev, $msg, $file, $line) {
    respuestaJSON("error", "Error: $msg en $file:$line");
});

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
function validarTelefono($v, $campo)
{
    if ($v !== null && !preg_match('/^\d{7,15}$/', $v))
        throw new Exception("$campo: ingresa solo dígitos (7 a 15).");
}
function validarContactoNombre($v)
{
    if ($v !== null && !preg_match("/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]{2,60}$/u", $v))
        throw new Exception("Contacto de emergencia: solo letras y espacios (2 a 60).");
}
function colExiste(mysqli $conn, $tabla, $col)
{
    $db = $conn->query("SELECT DATABASE() AS d")->fetch_assoc()['d'] ?? '';
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?");
    $stmt->bind_param("sss", $db, $tabla, $col);
    $stmt->execute();
    $c = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $c > 0;
}

/* ========== Actor desde sesión (robusto para secretarías) ========== */
/* Busca un ID de secretaria válido en varias llaves posibles, primero en
   $_SESSION['usuario'] y luego en la raíz de $_SESSION. Devuelve null si no hay. */
function get_secretaria_actor_id_from_session(): ?int
{
    $roles_secretaria = ['secretaria', 'secretarias', 'secretaría', 'secretarías'];
    $rol = strtolower($_SESSION['rol'] ?? '');
    // Solo intentamos mapear a secretaría si el rol lo es
    if (!in_array($rol, $roles_secretaria, true)) {
        return null; // si es admin u otro, no intentamos mapear a secretarias.id_secretaria
    }

    $candidatas = ['id_secretaria', 'secretaria_id', 'iduser', 'id'];
    $fuentes = [];

    // Primero dentro de usuario
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
        $fuentes[] = $_SESSION['usuario'];
    }
    // Luego a nivel raíz de sesión
    $fuentes[] = $_SESSION;

    foreach ($fuentes as $arr) {
        foreach ($candidatas as $k) {
            if (isset($arr[$k]) && (int) $arr[$k] > 0) {
                return (int) $arr[$k];
            }
        }
    }
    return null;
}

/* ========== Notificar al admin (normalizado, sin NULLs “feos”) ========== */
function notificar_admin(mysqli $conn, array $cfg): void
{
    $tipo = trim((string) ($cfg['tipo'] ?? 'movimiento'));
    if (!in_array($tipo, ['movimiento', 'mensaje'], true))
        $tipo = 'movimiento';

    $titulo = (string) ($cfg['titulo'] ?? '');
    $detalle = (string) ($cfg['detalle'] ?? '');  // cadena vacía si no mandan detalle
    $para_rol = 'admin';

    // OJO: actor_id debe apuntar a secretarias.id_secretaria para que el JOIN lo encuentre
    $actor_id = $cfg['actor_id'] ?? null;
    $actor_id = is_numeric($actor_id) ? (int) $actor_id : null;

    $recurso = (string) ($cfg['recurso'] ?? 'alumno'); // default
    $accion = (string) ($cfg['accion'] ?? '');

    $meta = $cfg['meta'] ?? null;
    if (is_array($meta)) {
        $meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
    } elseif ($meta !== null) {
        $meta = (string) $meta;
    }

    $leido = 0;

    $sql = "INSERT INTO notificaciones
            (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta, leido)
            VALUES (?,?,?,?,?,?,?,?,?)";

    if ($stmt = $conn->prepare($sql)) {
        // s s s s i s s s i
        $stmt->bind_param(
            "ssssisssi",
            $tipo,
            $titulo,
            $detalle,
            $para_rol,
            $actor_id,
            $recurso,
            $accion,
            $meta,
            $leido
        );
        $stmt->execute();
        $stmt->close();
    }
}

/* ================= Seguridad método/acción ================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON("error", "Método no permitido.");
}
$action = $_POST['action'] ?? '';
if (!$action) {
    respuestaJSON("error", "No se recibió acción.");
}

/* ================= Lógica principal ================= */
try {
    /* --------- CREATE --------- */
    if ($action === 'create') {
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
        required($payload, ['nombre', 'apellido_paterno', 'curp', 'fecha_nacimiento', 'sexo', 'id_nombre_semestre']);

        $apellido_materno = nullIfEmpty($payload['apellido_materno']);
        $telefono = nullIfEmpty($payload['telefono']);
        $direccion = nullIfEmpty($payload['direccion']);
        $correo_personal = nullIfEmpty($payload['correo_personal']);
        $id_nombre_semestre = (int) $payload['id_nombre_semestre'];
        $contacto_emergencia = nullIfEmpty($payload['contacto_emergencia']);
        $parentesco_emergencia = nullIfEmpty($payload['parentesco_emergencia']);
        $telefono_emergencia = nullIfEmpty($payload['telefono_emergencia']);

        validarTelefono($telefono, 'Teléfono');
        validarTelefono($telefono_emergencia, 'Teléfono de emergencia');
        validarContactoNombre($contacto_emergencia);

        $matricula = 'AL' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $password_plain = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

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
        if (!$stmt->execute())
            throw new Exception("Error al agregar alumno: " . $stmt->error);
        $nuevoId = $stmt->insert_id;
        $stmt->close();

        $nombre_semestre = '';
        if ($rs = $conn->query("SELECT nombre FROM cat_nombres_semestre WHERE id_nombre_semestre = {$id_nombre_semestre} LIMIT 1")) {
            if ($rs->num_rows)
                $nombre_semestre = $rs->fetch_assoc()['nombre'] ?? '';
            $rs->close();
        }

        $actor_id = get_secretaria_actor_id_from_session();
        notificar_admin($conn, [
            'tipo' => 'movimiento',
            'titulo' => 'Alta de alumno',
            'detalle' => 'La secretaría registró al alumno ' . $payload['nombre'] . ' ' . $payload['apellido_paterno'] . ' (' . $matricula . ').',
            'actor_id' => $actor_id,
            'recurso' => 'alumno',
            'accion' => 'alta',
            'meta' => ['id_alumno' => $nuevoId, 'matricula' => $matricula, 'id_semestre' => $id_nombre_semestre, 'semestre' => $nombre_semestre],
        ]);

        respuestaJSON("success", "Alumno agregado correctamente.", [
            "id_alumno" => $nuevoId,
            "matricula" => $matricula,
            "password" => $password_plain,
            "nombre_semestre" => $nombre_semestre
        ]);
    }

    /* --------- EDIT --------- */
    if ($action === 'edit') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

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

        if (array_key_exists('telefono', $_POST)) {
            validarTelefono(nullIfEmpty($_POST['telefono']), 'Teléfono');
        }
        if (array_key_exists('telefono_emergencia', $_POST)) {
            validarTelefono(nullIfEmpty($_POST['telefono_emergencia']), 'Teléfono de emergencia');
        }
        if (array_key_exists('contacto_emergencia', $_POST)) {
            validarContactoNombre(nullIfEmpty($_POST['contacto_emergencia']));
        }

        $updates = [];
        $types = '';
        $params = [];
        foreach ($permitidos as $f) {
            if (array_key_exists($f, $_POST)) {
                if ($f === 'id_nombre_semestre') {
                    $types .= 'i';
                    $params[] = (int) $_POST[$f];
                } else {
                    $val = in_array($f, ['telefono', 'contacto_emergencia', 'telefono_emergencia', 'apellido_materno', 'direccion', 'correo_personal'])
                        ? nullIfEmpty($_POST[$f]) : $_POST[$f];
                    $types .= 's';
                    $params[] = $val;
                }
                $updates[] = "$f = ?";
            }
        }
        if (empty($updates))
            throw new Exception("No hay campos para actualizar.");

        $sql = "UPDATE alumnos SET " . implode(', ', $updates) . " WHERE id_alumno = ?";
        $types .= 'i';
        $params[] = $id_alumno;

        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute())
            throw new Exception("Error al actualizar alumno: " . $stmt->error);
        $stmt->close();

        $alumnoNombre = '';
        $matricula = '';
        $id_sem = null;
        $sem = null;
        if (
            $rs = $conn->prepare("SELECT a.nombre, a.apellido_paterno, a.matricula, a.id_nombre_semestre, ns.nombre AS nombre_semestre
                                  FROM alumnos a
                                  LEFT JOIN cat_nombres_semestre ns ON ns.id_nombre_semestre = a.id_nombre_semestre
                                  WHERE a.id_alumno=? LIMIT 1")
        ) {
            $rs->bind_param('i', $id_alumno);
            $rs->execute();
            $row = $rs->get_result()->fetch_assoc();
            $rs->close();
            if ($row) {
                $alumnoNombre = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? ''));
                $matricula = (string) ($row['matricula'] ?? '');
                $id_sem = $row['id_nombre_semestre'] ?? null;
                $sem = $row['nombre_semestre'] ?? null;
            }
        }

        $actor_id = get_secretaria_actor_id_from_session();
        notificar_admin($conn, [
            'tipo' => 'movimiento',
            'titulo' => 'Edición de alumno',
            'detalle' => 'La secretaría editó al alumno ' . $alumnoNombre . ' (' . $matricula . ').',
            'actor_id' => $actor_id,
            'recurso' => 'alumno',
            'accion' => 'edicion',
            'meta' => ['id_alumno' => $id_alumno, 'matricula' => $matricula, 'id_semestre' => $id_sem, 'semestre' => $sem],
        ]);

        respuestaJSON("success", "Alumno actualizado correctamente.");
    }

    /* --------- BAJA --------- */
    if ($action === 'baja') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $tieneFechaBaja = colExiste($conn, 'alumnos', 'fecha_baja');
        $sql = $tieneFechaBaja
            ? "UPDATE alumnos SET estatus='baja', fecha_baja=CURDATE() WHERE id_alumno=?"
            : "UPDATE alumnos SET estatus='baja' WHERE id_alumno=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_alumno);
        if (!$stmt->execute())
            throw new Exception("No se pudo dar de baja: " . $stmt->error);
        $stmt->close();

        $row = null;
        if ($rs = $conn->prepare("SELECT nombre, apellido_paterno, matricula FROM alumnos WHERE id_alumno=?")) {
            $rs->bind_param('i', $id_alumno);
            $rs->execute();
            $row = $rs->get_result()->fetch_assoc();
            $rs->close();
        }

        $actor_id = get_secretaria_actor_id_from_session();
        notificar_admin($conn, [
            'tipo' => 'movimiento',
            'titulo' => 'Baja de alumno',
            'detalle' => 'La secretaría dio de baja a ' . ($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' (' . ($row['matricula'] ?? '') . ').',
            'actor_id' => $actor_id,
            'recurso' => 'alumno',
            'accion' => 'baja',
            'meta' => ['id_alumno' => $id_alumno, 'matricula' => $row['matricula'] ?? null],
        ]);

        respuestaJSON("success", "Alumno dado de baja.");
    }

    /* --------- REACTIVAR --------- */
    if ($action === 'reactivar') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $tieneFechaBaja = colExiste($conn, 'alumnos', 'fecha_baja');
        $sql = $tieneFechaBaja
            ? "UPDATE alumnos SET estatus='activo', fecha_baja=NULL WHERE id_alumno=?"
            : "UPDATE alumnos SET estatus='activo' WHERE id_alumno=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_alumno);
        if (!$stmt->execute())
            throw new Exception("No se pudo reactivar: " . $stmt->error);
        $stmt->close();

        $row = null;
        if ($rs = $conn->prepare("SELECT nombre, apellido_paterno, matricula FROM alumnos WHERE id_alumno=?")) {
            $rs->bind_param('i', $id_alumno);
            $rs->execute();
            $row = $rs->get_result()->fetch_assoc();
            $rs->close();
        }

        $actor_id = get_secretaria_actor_id_from_session();
        notificar_admin($conn, [
            'tipo' => 'movimiento',
            'titulo' => 'Reactivación de alumno',
            'detalle' => 'La secretaría reactivó a ' . ($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' (' . ($row['matricula'] ?? '') . ').',
            'actor_id' => $actor_id,
            'recurso' => 'alumno',
            'accion' => 'reactivar',
            'meta' => ['id_alumno' => $id_alumno, 'matricula' => $row['matricula'] ?? null],
        ]);

        respuestaJSON("success", "Alumno reactivado.");
    }

    /* --------- SUSPENDER --------- */
    if ($action === 'suspender') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $stmt = $conn->prepare("UPDATE alumnos SET estatus='suspendido' WHERE id_alumno=?");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_alumno);
        if (!$stmt->execute())
            throw new Exception("No se pudo suspender: " . $stmt->error);
        $stmt->close();

        $row = null;
        if ($rs = $conn->prepare("SELECT nombre, apellido_paterno, matricula FROM alumnos WHERE id_alumno=?")) {
            $rs->bind_param('i', $id_alumno);
            $rs->execute();
            $row = $rs->get_result()->fetch_assoc();
            $rs->close();
        }

        $actor_id = get_secretaria_actor_id_from_session();
        notificar_admin($conn, [
            'tipo' => 'movimiento',
            'titulo' => 'Suspensión de alumno',
            'detalle' => 'La secretaría suspendió a ' . ($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' (' . ($row['matricula'] ?? '') . ').',
            'actor_id' => $actor_id,
            'recurso' => 'alumno',
            'accion' => 'suspension',
            'meta' => ['id_alumno' => $id_alumno, 'matricula' => $row['matricula'] ?? null],
        ]);

        respuestaJSON("success", "Alumno suspendido.");
    }

    /* --------- QUITAR SUSPENSIÓN --------- */
    if ($action === 'quitar_suspension') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $stmt = $conn->prepare("UPDATE alumnos SET estatus='activo' WHERE id_alumno=?");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_alumno);
        if (!$stmt->execute())
            throw new Exception("No se pudo quitar la suspensión: " . $stmt->error);
        $stmt->close();

        $row = null;
        if ($rs = $conn->prepare("SELECT nombre, apellido_paterno, matricula FROM alumnos WHERE id_alumno=?")) {
            $rs->bind_param('i', $id_alumno);
            $rs->execute();
            $row = $rs->get_result()->fetch_assoc();
            $rs->close();
        }

        $actor_id = get_secretaria_actor_id_from_session();
        notificar_admin($conn, [
            'tipo' => 'movimiento',
            'titulo' => 'Quitar suspensión de alumno',
            'detalle' => 'La secretaría quitó la suspensión a ' . ($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' (' . ($row['matricula'] ?? '') . ').',
            'actor_id' => $actor_id,
            'recurso' => 'alumno',
            'accion' => 'quitar_suspension',
            'meta' => ['id_alumno' => $id_alumno, 'matricula' => $row['matricula'] ?? null],
        ]);

        respuestaJSON("success", "Suspensión retirada. Alumno activo.");
    }

    /* --------- DELETE --------- */
    if ($action === 'delete') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $row = null;
        if ($rs = $conn->prepare("SELECT nombre, apellido_paterno, matricula FROM alumnos WHERE id_alumno=?")) {
            $rs->bind_param('i', $id_alumno);
            $rs->execute();
            $row = $rs->get_result()->fetch_assoc();
            $rs->close();
        }

        $stmt = $conn->prepare("DELETE FROM alumnos WHERE id_alumno = ?");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_alumno);
        if (!$stmt->execute())
            throw new Exception("Error al eliminar alumno: " . $stmt->error);
        $stmt->close();

        $actor_id = get_secretaria_actor_id_from_session();
        notificar_admin($conn, [
            'tipo' => 'movimiento',
            'titulo' => 'Eliminación de alumno',
            'detalle' => 'La secretaría eliminó al alumno ' . ($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? '') . ' (' . ($row['matricula'] ?? '') . ').',
            'actor_id' => $actor_id,
            'recurso' => 'alumno',
            'accion' => 'eliminacion',
            'meta' => ['id_alumno' => $id_alumno, 'matricula' => $row['matricula'] ?? null],
        ]);

        respuestaJSON("success", "Alumno eliminado correctamente.");
    }

    /* --------- GET --------- */
    if ($action === 'get') {
        $id_alumno = (int) ($_POST['id_alumno'] ?? 0);
        if ($id_alumno <= 0)
            throw new Exception("ID de alumno inválido.");

        $sql = "SELECT a.*, ns.nombre AS nombre_semestre
                FROM alumnos a
                LEFT JOIN cat_nombres_semestre ns ON ns.id_nombre_semestre = a.id_nombre_semestre
                WHERE a.id_alumno = ? LIMIT 1";
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

    /* --------- LIST --------- */
    if ($action === 'list') {
        $sql = "SELECT a.*, ns.nombre AS nombre_semestre
                FROM alumnos a
                LEFT JOIN cat_nombres_semestre ns ON ns.id_nombre_semestre = a.id_nombre_semestre
                ORDER BY a.id_alumno DESC";
        $rs = $conn->query($sql);
        if (!$rs)
            throw new Exception("Error al listar: " . $conn->error);
        $data = [];
        while ($row = $rs->fetch_assoc())
            $data[] = $row;
        respuestaJSON("success", "OK", ["rows" => $data]);
    }

    throw new Exception("Acción no válida.");

} catch (Exception $e) {
    respuestaJSON("error", $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}