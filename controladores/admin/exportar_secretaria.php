<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Debe definir $conn (mysqli)
require_once __DIR__ . '/../../fpdf/fpdf.php';

// --- Consulta de secretarias (incluye campos de emergencia y fecha_ingreso) ---
$sql = "SELECT
            id_secretaria,
            correo_institucional,
            nombre,
            apellido_paterno,
            apellido_materno,
            curp,
            rfc,
            departamento,
            fecha_ingreso,
            contacto_emergencia,
            parentesco_emergencia,
            telefono_emergencia
        FROM secretarias
        ORDER BY id_secretaria ASC";
$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}
$conn->close();

// Helper UTF-8 -> ISO para FPDF (evita problemas de acentos)
function iso($s)
{
    return utf8_decode((string) $s);
}

class SecretariasPDF extends FPDF
{
    public $title = 'Lista de Secretarias';
    public $colW = [];  // anchos de columnas
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

        // Encabezados de la tabla (si ya hay colW)
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
        $this->SetFont('Arial', 'B', 10); // un poco más chico porque son 12 columnas
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 60, 0);
        $this->SetFillColor(200, 255, 200);

        $headers = [
            'ID',
            'Correo Institucional',
            'Nombre',
            'Apellido Paterno',
            'Apellido Materno',
            'CURP',
            'RFC',
            'Departamento',
            'Fecha Ingreso',
            'Contacto Emergencia',
            'Parentesco',
            'Tel. Emerg.'
        ];

        foreach ($headers as $i => $h) {
            $this->Cell($this->colW[$i], 8, iso($h), 1, 0, 'C', true);
        }
        $this->Ln();
    }

    // === Helpers para filas con MultiCell (auto-ajuste y salto de página) ===
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
        // Altura según el mayor número de líneas
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->colW[$i], iso($data[$i])));
        }
        $h = 5.8 * max(1, $nb); // un pelín más compacto por columnas
        $this->CheckPageBreak($h);

        // Estilos por fila (zebra)
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

            $this->Rect($x, $y, $w, $h);         // borde
            $this->SetXY($x + 1.2, $y + 1.6);    // padding
            $this->MultiCell($w - 2.4, 5.8, iso($data[$i]), 0, $a, true);
            $this->SetXY($x + $w, $y);           // mover a la siguiente celda
        }
        $this->Ln($h);
    }
}

// === Crear PDF (A4 apaisado) ===
$pdf = new SecretariasPDF('L', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AliasNbPages();

/* 1) Define anchos/aligns ANTES de AddPage()
   Suma total EXACTA ~273mm útiles en A4 landscape */
$widths = [10, 46, 22, 22, 22, 30, 18, 20, 20, 26, 22, 15];
$aligns = ['C', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'C', 'L', 'L', 'C'];
$pdf->setTableStyle($widths, $aligns);

/* 2) Agregar la página */
$pdf->AddPage();

/* 3) Pintar filas */
$fill = false;
foreach ($rows as $r) {
    $pdf->Row([
        $r['id_secretaria'],
        $r['correo_institucional'],
        $r['nombre'],
        $r['apellido_paterno'],
        $r['apellido_materno'],
        $r['curp'],
        $r['rfc'],
        $r['departamento'],
        $r['fecha_ingreso'],
        $r['contacto_emergencia'],
        $r['parentesco_emergencia'],
        $r['telefono_emergencia']
    ], $fill);
    $fill = !$fill;
}

/* 4) Limpieza del buffer (por si algo imprimió antes) */
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}

/* 5) Descargar */
$pdf->Output('D', 'Secretarias.pdf');
exit;