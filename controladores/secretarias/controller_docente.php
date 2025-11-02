<?php
// ===== Controlador DOCENTES (Secretarías) =====
session_start(); // para validar rol en delete (soft)

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
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra), JSON_UNESCAPED_UNICODE);
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
    $sql = "SELECT matricula FROM docentes 
            WHERE matricula REGEXP '^DOC[0-9]{4}$' 
            ORDER BY matricula DESC LIMIT 1";
    $res = $conn->query($sql);
    $next = 1;
    if ($res && $row = $res->fetch_assoc()) {
        $ult = (int) substr($row['matricula'], 3); // después de DOC
        $next = $ult + 1;
    }
    // Intentos en caso de colisión
    for ($i = 0; $i < 50; $i++) {
        $mat = 'DOC' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM docentes WHERE matricula=?");
        $stmt->bind_param("s", $mat);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ((int) $count === 0)
            return $mat;
        $next++;
    }
    throw new Exception("No fue posible generar matrícula única.");
}

function requireAdmin()
{
    $rol = $_SESSION['rol'] ?? '';
    if ($rol !== 'admin')
        throw new Exception("Acción permitida solo para administrador.");
}

/* ===== Helper: obtener actor_id (id_secretaria) desde sesión =====
 * Busca en varias llaves en $_SESSION['usuario'] y en la raíz de $_SESSION.
 */
function get_secretaria_actor_id_from_session(): ?int
{
    $roles_secretaria = ['secretaria', 'secretarías', 'secretarias', 'secretaría'];
    $rol = strtolower($_SESSION['rol'] ?? '');
    if (!in_array($rol, $roles_secretaria, true))
        return null;

    $candidatas = ['id_secretaria', 'secretaria_id', 'iduser', 'id'];
    $fuentes = [];
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']))
        $fuentes[] = $_SESSION['usuario'];
    $fuentes[] = $_SESSION;

    foreach ($fuentes as $arr) {
        foreach ($candidatas as $k) {
            if (isset($arr[$k]) && (int) $arr[$k] > 0)
                return (int) $arr[$k];
        }
    }
    return null;
}

/**
 * ===== Helper de Notificaciones (mysqli) =====
 * Tabla sugerida:
 * CREATE TABLE IF NOT EXISTS notificaciones (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   tipo ENUM('movimiento','mensaje') NOT NULL DEFAULT 'movimiento',
 *   titulo VARCHAR(120) NOT NULL,
 *   detalle TEXT NULL,
 *   para_rol ENUM('admin','secretaria') NOT NULL DEFAULT 'admin',
 *   actor_id INT NULL,
 *   recurso VARCHAR(50) NULL,
 *   accion  VARCHAR(30) NULL,
 *   meta JSON NULL,
 *   leido TINYINT(1) NOT NULL DEFAULT 0,
 *   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 * );
 */
function registrarNotificacion(mysqli $conn, array $data): void
{
    $sql = "INSERT INTO notificaciones 
            (tipo, titulo, detalle, para_rol, actor_id, recurso, accion, meta)
            VALUES (?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Si no existe la tabla o falla, no romper el flujo principal
        return;
    }
    $tipo = $data['tipo'] ?? 'movimiento';
    $titulo = $data['titulo'] ?? 'Movimiento registrado';
    $detalle = $data['detalle'] ?? null;
    $para_rol = $data['para_rol'] ?? 'admin';
    $actor_id = $data['actor_id'] ?? null; // id_secretaria
    $recurso = $data['recurso'] ?? 'docente'; // singular para consistencia
    $accion = $data['accion'] ?? null;
    $meta = isset($data['meta']) ? json_encode($data['meta'], JSON_UNESCAPED_UNICODE) : null;

    // tipo(s) titulo(s) detalle(s) para_rol(s) actor_id(i) recurso(s) accion(s) meta(s)
    $stmt->bind_param("ssssisss", $tipo, $titulo, $detalle, $para_rol, $actor_id, $recurso, $accion, $meta);
    // Silencioso: si falla, no interrumpe la acción principal
    @$stmt->execute();
    $stmt->close();
}

// --- Validación de método/acción ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON("error", "Método no permitido.");
}
$action = $_POST['action'] ?? '';
if (!$action) {
    respuestaJSON("error", "No se recibió acción.");
}

