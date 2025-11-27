<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclusión de archivos
include_once __DIR__ . "/../conexion/conexion.php";
include_once __DIR__ . "/../controladores/docentes/ChatController.php";
include_once __DIR__ . "/../controladores/alumnos/ChatAlumnoController.php";

// ==========================================================
// 1. INICIALIZACIÓN Y SEGURIDAD
// ==========================================================
$action = $_GET['action'] ?? '';
$tipoUsuario = $_SESSION['rol'] ?? ($_SESSION['usuario']['rol'] ?? '');
$idUsuario = $_SESSION['usuario']['id_alumno'] ?? $_SESSION['usuario']['id_docente'] ?? 0;

if (!$idUsuario) {
    echo json_encode(['status' => 'error', 'msg' => 'No hay sesión activa']);
    exit;
}

// Inicialización de controladores
$chatDoc = new ChatController();
$chatAlu = new ChatAlumnoController($pdo);

// ==========================================================
// 2. ACTUALIZACIÓN DE ACTIVIDAD (Heartbeat)
// ==========================================================
$rol = $tipoUsuario;
if ($idUsuario) {
    if ($rol === 'docente') {
        $pdo->prepare("UPDATE docentes SET ultima_actividad = NOW(), en_linea = 1 WHERE id_docente = ?")
            ->execute([$idUsuario]);
    } else {
        $pdo->prepare("UPDATE alumnos SET ultima_actividad = NOW(), en_linea = 1 WHERE id_alumno = ?")
            ->execute([$idUsuario]);
    }
}

