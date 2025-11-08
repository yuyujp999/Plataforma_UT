<?php
class AjustesController
{
    public static function cambiarPassword($idAlumno, $nueva, $confirmar)
    {
        include __DIR__ . '/../../conexion/conexion.php';

        if (trim($nueva) === '' || trim($confirmar) === '') {
            return "⚠️ Todos los campos son obligatorios.";
        }

        if ($nueva !== $confirmar) {
            return "❌ Las contraseñas no coinciden.";
        }

        if (strlen($nueva) < 6) {
            return "🔒 La contraseña debe tener al menos 6 caracteres.";
        }

        $passwordHash = password_hash($nueva, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE alumnos SET password = ? WHERE id_alumno = ?");
        $stmt->bind_param("si", $passwordHash, $idAlumno);
        $stmt->execute();

        return $stmt->affected_rows > 0
            ? "✅ Contraseña actualizada correctamente."
            : "⚠️ No se pudo actualizar la contraseña.";
    }
}
