<?php
require_once __DIR__ . '/../../conexion/conexion.php';

// --- Validar tipo de exportación ---
$tipo = strtolower($_GET['tipo'] ?? '');
if (!in_array($tipo, ['pdf', 'excel'])) {
    http_response_code(400);
    exit('Tipo no válido (pdf|excel).');
}

// --- Obtener datos de asignaciones de alumnos ---
$sql = "SELECT 
            a.id_asignacion,
            CONCAT(al.nombre, ' ', al.apellido_paterno, ' ', COALESCE(al.apellido_materno, '')) AS alumno,
            ca.nombre_carrera AS carrera,
            g.nombre_grado AS grado,
            c.nombre_ciclo AS ciclo,
            a.grupo,
            a.fecha_asignacion
        FROM asignaciones_alumnos a
        INNER JOIN alumnos al ON a.id_alumno = al.id_alumno
        INNER JOIN carreras ca ON a.id_carrera = ca.id_carrera
        INNER JOIN grados g ON a.id_grado = g.id_grado
        INNER JOIN ciclos_escolares c ON a.id_ciclo = c.id_ciclo
        ORDER BY a.id_asignacion ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}
$conn->close();


// ---------------------------------------------------------
//  EXPORTAR A EXCEL (CSV simple)
// ---------------------------------------------------------
if ($tipo === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Asignaciones_Alumnos.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Alumno', 'Carrera', 'Grado', 'Ciclo Escolar', 'Grupo', 'Fecha de Asignación']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id_asignacion'],
            $r['alumno'],
            $r['carrera'],
            $r['grado'],
            $r['ciclo'],
            $r['grupo'],
            $r['fecha_asignacion']
        ]);
    }
    fclose($output);
    exit;
}


// ---------------------------------------------------------
//  EXPORTAR A PDF (sin librerías externas)
// ---------------------------------------------------------
if ($tipo === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Asignaciones_Alumnos.pdf"');

    $pdf = "%PDF-1.3\n";
    $pdf .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $pdf .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $contenido = "Lista de Asignaciones de Alumnos\n\n";

    foreach ($rows as $r) {
        $contenido .= "ID: {$r['id_asignacion']}  |  Alumno: {$r['alumno']}  |  Carrera: {$r['carrera']}  |  Grado: {$r['grado']}  |  Ciclo: {$r['ciclo']}  |  Grupo: {$r['grupo']}  |  Fecha: {$r['fecha_asignacion']}\n";
    }

    $contenido = str_replace("(", "\\(", $contenido);
    $contenido = str_replace(")", "\\)", $contenido);

    $stream = "BT /F1 10 Tf 50 750 Td ($contenido) Tj ET";
    $len = strlen($stream);

    $pdf .= "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n";
    $pdf .= "4 0 obj << /Length $len >> stream\n$stream\nendstream endobj\n";
    $pdf .= "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    $pdf .= "trailer << /Root 1 0 R /Size 6 >>\nstartxref\n";
    $pdf .= (strlen($pdf) + 20) . "\n%%EOF";

    echo $pdf;
    exit;
}
?>