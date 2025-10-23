<?php
// controladores/docentes/RecursosController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class RecursosController
{
  /* ======================================================
     📤 SUBIR NUEVO RECURSO
  ====================================================== */
  public static function subirRecurso($idAsignacion, $titulo, $descripcion, $archivo)
  {
    global $conn;

    if (!$conn || $conn->connect_error) {
      return ["error" => true, "mensaje" => "Error de conexión con la base de datos."];
    }

    if (empty($idAsignacion) || empty($titulo)) {
      return ["error" => true, "mensaje" => "El título y la asignación son obligatorios."];
    }

    $rutaArchivo = null;

    if (!empty($archivo['name'])) {
      $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
      $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

      if (!in_array($ext, $permitidos)) {
        return ["error" => true, "mensaje" => "Tipo de archivo no permitido."];
      }

      $nombreFinal = time() . "_" . basename($archivo['name']);
      $rutaDestino = __DIR__ . "/../../uploads/recursos/" . $nombreFinal;

      if (!is_dir(__DIR__ . "/../../uploads/recursos")) {
        mkdir(__DIR__ . "/../../uploads/recursos", 0777, true);
      }

      if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        $rutaArchivo = "uploads/recursos/" . $nombreFinal;
      } else {
        return ["error" => true, "mensaje" => "No se pudo guardar el archivo."];
      }
    }

    $stmt = $conn->prepare("INSERT INTO recursos_materias (id_asignacion_docente, titulo, descripcion, archivo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $idAsignacion, $titulo, $descripcion, $rutaArchivo);

    if ($stmt->execute()) {
      return ["error" => false, "mensaje" => "Recurso subido correctamente."];
    } else {
      return ["error" => true, "mensaje" => "Error al guardar en la base de datos."];
    }
  }

  /* ======================================================
     📄 OBTENER RECURSOS POR MATERIA
  ====================================================== */
  public static function obtenerRecursosPorMateria($idAsignacion)
  {
    global $conn;
    $sql = "SELECT * FROM recursos_materias WHERE id_asignacion_docente = ? ORDER BY fecha_creacion DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idAsignacion);
    $stmt->execute();
    return $stmt->get_result();
  }

  /* ======================================================
     ✏️ OBTENER UN RECURSO ESPECÍFICO
  ====================================================== */
  public static function obtenerRecurso($idRecurso)
  {
    global $conn;
    $sql = "SELECT * FROM recursos_materias WHERE id_recurso = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idRecurso);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  /* ======================================================
     🖊️ EDITAR RECURSO EXISTENTE
  ====================================================== */
  public static function editarRecurso($idRecurso, $titulo, $descripcion, $archivo)
  {
    global $conn;

    $recurso = self::obtenerRecurso($idRecurso);
    if (!$recurso) {
      return ["error" => true, "mensaje" => "Recurso no encontrado."];
    }

    $rutaArchivo = $recurso["archivo"];

    if (!empty($archivo['name'])) {
      $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
      $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

      if (!in_array($ext, $permitidos)) {
        return ["error" => true, "mensaje" => "Tipo de archivo no permitido."];
      }

      if (!empty($rutaArchivo) && file_exists(__DIR__ . "/../../" . $rutaArchivo)) {
        unlink(__DIR__ . "/../../" . $rutaArchivo);
      }

      $nombreFinal = time() . "_" . basename($archivo['name']);
      $rutaDestino = __DIR__ . "/../../uploads/recursos/" . $nombreFinal;

      if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        $rutaArchivo = "uploads/recursos/" . $nombreFinal;
      }
    }

    $stmt = $conn->prepare("UPDATE recursos_materias SET titulo = ?, descripcion = ?, archivo = ? WHERE id_recurso = ?");
    $stmt->bind_param("sssi", $titulo, $descripcion, $rutaArchivo, $idRecurso);
    $stmt->execute();

    return ["error" => false, "mensaje" => "Recurso actualizado correctamente."];
  }

  /* ======================================================
     🗑️ ELIMINAR RECURSO
  ====================================================== */
  public static function eliminarRecurso($idRecurso)
  {
    global $conn;
    $recurso = self::obtenerRecurso($idRecurso);

    if ($recurso && !empty($recurso['archivo']) && file_exists(__DIR__ . "/../../" . $recurso['archivo'])) {
      unlink(__DIR__ . "/../../" . $recurso['archivo']);
    }

    $stmt = $conn->prepare("DELETE FROM recursos_materias WHERE id_recurso = ?");
    $stmt->bind_param("i", $idRecurso);
    return $stmt->execute();
  }
}
?>
