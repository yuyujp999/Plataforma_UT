<?php
class AdeudosController
{
    public static function obtenerAdeudos($idAlumno)
    {
        // 🔹 Por ahora devolvemos datos de ejemplo (sin conexión a BD aún)
        return [
            [
                "concepto" => "Pago de reinscripción",
                "fecha_limite" => "2025-01-10",
                "monto" => 850.00,
                "estado" => "Pendiente"
            ],
            [
                "concepto" => "Pago de credencial universitaria",
                "fecha_limite" => "2025-03-05",
                "monto" => 120.00,
                "estado" => "Pagado"
            ],
            [
                "concepto" => "Cuota de laboratorio",
                "fecha_limite" => "2025-02-15",
                "monto" => 300.00,
                "estado" => "Pendiente"
            ]
        ];
    }
}
