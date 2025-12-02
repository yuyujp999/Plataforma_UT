<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: /Plataforma_UT/inicio.php');
    exit;
}

include_once __DIR__ . "/../../conexion/conexion.php";

// ✅ Marcar al usuario como "en línea"
$rol      = $_SESSION['rol'];
$usuario  = $_SESSION['usuario'] ?? [];

if ($rol === 'docente' && isset($usuario['id_docente'])) {
    $id_docente = $usuario['id_docente'];
    $stmt = $pdo->prepare("UPDATE docentes SET en_linea = TRUE WHERE id_docente = ?");
    $stmt->execute([$id_docente]);
} elseif ($rol === 'alumno' && isset($usuario['id_alumno'])) {
    $id_alumno = $usuario['id_alumno'];
    $stmt = $pdo->prepare("UPDATE alumnos SET en_linea = TRUE WHERE id_alumno = ?");
    $stmt->execute([$id_alumno]);
}

$nombre        = $usuario['nombre'] ?? 'Usuario';
$apellido      = $usuario['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;

$idUsuario = null;
if ($rol === 'docente' && isset($usuario['id_docente'])) {
    $idUsuario = $usuario['id_docente'];
} elseif ($rol === 'alumno' && isset($usuario['id_alumno'])) {
    $idUsuario = $usuario['id_alumno'];
}

// Datos específicos de alumno (se mantienen tal cual los tenías)
$alumno        = $_SESSION['usuario'] ?? [];
$idAlumno      = $alumno['id_alumno'] ?? null;
$nombre        = $alumno['nombre'] ?? 'Alumno';
$apellido      = $alumno['apellido_paterno'] ?? '';
$usuarioNombre = $nombre . ' ' . $apellido;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Chat Alumnos | UT Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <link rel="stylesheet" href="../../css/styleD.css">
    <link rel="icon" href="/Plataforma_UT/img/ut_logo.png" />

    <link rel="stylesheet" href="../../css/docentes/chat_docentes.css">
</head>
<body>
<div class="container">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="overlay" id="overlay"></div>
        <div class="logo">
            <h1>UT<span>Panel</span></h1>
        </div>
        <div class="nav-menu" id="menu">
            <div class="menu-heading">Menú</div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="materia-navbar">
            <div class="materia-info">
                <h2><i class="fa-solid fa-comments"></i> Chat unificado</h2>
                <p>Buscar...</p>
            </div>
            <div class="user-info">
                <i class="fa-solid fa-user-tie"></i>
                <span id="meName"><?= htmlspecialchars($usuarioNombre) ?></span>
                <span id="badgeChat" class="badgeMenu" style="display:inline-block;"></span>
            </div>
        </div>

        <div class="chat-dashboard">

            <!-- SIDEBAR CHAT -->
            <div class="chat-sidebar">
                <h3><i class="fa-solid fa-magnifying-glass"></i> Buscar usuarios</h3>

                <div class="search-box">
                    <input id="searchInput" type="text" placeholder="Buscar alumno o docente...">
                    <button id="searchBtn">Buscar</button>
                </div>

                <div id="resultadosBusqueda"></div>

                <h4 style="margin:8px 0 6px 0">En línea</h4>
                <div id="usuariosEnLinea">
                    <p class="placeholder">Cargando usuarios...</p>
                </div>

                <h4 style="margin:8px 0 6px 0">Chats activos</h4>
                <div id="chatsActivos">
                    <p class="placeholder">Cargando chats...</p>
                </div>
            </div>

            <!-- MAIN CHAT -->
            <div class="chat-main">
                <div id="chatHeader" class="chat-header">
                    <h3>Selecciona una conversación</h3>
                </div>

                <div id="chatBody" class="chat-body">
                    <p class="placeholder">Selecciona un chat para comenzar.</p>
                </div>

                <div class="chat-input">

                    <label for="inputArchivo" class="file-btn">
                        <i class="fa-solid fa-paperclip"></i>
                    </label>

                    <input
                        type="file"
                        id="inputArchivo"
                        accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        hidden
                    >

                    <button id="emojiBtn" class="emoji-btn">
                        <i class="fa-regular fa-face-smile"></i>
                    </button>

                    <div id="previewFile"
                         class="preview-file"
                         style="display:none; padding:10px; background:#eee; border-radius:8px; margin-bottom:10px;">
                    </div>

                    <input id="msgInput" type="text" placeholder="Escribe un mensaje..." disabled>

                    <button id="sendBtn" disabled>
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>

                    <!-- Panel de emojis -->
                    <div id="emojiPanel" class="emoji-panel">
                        <span>😀</span> <span>😁</span> <span>😂</span> <span>🤣</span>
                        <span>😊</span> <span>😍</span> <span>😡</span> <span>😱</span>
                        <span>😭</span> <span>😴</span> <span>💀</span> <span>🎉</span>
                        <span>❤️</span> <span>👍</span> <span>👌</span> <span>🙌</span>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // igual que en el de docentes: para que chat.js tenga el ID del usuario
    window.ID_USUARIO   = <?= json_encode($idUsuario) ?>;
    window.rolUsuarioPHP = "<?= htmlspecialchars($_SESSION['rol']); ?>";
</script>

<script src="/Plataforma_UT/js/docentes/chat.js"></script>
<script src="/Plataforma_UT/js/Dashboard_Inicio.js"></script>
</body>
</html>
