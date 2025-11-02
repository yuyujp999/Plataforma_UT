<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Debe definir $conn (mysqli)
require_once __DIR__ . '/../../fpdf/fpdf.php';

/* =============================
   CONSULTA ALUMNOS (con semestre)
   ============================= */
$sql = "SELECT
            a.id_alumno,
            a.matricula,
            a.nombre,
            a.apellido_paterno,
            a.apellido_materno,
            a.curp,
            a.fecha_nacimiento,
            a.sexo,
            a.telefono,
            a.correo_personal,
            a.contacto_emergencia,
            a.parentesco_emergencia,
            a.telefono_emergencia,
            a.fecha_registro,
            ns.nombre AS nombre_semestre
        FROM alumnos a
        LEFT JOIN cat_nombres_semestre ns
          ON ns.id_nombre_semestre = a.id_nombre_semestre
        ORDER BY a.id_alumno ASC";
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
   Clase PDF (igual estilo)
   ============================= */
class AlumnosPDF extends FPDF
{
    public $title = 'Lista de Alumnos';
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
        if (!empty($this->colW) && count($this->colW) >= 12) {
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

        // 12 columnas (igual que secretarias para que quepa bien en A4 apaisado)
        $headers = [
            'ID',
            'Matrícula',
            'Nombre',
            'Apellido Paterno',
            'CURP',
            'Fecha Nac.',
            'Teléfono',
            'Semestre',
            'Correo',
            'Contacto Emerg.',
            'Parentesco',
            'Tel. Emerg.'
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
            if (!empty($this->colW) && count($this->colW) >= 12) {
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
        // Altura según la celda que más líneas ocupe
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
            $this->SetXY($x + 1.2, $y + 1.6);
            $this->MultiCell($w - 2.4, 5.8, iso($data[$i]), 0, $a, true);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }
}

/* =============================
   Crear PDF (A4 apaisado)
   ============================= */
$pdf = new AlumnosPDF('L', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AliasNbPages();

/* 
   12 columnas (como en Secretarias). Ajusta anchos para ~273mm útiles.
   Suma aprox: 10+26+28+28+30+20+22+26+32+28+22+21 = 273
*/
$widths = [10, 26, 28, 28, 30, 20, 22, 26, 32, 28, 22, 21];
$aligns = ['C', 'L', 'L', 'L', 'L', 'C', 'C', 'L', 'L', 'L', 'L', 'C'];
$pdf->setTableStyle($widths, $aligns);

$pdf->AddPage();

/* =============================
   Pintar filas (zebra)
   ============================= */
$fill = false;
foreach ($rows as $r) {
    $pdf->Row([
        $r['id_alumno'],
        $r['matricula'],
        $r['nombre'],
        $r['apellido_paterno'],
        $r['curp'],
        $r['fecha_nacimiento'],
        $r['telefono'],
        $r['nombre_semestre'],
        $r['correo_personal'],
        $r['contacto_emergencia'],
        $r['parentesco_emergencia'],
        $r['telefono_emergencia']
    ], $fill);
    $fill = !$fill;
}

/* =============================
   Limpieza del buffer y descarga
   ============================= */
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}
$pdf->Output('D', 'Alumnos.pdf');
exit;