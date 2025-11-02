<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Debe definir $conn (mysqli)
require_once __DIR__ . '/../../fpdf/fpdf.php';

/* =============================
   CONSULTA CARRERAS
   ============================= */
$sql = "SELECT
            id_carrera,
            nombre_carrera,
            COALESCE(descripcion, '')      AS descripcion,
            duracion_anios,
            fecha_creacion
        FROM carreras
        ORDER BY nombre_carrera ASC";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}
$conn->close();

/* =============================
   Helper UTF-8 -> ISO-8859-1
   ============================= */
function iso($s)
{
    return utf8_decode((string) $s);
}

/* =============================
   Clase PDF (estilo similar)
   ============================= */
class CarrerasPDF extends FPDF
{
    public $title = 'Listado de Carreras';
    public $colW = [];  // anchos columnas
    public $colA = [];  // alineaciones

    function Header()
    {
        // Título
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 100, 0);
        $this->Cell(0, 10, iso($this->title), 0, 1, 'C');

        // Fecha de generación
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 6, iso('Generado: ' . date('Y-m-d H:i')), 0, 1, 'C');
        $this->Ln(2);

        // Encabezados si ya hay configuración
        if (!empty($this->colW) && count($this->colW) >= 5) {
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
        $this->SetFont('Arial', 'B', 10);
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 60, 0);
        $this->SetFillColor(200, 255, 200);

        // 5 columnas
        $headers = [
            'ID',
            'Nombre de la Carrera',
            'Descripción',
            'Duración (años)',
            'Fecha de Creación'
        ];

        foreach ($headers as $i => $h) {
            $this->Cell($this->colW[$i], 8, iso($h), 1, 0, 'C', true);
        }
        $this->Ln();
    }

    // === Helpers para MultiCell con saltos y altura dinámica ===
    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            if (!empty($this->colW) && count($this->colW) >= 5) {
                $this->printHeaderRow();
            }
        }
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
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
        // Altura según la celda con más líneas
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->colW[$i], iso($data[$i])));
        }
        $h = 5.8 * max(1, $nb);
        $this->CheckPageBreak($h);

        // Zebra
        $this->SetFillColor($fill ? 235 : 255, 255, $fill ? 235 : 255);
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9.5);

        // Celdas
        for ($i = 0; $i < count($data); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            $w = $this->colW[$i];
            $a = $this->colA[$i] ?? 'L';

            $this->Rect($x, $y, $w, $h);
            $this->SetXY($x + 1.6, $y + 1.6);
            $this->MultiCell($w - 3.2, 5.8, iso($data[$i]), 0, $a, true);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }
}

/* =============================
   Crear PDF (A4 apaisado)
   ============================= */
$pdf = new CarrerasPDF('L', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AliasNbPages();

/*
   5 columnas. Usamos ~273 mm útiles en horizontal:
   12 + 80 + 130 + 20 + 31 = 273
*/
$widths = [12, 80, 130, 20, 31];
$aligns = ['C', 'L', 'L', 'C', 'C'];
$pdf->setTableStyle($widths, $aligns);

$pdf->AddPage();

/* =============================
   Pintar filas (zebra)
   ============================= */
$fill = false;
foreach ($rows as $r) {
    $pdf->Row([
        $r['id_carrera'],
        $r['nombre_carrera'],
        $r['descripcion'],
        (string) $r['duracion_anios'],
        $r['fecha_creacion'],
    ], $fill);
    $fill = !$fill;
}

/* =============================
   Limpieza del buffer y descarga
   ============================= */
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}
$pdf->Output('D', 'Carreras.pdf');
exit;