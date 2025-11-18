<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Mostrar errores en desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['rol'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesión no válida']);
    exit;
}

$rolUsuario = strtolower($_SESSION['rol']);

$esAdmin = ($rolUsuario === 'admin');
$esSecretaria = in_array($rolUsuario, ['secretaria', 'secretarias', 'secretaría', 'secretarías'], true);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            if (!($esAdmin || $esSecretaria)) {
                throw new Exception('No tienes permiso para crear horarios');
            }

            $id_combo = (int) ($_POST['id_nombre_profesor_materia_grupo'] ?? 0);
            $id_aula = (int) ($_POST['id_aula'] ?? 0);
            $dia = $_POST['dia'] ?? '';
            $bloque = (int) ($_POST['bloque'] ?? 0);
            $hora_inicio = $_POST['hora_inicio'] ?? '';
            $hora_fin = $_POST['hora_fin'] ?? '';

            if (!$id_combo || !$id_aula || !$dia || !$bloque || !$hora_inicio || !$hora_fin) {
                throw new Exception('Datos incompletos');
            }

            // Empalme AULA
            $check = $pdo->prepare("
                SELECT COUNT(*) 
                FROM horarios
                WHERE dia = :dia
                  AND bloque = :bloque
                  AND id_aula = :id_aula
            ");
            $check->execute([
                ':dia' => $dia,
                ':bloque' => $bloque,
                ':id_aula' => $id_aula,
            ]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Ya hay un horario en esa aula, día y bloque');
            }

            // Empalme PROFESOR-MATERIA-GRUPO
            $check2 = $pdo->prepare("
                SELECT COUNT(*) 
                FROM horarios
                WHERE dia = :dia
                  AND bloque = :bloque
                  AND id_nombre_profesor_materia_grupo = :id_combo
            ");
            $check2->execute([
                ':dia' => $dia,
                ':bloque' => $bloque,
                ':id_combo' => $id_combo,
            ]);
            if ($check2->fetchColumn() > 0) {
                throw new Exception('Ese profesor ya tiene clase en ese día y bloque');
            }

            $stmt = $pdo->prepare("
                INSERT INTO horarios 
                    (id_nombre_profesor_materia_grupo, id_aula, dia, bloque, hora_inicio, hora_fin)
                VALUES 
                    (:id_combo, :id_aula, :dia, :bloque, :hora_inicio, :hora_fin)
            ");
            $stmt->execute([
                ':id_combo' => $id_combo,
                ':id_aula' => $id_aula,
                ':dia' => $dia,
                ':bloque' => $bloque,
                ':hora_inicio' => $hora_inicio,
                ':hora_fin' => $hora_fin,
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Horario creado correctamente']);
            break;

        case 'actualizar':
            if (!($esAdmin || $esSecretaria)) {
                throw new Exception('No tienes permiso para editar horarios');
            }

            $id_horario = (int) ($_POST['id_horario'] ?? 0);
            $id_combo = (int) ($_POST['id_nombre_profesor_materia_grupo'] ?? 0);
            $id_aula = (int) ($_POST['id_aula'] ?? 0);
            $dia = $_POST['dia'] ?? '';
            $bloque = (int) ($_POST['bloque'] ?? 0);
            $hora_inicio = $_POST['hora_inicio'] ?? '';
            $hora_fin = $_POST['hora_fin'] ?? '';

            if (!$id_horario || !$id_combo || !$id_aula || !$dia || !$bloque || !$hora_inicio || !$hora_fin) {
                throw new Exception('Datos incompletos');
            }

            // Empalme AULA (excluyendo este horario)
            $check = $pdo->prepare("
                SELECT COUNT(*) 
                FROM horarios
                WHERE dia = :dia
                  AND bloque = :bloque
                  AND id_aula = :id_aula
                  AND id_horario <> :id_horario
            ");
            $check->execute([
                ':dia' => $dia,
                ':bloque' => $bloque,
                ':id_aula' => $id_aula,
                ':id_horario' => $id_horario,
            ]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Ya hay un horario en esa aula, día y bloque');
            }

            // Empalme PROFESOR-MATERIA-GRUPO (excluyendo este horario)
            $check2 = $pdo->prepare("
                SELECT COUNT(*) 
                FROM horarios
                WHERE dia = :dia
                  AND bloque = :bloque
                  AND id_nombre_profesor_materia_grupo = :id_combo
                  AND id_horario <> :id_horario
            ");
            $check2->execute([
                ':dia' => $dia,
                ':bloque' => $bloque,
                ':id_combo' => $id_combo,
                ':id_horario' => $id_horario,
            ]);
            if ($check2->fetchColumn() > 0) {
                throw new Exception('Ese profesor ya tiene clase en ese día y bloque');
            }

            $stmt = $pdo->prepare("
                UPDATE horarios
                SET id_nombre_profesor_materia_grupo = :id_combo,
                    id_aula = :id_aula,
                    dia = :dia,
                    bloque = :bloque,
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin
                WHERE id_horario = :id_horario
            ");
            $stmt->execute([
                ':id_combo' => $id_combo,
                ':id_aula' => $id_aula,
                ':dia' => $dia,
                ':bloque' => $bloque,
                ':hora_inicio' => $hora_inicio,
                ':hora_fin' => $hora_fin,
                ':id_horario' => $id_horario,
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Horario actualizado correctamente']);
            break;

        case 'eliminar':
            if (!$esAdmin) {
                throw new Exception('Solo el administrador puede eliminar horarios');
            }

            $id_horario = (int) ($_POST['id_horario'] ?? 0);
            if (!$id_horario) {
                throw new Exception('ID de horario inválido');
            }

            $stmt = $pdo->prepare("DELETE FROM horarios WHERE id_horario = :id_horario");
            $stmt->execute([':id_horario' => $id_horario]);

            echo json_encode(['ok' => true, 'msg' => 'Horario eliminado correctamente']);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}