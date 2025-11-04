<?php
// controladores/docentes/ChatController.php
include_once __DIR__ . "/../../conexion/conexion.php";

class ChatController
{
  private $pdo;

  public function __construct()
  {
    global $pdo; // Usamos la conexión PDO ya incluida en tu sistema
    $this->pdo = $pdo;
  }

  /* ========================================================
     🔹 Obtener grupos del docente (basado en asignaciones)
  ======================================================== */
  public function obtenerGruposDocente($idDocente)
  {
    $sql = "
      SELECT DISTINCT 
        g.id_grupo, 
        cng.nombre AS nombre_grupo
      FROM asignaciones_docentes ad
      INNER JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
      INNER JOIN grupos g ON g.id_nombre_grupo = am.id_nombre_grupo_int
      INNER JOIN cat_nombres_grupo cng ON g.id_nombre_grupo = cng.id_nombre_grupo
      WHERE ad.id_docente = :id_docente
      ORDER BY cng.nombre ASC
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id_docente' => $idDocente]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ========================================================
     👥 Obtener alumnos de un grupo
  ======================================================== */
  public function obtenerAlumnosPorGrupo($idGrupo)
  {
    $sql = "
      SELECT DISTINCT 
        a.id_alumno,
        CONCAT(a.nombre, ' ', a.apellido_paterno, ' ', a.apellido_materno) AS nombre
      FROM asignaciones_grupo_alumno aga
      INNER JOIN alumnos a ON aga.id_alumno = a.id_alumno
      WHERE aga.id_grupo = :id_grupo
      ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id_grupo' => $idGrupo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ========================================================
     💬 Crear u obtener chat entre docente y alumno
  ======================================================== */
  public function obtenerOCrearChat($idDocente, $idAlumno, $idGrupo)
  {
    $sql = "SELECT id_chat FROM chats WHERE id_docente = :id_docente AND id_alumno = :id_alumno";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      'id_docente' => $idDocente,
      'id_alumno' => $idAlumno
    ]);
    $chat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chat) {
      return $chat['id_chat'];
    } else {
      $sql = "INSERT INTO chats (id_docente, id_alumno, id_grupo) VALUES (:id_docente, :id_alumno, :id_grupo)";
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute([
        'id_docente' => $idDocente,
        'id_alumno' => $idAlumno,
        'id_grupo' => $idGrupo
      ]);
      return $this->pdo->lastInsertId();
    }
  }

  /* ========================================================
     📜 Obtener mensajes del chat
  ======================================================== */
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


  /* ========================================================
     📨 Enviar mensaje
  ======================================================== */
  public function enviarMensaje($idChat, $remitente, $contenido)
  {
    if(empty(trim($contenido))) return false;

    $sql = "INSERT INTO mensajesDocente (id_chat, remitente, contenido) 
            VALUES (:id_chat, :remitente, :contenido)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      'id_chat' => $idChat,
      'remitente' => $remitente ==='docente' ? 'docente' : 'alumno',
      'contenido' =>  htmlspecialchars($contenido, ENT_QUOTES, 'UTF-8')
    ]);
    
  }

   // 🔹 Marcar mensajes como leídos
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

// 🔹 Eliminar mensaje
public function eliminarMensaje($id_mensaje)
{
  $sql = "UPDATE mensajesDocente 
          SET eliminado = 1, contenido = '[Mensaje eliminado]' 
          WHERE id_mensaje = :id_mensaje";
  $stmt = $this->pdo->prepare($sql);
  return $stmt->execute(['id_mensaje' => $id_mensaje]);
}

}
?>