// Permite controlar tipo/destinatario de la notificación desde el front si lo deseas
$NOTI_TIPO = strtolower(trim($_POST['noti_tipo'] ?? 'movimiento'));
if (!in_array($NOTI_TIPO, ['movimiento', 'mensaje'], true))
    $NOTI_TIPO = 'movimiento';

$NOTI_PARA = strtolower(trim($_POST['noti_para'] ?? 'admin'));
if (!in_array($NOTI_PARA, ['admin', 'secretaria'], true))
    $NOTI_PARA = 'admin';

// ===== Campos del docente conforme a tu tabla ACTUAL =====
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
    // NO incluimos 'matricula' ni 'password' aquí para edición directa
];

try {
    // ====== CREATE ======
    if ($action === 'create') {
        $data = [];
        foreach ($fields as $f)
            $data[$f] = trim($_POST[$f] ?? '');

        // Requeridos
        $obligatorios = ['nombre', 'apellido_paterno', 'curp', 'rfc', 'fecha_nacimiento', 'sexo', 'nivel_estudios', 'puesto', 'tipo_contrato', 'fecha_ingreso'];
        foreach ($obligatorios as $ob) {
            if (($data[$ob] ?? '') === '')
                throw new Exception("Faltan campos obligatorios: $ob");
        }

        // Solo dígitos en teléfonos
        $data['telefono'] = soloDigitos($data['telefono']);
        $data['telefono_emergencia'] = soloDigitos($data['telefono_emergencia']);

        // Generar matrícula y password
        $matricula = generarMatriculaUnica($conn);
        $passwordPlano = generarPasswordPlano(10);
        $passwordHash = password_hash($passwordPlano, PASSWORD_BCRYPT);

        // INSERT (estatus/fecha_baja/deleted_at usan DEFAULT/NULL)
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

        if (!$stmt->execute())
            throw new Exception("Error al insertar docente: " . $stmt->error);
        $nuevoId = $stmt->insert_id;
        $stmt->close();

        // ===== Notificación (crear) =====
        $actorId = get_secretaria_actor_id_from_session();
        registrarNotificacion($conn, [
            'tipo' => $NOTI_TIPO,
            'para_rol' => $NOTI_PARA,
            'titulo' => 'Docente creado',
            'detalle' => 'Matrícula ' . $matricula . ' - ' . ($data['nombre'] ?? ''),
            'accion' => 'crear',
            'recurso' => 'docente',
            'actor_id' => $actorId,
            'meta' => ['id_docente' => $nuevoId, 'matricula' => $matricula]
        ]);

        respuestaJSON("success", "Docente agregado correctamente.", [
            "id_docente" => $nuevoId,
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
                if (in_array($f, ['telefono', 'telefono_emergencia'], true))
                    $val = soloDigitos($val);
                $updates[] = "$f=?";
                $params[] = $val;
                $types .= 's';
            }
        }
        if (empty($updates))
            throw new Exception("No hay campos para actualizar.");

        // Respetar soft-deletes
        $sql = "UPDATE docentes SET " . implode(',', $updates) . " WHERE id_docente=? AND deleted_at IS NULL";
        $types .= 'i';
        $params[] = $id_docente;

        $stmt = $conn->prepare($sql);
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute())
            throw new Exception("Error al actualizar docente: " . $stmt->error);
        $stmt->close();

        // Devolver matrícula como referencia
        $stmt2 = $conn->prepare("SELECT matricula, nombre FROM docentes WHERE id_docente=?");
        $stmt2->bind_param("i", $id_docente);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        // ===== Notificación (editar) =====
        $actorId = get_secretaria_actor_id_from_session();
        registrarNotificacion($conn, [
            'tipo' => $NOTI_TIPO,
            'para_rol' => $NOTI_PARA,
            'titulo' => 'Docente actualizado',
            'detalle' => 'Matrícula ' . ($res['matricula'] ?? '') . ' - ID ' . $id_docente,
            'accion' => 'editar',
            'recurso' => 'docente',
            'actor_id' => $actorId,
            'meta' => ['id_docente' => $id_docente, 'matricula' => ($res['matricula'] ?? '')]
        ]);

        respuestaJSON("success", "Docente actualizado correctamente.", [
            "matricula" => $res['matricula'] ?? ''
        ]);
    }

    // ====== BAJA ======
    if ($action === 'baja') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("UPDATE docentes 
                                SET estatus='baja', fecha_baja=CURDATE() 
                                WHERE id_docente=? AND deleted_at IS NULL");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_docente);
        if (!$stmt->execute())
            throw new Exception("No se pudo dar de baja: " . $stmt->error);
        $stmt->close();

        // Obtener matrícula para detalle
        $stmt2 = $conn->prepare("SELECT matricula FROM docentes WHERE id_docente=?");
        $stmt2->bind_param("i", $id_docente);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        // ===== Notificación (baja) =====
        $actorId = get_secretaria_actor_id_from_session();
        registrarNotificacion($conn, [
            'tipo' => $NOTI_TIPO,
            'para_rol' => $NOTI_PARA,
            'titulo' => 'Docente dado de baja',
            'detalle' => 'Matrícula ' . ($res['matricula'] ?? '') . ' - ID ' . $id_docente,
            'accion' => 'baja',
            'recurso' => 'docente',
            'actor_id' => $actorId,
            'meta' => ['id_docente' => $id_docente, 'matricula' => ($res['matricula'] ?? '')]
        ]);

        respuestaJSON("success", "Docente dado de baja.");
    }

    // ====== REACTIVAR ======
    if ($action === 'reactivar') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("UPDATE docentes 
                                SET estatus='activo', fecha_baja=NULL 
                                WHERE id_docente=? AND deleted_at IS NULL");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_docente);
        if (!$stmt->execute())
            throw new Exception("No se pudo reactivar: " . $stmt->error);
        $stmt->close();

        $stmt2 = $conn->prepare("SELECT matricula FROM docentes WHERE id_docente=?");
        $stmt2->bind_param("i", $id_docente);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        // ===== Notificación (reactivar) =====
        $actorId = get_secretaria_actor_id_from_session();
        registrarNotificacion($conn, [
            'tipo' => $NOTI_TIPO,
            'para_rol' => $NOTI_PARA,
            'titulo' => 'Docente reactivado',
            'detalle' => 'Matrícula ' . ($res['matricula'] ?? '') . ' - ID ' . $id_docente,
            'accion' => 'reactivar',
            'recurso' => 'docente',
            'actor_id' => $actorId,
            'meta' => ['id_docente' => $id_docente, 'matricula' => ($res['matricula'] ?? '')]
        ]);

        respuestaJSON("success", "Docente reactivado.");
    }

    // ====== SUSPENDER ======
    if ($action === 'suspender') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("UPDATE docentes 
                                SET estatus='suspendido' 
                                WHERE id_docente=? AND deleted_at IS NULL");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_docente);
        if (!$stmt->execute())
            throw new Exception("No se pudo suspender: " . $stmt->error);
        $stmt->close();

        $stmt2 = $conn->prepare("SELECT matricula FROM docentes WHERE id_docente=?");
        $stmt2->bind_param("i", $id_docente);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        // ===== Notificación (suspender) =====
        $actorId = get_secretaria_actor_id_from_session();
        registrarNotificacion($conn, [
            'tipo' => $NOTI_TIPO,
            'para_rol' => $NOTI_PARA,
            'titulo' => 'Docente suspendido',
            'detalle' => 'Matrícula ' . ($res['matricula'] ?? '') . ' - ID ' . $id_docente,
            'accion' => 'suspender',
            'recurso' => 'docente',
            'actor_id' => $actorId,
            'meta' => ['id_docente' => $id_docente, 'matricula' => ($res['matricula'] ?? '')]
        ]);

        respuestaJSON("success", "Docente suspendido.");
    }

    // ====== QUITAR SUSPENSIÓN ======
    if ($action === 'quitar_suspension') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("UPDATE docentes 
                                SET estatus='activo' 
                                WHERE id_docente=? AND deleted_at IS NULL");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_docente);
        if (!$stmt->execute())
            throw new Exception("No se pudo quitar la suspensión: " . $stmt->error);
        $stmt->close();

        $stmt2 = $conn->prepare("SELECT matricula FROM docentes WHERE id_docente=?");
        $stmt2->bind_param("i", $id_docente);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        // ===== Notificación (quitar_suspension) =====
        $actorId = get_secretaria_actor_id_from_session();
        registrarNotificacion($conn, [
            'tipo' => $NOTI_TIPO,
            'para_rol' => $NOTI_PARA,
            'titulo' => 'Suspensión retirada',
            'detalle' => 'Matrícula ' . ($res['matricula'] ?? '') . ' - ID ' . $id_docente,
            'accion' => 'quitar_suspension',
            'recurso' => 'docente',
            'actor_id' => $actorId,
            'meta' => ['id_docente' => $id_docente, 'matricula' => ($res['matricula'] ?? '')]
        ]);

        respuestaJSON("success", "Suspensión retirada. Docente activo.");
    }

    // ====== DELETE (soft, solo admin) ======
    if ($action === 'delete') {
        requireAdmin(); // Secretaría NO elimina definitivamente

        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("UPDATE docentes SET deleted_at = NOW() WHERE id_docente=? AND deleted_at IS NULL");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param('i', $id_docente);
        if (!$stmt->execute())
            throw new Exception("Error al eliminar (soft): " . $stmt->error);
        $af = $stmt->affected_rows;
        $stmt->close();

        if ($af === 0) {
            respuestaJSON("warning", "No se encontró el docente o ya estaba eliminado.");
        } else {
            // Obtener matrícula para detalle
            $stmt2 = $conn->prepare("SELECT matricula FROM docentes WHERE id_docente=?");
            $stmt2->bind_param("i", $id_docente);
            $stmt2->execute();
            $res = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            // ===== Notificación (delete) =====
            $actorId = get_secretaria_actor_id_from_session();
            registrarNotificacion($conn, [
                'tipo' => $NOTI_TIPO,
                'para_rol' => $NOTI_PARA,
                'titulo' => 'Docente eliminado (soft)',
                'detalle' => 'Matrícula ' . ($res['matricula'] ?? '') . ' - ID ' . $id_docente,
                'accion' => 'eliminar',
                'recurso' => 'docente',
                'actor_id' => $actorId,
                'meta' => ['id_docente' => $id_docente, 'matricula' => ($res['matricula'] ?? '')]
            ]);

            respuestaJSON("success", "Docente eliminado (soft delete).");
        }
    }

    // ====== GET ======
    if ($action === 'get') {
        $id_docente = intval($_POST['id_docente'] ?? 0);
        if ($id_docente <= 0)
            throw new Exception("ID docente inválido.");

        $stmt = $conn->prepare("SELECT * FROM docentes WHERE id_docente=? AND deleted_at IS NULL");
        if (!$stmt)
            throw new Exception("Error de preparación: " . $conn->error);
        $stmt->bind_param("i", $id_docente);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row)
            throw new Exception("Docente no encontrado.");

        unset($row['password']); // por seguridad
        respuestaJSON("success", "Docente encontrado", ["docente" => $row]);
    }

    // ====== LIST ======
    if ($action === 'list') {
        // Filtro opcional: estatus ('activo','baja','suspendido','todos')
        $estatus = $_POST['estatus'] ?? 'activo'; // por defecto activos
        $where = "deleted_at IS NULL";
        $types = '';
        $params = [];

        if ($estatus && $estatus !== 'todos') {
            $where .= " AND estatus = ?";
            $types .= 's';
            $params[] = $estatus;
        }

        $sql = "SELECT * FROM docentes WHERE $where ORDER BY id_docente DESC";
        if ($types) {
            $stmt = $conn->prepare($sql);
            if (!$stmt)
                throw new Exception("Error de preparación: " . $conn->error);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $rs = $stmt->get_result();
        } else {
            $rs = $conn->query($sql);
        }
        if (!$rs)
            throw new Exception("Error al listar: " . $conn->error);

        $rows = [];
        while ($row = $rs->fetch_assoc()) {
            unset($row['password']); // no exponer hash
            $rows[] = $row;
        }
        if (isset($stmt) && $stmt instanceof mysqli_stmt)
            $stmt->close();

        respuestaJSON("success", "OK", ["rows" => $rows]);
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