<?php
session_start();

 require_once __DIR__ . '/../conexion/conexion.php'; // ⚠️asegúrate de incluir tu conexión
/* ================================
 🟥1. Marcar usuario como OFFLINE
================================ */
if (isset($_SESSION['rol'])) {
 $rol = $_SESSION['rol'];
 if ($rol === 'docente' && isset($_SESSION['id_docente'])) {
 $id = $_SESSION['id_docente'];
 $stmt = $conn->prepare("UPDATE docentes SET en_linea = FALSE, ultima_actividad = NOW()
WHERE id_docente = ?");
 $stmt->bind_param('i', $id);
 $stmt->execute();
 $stmt->close();
 } elseif ($rol === 'alumno' && isset($_SESSION['id_alumno'])) {
 $id = $_SESSION['id_alumno'];
 $stmt = $conn->prepare("UPDATE alumnos SET en_linea = FALSE, ultima_actividad = NOW()
WHERE id_alumno = ?");
 $stmt->bind_param('i', $id);
 $stmt->execute();
 $stmt->close();
 }
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si existe cookie de sesión, eliminarla
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finalmente destruir la sesión
session_destroy();

// Redirigir al inicio
header("Location: /Plataforma_UT/inicio.php");
exit;