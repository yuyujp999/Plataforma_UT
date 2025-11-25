<?php
// /controladores/alumnos/AdeudosController.php

class AdeudosController
{
    /**
     * Obtiene todos los registros de la tabla `pagos`
     * para el alumno indicado (usando su id_alumno -> matrícula).
     */
    public static function obtenerAdeudos(int $idAlumno): array
    {
        if (!$idAlumno) {
            return [];
        }

        include __DIR__ . '/../../conexion/conexion.php'; // $conn (MySQLi)

        // 1) Obtener matrícula real del alumno
        $sqlMat = "SELECT matricula, nombre, apellido_paterno, apellido_materno 
                   FROM alumnos 
                   WHERE id_alumno = ?";
        $stmtMat = $conn->prepare($sqlMat);
        if (!$stmtMat) {
            return [];
        }

        $stmtMat->bind_param("i", $idAlumno);
        $stmtMat->execute();
        $resMat = $stmtMat->get_result();
        $al = $resMat->fetch_assoc();

        if (!$al || empty($al['matricula'])) {
            return [];
        }

        $matricula = $al['matricula'];

        // 2) Traer los pagos de la tabla `pagos` como en el dash de secretaría,
        //    pero filtrados por la matrícula del alumno.
        $sql = "
            SELECT 
                p.id,
                p.matricula,
                p.periodo,
                p.concepto,
                p.monto,
                p.adeudo,
                p.pago,
                p.condonacion,
                p.fecha_registro
            FROM pagos p
            WHERE p.matricula = ?
            ORDER BY p.fecha_registro DESC, p.id DESC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $res = $stmt->get_result();

        $adeudos = [];
        while ($row = $res->fetch_assoc()) {
            $monto       = (float)($row['monto'] ?? 0);
            $adeudo      = (float)($row['adeudo'] ?? 0);
            $pago        = (float)($row['pago'] ?? 0);
            $condonacion = (float)($row['condonacion'] ?? 0);

            // 3) Calcular un estado amigable (Pagado / Parcial / Pendiente)
            if ($adeudo <= 0.009 || $monto <= ($pago + $condonacion + 0.009)) {
                $estado = 'Pagado';
            } elseif ($pago > 0 || $condonacion > 0) {
                $estado = 'Parcial';
            } else {
                $estado = 'Pendiente';
            }

            $adeudos[] = [
                'id'             => (int)$row['id'],
                'matricula'      => $row['matricula'],
                'periodo'        => $row['periodo'],
                'concepto'       => $row['concepto'],
                'monto'          => $monto,
                'adeudo'         => $adeudo,
                'pago'           => $pago,
                'condonacion'    => $condonacion,
                'fecha_registro' => $row['fecha_registro'],
                'estado'         => $estado,
            ];
        }

        return $adeudos;
    }
}
