<?php
require_once __DIR__ . '/../conexion/conexion.php';
// 🔹 Desconecta docentes inactivos más de 40 segundos
$conn->query("
UPDATE docentes
SET en_linea = FALSE
WHERE TIMESTAMPDIFF(SECOND, ultima_actividad, NOW()) > 40
");
// 🔹 Desconecta alumnos inactivos más de 40 segundos
$conn->query("
UPDATE alumnos
SET en_linea = FALSE
WHERE TIMESTAMPDIFF(SECOND, ultima_actividad, NOW()) > 40
");