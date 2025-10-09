<?php
require_once __DIR__ . '/../../conexion/conexion.php';

// --- Validar tipo (pdf | excel) ---
$tipo = strtolower($_GET['tipo'] ?? '');
if (!in_array($tipo, ['pdf', 'excel'])) {
    http_response_code(400);
    exit('Tipo no válido (pdf|excel).');
}

// --- Obtener datos de secretarias ---
$sql = "SELECT 
            id_secretaria, 
            nombre, 
            apellido_paterno, 
            apellido_materno, 
            curp, 
            rfc, 
            correo_institucional, 
            departamento 
        FROM secretarias";
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
//  EXPORTAR A EXCEL (CSV)
// ---------------------------------------------------------
if ($tipo === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Secretarias.csv"');

    $output = fopen('php://output', 'w');
    // Encabezados del archivo CSV
    fputcsv($output, [
        'ID',
        'Nombre',
        'Apellido Paterno',
        'Apellido Materno',
        'CURP',
        'RFC',
        'Correo Institucional',
        'Departamento'
    ]);

    // Datos
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id_secretaria'],
            $r['nombre'],
            $r['apellido_paterno'],
            $r['apellido_materno'],
            $r['curp'],
            $r['rfc'],
            $r['correo_institucional'],
            $r['departamento']
        ]);
    }

    fclose($output);
    exit;
}

// ---------------------------------------------------------
//  EXPORTAR A PDF
// ---------------------------------------------------------
if ($tipo === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Secretarias.pdf"');

    // Construcción del HTML para el PDF
    $html = "
    <h2 style='text-align:center; font-family:Arial;'>Lista de Secretarias</h2>
    <table border='1' cellspacing='0' cellpadding='6' style='width:100%; border-collapse:collapse; font-family:Arial; font-size:12px;'>
        <thead style='background-color:#f2f2f2;'>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido Paterno</th>
                <th>Apellido Materno</th>
                <th>CURP</th>
                <th>RFC</th>
                <th>Correo Institucional</th>
                <th>Departamento</th>
            </tr>
        </thead>
        <tbody>
    ";

    foreach ($rows as $r) {
        $html .= "
            <tr>
                <td>{$r['id_secretaria']}</td>
                <td>{$r['nombre']}</td>
                <td>{$r['apellido_paterno']}</td>
                <td>{$r['apellido_materno']}</td>
                <td>{$r['curp']}</td>
                <td>{$r['rfc']}</td>
                <td>{$r['correo_institucional']}</td>
                <td>{$r['departamento']}</td>
            </tr>
        ";
    }

    $html .= "</tbody></table>";

    echo $html;
    exit;
}
?>