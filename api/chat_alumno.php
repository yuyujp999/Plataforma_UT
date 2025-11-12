<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . "/../conexion/conexion.php";

$action = $_GET['action'] ?? '';
$idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;

if (!$idAlumno) {
  echo json_encode([]);
  exit;
}

switch ($action) {
  /* ==========================================================
     🔹 1. Mostrar los docentes que tiene el alumno actualmente
  ========================================================== */
  case 'chats':
    $sql = "
      SELECT DISTINCT 
        d.id_docente,
        CONCAT(d.nombre, ' ', d.apellido_paterno, ' ', d.apellido_materno) AS nombre_docente,
        g.id_grupo,
        cng.nombre AS grupo
      FROM asignaciones_docentes ad
      INNER JOIN asignar_materias am ON ad.id_nombre_materia = am.id_nombre_materia
      INNER JOIN grupos g ON am.id_nombre_grupo_int = g.id_nombre_grupo
      INNER JOIN cat_nombres_grupo cng ON g.id_nombre_grupo = cng.id_nombre_grupo
      INNER JOIN asignaciones_grupo_alumno aga ON aga.id_grupo = g.id_grupo
      INNER JOIN docentes d ON ad.id_docente = d.id_docente
      WHERE aga.id_alumno = :id_alumno
      ORDER BY d.nombre ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_alumno' => $idAlumno]);
    $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔸 Crear chats automáticos si no existen
    $chats = [];
    foreach ($docentes as $d) {
      $q = "SELECT id_chat FROM chats 
            WHERE id_docente = :id_docente AND id_alumno = :id_alumno";
      $check = $pdo->prepare($q);
      $check->execute([
        'id_docente' => $d['id_docente'],
        'id_alumno' => $idAlumno
      ]);
      $row = $check->fetch(PDO::FETCH_ASSOC);

      if (!$row) {
        $insert = $pdo->prepare("INSERT INTO chats (id_docente, id_alumno, id_grupo) 
                                 VALUES (:id_docente, :id_alumno, :id_grupo)");
        $insert->execute([
          'id_docente' => $d['id_docente'],
          'id_alumno' => $idAlumno,
          'id_grupo' => $d['id_grupo']
        ]);
        $id_chat = $pdo->lastInsertId();
      } else {
        $id_chat = $row['id_chat'];
      }

      $chats[] = [
        'id_chat' => $id_chat,
        'nombre_docente' => $d['nombre_docente'],
        'grupo' => $d['grupo']
      ];
    }

    echo json_encode($chats);
    break;

  /* ==========================================================
     🔹 2. Obtener mensajes de un chat
  ========================================================== */
  case 'mensajes':
    $id_chat = intval($_GET['id_chat'] ?? 0);
    $stmt = $pdo->prepare("SELECT id_mensaje, remitente, contenido, fecha_envio, leido 
                           FROM mensajesDocente 
                           WHERE id_chat = :id_chat AND eliminado = 0
                           ORDER BY fecha_envio ASC");
    $stmt->execute(['id_chat' => $id_chat]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    break;

  /* ==========================================================
     🔹 3. Enviar mensaje del alumno
  ========================================================== */
  case 'enviar':
    $id_chat = intval($_POST['id_chat'] ?? 0);
    $contenido = trim($_POST['contenido'] ?? '');
    if ($id_chat && $contenido !== '') {
      $stmt = $pdo->prepare("INSERT INTO mensajesDocente (id_chat, remitente, contenido) 
                             VALUES (:id_chat, 'alumno', :contenido)");
      $stmt->execute(['id_chat' => $id_chat, 'contenido' => $contenido]);
      echo json_encode(['status' => 'ok']);
    } else {
      echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
    }
    break;

  /* ==========================================================
     🔹 4. Marcar mensajes como leídos
  ========================================================== */
  case 'marcar_leido':
    $id_chat = intval($_POST['id_chat'] ?? 0);
    $stmt = $pdo->prepare("UPDATE mensajesDocente 
                           SET leido = 1 
                           WHERE id_chat = :id_chat AND remitente = 'docente'");
    $stmt->execute(['id_chat' => $id_chat]);
    echo json_encode(['status' => 'ok']);
    break;

      /* ==========================================================
     🔹 5. Eliminar mensaje del alumno (solo sus mensajes)
  ========================================================== */
  case 'eliminar_mensaje':
    $id_mensaje = intval($_POST['id_mensaje'] ?? 0);

    // Verificar que el mensaje pertenece al alumno
    $sql = "SELECT m.id_chat, c.id_alumno
            FROM mensajesDocente m
            INNER JOIN chats c ON m.id_chat = c.id_chat
            WHERE m.id_mensaje = :id_mensaje";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_mensaje' => $id_mensaje]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($msg && intval($msg['id_alumno']) === $idAlumno) {
      // Marcar como eliminado
      $delete = $pdo->prepare("UPDATE mensajesDocente SET eliminado = 1 WHERE id_mensaje = :id_mensaje");
      $delete->execute(['id_mensaje' => $id_mensaje]);
      echo json_encode(['status' => 'ok']);
    } else {
      echo json_encode(['status' => 'error', 'msg' => 'No autorizado']);
    }
    break;

      /* ==========================================================
     🔹 6. Contar mensajes no leídos del alumno
  ========================================================== */
  case 'mensajes_no_leidos':
    $idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;

    if ($idAlumno) {
      $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total 
        FROM mensajesDocente m
        INNER JOIN chats c ON c.id_chat = m.id_chat
        WHERE c.id_alumno = :id_alumno
          AND m.remitente = 'docente'
          AND m.leido = 0
      ");
      $stmt->execute(['id_alumno' => $idAlumno]);
      $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
      echo json_encode(['total' => $total]);
    } else {
      echo json_encode(['total' => 0]);
    }
    break;



  default:
    echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
    break;
}

// 🔹 Buscar usuarios (alumnos y docentes)
if ($action === 'buscar_usuarios') {
    $query = $_GET['query'] ?? '';
    $query = "%$query%";
    $stmt = $pdo->prepare("
        SELECT id_docente AS id_usuario, CONCAT(nombre, ' ', apellido_paterno) AS nombre, 'docente' AS rol
        FROM docentes
        WHERE nombre LIKE ? OR apellido_paterno LIKE ?
        UNION
        SELECT id_alumno AS id_usuario, CONCAT(nombre, ' ', apellido_paterno) AS nombre, 'alumno' AS rol
        FROM alumnos
        WHERE nombre LIKE ? OR apellido_paterno LIKE ?
        LIMIT 20
    ");
    $stmt->execute([$query, $query, $query, $query]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 🔹 Crear (o abrir si ya existe) chat entre el alumno actual y otro usuario
if ($action === 'crear_chat') {
    $idUsuario = $_POST['id_usuario'] ?? null;
    $rol = $_POST['rol'] ?? '';
    $idAlumno = $_SESSION['usuario']['id_alumno'] ?? null;

    if (!$idAlumno || !$idUsuario) {
        echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
        exit;
    }

    // Verificar si ya existe el chat
    $stmt = $pdo->prepare("
        SELECT id_chat FROM chat
        WHERE (id_alumno = ? AND id_docente = ?) OR (id_alumno = ? AND id_docente = ?)
        LIMIT 1
    ");
    $idDocente = ($rol === 'docente') ? $idUsuario : null;
    $stmt->execute([$idAlumno, $idDocente, $idAlumno, $idDocente]);
    $chat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chat) {
        echo json_encode(['status' => 'ok', 'id_chat' => $chat['id_chat']]);
        exit;
    }

    // Crear nuevo chat si no existe
    $stmt = $pdo->prepare("INSERT INTO chat (id_alumno, id_docente, fecha_creacion) VALUES (?, ?, NOW())");
    $stmt->execute([$idAlumno, $idDocente]);
    echo json_encode(['status' => 'ok', 'id_chat' => $pdo->lastInsertId()]);
    exit;
}

