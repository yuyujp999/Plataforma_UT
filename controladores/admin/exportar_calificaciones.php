<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Asegúrate de que $conn exista

// --- Validar tipo ---
$tipo = strtolower($_GET['tipo'] ?? '');
if (!in_array($tipo, ['pdf', 'excel'])) {
    http_response_code(400);
    exit('Tipo no válido (pdf|excel).');
}

// --- Obtener datos ---
$sql = "SELECT c.id_calificacion, c.calificacion_final, c.observaciones, c.fecha_registro,
               a.nombre AS nombre_alumno, a.apellido_paterno AS apellido_alumno, a.apellido_materno AS apellido_alumno2,
               d.nombre AS nombre_docente, d.apellido_paterno AS apellido_docente,
               m.nombre_materia AS materia
        FROM calificaciones c
        INNER JOIN alumnos a ON c.id_alumno = a.id_alumno
        INNER JOIN asignaciones_docentes ad ON c.id_asignacion_docente = ad.id_asignacion_docente
        INNER JOIN docentes d ON ad.id_docente = d.id_docente
        INNER JOIN materias m ON ad.id_materia = m.id_materia
        ORDER BY c.id_calificacion ASC";

$result = $conn->query($sql);
if (!$result) {
    die("Error al consultar: " . $conn->error);
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}
$conn->close();

// ---------------------------------------------------------
//  EXCEL: generamos un CSV
// ---------------------------------------------------------
if ($tipo === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Calificaciones.csv"');

    $output = fopen('php://output', 'w');
    // Encabezados
    fputcsv($output, ['ID', 'Alumno', 'Docente', 'Materia', 'Calificación Final', 'Observaciones', 'Fecha Registro']);
    // Datos
    foreach ($rows as $r) {
        $alumno = $r['nombre_alumno'] . ' ' . $r['apellido_alumno'] . ' ' . $r['apellido_alumno2'];
        $docente = $r['nombre_docente'] . ' ' . $r['apellido_docente'];
        fputcsv($output, [
            $r['id_calificacion'],
            $alumno,
            $docente,
            $r['materia'],
            $r['calificacion_final'],
            $r['observaciones'],
            $r['fecha_registro']
        ]);
    }
    fclose($output);
    exit;
}

// ---------------------------------------------------------
//  PDF: HTML simple (para navegadores modernos)
// ---------------------------------------------------------
if ($tipo === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Calificaciones.pdf"');

    $html = "<h2 style='text-align:center;'>Lista de Calificaciones</h2>";
    $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; font-family:Arial; font-size:12px;'>";
    $html .= "<tr>
                <th>ID</th>
                <th>Alumno</th>
                <th>Docente</th>
                <th>Materia</th>
                <th>Calificación Final</th>
                <th>Observaciones</th>
                <th>Fecha Registro</th>
              </tr>";
    foreach ($rows as $r) {
        $alumno = $r['nombre_alumno'] . ' ' . $r['apellido_alumno'] . ' ' . $r['apellido_alumno2'];
        $docente = $r['nombre_docente'] . ' ' . $r['apellido_docente'];
        $html .= "<tr>
                    <td>{$r['id_calificacion']}</td>
                    <td>{$alumno}</td>
                    <td>{$docente}</td>
                    <td>{$r['materia']}</td>
                    <td>{$r['calificacion_final']}</td>
                    <td>{$r['observaciones']}</td>
                    <td>{$r['fecha_registro']}</td>
                  </tr>";
    }
    $html .= "</table>";

    echo $html;
    exit;
}