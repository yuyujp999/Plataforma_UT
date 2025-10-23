<?php
// controladores/docentes/DocenteController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class DocenteController
{
  /**
   * 🧭 Obtener todas las materias asignadas al docente
   */
  public static function obtenerMateriasAsignadas($idDocente)
  {
    global $conn;

    if (!$conn || $conn->connect_error) {
      return false;
    }

    $sql = "
      SELECT 
        ad.id_asignacion_docente,
        cnm.nombre AS codigo_materia,
        m.nombre_materia AS nombre_materia,
        cng.nombre AS grupo
      FROM asignaciones_docentes AS ad
      INNER JOIN cat_nombres_materias AS cnm
        ON ad.id_nombre_materia = cnm.id_nombre_materia
      LEFT JOIN asignar_materias AS am
        ON ad.id_nombre_materia = am.id_nombre_materia
      LEFT JOIN materias AS m
        ON am.id_materia = m.id_materia
      LEFT JOIN cat_nombres_grupo AS cng
        ON am.id_nombre_grupo_int = cng.id_nombre_grupo
      WHERE ad.id_docente = ?
      ORDER BY cnm.nombre ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDocente);
    $stmt->execute();
    return $stmt->get_result();
  }

  /**
   * 👥 Obtener los grupos únicos asignados al docente
   */
  public static function obtenerGruposAsignados($idDocente)
  {
    global $conn;

    if (!$conn || $conn->connect_error) {
      return [];
    }

    $sql = "
      SELECT DISTINCT 
          g.id_grupo,
          cng.nombre AS grupo,
          cns.nombre AS semestre
      FROM asignaciones_docentes ad
      INNER JOIN cat_nombres_materias cnm 
          ON ad.id_nombre_materia = cnm.id_nombre_materia
      INNER JOIN asignar_materias am 
          ON cnm.id_nombre_materia = am.id_nombre_materia
      INNER JOIN cat_nombres_grupo cng 
          ON am.id_nombre_grupo_int = cng.id_nombre_grupo
      INNER JOIN grupos g 
          ON cng.id_nombre_grupo = g.id_nombre_grupo
      INNER JOIN cat_nombres_semestre cns 
          ON g.id_nombre_semestre = cns.id_nombre_semestre
      WHERE ad.id_docente = ?
      ORDER BY cng.nombre ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDocente);
    $stmt->execute();
    $result = $stmt->get_result();

    $grupos = [];
    while ($row = $result->fetch_assoc()) {
      $grupos[] = [
        'id_grupo' => $row['id_grupo'],
        'nombre'   => $row['grupo'],
        'semestre' => $row['semestre']
      ];
    }

    return $grupos;
  }
}
?>
