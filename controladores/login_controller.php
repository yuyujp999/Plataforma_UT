<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

$rol = trim($_POST['rol'] ?? '');
$pass = $_POST['contrasena'] ?? '';

if (!$rol || $pass === '') {
    die('Datos incompletos.');
}

// Determinar tabla, campo y usuario según rol
switch ($rol) {
    case 'docente':
        $tabla = 'docentes';
        $campo = 'matricula';
        $usuario = trim($_POST['matricula'] ?? '');
        break;

    case 'alumno':
        $tabla = 'alumnos';
        $campo = 'matricula';
        $usuario = trim($_POST['matricula'] ?? '');
        break;

    case 'secretaria':
        $tabla = 'secretarias';
        $campo = 'correo_institucional';
        $usuario = trim($_POST['correo'] ?? '');
        break;

    case 'admin':
        $tabla = 'administradores';
        $campo = 'correo';
        $usuario = trim($_POST['correo'] ?? '');
        break;

    default:
        die('Rol no válido.');
}

if (!$usuario) {
    die('Usuario requerido.');
}

// --- Traer registro (no filtramos aquí por estatus para poder dar mensajes claros) ---
$sql = "SELECT * FROM `$tabla` WHERE `$campo` = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die('Error interno (prepare).');
}
$stmt->bind_param('s', $usuario);
$stmt->execute();
$res = $stmt->get_result();
if (!$res) {
    error_log("Get_result failed: " . $stmt->error);
    die('Error interno (get_result).');
}
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    $_SESSION['login_error'] = 'Usuario no encontrado';
    header('Location: /Plataforma_UT/Login.php?rol=' . urlencode($rol));
    exit;
}

/* ===============================
   🔒 Verificación de contraseña
================================ */
function looks_like_bcrypt_or_argon($s)
{
    return (bool) preg_match('/^\$2[ayb]\$|^\$argon2i\$|^\$argon2id\$/', $s);
}
function looks_like_sha256_hex($s)
{
    return (bool) (strlen($s) === 64 && ctype_xdigit($s));
}

$login_ok = false;
$stored = $row['password'] ?? '';

if ($stored !== '' && looks_like_bcrypt_or_argon($stored)) {
    $login_ok = password_verify($pass, $stored);
} elseif ($stored !== '' && looks_like_sha256_hex($stored)) {
    $login_ok = (hash('sha256', $pass) === strtolower($stored));
} else {
    // soporte legacy (texto plano)
    $login_ok = ($pass === $stored);
}

if (!$login_ok) {
    $_SESSION['login_error'] = 'Contraseña incorrecta';
    header('Location: /Plataforma_UT/Login.php?rol=' . urlencode($rol));
    exit;
}

/* ===============================
   🚧 Validaciones de estado
   (aplican si existen esas columnas)
================================ */
$deleted_at = $row['deleted_at'] ?? null;
$estatus = strtolower($row['estatus'] ?? 'activo');

// Si tiene soft delete, bloquear
if (!is_null($deleted_at) && $deleted_at !== '' && $deleted_at !== '0000-00-00 00:00:00') {
    $_SESSION['login_error'] = 'Tu cuenta no está disponible. Contacta al administrador.';
    header('Location: /Plataforma_UT/Login.php?rol=' . urlencode($rol));
    exit;
}

// Para roles con estatus (docente/alumno; también funcionará si secretarías/admin lo tuvieran)
if (in_array($rol, ['docente', 'alumno'], true) || array_key_exists('estatus', $row)) {
    if ($estatus !== 'activo') {
        if ($estatus === 'baja') {
            $_SESSION['login_error'] = 'Tu cuenta está de baja. Contacta a Secretaría.';
        } elseif ($estatus === 'suspendido') {
            $_SESSION['login_error'] = 'Tu cuenta está suspendida temporalmente.';
        } else {
            $_SESSION['login_error'] = 'Tu cuenta no está activa. Contacta a Secretaría.';
        }
        header('Location: /Plataforma_UT/Login.php?rol=' . urlencode($rol));
        exit;
    }
}

/* ===============================
   ✅ Login OK → crear sesión
================================ */
$_SESSION['rol'] = $rol;

switch ($rol) {
    /* ===============================
       👨‍🏫 DOCENTE
    ================================ */
    case 'docente':
        $_SESSION['usuario'] = [
            'id_docente' => $row['id_docente'] ?? null,
            'matricula' => $row['matricula'] ?? '',
            'nombre' => $row['nombre'] ?? '',
            'apellido_paterno' => $row['apellido_paterno'] ?? '',
            'apellido_materno' => $row['apellido_materno'] ?? ''
        ];
        $_SESSION['id_docente'] = $row['id_docente'] ?? null; // compatibilidad
        $destino = '/Plataforma_UT/vistas/Docentes/dashboardDocente.php';
        break;

    /* ===============================
       🎓 ALUMNO
    ================================ */
    case 'alumno':
        $_SESSION['usuario'] = [
            'id_alumno' => $row['id_alumno'] ?? null,
            'matricula' => $row['matricula'] ?? '',
            'nombre' => $row['nombre'] ?? '',
            'apellido_paterno' => $row['apellido_paterno'] ?? '',
            'apellido_materno' => $row['apellido_materno'] ?? ''
        ];
        $_SESSION['id_alumno'] = $row['id_alumno'] ?? null;
        $destino = '/Plataforma_UT/vistas/Alumnos/dashboardAlumno.php';
        break;

    /* ===============================
       🧑‍💼 SECRETARIA
    ================================ */
    case 'secretaria':
        $_SESSION['usuario'] = [
            'id_secretaria' => $row['id_secretaria'] ?? null,
            'nombre' => $row['nombre'] ?? '',
            'apellido_paterno' => $row['apellido_paterno'] ?? '',
            'apellido_materno' => $row['apellido_materno'] ?? '',
            'correo' => $row['correo_institucional'] ?? '',
            'nivel_acceso' => $row['nivel_acceso'] ?? '',
            'fecha_registro' => $row['fecha_registro'] ?? ''
        ];
        $destino = '/Plataforma_UT/vistas/Secretarias/dashboardSecretaria.php';
        break;

    /* ===============================
       👑 ADMINISTRADOR
    ================================ */
    case 'admin':
        $_SESSION['usuario'] = [
            'id_admin' => $row['id_admin'] ?? null,
            'nombre' => $row['nombre'] ?? '',
            'apellido_paterno' => $row['apellido_paterno'] ?? '',
            'apellido_materno' => $row['apellido_materno'] ?? '',
            'correo' => $row['correo'] ?? '',
            'nivel_acceso' => $row['nivel_acceso'] ?? '',
            'fecha_registro' => $row['fecha_registro'] ?? ''
        ];
        $destino = '/Plataforma_UT/vistas/admin/dashboardAdmin.php';
        break;

    default:
        $destino = '/Plataforma_UT/inicio.php';
        break;
}

header("Location: $destino");
exit;