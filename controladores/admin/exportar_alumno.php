<?php
require_once __DIR__ . '/../../conexion/conexion.php';

// --- Validar tipo de exportación ---
$tipo = strtolower($_GET['tipo'] ?? '');
if (!in_array($tipo, ['pdf', 'excel'])) {
    http_response_code(400);
    exit('Tipo no válido (pdf|excel).');
}

// --- Obtener datos de alumnos ---
$sql = "SELECT 
            id_alumno, 
            matricula, 
            CONCAT(nombre, ' ', apellido_paterno, ' ', COALESCE(apellido_materno, '')) AS nombre_completo,
            curp,
            correo_personal,
            carrera,
            semestre,
            grupo,
            telefono
        FROM alumnos";
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
    header('Content-Disposition: attachment; filename="Alumnos.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Matrícula', 'Nombre completo', 'CURP', 'Correo', 'Carrera', 'Semestre', 'Grupo', 'Teléfono']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id_alumno'],
            $r['matricula'],
            $r['nombre_completo'],
            $r['curp'],
            $r['correo_personal'],
            $r['carrera'],
            $r['semestre'],
            $r['grupo'],
            $r['telefono']
        ]);
    }
    fclose($output);
    exit;
}


// ---------------------------------------------------------
//  EXPORTAR A PDF (sin librerías externas)
// ---------------------------------------------------------
if ($tipo === 'pdf') {
    // Usamos cabeceras para forzar descarga como PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Alumnos.pdf"');

    // Generamos contenido como texto simple (PDF básico)
    $pdf = "%PDF-1.3\n";
    $pdf .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $pdf .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $contenido = "Lista de Alumnos\n\n";

    foreach ($rows as $r) {
        $contenido .= "ID: {$r['id_alumno']}  |  Matrícula: {$r['matricula']}  |  {$r['nombre_completo']}  |  {$r['carrera']}\n";
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