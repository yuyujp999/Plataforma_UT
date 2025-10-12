<?php
require_once __DIR__ . '/../../conexion/conexion.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener todas las carreras
    $stmt = $pdo->query("SELECT id_carrera, nombre_carrera, descripcion, duracion_anios, fecha_creacion FROM carreras ORDER BY id_carrera ASC");
    $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nombre del archivo
    $filename = "carreras_" . date('Ymd_His') . ".csv";

    // Headers para descarga
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Encabezados
    fputcsv($output, ['ID', 'Nombre de la Carrera', 'Descripción', 'Duración (Años)', 'Fecha de Creación']);

    // Contenido
    foreach ($carreras as $c) {
        fputcsv($output, [
            $c['id_carrera'],
            $c['nombre_carrera'],
            $c['descripcion'],
            $c['duracion_anios'],
            $c['fecha_creacion']
        ]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}