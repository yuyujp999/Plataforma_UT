<?php
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../fpdf/fpdf.php';

// --- Consulta de administradores ---
$sql = "SELECT id_admin, correo, nombre, apellido_paterno, apellido_materno, fecha_registro
        FROM administradores
        ORDER BY id_admin ASC";
$result = $conn->query($sql);
if (!$result)
    die("Error en la consulta: " . $conn->error);

$rows = [];
while ($r = $result->fetch_assoc())
    $rows[] = $r;
$conn->close();

// Helper UTF-8 -> ISO para FPDF
function iso($s)
{
    return utf8_decode((string) $s);
}

class AdminPDF extends FPDF
{
    public $title = 'Lista de Administradores';
    public $colW = [];  // anchos de columnas
    public $colA = [];  // alineaciones

    function Header()
    {
        // Título
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 100, 0);
        $this->Cell(0, 10, iso($this->title), 0, 1, 'C');

        // Fecha
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 6, iso('Generado: ' . date('Y-m-d H:i')), 0, 1, 'C');
        $this->Ln(2);

        // Solo imprime encabezado de tabla si ya tenemos anchos definidos
        if (!empty($this->colW) && count($this->colW) >= 6) {
            $this->printHeaderRow();
        }
    }

    function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 10, iso('Página ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }

    function setTableStyle($widths, $aligns)
    {
        $this->colW = $widths;
        $this->colA = $aligns;
    }

    function printHeaderRow()
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 60, 0);
        $this->SetFillColor(200, 255, 200);
        $headers = ['ID', 'Correo', 'Nombre', 'Apellido Paterno', 'Apellido Materno', 'Fecha Registro'];
        foreach ($headers as $i => $h) {
            $this->Cell($this->colW[$i], 9, iso($h), 1, 0, 'C', true);
        }
        $this->Ln();
    }

    // --- Helpers para filas con MultiCell ---
    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            // Reimprimir encabezado tras salto (si los anchos existen)
            if (!empty($this->colW) && count($this->colW) >= 6) {
                $this->printHeaderRow();
            }
        }
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x; // $this->w (float) = ancho página
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    function Row($data, $fill = false)
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->colW[$i], iso($data[$i])));
        }
        $h = 6 * max(1, $nb);
        $this->CheckPageBreak($h);

        // zebra
        $this->SetFillColor($fill ? 235 : 255, 255, $fill ? 235 : 255);
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 10);

        for ($i = 0; $i < count($data); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            $w = $this->colW[$i];
            $a = $this->colA[$i] ?? 'L';

            $this->Rect($x, $y, $w, $h);          // borde
            $this->SetXY($x + 1.2, $y + 2);           // padding
            $this->MultiCell($w - 2.4, 6, iso($data[$i]), 0, $a, true);
            $this->SetXY($x + $w, $y);              // a la derecha
        }
        $this->Ln($h);
    }
}

// === Crear PDF (Landscape) ===
$pdf = new AdminPDF('L', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AliasNbPages();

/* 1) Define anchos/aligns ANTES de AddPage() */
$widths = [14, 90, 40, 50, 50, 29];  // total ~273mm útiles en A4 landscape
$aligns = ['C', 'L', 'L', 'L', 'L', 'C'];
$pdf->setTableStyle($widths, $aligns);

/* 2) Ahora sí se puede agregar la página */
$pdf->AddPage();

/* 3) Pintar filas */
$fill = false;
foreach ($rows as $r) {
    $pdf->Row([
        $r['id_admin'],
        $r['correo'],
        $r['nombre'],
        $r['apellido_paterno'],
        $r['apellido_materno'],
        date('Y-m-d H:i', strtotime($r['fecha_registro']))
    ], $fill);
    $fill = !$fill;
}

/* 4) Limpieza del buffer (defensivo; por si algo imprimió) */
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}

/* 5) Descargar */
$pdf->Output('D', 'Administradores.pdf');
exit;