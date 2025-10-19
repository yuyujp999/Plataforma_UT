<?php
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../fpdf/fpdf.php';

// --- Obtener datos de materias ---
$sql = "SELECT id_materia, nombre_materia FROM materias";
$result = $conn->query($sql);
if (!$result)
    die("Error en la consulta: " . $conn->error);

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}
$conn->close();

// --- EXPORTAR A PDF ---
$pdf = new FPDF();
$pdf->AddPage();

// --- Fuente y título ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(0, 100, 0); // verde oscuro para título
$pdf->Cell(0, 10, 'Lista de Materias', 0, 1, 'C');
$pdf->Ln(3);

// --- Encabezados de tabla ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(200, 255, 200); // verde clarito
$pdf->SetTextColor(0, 60, 0);
$pdf->SetDrawColor(0, 100, 0);
$headers = ['ID', 'Nombre Materia'];
$widths = [20, 100]; // ajusta ancho según tu diseño
foreach ($headers as $i => $h) {
    $pdf->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
}
$pdf->Ln();

// --- Filas de datos ---
$pdf->SetFont('Arial', '', 12);
$fill = false;
foreach ($rows as $r) {
    $pdf->SetFillColor($fill ? 230 : 255, 255, 230);

    $pdf->Cell($widths[0], 8, $r['id_materia'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[1], 8, $r['nombre_materia'], 1, 0, 'L', $fill);
    $pdf->Ln();

    $fill = !$fill;
}

// --- Descargar PDF ---
$pdf->Output('D', 'Materias.pdf');
exit;