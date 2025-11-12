<?php
// controladores/alumnos/ChatAlumnoController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class ChatAlumnoController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // 🔹 Obtener los docentes con los que el alumno tiene chats
  public function obtenerChatsAlumno($id_alumno)
  {
    $sql = "
      SELECT 
        c.id_chat,
        d.id_docente,
        CONCAT(d.nombre, ' ', d.apellido_paterno, ' ', d.apellido_materno) AS nombre_docente,
        ng.nombre AS grupo
      FROM chats c
      JOIN docentes d ON c.id_docente = d.id_docente
      JOIN cat_nombres_grupo ng ON ng.id_nombre_grupo = c.id_grupo
      WHERE c.id_alumno = :id_alumno
      GROUP BY c.id_chat
      ORDER BY ng.nombre ASC
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id_alumno' => $id_alumno]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // 🔹 Obtener mensajes
  public function obtenerMensajes($id_chat)
  {
    $sql = "SELECT id_mensaje, remitente, contenido, fecha_envio, leido 
            FROM mensajesDocente
            WHERE id_chat = :id_chat AND eliminado = 0
            ORDER BY fecha_envio ASC";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id_chat' => $id_chat]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // 🔹 Enviar mensaje del alumno
  public function enviarMensaje($id_chat, $remitente, $contenido)
  {
    $sql = "INSERT INTO mensajesDocente (id_chat, remitente, contenido, fecha_envio)
            VALUES (:id_chat, :remitente, :contenido, NOW())";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      'id_chat' => $id_chat,
      'remitente' => $remitente,
      'contenido' => $contenido
    ]);
  }

  // 🔹 Marcar como leído
  public function marcarComoLeido($id_chat, $remitente)
  {
    $sql = "UPDATE mensajesDocente 
            SET leido = 1 
            WHERE id_chat = :id_chat AND remitente != :remitente";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      'id_chat' => $id_chat,
      'remitente' => $remitente
    ]);
  }

  public static function obtenerDocentesPorAlumno($idAlumno) {
    global $conn;

    $sql = "
      SELECT DISTINCT 
        d.id_docente,
        CONCAT(d.nombre, ' ', d.apellido_paterno, ' ', d.apellido_materno) AS nombre_docente,
        cng.nombre AS grupo,
        cns.nombre AS semestre,
        t.nombre AS tetra
      FROM asignaciones_grupo_alumno aga
      INNER JOIN grupos g ON aga.id_grupo = g.id_grupo
      INNER JOIN cat_nombres_grupo cng ON g.id_nombre_grupo = cng.id_nombre_grupo
      INNER JOIN cat_nombres_semestre cns ON g.id_nombre_semestre = cns.id_nombre_semestre
      INNER JOIN asignar_materias am ON am.id_nombre_grupo_int = cng.id_nombre_grupo
      INNER JOIN asignaciones_docentes ad 
        ON ad.id_nombre_materia = am.id_nombre_materia
      INNER JOIN docentes d ON ad.id_docente = d.id_docente
      INNER JOIN tetramestres t ON g.id_tetramestre = t.id_tetramestre
      WHERE aga.id_alumno = ?
        AND t.activo = 1
      ORDER BY d.apellido_paterno, d.apellido_materno, d.nombre
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

}
?>
