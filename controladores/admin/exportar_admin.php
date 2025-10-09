<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Asegúrate de que $conn exista

// --- Validar tipo ---
$tipo = strtolower($_GET['tipo'] ?? '');
if (!in_array($tipo, ['pdf', 'excel'])) {
    http_response_code(400);
    exit('Tipo no válido (pdf|excel).');
}

// --- Obtener datos ---
$sql = "SELECT id_admin, correo, nombre, apellido_paterno, apellido_materno, fecha_registro FROM administradores";
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
    header('Content-Disposition: attachment; filename="Administradores.csv"');

    $output = fopen('php://output', 'w');
    // Encabezados
    fputcsv($output, ['ID', 'Correo', 'Nombre', 'Apellido Paterno', 'Apellido Materno', 'Fecha Registro']);
    // Datos
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id_admin'],
            $r['correo'],
            $r['nombre'],
            $r['apellido_paterno'],
            $r['apellido_materno'],
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
    header('Content-Disposition: attachment; filename="Administradores.pdf"');

    $html = "<h2 style='text-align:center;'>Lista de Administradores</h2>";
    $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; font-family:Arial; font-size:12px;'>";
    $html .= "<tr>
                <th>ID</th>
                <th>Correo</th>
                <th>Nombre</th>
                <th>Apellido Paterno</th>
                <th>Apellido Materno</th>
                <th>Fecha Registro</th>
              </tr>";
    foreach ($rows as $r) {
        $html .= "<tr>
                    <td>{$r['id_admin']}</td>
                    <td>{$r['correo']}</td>
                    <td>{$r['nombre']}</td>
                    <td>{$r['apellido_paterno']}</td>
                    <td>{$r['apellido_materno']}</td>
                    <td>{$r['fecha_registro']}</td>
                  </tr>";
    }
    $html .= "</table>";

    echo $html;
    exit;
}