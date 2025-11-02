<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Debe definir $conn (mysqli)
require_once __DIR__ . '/../../fpdf/fpdf.php';

// =================== CONSULTA DOCENTES (TODOS LOS CAMPOS VIGENTES, SIN PASSWORD) ===================
$sql = "SELECT
            id_docente,
            matricula,
            nombre,
            apellido_paterno,
            apellido_materno,
            curp,
            rfc,
            fecha_nacimiento,
            sexo,
            telefono,
            direccion,
            correo_personal,
            nivel_estudios,
            area_especialidad,
            universidad_egreso,
            cedula_profesional,
            idiomas,
            puesto,
            tipo_contrato,
            fecha_ingreso,
            contacto_emergencia,
            parentesco_emergencia,
            telefono_emergencia,
            fecha_registro
        FROM docentes
        ORDER BY id_docente ASC";
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
class DocentesPDF extends FPDF
{
    public $title = 'Lista de Docentes';
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

        // Encabezados
        if (!empty($this->colW) && count($this->colW) >= 24) {
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
        $this->SetFont('Arial', 'B', 9); // chico para 24 columnas
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 60, 0);
        $this->SetFillColor(200, 255, 200);

        $headers = [
            'ID',
            'Matrícula',
            'Nombre',
            'Ap. Paterno',
            'Ap. Materno',
            'CURP',
            'RFC',
            'F. Nac.',
            'Sexo',
            'Teléfono',
            'Dirección',
            'Correo',
            'Nivel',
            'Área',
            'Universidad',
            'Cédula',
            'Idiomas',
            'Puesto',
            'Tipo Contrato',
            'F. Ingreso',
            'Contacto Emerg.',
            'Parentesco',
            'Tel. Emerg.',
            'F. Registro'
        ];

        foreach ($headers as $i => $h) {
            $this->Cell($this->colW[$i], 7, iso($h), 1, 0, 'C', true);
        }
        $this->Ln();
    }

    // === Helpers para filas con MultiCell (auto-ajuste y salto de página) ===
    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            if (!empty($this->colW) && count($this->colW) >= 24) {
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
        $h = 5.2 * max(1, $nb); // compacto para 24 columnas
        $this->CheckPageBreak($h);

        // Estilos por fila (zebra)
        $this->SetFillColor($fill ? 240 : 255, 255, $fill ? 240 : 255);
        $this->SetDrawColor(0, 100, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8.5);

        // Celdas
        for ($i = 0; $i < count($data); $i++) {
            $x = $this->GetX();
            $y = $this->GetY();
            $w = $this->colW[$i];
            $a = $this->colA[$i] ?? 'L';

            $this->Rect($x, $y, $w, $h);         // borde
            $this->SetXY($x + 1.2, $y + 1.2);    // padding
            $this->MultiCell($w - 2.4, 5.2, iso($data[$i]), 0, $a, true);
            $this->SetXY($x + $w, $y);           // mover a la siguiente celda
        }
        $this->Ln($h);
    }
}

// =================== CREAR PDF (A4 apaisado) ===================
$pdf = new DocentesPDF('L', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AliasNbPages();

/*
  24 columnas -> anchos muy ajustados para ~273mm útiles en A4 horizontal.
  Puedes afinar valores si lo deseas.
*/
$widths = [
    7,  // ID
    12, // Matrícula
    13, // Nombre
    11, // Ap. Paterno
    11, // Ap. Materno
    14, // CURP
    11, // RFC
    11, // F. Nac.
    8,  // Sexo
    11, // Teléfono
    14, // Dirección
    16, // Correo
    10, // Nivel
    11, // Área
    13, // Universidad
    10, // Cédula
    11, // Idiomas
    11, // Puesto
    11, // Tipo Contrato
    11, // F. Ingreso
    11, // Contacto Emerg.
    10, // Parentesco
    10, // Tel. Emerg.
    11  // F. Registro
];
$aligns = [
    'C',
    'L',
    'L',
    'L',
    'L',
    'L',
    'L',
    'C',
    'C',
    'L',
    'L',
    'L',
    'L',
    'L',
    'L',
    'L',
    'L',
    'L',
    'L',
    'C',
    'L',
    'L',
    'C',
    'C'
];

$pdf->setTableStyle($widths, $aligns);

// Agregar página
$pdf->AddPage();

// Pintar filas
$fill = false;
foreach ($rows as $r) {
    $pdf->Row([
        $r['id_docente'],
        $r['matricula'],
        $r['nombre'],
        $r['apellido_paterno'],
        $r['apellido_materno'],
        $r['curp'],
        $r['rfc'],
        $r['fecha_nacimiento'],
        $r['sexo'],
        $r['telefono'],
        $r['direccion'],
        $r['correo_personal'],
        $r['nivel_estudios'],
        $r['area_especialidad'],
        $r['universidad_egreso'],
        $r['cedula_profesional'],
        $r['idiomas'],
        $r['puesto'],
        $r['tipo_contrato'],
        $r['fecha_ingreso'],
        $r['contacto_emergencia'],
        $r['parentesco_emergencia'],
        $r['telefono_emergencia'],
        $r['fecha_registro']
    ], $fill);
    $fill = !$fill;
}

// Limpieza del buffer (por si algo imprimió antes)
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}

// Descargar
$pdf->Output('D', 'Docentes.pdf');
exit;