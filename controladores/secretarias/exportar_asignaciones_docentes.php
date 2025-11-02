<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Debe definir $conn (mysqli)
require_once __DIR__ . '/../../fpdf/fpdf.php';

/*
  Esquema:
  - asignaciones_docentes a (id_asignacion_docente, id_docente, id_nombre_materia, id_nombre_profesor_materia_grupo)
  - docentes d (id_docente, nombre, apellido_paterno, apellido_materno)
  - cat_nombres_materias cm (id_nombre_materia, nombre)
  - cat_nombre_profesor_materia_grupo cpmg (id_nombre_profesor_materia_grupo, nombre)
*/

$sql = "SELECT
          a.id_asignacion_docente      AS id,
          CONCAT(d.nombre,' ',d.apellido_paterno,' ',COALESCE(d.apellido_materno,'')) AS docente,
          cm.nombre                    AS clave_materia,
          cpmg.nombre                  AS profesor_materia_grupo
        FROM asignaciones_docentes a
        INNER JOIN docentes d ON d.id_docente = a.id_docente
        LEFT JOIN cat_nombres_materias cm ON cm.id_nombre_materia = a.id_nombre_materia
        LEFT JOIN cat_nombre_profesor_materia_grupo cpmg
               ON cpmg.id_nombre_profesor_materia_grupo = a.id_nombre_profesor_materia_grupo
        ORDER BY a.id_asignacion_docente ASC";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}
$conn->close();

// ==== Helper UTF-8 -> ISO (acentos) ====
function iso($s)
{
    return utf8_decode((string) $s);
}

// =================== CLASE PDF ===================
class AsigDocPDF extends FPDF
{
    public $title = 'Asignaciones de Docentes';
    public $colW = [];
    public $colA = [];

    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 100, 0);
        $this->Cell(0, 10, iso($this->title), 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 6, iso('Generado: ' . date('Y-m-d H:i')), 0, 1, 'C');
        $this->Ln(2);

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

        $headers = ['ID', 'Docente', 'Clave Materia', 'Profesor - Materia - Grupo'];
        foreach ($headers as $i => $h) {
            $this->Cell($this->colW[$i], 8, iso($h), 1, 0, 'C', true);
        }
        $this->Ln();
    }

    // ---- Helpers para filas con MultiCell ----
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
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->colW[$i], iso($data[$i])));
        }
        $h = 7 * max(1, $nb);
        $this->CheckPageBreak($h);

        $this->SetFillColor($fill ? 245 : 255, 255, $fill ? 245 : 255);
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 11);

        for ($i = 0; $i < count($data); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            $w = $this->colW[$i];
            $a = $this->colA[$i] ?? 'L';

            $this->Rect($x, $y, $w, $h);
            $this->SetXY($x + 1.8, $y + 1.8);
            $this->MultiCell($w - 3.6, 6, iso($data[$i]), 0, $a, true);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }
}

// ===== Crear PDF (A4 retrato) =====
$pdf = new AsigDocPDF('P', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AliasNbPages();

/* 4 columnas ~186mm útiles */
$widths = [16, 70, 40, 60];
$aligns = ['C', 'L', 'C', 'L'];
$pdf->setTableStyle($widths, $aligns);
$pdf->AddPage();

// Filas
$fill = false;
foreach ($rows as $r) {
    $pdf->Row([
        $r['id'] ?? '—',
        $r['docente'] ?? '—',
        $r['clave_materia'] ?? '—',
        $r['profesor_materia_grupo'] ?? '—'
    ], $fill);
    $fill = !$fill;
}

// Limpieza de buffer (si algo imprimió antes)
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}

// Descargar
$pdf->Output('D', 'Asignaciones_Docentes.pdf');
exit;