<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Asegúrate de que $conn exista

// --- Validar tipo ---
$tipo = strtolower($_GET['tipo'] ?? '');
if (!in_array($tipo, ['pdf', 'excel'])) {
    http_response_code(400);
    exit('Tipo no válido (pdf|excel).');
}

// --- Obtener datos ---
$sql = "SELECT m.id_materia, m.nombre_materia, m.clave, m.horas_semana, g.nombre_grado 
        FROM materias m
        LEFT JOIN grados g ON m.id_grado = g.id_grado";
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
    header('Content-Disposition: attachment; filename="Materias.csv"');

    $output = fopen('php://output', 'w');
    // Encabezados
    fputcsv($output, ['ID', 'Nombre Materia', 'Clave', 'Horas/Semana', 'Grado']);
    // Datos
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id_materia'],
            $r['nombre_materia'],
            $r['clave'],
            $r['horas_semana'],
            $r['nombre_grado'] ?? ''
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
    header('Content-Disposition: attachment; filename="Materias.pdf"');

    $html = "<h2 style='text-align:center;'>Lista de Materias</h2>";
    $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; font-family:Arial; font-size:12px;'>";
    $html .= "<tr>
                <th>ID</th>
                <th>Nombre Materia</th>
                <th>Clave</th>
                <th>Horas/Semana</th>
                <th>Grado</th>
              </tr>";
    foreach ($rows as $r) {
        $html .= "<tr>
                    <td>{$r['id_materia']}</td>
                    <td>{$r['nombre_materia']}</td>
                    <td>{$r['clave']}</td>
                    <td>{$r['horas_semana']}</td>
                    <td>" . ($r['nombre_grado'] ?? '') . "</td>
                  </tr>";
    }
    $html .= "</table>";

    echo $html;
    exit;
}