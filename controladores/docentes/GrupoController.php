<?php
// controladores/docentes/GrupoController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class GrupoController
{
  /* ========================================================
     📋 Información general del grupo
  ======================================================== */
  public static function obtenerInfoGrupo($idGrupo)
  {
    global $conn;

    $sql = "
      SELECT 
        g.id_grupo,
        cng.nombre AS nombre_grupo,
        cns.nombre AS semestre,
        ca.nombre_carrera AS carrera
      FROM grupos g
      INNER JOIN cat_nombres_grupo cng 
        ON g.id_nombre_grupo = cng.id_nombre_grupo
      INNER JOIN cat_nombres_semestre cns 
        ON g.id_nombre_semestre = cns.id_nombre_semestre
      INNER JOIN semestres s 
        ON s.id_nombre_semestre = cns.id_nombre_semestre
      INNER JOIN carreras ca 
        ON s.id_carrera = ca.id_carrera
      WHERE g.id_grupo = ?
      LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idGrupo);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  /* ========================================================
     👥 Alumnos del grupo
  ======================================================== */
  public static function obtenerAlumnosPorGrupo($idGrupo)
  {
    global $conn;

    $sql = "
      SELECT 
        a.id_alumno,
        a.matricula,
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno
      FROM asignaciones_grupo_alumno aga
      INNER JOIN alumnos a 
        ON aga.id_alumno = a.id_alumno
      WHERE aga.id_grupo = ?
      ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idGrupo);
    $stmt->execute();
    return $stmt->get_result();
  }

  /* ========================================================
     📘 Materias del docente en este grupo
  ======================================================== */
  public static function obtenerMateriasPorDocenteYGrupo($idDocente, $idGrupo)
  {
    global $conn;

    // Obtener id_nombre_grupo del grupo actual
    $stmt = $conn->prepare("SELECT id_nombre_grupo FROM grupos WHERE id_grupo = ?");
    $stmt->bind_param("i", $idGrupo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) return false;
    $idNombreGrupo = intval($row['id_nombre_grupo']);

    // Materias del docente en este grupo
    $sql = "
      SELECT DISTINCT
        ad.id_asignacion_docente,
        cnm.nombre AS codigo_materia,
        m.nombre_materia AS nombre_materia,
        cng.nombre AS grupo
      FROM asignaciones_docentes ad
      INNER JOIN cat_nombres_materias cnm 
        ON ad.id_nombre_materia = cnm.id_nombre_materia
      INNER JOIN asignar_materias am 
        ON am.id_nombre_materia = cnm.id_nombre_materia
      INNER JOIN materias m 
        ON am.id_materia = m.id_materia
      INNER JOIN grupos g 
        ON g.id_nombre_grupo = am.id_nombre_grupo_int
      INNER JOIN cat_nombres_grupo cng 
        ON g.id_nombre_grupo = cng.id_nombre_grupo
      WHERE ad.id_docente = ?
        AND g.id_nombre_grupo = ?
      ORDER BY m.nombre_materia ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $idDocente, $idNombreGrupo);
    $stmt->execute();
    return $stmt->get_result();
  }
}
?>