// ==========================================================
// 3. MANEJO DE ACCIONES (SWITCH)
// ==========================================================
switch ($action) {

    // 1️⃣BUSCAR USUARIOS (Docentes y Alumnos)
    case 'buscar_usuarios':
        $query = '%' . ($_GET['query'] ?? '') . '%';
        $stmt = $pdo->prepare("
            SELECT id_docente AS id_usuario, CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) AS nombre, 'docente' AS rol
            FROM docentes
            WHERE nombre LIKE ? OR apellido_paterno LIKE ?
            UNION
            SELECT id_alumno AS id_usuario, CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) AS nombre, 'alumno' AS rol
            FROM alumnos
            WHERE nombre LIKE ? OR apellido_paterno LIKE ?
            LIMIT 20
        ");
        $stmt->execute([$query, $query, $query, $query]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // 2️⃣CREAR O OBTENER CHAT (UNIVERSAL)
    case 'crear_chat':
        $idDestino = intval($_POST['id_usuario'] ?? 0);
        $rolDestino = $_POST['rol'] ?? '';

        if (!$idDestino || !$rolDestino) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        // Lógica para asignar ID_DOCENTE/ID_ALUMNO
        $idDocente = 0;
        $idAlumno = 0;
        if ($tipoUsuario === 'docente' && $rolDestino === 'docente') {
            $idDocente = $idUsuario;
            $idAlumno = $idDestino;
        } elseif ($tipoUsuario === 'docente' && $rolDestino === 'alumno') {
            $idDocente = $idUsuario;
            $idAlumno = $idDestino;
        } elseif ($tipoUsuario === 'alumno' && $rolDestino === 'docente') {
            $idDocente = $idDestino;
            $idAlumno = $idUsuario;
        } elseif ($tipoUsuario === 'alumno' && $rolDestino === 'alumno') {
            if ($idUsuario < $idDestino) {
                $idDocente = $idUsuario;
                $idAlumno = $idDestino;
            } else {
                $idDocente = $idDestino;
                $idAlumno = $idUsuario;
            }
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Tipo de chat no permitido']);
            exit;
        }

        // Buscar o crear chat
        $stmt = $pdo->prepare("
            SELECT id_chat
            FROM chats
            WHERE (id_docente = :id_docente AND id_alumno = :id_alumno)
            OR (id_docente = :id_alumno AND id_alumno = :id_docente)
        ");
        $stmt->execute(['id_docente' => $idDocente, 'id_alumno' => $idAlumno]);
        $chat = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($chat) {
            echo json_encode(['status' => 'ok', 'id_chat' => $chat['id_chat']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO chats (id_docente, id_alumno, fecha_creacion) VALUES (:id_docente, :id_alumno, NOW())");
            $stmt->execute(['id_docente' => $idDocente, 'id_alumno' => $idAlumno]);
            echo json_encode(['status' => 'ok', 'id_chat' => $pdo->lastInsertId()]);
        }
        break;

    // 3️⃣OBTENER MENSAJES DEL CHAT
    case 'mensajes':
        $id_chat = $_GET['id_chat'] ?? 0;
        if (!$id_chat) {
            echo json_encode([]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM mensajesDocente WHERE id_chat = ? ORDER BY fecha_envio ASC");
        $stmt->execute([$id_chat]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // 4️⃣ENVIAR MENSAJE
    case 'enviar':
        $id_chat = intval($_POST['id_chat'] ?? 0);
        $contenido = trim($_POST['contenido'] ?? '');

        if (!$id_chat || $contenido === '') {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        if (!in_array($tipoUsuario, ['docente', 'alumno'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Tipo usuario inválido']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO mensajesDocente (id_chat, remitente, contenido, fecha_envio) VALUES (:id_chat, :remitente, :contenido, NOW())");
        $stmt->execute(['id_chat' => $id_chat, 'remitente' => $tipoUsuario, 'contenido' => htmlspecialchars($contenido, ENT_QUOTES, 'UTF-8')]);
        $id_mensaje = $pdo->lastInsertId();

        // Recuperar el mensaje completo para enviarlo al cliente
        $msg = $pdo->query("SELECT * FROM mensajesDocente WHERE id_mensaje = $id_mensaje")->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'mensaje' => $msg]);
        break;

    // 5️⃣CHATS ACTIVOS (UNIVERSAL)
    case 'chats':
        $idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
        $idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;
        
        if ($tipoUsuario === 'docente') {
            $stmt = $pdo->prepare("
                SELECT c.id_chat,
                    COALESCE(
                        (SELECT CONCAT(a.nombre, ' ', a.apellido_paterno) FROM alumnos a WHERE a.id_alumno = c.id_alumno),
                        (SELECT CONCAT(d.nombre, ' ', d.apellido_paterno) FROM docentes d WHERE d.id_docente = c.id_alumno)
                    ) AS nombre,
                    CASE
                        WHEN EXISTS (SELECT 1 FROM alumnos a WHERE a.id_alumno = c.id_alumno) THEN 'alumno'
                        ELSE 'docente'
                    END AS rol,
                    (SELECT MAX(fecha_envio) FROM mensajesDocente m WHERE m.id_chat = c.id_chat) AS ultimo
                FROM chats c
                WHERE c.id_docente = :id AND EXISTS (SELECT 1 FROM mensajesDocente m WHERE m.id_chat = c.id_chat)
                ORDER BY ultimo DESC
            ");
            $stmt->execute(['id' => $idDocente]);
        } else { // Alumno
            $stmt = $pdo->prepare("
                SELECT c.id_chat,
                    COALESCE(
                        (SELECT CONCAT(d.nombre, ' ', d.apellido_paterno) FROM docentes d WHERE d.id_docente = c.id_docente),
                        (SELECT CONCAT(a.nombre, ' ', a.apellido_paterno) FROM alumnos a WHERE a.id_alumno = c.id_docente)
                    ) AS nombre,
                    CASE
                        WHEN EXISTS (SELECT 1 FROM docentes d WHERE d.id_docente = c.id_docente) THEN 'docente'
                        ELSE 'alumno'
                    END AS rol,
                    (SELECT MAX(fecha_envio) FROM mensajesDocente m WHERE m.id_chat = c.id_chat) AS ultimo
                FROM chats c
                WHERE c.id_alumno = :id AND EXISTS (SELECT 1 FROM mensajesDocente m WHERE m.id_chat = c.id_chat)
                ORDER BY ultimo DESC
            ");
            $stmt->execute(['id' => $idAlumno]);
        }

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;

    // 6️⃣USUARIOS EN LÍNEA
    case 'usuarios_en_linea':
        $usuarios = [];
        if ($tipoUsuario === 'docente') {
            $stmt = $pdo->prepare("
                SELECT id_docente AS id_usuario, CONCAT(nombre, ' ', apellido_paterno) AS nombre, 'docente' AS rol, en_linea
                FROM docentes
                WHERE en_linea = TRUE AND id_docente != ?
            ");
            $stmt->execute([$idUsuario]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id_docente AS id_usuario, CONCAT(nombre, ' ', apellido_paterno) AS nombre, 'docente' AS rol, en_linea
                FROM docentes
                WHERE en_linea = TRUE
            ");
            $stmt->execute();
        }

        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($tipoUsuario === 'alumno') {
            $stmt = $pdo->prepare("
                SELECT id_alumno AS id_usuario, CONCAT(nombre, ' ', apellido_paterno) AS nombre, 'alumno' AS rol, en_linea
                FROM alumnos
                WHERE en_linea = TRUE AND id_alumno != ?
            ");
            $stmt->execute([$idUsuario]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id_alumno AS id_usuario, CONCAT(nombre, ' ', apellido_paterno) AS nombre, 'alumno' AS rol, en_linea
                FROM alumnos
                WHERE en_linea = TRUE
            ");
            $stmt->execute();
        }

        $usuarios = array_merge($usuarios, $stmt->fetchAll(PDO::FETCH_ASSOC));

        echo json_encode($usuarios);
        
        break;

    // 7️⃣MARCAR MENSAJES COMO LEÍDOS
    case 'marcar_leido':
        $id_chat = intval($_POST['id_chat'] ?? 0);
        if (!$id_chat) {
            echo json_encode(['status' => 'error', 'msg' => 'id_chat faltante']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE mensajesDocente SET leido = 1 WHERE id_chat = :id_chat AND remitente <> :rol");
        $stmt->execute(['id_chat' => $id_chat, 'rol' => $tipoUsuario]);
        echo json_encode(['status' => 'ok']);
        break;

    // 8️⃣NOTIFICACIONES (Mensajes no leídos)
    case 'notificaciones':
        $idDocente = $_SESSION['usuario']['id_docente'] ?? 0;
        $idAlumno = $_SESSION['usuario']['id_alumno'] ?? 0;

        if ($tipoUsuario === 'docente') {
            $stmt = $pdo->prepare("
                SELECT c.id_chat,
                    COALESCE((SELECT CONCAT(a.nombre,' ',a.apellido_paterno) FROM alumnos a WHERE a.id_alumno = c.id_alumno), '') AS nombre,
                    (SELECT COUNT(*) FROM mensajesDocente m WHERE m.id_chat = c.id_chat AND m.leido = 0 AND m.remitente <> :rol) AS no_leidos
                FROM chats c
                WHERE c.id_docente = :id
            ");
            $stmt->execute(['id' => $idDocente, 'rol' => 'docente']);
        } else { // Alumno
            $stmt = $pdo->prepare("
                SELECT c.id_chat,
                    COALESCE((SELECT CONCAT(d.nombre,' ',d.apellido_paterno) FROM docentes d WHERE d.id_docente = c.id_docente), '') AS nombre,
                    (SELECT COUNT(*) FROM mensajesDocente m WHERE m.id_chat = c.id_chat AND m.leido = 0 AND m.remitente <> :rol) AS no_leidos
                FROM chats c
                WHERE c.id_alumno = :id
            ");
            $stmt->execute(['id' => $idAlumno, 'rol' => 'alumno']);
        }

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        break;

    // 9️⃣SUBIR ARCHIVO
    case 'subirArchivo':  
        // Validar que exista un archivo
        if (!isset($_FILES['archivo'])) {
            echo json_encode(["status" => "error", "msg" => "No se recibió ningún archivo"]);
            exit;
        }

        // Validar datos necesarios
        $id_chat = $_POST['id_chat'] ?? null;
        $remitente = $_POST['remitente'] ?? null; // "docente" o "alumno"
        if (!$id_chat || !$remitente) {
            echo json_encode(["status" => "error", "msg" => "Datos incompletos (id_chat o remitente faltante)"]);
            exit;
        }

        // Carpeta donde guardar
        $carpeta = __DIR__ . "/../docs/documentos/";
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        // Nombre del archivo
        $archivo = $_FILES['archivo'];
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombreFinal = uniqid("file_") . "." . $extension;
        $rutaArchivo = $carpeta . $nombreFinal;

        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $rutaArchivo)) {
            echo json_encode(["status" => "error", "msg" => "Error al guardar el archivo"]);
            exit;
        }

        // Detectar tipo de archivo según extensión
        $tipo_archivo = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'imagen' : 'documento';

        // Guardar en la BD
        $stmt = $pdo->prepare("
            INSERT INTO mensajesdocente (id_chat, remitente, contenido, archivo, tipo_archivo, fecha_envio)
            VALUES (?, ?, NULL, ?, ?, NOW())
        ");
        $stmt->execute([$id_chat, $remitente, $nombreFinal, $tipo_archivo]);

        echo json_encode([
            "status" => "ok",
            "msg" => "Archivo subido correctamente",
            "archivo" => $nombreFinal,
            "tipo" => $tipo_archivo
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}
