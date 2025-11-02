<?php
class MateriasAlumnoController {

  // 🔹 Obtener información general de la materia
  public static function obtenerDatosMateria($idAsignacion) {
    include __DIR__ . '/../../conexion/conexion.php';

    $sql = "
      SELECT 
        cnm.nombre AS codigo_materia,
        m.nombre_materia,
        cng.nombre AS grupo,
        d.nombre AS nombre_docente,
        d.apellido_paterno AS apellido_docente
      FROM asignaciones_docentes ad
      INNER JOIN cat_nombres_materias cnm 
        ON ad.id_nombre_materia = cnm.id_nombre_materia
      LEFT JOIN asignar_materias am 
        ON ad.id_nombre_materia = am.id_nombre_materia
      LEFT JOIN materias m 
        ON am.id_materia = m.id_materia
      LEFT JOIN cat_nombres_grupo cng 
        ON am.id_nombre_grupo_int = cng.id_nombre_grupo
      LEFT JOIN docentes d 
        ON ad.id_docente = d.id_docente
      WHERE ad.id_asignacion_docente = $idAsignacion
      LIMIT 1
    ";

    $resultado = $conn->query($sql);
    return $resultado ? $resultado->fetch_assoc() : null;
  }

  // 🔹 Obtener tareas asociadas a la materia
  public static function obtenerTareas($idAsignacion) {
    include __DIR__ . '/../../conexion/conexion.php';
    $sql = "
      SELECT 
        id_tarea, 
        titulo, 
        descripcion, 
        archivo, 
        fecha_entrega, 
        fecha_creacion
      FROM tareas_materias
      WHERE id_asignacion_docente = $idAsignacion
      ORDER BY fecha_creacion DESC
    ";
    return $conn->query($sql);
  }

  // 🔹 Obtener recursos (material de apoyo)
  public static function obtenerRecursos($idAsignacion) {
    include __DIR__ . '/../../conexion/conexion.php';
    $sql = "
      SELECT 
        id_recurso, 
        titulo, 
        descripcion, 
        archivo, 
        fecha_creacion
      FROM recursos_materias
      WHERE id_asignacion_docente = $idAsignacion
      ORDER BY fecha_creacion DESC
    ";
    return $conn->query($sql);
  }
}
