<?php
class EvaluacionesController
{
    public static function obtenerEvaluaciones($idDocente)
    {
        // 🔹 Temporalmente datos de ejemplo (conectar a BD después)
        return [
            [
                'id_evaluacion' => 1,
                'titulo' => 'Examen Final - Programación Web',
                'tipo' => 'Examen',
                'materia' => 'Programación Web',
                'fecha' => '2025-11-05',
                'archivo' => 'examen_web.pdf'
            ],
            [
                'id_evaluacion' => 2,
                'titulo' => 'Proyecto Final - Base de Datos',
                'tipo' => 'Proyecto Final',
                'materia' => 'Base de Datos',
                'fecha' => '2025-10-25',
                'archivo' => 'proyecto_bd.zip'
            ]
        ];
    }

    // ⚙️ Método para manejar subida (cuando implementes backend)
    public static function subirEvaluacion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'subir') {
            $titulo = $_POST['titulo'] ?? '';
            $tipo = $_POST['tipo'] ?? '';
            $archivo = $_FILES['archivo']['name'] ?? '';

            if ($archivo) {
                $rutaDestino = __DIR__ . '/../../uploads/evaluaciones/';
                if (!file_exists($rutaDestino)) mkdir($rutaDestino, 0777, true);

                $nombreFinal = time() . '_' . basename($archivo);
                move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino . $nombreFinal);

                // Aquí insertarías en la BD
                echo "<script>alert('✅ Evaluación subida correctamente'); window.location.href='/Plataforma_UT/vistas/Docentes/evaluaciones.php';</script>";
            } else {
                echo "<script>alert('⚠️ Error al subir el archivo');</script>";
            }
        }
    }
}

if (isset($_POST['accion']) && $_POST['accion'] === 'subir') {
    EvaluacionesController::subirEvaluacion();
}
