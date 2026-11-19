<?php
/** Descarga la plantilla CSV del importador, con una fila de ejemplo. */
require __DIR__ . '/eqr_common.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_equipos_rentados.csv"');

$cols = ['codigo_interno', 'tipo', 'marca', 'modelo', 'procesador', 'ram', 'almacenamiento',
    'pantalla', 'serie', 'cargador_entregado', 'serie_cargador', 'estado_fisico',
    'estado_operativo', 'observaciones', 'custodio_cedula', 'custodio_nombre',
    'custodio_correo', 'custodio_cargo', 'custodio_area', 'referencia_acta'];

$ejemplo = ['REN-0001', 'Computador portátil', 'DELL', 'Latitude 5430',
    'Intel Core i7-1265U (12ª Gen)', '32GB', '512 GB', '14" FHD IPS', 'BDDMCK3',
    'Si', 'TH0HRR76CHG00',
    'Equipo nuevo, sin golpes ni piezas faltantes al momento de la entrega.',
    'Equipo encendido y operativo al momento de la entrega.',
    '', '1710000001', 'APELLIDOS NOMBRES DEL CUSTODIO', 'usuario@arconel.gob.ec',
    'Analista', 'Dirección de Ejemplo', 'ACTA-001-2026'];

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");            // BOM para que Excel abra UTF-8 bien
fputcsv($out, $cols);
fputcsv($out, $ejemplo);
fclose($out);
