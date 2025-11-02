<?php
include_once __DIR__ . "/../../conexion/conexion.php";

// --- Peticiones AJAX ---
if (isset($_GET['action'])) {
  session_start();
  $idDocente = $_SESSION['id_docente'] ?? null;

  if (!$idDocente) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida.']);
    exit;
  }

  if ($_GET['action'] === 'actualizarDatos') {
    $nombre    = $_POST['nombre'] ?? '';
    $telefono  = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $correo    = $_POST['correo'] ?? '';

    $response = AjustesController::actualizarDatos($idDocente, $nombre, $telefono, $direccion, $correo);
    echo json_encode($response);
    exit;
  }

  if ($_GET['action'] === 'cambiarPassword') {
    $actual = $_POST['actual'] ?? '';
    $nueva  = $_POST['nueva'] ?? '';

    $response = AjustesController::cambiarPassword($idDocente, $actual, $nueva);
    echo json_encode($response);
    exit;
  }
}

class AjustesController
{
  /** 🪪 Actualizar datos personales */
  public static function actualizarDatos($idDocente, $nombre, $telefono, $direccion, $correo)
  {
    global $conn;

    if (empty($nombre) || empty($correo)) {
      return ['success' => false, 'mensaje' => 'El nombre y el correo no pueden estar vacíos.'];
    }

    $sql = "UPDATE docentes 
            SET nombre = ?, telefono = ?, direccion = ?, correo_personal = ? 
            WHERE id_docente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $telefono, $direccion, $correo, $idDocente);

    if ($stmt->execute()) {
      return ['success' => true, 'mensaje' => 'Datos personales actualizados correctamente.'];
    } else {
      return ['success' => false, 'mensaje' => 'Error al actualizar los datos personales.'];
    }
  }

  /** 🔐 Cambiar contraseña validando la actual */
  public static function cambiarPassword($idDocente, $actual, $nueva)
  {
    global $conn;

    if (empty($actual) || empty($nueva)) {
      return ['success' => false, 'mensaje' => 'Completa todos los campos.'];
    }

    $sql = "SELECT password FROM docentes WHERE id_docente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDocente);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row || !password_verify($actual, $row['password'])) {
      return ['success' => false, 'mensaje' => 'La contraseña actual es incorrecta.'];
    }

    $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE docentes SET password = ? WHERE id_docente = ?");
    $update->bind_param("si", $nuevoHash, $idDocente);

    if ($update->execute()) {
      return ['success' => true, 'mensaje' => 'Contraseña actualizada correctamente.'];
    } else {
      return ['success' => false, 'mensaje' => 'Error al actualizar la contraseña.'];
    }
  }
}
