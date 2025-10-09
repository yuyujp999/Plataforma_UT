<?php
require_once __DIR__ . '/../../conexion/conexion.php'; // Asegúrate de que $conn exista

// --- Validar tipo ---
$tipo = strtolower($_GET['tipo'] ?? '');
if (!in_array($tipo, ['pdf', 'excel'])) {
    http_response_code(400);
    exit('Tipo no válido (pdf|excel).');
}

// --- Obtener datos ---
$sql = "SELECT id_docente, nombre, apellido_paterno, apellido_materno, curp, rfc,
        fecha_nacimiento, sexo, telefono, direccion, correo_personal, matricula, password,
        nivel_estudios, area_especialidad, universidad_egreso, cedula_profesional, idiomas,
        departamento, puesto, tipo_contrato, fecha_ingreso, num_empleado,
        contacto_emergencia, parentesco_emergencia, telefono_emergencia, fecha_registro
        FROM docentes";
$result = $conn->query($sql);
if (!$result) {
    die("Error al consultar: " . $conn->error);
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}
$conn->close();

// =================== EXCEL ===================
if ($tipo === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Docentes.csv"');

    $output = fopen('php://output', 'w');

    // Encabezados
    fputcsv($output, [
        'ID',
        'Nombre',
        'Apellido Paterno',
        'Apellido Materno',
        'CURP',
        'RFC',
        'Fecha Nacimiento',
        'Sexo',
        'Teléfono',
        'Dirección',
        'Correo',
        'Matrícula',
        'Contraseña',
        'Nivel Estudios',
        'Área Especialidad',
        'Universidad Egreso',
        'Cédula Profesional',
        'Idiomas',
        'Departamento',
        'Puesto',
        'Tipo Contrato',
        'Fecha Ingreso',
        'Num. Empleado',
        'Contacto Emergencia',
        'Parentesco Emergencia',
        'Teléfono Emergencia',
        'Fecha Registro'
    ]);

    // Datos
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id_docente'],
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
            $r['matricula'],
            $r['password'],
            $r['nivel_estudios'],
            $r['area_especialidad'],
            $r['universidad_egreso'],
            $r['cedula_profesional'],
            $r['idiomas'],
            $r['departamento'],
            $r['puesto'],
            $r['tipo_contrato'],
            $r['fecha_ingreso'],
            $r['num_empleado'],
            $r['contacto_emergencia'],
            $r['parentesco_emergencia'],
            $r['telefono_emergencia'],
            $r['fecha_registro']
        ]);
    }
    fclose($output);
    exit;
}

// =================== PDF ===================
if ($tipo === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Docentes.pdf"');

    $html = "<h2 style='text-align:center;'>Lista de Docentes</h2>";
    $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; font-family:Arial; font-size:10px;'>";

    // Encabezados
    $html .= "<tr style='background-color:#28a745; color:white; text-align:center;'>";
    $html .= "<th>ID</th><th>Nombre</th><th>Apellido Paterno</th><th>Apellido Materno</th>";
    $html .= "<th>CURP</th><th>RFC</th><th>Fecha Nacimiento</th><th>Sexo</th>";
    $html .= "<th>Teléfono</th><th>Dirección</th><th>Correo</th><th>Matrícula</th>";
    $html .= "<th>Contraseña</th><th>Nivel Estudios</th><th>Área Especialidad</th><th>Universidad Egreso</th>";
    $html .= "<th>Cédula Profesional</th><th>Idiomas</th><th>Departamento</th><th>Puesto</th>";
    $html .= "<th>Tipo Contrato</th><th>Fecha Ingreso</th><th>Num. Empleado</th><th>Contacto Emergencia</th>";
    $html .= "<th>Parentesco Emergencia</th><th>Teléfono Emergencia</th><th>Fecha Registro</th>";
    $html .= "</tr>";

    // Filas de datos
    foreach ($rows as $r) {
        $html .= "<tr style='text-align:center;'>";
        $html .= "<td>{$r['id_docente']}</td><td>{$r['nombre']}</td><td>{$r['apellido_paterno']}</td><td>{$r['apellido_materno']}</td>";
        $html .= "<td>{$r['curp']}</td><td>{$r['rfc']}</td><td>{$r['fecha_nacimiento']}</td><td>{$r['sexo']}</td>";
        $html .= "<td>{$r['telefono']}</td><td>{$r['direccion']}</td><td>{$r['correo_personal']}</td><td>{$r['matricula']}</td>";
        $html .= "<td>{$r['password']}</td><td>{$r['nivel_estudios']}</td><td>{$r['area_especialidad']}</td><td>{$r['universidad_egreso']}</td>";
        $html .= "<td>{$r['cedula_profesional']}</td><td>{$r['idiomas']}</td><td>{$r['departamento']}</td><td>{$r['puesto']}</td>";
        $html .= "<td>{$r['tipo_contrato']}</td><td>{$r['fecha_ingreso']}</td><td>{$r['num_empleado']}</td><td>{$r['contacto_emergencia']}</td>";
        $html .= "<td>{$r['parentesco_emergencia']}</td><td>{$r['telefono_emergencia']}</td><td>{$r['fecha_registro']}</td>";
        $html .= "</tr>";
    }

    $html .= "</table>";
    echo $html;
    exit;
}