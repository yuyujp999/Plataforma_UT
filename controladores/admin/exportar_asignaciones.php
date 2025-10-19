<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Debe definir $conn (mysqli)
require_once __DIR__ . '/../../fpdf/fpdf.php';

// =================== CONSULTA ASIGNACIONES DE MATERIAS ===================
// Esquema esperado:
//  - asignar_materias a (id_asignacion, id_materia, id_nombre_grupo_int, id_nombre_materia)
//  - materias m (id_materia, nombre_materia)
//  - cat_nombres_grupo cg (id_nombre_grupo, nombre)
//  - cat_nombres_materias cm (id_nombre_materia, nombre)
$sql = "SELECT
            a.id_asignacion,
            m.nombre_materia AS materia,
            cg.nombre        AS grupo,
            cm.nombre        AS clave
        FROM asignar_materias a
        LEFT JOIN materias m ON a.id_materia = m.id_materia
        LEFT JOIN cat_nombres_grupo cg ON a.id_nombre_grupo_int = cg.id_nombre_grupo
        LEFT JOIN cat_nombres_materias cm ON a.id_nombre_materia = cm.id_nombre_materia
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

// =================== HELPERS ===================
function iso($s)
{
    return utf8_decode((string) $s);
}

// =================== CLASE PDF ===================
class AsignMatPDF extends FPDF
{
    public $title = 'Asignaciones de Materias';
    public $colW = [];  // anchos columnas
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

        // Encabezados
        if (!empty($this->colW) && count($this->colW) >= 4) {
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

        $headers = ['ID', 'Materia', 'Grupo', 'Clave'];
        foreach ($headers as $i => $h) {
            $this->Cell($this->colW[$i], 8, iso($h), 1, 0, 'C', true);
        }
        $this->Ln();
    }

    // ===== Helpers para filas con MultiCell =====
    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            if (!empty($this->colW) && count($this->colW) >= 4) {
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
        $s = str_replace("\r", '', (string) $txt);
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
        // Altura según la celda más alta
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->colW[$i], iso($data[$i])));
        }
        $h = 7 * max(1, $nb);
        $this->CheckPageBreak($h);

        // Estilos fila
        $this->SetFillColor($fill ? 245 : 255, 255, $fill ? 245 : 255);
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 11);

        // Celdas
        for ($i = 0; $i < count($data); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            $w = $this->colW[$i];
            $a = $this->colA[$i] ?? 'L';

            $this->Rect($x, $y, $w, $h);        // borde
            $this->SetXY($x + 1.8, $y + 1.8);   // padding
            $this->MultiCell($w - 3.6, 6, iso($data[$i]), 0, $a, true);
            $this->SetXY($x + $w, $y);          // siguiente celda
        }
        $this->Ln($h);
    }
}

// =================== CREAR PDF (A4 vertical) ===================
$pdf = new AsignMatPDF('P', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AliasNbPages();

// 4 columnas -> distribuir ~186mm útiles (A4 retrato con márgenes)
$widths = [
    18,  // ID
    96,  // Materia
    32,  // Grupo
    40   // Clave
];
$aligns = [
    'C', // ID
    'L', // Materia
    'C', // Grupo
    'C'  // Clave
];

$pdf->setTableStyle($widths, $aligns);
$pdf->AddPage();

// Pintar filas (zebra)
$fill = false;
foreach ($rows as $r) {
    $pdf->Row([
        $r['id_asignacion'],
        $r['materia'] ?? '—',
        $r['grupo'] ?? '—',
        $r['clave'] ?? '—'
    ], $fill);
    $fill = !$fill;
}

// Limpieza del buffer por si algo imprimió
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}

// Descargar
$pdf->Output('D', 'Asignaciones_Materias.pdf');
exit;