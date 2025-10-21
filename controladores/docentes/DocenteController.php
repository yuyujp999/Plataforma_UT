<?php
// controladores/DocenteController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class DocenteController
{
  /**
   * Obtener todas las materias asignadas al docente
   * @param int $idDocente
   * @return mysqli_result|false
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
  WHERE ad.id_docente = $idDocente
  ORDER BY cnm.nombre ASC
";


    return $conn->query($sql);
  }
}
?>
