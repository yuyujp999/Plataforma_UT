<?php
// controladores/docentes/TareasController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class TareasController
{
  /**
   * 🟢 Subir una nueva tarea vinculada a una materia (asignación docente)
   */
  public static function subirTarea($idAsignacion, $titulo, $descripcion, $fecha_entrega, $archivo)
  {
    global $conn;

    if (!$conn || $conn->connect_error) {
      return ["error" => true, "mensaje" => "Error de conexión con la base de datos."];
    }

    if (empty($idAsignacion) || empty($titulo)) {
      return ["error" => true, "mensaje" => "Faltan datos obligatorios."];
    }

    // === Procesar archivo ===
    $archivoRuta = null;
    if (!empty($archivo['name'])) {
      $nombreArchivo = time() . "_" . basename($archivo['name']);
      $directorio = __DIR__ . "/../../uploads/tareas/";

      if (!file_exists($directorio)) mkdir($directorio, 0777, true);

      $rutaDestino = $directorio . $nombreArchivo;
      if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        $archivoRuta = "uploads/tareas/" . $nombreArchivo;
      } else {
        return ["error" => true, "mensaje" => "No se pudo subir el archivo."];
      }
    }

    $titulo = $conn->real_escape_string($titulo);
    $descripcion = $conn->real_escape_string($descripcion);
    $sql = "INSERT INTO tareas_materias 
            (id_asignacion_docente, titulo, descripcion, archivo, fecha_entrega)
            VALUES ($idAsignacion, '$titulo', '$descripcion', " .
            ($archivoRuta ? "'$archivoRuta'" : "NULL") . ", " .
            ($fecha_entrega ? "'$fecha_entrega'" : "NULL") . ")";

    if ($conn->query($sql)) {
      return ["error" => false, "mensaje" => "✅ Tarea subida correctamente."];
    } else {
      return ["error" => true, "mensaje" => "Error al guardar: " . $conn->error];
    }
  }

  /**
   * 📘 Obtener todas las tareas de una materia específica
   */
  public static function obtenerTareasPorMateria($idAsignacion)
  {
    global $conn;

    $sql = "SELECT id_tarea, titulo, descripcion, archivo, fecha_entrega, fecha_creacion
            FROM tareas_materias
            WHERE id_asignacion_docente = $idAsignacion
            ORDER BY fecha_creacion DESC";

    return $conn->query($sql);
  }

  /**
   * 📄 Obtener una tarea específica por ID
   */
  public static function obtenerTarea($idTarea)
  {
    global $conn;

    $sql = "SELECT * FROM tareas_materias WHERE id_tarea = $idTarea LIMIT 1";
    $resultado = $conn->query($sql);
    return $resultado ? $resultado->fetch_assoc() : null;
  }

  /**
   * ✏️ Editar una tarea existente
   */
  public static function editarTarea($idTarea, $titulo, $descripcion, $fecha_entrega, $archivo = null)
  {
    global $conn;

    if (!$conn || $conn->connect_error) {
      return ["error" => true, "mensaje" => "Error de conexión con la base de datos."];
    }

    $titulo = $conn->real_escape_string($titulo);
    $descripcion = $conn->real_escape_string($descripcion);

    // Si se sube un nuevo archivo
    if ($archivo && !empty($archivo['name'])) {
      $nombreArchivo = time() . "_" . basename($archivo['name']);
      $directorio = __DIR__ . "/../../uploads/tareas/";
      if (!file_exists($directorio)) mkdir($directorio, 0777, true);

      $rutaDestino = $directorio . $nombreArchivo;
      if (move_uploaded_file($archivo["tmp_name"], $rutaDestino)) {
        $archivoRuta = "uploads/tareas/" . $nombreArchivo;
        $sql = "UPDATE tareas_materias 
                SET titulo='$titulo', descripcion='$descripcion', fecha_entrega=" .
                ($fecha_entrega ? "'$fecha_entrega'" : "NULL") . ", archivo='$archivoRuta'
                WHERE id_tarea = $idTarea";
      } else {
        return ["error" => true, "mensaje" => "No se pudo reemplazar el archivo."];
      }
    } else {
      $sql = "UPDATE tareas_materias 
              SET titulo='$titulo', descripcion='$descripcion', fecha_entrega=" .
              ($fecha_entrega ? "'$fecha_entrega'" : "NULL") . "
              WHERE id_tarea = $idTarea";
    }

    if ($conn->query($sql)) {
      return ["error" => false, "mensaje" => "✅ Tarea actualizada correctamente."];
    } else {
      return ["error" => true, "mensaje" => "❌ Error al actualizar: " . $conn->error];
    }
  }

  /**
   * 🗑️ Eliminar una tarea por ID
   */
  public static function eliminarTarea($idTarea)
  {
    global $conn;

    if (!$conn || $conn->connect_error) {
      return ["error" => true, "mensaje" => "Error de conexión con la base de datos."];
    }

    // Buscar el archivo para eliminarlo físicamente
    $q = $conn->query("SELECT archivo FROM tareas_materias WHERE id_tarea = $idTarea");
    if ($q && $row = $q->fetch_assoc()) {
      if (!empty($row['archivo']) && file_exists(__DIR__ . "/../../" . $row['archivo'])) {
        unlink(__DIR__ . "/../../" . $row['archivo']);
      }
    }

    $conn->query("DELETE FROM tareas_materias WHERE id_tarea = $idTarea");

    return ["error" => false, "mensaje" => "🗑️ Tarea eliminada correctamente."];
  }
}
?>
