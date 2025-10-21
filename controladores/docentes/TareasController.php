<?php
// controladores/docentes/TareasController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class TareasController
{
  /**
   * Subir una nueva tarea vinculada a una materia (asignación docente)
   */
  public static function subirTarea($idAsignacion, $titulo, $descripcion, $fecha_entrega, $archivo)
  {
    global $conn;

    if (!$conn || $conn->connect_error) {
      return ["error" => true, "mensaje" => "Error de conexión con la base de datos."];
    }

    // === Validación básica ===
    if (empty($idAsignacion) || empty($titulo)) {
      return ["error" => true, "mensaje" => "Faltan datos obligatorios."];
    }

    // === Procesar archivo si existe ===
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

    // === Guardar en la BD ===
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
   * Obtener todas las tareas de una materia específica
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
}
?>
