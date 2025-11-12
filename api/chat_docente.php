<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . "/../conexion/conexion.php";
include_once __DIR__ . "/../controladores/docentes/ChatController.php";

$chat = new ChatController();
$action = $_GET['action'] ?? '';

$idDocente = $_SESSION['usuario']['id_docente'] ?? 0;

switch ($action) {

  // 🔹 Obtener los grupos del docente
  case 'grupos':
    echo json_encode($chat->obtenerGruposDocente($idDocente));
    break;

  // 🔹 Obtener los alumnos del grupo
  case 'alumnos':
    $idGrupo = intval($_GET['id_grupo'] ?? 0);
    echo json_encode($chat->obtenerAlumnosPorGrupo($idGrupo));
    break;

  // 🔹 Crear o recuperar un chat
  case 'crear_chat':
    $idAlumno = intval($_POST['id_alumno'] ?? 0);
    $idGrupo = intval($_POST['id_grupo'] ?? 0);
    $idChat = $chat->obtenerOCrearChat($idDocente, $idAlumno, $idGrupo);
    echo json_encode(['id_chat' => $idChat]);
    break;

  // 🔹 Obtener mensajes del chat
  case 'mensajes':
    $idChat = intval($_GET['id_chat'] ?? 0);
    echo json_encode($chat->obtenerMensajes($idChat));
    break;

  // 🔹 Enviar un mensaje
  case 'enviar':
    $idChat = intval($_POST['id_chat'] ?? 0);
    $remitente = $_POST['remitente'] ?? 'docente';
    $contenido = trim($_POST['contenido'] ?? '');
    $chat->enviarMensaje($idChat, $remitente, $contenido);
    echo json_encode(['status' => 'ok']);
    break;

    // 🔹 Marcar mensajes como leídos
case 'marcar_leido':
  $id_chat = intval($_POST['id_chat'] ?? 0);
  $remitente = trim($_POST['remitente'] ?? '');
  $ok = $chat->marcarComoLeido($id_chat, $remitente);
  echo json_encode(['status' => $ok ? 'ok' : 'error']);
  break;

// 🔹 Eliminar mensaje
case 'eliminar_mensaje':
  $id_mensaje = intval($_POST['id_mensaje'] ?? 0);
  $ok = $chat->eliminarMensaje($id_mensaje);
  echo json_encode(['status' => $ok ? 'ok' : 'error']);
  break;

    // 🔹 Contar mensajes no leídos
    case 'mensajes_no_leidos':
  header('Content-Type: application/json; charset=utf-8');
  if (!isset($_SESSION['usuario']['id_docente'])) {
    echo json_encode(['error' => 'Sesión inválida', 'no_leidos' => 0]);
    exit;
  }

  $idUsuario = intval($_SESSION['usuario']['id_docente']);
  $tipoUsuario = 'docente';
  $noLeidos = $chat->contarMensajesNoLeidos($idUsuario, $tipoUsuario);

  echo json_encode(['no_leidos' => $noLeidos]);
  break;

  case 'listar_chats':
  $idUsuario = intval($_SESSION['usuario']['id_docente'] ?? 0);
  if ($idUsuario > 0) {
    $chats = $chat->obtenerChatsDocente($idUsuario);
    echo json_encode(['chats' => $chats]);
  } else {
    echo json_encode(['chats' => []]);
  }
  break;





  default:
    echo json_encode(['error' => 'Acción no válida']);
    break;
}
?>
